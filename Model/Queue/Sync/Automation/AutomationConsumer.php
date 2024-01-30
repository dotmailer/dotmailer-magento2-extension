<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Queue\Sync\Automation;

use Dotdigitalgroup\Email\Exception\PendingOptInException;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Automation;
use Dotdigitalgroup\Email\Model\AutomationFactory;
use Dotdigitalgroup\Email\Model\Queue\Data\AutomationData;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation as AutomationResource;
use Dotdigitalgroup\Email\Model\StatusInterface;
use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationProcessorFactory;
use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationTypeHandler;
use Dotdigitalgroup\Email\Model\Sync\Automation\Sender;
use Dotdigitalgroup\Email\Model\Sync\Automation\Type\AbandonedCartFactory;
use Magento\Framework\Exception\LocalizedException;

class AutomationConsumer
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var AutomationFactory
     */
    private $automationFactory;

    /**
     * @var AutomationResource
     */
    private $automationResource;

    /**
     * @var AutomationProcessorFactory
     */
    private $automationProcessorFactory;

    /**
     * @var AbandonedCartFactory
     */
    private $abandonedCartAutomationFactory;

    /**
     * @var Sender
     */
    private $sender;

    /**
     * AutomationConsumer constructor.
     *
     * @param Logger $logger
     * @param AutomationResource $automationResource
     * @param AutomationFactory $automationFactory
     * @param AutomationProcessorFactory $automationProcessorFactory
     * @param AbandonedCartFactory $abandonedCartAutomationFactory
     * @param Sender $sender
     */
    public function __construct(
        Logger $logger,
        AutomationResource $automationResource,
        AutomationFactory $automationFactory,
        AutomationProcessorFactory $automationProcessorFactory,
        AbandonedCartFactory $abandonedCartAutomationFactory,
        Sender $sender
    ) {
        $this->logger = $logger;
        $this->automationFactory = $automationFactory;
        $this->automationResource = $automationResource;
        $this->automationProcessorFactory = $automationProcessorFactory;
        $this->abandonedCartAutomationFactory = $abandonedCartAutomationFactory;
        $this->sender = $sender;
    }

    /**
     * Process.
     *
     * @param AutomationData $automationData
     *
     * @return void
     * @throws LocalizedException
     */
    public function process(AutomationData $automationData): void
    {
        $model = $this->automationFactory->create();
        $this->automationResource->load($model, $automationData->getId());

        $contactId = $this->getContactIdForEnrolment($model);

        if (!$contactId) {
            return;
        }

        try {
            $this->sender->sendAutomationEnrolments(
                $model->getAutomationType(),
                [$automationData->getId() => $contactId],
                $model->getWebsiteId(),
                $model->getProgramId()
            );
        } catch (\Exception $e) {
            $this->automationResource->setStatusAndSaveAutomation(
                $model,
                StatusInterface::FAILED,
                $e->getMessage()
            );
            $this->logger->error(
                sprintf("Automation enrolment send error for automation id %s:", $model->getId()),
                [(string) $e]
            );
            return;
        }

        $this->logger->info(
            "Queued automation send complete",
            ['id' => $automationData->getId()]
        );
    }

    /**
     * Get contact id for enrolment.
     *
     * @param Automation $automation
     * @return false|int
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function getContactIdForEnrolment(Automation $automation)
    {
        $processorFactory =
            $automation->getAutomationType() === AutomationTypeHandler::AUTOMATION_TYPE_ABANDONED_CART_PROGRAM_ENROLMENT
                ? $this->abandonedCartAutomationFactory
                : $this->automationProcessorFactory;

        try {
            $contactId = $processorFactory->create()
                ->assembleDataForEnrolment($automation);
        } catch (PendingOptInException $e) {
            $this->automationResource->setStatusAndSaveAutomation(
                $automation,
                StatusInterface::PENDING_OPT_IN
            );
            return false;
        } catch (\Exception $e) {
            $this->automationResource->setStatusAndSaveAutomation(
                $automation,
                StatusInterface::FAILED,
                $e->getMessage()
            );
            $this->logger->error(
                sprintf("Automation enrolment assembly error for automation id %s:", $automation->getId()),
                [(string) $e]
            );
            return false;
        }

        return $contactId;
    }
}
