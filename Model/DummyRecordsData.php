<?php

namespace Dotdigitalgroup\Email\Model;

use Dotdigitalgroup\Email\Helper\Data;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\Account;
use Magento\Framework\UrlInterface;
use Dotdigitalgroup\Email\Model\Connector\AccountHandler;

class DummyRecordsData
{
    private const PRODUCT_NAME = 'Chaz Kangeroo Hoodie';

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var array
     */
    public $email = [];

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Account
     */
    private $account;

    /**
     * @var AccountHandler
     */
    private $accountHandler;

    /**
     * DummyRecordsData constructor.
     *
     * @param Data $helper
     * @param StoreManagerInterface $storeManager
     * @param Logger $logger
     * @param Account $account
     * @param AccountHandler $accountHandler
     */
    public function __construct(
        Data $helper,
        StoreManagerInterface $storeManager,
        Logger $logger,
        Account $account,
        AccountHandler $accountHandler
    ) {
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->account = $account;
        $this->accountHandler = $accountHandler;
    }

    /**
     * Get active websites.
     *
     * If an EC user is connected to multiple websites then we return the first
     * as we don't need to send multiple api requests for the same user.
     *
     * This is to be used when in default level.
     *
     * @return \Iterator
     */
    public function getActiveWebsites()
    {
        foreach ($this->accountHandler->getAPIUsersForECEnabledWebsites() as $user) {
            foreach ($user as $websites) {
                yield $websites[0];
            }
        }
    }

    /**
     * Get contact insight data.
     *
     * @param int|string $websiteId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getContactInsightData($websiteId): array
    {
        /** @var Store $store */
        $store = $this->getStore($websiteId);
        return $this->getStaticData($store, $websiteId);
    }

    /**
     * Get store.
     *
     * @param int|string $websiteId
     * @return \Magento\Store\Api\Data\StoreInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getStore($websiteId)
    {
        foreach ($this->storeManager->getStores(true) as $store) {
            if ($store->getWebsiteId() == $websiteId) {
                return $store;
            }
        }
        return $this->storeManager->getStore();
    }

    /**
     * Get static data.
     *
     * @param Store $store
     * @param int $websiteId
     * @return array
     */
    private function getStaticData($store, $websiteId)
    {
        $data = [
            'key' => '1',
            'contactIdentifier' => $this->getEmailFromAccountInfo($websiteId),
            'json' => [
                'cartId' => '1',
                'cartUrl' => $store->getBaseUrl(
                    UrlInterface::URL_TYPE_WEB,
                    $store->isCurrentlySecure()
                ).'connector/email/getbasket/quote_id/1/',
                'createdDate' => date(\DateTime::ATOM, time()),
                'modifiedDate' => date(\DateTime::ATOM, time()),
                'currency' => 'USD',
                'subTotal' => round(52, 2),
                'taxAmount' => (float) 0,
                'shipping' => (float) 0,
                'grandTotal' => round(52, 2)
            ]
        ];

        $lineItems[] = [
            'sku' => 'MH06-M-Blue',
            'imageUrl' => $this->getImageUrl(),
            'productUrl' => $store->getBaseUrl(
                UrlInterface::URL_TYPE_WEB,
                $store->isCurrentlySecure()
            ),
            'name' => self::PRODUCT_NAME,
            'unitPrice' => 0,
            'quantity' => 1,
            'salePrice' => round(52, 2),
            'totalPrice' => round(52, 2)
        ];

        $data['json']['discountAmount'] = 0;
        $data['json']['lineItems'] = $lineItems;
        $data['json']['cartPhase'] = 'ORDER_PENDING';

        return $data;
    }

    /**
     * Get image url.
     *
     * @return string
     */
    private function getImageUrl()
    {
        return 'https://raw.githubusercontent.com/'
            .'magento/magento2-sample-data/'
            .'2.3/pub/media/catalog/product/m/h/mh01-black_main.jpg';
    }

    /**
     * Get account email.
     *
     * @param string|int $websiteId
     * @return string
     * @throws \Exception
     */
    private function getEmailFromAccountInfo($websiteId)
    {
        $accountInfo = $this->helper->getWebsiteApiClient($websiteId)->getAccountInfo();
        return $this->account->getAccountOwnerEmail($accountInfo);
    }
}
