<?php

namespace Dotdigitalgroup\Email\Test\Unit\Plugin;

use Magento\Framework\Mail\MessageInterface;

class Magento22MailClassTestDouble extends \Zend_Mail implements MessageInterface
{
    public function setBody($body)
    {
    }

    public function getBody()
    {
    }

    public function setMessageType($type)
    {
    }
}
