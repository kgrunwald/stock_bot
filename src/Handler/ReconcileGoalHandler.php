<?php

namespace App\Handler;

use App\Entity\Goal;
use App\Entity\Holding;
use App\Entity\LockInterface;
use App\Entity\Security;
use App\Handler\Messages\ReconcileGoalMessage;
use App\Repository\DbContext;
use App\Security\SecurityService;
use App\Service\BrokerService;

class ReconcileGoalHandler
{
    private BrokerService $brokerService;
    private DbContext $dbContext;
    private LockInterface $lockInterface;
    private SecurityService $securityService;

    public function __construct(BrokerService $brokerService, DbContext $context, LockInterface $lockInterface, SecurityService $security)
    {
        $this->brokerService = $brokerService;
        $this->dbContext = $context;
        $this->lockInterface = $lockInterface;
        $this->securityService = $security;
    }

    public function __invoke(ReconcileGoalMessage $message)
    {
        $goal = $this->dbContext->goals->getById($message->goalId);
        $user = $this->dbContext->users->getByAccountId($goal->getUserId());
        $this->securityService->setUser($user);

        $lock = $this->lockInterface->acquire($goal);

        $this->updateHoldings($goal);
        $this->dbContext->goals->add($goal);
        $this->dbContext->commit();
    }

    public function updateHoldings(Goal $goal)
    {
        foreach($goal->getHoldings() as $holding)
        {
            $this->updateHolding($holding);
            $goal->addHolding($holding);
        }
    }

    public function updateHolding(Holding $holding)
    {
        if($holding->getSymbol() === Security::CASH) {
            return;
        }

        $details = $this->brokerService->getPosition($holding);
        $currentPrice = intval(floatval($details['current_price']) * 100);
        
        $totalQuantity = intval($details['qty']);
        $totalCostBasis = intval(floatval($details['cost_basis']) * 100);
        $basis = intval($holding->getQuantity() / $totalQuantity * $totalCostBasis);

        $holding->setValue($holding->getQuantity() * $currentPrice);
        $holding->setCostBasis($basis);
    }
}