<?php

namespace App\Command;

use App\Entity\Security;
use App\Repository\DbContext;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AddSecurityCommand extends Command
{
    protected static $defaultName = 'app:add-security';
    private DbContext $dbContext;

    public function __construct(DbContext $dbContext)
    {
        parent::__construct();
        $this->dbContext = $dbContext;
    }

    protected function configure()
    {
        $this
            ->setDescription('Add a new security.')
            ->setHelp('This command adds a new security listing that will be available for use in Plans')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of stock')
            ->addArgument('symbol', InputArgument::REQUIRED, 'Ticker symbol of stock')
            ->addArgument('type', InputArgument::REQUIRED, 'Security type')
            ->addArgument('class', InputArgument::REQUIRED, 'Security class');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $security = new Security();
        $security->setName($input->getArgument('name'));
        $security->setSymbol($input->getArgument('symbol'));
        $security->setType($input->getArgument('type'));
        $security->setClass($input->getArgument('class'));

        $this->dbContext->securities->add($security);
        $this->dbContext->commit();

        return Command::SUCCESS;
    }
}
