<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Developer;

class Connect extends \Magento\Config\Block\System\Config\Form\Field
{
    protected $_buttonLabel = 'Connect';

    protected $_auth;

    protected $_helper;

    /**
     * Connect constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data      $helper
     * @param \Magento\Backend\Model\Auth             $auth
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array                                   $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Backend\Model\Auth $auth,
        \Magento\Backend\Block\Template\Context $context,
        $data = []
    ) {
        $this->_helper = $helper;
        $this->_auth = $auth;

        parent::__construct($context, $data);
    }

    /**
     * @param $buttonLabel
     *
     * @return $this
     */
    public function setButtonLabel($buttonLabel)
    {
        $this->_buttonLabel = $buttonLabel;

        return $this;
    }

    /**
     * Get the button and scripts contents.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(
        \Magento\Framework\Data\Form\Element\AbstractElement $element
    ) {
        $url = $this->_helper->getAuthoriseUrl();
        $ssl = $this->_checkForSecureUrl();
        $disabled = false;
        //disable for ssl missing
        if (!$ssl) {
            $disabled = true;
        }

        $adminUser = $this->_auth->getUser();
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

    protected function _checkForSecureUrl()
    {
        $baseUrl = $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_WEB, true
        );

        if (!preg_match('/https/', $baseUrl)) {
            return false;
        }

        return $this;
    }
}
