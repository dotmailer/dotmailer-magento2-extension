<?php

namespace Dotdigitalgroup\Email\Console\Command\Provider;

use Dotdigitalgroup\Email\Model\Contact\PlatformChangeManagerFactory;
use Dotdigitalgroup\Email\Model\Cron\CleanerFactory;
use Dotdigitalgroup\Email\Model\MonitorFactory;
use Dotdigitalgroup\Email\Model\Newsletter\UnsubscriberFactory;
use Dotdigitalgroup\Email\Model\Contact\PendingContactCheckerFactory;

/**
 * Provides factories for all available task models, and exposes its properties to show what's available
 */
class TaskProvider
{
    /**
     * @var PlatformChangeManagerFactory
     */
    private $platformChangeManagerFactory;

    /**
     * @var CleanerFactory
     */
    private $cleanerFactory;

    /**
     * @var MonitorFactory
     */
    private $monitorFactory;

    /**
     * @var UnsubscriberFactory
     */
    private $unsubscriberFactory;

    /**
     * @var PendingContactCheckerFactory
     */
    private $pendingContactCheckerFactory;

    /**
     * TaskProvider constructor.
     *
     * @param PlatformChangeManagerFactory $platformChangeManagerFactory
     * @param CleanerFactory $cleanerFactory
     * @param MonitorFactory $monitorFactory
     * @param UnsubscriberFactory $unsubscriberFactory
     * @param PendingContactCheckerFactory $pendingContactCheckerFactory
     */
    public function __construct(
        PlatformChangeManagerFactory $platformChangeManagerFactory,
        CleanerFactory $cleanerFactory,
        MonitorFactory $monitorFactory,
        UnsubscriberFactory $unsubscriberFactory,
        PendingContactCheckerFactory $pendingContactCheckerFactory
    ) {
        $this->platformChangeManagerFactory = $platformChangeManagerFactory;
        $this->cleanerFactory = $cleanerFactory;
        $this->monitorFactory = $monitorFactory;
        $this->unsubscriberFactory = $unsubscriberFactory;
        $this->pendingContactCheckerFactory = $pendingContactCheckerFactory;
    }

    /**
     * Get available task factories
     *
     * @param array $additionalTasks
     * @return array
     */
    public function getAvailableTasks(array $additionalTasks = [])
    {
        static $availableTasks;

        return $availableTasks ?: $availableTasks = array_map(function ($class) {
            $classBasename = substr(get_class($class), strrpos(get_class($class), '\\') + 1);
            return [
                'title' => str_replace('Factory', '', $classBasename),
                'factory' => $class,
            ];
        }, get_object_vars($this) + $additionalTasks);
    }

    /**
     * Get a task object from those available
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        $name = lcfirst($name) . 'Factory';
        $availableTasks = $this->getAvailableTasks();

        if (isset($availableTasks[$name])) {
            return $availableTasks[$name]['factory']->create();
        }
        return null;
    }
}
