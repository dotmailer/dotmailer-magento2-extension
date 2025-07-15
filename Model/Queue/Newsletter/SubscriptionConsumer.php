<?php

namespace Dotdigitalgroup\Email\Model\Queue\Newsletter;

use Dotdigital\Exception\ResponseValidationException;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\Connector\ContactData;
use Dotdigitalgroup\Email\Model\ContactFactory;
use Dotdigitalgroup\Email\Model\Queue\Data\AutomationDataFactory;
use Dotdigitalgroup\Email\Model\Queue\Sync\Automation\AutomationPublisher;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Email\Model\Queue\Data\SubscriptionData;
use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationTypeHandler;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\SingleSubscriberSyncer;
use Http\Client\Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\MessageQueue\PublisherInterface;
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
     * @var AutomationDataFactory
     */
    private $automationDataFactory;

    /**
     * @var ContactResource
     */
    private $contactResource;

    /**
     * @var SingleSubscriberSyncer
     */
    private $singleSubscriberSyncer;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @param Data $helper
     * @param Logger $logger
     * @param AutomationDataFactory $automationDataFactory
     * @param ContactData $contactData
     * @param ContactFactory $contactFactory
     * @param ContactResource $contactResource
     * @param PublisherInterface $publisher
     * @param SingleSubscriberSyncer $singleSubscriberSyncer
     */
    public function __construct(
        Data $helper,
        Logger $logger,
        AutomationDataFactory $automationDataFactory,
        ContactData $contactData,
        ContactFactory $contactFactory,
        ContactResource $contactResource,
        PublisherInterface $publisher,
        SingleSubscriberSyncer $singleSubscriberSyncer
    ) {
        $this->helper = $helper;
        $this->logger = $logger;
        $this->automationDataFactory = $automationDataFactory;
        $this->contactData = $contactData;
        $this->contactFactory = $contactFactory;
        $this->contactResource = $contactResource;
        $this->singleSubscriberSyncer = $singleSubscriberSyncer;
        $this->publisher = $publisher;
    }

    /**
     * Process consumer.
     *
     * @param SubscriptionData $data
     *
     * @return void
     * @throws LocalizedException|Exception
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
     * If the upstream contact is unsubscribed or mail blocked,
     * the API request will trigger a resubscribe challenge.
     *
     * @param SubscriptionData $subscribeData
     *
     * @return void
     */
    private function subscribe(SubscriptionData $subscribeData)
    {
        try {
            $contact = $this->contactFactory->create();
            $this->contactResource->load($contact, $subscribeData->getId());
            $this->singleSubscriberSyncer->execute($contact);
            $this->logger->info('Newsletter subscribe success', ['email' => $subscribeData->getEmail()]);
        } catch (ResponseValidationException $e) {
            $this->logger->error(
                sprintf(
                    'Newsletter subscribe error: %s',
                    $e->getMessage()
                ),
                [$e->getDetails()]
            );
        } catch (\Exception|Exception $e) {
            $this->logger->error(
                'Newsletter subscribe error',
                [
                    'identifier' => $subscribeData->getEmail(),
                    'exception' => $e,
                ]
            );
        }

        if (!$subscribeData->getAutomationId()) {
            return;
        }

        $message = $this->automationDataFactory->create();
        $message->setId($subscribeData->getAutomationId());
        $message->setType(AutomationTypeHandler::AUTOMATION_TYPE_NEW_SUBSCRIBER);

        try {
            $this->publisher->publish(AutomationPublisher::TOPIC_SYNC_AUTOMATION, $message);
            $this->logger->info('Subscriber automation publish success', ['email' => $subscribeData->getEmail()]);
        } catch (\Exception $e) {
            $this->logger->error('Subscriber automation publish failed', [(string) $e]);
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
     *
     * @deprecated Use the 'subscribe' route. Retaining for backwards compatibility.
     * @see SubscriptionConsumer::subscribe
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
