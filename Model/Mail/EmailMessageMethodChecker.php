<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Mail;

use Magento\Framework\Mail\EmailMessageInterface;

class EmailMessageMethodChecker
{
    /**
     * Check if the message is a Symfony email message.
     *
     * @param EmailMessageInterface $message
     * @return bool
     */
    public function hasGetSymfonyMessageMethod(EmailMessageInterface $message): bool
    {
        return method_exists($message, 'getSymfonyMessage');
    }
}
