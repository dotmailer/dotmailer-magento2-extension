<?php

namespace Dotdigitalgroup\Email\Model\Sync\Automation;

use Dotdigitalgroup\Email\Exception\PendingOptInException;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Automation;
use Dotdigitalgroup\Email\Model\ContactFactory;
use Dotdigitalgroup\Email\Model\Contact\ContactResponseHandler;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation as AutomationResource;
use Dotdigitalgroup\Email\Model\StatusInterface;
use Dotdigitalgroup\Email\Model\Sync\Automation\ContactManager;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\DataFieldCollector;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\DataFieldTypeHandler;
use Magento\Newsletter\Model\SubscriberFactory;

class AutomationProcessor
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ContactResponseHandler
     */
    protected $contactResponseHandler;

    /**
     * @var AutomationResource
     */
    protected $automationResource;

    /**
     * @var ContactFactory
     */
    protected $contactFactory;

    /**
     * @var ContactManager
     */
    protected $contactManager;

    /**
     * @var DataFieldCollector
     */
    protected $dataFieldCollector;

    /**
     * @var DataFieldTypeHandler
     */
    protected $dataFieldTypeHandler;

    /**
     * @var SubscriberFactory
     */
    protected $subscriberFactory;

    /**
     * AutomationProcessor constructor.
     *
     * @param Data $helper
     * @param Logger $logger
     * @param ContactResponseHandler $contactResponseHandler
     * @param AutomationResource $automationResource
     * @param ContactFactory $contactFactory
     * @param ContactManager $contactManager
     * @param DataFieldCollector $dataFieldCollector
     * @param DataFieldTypeHandler $dataFieldTypeHandler
     * @param SubscriberFactory $subscriberFactory
     */
    public function __construct(
        Data $helper,
        Logger $logger,
        ContactResponseHandler $contactResponseHandler,
        AutomationResource $automationResource,
        ContactFactory $contactFactory,
        ContactManager $contactManager,
        DataFieldCollector $dataFieldCollector,
        DataFieldTypeHandler $dataFieldTypeHandler,
        SubscriberFactory $subscriberFactory
    ) {
        $this->helper = $helper;
        $this->logger = $logger;
        $this->contactResponseHandler = $contactResponseHandler;
        $this->automationResource = $automationResource;
        $this->contactFactory = $contactFactory;
        $this->contactManager = $contactManager;
        $this->dataFieldCollector = $dataFieldCollector;
        $this->dataFieldTypeHandler = $dataFieldTypeHandler;
        $this->subscriberFactory = $subscriberFactory;
    }

    /**
     * Process.
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process($collection)
    {
        $data = [];

        foreach ($collection as $automation) {
            if ($this->shouldExitLoop($automation)) {
                continue;
            }

            $email = $automation->getEmail();
            $websiteId = $automation->getWebsiteId();
            $storeId = $automation->getStoreId();
            $automationDataFields = $this->retrieveAutomationDataFields($automation, $email, $websiteId);

            $automationContact = $this->contactFactory->create()
                ->loadByCustomerEmail($email, $websiteId);
            $automationSubscriber = $this->subscriberFactory->create()
                ->loadBySubscriberEmail($email, $websiteId);

            try {
                $contactId = $this->contactManager->prepareDotdigitalContact(
                    $automationContact,
                    $automationSubscriber,
                    $automationDataFields
                );

                $data[$websiteId][$storeId]['contacts'][$automation->getId()] = $contactId;
            } catch (PendingOptInException $e) {
                $this->automationResource->setStatusAndSaveAutomation(
                    $automation,
                    StatusInterface::PENDING_OPT_IN
                );
                continue;
            } catch (\Exception $e) {
                $this->automationResource->setStatusAndSaveAutomation(
                    $automation,
                    StatusInterface::FAILED,
                    $e->getMessage()
                );
                $this->logger->debug(
                    sprintf('Enrolment failed for automation id: %s', $automation->getId()),
                    [(string) $e]
                );
                continue;
            }
        }

        return $data;
    }

    /**
     * Check if automation should be processed.
     *
     * @param Automation $automation
     * @return bool
     */
    protected function shouldExitLoop(Automation $automation)
    {
        return false;
    }

    /**
     * Retrieve automation data fields.
     *
     * @param Automation $automation
     * @param string $email
     * @param string|int $websiteId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function retrieveAutomationDataFields(Automation $automation, $email, $websiteId): array
    {
        $type = $automation->getAutomationType();
        //Set type to generic automation status if type contains constant value
        if (strpos($type, AutomationTypeHandler::ORDER_STATUS_AUTOMATION) !== false) {
            $type = AutomationTypeHandler::ORDER_STATUS_AUTOMATION;
        }

        return $this->dataFieldTypeHandler->retrieveDatafieldsByType(
            $type,
            $email,
            $websiteId,
            $automation->getTypeId(),
            $automation->getStoreName()
        );
    }
}
