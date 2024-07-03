<?php

namespace Dotdigitalgroup\Email\Model\Queue\Newsletter;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\Connector\ContactData;
use Dotdigitalgroup\Email\Model\ContactFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Email\Model\Queue\Data\SubscriptionData;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\SingleSubscriberSyncer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Newsletter\Model\Subscriber;

class SubscriptionConsumer
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ContactData
     */
    private $contactData;

    /**
     * @var ContactFactory
     */
    private $contactFactory;

    /**
     * @var ContactResource
     */
    private $contactResource;

    /**
     * @var SingleSubscriberSyncer
     */
    private $singleSubscriberSyncer;

    /**
     * @param Data $helper
     * @param Logger $logger
     * @param ContactData $contactData
     * @param ContactFactory $contactFactory
     * @param ContactResource $contactResource
     * @param SingleSubscriberSyncer $singleSubscriberSyncer
     */
    public function __construct(
        Data $helper,
        Logger $logger,
        ContactData $contactData,
        ContactFactory $contactFactory,
        ContactResource $contactResource,
        SingleSubscriberSyncer $singleSubscriberSyncer
    ) {
        $this->helper = $helper;
        $this->logger = $logger;
        $this->contactData = $contactData;
        $this->contactFactory = $contactFactory;
        $this->contactResource = $contactResource;
        $this->singleSubscriberSyncer = $singleSubscriberSyncer;
    }

    /**
     * Process consumer.
     *
     * @param SubscriptionData $data
     *
     * @return void
     * @throws LocalizedException
     */
    public function process(SubscriptionData $data)
    {
        if (!$data->getType()) {
            throw new LocalizedException(__('Unknown subscription type'));
        }

        $client = $this->helper->getWebsiteApiClient($data->getWebsiteId());
        $listId = (int) $this->helper->getSubscriberAddressBook($data->getWebsiteId());

        switch ($data->getType()) {
            case 'subscribe':
                $this->subscribe($data);
                break;
            case 'unsubscribe':
                $this->unsubscribe($data, $client, $listId);
                break;
            case 'resubscribe':
                $this->resubscribe($data, $client, $listId);
                break;
        }
    }

    /**
     * Subscribe.
     *
     * @param SubscriptionData $subscribeData
     *
     * @return void
     */
    private function subscribe(SubscriptionData $subscribeData)
    {
        $contact = $this->contactFactory->create();
        $this->contactResource->load($contact, $subscribeData->getId());

        try {
            $this->singleSubscriberSyncer->pushContactToSubscriberAddressBook($contact);
            $this->logger->info('Newsletter subscribe success', ['email' => $subscribeData->getEmail()]);
        } catch (\Exception $e) {
            $this->logger->error(
                'Newsletter subscribe error',
                [
                'identifier' => $subscribeData->getEmail(),
                'exception' => $e,
                ]
            );
        }
    }

    /**
     * Unsubscribe.
     *
     * @param SubscriptionData $unsubscribeData
     * @param Client $client
     * @param int $listId
     *
     * @return void
     */
    private function unsubscribe(SubscriptionData $unsubscribeData, Client $client, int $listId)
    {
        $data[] = [
            'Key' => 'SUBSCRIBER_STATUS',
            'Value' => $this->contactData->getSubscriberStatusString(
                Subscriber::STATUS_UNSUBSCRIBED
            )
        ];

        try {
            $result = $client->updateContactDatafieldsByEmail($unsubscribeData->getEmail(), $data);

            if (isset($result->id)) {
                $contactId = $result->id;
                $client->deleteAddressBookContact(
                    $listId,
                    $contactId
                );
            } else {
                $this->contactResource->setContactSuppressedForContactIds([$unsubscribeData->getId()]);
            }
            $this->logger->info('Newsletter unsubscribe success', ['email' => $unsubscribeData->getEmail()]);
        } catch (\Exception $e) {
            $this->logger->error(
                'Newsletter unsubscribe error',
                [
                'identifier' => $unsubscribeData->getEmail(),
                'exception' => $e,
                ]
            );
        }
    }

    /**
     * Resubscribe.
     *
     * @param SubscriptionData $resubscribeData
     * @param Client $client
     * @param int $listId
     *
     * @return void
     */
    private function resubscribe(SubscriptionData $resubscribeData, Client $client, int $listId)
    {
        try {
            ($listId) ?
                $client->postAddressBookContactResubscribe(
                    $listId,
                    $resubscribeData->getEmail()
                ) :
                $client->resubscribeContactByEmail($resubscribeData->getEmail());
            $this->logger->info('Newsletter resubscribe success', ['email' => $resubscribeData->getEmail()]);
        } catch (\Exception $e) {
            $this->logger->error(
                'Newsletter resubscribe error',
                [
                'identifier' => $resubscribeData->getEmail(),
                'exception' => $e,
                ]
            );
        }
    }
}
