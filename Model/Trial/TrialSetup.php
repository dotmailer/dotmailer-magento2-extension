<?php

namespace Dotdigitalgroup\Email\Model\Trial;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Config as EmailConfig;
use Dotdigitalgroup\Email\Model\SetsTimezoneAndCultureTrait as SetsTimezoneAndCulture;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Information;

/**
 * Handle the trial account creation.
 */
class TrialSetup
{
    use SetsTimezoneAndCulture;

    /*
     * Sources of account creation
     */
    const SOURCE_ENGAGEMENT_CLOUD = 'ec';
    const SOURCE_CHAT = 'chat';

    /*
     * Magento editions recognised by microsite
     */
    const EDITION_EE = 'Magento EE';
    const EDITION_EE_B2B = 'Magento EE B2B';
    const EDITION_CE = 'Magento CE';

    /**
     * Map of address books to config paths
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
     * @var \Magento\Framework\App\Config\ReinitableConfigInterface
     */
    private $config;

    /**
     * @var \Magento\Framework\Math\Random
     */
    private $randomMath;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\Timezone
     */
    private $timezone;

    /**
     * @var \Dotdigitalgroup\Email\Model\DateIntervalFactory
     */
    private $dateIntervalFactory;

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
     * TrialSetup constructor.
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Framework\Math\Random $randomMath
     * @param \Dotdigitalgroup\Email\Model\Connector\Datafield $dataField
     * @param \Magento\Framework\App\Config\ReinitableConfigInterface $config
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $timezone
     * @param \Dotdigitalgroup\Email\Model\DateIntervalFactory $dateIntervalFactory
     * @param \Magento\Framework\HTTP\PhpEnvironment\ServerAddress $serverAddress
     * @param EncryptorInterface $encryptor
     * @param ProductMetadataInterface $productMetadata
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\Math\Random $randomMath,
        \Dotdigitalgroup\Email\Model\Connector\Datafield $dataField,
        \Magento\Framework\App\Config\ReinitableConfigInterface $config,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
        \Dotdigitalgroup\Email\Model\DateIntervalFactory $dateIntervalFactory,
        \Magento\Framework\HTTP\PhpEnvironment\ServerAddress $serverAddress,
        EncryptorInterface $encryptor,
        ProductMetadataInterface $productMetadata
    ) {
        $this->dateIntervalFactory = $dateIntervalFactory;
        $this->timezone = $timezone;
        $this->randomMath = $randomMath;
        $this->helper = $helper;
        $this->dataField = $dataField;
        $this->config = $config;
        $this->serverAddress = $serverAddress;
        $this->encryptor = $encryptor;
        $this->productMetadata = $productMetadata;
    }

    /**
     * Save api credentials.
     *
     * @param string $apiUser
     * @param string $apiPass
     *
     * @return bool
     */
    public function saveApiCreds($apiUser, $apiPass)
    {
        $this->helper->saveConfigData(
            Config::XML_PATH_CONNECTOR_API_ENABLED,
            '1',
            'default',
            0
        );
        $this->helper->saveConfigData(
            Config::XML_PATH_CONNECTOR_API_USERNAME,
            $apiUser,
            'default',
            0
        );

        //Save encrypted password
        $this->helper->saveConfigData(
            Config::XML_PATH_CONNECTOR_API_PASSWORD,
            $this->encryptor->encrypt($apiPass),
            'default',
            0
        );

        //Clear config cache
        $this->config->reinit();

        return true;
    }

    /**
     * Setup data fields.
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setupDataFields()
    {
        if (!$this->helper->isEnabled()
            || !$apiModel = $this->helper->getWebsiteApiClient()
        ) {
            $this->helper->log('setupDataFields client is not enabled');
            return false;
        }

        //validate account
        $accountInfo = $apiModel->getAccountInfo();
        if (isset($accountInfo->message)) {
            $this->helper->log('setupDataFields ' . $accountInfo->message);
            return false;
        }

        foreach ($this->dataField->getContactDatafields() as $key => $dataField) {
            $apiModel->postDataFields($dataField);
            //map the successfully created data field
            $this->helper->saveConfigData(
                'connector_data_mapping/customer_data/' . $key,
                strtoupper($dataField['name']),
                'default',
                0
            );
            $this->helper->log('setupDataFields successfully connected : ' . $dataField['name']);
        }

        return true;
    }

    /**
     * Create certain address books.
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createAddressBooks()
    {
        $addressBooks = array_map(function ($addressBookData, $addressBookName) {
            return [
                'name' => $addressBookName,
                'visibility' => $addressBookData['visibility'],
            ];
        }, self::$addressBookMap, array_keys(self::$addressBookMap));

        if (!$this->helper->isEnabled()
            || !$client = $this->helper->getWebsiteApiClient()
        ) {
            $this->helper->log('createAddressBooks client is not enabled');
            return false;
        }

        return $this->validateAccountAndCreateAddressbooks($client, $addressBooks);
    }

    /**
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
     *
     * @return null
     */
    public function mapAddressBook($name, $id)
    {
        $this->helper->saveConfigData(self::$addressBookMap[$name]['path'], $id, 'default', 0);
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
            EmailConfig::XML_PATH_CONNECTOR_API_TRIAL_TEMPORARY_PASSCODE_EXPIRY
        );
        if ($now >= $expiryDateString) {
            return false;
        }

        $codeFromConfig = $this->helper->getWebsiteConfig(EmailConfig::XML_PATH_CONNECTOR_API_TRIAL_TEMPORARY_PASSCODE);
        return $codeFromConfig === $code;
    }

    /**
     * Enable certain syncs for newly created trial account.
     *
     * @return bool
     */
    public function enableSyncForTrial()
    {
        $this->helper->saveConfigData(
            Config::XML_PATH_CONNECTOR_SYNC_CUSTOMER_ENABLED,
            '1',
            'default',
            0
        );
        $this->helper->saveConfigData(
            Config::XML_PATH_CONNECTOR_SYNC_GUEST_ENABLED,
            '1',
            'default',
            0
        );
        $this->helper->saveConfigData(
            Config::XML_PATH_CONNECTOR_SYNC_SUBSCRIBER_ENABLED,
            '1',
            'default',
            0
        );
        $this->helper->saveConfigData(
            Config::XML_PATH_CONNECTOR_SYNC_ORDER_ENABLED,
            '1',
            'default',
            0
        );

        //Clear config cache
        $this->config->reinit();

        return true;
    }

    /**
     * @param \Dotdigitalgroup\Email\Model\Apiconnector\Client $client
     * @param array $addressBooks
     *
     * @return bool
     * @throws \Exception
     */
    private function validateAccountAndCreateAddressbooks($client, $addressBooks)
    {
        //validate account
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
                            $this->mapAddressBook($addressBookName, $book->id);
                            break;
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
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
        $this->helper->saveConfigData(
            Config::XML_PATH_CONNECTOR_API_TRIAL_TEMPORARY_PASSCODE,
            $code,
            'default',
            0
        );

        $expiryDate = $this->timezone->date();
        $expiryDate->add($this->dateIntervalFactory->create(['interval_spec' => 'PT30M']));
        $this->helper->saveConfigData(
            Config::XML_PATH_CONNECTOR_API_TRIAL_TEMPORARY_PASSCODE_EXPIRY,
            $expiryDate->format(\DateTime::ATOM),
            'default',
            0
        );

        //Clear config cache
        $this->config->reinit();

        return $code;
    }

    /**
     * Generate url for iframe for trial account popup.
     *
     * @param RequestInterface $request     Request object
     * @param string $source                Source of this account creation
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getEcSignupUrl(RequestInterface $request, string $source = self::SOURCE_ENGAGEMENT_CLOUD)
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

        $store = $this->helper->storeManager->getStore();
        $baseUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_WEB, $store->isCurrentlySecure());
        // @codingStandardsIgnoreLine
        $baseUrlParsed = parse_url($baseUrl);
        $magentoHost = sprintf(
            '%s://%s%s',
            $baseUrlParsed['scheme'],
            $baseUrlParsed['host'],
            isset($magentoHost['port']) ? ':' . $baseUrlParsed['port'] : ''
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
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getLocalCallbackUrl()
    {
        $store = $this->helper->storeManager->getStore();
        return sprintf(
            '%s%s?isAjax=true',
            $store->getBaseUrl(UrlInterface::URL_TYPE_WEB, $store->isCurrentlySecure()),
            \Dotdigitalgroup\Email\Helper\Config::MAGENTO_ROUTE
        );
    }

    /**
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
        return $this->helper->getScopeConfig()->getValue(EmailConfig::XML_PATH_CONNECTOR_TRIAL_URL_OVERRIDE)
            ?: EmailConfig::API_CONNECTOR_TRIAL_FORM_URL;
    }
}
