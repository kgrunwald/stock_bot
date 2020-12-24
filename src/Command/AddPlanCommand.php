<?php

namespace App\Command;

use App\Entity\Allocation;
use App\Entity\NineSigPlan;
use App\Entity\Plan;
use App\Repository\DbContext;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AddPlanCommand extends Command
{
    protected static $defaultName = 'app:add-plan';
    private DbContext $dbContext;

    public function __construct(DbContext $dbContext)
    {
        parent::__construct();
        $this->dbContext = $dbContext;
    }

    protected function configure()
    {
        $this
            ->setDescription('Add a new plan.')
            ->setHelp('This command adds a new plan listing that will be available for use in Goals');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $plan = new Plan();
        $plan->setName(NineSigPlan::NAME);
        $tqqq = new Allocation();
        $tqqq->setSecurity($this->dbContext->securities->getBySymbol('TQQQ'));
        $tqqq->setPercentage(60);
        $plan->addAllocation($tqqq);

        $agg = new Allocation();
        $agg->setSecurity($this->dbContext->securities->getBySymbol('AGG'));
        $agg->setPercentage(40);
        $plan->addAllocation($agg);

        $this->dbContext->plans->add($plan);
        $this->dbContext->commit();

        return Command::SUCCESS;
    }
}
