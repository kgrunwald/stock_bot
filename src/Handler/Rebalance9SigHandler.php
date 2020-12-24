<?php

namespace App\Handler;

use App\Entity\Goal;
use App\Entity\Holding;
use App\Entity\LockInterface;
use App\Entity\NineSigPlan;
use App\Entity\Order;
use App\Handler\Messages\SequentialOrderMessage;
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
        $g = new Goal();        
    }

    public function rebalance(Goal $goal, NineSigPlan $plan)
    {
        $lock = $this->lockInterface->acquire($goal);
        $created = $this->createOrders($goal, $plan);
        $this->lockInterface->release($lock);

        if($created) {
            $this->bus->dispatch(new SubmitOrdersMessage($goal->getId()));
        }
    }

    public function createOrders(Goal $goal, NineSigPlan $plan)
    {
        $target = $plan->getTarget();
        $stockHolding = $goal->holdingBySymbol(NineSigPlan::STOCK_FUND);
        $bondHolding = $goal->holdingBySymbol(NineSigPlan::BOND_FUND);

        $value = $stockHolding->getValue();

        if ($value < $target) {    
            $this->logger->info('Stock balance below target');
            $sellHolding = $bondHolding;
            $buyHolding = $stockHolding;
        } else if ($value > $target) {
            $this->logger->info('Stock balance above target');
            $sellHolding = $stockHolding;
            $buyHolding = $bondHolding;
        } else {
            $this->logger->info('Not rebalancing');
            return false;
        }

        $info = $this->getSellInfo($sellHolding, $target, $value);
        $sellOrder = new Order();
        $sellOrder->setSecurity($sellHolding->getSecurity())->setSide(Order::SIDE_SELL)->setQty($info['qty'])->setLimitPrice($info['limit']);

        $buyOrder = new Order();
        $buyOrder->setSecurity($buyHolding->getSecurity())->setSide(Order::SIDE_BUY)->setQty(-1);

        $goal->addOrder($sellOrder);
        $goal->addOrder($buyOrder);
        $this->dbContext->goals->add($goal);
        $this->dbContext->commit();
        return true;
    }

    public function getSellInfo(Holding $holding, int $target, int $value)
    {
        // need to sell 10.3 -> 11 going to be sold
        // $$$ needed =  10.3 * bid 
        // limit = $$$ amount / 11, since we wil sell 11
        $price = $this->brokerService->getCurrentBid($holding->getSecurity());

        $drift = abs($value - $target);
        $qty = intval(ceil($drift / $price));
    
        if ($qty == 0.) {
            return ['qty' => 0, 'limit' => 99999999];
        }

        return [
            'qty' => $qty,
            'limit' => ceil($drift / $qty)
        ];
    }
}