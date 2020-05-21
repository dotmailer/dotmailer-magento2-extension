<?php

namespace Dotdigitalgroup\Email\Plugin;

use Magento\Framework\Mail\TransportInterface;
use Magento\Framework\FlagManager;
use Dotdigitalgroup\Email\Model\Monitor\Smtp\Monitor;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * SMTP mail transport.
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class TransportPlugin
{
    /**
     * @var \Dotdigitalgroup\Email\Model\Mail\SmtpTransportAdapter
     */
    private $smtpTransportAdapter;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Transactional
     */
    private $helper;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $dataHelper;

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @var TimezoneInterface
     */
    private $timeZone;

    /**
     * TransportPlugin constructor.
     * @param \Dotdigitalgroup\Email\Model\Mail\SmtpTransportAdapter $smtpTransportAdapter
     * @param \Dotdigitalgroup\Email\Helper\Transactional $helper
     * @param \Dotdigitalgroup\Email\Helper\Data $dataHelper
     * @param \Magento\Framework\Registry $registry
     * @param FlagManager $flagManager
     * @param TimezoneInterface $timeZone
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Mail\SmtpTransportAdapter $smtpTransportAdapter,
        \Dotdigitalgroup\Email\Helper\Transactional $helper,
        \Dotdigitalgroup\Email\Helper\Data $dataHelper,
        \Magento\Framework\Registry $registry,
        FlagManager $flagManager,
        TimezoneInterface $timeZone
    ) {
        $this->smtpTransportAdapter = $smtpTransportAdapter;
        $this->helper = $helper;
        $this->dataHelper = $dataHelper;
        $this->registry = $registry;
        $this->flagManager = $flagManager;
        $this->timeZone = $timeZone;
    }

    /**
     * @param TransportInterface $subject
     * @param \Closure $proceed
     * @throws \Exception
     *
     * @return null
     */
    public function aroundSendMessage(
        TransportInterface $subject,
        \Closure $proceed
    ) {
        $storeId = $this->registry->registry('transportBuilderPluginStoreId');
        if ($this->helper->isEnabled($storeId)) {
            try {
                $this->smtpTransportAdapter->send($subject, $storeId);
            } catch (\Exception $e) {
                $errorData = [
                    'date' => $this->timeZone->date()->format("Y-m-d H:i:s"),
                    'error_message' => (string) $e->getMessage()
                ];

                $flagData = $this->flagManager->getFlagData(Monitor::SMTP_ERROR_FLAG_CODE) ?? [];
                array_push($flagData, $errorData);

                $this->flagManager->saveFlag(
                    Monitor::SMTP_ERROR_FLAG_CODE,
                    $flagData
                );

                $this->dataHelper->log("TransportPlugin send exception: " . $e->getMessage());
                return $proceed();
            }
        } else {
            return $proceed();
        }
    }
}
