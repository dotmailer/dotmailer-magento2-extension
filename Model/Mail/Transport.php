<?php

namespace Dotdigitalgroup\Email\Model\Mail;

class Transport extends \Zend_Mail_Transport_Smtp implements \Magento\Framework\Mail\TransportInterface
{
    /**
     * @var \Magento\Framework\Mail\MessageInterface
     */
    public $message;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Transactional
     */
    public $helper;
    /**
     * @var \Zend_Mail_Transport_Sendmail
     */
    public $sendMail;

    /**
     * Transport constructor.
     *
     * @param \Zend_Mail_Transport_Sendmail               $sendmail
     * @param \Magento\Framework\Mail\MessageInterface    $message
     * @param \Dotdigitalgroup\Email\Helper\Transactional $helper
     */
    public function __construct(
        \Zend_Mail_Transport_Sendmail $sendmail,
        \Magento\Framework\Mail\MessageInterface $message,
        \Dotdigitalgroup\Email\Helper\Transactional $helper
    ) {
        if (!$message instanceof \Zend_Mail) {
            throw new \InvalidArgumentException('The message should be an instance of \Zend_Mail');
        }
        $this->message  = $message;
        $this->helper   = $helper;
        $this->sendMail = $sendmail;

        parent::__construct(
            $this->helper->getSmtpHost(),
            $this->helper->getTransportConfig()
        );
    }

    /**
     * @throws \Magento\Framework\Exception\MailException
     */
    public function sendMessage()
    {
        try {
            if ($this->helper->isEnabled()) {
                parent::send($this->message);
            } else {
                $this->sendMail->send($this->message);
            }
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\MailException(
                __($e->getMessage()),
                $e
            );
        }
    }
}
