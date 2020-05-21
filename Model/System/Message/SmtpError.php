<?php

namespace Dotdigitalgroup\Email\Model\System\Message;

use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\UrlInterface;
use Dotdigitalgroup\Email\Model\Monitor\Smtp\StatusProvider;
use Dotdigitalgroup\Email\Helper\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;

class SmtpError implements MessageInterface
{
    const MESSAGE_IDENTITY = 'ddg_smtp_error_system_message';

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var StatusProvider
     */
    private $statusProvider;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param UrlInterface $urlBuilder
     * @param StatusProvider $statusProvider
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        UrlInterface $urlBuilder,
        StatusProvider $statusProvider,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->statusProvider = $statusProvider;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Retrieve unique system message identity
     *
     * @return string
     */
    public function getIdentity()
    {
        return hash("sha256", self::MESSAGE_IDENTITY);
    }

    /**
     * Check whether the system message should be shown
     *
     * @return bool
     */
    public function isDisplayed()
    {
        $ddgSystemMessagesEnabledInConfig = $this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_SYSTEM_ALERTS_SYSTEM_MESSAGES
        );

        return $ddgSystemMessagesEnabledInConfig && $this->statusProvider->hasErrors();
    }

    /**
     * Retrieve system message text
     *
     * @return string
     */
    public function getText()
    {
        return $this->filterSmtpErrors(explode(PHP_EOL, $this->statusProvider->getErrorSummary()));
    }

    /**
     * Retrieve system message severity
     * Possible default system message types:
     * - MessageInterface::SEVERITY_CRITICAL
     * - MessageInterface::SEVERITY_MAJOR
     * - MessageInterface::SEVERITY_MINOR
     * - MessageInterface::SEVERITY_NOTICE
     *
     * @return int
     */
    public function getSeverity()
    {
        return self::SEVERITY_MAJOR;
    }

    /**
     * @param $errors
     * @return \Magento\Framework\Phrase
     */
    private function filterSmtpErrors($errors)
    {
        foreach ($errors as $error) {
            if (strpos($error, 'Authentication not successful') !== false) {
                $transactionalEmailsUrl = $this->urlBuilder->getUrl(
                    'adminhtml/system_config/edit/section/transactional_emails'
                );
                return  __('Recent dotdigital SMTP mail sends have failed due to an authentication issue.
                    Please <a href="%1">verify your SMTP credentials</a>.', $transactionalEmailsUrl);
            }
        }
        $logsUrl = $this->urlBuilder->getUrl('dotdigitalgroup_email/logviewer/index');
        return __('One or more recent dotdigital SMTP mail sends have failed. Please
                     <a href="%1">check your logs</a> for more detail.', $logsUrl);
    }
}
