<?php

namespace Dotdigitalgroup\Email\Model\System\Message;

use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\UrlInterface;
use Dotdigitalgroup\Email\Model\Monitor\Importer\StatusProvider;
use Dotdigitalgroup\Email\Helper\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;

class ImporterError implements MessageInterface
{
    const MESSAGE_IDENTITY = 'ddg_importer_error_system_message';

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
        $errorSummary = $this->statusProvider->getErrorSummary();
        $message = __('One or more of your dotdigital importer tasks have errors: %1. ', $errorSummary) . ' ';
        $url = $this->urlBuilder->getUrl('dotdigitalgroup_email/importer/index');
        $message .= __('Please go to the <a href="%1">Import Report</a> to review.', $url);
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
