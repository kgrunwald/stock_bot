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
        $this->brokerService = $this->createMock(BrokerService::class);
        $this->bus = $this->getMockBuilder(MessageBusInterface::class)->disableOriginalConstructor()->setMethods(['dispatch'])->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $this->goalRepo = $this->createMock(GoalRepository::class);
        $this->dbContext = $this->createMock(DbContext::class);
        $this->dbContext->goals = $this->goalRepo;

        $this->lockInterface = $this->createMock(LockInterface::class);

        $this->handler = new Rebalance9SigHandler($this->brokerService, $this->bus, $this->logger, $this->dbContext, $this->lockInterface);
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