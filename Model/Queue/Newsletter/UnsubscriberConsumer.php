<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Queue\Newsletter;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Connector\ContactData;
use Dotdigitalgroup\Email\Model\Queue\Data\UnsubscriberData;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Magento\Newsletter\Model\Subscriber;

/**
 * @deprecated Subscriptions now use a single consumer.
 * @see SubscriptionConsumer
 */
class UnsubscriberConsumer
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var ContactData
     */
    private $contactData;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ContactResource
     */
    private $contactResource;

    /**
     * @param Data $helper
     * @param ContactData $contactData
     * @param Logger $logger
     * @param ContactResource $contactResource
     */
    public function __construct(
        Data $helper,
        ContactData $contactData,
        Logger $logger,
        ContactResource $contactResource
    ) {
        $this->helper = $helper;
        $this->contactData = $contactData;
        $this->logger = $logger;
        $this->contactResource = $contactResource;
    }

    /**
     * Process consumer.
     *
     * @param UnsubscriberData $unsubscriberData
     *
     * @return void
     */
    public function process(UnsubscriberData $unsubscriberData)
    {
        $client = $this->helper->getWebsiteApiClient($unsubscriberData->getWebsiteId());

        $data[] = [
            'Key' => 'SUBSCRIBER_STATUS',
            'Value' => $this->contactData->getSubscriberStatusString(
                Subscriber::STATUS_UNSUBSCRIBED
            )
        ];

        try {
            /** @var \StdClass $result */
            $result = $client->updateContactDatafieldsByEmail($unsubscriberData->getEmail(), $data);

            if (isset($result->id)) {
                $contactId = $result->id;
                $client->deleteAddressBookContact(
                    $this->helper->getSubscriberAddressBook($unsubscriberData->getWebsiteId()),
                    $contactId
                );
            } else {
                $this->contactResource->setContactSuppressedForContactIds([$unsubscriberData->getId()]);
            }
            $this->logger->debug("Unsubscribe contact success:", ['email' => $unsubscriberData->getEmail()]);
        } catch (\Exception $exception) {
            $this->logger->debug("Unsubscribe contact error:", ['exception' => $exception]);
        }
    }
}
