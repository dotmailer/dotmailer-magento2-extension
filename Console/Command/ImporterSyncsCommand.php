<?php

namespace Dotdigitalgroup\Email\Console\Command;

use Dotdigitalgroup\Email\Console\Command\Provider\SyncProvider;
use Dotdigitalgroup\Email\Model\Sync\SyncInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class ImporterSyncsCommand extends Command
{
    /**
     * @var SyncProvider
     */
    private $syncProvider;

    /**
     * @var State
     */
    private $state;

    /**
     * @param SyncProvider $syncProvider
     * @param State $state
     */
    public function __construct(
        SyncProvider $syncProvider,
        State $state
    ) {
        $this->syncProvider = $syncProvider;
        $this->state = $state;
        parent::__construct();
    }

    /**
     * Configure this command
     */
    protected function configure()
    {
        $this
            ->setName('dotdigital:sync')
            ->setDescription(__('Run syncs to collect data from email_ tables and import to Dotdigital'))
            ->addArgument(
                'sync',
                InputArgument::OPTIONAL,
                __('The name of the sync to run')
            )
            ->addOption(
                'from',
                null,
                InputOption::VALUE_OPTIONAL,
                __('Specify a date/time (parsable by \DateTime) to run a sync from (if supported)')
            )
            ->addOption(
                'mode',
                null,
                InputOption::VALUE_OPTIONAL,
                __('Switch to debug mode to output database profiler queries (if enabled)')
            )
        ;
        parent::configure();
    }

    /**
     * Run the sync command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->state->setAreaCode(Area::AREA_CRONTAB);
        } catch (LocalizedException $e) {
            if ($this->state->getAreaCode() != Area::AREA_CRONTAB) {
                $output->writeln(__(
                    sprintf('Warning: command running in an unexpected state (%s)', $this->state->getAreaCode())
                )->getText());
            }
        }

        if (!$requestedSync = $input->getArgument('sync')) {
            $requestedSync = $this->askForSync($input, $output);
        }

        // get the requested sync class
        /** @var SyncInterface $syncClass */
        $syncClass = $this->syncProvider->$requestedSync;
        if ($syncClass === null || !$syncClass instanceof SyncInterface) {
            $output->writeln(__('Requested sync was not recognised')->getText());
            return 0;
        }

        $start = microtime(true);
        $output->writeln(sprintf(
            '[%s] %s: %s',
            date('Y-m-d H:i:s'),
            __('Started running sync')->getText(),
            get_class($syncClass)
        ));

        // check whether a from time was specified
        if ($fromTimeString = $input->getOption('from')) {
            $fromTime = new \DateTime($fromTimeString, new \DateTimeZone('UTC'));
        } else {
            $fromTime = null;
        }

        // run the sync
        $syncClass->sync($fromTime);

        $output->writeln(sprintf(
            '[%s] %s %s',
            date('Y-m-d H:i:s'),
            __('Complete in')->getText(),
            round(microtime(true) - $start, 2)
        ));

        if ($input->getOption('mode') == 'debug') {
            $this->outputDbProfilerQueries($output);
        }

        return 0;
    }

    /**
     * Ask for sync.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return string
     */
    private function askForSync(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $syncQuestion = new ChoiceQuestion(
            __('Please select a Dotdigital sync to run')->getText(),
            array_column($this->syncProvider->getAvailableSyncs(), 'title')
        );
        $syncQuestion->setErrorMessage(__('Please select a sync')->getText());
        /** @var QuestionHelper $helper */
        return $helper->ask($input, $output, $syncQuestion);
    }

    /**
     * Output db profiler queries.
     *
     * Requires the Magento database profiler to be enabled.
     * To view this output, run the command with the --mode=debug option.
     * It's helpful to send the output to a file for easier reading, e.g.
     * bin/magento dotdigital:sync --mode=debug > /tmp/dotdigital_sync.log
     *
     * @param OutputInterface $output
     * @return void
     */
    private function outputDbProfilerQueries(OutputInterface $output): void
    {
        /** @var ResourceConnection $res */
        $res = ObjectManager::getInstance()->get(ResourceConnection::class);
        /** @var \Magento\Framework\DB\Adapter\Pdo\Mysql $connection */
        $connection = $res->getConnection('read');
        /** @var \Magento\Framework\DB\Profiler $profiler */
        $profiler = $connection->getProfiler();

        if (!$profiler->getQueryProfiles()) {
            $output->writeln(__('Database profiler is not enabled')->getText());
            return;
        }

        foreach ($profiler->getQueryProfiles() as $query) {
            /** @var \Zend_Db_Profiler_Query $query*/
            if ($query->getQueryType() == \Zend_Db_Profiler::INSERT) {
                continue;
            }
            $output->writeln(sprintf(
                '[%s] %s %s %s',
                date('Y-m-d H:i:s'),
                __('Query'),
                $query->getQuery(),
                json_encode($query->getQueryParams())
            ));
        }

        $output->writeln(sprintf(
            '[%s] %s %s',
            date('Y-m-d H:i:s'),
            __('Total queries run')->getText(),
            $profiler->getTotalNumQueries()
        ));

        $output->write(sprintf(
            '[%s] %s %s',
            date('Y-m-d H:i:s'),
            __('Total time taken')->getText(),
            $profiler->getTotalElapsedSecs()
        ));
    }
}
