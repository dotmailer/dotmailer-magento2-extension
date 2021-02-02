<?php

namespace Dotdigitalgroup\Email\Console\Command;

use Dotdigitalgroup\Email\Console\Command\Provider\TaskProvider;
use Dotdigitalgroup\Email\Model\Task\TaskRunInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class TaskRunnerCommand extends Command
{
    /**
     * @var TaskProvider
     */
    private $taskProvider;

    /**
     * @var State
     */
    private $state;

    /**
     * @param TaskProvider $taskProvider
     * @param State $state
     */
    public function __construct(
        TaskProvider $taskProvider,
        State $state
    ) {
        $this->taskProvider = $taskProvider;
        $this->state = $state;
        parent::__construct();
    }

    /**
     * Configure this command
     */
    protected function configure()
    {
        $this
            ->setName('dotdigital:task')
            ->setDescription(__('Run dotdigital module tasks on demand'))
            ->addArgument(
                'task',
                InputArgument::OPTIONAL,
                __('The name of the task to run')
            );
        parent::configure();
    }

    /**
     * Run the task command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
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

        if (!$requestedTask = $input->getArgument('task')) {
            $requestedTask = $this->askForTask($input, $output);
        }

        // get the requested sync class
        /** @var TaskRunInterface $taskClass */
        $taskClass = $this->taskProvider->$requestedTask;
        if ($taskClass === null) {
            $output->writeln(__('Requested task was not recognised')->getText());
            return;
        }

        $start = microtime(true);
        $output->writeln(sprintf(
            '[%s] %s: %s',
            date('Y-m-d H:i:s'),
            __('Started task')->getText(),
            get_class($taskClass)
        ));

        // run the sync
        $taskClass->run();

        $output->writeln(sprintf(
            '[%s] %s %s',
            date('Y-m-d H:i:s'),
            __('Complete in')->getText(),
            round(microtime(true) - $start, 2)
        ));
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return string
     */
    private function askForTask(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            __('Please select a dotdigital CLI task to run')->getText(),
            array_column($this->taskProvider->getAvailableTasks(), 'title')
        );
        $question->setErrorMessage(__('Please select a task')->getText());
        return $helper->ask($input, $output, $question);
    }
}
