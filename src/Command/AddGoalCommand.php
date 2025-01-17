<?php

namespace App\Command;

use App\Entity\Allocation;
use App\Entity\Goal;
use App\Entity\Holding;
use App\Entity\NineSigPlan;
use App\Entity\Plan;
use App\Repository\DbContext;
use DateTime;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AddGoalCommand extends Command
{
    protected static $defaultName = 'app:add-goal';
    private DbContext $dbContext;
    private LoggerInterface $logger;

    public function __construct(DbContext $dbContext, LoggerInterface $logger)
    {
        parent::__construct();
        $this->dbContext = $dbContext;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this
            ->setDescription('Add a new goal.')
            ->setHelp('This command adds a new goal to a user');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $plan = $this->dbContext->plans->getByName(NineSigPlan::NAME);
        $user = $this->dbContext->users->getByAccountId('e6e4ec5b-5c3e-409b-be2b-ab8b6a0f2e72');

        // $this->logger->info('plan', ['e' => $plan, 'u' => $user]);
        $goal = new Goal();
        $goal->setCreatedAt(new DateTime());
        $goal->setName('Banana Stand');
        $goal->setPlan($plan);
        $goal->setUserId($user->getId());
        $this->logger->info('Goal', ['g' => $goal]);

        $tqqq = $this->dbContext->securities->getBySymbol('TQQQ');
        $this->logger->info('tqqq', ['e' => $tqqq]);
        $tqqqHolding = new Holding();
        $tqqqHolding->setSecurity($tqqq);
        $tqqqHolding->setQuantity(599);
        $tqqqHolding->setValue(10356111);
        $tqqqHolding->setCostBasis(6251031);

        $agg = $this->dbContext->securities->getBySymbol('AGG');
        $this->logger->info('agg', ['e' => $agg]);
        $aggHolding = new Holding();
        $aggHolding->setSecurity($agg);
        $aggHolding->setQuantity(43);
        $aggHolding->setValue(43*11778);
        $aggHolding->setCostBasis(43*17703);

        $goal->addHolding($tqqqHolding);
        $goal->addHolding($aggHolding);

        $this->dbContext->goals->addGoal($user, $goal);
        $this->dbContext->commit();

        return Command::SUCCESS;
    }
}
