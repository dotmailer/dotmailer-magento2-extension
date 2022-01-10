<?php

namespace Dotdigitalgroup\Email\Plugin;

use Dotdigitalgroup\Email\Model\Mail\SmtpTransporter;
use Dotdigitalgroup\Email\Model\Monitor\Smtp\Monitor;
use Magento\Framework\FlagManager;
use Magento\Framework\Mail\EmailMessageInterface;
use Magento\Framework\Mail\TransportInterface;

/**
 * SMTP mail transport.
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class TransportPlugin
{
    const EXCLUDED_ERRORS = [
        'Requested action not taken: mailbox unavailable'
    ];

    /**
     * @var SmtpTransporter
     */
    private $smtpTransporter;

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
     * TransportPlugin constructor.
     * @param SmtpTransporter $smtpTransporter
     * @param \Dotdigitalgroup\Email\Helper\Transactional $helper
     * @param \Dotdigitalgroup\Email\Helper\Data $dataHelper
     * @param \Magento\Framework\Registry $registry
     * @param FlagManager $flagManager
     */
    public function __construct(
        SmtpTransporter $smtpTransporter,
        \Dotdigitalgroup\Email\Helper\Transactional $helper,
        \Dotdigitalgroup\Email\Helper\Data $dataHelper,
        \Magento\Framework\Registry $registry,
        FlagManager $flagManager
    ) {
        $this->smtpTransporter = $smtpTransporter;
        $this->helper = $helper;
        $this->dataHelper = $dataHelper;
        $this->registry = $registry;
        $this->flagManager = $flagManager;
    }

    /**
     * @param TransportInterface $subject
     * @param \Closure $proceed
     * @throws \Exception
     * @return \Closure|void
     */
    public function aroundSendMessage(
        TransportInterface $subject,
        \Closure $proceed
    ) {
        $storeId = $this->registry->registry('transportBuilderPluginStoreId');
        if (!$this->helper->isEnabled($storeId)) {
            return $proceed();
        }

        try {
            $this->smtpTransporter->send($subject, $storeId);
        } catch (\Exception $e) {
            if (in_array(str_replace("\r\n", "", $e->getMessage()), self::EXCLUDED_ERRORS)) {
                $to = $this->getAddressee($subject);
                $this->dataHelper->log(
                    sprintf(
                        "Unable to deliver transactional email. Invalid email address. %s",
                        $to ? '[' . $to . ']' : ''
                    ),
                    [(string) $e]
                );
                return $proceed();
            }

            $now = new \DateTime('now', new \DateTimezone('UTC'));
            $errorData = [
                'date' => $now->format("Y-m-d H:i:s"),
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
    }

    /**
     * @param TransportInterface $subject
     *
     * @return bool|string
     */
    private function getAddressee(TransportInterface $subject)
    {
        $message = $subject->getMessage();
        if ($message instanceof EmailMessageInterface) {
            $to = $message->getTo();
            return reset($to)->getEmail();
        }

        return false;
    }
}
