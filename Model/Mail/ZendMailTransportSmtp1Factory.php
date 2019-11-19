<?php

namespace Dotdigitalgroup\Email\Model\Mail;

class ZendMailTransportSmtp1Factory
{
    /**
     * @param  string $host
     * @param  array $config
     *
     * @return \Zend_Mail_Transport_Smtp
     */
    public function create($host, $config)
    {
        return new \Zend_Mail_Transport_Smtp($host, $config);
    }
}
