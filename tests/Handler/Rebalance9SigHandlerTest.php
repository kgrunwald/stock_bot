<?php

namespace App\Tests\Handler;

use App\Entity\Goal;
use App\Entity\Holding;
use App\Entity\Lock;
use App\Entity\LockInterface;
use App\Entity\NineSigPlan;
use App\Entity\Security;
use App\Entity\User;
use App\Handler\Messages\SubmitOrdersMessage;
use App\Handler\Rebalance9SigHandler;
use App\Repository\DbContext;
use App\Repository\GoalRepository;
use App\Repository\PlanRepository;
use App\Repository\UserRepository;
use App\Service\BrokerService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class Rebalance9SigHandlerTest extends TestCase
{
    private Rebalance9SigHandler $handler;

    public function setUp()
    {
        $this->brokerService = $this->createMock(BrokerService::class);
        $this->bus = $this->getMockBuilder(MessageBusInterface::class)->disableOriginalConstructor()->setMethods(['dispatch'])->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $this->goalRepo = $this->createMock(GoalRepository::class);
        $this->planRepo = $this->createMock(PlanRepository::class);
        $this->userRepo = $this->createMock(UserRepository::class);
        $this->dbContext = $this->createMock(DbContext::class);
        $this->dbContext->goals = $this->goalRepo;
        $this->dbContext->plans = $this->planRepo;
        $this->dbContext->users = $this->userRepo;

        $this->lockInterface = $this->createMock(LockInterface::class);

        $this->handler = new Rebalance9SigHandler($this->brokerService, $this->bus, $this->logger, $this->dbContext, $this->lockInterface);
    }

    public function testInvoke()
    {
        $p = new NineSigPlan();

        $g = new Goal();

        $goalPlan = new NineSigPlan();
        $g->setPlan($goalPlan);

        $this->planRepo->expects($this->once())->method('getByName')->with(NineSigPlan::NAME)->willReturn($p);
        $this->goalRepo->expects($this->once())->method('getAllByPlan')->with($p)->willReturn([$g]);
        
        $handler = $this->getMockBuilder(Rebalance9SigHandler::class)->setConstructorArgs([$this->brokerService, $this->bus, $this->logger, $this->dbContext, $this->lockInterface])->setMethods(['rebalance'])->getMock();
        $handler->expects($this->once())->method('rebalance')->with($g, $goalPlan);

        $handler();
    }

    public function testRebalance()
    {
        $u = new User();
        $u->setId('test@test.com');

        $plan = new NineSigPlan();
        $plan->setName('Test Plan');

        $goal = new Goal();
        $goal->setPlan($plan);
        $goal->setUserId($u->getId());

        $handler = $this->getMockBuilder(Rebalance9SigHandler::class)->setConstructorArgs([$this->brokerService, $this->bus, $this->logger, $this->dbContext, $this->lockInterface])->setMethods(['createOrders'])->getMock();
        
        $lock = new Lock('test', 'test', $this->createMock(LockInterface::class));
        $this->lockInterface->expects($this->once())->method('acquire')->with($goal)->willReturn($lock);
        $this->lockInterface->expects($this->once())->method('release')->with($lock);
        $this->bus->expects($this->once())->method('dispatch')->with($this->callback(function($arg) { return $arg instanceof SubmitOrdersMessage; }))->willReturn(new Envelope(new stdClass()));;
        $handler->expects($this->once())->method('createOrders')->with($goal, $plan)->willReturn(true);
        $this->userRepo->expects($this->once())->method('getByAccountId')->with('test@test.com')->willReturn($u);
        
        $handler->rebalance($goal, $plan);
    }

    public function testCreateOrdersAllocatesIfNoTarget()
    {
        $plan = new NineSigPlan();
        $plan->setName('Test Plan');

        $goal = new Goal();
        $goal->setPlan($plan);

        $handler = $this->getMockBuilder(Rebalance9SigHandler::class)->disableOriginalConstructor()->setMethods(['setInitialAllocation'])->getMock();
        $handler->expects($this->once())->method('setInitialAllocation')->with($goal, $plan);
        $handler->createOrders($goal, $plan);
    }

    public function testCreateOrdersRebalancesIfTarget()
    {
        $plan = new NineSigPlan();
        $plan->setName('Test Plan');
        $plan->setTarget(1);

        $goal = new Goal();
        $goal->setPlan($plan);

        $handler = $this->getMockBuilder(Rebalance9SigHandler::class)->disableOriginalConstructor()->setMethods(['signalReallocation'])->getMock();
        $handler->expects($this->once())->method('signalReallocation')->with($goal, $plan);
        $handler->createOrders($goal, $plan);
    }

    public function testInitialAllocationNoCash()
    {
        $plan = new NineSigPlan();
        $plan->setName('Test Plan');
        $plan->setTarget(12345.67);

        $goal = new Goal();
        $goal->setPlan($plan);

        $this->assertFalse($this->handler->setInitialAllocation($goal, $plan));
    }

    public function testInitialAllocation()
    {
        $plan = new NineSigPlan();
        $plan->setName('Test Plan');

        $goal = new Goal();
        $goal->setPlan($plan);
        $cash = $goal->holdingBySymbol(Security::CASH);
        $cash->setValue(10000);

        $agg = new Holding();
        $aggSecurity = new Security();
        $aggSecurity->setSymbol('AGG');
        $agg->setSecurity($aggSecurity);

        $tqqq = new Holding();
        $security = new Security();
        $security->setSymbol('TQQQ');
        $tqqq->setSecurity($security);

        $goal->addHolding($tqqq);
        $goal->addHolding($agg);

        $this->brokerService->expects($this->once())
                            ->method('getOrderToBuyAmount')
                            ->with($tqqq, 6000)
                            ->willReturn([
                                'side' => 'sell',
                                'qty' => 123,
                                'limit' => 456
                            ]);

        $this->assertTrue($this->handler->setInitialAllocation($goal, $plan));
        $this->assertCount(2, $goal->getOrders());

        $this->assertEquals($security, $goal->getOrders()[0]->getSecurity());
        $this->assertEquals(123, $goal->getOrders()[0]->getQty());
        $this->assertEquals(456, $goal->getOrders()[0]->getLimitPrice());

        $this->assertEquals($aggSecurity, $goal->getOrders()[1]->getSecurity());
        $this->assertEquals(-1, $goal->getOrders()[1]->getQty());
    }

    public function testRebalanceAboveTarget()
    {
        $plan = new NineSigPlan();
        $plan->setName('Test Plan');
        $plan->setTarget(12345.67);

        $goal = new Goal();
        $goal->setPlan($plan);

        $agg = new Holding();
        $aggSecurity = new Security();
        $aggSecurity->setSymbol('AGG');
        $agg->setSecurity($aggSecurity);
        $agg->setValue(555.43);

        $tqqq = new Holding();
        $security = new Security();
        $security->setSymbol('TQQQ');
        $tqqq->setSecurity($security);
        $tqqq->setValue(13456.78);

        $goal->addHolding($tqqq);
        $goal->addHolding($agg);

        $this->brokerService->expects($this->once())
                            ->method('getOrderToSellAmount')
                            ->with($tqqq, $tqqq->getValue() - $plan->getTarget())
                            ->willReturn([
                                'side' => 'sell',
                                'qty' => 123,
                                'limit' => 456
                            ]);

        $this->goalRepo->expects($this->once())->method('add')->with($this->equalTo($goal));

        $this->handler->createOrders($goal, $plan);

        $this->assertCount(2, $goal->getOrders());
        $sell = $goal->getOrders()[0];
        $this->assertEquals('sell', $sell->getSide());
        $this->assertEquals(123, $sell->getQty());
        $this->assertEquals(456, $sell->getLimitPrice());
        $this->assertEquals($security, $sell->getSecurity());

        $buy = $goal->getOrders()[1];
        $this->assertEquals('buy', $buy->getSide());
        $this->assertEquals(-1, $buy->getQty());
        $this->assertEquals($aggSecurity, $buy->getSecurity());
    }

    public function testRebalanceBelowTarget()
    {
        $plan = new NineSigPlan();
        $plan->setName('Test Plan');
        $plan->setTarget(12345.67);

        $goal = new Goal();
        $goal->setPlan($plan);

        $agg = new Holding();
        $aggSecurity = new Security();
        $aggSecurity->setSymbol('AGG');
        $agg->setSecurity($aggSecurity);
        $agg->setValue(555.43);

        $tqqq = new Holding();
        $security = new Security();
        $security->setSymbol('TQQQ');
        $tqqq->setSecurity($security);
        $tqqq->setValue(10456.78);

        $goal->addHolding($tqqq);
        $goal->addHolding($agg);

        $this->brokerService->expects($this->once())
                            ->method('getOrderToSellAmount')
                            ->with($agg, $plan->getTarget() - $tqqq->getValue())
                            ->willReturn([
                                'side' => 'sell',
                                'qty' => 123,
                                'limit' => 456
                            ]);

        $this->goalRepo->expects($this->once())->method('add')->with($this->equalTo($goal));

        $this->handler->createOrders($goal, $plan);

        $this->assertCount(2, $goal->getOrders());
        $sell = $goal->getOrders()[0];
        $this->assertEquals('sell', $sell->getSide());
        $this->assertEquals(123, $sell->getQty());
        $this->assertEquals(456, $sell->getLimitPrice());
        $this->assertEquals($aggSecurity, $sell->getSecurity());

        $buy = $goal->getOrders()[1];
        $this->assertEquals('buy', $buy->getSide());
        $this->assertEquals(-1, $buy->getQty());
        $this->assertEquals($security, $buy->getSecurity());
    }
}