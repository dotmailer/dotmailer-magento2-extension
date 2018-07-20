<?php

namespace Dotdigitalgroup\Email\Model\Trial;

use Dotdigitalgroup\Email\Helper\Config;

/**
 * Handle the trial account creation.
 */
class TrialSetup
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;

    /**
     * @var \Dotdigitalgroup\Email\Model\Connector\Datafield
     */
    private $dataField;

    /**
     * @var \Magento\Framework\App\Config\ReinitableConfigInterface
     */
    public $config;

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
     * TrialSetup constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Framework\Math\Random $randomMath
     * @param \Dotdigitalgroup\Email\Model\Connector\Datafield $dataField
     * @param \Magento\Framework\App\Config\ReinitableConfigInterface $config
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
     * @param \Dotdigitalgroup\Email\Model\DateIntervalFactory $dateIntervalFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\Math\Random $randomMath,
        \Dotdigitalgroup\Email\Model\Connector\Datafield $dataField,
        \Magento\Framework\App\Config\ReinitableConfigInterface $config,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
        \Dotdigitalgroup\Email\Model\DateIntervalFactory $dateIntervalFactory
    ) {
        $this->dateIntervalFactory = $dateIntervalFactory;
        $this->timezone = $timezone;
        $this->randomMath = $randomMath;
        $this->helper = $helper;
        $this->dataField = $dataField;
        $this->config = $config;
    }

    /**
     * Save api credentioals.
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
            $this->helper->encryptor->encrypt($apiPass),
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
     * @param string $username
     * @param string $password
     *
     * @return bool
     */
    public function setupDataFields($username, $password)
    {
        $error = false;
        $apiModel = false;
        if ($this->helper->isEnabled()) {
            $apiModel = $this->helper->getWebsiteApiClient(0, $username, $password);
        }
        if (!$apiModel) {
            $error = true;
            $this->helper->log('setupDataFields client is not enabled');
        } else {
            //validate account
            $accountInfo = $apiModel->getAccountInfo();
            if (isset($accountInfo->message)) {
                $this->helper->log('setupDataFields ' . $accountInfo->message);
                $error = true;
            } else {
                $dataFields = $this->dataField->getContactDatafields();
                foreach ($dataFields as $key => $dataField) {
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
            }
        }

        return $error == true ? false : true;
    }

    /**
     * Create certain address books.
     *
     * @param string $username
     * @param string $password
     *
     * @return bool
     */
    public function createAddressBooks($username, $password)
    {
        $addressBooks = [
            ['name' => 'Magento_Customers', 'visibility' => 'Private'],
            ['name' => 'Magento_Subscribers', 'visibility' => 'Private'],
            ['name' => 'Magento_Guests', 'visibility' => 'Private'],
        ];
        $client = false;
        if ($this->helper->isEnabled()) {
            $client = $this->helper->getWebsiteApiClient(0, $username, $password);
        }
        if (!$client) {
            $error = true;
            $this->helper->log('createAddressBooks client is not enabled');
        } else {
            $error = $this->validateAccountAndCreateAddressbooks($client, $addressBooks);
        }

        return $error == true ? false : true;
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
        $addressBookMap = [
            'Magento_Customers' => Config::XML_PATH_CONNECTOR_CUSTOMERS_ADDRESS_BOOK_ID,
            'Magento_Subscribers' => Config::XML_PATH_CONNECTOR_SUBSCRIBERS_ADDRESS_BOOK_ID,
            'Magento_Guests' => Config::XML_PATH_CONNECTOR_GUEST_ADDRESS_BOOK_ID,
        ];

        $this->helper->saveConfigData($addressBookMap[$name], $id, 'default', 0);
        $this->helper->log('successfully connected address book : ' . $name);
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
     * Save api endpoint.
     *
     * @param string $value
     *
     * @return null
     */
    public function saveApiEndPoint($value)
    {
        $this->helper->saveConfigData(
            Config::PATH_FOR_API_ENDPOINT,
            $value,
            'default',
            0
        );
    }

    /**
     * @param \Dotdigitalgroup\Email\Model\Apiconnector\Client $client
     * @param array $addressBooks
     *
     * @return bool
     */
    private function validateAccountAndCreateAddressbooks($client, $addressBooks)
    {
        //validate account
        $accountInfo = $client->getAccountInfo();
        $error = false;
        if (isset($accountInfo->message)) {
            $this->helper->log('createAddressBooks ' . $accountInfo->message);
            $error = true;
        } else {
            foreach ($addressBooks as $addressBook) {
                $addressBookName = $addressBook['name'];
                $visibility = $addressBook['visibility'];
                if (!empty($addressBookName)) {
                    $response = $client->postAddressBooks($addressBookName, $visibility);
                    if (isset($response->id)) {
                        $this->mapAddressBook($addressBookName, $response->id);
                    } else { //Need to fetch addressbook id to map. Addressbook already exist.
                        $response = $client->getAddressBooks();
                        if (!isset($response->message)) {
                            foreach ($response as $book) {
                                if ($book->name == $addressBookName) {
                                    $this->mapAddressBook($addressBookName, $book->id);
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $error;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function generateTemporaryPasscode()
    {
        $code = $this->randomMath->getRandomString(32);
        $this->helper->saveConfigData(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_API_TRIAL_TEMPORARY_PASSCODE,
            $code,
            'default',
            0
        );

        $expiryDate = $this->timezone->date();
        $expiryDate->add($this->dateIntervalFactory->create(['interval_spec' => 'PT30M']));
        $this->helper->saveConfigData(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_API_TRIAL_TEMPORARY_PASSCODE_EXPIRY,
            $expiryDate->format(\DateTime::ATOM),
            'default',
            0
        );

        //Clear config cache
        $this->config->reinit();

        return $code;
    }
}
