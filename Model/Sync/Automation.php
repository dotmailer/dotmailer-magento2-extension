<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync;

use Dotdigitalgroup\Email\Model\ResourceModel\Automation\CollectionFactory;
use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationProcessorFactory;
use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationTypeHandler;
use Dotdigitalgroup\Email\Model\Sync\Automation\ProgramFinder;
use Dotdigitalgroup\Email\Model\Sync\Automation\Sender;

class Automation implements SyncInterface
{
    private const AUTOMATION_SYNC_LIMIT = 100;

    /**
     * @var CollectionFactory
     */
    private $automationCollectionFactory;

    /**
     * @var AutomationTypeHandler
     */
    private $automationTypeHandler;

    /**
     * @var ProgramFinder
     */
    private $programFinder;

    /**
     * @var Sender
     */
    private $sender;

    /**
     * Automation constructor.
     *
     * @param CollectionFactory $automationCollectionFactory
     * @param AutomationTypeHandler $automationTypeHandler
     * @param ProgramFinder $programFinder
     * @param Sender $sender
     */
    public function __construct(
        CollectionFactory $automationCollectionFactory,
        AutomationTypeHandler $automationTypeHandler,
        ProgramFinder $programFinder,
        Sender $sender
    ) {
        $this->automationCollectionFactory = $automationCollectionFactory;
        $this->automationTypeHandler = $automationTypeHandler;
        $this->programFinder = $programFinder;
        $this->sender = $sender;
    }

    /**
     * Sync.
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @param \DateTime|null $from
     * @return void
     */
    public function sync(\DateTime $from = null)
    {
        foreach ($this->automationTypeHandler->getAutomationTypes() as $type => $properties) {

            $collection = $this->automationCollectionFactory->create()
                ->getCollectionByType($type, self::AUTOMATION_SYNC_LIMIT);

            $data = $properties['model']->create()
                ->process($collection);

            foreach ($data as $websiteId => $websiteGroup) {
                foreach ($websiteGroup as $storeId => $storeData) {
                    $programId = $this->programFinder->getProgramIdForType($type, $storeId);
                    $this->sender->sendAutomationEnrolments(
                        $type,
                        $storeData['contacts'],
                        $websiteId,
                        $programId
                    );
                }
            }
        }
    }
}
