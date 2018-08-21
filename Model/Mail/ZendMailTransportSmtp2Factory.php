<?php

namespace Dotdigitalgroup\Email\Model\Mail;

use Zend\Mail\Transport\Smtp;
use Zend\Mail\Transport\SmtpOptions;

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
