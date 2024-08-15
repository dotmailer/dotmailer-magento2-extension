<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Subscriber;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\Newsletter\OptInTypeFinder;
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
     * @var OptInTypeFinder
     */
    private $optInTypeFinder;

    /**
     * @param Data $helper
     * @param DataFieldCollector $dataFieldCollector
     * @param OptInTypeFinder $optInTypeFinder
     */
    public function __construct(
        Data $helper,
        DataFieldCollector $dataFieldCollector,
        OptInTypeFinder $optInTypeFinder
    ) {
        $this->helper = $helper;
        $this->dataFieldCollector = $dataFieldCollector;
        $this->optInTypeFinder = $optInTypeFinder;
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
                $websiteId,
                (int) $subscriberAddressBookId
            )
        );

        return $client->addContactToAddressBook(
            $contact->getEmail(),
            $subscriberAddressBookId,
            $this->optInTypeFinder->getOptInType($contact->getStoreId()),
            $subscriberDataFields
        );
    }
}
