<?php

namespace Dotdigitalgroup\Email\Model\Integration;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Integration\Data\Orders;
use Dotdigitalgroup\Email\Model\Integration\Data\Products;
use Dotdigitalgroup\Email\Model\ResourceModel\Cron\CollectionFactory as CronCollectionFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Dotdigitalgroup\Email\Model\SetsTimezoneAndCultureTrait as SetsTimezoneAndCulture;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Information;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Config\Model\ResourceModel\Config as ResourceConfig;

/**
 * Handle the trial account creation and integration setup.
 */
class IntegrationSetup
{
    use SetsTimezoneAndCulture;

    /*
     * Sources of account creation
     */
    public const SOURCE_ENGAGEMENT_CLOUD = 'ec';
    public const SOURCE_CHAT = 'chat';

    /*
     * Magento editions recognised by microsite
     */
    public const EDITION_EE = 'Magento EE';
    public const EDITION_EE_B2B = 'Magento EE B2B';
    public const EDITION_CE = 'Magento CE';

    /**
     * Map of address books to config paths.
     *
     * @var array[]
     */
    private static $addressBookMap = [
        'Magento Customers' => [
            'visibility' => 'Private',
            'path' => Config::XML_PATH_CONNECTOR_CUSTOMERS_ADDRESS_BOOK_ID,
        ],
        'Magento Subscribers' => [
            'visibility' => 'Private',
            'path' => Config::XML_PATH_CONNECTOR_SUBSCRIBERS_ADDRESS_BOOK_ID,
        ],
        'Magento Guests' => [
            'visibility' => 'Private',
            'path' => Config::XML_PATH_CONNECTOR_GUEST_ADDRESS_BOOK_ID,
        ],
    ];

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Dotdigitalgroup\Email\Model\Connector\Datafield
     */
    private $dataField;

    /**
     * @var \Magento\Framework\Math\Random
     */
    private $randomMath;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\Timezone
     */
    private $timezone;

    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\ServerAddress
     */
    private $serverAddress;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ResourceConfig
     */
    private $resourceConfig;

    /**
     * @var CronCollectionFactory
     */
    private $cronCollectionFactory;

    /**
     * @var OrderCollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Orders
     */
    private $orderData;

    /**
     * @var Products
     */
    private $productData;

    /**
     * @var ReinitableConfigInterface
     */
    private $reinitableConfig;

    /**
     * IntegrationSetup constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Framework\Math\Random $randomMath
     * @param \Dotdigitalgroup\Email\Model\Connector\Datafield $dataField
     * @param CronCollectionFactory $cronCollectionFactory
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $timezone
     * @param \Magento\Framework\HTTP\PhpEnvironment\ServerAddress $serverAddress
     * @param EncryptorInterface $encryptor
     * @param ProductMetadataInterface $productMetadata
     * @param StoreManagerInterface $storeManager
     * @param ResourceConfig $resourceConfig
     * @param Orders $orderData
     * @param Products $productData
     * @param ReinitableConfigInterface $reinitableConfig
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\Math\Random $randomMath,
        \Dotdigitalgroup\Email\Model\Connector\Datafield $dataField,
        CronCollectionFactory $cronCollectionFactory,
        OrderCollectionFactory $orderCollectionFactory,
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
        \Magento\Framework\HTTP\PhpEnvironment\ServerAddress $serverAddress,
        EncryptorInterface $encryptor,
        ProductMetadataInterface $productMetadata,
        StoreManagerInterface $storeManager,
        ResourceConfig $resourceConfig,
        Orders $orderData,
        Products $productData,
        ReinitableConfigInterface $reinitableConfig
    ) {
        $this->timezone = $timezone;
        $this->randomMath = $randomMath;
        $this->helper = $helper;
        $this->dataField = $dataField;
        $this->cronCollectionFactory = $cronCollectionFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->serverAddress = $serverAddress;
        $this->encryptor = $encryptor;
        $this->productMetadata = $productMetadata;
        $this->storeManager = $storeManager;
        $this->resourceConfig = $resourceConfig;
        $this->orderData = $orderData;
        $this->productData = $productData;
        $this->reinitableConfig = $reinitableConfig;
    }

    /**
     * Setup data fields.
     *
     * @param int $websiteId
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setupDataFields($websiteId = 0)
    {
        $apiModel = $this->helper->getWebsiteApiClient($websiteId);

        //validate account
        $accountInfo = $apiModel->getAccountInfo();
        if (isset($accountInfo->message)) {
            $this->helper->log('setupDataFields ' . $accountInfo->message);
            return false;
        }

        foreach ($this->dataField->getContactDatafields() as $key => $dataField) {
            $apiModel->postDataFields($dataField);
            //map the successfully created data field
            $this->saveConfigData(
                'connector_data_mapping/customer_data/' . $key,
                strtoupper($dataField['name']),
                $this->getScope($websiteId),
                $websiteId
            );
            $this->helper->log('setupDataFields successfully connected : ' . $dataField['name']);
        }

        return true;
    }

    /**
     * Create certain address books.
     *
     * @param int $websiteId
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createAddressBooks(int $websiteId = 0)
    {
        $websiteName = $websiteId ? $this->getWebsiteName($websiteId) : null;

        $addressBooks = array_map(function ($addressBookName, $addressBookData) use ($websiteName) {
            return [
                'name' => $this->getAddressBookName($websiteName, $addressBookName),
                'visibility' => $addressBookData['visibility'],
            ];
        }, array_keys(self::$addressBookMap), self::$addressBookMap);

        return $this->validateAccountAndCreateAddressbooks($addressBooks, $websiteId);
    }

    /**
     * Get address book map.
     *
     * @return array
     */
    public function getAddressBookMap()
    {
        return self::$addressBookMap;
    }

    /**
     * Map the successfully created address book
     *
     * @param string $name
     * @param int $id
     * @param int|string $websiteId
     *
     * @return void
     */
    public function mapAddressBook($name, $id, $websiteId = 0)
    {
        //Website level
        if (strpos($name, '-') !== false) {
            $name = substr(explode("-", $name)[1], 1);
        }

        $this->saveConfigData(
            self::$addressBookMap[$name]['path'],
            $id,
            $this->getScope($websiteId),
            $websiteId
        );

        $this->helper->log('successfully connected address book : ' . $name);
    }

    /**
     * Validate code
     *
     * @param string $code
     * @return bool
     */
    public function isCodeValid($code)
    {
        $now = $this->timezone->date()->format(\DateTime::ATOM);
        $expiryDateString = $this->helper->getWebsiteConfig(
            Config::XML_PATH_CONNECTOR_API_TRIAL_TEMPORARY_PASSCODE_EXPIRY
        );
        if ($now >= $expiryDateString) {
            return false;
        }

        $codeFromConfig = $this->helper->getWebsiteConfig(Config::XML_PATH_CONNECTOR_API_TRIAL_TEMPORARY_PASSCODE);
        return $codeFromConfig === $code;
    }

    /**
     * Enable syncs.
     *
     * @param int|string $websiteId
     * @return bool
     */
    public function enableSyncs($websiteId = 0)
    {
        $this->saveConfigData(
            Config::XML_PATH_CONNECTOR_SYNC_CUSTOMER_ENABLED,
            '1',
            $this->getScope($websiteId),
            $websiteId
        );
        $this->saveConfigData(
            Config::XML_PATH_CONNECTOR_SYNC_GUEST_ENABLED,
            '1',
            $this->getScope($websiteId),
            $websiteId
        );
        $this->saveConfigData(
            Config::XML_PATH_CONNECTOR_SYNC_SUBSCRIBER_ENABLED,
            '1',
            $this->getScope($websiteId),
            $websiteId
        );
        $this->saveConfigData(
            Config::XML_PATH_CONNECTOR_SYNC_ORDER_ENABLED,
            '1',
            $this->getScope($websiteId),
            $websiteId
        );
        $this->saveConfigData(
            Config::XML_PATH_CONNECTOR_SYNC_CATALOG_ENABLED,
            '1',
            $this->getScope($websiteId),
            $websiteId
        );

        return true;
    }

    /**
     * Send orders.
     *
     * @param string|int $websiteId
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function sendOrders($websiteId)
    {
        return $this->orderData->prepareAndSend(
            $this->setScopeForDefaultLevel($websiteId)
        );
    }

    /**
     * Send products.
     *
     * @param string|int $websiteId
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function sendProducts($websiteId)
    {
        return $this->productData->prepareAndSend(
            $this->setScopeForDefaultLevel($websiteId)
        );
    }

    /**
     * Check crons.
     *
     * @return bool
     */
    public function checkCrons()
    {
        $fromTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $toTime = clone $fromTime;
        $toTime->add(new \DateInterval('PT1H'));

        $timeWindow = [
            'from' => $fromTime->format('Y-m-d H:i:s'),
            'to' => $toTime->format('Y-m-d H:i:s'),
            'date' => true,
        ];

        $ddgCronTasks = $this->cronCollectionFactory->create()
            ->fetchPendingCronTasksScheduledInNextHour($timeWindow);

        return (bool) $ddgCronTasks->getSize();
    }

    /**
     * Validate account and create address books.
     *
     * @param array $addressBooks
     * @param int|string $websiteId
     *
     * @return bool
     * @throws \Exception
     */
    private function validateAccountAndCreateAddressbooks($addressBooks, $websiteId = 0)
    {
        $client = $this->helper->getWebsiteApiClient($websiteId);
        $accountInfo = $client->getAccountInfo();

        if (isset($accountInfo->message)) {
            $this->helper->log('createAddressBooks ' . $accountInfo->message);
            return false;
        }

        foreach ($addressBooks as $addressBook) {
            $addressBookName = $addressBook['name'];
            $visibility = $addressBook['visibility'];

            if (!empty($addressBookName)) {
                $response = $client->postAddressBooks($addressBookName, $visibility);

                if (isset($response->id)) {
                    $this->mapAddressBook($addressBookName, $response->id);
                } else {
                    //Need to fetch addressbook id to map. Addressbook already exist.
                    $response = $client->getAddressBooks();
                    if (isset($response->message)) {
                        continue;
                    }
                    foreach ($response as $book) {
                        if ($book->name == $addressBookName) {
                            $this->mapAddressBook($addressBookName, $book->id, $websiteId);
                            break;
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * Generate temporary passcode.
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function generateTemporaryPasscode()
    {
        // remove any previous passcode
        $this->helper->resourceConfig->deleteConfig(
            Config::XML_PATH_CONNECTOR_API_TRIAL_TEMPORARY_PASSCODE,
            'default',
            0
        );

        $code = $this->randomMath->getRandomString(32);
        $this->saveConfigData(
            Config::XML_PATH_CONNECTOR_API_TRIAL_TEMPORARY_PASSCODE,
            $code,
            'default',
            0
        );

        $expiryDate = $this->timezone->date();
        $expiryDate->add(new \DateInterval('PT30M'));
        $this->saveConfigData(
            Config::XML_PATH_CONNECTOR_API_TRIAL_TEMPORARY_PASSCODE_EXPIRY,
            $expiryDate->format(\DateTime::ATOM),
            'default',
            0
        );

        //Clear config cache
        $this->reinitableConfig->reinit();

        return $code;
    }

    /**
     * Generate url for iframe for trial account popup.
     *
     * @param Http $request Request object
     * @param string $source Source of this account creation
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getEcSignupUrl(Http $request, string $source = self::SOURCE_ENGAGEMENT_CLOUD)
    {
        $trialSignupBaseUrl = $this->getTrialSignupBaseUrl();
        $ipAddress = $this->serverAddress->getServerAddress();

        // get the forward ip address for the request
        if ($ipAddress) {
            $ipAddress = $request->getServer('HTTP_X_FORWARDED_FOR', $ipAddress);
            //get the first ip
            if (strpos($ipAddress, ',') !== false) {
                $ipList = explode(',', $ipAddress);
                $ipAddress = trim(reset($ipList));
            }
        }

        /** @var Store $store */
        $store = $this->storeManager->getStore();
        $baseUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_WEB, $store->isCurrentlySecure());
        // @codingStandardsIgnoreLine
        $baseUrlParsed = parse_url($baseUrl);
        $magentoHost = sprintf(
            '%s://%s%s',
            $baseUrlParsed['scheme'],
            $baseUrlParsed['host'],
            isset($baseUrlParsed['port']) ? ':' . $baseUrlParsed['port'] : ''
        );

        // get the magento edition
        switch ($this->productMetadata->getEdition()) {
            case 'B2B':
                $magentoEdition = self::EDITION_EE_B2B;
                break;

            case 'Enterprise':
                $magentoEdition = self::EDITION_EE;
                break;

            default:
                $magentoEdition = self::EDITION_CE;
        }

        return sprintf(
            '%s?%s',
            $trialSignupBaseUrl,
            http_build_query([
                'callback' => $baseUrl . \Dotdigitalgroup\Email\Helper\Config::MAGENTO_ROUTE,
                'company' => $this->helper->getWebsiteConfig(Information::XML_PATH_STORE_INFO_NAME),
                'culture' => $this->getCultureId(),
                'timezone' => $this->getTimeZoneId(),
                'code' => $this->generateTemporaryPasscode(),
                'magentohost' => $magentoHost,
                'magentoedition' => $magentoEdition,
                'ip' => $ipAddress,
                'source' => $source,
            ])
        );
    }

    /**
     * Get callback url.
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getLocalCallbackUrl()
    {
        /** @var Store $store */
        $store = $this->storeManager->getStore();
        return sprintf(
            '%s%s?isAjax=true',
            $store->getBaseUrl(UrlInterface::URL_TYPE_WEB, $store->isCurrentlySecure()),
            \Dotdigitalgroup\Email\Helper\Config::MAGENTO_ROUTE
        );
    }

    /**
     * Get integration signup host and scheme.
     *
     * @return string
     */
    public function getTrialSignupHostAndScheme()
    {
        // @codingStandardsIgnoreLine
        $url = parse_url($this->getTrialSignupBaseUrl());
        return sprintf(
            '%s://%s%s',
            $url['scheme'],
            $url['host'],
            isset($url['port']) ? ':' . $url['port'] : ''
        );
    }

    /**
     * Get the URL for signing up to Engagement Cloud
     *
     * @return string
     */
    public function getTrialSignupBaseUrl()
    {
        return $this->helper->getScopeConfig()->getValue(Config::XML_PATH_CONNECTOR_TRIAL_URL_OVERRIDE)
            ?: Config::API_CONNECTOR_TRIAL_FORM_URL;
    }

    /**
     * Enable easy email capture.
     *
     * @param int|string $websiteId
     * @return bool
     */
    public function enableEasyEmailCapture($websiteId = 0)
    {
        $this->saveConfigData(
            Config::XML_PATH_CONNECTOR_EMAIL_CAPTURE_NEWSLETTER,
            '1',
            $this->getScope($websiteId),
            $websiteId
        );

        $this->saveConfigData(
            Config::XML_PATH_CONNECTOR_EMAIL_CAPTURE,
            '1',
            $this->getScope($websiteId),
            $websiteId
        );

        return true;
    }

    /**
     * Get website name.
     *
     * @param int|string $websiteId
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getWebsiteName($websiteId)
    {
        return $this->storeManager->getWebsite($websiteId)->getName();
    }

    /**
     * Get website scoped address book name.
     *
     * @param string|null $websiteName
     * @param string $addressBookName
     * @return string
     */
    private function getAddressBookName(?string $websiteName, string $addressBookName): string
    {
        if ($websiteName) {
            return $websiteName . ' - ' . $addressBookName;
        }

        return $addressBookName;
    }

    /**
     * Get scope.
     *
     * @param int|string $websiteId
     * @return string
     */
    private function getScope($websiteId): string
    {
        return $websiteId ? 'websites' : 'default';
    }

    /**
     * Save config data.
     *
     * @param string $path
     * @param string $value
     * @param string $scope
     * @param string|int $scopeId
     */
    private function saveConfigData($path, $value, $scope, $scopeId)
    {
        $this->resourceConfig->saveConfig(
            $path,
            $value,
            $scope,
            $scopeId
        );
    }

    /**
     * Set scope if script is running at default level.
     *
     * Loop through connected websites, as soon as we find a website
     * connected to the same account, return its ID.
     *
     * @param string|int $websiteId
     *
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function setScopeForDefaultLevel($websiteId)
    {
        if ($websiteId != 0) {
            return (int) $websiteId;
        }

        $websites = $this->storeManager->getWebsites();
        $defaultLevelApiUser = $this->helper->getApiUsername();

        /** @var \Magento\Store\Model\Website $website */
        foreach ($websites as $website) {
            if ($this->helper->isEnabled($website->getId()) &&
                $defaultLevelApiUser === $this->helper->getApiUsername($website->getId())
            ) {
                return (int) $website->getId();
            }
        }

        return 0;
    }
}
