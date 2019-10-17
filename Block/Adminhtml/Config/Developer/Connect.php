<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Developer;

class Connect extends AbstractDeveloper
{

    /**
     * @var string
     */
    public $buttonLabel = 'Connect';

    /**
     * @var \Magento\Backend\Model\Auth
     */
    public $auth;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    public $sessionModel;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Config
     */
    public $configHelper;

    /**
     * @var String
     */
    private $refreshToken;

    /**
     * Connect constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Dotdigitalgroup\Email\Helper\Config $configHelper
     * @param \Magento\Backend\Model\Auth $auth
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Helper\Config $configHelper,
        \Magento\Backend\Model\Auth $auth,
        $data = []
    ) {
        $this->helper       = $helper;
        $this->configHelper = $configHelper;
        $this->auth         = $auth;

        parent::__construct($context, $data);
    }

    /**
     * @return bool
     */
    protected function getDisabled()
    {
        return !$this->_isSecureUrl();
    }

    /**
     * @return \Magento\Framework\Phrase|string
     */
    protected function getButtonLabel()
    {
        return $this->getRefreshToken() ? __('Disconnect') : __('Connect');
    }

    /**
     * @return string
     */
    protected function getButtonUrl()
    {
        $url = $this->escapeUrl($this->getAuthoriseUrl());

        return $this->getRefreshToken()
            ? $this->escapeUrl($this->getUrl('dotdigitalgroup_email/studio/disconnect'))
            : $url;
    }

    /**
     * @return bool
     */
    public function _isSecureUrl()
    {
        $baseUrl = $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_WEB,
            true
        );

        if (!preg_match('/https/', $baseUrl)) {
            return false;
        }

        return true;
    }

    /**
     * Authorisation url for OAUTH.
     *
     * @return string
     */
    public function getAuthoriseUrl()
    {
        $clientId = $this->_scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CLIENT_ID
        );

        //callback uri if not set custom
        $redirectUri = $this->helper->getRedirectUri();
        $redirectUri .= 'connector/email/callback';

        $adminUser = $this->auth->getUser();

        //query params
        $params = [
            'redirect_uri' => $redirectUri,
            'scope' => 'Account',
            'state' => $adminUser->getId(),
            'response_type' => 'code',
        ];

        $authorizeBaseUrl = $this->configHelper
            ->getAuthorizeLink();
        $url = $authorizeBaseUrl . http_build_query($params)
            . '&client_id=' . $clientId;

        return $url;
    }

    /**
     * Store the refresh token
     *
     * @return string
     */
    private function getRefreshToken()
    {
        if ($this->refreshToken) {
            return $this->refreshToken;
        }

        return $this->refreshToken = $this->auth->getUser()->getRefreshToken();
    }
}
