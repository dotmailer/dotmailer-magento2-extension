<?php

namespace Dotdigitalgroup\Email\Model\Contact;

interface ContactUpdaterInterface
{
    /**
     * Process batch of modified contacts.
     *
     * @param array $batch
     * @param array $websiteIds
     *
     * @return void
     */
    public function processBatch(array $batch, array $websiteIds);
}
