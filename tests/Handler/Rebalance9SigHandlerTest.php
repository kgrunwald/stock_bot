<?php

namespace App\Tests\Handler;

use App\Entity\Goal;
use App\Entity\Holding;
use App\Entity\Lock;
use App\Entity\LockInterface;
use App\Entity\NineSigPlan;
use App\Entity\Security;
use App\Handler\Messages\SubmitOrdersMessage;
use App\Handler\Rebalance9SigHandler;
use App\Repository\DbContext;
use App\Repository\GoalRepository;
use App\Service\BrokerService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class Rebalance9SigHandlerTest extends TestCase
{
    private Rebalance9SigHandler $handler;

    public function setUp()
    {
        $this->brokerService = $this->getMockBuilder(BrokerService::class)->disableOriginalConstructor()->setMethods(['getCurrentBid'])->getMock();
        $this->bus = $this->getMockBuilder(MessageBusInterface::class)->disableOriginalConstructor()->setMethods(['dispatch'])->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $this->goalRepo = $this->createMock(GoalRepository::class);
        $this->dbContext = $this->createMock(DbContext::class);
        $this->dbContext->goals = $this->goalRepo;

        $this->lockInterface = $this->createMock(LockInterface::class);

        $this->handler = new Rebalance9SigHandler($this->brokerService, $this->bus, $this->logger, $this->dbContext, $this->lockInterface);
    }

    /**
     * @dataProvider sellInfoProvider
     */
    public function testSellInfoAboveTarget($target, $value, $price, $qty, $limit)
    {
        $security = new Security();
        $security->setSymbol('TQQQ');

        $this->brokerService->expects($this->once())->method('getCurrentBid')->with($this->equalTo($security))->willReturn($price);

        $holding = new Holding();
        $holding->setSecurity($security);

        $info = $this->handler->getSellInfo($holding, $target, $value);  
        
        $this->assertEquals($qty, $info['qty']);
        $this->assertEquals($limit, $info['limit']);
    }

    public function sellInfoProvider()
    {
        return [
            [1234567, 1345678, 14322, 8, 13889],
            [10000, 11000, 1000, 1, 1000],
            [10000, 10900, 1000, 1, 900],
            [10000, 10000, 1000, 0, 99999999],
            [10000, 9500, 1000, 1, 500],
            [10000, 8700, 1000, 2, 13/2*100],
            [38471321, 32638245, 12532, 466, 12518]
        ];
    }

    public function testRebalanceAboveTarget()
    {
        $plan = new NineSigPlan();
        $plan->setName('Test Plan');
        $plan->setTarget(12345.67);

        $goal = new Goal();
        $goal->setPlan($plan);

        $agg = new Holding();
        $security = new Security();
        $security->setSymbol('AGG');
        $agg->setSecurity($security);
        $agg->setValue(555.43);

        $tqqq = new Holding();
        $security = new Security();
        $security->setSymbol('TQQQ');
        $tqqq->setSecurity($security);
        $tqqq->setValue(13456.78);

        $goal->addHolding($tqqq);
        $goal->addHolding($agg);

        $this->brokerService->expects($this->once())->method('getCurrentBid')->with($this->identicalTo($security))->willReturn(143.22);
        $this->goalRepo->expects($this->once())->method('add')->with($this->equalTo($goal));
        $this->dbContext->expects($this->once())->method('commit');

        $this->handler->createOrders($goal, $plan);
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

        $this->brokerService->expects($this->once())->method('getCurrentBid')->with($this->identicalTo($aggSecurity))->willReturn(143.22);
        $this->goalRepo->expects($this->once())->method('add')->with($this->equalTo($goal));
        $this->dbContext->expects($this->once())->method('commit');

        $this->handler->createOrders($goal, $plan);
    }

    public function testRebalanceNotNeeded()
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
        $tqqq->setValue(12345.67);

        $goal->addHolding($tqqq);
        $goal->addHolding($agg);

        $this->logger->expects($this->once())->method('info')->with('Not rebalancing');
        $this->handler->createOrders($goal, $plan);
    }

    public function testRebalanceDispatchesMessage()
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
        $tqqq->setValue(124445.67);

        $goal->addHolding($tqqq);
        $goal->addHolding($agg);
        $this->bus->expects($this->once())->method('dispatch')->with($this->callback(function($arg) use($goal) {
            $this->assertInstanceOf(SubmitOrdersMessage::class, $arg);
            $this->assertEquals($goal->getId(), $arg->goalId);
            return true;
        }))
        ->willReturn(new Envelope($goal));

        $this->brokerService->expects($this->once())->method('getCurrentBid')->with($this->identicalTo($security))->willReturn(143.22);
        $this->lockInterface->expects($this->once())->method('acquire')->willReturn(new Lock('', $goal->getId(), $this->lockInterface));
        $this->handler->rebalance($goal, $plan);
    }
}