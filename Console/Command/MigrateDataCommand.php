<?php

namespace Dotdigitalgroup\Email\Console\Command;

use Dotdigitalgroup\Email\Setup\Install\DataMigrationHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateDataCommand extends Command
{
    /**
     * @var DataMigrationHelper
     */
    private $migrateData;

    /**
     * ImportDataCommand constructor
     * @param DataMigrationHelper $migrateData
     */
    public function __construct(DataMigrationHelper $migrateData)
    {
        $this->migrateData = $migrateData;
        parent::__construct();
    }

    /**
     * Configure this command
     */
    protected function configure()
    {
        $this->setName('dotdigital:migrate')
            ->setDescription('Migrate data into email_ tables to sync with Engagement Cloud')
            ->addOption('truncate-importer', null, InputOption::VALUE_OPTIONAL, 'Truncate the email_importer table');

        parent::configure();
    }

    /**
     * Execute the data migration
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $start = microtime(true);
        $output->writeln(__('Starting data import')->getText());

        $this->migrateData->setOutputInterface($output)->run((bool) $input->getOption('truncate-importer'));

        $output->writeln(__(sprintf('Import complete in %s', round(microtime(true) - $start, 2)))->getText());
    }
}
