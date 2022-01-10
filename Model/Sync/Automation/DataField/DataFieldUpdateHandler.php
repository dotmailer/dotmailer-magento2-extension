<?php

namespace Dotdigitalgroup\Email\Model\Sync\Automation\DataField;

use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationTypeHandler;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\Updater\Order;

class DataFieldUpdateHandler
{
    /**
     * @var DataFieldUpdater
     */
    private $defaultUpdater;

    /**
     * @var Order
     */
    private $orderUpdater;

    /**
     * DataFieldUpdateHandler constructor.
     * @param DataFieldUpdater $defaultUpdater
     * @param Order $orderUpdater
     */
    public function __construct(
        DataFieldUpdater $defaultUpdater,
        Order $orderUpdater
    ) {
        $this->defaultUpdater = $defaultUpdater;
        $this->orderUpdater = $orderUpdater;
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
                $this->orderUpdater->setDataFields($websiteId, $typeId, $storeName)
                    ->updateDataFields();
                break;
            default:
                $this->defaultUpdater->setDefaultDataFields($email, $websiteId, $storeName)
                    ->updateDataFields();
                break;
        }
    }
}
