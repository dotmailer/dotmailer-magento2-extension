<?php

namespace Dotdigitalgroup\Email\Model\Sync\Automation;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation as AutomationResource;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\DataFieldUpdateHandler;
use Dotdigitalgroup\Email\Model\StatusInterface;

class AutomationProcessor
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var AutomationResource
     */
    protected $automationResource;

    /**
     * @var DataFieldUpdateHandler
     */
    protected $dataFieldUpdateHandler;

    /**
     * AutomationProcessor constructor.
     * @param Data $helper
     * @param AutomationResource $automationResource
     * @param DataFieldUpdateHandler $dataFieldUpdateHandler
     */
    public function __construct(
        Data $helper,
        AutomationResource $automationResource,
        DataFieldUpdateHandler $dataFieldUpdateHandler
    ) {
        $this->helper = $helper;
        $this->automationResource = $automationResource;
        $this->dataFieldUpdateHandler = $dataFieldUpdateHandler;
    }

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process($collection)
    {
        $data = [];

        foreach ($collection as $automation) {
            $email = $automation->getEmail();
            $websiteId = $automation->getWebsiteId();
            $storeId = $automation->getStoreId();

            $contact = $this->helper->getOrCreateContact($email, $websiteId);
            //contact id is valid, can update data fields
            if ($contact && isset($contact->id)) {
                if ($contact->status === StatusInterface::PENDING_OPT_IN) {
                    $this->automationResource->setStatusAndSaveAutomation(
                        $automation,
                        StatusInterface::PENDING_OPT_IN
                    );
                    continue;
                }

                if ($this->shouldExitLoop($automation)) {
                    continue;
                }

                $this->orchestrateDataFieldUpdate($automation, $email, $websiteId);

                $data[$websiteId][$storeId]['contacts'][$automation->getId()] = $contact->id;
            } else {
                // the contact is suppressed or the request failed
                $this->automationResource->setStatusAndSaveAutomation(
                    $automation,
                    StatusInterface::FAILED,
                    'Contact cannot be created or has been suppressed'
                );
            }
        }

        return $data;
    }

    /**
     * @param \Dotdigitalgroup\Email\Model\Automation $automation
     * @return bool
     */
    protected function shouldExitLoop($automation)
    {
        return false;
    }

    /**
     * @param \Dotdigitalgroup\Email\Model\Automation $automation
     * @param string $email
     * @param string|int $websiteId
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function orchestrateDataFieldUpdate($automation, $email, $websiteId)
    {
        $type = $automation->getAutomationType();
        //Set type to generic automation status if type contains constant value
        if (strpos($type, AutomationTypeHandler::ORDER_STATUS_AUTOMATION) !== false) {
            $type = AutomationTypeHandler::ORDER_STATUS_AUTOMATION;
        }

        $this->dataFieldUpdateHandler->updateDatafieldsByType(
            $type,
            $email,
            $websiteId,
            $automation->getTypeId(),
            $automation->getStoreName()
        );
    }
}
