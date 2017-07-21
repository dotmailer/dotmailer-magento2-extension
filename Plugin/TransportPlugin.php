<?php

namespace Dotdigitalgroup\Email\Plugin;

use Magento\Framework\Mail\TransportInterface;

/**
 * SMTP mail transport.
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class TransportPlugin
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Transactional
     */
    private $helper;

    /**
     * @var \Zend_Mail_Transport_Smtp
     */
    private $smtp;

    /**
     * TransportPlugin constructor.
     *
     * @param \Zend_Mail_Transport_SmtpFactory $smtpFactory
     * @param \Dotdigitalgroup\Email\Helper\Transactional $helper
     */
    public function __construct(
        \Zend_Mail_Transport_SmtpFactory $smtpFactory,
        \Dotdigitalgroup\Email\Helper\Transactional $helper
    ) {
        $this->helper   = $helper;
        $this->smtp = $smtpFactory->create(
            [
            'host' => $this->helper->getSmtpHost(),
            'config' => $this->helper->getTransportConfig()
            ]
        );
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
        if ($this->helper->isEnabled()) {
            $reflection = new \ReflectionClass($subject);

            if (method_exists($subject, 'getMessage')) {
                $this->smtp->send($subject->getMessage());
            } else {
                $property = $reflection->getProperty('_message');
                $property->setAccessible(true);
                $this->smtp->send($property->getValue($subject));
            }
        } else {
            $proceed();
        }
    }
}
