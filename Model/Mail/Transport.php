<?php

namespace Dotdigitalgroup\Email\Model\Mail;

class Transport extends \Zend_Mail_Transport_Smtp
    implements \Magento\Framework\Mail\TransportInterface
{
    /**
     * @var \Magento\Framework\Mail\MessageInterface
     */
    protected $_message;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Transactional
     */
    protected $_helper;

    /**
     * Transport constructor.
     *
     * @param \Magento\Framework\Mail\MessageInterface    $message
     * @param \Dotdigitalgroup\Email\Helper\Transactional $helper
     */
    public function __construct(
        \Magento\Framework\Mail\MessageInterface $message,
        \Dotdigitalgroup\Email\Helper\Transactional $helper
    ) {
        if (!$message instanceof \Zend_Mail) {
            throw new \InvalidArgumentException('The message should be an instance of \Zend_Mail');
        }
        $this->_message = $message;
        $this->_helper = $helper;

        parent::__construct($this->_helper->getSmtpHost(),
            $this->_helper->getTransportConfig());
    }

    /**
     * Send a mail using this transport.
     *
     * @throws \Magento\Framework\Exception\MailException
     */
    public function sendMessage()
    {
        try {
            if ($this->_helper->isEnabled()) {
                parent::send($this->_message);
            } else {
                $normal = new \Zend_Mail_Transport_Sendmail();
                $normal->send($this->_message);
            }
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\MailException(new \Magento\Framework\Phrase($e->getMessage()),
                $e);
        }
    }
}
