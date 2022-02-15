<?php

namespace Dotdigitalgroup\Email\Helper;

use Zend\Mail\Transport\SmtpOptions;

/**
 * Transactional emails configuration data values.
 */
class Transactional extends \Magento\Framework\App\Helper\AbstractHelper
{
    public const XML_PATH_DDG_TRANSACTIONAL_ENABLED    = 'transactional_emails/ddg_transactional/enabled';
    public const XML_PATH_DDG_TRANSACTIONAL_HOST       = 'transactional_emails/ddg_transactional/host';
    public const XML_PATH_DDG_TRANSACTIONAL_USERNAME   = 'transactional_emails/ddg_transactional/username';
    public const XML_PATH_DDG_TRANSACTIONAL_PASSWORD   = 'transactional_emails/ddg_transactional/password';
    public const XML_PATH_DDG_TRANSACTIONAL_PORT       = 'transactional_emails/ddg_transactional/port';
    public const XML_PATH_DDG_TRANSACTIONAL_DEBUG      = 'transactional_emails/ddg_transactional/debug';

    /**
     * Is transactional email enabled.
     *
     * @param int $storeId
     *
     * @return bool
     */
    public function isEnabled($storeId)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_DDG_TRANSACTIONAL_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get transactional email host.
     *
     * @param int $storeId
     *
     * @return boolean|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getSmtpHost($storeId)
    {
        $regionId = $this->scopeConfig->getValue(
            self::XML_PATH_DDG_TRANSACTIONAL_HOST,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if ($regionId) {
            return 'r' . $regionId . '-smtp.dotdigital.com';
        }

        throw new \Magento\Framework\Exception\LocalizedException(
            __('Dotdigital smtp host region is not defined')
        );
    }

    /**
     * Get smtp username.
     *
     * @param int $storeId
     *
     * @return boolean|string
     */
    private function getSmtpUsername($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_DDG_TRANSACTIONAL_USERNAME,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get smtp password.
     *
     * @param int $storeId
     *
     * @return boolean|string
     */
    private function getSmtpPassword($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_DDG_TRANSACTIONAL_PASSWORD,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get smtp port.
     *
     * @param int $storeId
     *
     * @return boolean|string
     */
    private function getSmtpPort($storeId)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_DDG_TRANSACTIONAL_PORT,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Fetch options.
     *
     * @param int $storeId
     *
     * @return SmtpOptions
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getSmtpOptions($storeId)
    {
        try {
            $host = $this->getSmtpHost($storeId);

            return new SmtpOptions(
                [
                'host' => $host,
                'port' => $this->getSmtpPort($storeId),
                'connection_class' => 'login',
                'connection_config' => [
                    'username' => $this->getSmtpUsername($storeId),
                    'password' => $this->getSmtpPassword($storeId),
                    'ssl' => 'tls'
                ]
                ]
            );
        } catch (\Exception $e) {
            $this->_logger->debug((string) $e);
        }
    }

    /**
     * Check if the template code is containing dotmailer.
     *
     * @param string $templateCode
     * @return bool
     */
    public function isDotmailerTemplate($templateCode)
    {
        preg_match("/\_\d{1,10}$/", (string) $templateCode, $matches);

        if (count($matches)) {
            return true;
        }

        return false;
    }
}
