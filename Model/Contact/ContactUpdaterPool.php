<?php

namespace Dotdigitalgroup\Email\Model\Contact;

class ContactUpdaterPool
{
    /**
     * @var ContactUpdaterInterface[]
     */
    private $updaters;

    /**
     * @param array $updaters
     */
    public function __construct(
        array $updaters = []
    ) {
        $this->updaters = $updaters;
    }

    /**
     * Execute
     *
     * @param array $apiContacts
     * @param array $websiteIds
     *
     * @return void
     */
    public function execute(array $apiContacts, array $websiteIds)
    {
        foreach ($this->updaters as $updater) {
            if (!$updater instanceof ContactUpdaterInterface) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Updater %s must implement %s',
                        get_class($updater),
                        ContactUpdaterInterface::class
                    )
                );
            }
            $updater->processBatch($apiContacts, $websiteIds);
        }
    }
}
