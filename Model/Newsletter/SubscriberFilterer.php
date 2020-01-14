<?php

namespace Dotdigitalgroup\Email\Model\Newsletter;

class SubscriberFilterer
{
    /**
     * @var \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory
     */
    private $subscriberCollectionFactory;

    /**
     * SubscriberFilterer constructor.
     * @param \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $subscriberCollectionFactory
     */
    public function __construct(
        \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $subscriberCollectionFactory
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
}
