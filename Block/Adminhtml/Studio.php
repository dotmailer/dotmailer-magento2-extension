<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml;

use Dotdigitalgroup\Email\Model\Apiconnector\Client;

/**
 * Automation studio block
 *
 * @api
 */
class Studio extends \Magento\Backend\Block\Template
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
    private $client;

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
        return (int) $this->getRequest()->getParam('website');
    }

    /**
     * Returns store id.
     *
     * @return int
     * @codeCoverageIgnore
     */
    public function getStoreId()
    {
        return (int) $this->getRequest()->getParam('store');
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
            $accessToken = $this->client->getAccessToken(
                $this->configFactory->getTokenUrl(),
                $this->buildUrlParams(
                    $this->helper->encryptor->decrypt($refreshToken)
                )
            );

            if (is_string($accessToken)) {
                return $accessToken;
            }
        }

        return false;
    }

    /**
     * @return string|null
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
