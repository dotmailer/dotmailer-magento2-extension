<?php

namespace Dotdigitalgroup\Email\Plugin;

/**
 * Class SegmentPlugin
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class SegmentPlugin
{
    /**
     * @var \Magento\CustomerSegment\Model\ResourceModel\Report\Customer\Collection
     */
    private $customerCollection;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Contact
     */
    private $contactResource;

    /**
     * SegmentPlugin constructor.
     *
     * @param \Magento\CustomerSegment\Model\ResourceModel\Report\Customer\Collection $customerCollection
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource
     */
    public function __construct(
        \Magento\CustomerSegment\Model\ResourceModel\Report\Customer\Collection $customerCollection,
        \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource
    ) {
        $this->customerCollection = $customerCollection;
        $this->contactResource = $contactResource;
    }

    /**
     * @param \Magento\CustomerSegment\Model\ResourceModel\Segment $subject
     * @param \Magento\CustomerSegment\Model\Segment $segment
     */
    public function beforeDeleteSegmentCustomers(
        \Magento\CustomerSegment\Model\ResourceModel\Segment $subject,
        $segment
    ) {
        $customers = $this->customerCollection->addSegmentFilter($segment);
        $customerIds = $customers->getColumnValues('entity_id');
        $this->setToReimport($customerIds);
    }

    /**
     * @param \Magento\CustomerSegment\Model\ResourceModel\Segment $subject
     * @param $result
     * @param \Magento\CustomerSegment\Model\Segment $segment
     * @param string $select
     *
     * @return \Magento\CustomerSegment\Model\ResourceModel\Segment
     */
    public function afterSaveCustomersFromSelect(
        \Magento\CustomerSegment\Model\ResourceModel\Segment $subject,
        $result,
        $segment,
        $select
    ) {
        $connection = $subject->getConnection();
        $stmt = $connection->query($select);
        $rows = $stmt->fetchAll();
        $customerIds = [];

        foreach ($rows as $row) {
            $customerIds[] = $row['entity_id'];
        }

        $this->setToReimport($customerIds);

        return $subject;
    }

    /**
     * @param \Magento\CustomerSegment\Model\ResourceModel\Segment $subject
     * @param \Closure $proceed
     * @param $segment
     *
     * @return \Magento\CustomerSegment\Model\ResourceModel\Segment
     */
    public function aroundAggregateMatchedCustomers(
        \Magento\CustomerSegment\Model\ResourceModel\Segment $subject,
        \Closure $proceed,
        $segment
    ) {
        //original call
        $proceed($segment);

        $websiteIds = $segment->getWebsiteIds();
        $customerIds = [];

        foreach ($websiteIds as $websiteId) {
            //get customers ids that satisfy conditions
            $ids = $segment->getConditions()->getSatisfiedIds($websiteId);
            if (is_array($ids)) {
                $customerIds = array_merge($customerIds, $ids);
            }
        }
        $this->setToReimport($customerIds);

        return $subject;
    }

    /**
     * Set contact to re-import
     *
     * @param $customerIds
     */
    private function setToReimport($customerIds)
    {
        if (! empty($customerIds)) {
            $this->contactResource->setNotImportedByCustomerIds($customerIds);
        }
    }
}
