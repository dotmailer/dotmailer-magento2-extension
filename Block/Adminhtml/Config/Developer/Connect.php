<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Developer;

class Connect extends \Magento\Config\Block\System\Config\Form\Field
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
     * Connect constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data      $helper
     * @param \Dotdigitalgroup\Email\Helper\Config    $configHelper
     * @param \Magento\Backend\Model\Auth             $auth
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array                                   $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Helper\Config $configHelper,
        \Magento\Backend\Model\Auth $auth,
        \Magento\Backend\Block\Template\Context $context,
        $data = []
    ) {
        $this->helper       = $helper;
        $this->configHelper = $configHelper;
        $this->auth         = $auth;

        parent::__construct($context, $data);
    }

    /**
     * @param $buttonLabel
     *
     * @return $this
     */
    public function setButtonLabel($buttonLabel)
    {
        $this->buttonLabel = $buttonLabel;

        return $this;
    }

    /**
     * Get the button and scripts contents.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     * @codingStandardsIgnoreStart
     */
    public function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        //@codingStandardsIgnoreEnd
        $url = $this->getAuthoriseUrl();
        $disabled = false;
        //disable for ssl missing
        if (! $this->_isSecureUrl()) {
            $disabled = true;
        }

        $adminUser = $this->auth->getUser();
        $refreshToken = $adminUser->getRefreshToken();

        $title = ($refreshToken) ? __('Disconnect') : __('Connect');

        $url = ($refreshToken) ? $this->getUrl(
            'dotdigitalgroup_email/studio/disconnect'
        ) : $url;

        return $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )
            ->setType('button')
            ->setLabel(__($title))
            ->setDisabled($disabled)
            ->setOnClick("window.location.href='" . $url . "'")
            ->toHtml();
    }

    /**
     * @return bool
     */
    public function _isSecureUrl() //@codingStandardsIgnoreLine
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
     * Autorisation url for OAUTH.
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
}
