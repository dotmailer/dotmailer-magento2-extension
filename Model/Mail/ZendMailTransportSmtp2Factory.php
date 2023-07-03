<?php

namespace Dotdigitalgroup\Email\Model\Mail;

use Laminas\Mail\Transport\Smtp;
use Laminas\Mail\Transport\SmtpOptions;

class ZendMailTransportSmtp2Factory
{
    /**
     * @param SmtpOptions $smtpOptions
     *
     * @return Smtp
     */
    public function create($smtpOptions)
    {
        return new Smtp($smtpOptions);
    }
}
