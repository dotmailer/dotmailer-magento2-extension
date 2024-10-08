<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Queue\Sync\Automation;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Automation;
use Dotdigitalgroup\Email\Model\Queue\Data\AutomationDataFactory;
use Magento\Framework\MessageQueue\PublisherInterface;

class AutomationPublisher
{
    public const TOPIC_SYNC_AUTOMATION = 'ddg.sync.automation';

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var AutomationDataFactory
     */
    private $automationDataFactory;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @param Logger $logger
     * @param AutomationDataFactory $automationDataFactory
     * @param PublisherInterface $publisher
     */
    public function __construct(
        Logger $logger,
        AutomationDataFactory $automationDataFactory,
        PublisherInterface $publisher
    ) {
        $this->logger = $logger;
        $this->automationDataFactory = $automationDataFactory;
        $this->publisher = $publisher;
    }

    /**
     * Publish message.
     *
     * @param Automation $automation
     *
     * @return void
     */
    public function publish(Automation $automation)
    {
        $message = $this->automationDataFactory->create();
        $message->setId((int) $automation->getId());
        $message->setType($automation->getAutomationType());

        try {
            $this->publisher->publish(self::TOPIC_SYNC_AUTOMATION, $message);
        } catch (\Exception $e) {
            $this->logger->error('Automation publish failed', [(string) $e]);
        }
    }
}
