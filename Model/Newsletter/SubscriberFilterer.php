<?php

namespace Dotdigitalgroup\Email\Model\Newsletter;

use Magento\Newsletter\Model\ResourceModel\Subscriber\Collection;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory;

/**
 * Class SubscriberFilterer
 *
 * This class is for retrieving or filtering subscriber collections.
 */
class SubscriberFilterer
{
    /**
     * @var CollectionFactory
     */
    private $subscriberCollectionFactory;

    /**
     * SubscriberFilterer constructor.
     * @param CollectionFactory $subscriberCollectionFactory
     */
    public function __construct(
        CollectionFactory $subscriberCollectionFactory
    ) {
        $this->subscriberCollectionFactory = $subscriberCollectionFactory;
    }

    /**
     * Add filter to collection for subscribers with subscribed status.
     * @param Object $collection
     * @param string $emailColumn
     * @return Object
     */
    public function filterBySubscribedStatus($collection, $emailColumn = 'customer_email')
    {
        $originalCollection = clone $collection;
        $emails = $originalCollection->getColumnValues($emailColumn);

        if (! empty($emails)) {
            $subscriberCollectionFactory = $this->subscriberCollectionFactory->create();
            $onlySubscribedEmails = $subscriberCollectionFactory->addFieldToFilter(
                'subscriber_email',
                ['in' => $emails]
            )
                ->addFieldToFilter('subscriber_status', \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED)
                ->getColumnValues('subscriber_email');

            $collection->addFieldToFilter($emailColumn, ['in' => $onlySubscribedEmails]);
        }

        return $collection;
    }

    /**
     * @param array $emails
     * @param array $storeIds
     * @param int $status
     *
     * @return Collection
     */
    public function getSubscribersByEmailsStoresAndStatus(array $emails, array $storeIds, $status)
    {
        return $this->subscriberCollectionFactory->create()
            ->addFieldToFilter('subscriber_email', ['in' => $emails])
            ->addFieldToFilter('store_id', ['in' => $storeIds])
            ->addFieldToFilter('subscriber_status', $status);
    }
}
