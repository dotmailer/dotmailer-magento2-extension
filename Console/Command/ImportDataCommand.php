<?php

namespace Dotdigitalgroup\Email\Console\Command;

use Dotdigitalgroup\Email\Setup\Install\DataMigrationHelper;
use Magento\Framework\Exception\InputException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportDataCommand extends Command
{
    /**
     * @var DataMigrationHelper
     */
    private $migrateData;

    /**
     * ImportDataCommand constructor
     * @param DataMigrationHelper $migrateData
     */
    public function __construct(
        DataMigrationHelper $migrateData
    ) {
        $this->migrateData = $migrateData;
        parent::__construct();
    }

    /**
     * Configure this command
     */
    protected function configure()
    {
        $this->setName('dotdigital:migrate')
            ->setDescription('Migrate data into email_ tables to sync with Dotdigital')
            ->addOption(
                'table',
                't',
                InputArgument::OPTIONAL,
                __('The name of the table you want to migrate'),
                null
            );

        parent::configure();
    }

    /**
     * Execute the data migration
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $requestedTable = $input->getOption('table');
        $availableTables = $this->migrateData->getTablesFromAvailableTypes();

        if ($requestedTable && !in_array($requestedTable, $availableTables)) {
            throw new InputException(
                __(
                    'You can specify one of the following tables: '
                    . implode(', ', $availableTables)
                )
            );
        }

        $start = microtime(true);
        $output->writeln(__('Starting data import')->getText());

        $this->migrateData->setOutputInterface($output)->emptyTables($requestedTable)->run($requestedTable);

        if (!$requestedTable) {
            $this->migrateData->generateAndSaveCode();
        }

        $output->writeln(__(sprintf('Import complete in %s', round(microtime(true) - $start, 2)))->getText());

        return 0;
    }
}
