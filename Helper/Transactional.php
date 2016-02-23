<?php

namespace Dotdigitalgroup\Email\Helper;

class Transactional extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_DDG_TRANSACTIONAL_ENABLED   = 'transactional_emails/ddg_transactional/enabled';
    const XML_PATH_DDG_TRANSACTIONAL_HOST      = 'transactional_emails/ddg_transactional/host';
    const XML_PATH_DDG_TRANSACTIONAL_USERNAME  = 'transactional_emails/ddg_transactional/username';
    const XML_PATH_DDG_TRANSACTIONAL_PASSWORD  = 'transactional_emails/ddg_transactional/password';
    const XML_PATH_DDG_TRANSACTIONAL_PORT      = 'transactional_emails/ddg_transactional/port';
    const XML_PATH_DDG_TRANSACTIONAL_DEBUG     = 'transactional_emails/ddg_transactional/debug';

    protected $_storeManager;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    )
    {
        $this->_storeManager = $storeManager;

        parent::__construct($context);
    }

    public function isEnabled()
    {
        $store = $this->_storeManager->getStore();
        return $this->scopeConfig->getValue(self::XML_PATH_DDG_TRANSACTIONAL_ENABLED, 'store', $store);
    }

    public function getSmtpHost()
    {
        $store = $this->_storeManager->getStore();
        return $this->scopeConfig->getValue(self::XML_PATH_DDG_TRANSACTIONAL_HOST, 'store', $store);
    }

    public function getSmtpUsername()
    {
        $store = $this->_storeManager->getStore();
        return $this->scopeConfig->getValue(self::XML_PATH_DDG_TRANSACTIONAL_USERNAME, 'store', $store);
    }

    public function getSmtpPassword()
    {
        $store = $this->_storeManager->getStore();
        return $this->scopeConfig->getValue(self::XML_PATH_DDG_TRANSACTIONAL_PASSWORD, 'store', $store);
    }

    public function getSmtpPort()
    {
        $store = $this->_storeManager->getStore();
        return $this->scopeConfig->getValue(self::XML_PATH_DDG_TRANSACTIONAL_PORT, 'store', $store);
    }

    public function isDebugEnabled()
    {
        $store = $this->_storeManager->getStore();
        return $this->scopeConfig->getValue(self::XML_PATH_DDG_TRANSACTIONAL_DEBUG, 'store', $store);
    }


    public function getTransportConfig()
    {
        $config = array(
            'port' =>$this->getSmtpPort(),
            'auth' => 'login',
            'username' => $this->getSmtpUsername(),
            'password' => $this->getSmtpPassword(),
            'ssl' => 'tls'
        );

        if ($this->isDebugEnabled())
            $this->_logger->debug('Mail transport config : '. implode(',', $config));

        return $config;
    }
}