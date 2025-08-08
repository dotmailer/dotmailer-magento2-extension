<?php

namespace Dotdigitalgroup\Email\Plugin;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Helper\Transactional;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Mail\SmtpTransporterFactory;
use Dotdigitalgroup\Email\Model\Monitor\Smtp\Monitor;
use Magento\Framework\FlagManager;
use Magento\Framework\Mail\EmailMessageInterface;
use Magento\Framework\Mail\TransportInterface;
use Magento\Framework\Registry;

/**
 * SMTP mail transport.
 */
class TransportPlugin
{
    private const EXCLUDED_ERRORS = [
        'Requested action not taken: mailbox unavailable'
    ];

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var SmtpTransporterFactory
     */
    private $smtpTransporterFactory;

    /**
     * @var Transactional
     */
    private $helper;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * TransportPlugin constructor.
     *
     * @param Logger $logger
     * @param SmtpTransporterFactory $smtpTransporterFactory
     * @param Transactional $helper
     * @param Registry $registry
     * @param FlagManager $flagManager
     */
    public function __construct(
        Logger $logger,
        SmtpTransporterFactory $smtpTransporterFactory,
        Transactional $helper,
        Registry $registry,
        FlagManager $flagManager
    ) {
        $this->logger = $logger;
        $this->smtpTransporterFactory = $smtpTransporterFactory;
        $this->helper = $helper;
        $this->registry = $registry;
        $this->flagManager = $flagManager;
    }

    /**
     * Around send message.
     *
     * @param TransportInterface $subject
     * @param \Closure $proceed
     * @throws \Exception
     * @return \Closure|void
     */
    public function aroundSendMessage(
        TransportInterface $subject,
        \Closure $proceed
    ) {
        $storeId = (int) $this->registry->registry('transportBuilderPluginStoreId');
        if (!$this->helper->isEnabled($storeId)) {
            return $proceed();
        }

        try {
            $this->smtpTransporterFactory->create()
                ->send($subject, $storeId);
        } catch (\Exception $e) {
            if (in_array(str_replace("\r\n", "", $e->getMessage()), self::EXCLUDED_ERRORS)) {
                $to = $this->getAddressee($subject);
                $this->logger->debug(
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

            $this->logger->error("TransportPlugin send exception: " . $e->getMessage());
        }
        return $proceed();
    }

    /**
     * Get addressee.
     *
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
