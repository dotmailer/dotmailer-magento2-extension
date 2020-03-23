<?php

namespace Dotdigitalgroup\Email\Model\System\Message;

use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\UrlInterface;
use Dotdigitalgroup\Email\Model\Monitor\Cron\StatusProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;

class CronError implements MessageInterface
{
    const MESSAGE_IDENTITY = 'ddg_cron_error_system_message';
    const XML_PATH_CONNECTOR_SYSTEM_MESSAGES = 'connector_developer_settings/system_alerts/enabled';

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var StatusProvider
     */
    private $cronStatusProvider;

    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * @param UrlInterface $urlBuilder
     * @param StatusProvider $cronStatusProvider
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        UrlInterface $urlBuilder,
        StatusProvider $cronStatusProvider,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->cronStatusProvider = $cronStatusProvider;
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
            self::XML_PATH_CONNECTOR_SYSTEM_MESSAGES
        );

        return $ddgSystemMessagesEnabledInConfig && count($this->cronStatusProvider->getErrors()) > 0;
    }

    /**
     * Retrieve system message text
     *
     * @return string
     */
    public function getText()
    {
        $errorTypes = implode(', ', $this->cronStatusProvider->getErrors());
        $message = __('One or more of your dotdigital cron tasks have errors: %1. ', $errorTypes) . ' ';
        $url = $this->urlBuilder->getUrl('dotdigitalgroup_email/cron/index');
        $message .= __('Please go to <a href="%1">Cron Tasks</a> to review.', $url);
        return $message;
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
}
