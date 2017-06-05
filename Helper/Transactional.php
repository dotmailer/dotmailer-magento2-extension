<?php

namespace Dotdigitalgroup\Email\Helper;

/**
 * Transactional emails configuration data values.
 */
class Transactional extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_DDG_TRANSACTIONAL_ENABLED = 'transactional_emails/ddg_transactional/enabled';
    const XML_PATH_DDG_TRANSACTIONAL_HOST = 'transactional_emails/ddg_transactional/host';
    const XML_PATH_DDG_TRANSACTIONAL_USERNAME = 'transactional_emails/ddg_transactional/username';
    const XML_PATH_DDG_TRANSACTIONAL_PASSWORD = 'transactional_emails/ddg_transactional/password';
    const XML_PATH_DDG_TRANSACTIONAL_PORT = 'transactional_emails/ddg_transactional/port';
    const XML_PATH_DDG_TRANSACTIONAL_DEBUG = 'transactional_emails/ddg_transactional/debug';

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * Transactional constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;

        parent::__construct($context);
    }

    /**
     * Is transactional email enabled.
     *
     * @return bool
     */
    public function isEnabled()
    {
        $store = $this->storeManager->getStore();

        return $this->scopeConfig->isSetFlag(self::XML_PATH_DDG_TRANSACTIONAL_ENABLED, 'store', $store);
    }

    /**
     * Get transactional email host.
     *
     * @return mixed
     */
    public function getSmtpHost()
    {
        $store = $this->storeManager->getStore();

        return $this->scopeConfig->getValue(self::XML_PATH_DDG_TRANSACTIONAL_HOST, 'store', $store);
    }

    /**
     * Get smtp username.
     *
     * @return mixed
     */
    private function getSmtpUsername()
    {
        $store = $this->storeManager->getStore();

        return $this->scopeConfig->getValue(self::XML_PATH_DDG_TRANSACTIONAL_USERNAME, 'store', $store);
    }

    /**
     * Get smtp password.
     *
     * @return mixed
     */
    private function getSmtpPassword()
    {
        $store = $this->storeManager->getStore();

        return $this->scopeConfig->getValue(self::XML_PATH_DDG_TRANSACTIONAL_PASSWORD, 'store', $store);
    }

    /**
     * Get smtp port.
     *
     * @return mixed
     */
    private function getSmtpPort()
    {
        $store = $this->storeManager->getStore();

        return $this->scopeConfig->getValue(self::XML_PATH_DDG_TRANSACTIONAL_PORT, 'store', $store);
    }

    /**
     * Get transactional log enabled.
     *
     * @return bool
     */
    private function isDebugEnabled()
    {
        $store = $this->storeManager->getStore();

        return $this->scopeConfig->isSetFlag(self::XML_PATH_DDG_TRANSACTIONAL_DEBUG, 'store', $store);
    }

    /**
     * Get config values for transport.
     *
     * @return array
     */
    public function getTransportConfig()
    {
        $config = [
            'port' => $this->getSmtpPort(),
            'auth' => 'login',
            'username' => $this->getSmtpUsername(),
            'password' => $this->getSmtpPassword(),
            'ssl' => 'tls',
        ];

        if ($this->isDebugEnabled()) {
            $this->_logger->debug('Mail transport config : ' . implode(',', $config));
        }

        return $config;
    }
}
