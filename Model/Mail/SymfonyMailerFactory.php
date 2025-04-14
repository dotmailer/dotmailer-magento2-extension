<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Mail;

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\TransportInterface as SymfonyTransportInterface;

class SymfonyMailerFactory
{
    /**
     * Create a new Mailer instance.
     *
     * @param SymfonyTransportInterface $transport
     *
     * @return Mailer
     */
    public function create(
        SymfonyTransportInterface $transport
    ) {
        return new Mailer($transport);
    }
}
