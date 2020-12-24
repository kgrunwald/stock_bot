<?php

namespace App\Handler;

use App\Entity\Goal;
use App\Entity\LockInterface;
use App\Entity\Order;
use App\Entity\Security;
use App\Handler\Messages\ReconcileGoalMessage;
use App\Handler\Messages\SubmitOrdersMessage;
use App\Repository\DbContext;
use App\Service\BrokerService;
use Exception;
use Symfony\Component\Messenger\MessageBusInterface;

class SequentialOrderHandler
{
    private MessageBusInterface $bus;
    private DbContext $dbContext;
    private BrokerService $brokerService;
    private LockInterface $lockInterface;

    public function __construct(MessageBusInterface $bus, DbContext $dbContext, BrokerService $brokerService, LockInterface $lockInterface)
    {
        $this->dbContext = $dbContext;
        $this->bus = $bus;
        $this->brokerService = $brokerService;
        $this->lockInterface = $lockInterface;
    }

    public function __invoke(SubmitOrdersMessage $message)
    {
        $goal = $this->dbContext->goals->getById($message->goalId);
        $lock = $this->lockInterface->acquire($goal);

        $pending = false;
        foreach ($goal->orders as $order) {
            if (!$order->getExternalId()) {
                $this->submitOrder($goal, $order);
                $this->sendMessage($message);
                $pending = true;
                break;
            }

            if (!$order->isReconciled()) {
                $this->reconcile($goal, $order);
            }

            $status = $order->getStatus();
            if ($status === Order::STATUS_FILLED) {
                continue;
            }

            switch ($status) {
                case Order::STATUS_NEW:
                case Order::STATUS_PARTIALLY_FILLED:
                case Order::STATUS_PENDING_CANCEL:
                case Order::STATUS_PENDING_REPLACE:
                    $this->sendMessage($message);
                    $pending = true;
                    break;
                case Order::STATUS_EXPIRED:
                case Order::STATUS_DONE_FOR_DAY:
                    throw new Exception('Unexpected order state: ' . $status);
                default:
                    continue 2; // continue the outer loop, not the switch statement
            }
        }

        $this->dbContext->commit();
        if (!$pending) {
            $this->bus->dispatch(new ReconcileGoalMessage($goal));
        }
    }

    public function submitOrder(Goal $goal, Order $order)
    {
        if ($order->getQty() === -1) {
            $this->calculateOrderQuantityFromCash($goal, $order);
        }

        if ($order->getQty() === 0 || $order->getLimitPrice() === 0) {
            $this->dbContext->goals->removeOrder($goal, $order);
            return;
        }

        $res = $this->brokerService->submitLimitOrder($order);
        $order->setExternalId($res['id']);

        $this->dbContext->goals->add($goal);
    }

    public function calculateOrderQuantityFromCash(Goal $goal, Order $order)
    {
        $cash = $goal->cashBalance();
        $bid = $this->brokerService->getCurrentAsk($order->getSecurity());
        $qty = floor($cash / $bid);
        $order->setQty($qty);

        if ($qty > 0) {
            $order->setLimitPrice(floor($cash / $qty * 100) / 100);
        } else {
            $order->setLimitPrice(0);
        }
    }

    public function reconcile(Goal $goal, Order $order)
    {
        $details = $this->brokerService->getOrderDetails($order);
        $order->setStatus($details['status']);
        $order->setSide($details['side']);

        if ($order->getStatus() === Order::STATUS_FILLED) {
            $order->setQty(intval($details['filled_qty']));
            $order->setAvgPrice(intval(floatval($details['filled_avg_price']) * 100));
            $order->setReconciled(true);

            $multiplier = $order->getSide() === Order::SIDE_BUY ? -1 : 1;
            $cash = $goal->holdingBySymbol(Security::CASH);
            $cash->setValue($cash->getValue() + $order->getQty() * $order->getAvgPrice() * $multiplier);
            $goal->addHolding($cash);

            $holding = $goal->holdingBySymbol($order->getSecurity()->getSymbol());
            $holding->setQuantity($holding->getQuantity() + $order->getQty() * $multiplier);
        }

        $this->dbContext->goals->add($goal);
    }

    public function sendMessage(SubmitOrdersMessage $message)
    {
        $this->bus->dispatch($message);
    }
}
