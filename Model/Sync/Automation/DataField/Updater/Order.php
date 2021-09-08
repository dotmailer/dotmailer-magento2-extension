<?php

namespace Dotdigitalgroup\Email\Model\Sync\Automation\DataField\Updater;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\DataFieldUpdater;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;

class Order extends DataFieldUpdater
{
    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * Order constructor.
     * @param OrderFactory $orderFactory
     * @param Data $helper
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        OrderFactory $orderFactory,
        Data $helper,
        StoreManagerInterface $storeManager
    ) {
        $this->orderFactory = $orderFactory;
        parent::__construct($helper, $storeManager);
    }

    /**
     * Update new order data fields.
     *
     * @param string|int $websiteId
     * @param string|int $typeId
     * @param string $storeName
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setDataFields($websiteId, $typeId, $storeName)
    {
        $orderModel = $this->orderFactory->create()
            ->loadByIncrementId($typeId);

        $this->setDefaultDataFields(
            $orderModel->getCustomerEmail(),
            $websiteId,
            $storeName
        );

        /** @var Website $website */
        $website = $this->getWebsite($websiteId);

        if ($lastOrderId = $website->getConfig(
            Config::XML_PATH_CONNECTOR_CUSTOMER_LAST_ORDER_ID
        )
        ) {
            $this->data[] = [
                'Key' => $lastOrderId,
                'Value' => $orderModel->getId(),
            ];
        }
        if ($orderIncrementId = $website->getConfig(
            Config::XML_PATH_CONNECTOR_CUSTOMER_LAST_ORDER_INCREMENT_ID
        )
        ) {
            $this->data[] = [
                'Key' => $orderIncrementId,
                'Value' => $orderModel->getIncrementId(),
            ];
        }
        if ($lastOrderDate = $website->getConfig(
            Config::XML_PATH_CONNECTOR_CUSTOMER_LAST_ORDER_DATE
        )) {
            $this->data[] = [
                'Key' => $lastOrderDate,
                'Value' => $orderModel->getCreatedAt(),
            ];
        }
        if (($customerId = $website->getConfig(Config::XML_PATH_CONNECTOR_CUSTOMER_ID))
            && $orderModel->getCustomerId()
        ) {
            $this->data[] = [
                'Key' => $customerId,
                'Value' => $orderModel->getCustomerId(),
            ];
        }

        return $this;
    }
}
