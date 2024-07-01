<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Subscriber;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\DataFieldCollector;
use Magento\Framework\Exception\LocalizedException;

class SingleSubscriberSyncer
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var DataFieldCollector
     */
    private $dataFieldCollector;

    /**
     * @param Data $helper
     * @param DataFieldCollector $dataFieldCollector
     */
    public function __construct(
        Data $helper,
        DataFieldCollector $dataFieldCollector
    ) {
        $this->helper = $helper;
        $this->dataFieldCollector = $dataFieldCollector;
    }

    /**
     * Add subscriber to subscriber list
     *
     * @param Contact $contact
     *
     * @return object|null
     * @throws LocalizedException
     */
    public function pushContactToSubscriberAddressBook(Contact $contact)
    {
        $websiteId = (int) $contact->getWebsiteId();
        $subscriberSyncEnabled = $this->helper->isSubscriberSyncEnabled($websiteId);
        $subscriberAddressBookId = $this->helper->getSubscriberAddressBook($websiteId);
        if (!$subscriberSyncEnabled || !$subscriberAddressBookId) {
            return null;
        }

        $client = $this->helper->getWebsiteApiClient($websiteId);
        $subscriberDataFields = $this->dataFieldCollector->mergeFields(
            [],
            $this->dataFieldCollector->collectForSubscriber(
                $contact,
                $websiteId
            )
        );

        // optInType will be set in $subscriberDataFields if it is 'Double'
        return $client->addContactToAddressBook(
            $contact->getEmail(),
            $subscriberAddressBookId,
            null,
            $subscriberDataFields
        );
    }
}
