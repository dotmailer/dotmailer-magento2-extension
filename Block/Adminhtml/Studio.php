<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml;

use Dotdigitalgroup\Email\Model\Apiconnector\Client;

/**
 * Class Studio.
 */
class Studio extends \Magento\Backend\Block\Widget\Form
{

    /**
     * Helper config.
     *
     * @var \Dotdigitalgroup\Email\Helper\Config
     */
    protected $_configFactory;
    /**
     * Helper.
     *
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $_helper;
    /**
     * Mage auth model.
     *
     * @var \Magento\Backend\Model\Auth
     */
    protected $_auth;
    /**
     * Messenger.
     *
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;

    /**
     * Session model.
     *
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $_sessionModel;
    /**
     * Apiconnector client.
     *
     * @var Client
     */
    protected $client;

    /**
     * Studio constructor.
     *
     * @param \Magento\Backend\Model\Auth                 $auth
     * @param \Dotdigitalgroup\Email\Helper\Config        $configFactory
     * @param \Dotdigitalgroup\Email\Helper\Data          $dataHelper
     * @param \Magento\Backend\Block\Template\Context     $context
     * @param \Magento\Backend\Model\Auth\Session         $sessionModel
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param Client                                      $client
     */
    public function __construct(
        \Magento\Backend\Model\Auth $auth,
        \Dotdigitalgroup\Email\Helper\Config $configFactory,
        \Dotdigitalgroup\Email\Helper\Data $dataHelper,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Model\Auth\Session $sessionModel,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        Client $client
    ) {
        $this->client = $client;
        $this->_auth = $auth;
        $this->_helper = $dataHelper;
        $this->_sessionModel = $sessionModel;
        $this->_configFactory = $configFactory;
        $this->_messageManager = $messageManager;

        parent::__construct($context, []);
    }

    /**
     * Constructor. Initialization required variables for class instance.
     */
    protected function _construct()
    {
        $this->_blockGroup = 'Dotdigitalgroup\Email';
        $this->_controller = 'adminhtml_studio';
        parent::_construct();
    }

    /**
     * Returns page header.
     *
     * @return \Magento\Framework\Phrase
     * @codeCoverageIgnore
     */
    public function getHeader()
    {
        return __('Automation');
    }

    /**
     * Returns URL for save action.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getFormActionUrl()
    {
        return $this->getUrl('adminhtml/*/save');
    }

    /**
     * Returns website id.
     *
     * @return int
     * @codeCoverageIgnore
     */
    public function getWebsiteId()
    {
        return $this->getRequest()->getParam('website');
    }

    /**
     * Returns store id.
     *
     * @return int
     * @codeCoverageIgnore
     */
    public function getStoreId()
    {
        return $this->getRequest()->getParam('store');
    }

    /**
     * Returns inheritance text.
     *
     * @return \Magento\Framework\Phrase
     * @codeCoverageIgnore
     */
    public function getInheritText()
    {
        return __('Use Standard');
    }

    /**
     * User login url.
     *
     * @return string
     */
    public function getLoginUserHtml()
    {
        // authorize or create token.
        $token = $this->generatetokenAction();
        $baseUrl = $this->_configFactory
            ->getLogUserUrl();

        $loginuserUrl = $baseUrl . $token . '&suppressfooter=true';

        return $loginuserUrl;
    }

    /**
     * Generate new token and connect from the admin.
     *
     * @return string
     */
    public function generatetokenAction()
    {
        $adminUser = $this->_auth->getUser();
        $refreshToken = $adminUser->getRefreshToken();

        if ($refreshToken) {
            $token = $this->client->getAccessToken(
                $this->buildUrlParams($refreshToken),
                $this->_configFactory->getTokenUrl()
            );

            //save the refresh token to the admin user
            if (is_string($token)) {
                $adminUser->setRefreshToken($token)
                    ->save();
            }
            return $token;
        } else {
            $this->_messageManager->addNoticeMessage('Please Connect To Access The Page.');
        }
    }

    /**
     * Retrieve authorisation code.
     *
     * @return string
     */
    public function getCode()
    {
        $adminUser = $this->_sessionModel->getUser();
        $code = $adminUser->getEmailCode();

        return $code;
    }

    /**
     * Build url param.
     *
     * @param string $refreshToken
     *
     * @return string
     */
    protected function buildUrlParams($refreshToken)
    {
        $params = 'client_id=' . $this->_helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CLIENT_ID
        )
            . '&client_secret=' . $this->_helper->getWebsiteConfig(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CLIENT_SECRET_ID
            )
            . '&refresh_token=' . $refreshToken . '&grant_type=refresh_token';

        return $params;
    }
}
