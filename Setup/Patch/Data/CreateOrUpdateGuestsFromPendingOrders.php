<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Setup\Patch\Data;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\ResourceModel\ContactFactory as ContactResourceFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as SalesOrderCollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class CreateOrUpdateGuestsFromPendingOrders
 * Now that guests are not added to the contact table during order sync,
 * we may have unprocessed orders in our table for which we need to capture guest data.
 */
class CreateOrUpdateGuestsFromPendingOrders implements DataPatchInterface
{
    /**
     * @var ContactResourceFactory
     */
    private $contactResourceFactory;

    /**
     * @var ContactCollectionFactory
     */
    private $contactCollectionFactory;

    /**
     * @var OrderCollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var State
     */
    private $state;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;

    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var SalesOrderCollectionFactory
     */
    private $salesOrderCollectionFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param Data $helper
     * @param ContactResourceFactory $contactResourceFactory
     * @param ContactCollectionFactory $contactCollectionFactory
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param State $state
     * @param ScopeConfigInterface $scopeConfig
     * @param DateTimeFactory $dateTimeFactory
     * @param SalesOrderCollectionFactory $salesOrderCollectionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        Data $helper,
        ContactResourceFactory $contactResourceFactory,
        ContactCollectionFactory $contactCollectionFactory,
        OrderCollectionFactory $orderCollectionFactory,
        State $state,
        ScopeConfigInterface $scopeConfig,
        DateTimeFactory $dateTimeFactory,
        SalesOrderCollectionFactory $salesOrderCollectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->helper = $helper;
        $this->contactResourceFactory = $contactResourceFactory;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->state = $state;
        $this->scopeConfig = $scopeConfig;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->salesOrderCollectionFactory = $salesOrderCollectionFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Apply patch
     *
     * @return void
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $stores = $this->storeManager->getStores(true);

        // @codingStandardsIgnoreStart
        try {
            $this->state->setAreaCode(Area::AREA_GLOBAL);
        } catch (\Exception $e) {
            // Area code was already set
        }
        // @codingStandardsIgnoreEnd

        foreach ($stores as $store) {
            if ($this->helper->isEnabled($store->getWebsiteId()) &&
                $this->scopeConfig->getValue(
                    Config::XML_PATH_CONNECTOR_SYNC_ORDER_ENABLED,
                    ScopeInterface::SCOPE_WEBSITE,
                    $store->getWebsiteId()
                )
            ) {
                $fromTime = $this->dateTimeFactory->create('now', new \DateTimeZone('UTC'));
                $fromTime->sub(new \DateInterval('PT1H'));

                $orderIdsToProcessFromLastHour = $this->orderCollectionFactory->create()
                    ->getOrderIdsFromRecentUnprocessedOrdersSince(
                        $store->getId(),
                        $fromTime
                    );

                if (empty($orderIdsToProcessFromLastHour)) {
                    continue;
                }

                $guestOrderEmails = $this->fetchGuestEmailsFromSalesOrderCollection($orderIdsToProcessFromLastHour);
                $matchingContactEmails = $this->contactCollectionFactory->create()
                    ->matchEmailsToContacts($guestOrderEmails, $store->getWebsiteId());
                $guestsToInsert = [];

                foreach (array_diff($guestOrderEmails, $matchingContactEmails) as $email) {
                    $guestsToInsert[] = [
                        'email' => $email,
                        'website_id' => $store->getWebsiteId(),
                        'store_id' => $store->getId(),
                        'is_guest' => 1
                    ];
                }

                /** @var \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource */
                $contactResource = $this->contactResourceFactory->create();
                $contactResource->setContactsAsGuest($matchingContactEmails, $store->getWebsiteId());
                $contactResource->insertGuests($guestsToInsert);
            }
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * Query email addresses for guest orders.
     *
     * @param array $orderIds
     *
     * @return array
     */
    private function fetchGuestEmailsFromSalesOrderCollection(array $orderIds)
    {
        return $this->salesOrderCollectionFactory->create()
            ->addFieldToFilter('entity_id', ['in' => $orderIds])
            ->addFieldToFilter('customer_id', ['null' => true])
            ->getColumnValues('customer_email');
    }
}
