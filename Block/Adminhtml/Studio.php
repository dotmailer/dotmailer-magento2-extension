<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml;

use Dotdigitalgroup\Email\Model\Apiconnector\Client;

/**
 * Class Studio
 * @package Dotdigitalgroup\Email\Block\Adminhtml
 */
class Studio extends \Magento\Backend\Block\Widget\Form
{

    /**
     * Helper config.
     *
     * @var \Dotdigitalgroup\Email\Helper\Config
     */
    public $configFactory;

    /**
     * Helper.
     *
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;

    /**
     * Mage auth model.
     *
     * @var \Magento\Backend\Model\Auth
     */
    public $auth;

    /**
     * Messenger.
     *
     * @var \Magento\Framework\Message\ManagerInterface
     */
    public $messageManager;

    /**
     * Apiconnector client.
     *
     * @var Client
     */
    public $client;

    /**
     * Studio constructor.
     *
     * @param \Magento\Backend\Model\Auth                 $auth
     * @param \Dotdigitalgroup\Email\Helper\Config        $configFactory
     * @param \Dotdigitalgroup\Email\Helper\Data          $dataHelper
     * @param \Magento\Backend\Block\Template\Context     $context
     * @param Client                                      $client
     */
    public function __construct(
        \Magento\Backend\Model\Auth $auth,
        \Dotdigitalgroup\Email\Helper\Config $configFactory,
        \Dotdigitalgroup\Email\Helper\Data $dataHelper,
        \Magento\Backend\Block\Template\Context $context,
        Client $client
    ) {
        $this->client         = $client;
        $this->auth           = $auth;
        $this->helper         = $dataHelper;
        $this->configFactory  = $configFactory;

        parent::__construct($context, []);
    }

    /**
     * Constructor. Initialization required variables for class instance.
     */
    public function _construct()
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
        $token = $this->generateToken();
        $baseUrl = $this->configFactory
            ->getLogUserUrl();

        $loginuserUrl = $baseUrl . $token . '&suppressfooter=true';

        return $loginuserUrl;
    }

    /**
     * Generate new token and connect from the admin.
     *
     * @return string
     */
    public function generateToken()
    {
        $adminUser = $this->auth->getUser();
        $refreshToken = $adminUser->getRefreshToken();

        if ($refreshToken) {
            $token = $this->client->getAccessToken(
                $this->buildUrlParams($refreshToken),
                $this->configFactory->getTokenUrl()
            );

            //save the refresh token to the admin user
            if (is_string($token)) {
                $adminUser->setRefreshToken($token)
                    ->save();
            }

            return $token;
        }
    }

    /**
     * @return mixed
     */
    public function getCode()
    {
        return $this->auth->getUser()->getEmailCode();
    }

    /**
     * Build url param.
     *
     * @param string $refreshToken
     *
     * @return string
     */
    public function buildUrlParams($refreshToken)
    {
        $params = 'client_id=' . $this->helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CLIENT_ID
        )
            . '&client_secret=' . $this->helper->getWebsiteConfig(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CLIENT_SECRET_ID
            )
            . '&refresh_token=' . $refreshToken . '&grant_type=refresh_token';

        return $params;
    }
}
