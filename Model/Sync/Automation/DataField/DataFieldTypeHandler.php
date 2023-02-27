<?php

namespace Dotdigitalgroup\Email\Model\Sync\Automation\DataField;

use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationTypeHandler;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\Updater\OrderFactory;

class DataFieldTypeHandler
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
     * DataFieldTypeHandler constructor.
     *
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
     * Retrieve contact data fields for this automation type.
     *
     * @param string $type
     * @param string $email
     * @param string|int $websiteId
     * @param string|int $typeId
     * @param string $storeName
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function retrieveDatafieldsByType($type, $email, $websiteId, $typeId, $storeName): array
    {
        switch ($type) {
            case AutomationTypeHandler::AUTOMATION_TYPE_NEW_ORDER:
            case AutomationTypeHandler::AUTOMATION_TYPE_NEW_GUEST_ORDER:
            case AutomationTypeHandler::ORDER_STATUS_AUTOMATION:
            case AutomationTypeHandler::AUTOMATION_TYPE_CUSTOMER_FIRST_ORDER:
                $data = $this->orderUpdaterFactory->create()
                    ->setDataFields($websiteId, $typeId, $storeName)
                    ->getData();
                break;
            default:
                $data = $this->defaultUpdaterFactory->create()
                    ->setDefaultDataFields($email, $websiteId, $storeName)
                    ->getData();
                break;
        }

        return $data;
    }
}
