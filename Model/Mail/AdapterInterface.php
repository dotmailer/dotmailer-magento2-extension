<?php

namespace Dotdigitalgroup\Email\Model\Mail;

/**
 * Smtp adapter interface
 */
interface AdapterInterface
{
    /**
     * @param \Magento\Framework\Mail\MessageInterface $message
     * @return void
     */
    public function send(\Magento\Framework\Mail\MessageInterface $message);
}
