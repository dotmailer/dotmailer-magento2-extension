<?php

namespace Dotdigitalgroup\Email\Model\Newsletter;

use Magento\Framework\Exception\LocalizedException;

/**
 * Sync subscribers.
 */
class Subscriber
{
    const STATUS_SUBSCRIBED = 1;
    const STATUS_NOT_ACTIVE = 2;
    const STATUS_UNSUBSCRIBED = 3;
    const STATUS_UNCONFIRMED = 4;

    /**
     * @var mixed
     */
    private $start;

    /**
     * Global number of subscriber updated.
     *
     * @var int
     */
    private $countSubscribers = 0;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Dotdigitalgroup\Email\Model\ContactFactory
     */
    private $contactFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory
     */
    private $orderCollection;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $timezone;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Contact
     */
    private $emailContactResource;

    /**
     * @var SubscriberWithSalesExporter
     */
    private $subscriberWithSalesExporter;

    /**
     * @var \Dotdigitalgroup\Email\Model\DateIntervalFactory
     */
    private $dateIntervalFactory;

    /**
     * @var SubscriberExporter
     */
    private $subscriberExporter;

    /**
     * Subscriber constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory $orderCollection
     * @param SubscriberExporter $subscriberExporter
     * @param SubscriberWithSalesExporter $subscriberWithSalesExporter
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Dotdigitalgroup\Email\Model\DateIntervalFactory $dateIntervalFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory $orderCollection,
        \Dotdigitalgroup\Email\Model\Newsletter\SubscriberExporter $subscriberExporter,
        \Dotdigitalgroup\Email\Model\Newsletter\SubscriberWithSalesExporter $subscriberWithSalesExporter,
        \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Dotdigitalgroup\Email\Model\DateIntervalFactory $dateIntervalFactory
    ) {
        $this->dateIntervalFactory = $dateIntervalFactory;
        $this->helper            = $helper;
        $this->contactFactory    = $contactFactory;
        $this->orderCollection   = $orderCollection;
        $this->subscriberExporter = $subscriberExporter;
        $this->subscriberWithSalesExporter = $subscriberWithSalesExporter;
        $this->emailContactResource = $contactResource;
        $this->timezone = $timezone;
    }

    /**
     * @return array
     */
    public function sync()
    {
        $response    = ['success' => true, 'message' => ''];
        $this->start = microtime(true);
        $websites    = $this->helper->getWebsites(true);

        foreach ($websites as $website) {
            $websiteId = $website->getId();
            //if subscriber is enabled and mapped
            $apiEnabled = $this->helper->isEnabled($websiteId);
            $addressBook = $this->helper->getSubscriberAddressBook($websiteId);
            $subscriberEnabled = $this->helper->isSubscriberSyncEnabled($websiteId);
            //enabled and mapped
            if ($apiEnabled && $addressBook && $subscriberEnabled) {
                //ready to start sync
                $numUpdated = $this->exportSubscribersPerWebsite($website);

                // show message for any number of customers
                if ($numUpdated) {
                    $response['message'] .= $website->getName() . ',  count = ' . $numUpdated;
                }
            }
        }
        //sync proccessed
        if ($this->countSubscribers) {
            $message = '----------- Subscribers sync ----------- : ' . gmdate('H:i:s', microtime(true) - $this->start) .
                ', updated = ' . $this->countSubscribers;
            $this->helper->log($message);
            $message .= $response['message'];
            $response['message'] = $message;
        }

        return $response;
    }

    /**
     * Export subscribers per website.
     *
     * @param \Magento\Store\Model\Website $website
     *
     * @return int
     *
     * @throws LocalizedException
     */
    public function exportSubscribersPerWebsite($website)
    {
        $isSubscriberSalesDataEnabled = $this->helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_ENABLE_SUBSCRIBER_SALES_DATA,
            $website
        );

        $updated = 0;
        $limit = $this->helper->getSyncLimit($website->getId());
        //subscriber collection to import
        $emailContactModel = $this->contactFactory->create();
        //Customer Subscribers
        $subscribersAreCustomers = $emailContactModel->getSubscribersToImport($website, $limit);
        //Guest Subscribers
        $subscribersAreGuest = $emailContactModel->getSubscribersToImport($website, $limit, false);
        $subscribersGuestEmails = $subscribersAreGuest->getColumnValues('email');
        $existInSales = [];
        //Only if subscriber with sales data enabled
        if ($isSubscriberSalesDataEnabled && ! empty($subscribersGuestEmails)) {
            $existInSales = $this->checkInSales($subscribersGuestEmails);
        }

        $emailsNotInSales = array_diff($subscribersGuestEmails, $existInSales);
        $customerSubscribers = $subscribersAreCustomers->getColumnValues('email');
        $emailsWithNoSaleData = array_merge($emailsNotInSales, $customerSubscribers);
        //subscriber that are customer or/and the one that do not exist in sales order table.
        $subscribersWithNoSaleData = [];
        if (! empty($emailsWithNoSaleData)) {
            $subscribersWithNoSaleData = $emailContactModel
                ->getSubscribersToImportFromEmails($emailsWithNoSaleData);
        }
        if (! empty($subscribersWithNoSaleData)) {
            $updated += $this->subscriberExporter->exportSubscribers(
                $website,
                $subscribersWithNoSaleData
            );
            //add updated number for the website
            $this->countSubscribers += $updated;
        }
        //subscriber that are guest and also exist in sales order table.
        $subscribersWithSaleData = [];
        if (! empty($existInSales)) {
            $subscribersWithSaleData = $emailContactModel->getSubscribersToImportFromEmails($existInSales);
        }

        if (! empty($subscribersWithSaleData)) {
            $updated += $this->subscriberWithSalesExporter->exportSubscribersWithSales(
                $website,
                $subscribersWithSaleData
            );
            //add updated number for the website
            $this->countSubscribers += $updated;
        }
        return $updated;
    }

    /**
     * Check emails exist in sales order table.
     *
     * @param array $emails
     *
     * @return array
     */
    public function checkInSales($emails)
    {
        return $this->orderCollection->create()
            ->checkInSales($emails);
    }

    /**
     * Un-subscribe suppressed contacts.
     *
     * @return array
     */
    public function unsubscribe()
    {
        $result['customers'] = 0;
        $suppressedEmails = [];

        /**
         * Sync all suppressed for each store
         */
        $websites = $this->helper->getWebsites(true);

        foreach ($websites as $website) {
            //not enabled
            if (! $this->helper->isEnabled($website)) {
                continue;
            }

            $suppressedEmails = $this->getSuppressedContacts($website);
        }
        //Mark suppressed contacts
        if (! empty($suppressedEmails)) {
            $result['customers'] = $this->emailContactResource->unsubscribe($suppressedEmails);
        }
        return $result;
    }

    /**
     * @param \Magento\Store\Api\Data\WebsiteInterface $website
     * @return array
     */
    private function getSuppressedContacts($website)
    {
        $limit = 5;
        $maxToSelect = 1000;
        $skip = $i = 0;
        $contacts = [];
        $suppressedEmails = [];
        $date = $this->timezone->date()->sub($this->dateIntervalFactory->create(['interval_spec' => 'PT24H']));
        $dateString = $date->format(\DateTime::W3C);
        $client = $this->helper->getWebsiteApiClient($website);

        //there is a maximum of request we need to loop to get more suppressed contacts
        for ($i=0; $i<= $limit; $i++) {
            $apiContacts = $client->getContactsSuppressedSinceDate($dateString, $maxToSelect, $skip);

            // skip no more contacts or the api request failed
            if (empty($apiContacts) || isset($apiContacts->message)) {
                break;
            }
            $contacts = array_merge($contacts, $apiContacts);
            $skip += 1000;
        }

        // Contacts to un-subscribe
        foreach ($contacts as $apiContact) {
            if (isset($apiContact->suppressedContact)) {
                $suppressedContactEmail = $apiContact->suppressedContact->email;
                if (!in_array($suppressedContactEmail, $suppressedEmails, true)) {
                    $suppressedEmails[] = $suppressedContactEmail;
                }
            }
        }

        return $suppressedEmails;
    }
}
