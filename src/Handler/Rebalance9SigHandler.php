<?php

namespace App\Handler;

use App\Entity\Goal;
use App\Entity\LockInterface;
use App\Entity\NineSigPlan;
use App\Entity\Order;
use App\Handler\Messages\SubmitOrdersMessage;
use App\Repository\DbContext;
use App\Service\BrokerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class Rebalance9SigHandler
{
    private BrokerService $brokerService;
    private MessageBusInterface $bus;
    private LoggerInterface $logger;
    private DbContext $dbContext;
    private LockInterface $lockInterface;

    public function __construct(BrokerService $brokerService, MessageBusInterface $bus, LoggerInterface $logger, DbContext $dbContext, LockInterface $lockInterface)
    {
        $this->brokerService = $brokerService;
        $this->bus = $bus;
        $this->logger = $logger;
        $this->dbContext = $dbContext;
        $this->lockInterface = $lockInterface;
    }

    public function __invoke()
    {
        $p = $this->dbContext->plans->getByName(NineSigPlan::NAME);
        $goals = $this->dbContext->goals->getAllByPlan($p);

        /** @var Goal $goal */
        foreach($goals as $goal) {
            $this->rebalance($goal, $goal->getPlan());
        }
    }

    public function rebalance(Goal $goal, NineSigPlan $plan)
    {
        $lock = $this->lockInterface->acquire($goal);
        $createdOrders = $this->createOrders($goal, $plan);
        $this->dbContext->commit();
        $this->lockInterface->release($lock);

        if ($createdOrders) {
            $this->bus->dispatch(new SubmitOrdersMessage($goal));
        }
    }

    public function createOrders(Goal $goal, NineSigPlan $plan)
    {
        $target = $plan->getTarget();

        if ($target === 0) {
            return $this->setInitialAllocation($goal, $plan);
        } else {
            return $this->signalReallocation($goal, $target);
        }
    }

    public function setInitialAllocation(Goal $goal, NineSigPlan $plan)
    {
        $stockHolding = $goal->holdingBySymbol(NineSigPlan::STOCK_FUND);
        $bondHolding = $goal->holdingBySymbol(NineSigPlan::BOND_FUND);

        $balance = $goal->cashBalance();
        $stockTarget = intval($balance * 0.6);

        $stockAction = $this->brokerService->getOrderToBuyAmount($stockHolding, $stockTarget);
        $stockOrder = new Order();
        $stockOrder->setSecurity($stockHolding->getSecurity())
                   ->setSide($stockAction['side'])
                   ->setQty($stockAction['qty'])
                   ->setLimitPrice($stockAction['limit']);
        
        $bondOrder = new Order();
        $bondOrder->setSecurity($bondHolding->getSecurity())
                  ->setSide(Order::SIDE_BUY)
                  ->setQty(-1);
        
        $goal->addOrder($stockOrder);
        $goal->addOrder($bondOrder);
        $plan->setTarget(intval(ceil($stockTarget * 1.09)));
        $this->dbContext->goals->add($goal);
        return true;
    }

    public function signalReallocation(Goal $goal, int $target)
    {
        $stockHolding = $goal->holdingBySymbol(NineSigPlan::STOCK_FUND);
        $bondHolding = $goal->holdingBySymbol(NineSigPlan::BOND_FUND);

        if ($stockHolding->getValue() < $target) {
            $this->logger->info('Stock balance below target');
            $sellHolding = $bondHolding;
            $buyHolding = $stockHolding;
            $neededCash = ($target - $stockHolding->getValue()) - $goal->cashBalance();
        } else {
            $this->logger->info('Stock balance above target');
            $sellHolding = $stockHolding;
            $buyHolding = $bondHolding;
            $neededCash = $stockHolding->getValue() - $target;
        }

        $action = $this->brokerService->getOrderToSellAmount($sellHolding, $neededCash);

        $sellOrder = new Order();
        $sellOrder->setSecurity($sellHolding->getSecurity())
                  ->setSide(Order::SIDE_SELL)
                  ->setQty($action['qty'])
                  ->setLimitPrice($action['limit']);

        $buyOrder = new Order();
        $buyOrder->setSecurity($buyHolding->getSecurity())
                 ->setSide(Order::SIDE_BUY)
                 ->setQty(-1);

        $goal->addOrder($sellOrder);
        $goal->addOrder($buyOrder);
        $this->dbContext->goals->add($goal);
        return true;
    }
}
