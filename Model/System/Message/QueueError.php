<?php

namespace Dotdigitalgroup\Email\Model\System\Message;

use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\UrlInterface;
use Dotdigitalgroup\Email\Model\Monitor\Queue\StatusProvider;
use Dotdigitalgroup\Email\Helper\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;

class QueueError implements MessageInterface
{
    private const MESSAGE_IDENTITY = 'ddg_queue_error_system_message';

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
     * CronError constructor.
     *
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
     * Retrieve unique system message identity.
     *
     * @return string
     */
    public function getIdentity()
    {
        return hash("sha256", self::MESSAGE_IDENTITY);
    }

    /**
     * Check whether the system message should be shown.
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
     * Retrieve system message text.
     *
     * @return string
     */
    public function getText()
    {
        $message = '';
        $errorSummary = $this->statusProvider->getErrorSummary();
        $url = $this->urlBuilder->getUrl('dotdigitalgroup_email/queue/index');

        $message .= '<strong>';
        $message .= __('Dotdigital Message Queue');
        $message .= '</strong>';
        $message .= '<p>' . __($errorSummary) . '</p>';
        $message .= '<p>' . __('Please visit <a href="%1">Message Queue</a> to review.', $url) . '</p>';

        if (strpos($errorSummary, 'pending') !== false) {
            $message .= '<p>' . __(' Messages pending for longer than 1 hour may indicate a problem with the consumers_runner cron, or with cron_consumers_runner in system configuration.') . '</p>'; // phpcs:ignore Generic.Files.LineLength.TooLong
        }

        return $message;
    }

    /**
     * Retrieve system message severity.
     *
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
