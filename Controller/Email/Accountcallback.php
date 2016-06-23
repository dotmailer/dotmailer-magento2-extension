<?php

namespace Dotdigitalgroup\Email\Controller\Email;

class Accountcallback extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $_jsonHelper;
    /**
     * @var \Dotdigitalgroup\Email\Model\Connector\Datafield
     */
    protected $_dataField;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var array
     */
    protected $_ipRange = [
        '104.40.179.234',
        '104.40.159.161',
        '191.233.82.46',
        '104.46.48.100',
        '104.40.187.26'
    ];
    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    protected $_remoteAddress;
    /**
     * @var \Magento\Framework\App\Config\ReinitableConfigInterface
     */
    protected $config;

    /**
     * Accountcallback constructor.
     *
     * @param \Magento\Framework\App\Action\Context                   $context
     * @param \Dotdigitalgroup\Email\Helper\Data                      $helper
     * @param \Magento\Framework\Json\Helper\Data                     $jsonHelper
     * @param \Magento\Store\Model\StoreManagerInterface              $storeManager
     * @param \Dotdigitalgroup\Email\Model\Connector\Datafield        $dataField
     * @param \Magento\Framework\App\Config\ReinitableConfigInterface $config
     * @param \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress    $remoteAddress
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Dotdigitalgroup\Email\Model\Connector\Datafield $dataField,
        \Magento\Framework\App\Config\ReinitableConfigInterface $config,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress
    ) {
        $this->_helper = $helper;
        $this->_jsonHelper = $jsonHelper;
        $this->_dataField = $dataField;
        $this->_storeManager = $storeManager;
        $this->_remoteAddress = $remoteAddress;
        $this->config = $config;

        parent::__construct($context);
    }

    /**
     * Execute method.
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();

        //if ip is not in range or any of the required params not set send error response
        if (!in_array($this->_remoteAddress->getRemoteAddress(), $this->_ipRange) or
            !isset($params['accountId']) or !isset($params['apiUser']) or !isset($params['pass'])
        ) {
            $this->sendAjaxResponse(true, $this->_getErrorHtml());
        }

        //if no value to any of the required params send error response
        if (empty($params['accountId']) or empty($params['apiUser']) or empty($params['pass'])) {
            $this->sendAjaxResponse(true, $this->_getErrorHtml());
        }

        $apiConfigStatus = $this->_saveApiCreds($params['apiUser'], $params['pass']);
        $dataFieldsStatus = $this->_setupDataFields($params['apiUser'], $params['pass']);
        $addressBookStatus = $this->_createAddressBooks($params['apiUser'], $params['pass']);
        $syncStatus = $this->_enableSyncForTrial();
        if (isset($params['apiEndpoint'])) {
            $this->_saveApiEndPoint($params['apiEndpoint']);
        }
        if ($apiConfigStatus && $dataFieldsStatus && $addressBookStatus && $syncStatus) {
            $this->sendAjaxResponse(false, $this->_getSuccessHtml());
        } else {
            $this->sendAjaxResponse(true, $this->_getErrorHtml());
        }
    }

    /**
     * Send ajax response.
     *
     * @param $error
     * @param $msg
     */
    public function sendAjaxResponse($error, $msg)
    {
        $message = [
            'err' => $error,
            'message' => $msg,
        ];
        $this->getResponse()->setBody(
            $this->getRequest()->getParam('callback') . '(' . $this->_jsonHelper->jsonEncode($message) . ')'
        )->sendResponse();
    }

    /**
     * Get success html.
     *
     * @return string
     */
    protected function _getSuccessHtml()
    {
        return
            "<div class='modal-page'>
                <div class='success'></div>
                <h2 class='center'>Congratulations your dotmailer account is now ready,
                 time to make your marketing awesome</h2>
                <div class='center'>
                    <input type='submit' class='center' value='Start making money' />
                </div>
            </div>";
    }

    /**
     * Get error html.
     *
     * @return string
     */
    protected function _getErrorHtml()
    {
        return
            "<div class='modal-page'>
                <div class='fail'></div>
                <h2 class='center'>Sorry, something went wrong whilst trying to create your new dotmailer account</h2>
                <div class='center'>
                    <a class='submit secondary center' href='mailto:support@dotmailer.com'>
                    Contact support@dotmailer.com</a>
                </div>
            </div>";
    }

    /**
     * Save api credentioals.
     *
     * @param $apiUser
     * @param $apiPass
     *
     * @return bool
     */
    protected function _saveApiCreds($apiUser, $apiPass)
    {
        $this->_helper->saveConfigData(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_API_ENABLED, '1', 'default', 0
        );
        $this->_helper->saveConfigData(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_API_USERNAME, $apiUser, 'default', 0
        );
        $this->_helper->saveConfigData(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_API_PASSWORD, $apiPass, 'default', 0
        );

        //Clear config cache
        $this->config->reinit();

        return true;
    }

    /**
     * Setup data fields.
     *
     * @param $username
     * @param $password
     *
     * @return bool
     */
    protected function _setupDataFields($username, $password)
    {
        $error = false;
        $apiModel = $this->_helper->getWebsiteApiClient(0, $username, $password);
        if (!$apiModel) {
            $error = true;
            $this->_helper->log('setupDataFields client is not enabled');
        } else {
            $dataFields = $this->_dataField->getContactDatafields();
            foreach ($dataFields as $key => $dataField) {
                $response = $apiModel->postDataFields($dataField);
                //ignore existing datafields message
                if (isset($response->message) &&
                    $response->message != \Dotdigitalgroup\Email\Model\Apiconnector\Client::API_ERROR_DATAFIELD_EXISTS
                ) {
                    $error = true;
                } else {
                    //map the successfully created data field
                    $this->_helper->saveConfigData(
                        'connector_data_mapping/customer_data/' . $key,
                        strtoupper($dataField['name']), 'default', 0);
                    $this->_helper->log('successfully connected : ' . $dataField['name']);
                }
            }
        }

        return $error == true ? false : true;
    }

    /**
     * Create certain address books.
     *
     * @param $username
     * @param $password
     *
     * @return bool
     */
    protected function _createAddressBooks($username, $password)
    {
        $addressBooks = [
            ['name' => 'Magento_Customers', 'visibility' => 'Private'],
            ['name' => 'Magento_Subscribers', 'visibility' => 'Private'],
            ['name' => 'Magento_Guests', 'visibility' => 'Private'],
        ];
        $addressBookMap = [
            'Magento_Customers' => \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOMERS_ADDRESS_BOOK_ID,
            'Magento_Subscribers' => 
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SUBSCRIBERS_ADDRESS_BOOK_ID,
            'Magento_Guests' => \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_GUEST_ADDRESS_BOOK_ID,
        ];
        $error = false;
        $client = $this->_helper->getWebsiteApiClient(0, $username, $password);
        if (!$client) {
            $error = true;
            $this->_helper->log('createAddressBooks client is not enabled');
        } else {
            foreach ($addressBooks as $addressBook) {
                $addressBookName = $addressBook['name'];
                $visibility = $addressBook['visibility'];
                if (strlen($addressBookName)) {
                    $response = $client->postAddressBooks($addressBookName, $visibility);
                    if (isset($response->message)) {
                        $error = true;
                    } else {
                        //map the successfully created address book
                        $this->_helper->saveConfigData($addressBookMap[$addressBookName], $response->id, 'default', 0);
                        $this->_helper->log('successfully connected address book : ' . $addressBookName);
                    }
                }
            }
        }

        return $error == true ? false : true;
    }

    /**
     * Enable certain syncs for newly created trial account.
     *
     * @return bool
     */
    protected function _enableSyncForTrial()
    {
        $this->_helper->saveConfigData(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_CUSTOMER_ENABLED, '1', 'default', 0
        );
        $this->_helper->saveConfigData(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_GUEST_ENABLED, '1', 'default', 0
        );
        $this->_helper->saveConfigData(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_SUBSCRIBER_ENABLED, '1', 'default', 0
        );
        $this->_helper->saveConfigData(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_ORDER_ENABLED, '1', 'default', 0
        );

        return true;
    }

    /**
     * Save api endpoint.
     *
     * @param $value
     */
    protected function _saveApiEndPoint($value)
    {
        $this->_helper->saveConfigData(
            \Dotdigitalgroup\Email\Helper\Config::PATH_FOR_API_ENDPOINT, $value, 'default', 0
        );
    }

    /**
     * Check if both frotnend and backend secure(HTTPS).
     *
     * @return bool
     */
    protected function _isFrontendAdminSecure()
    {
        $frontend = $this->_storeManager->getStore()->isFrontUrlSecure();
        $admin = $this->_helper->getWebsiteConfig(\Magento\Store\Model\Store::XML_PATH_SECURE_IN_ADMINHTML);
        $current = $this->_storeManager->getStore()->isCurrentlySecure();

        if ($frontend && $admin && $current) {
            return true;
        }

        return false;
    }
}
