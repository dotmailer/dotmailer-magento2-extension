<?php

namespace Dotdigitalgroup\Email\Model\Integration;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Connector\Datafield;
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
use Magento\Framework\HTTP\PhpEnvironment\ServerAddress;
use Magento\Framework\Math\Random;
use Magento\Framework\Stdlib\DateTime\Timezone;
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
     * @var Data
     */
    private $helper;

    /**
     * @var Datafield
     */
    private $dataField;

    /**
     * @var Random
     */
    private $randomMath;

    /**
     * @var Timezone
     */
    private $timezone;

    /**
     * @var ServerAddress
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
     * @param Data $helper
     * @param Random $randomMath
     * @param Datafield $dataField
     * @param CronCollectionFactory $cronCollectionFactory
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param Timezone $timezone
     * @param ServerAddress $serverAddress
     * @param ProductMetadataInterface $productMetadata
     * @param EncryptorInterface $encryptor
     * @param StoreManagerInterface $storeManager
     * @param ResourceConfig $resourceConfig
     * @param Orders $orderData
     * @param Products $productData
     * @param ReinitableConfigInterface $reinitableConfig
     */
    public function __construct(
        Data $helper,
        Random $randomMath,
        Datafield $dataField,
        CronCollectionFactory $cronCollectionFactory,
        OrderCollectionFactory $orderCollectionFactory,
        ScopeConfigInterface $scopeConfig,
        Timezone $timezone,
        ServerAddress $serverAddress,
        ProductMetadataInterface $productMetadata,
        EncryptorInterface $encryptor,
        StoreManagerInterface $storeManager,
        ResourceConfig $resourceConfig,
        Orders $orderData,
        Products $productData,
        ReinitableConfigInterface $reinitableConfig
    ) {
        $this->helper = $helper;
        $this->randomMath = $randomMath;
        $this->dataField = $dataField;
        $this->cronCollectionFactory = $cronCollectionFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->timezone = $timezone;
        $this->serverAddress = $serverAddress;
        $this->productMetadata = $productMetadata;
        $this->encryptor = $encryptor;
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
    public function setupDataFields($websiteId = 0): bool
    {
        $apiModel = $this->helper->getWebsiteApiClient($websiteId);
        $dotdigitalDataFields  = array_column((array) $apiModel->getDataFields(), 'name');
        foreach ($this->dataField->getContactDatafields() as $key => $dataField) {
            $response = $apiModel->postDataFields($dataField);
            //if request to create fails, make sure it exists in dotdigital
            if (!empty($response->message) && (!in_array($dataField['name'], $dotdigitalDataFields))
            ) {
                continue;
            }
            //map the successfully created data field
            $this->saveConfigData(
                'connector_data_mapping/customer_data/' . $key,
                strtoupper($dataField['name']),
                $this->getScope($websiteId),
                $websiteId
            );
            $mappedDataFields[] = $key;
            $this->helper->log('setupDataFields successfully connected : ' . $dataField['name']);
        }

        return !empty($mappedDataFields);
    }

    /**
     * Create certain address books.
     *
     * @param int $websiteId
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    public function createAddressBooks(int $websiteId = 0): bool
    {
        $websiteName = $websiteId ? $this->getWebsiteName($websiteId) : null;

        $addressBooks = array_map(function ($addressBookName, $addressBookData) use ($websiteName) {
            return [
                'name' => $this->getAddressBookName($websiteName, $addressBookName),
                'visibility' => $addressBookData['visibility'],
            ];
        }, array_keys(self::$addressBookMap), self::$addressBookMap);

        return $this->postAndMapAddressbooks($addressBooks, $websiteId);
    }

    /**
     * Get address book map.
     *
     * @return array
     */
    public function getAddressBookMap(): array
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
    public function isCodeValid($code): bool
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
    public function enableSyncs($websiteId = 0): bool
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
    public function sendOrders($websiteId): bool
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
    public function sendProducts($websiteId): bool
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
    public function checkCrons(): bool
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
     * Create address books Dotdigital-side and map them in Magento.
     *
     * @param array $addressBooks
     * @param int|string $websiteId
     *
     * @return bool
     * @throws \Exception
     */
    private function postAndMapAddressbooks($addressBooks, $websiteId = 0): bool
    {
        $client = $this->helper->getWebsiteApiClient($websiteId);
        foreach ($addressBooks as $addressBook) {
            $addressBookName = $addressBook['name'];
            $visibility = $addressBook['visibility'];

            if (!empty($addressBookName)) {
                $response = $client->postAddressBooks($addressBookName, $visibility);

                if (isset($response->id)) {
                    $this->mapAddressBook($addressBookName, $response->id);
                    $mappedAddressBooks[] = $addressBookName;
                } else {
                    //Need to fetch address book id to map. Address-book already exist.
                    $response = $client->getAddressBooks();
                    if (isset($response->message)) {
                        continue;
                    }
                    foreach ($response as $book) {
                        if ($book->name == $addressBookName) {
                            $this->mapAddressBook($addressBookName, $book->id, $websiteId);
                            $mappedAddressBooks[] = $book->name;
                            break;
                        }
                    }
                }
            }
        }

        return !empty($mappedAddressBooks);
    }

    /**
     * Generate temporary passcode.
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function generateTemporaryPasscode(): string
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
    public function getEcSignupUrl(Http $request, string $source = self::SOURCE_ENGAGEMENT_CLOUD): string
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
    public function getLocalCallbackUrl(): string
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
    public function getTrialSignupHostAndScheme(): string
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
    public function getTrialSignupBaseUrl(): string
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
    public function enableEasyEmailCapture($websiteId = 0): bool
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
    private function getWebsiteName($websiteId): string
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
    private function setScopeForDefaultLevel($websiteId): int
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
