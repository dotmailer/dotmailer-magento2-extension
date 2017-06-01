<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel\Rules;

class Collection extends
 \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';
    /**
     * Initialize resource collection.
     */
    public function _construct()
    {
        $this->_init(
            'Dotdigitalgroup\Email\Model\Rules',
            'Dotdigitalgroup\Email\Model\ResourceModel\Rules'
        );
    }

    /**
     * Reset collection.
     *
     * @return $this
     */
    public function reset()
    {
        $this->_reset();

        return $this;
    }

    /**
     * Check if rule already exist for website.
     *
     * @param      $websiteId
     * @param      $type
     * @param bool $ruleId
     *
     * @return bool
     */
    public function hasCollectionAnyItemsByWebsiteAndType($websiteId, $type, $ruleId = false)
    {
        $collection = $this->addFieldToFilter('type', ['eq' => $type])
            ->addFieldToFilter('website_ids', ['finset' => $websiteId]);

        if ($ruleId) {
            $collection->addFieldToFilter('id', ['neq' => $ruleId]);
        }
        $collection->setPageSize(1);

        if ($collection->getSize()) {
            return false;
        }

        return true;
    }

    /**
     * Get rule for website.
     *
     * @param $type
     * @param $websiteId
     *
     * @return array|\Magento\Framework\DataObject
     */
    public function getActiveRuleByWebsiteAndType($type, $websiteId)
    {
        $collection = $this->addFieldToFilter('type', ['eq' => $type])
            ->addFieldToFilter('status', ['eq' => 1])
            ->addFieldToFilter('website_ids', ['finset' => $websiteId])
            ->setPageSize(1);

        if ($collection->getSize()) {
            //@codingStandardsIgnoreStart
            return $collection->getFirstItem();
            //@codingStandardsIgnoreEnd
        }

        return [];
    }
}
