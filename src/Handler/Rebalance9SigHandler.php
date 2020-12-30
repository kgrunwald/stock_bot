<?php

namespace App\Handler;

use App\Entity\Goal;
use App\Entity\LockInterface;
use App\Entity\NineSigPlan;
use App\Entity\Order;
use App\Entity\Plan;
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
        $this->logger->info('Rebalancing goal', ['goal' => $goal->getId(), 'userId' => $goal->getUserId()]);

        $createdOrders = $this->createOrders($goal, $plan);
        $this->dbContext->commit();
        $this->lockInterface->release($lock);

        if ($createdOrders) {
            $user = $this->dbContext->users->getByAccountId($goal->getUserId());
            $msg = new SubmitOrdersMessage($user, $goal);
            $this->logger->info('Dispatching order message', ['message' => $msg]);
            $this->bus->dispatch($msg);
        }
    }

    public function createOrders(Goal $goal, NineSigPlan $plan)
    {
        $target = $plan->getTarget();

        if ($target == 0) {
            return $this->setInitialAllocation($goal, $plan);
        } else {
            return $this->signalReallocation($goal, $plan);
        }
    }

    public function setInitialAllocation(Goal $goal, NineSigPlan $plan)
    {
        $this->logger->info('Setting initial allocation', ['goal' => $goal->getId()]);

        if($goal->cashBalance() <= 0) {
            $this->logger->error('Goal does not have allocated cash', ['goal' => $goal->getId()]);
            return false;
        }
        
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

        $this->logger->info('Created stock buy order', ['order' => $stockOrder, 'goal' => $goal->getId()]);
        
        $bondOrder = new Order();
        $bondOrder->setSecurity($bondHolding->getSecurity())
                  ->setSide(Order::SIDE_BUY)
                  ->setQty(-1);

        $this->logger->info('Created bond buy order', ['order' => $bondOrder, 'goal' => $goal->getId()]);
        
        $goal->addOrder($stockOrder);
        $goal->addOrder($bondOrder);
        $this->updatePlanTarget($plan, $stockTarget);

        $this->dbContext->goals->add($goal);
        return true;
    }

    public function signalReallocation(Goal $goal, NineSigPlan $plan)
    {
        $target = $plan->getTarget();

        $stockHolding = $goal->holdingBySymbol(NineSigPlan::STOCK_FUND);
        $bondHolding = $goal->holdingBySymbol(NineSigPlan::BOND_FUND);

        if ($stockHolding->getValue() < $target) {
            $this->logger->info('Stock balance below target', ['goal' => $goal->getId(), 'balance' => $stockHolding->getValue(), 'target' => $target]);
            $sellHolding = $bondHolding;
            $buyHolding = $stockHolding;
            $neededCash = ($target - $stockHolding->getValue()) - $goal->cashBalance();
        } else {
            $this->logger->info('Stock balance above target', ['goal' => $goal->getId(), 'balance' => $stockHolding->getValue(), 'target' => $target]);
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
        $this->logger->info('Created sell order', ['order' => $sellOrder, 'goal' => $goal->getId()]);

        $buyOrder = new Order();
        $buyOrder->setSecurity($buyHolding->getSecurity())
                 ->setSide(Order::SIDE_BUY)
                 ->setQty(-1);
        $this->logger->info('Created buy order', ['order' => $buyOrder, 'goal' => $goal->getId()]);

        $goal->addOrder($sellOrder);
        $goal->addOrder($buyOrder);
        $this->updatePlanTarget($plan, $target);

        $this->dbContext->goals->add($goal);
        return true;
    }

    public function updatePlanTarget(NineSigPlan $plan, int $currentTarget)
    {
        $plan->setTarget(intval(ceil($currentTarget * 1.09)));
        $this->logger->info('Updated plan target', ['plan' => $plan->getId(), 'target' => $plan->getTarget()]);
    }
}
