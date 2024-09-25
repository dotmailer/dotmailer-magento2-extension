<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Subscriber;

use Dotdigital\V3\Models\Contact as SdkContact;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory;
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
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var DataFieldCollector
     */
    private $dataFieldCollector;

    /**
     * @param Data $helper
     * @param ClientFactory $clientFactory
     * @param DataFieldCollector $dataFieldCollector
     */
    public function __construct(
        Data $helper,
        ClientFactory $clientFactory,
        DataFieldCollector $dataFieldCollector
    ) {
        $this->helper = $helper;
        $this->clientFactory = $clientFactory;
        $this->dataFieldCollector = $dataFieldCollector;
    }

    /**
     * Add subscriber to subscriber list
     *
     * @param Contact $contact
     *
     * @return SdkContact|null
     * @throws LocalizedException|\Http\Client\Exception
     */
    public function pushContactToSubscriberAddressBook(Contact $contact): ?SdkContact
    {
        $websiteId = (int) $contact->getWebsiteId();
        $subscriberSyncEnabled = $this->helper->isSubscriberSyncEnabled($websiteId);
        $subscriberAddressBookId = $this->helper->getSubscriberAddressBook($websiteId);
        if (!$subscriberSyncEnabled || !$subscriberAddressBookId) {
            return null;
        }

        $sdkSubscriber = $this->dataFieldCollector->collectForSubscriber(
            $contact,
            $websiteId,
            (int) $subscriberAddressBookId
        );

        if (!$sdkSubscriber) {
            return null;
        }

        return $this->clientFactory
            ->create(['data' => ['websiteId' => $websiteId]])
            ->contacts
            ->patchByIdentifier(
                $contact->getEmail(),
                $sdkSubscriber
            );
    }
}
