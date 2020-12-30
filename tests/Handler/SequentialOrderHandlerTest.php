<?php

namespace App\Test\Handler;

use App\Entity\Goal;
use App\Entity\Holding;
use App\Entity\LockInterface;
use App\Entity\Order;
use App\Entity\Security;
use App\Handler\SequentialOrderHandler;
use App\Repository\DbContext;
use App\Repository\GoalRepository;
use App\Security\SecurityService;
use App\Service\BrokerService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class SequentialOrderHandlerTest extends TestCase
{
    private Goal $goal;

    public function setUp()
    {
        $this->security = $this->createMock(SecurityService::class);
        $this->bus = $this->createMock(MessageBusInterface::class);
        $this->dbContext = $this->createMock(DbContext::class);
        $this->brokerService = $this->createMock(BrokerService::class);
        $this->lockInterface = $this->createMock(LockInterface::class);
        $this->goalRepo = $this->createMock(GoalRepository::class);
        $this->dbContext->goals = $this->goalRepo;
        $logger = $this->createMock(LoggerInterface::class);

        $this->goal = new Goal();

        $this->handler = new SequentialOrderHandler($this->security, $this->bus, $this->dbContext, $this->brokerService, $this->lockInterface, $logger);
    }

    public function testSubmitOrderNoCash()
    {
        $security = new Security();
        $security->setSymbol('TQQQ');
        $order = new Order();
        $order->setQty(1);
        $order->setLimitPrice(1);
        $order->setSecurity($security);

        $this->goal->addOrder($order);

        $this->assertFalse($this->handler->submitOrder($this->goal, $order));
    }

    public function testSubmitOrder()
    {
        $security = new Security();
        $security->setSymbol('TQQQ');
        $order = new Order();
        $order->setQty(1);
        $order->setLimitPrice(1);
        $order->setSecurity($security);

        $this->goal->addOrder($order);

        $cash = $this->goal->holdingBySymbol(Security::CASH);
        $cash->setValue(1);

        $this->brokerService->expects($this->once())->method('submitLimitOrder')->with($order)->willReturn(['id' => 'testId']);
        $this->assertTrue($this->handler->submitOrder($this->goal, $order));
        $this->assertCount(1, $this->goal->getOrders());
        $this->assertEquals('testId', $this->goal->getOrders()[0]->getExternalId());
    }

    public function testSubmitOrderCashBalance()
    {
        $security = new Security();
        $security->setSymbol('TQQQ');
        $order = new Order();
        $order->setQty(-1);
        $order->setLimitPrice(1);
        $order->setSecurity($security);

        $this->goal->holdingBySymbol(Security::CASH)->setValue(10000);
        $this->goal->addOrder($order);

        $this->brokerService->expects($this->once())->method('submitLimitOrder')->with($order)->willReturn(['id' => 'testId']);
        $this->brokerService->expects($this->once())->method('getCurrentAsk')->with($security)->willReturn(1000);
        $this->handler->submitOrder($this->goal, $order);
        
        $this->assertGreaterThan(0, $order->getQty());
    }

    public function testSubmitOrder0Quantity()
    {
        $security = new Security();
        $security->setSymbol('TQQQ');
        $order = new Order();
        $order->setQty(0);
        $order->setLimitPrice(1);
        $order->setSecurity($security);

        $this->goal->addOrder($order);

        $this->goalRepo->expects($this->once())->method('removeOrder')->with($this->goal, $order);
        $this->handler->submitOrder($this->goal, $order);
    }

    /**
     * @dataProvider orderQuantityFromCashData
     */
    public function testCalculateOrderQuantityFromCash($cashBalance, $bid, $qty, $limit)
    {
        $cash = $this->goal->holdingBySymbol(Security::CASH);
        $cash->setValue($cashBalance);

        $security = new Security();
        $security->setSymbol('TQQQ');
        $order = new Order();
        $order->setSecurity($security);
        $this->goal->addOrder($order);

        $this->brokerService->expects($this->once())->method('getCurrentAsk')->with($security)->willReturn($bid);
        
        $this->handler->calculateOrderQuantityFromCash($this->goal, $order);
        
        $this->assertEquals($qty, $order->getQty());
        $this->assertEquals($limit, $order->getLimitPrice());
    }

    public function orderQuantityFromCashData() 
    {
        return [
            // cash, bid, qty, limit
            [12500, 2700, 4, 3125],
            [12500, 2500, 5, 2500],
            [12500, 12501, 0, 0],
        ];
    }

    public function testReconcileSell()
    {
        $order = new Order();
        $security = new Security();
        $security->setSymbol('TQQQ');
        $order->setSecurity($security);
        $holding = new Holding();
        $holding->setSecurity($security);
        $this->goal->addHolding($holding);
        $this->goal->addOrder($order);

        $this->brokerService->expects($this->once())->method('getOrderDetails')->with($order)->willReturn(self::ORDER_DETAILS);
        $this->handler->reconcile($this->goal, $order);

        $this->assertEquals(Order::STATUS_FILLED, $order->getStatus());
        $this->assertEquals(15, $order->getQty());
        $this->assertEquals(10600, $order->getAvgPrice());
        $this->assertEquals($order->getQty() * $order->getAvgPrice(), $this->goal->cashBalance());
        $this->assertTrue($order->isReconciled());
    }

    public function testReconcileBuy()
    {
        $order = new Order();
        $security = new Security();
        $security->setSymbol('TQQQ');
        $order->setSecurity($security);
        $holding = new Holding();
        $holding->setSecurity($security);
        $this->goal->addHolding($holding);
        $this->goal->addOrder($order);

        $this->brokerService->expects($this->once())->method('getOrderDetails')->with($order)->willReturn(array_merge(self::ORDER_DETAILS, ['side' => 'buy']));
        $this->handler->reconcile($this->goal, $order);
        $this->assertEquals(-1 * $order->getQty() * $order->getAvgPrice(), $this->goal->cashBalance());
    }

    public function testReconcilePartialFill()
    {
        $order = new Order();
        $this->goal->addOrder($order);

        $this->brokerService->expects($this->once())->method('getOrderDetails')->with($order)->willReturn(array_merge(self::ORDER_DETAILS, ['status' => 'partially_filled']));
        $this->handler->reconcile($this->goal, $order);
        
        $this->assertEquals(Order::STATUS_PARTIALLY_FILLED, $order->getStatus());
        $this->assertEquals(0, $this->goal->cashBalance());
        $this->assertFalse($order->isReconciled());
        $this->assertEquals(0, $order->getAvgPrice());
    }

    const ORDER_DETAILS = [
        "id" => "904837e3-3b76-47ec-b432-046db621571b",
        "client_order_id"=> "904837e3-3b76-47ec-b432-046db621571b",
        "created_at"=> "2018-10-05T05:48:59Z",
        "updated_at"=> "2018-10-05T05:48:59Z",
        "submitted_at"=> "2018-10-05T05:48:59Z",
        "filled_at"=> "2018-10-05T05:48:59Z",
        "expired_at"=> "2018-10-05T05:48:59Z",
        "canceled_at"=> "2018-10-05T05:48:59Z",
        "failed_at"=> "2018-10-05T05:48:59Z",
        "replaced_at"=> "2018-10-05T05:48:59Z",
        "replaced_by"=> "904837e3-3b76-47ec-b432-046db621571b",
        "replaces"=> null,
        "asset_id"=> "904837e3-3b76-47ec-b432-046db621571b",
        "symbol"=> "AAPL",
        "asset_class"=> "us_equity",
        "qty"=> "15",
        "filled_qty"=> "15",
        "type"=> "market",
        "side"=> "sell",
        "time_in_force"=> "day",
        "limit_price"=> "107.00",
        "stop_price"=> "106.00",
        "filled_avg_price"=> "106.00",
        "status"=> "filled",
        "extended_hours"=> false,
        "legs"=> null,
        "trail_price"=> "1.05",
        "trail_percent"=> null,
        "hwm"=> "108.05"
    ];
}