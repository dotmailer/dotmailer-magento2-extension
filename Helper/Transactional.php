<?php

namespace Dotdigitalgroup\Email\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Zend\Mail\Transport\SmtpOptions;

/**
 * Transactional emails configuration data values.
 */
class Transactional extends AbstractHelper
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
            ScopeInterface::SCOPE_STORE,
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
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $subdomain = $this->scopeConfig->getValue(
            Config::PATH_FOR_API_ENDPOINT_SUBDOMAIN,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if ($regionId) {
            return 'r' . $regionId . '-smtp' . ($subdomain ? '-' . $subdomain : '') . '.dotdigital.com';
        }

        throw new \Magento\Framework\Exception\LocalizedException(
            __('Dotdigital SMTP host region is not defined')
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
            ScopeInterface::SCOPE_STORE,
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
            ScopeInterface::SCOPE_STORE,
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
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Fetch options.
     *
     * @param int $storeId
     *
     * @return SmtpOptions|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getSmtpOptions($storeId)
    {
        try {
            return new SmtpOptions(
                [
                    'host' => $this->getSmtpHost($storeId),
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
            return null;
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
