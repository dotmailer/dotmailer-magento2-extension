<?php

namespace Dotdigitalgroup\Email\Model\Sync\Automation\DataField;

use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationTypeHandler;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\Updater\OrderFactory;

class DataFieldUpdateHandler
{
    /**
     * @var DataFieldUpdaterFactory
     */
    private $defaultUpdaterFactory;

    /**
     * @var OrderFactory
     */
    private $orderUpdaterFactory;

    /**
     * DataFieldUpdateHandler constructor.
     * @param DataFieldUpdaterFactory $defaultUpdaterFactory
     * @param OrderFactory $orderUpdaterFactory
     */
    public function __construct(
        DataFieldUpdaterFactory $defaultUpdaterFactory,
        OrderFactory $orderUpdaterFactory
    ) {
        $this->defaultUpdaterFactory = $defaultUpdaterFactory;
        $this->orderUpdaterFactory = $orderUpdaterFactory;
    }

    /**
     * Update single contact data fields for this automation type.
     *
     * @param string $type
     * @param string $email
     * @param string|int $websiteId
     * @param string|int $typeId
     * @param string $storeName
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function updateDatafieldsByType($type, $email, $websiteId, $typeId, $storeName)
    {
        switch ($type) {
            case AutomationTypeHandler::AUTOMATION_TYPE_NEW_ORDER:
            case AutomationTypeHandler::AUTOMATION_TYPE_NEW_GUEST_ORDER:
            case AutomationTypeHandler::ORDER_STATUS_AUTOMATION:
            case AutomationTypeHandler::AUTOMATION_TYPE_CUSTOMER_FIRST_ORDER:
                $this->orderUpdaterFactory->create()
                    ->setDataFields($websiteId, $typeId, $storeName)
                    ->updateDataFields();
                break;
            default:
                $this->defaultUpdaterFactory->create()
                    ->setDefaultDataFields($email, $websiteId, $storeName)
                    ->updateDataFields();
                break;
        }
    }
}
