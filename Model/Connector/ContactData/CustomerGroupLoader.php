<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Connector\ContactData;

use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Model\GroupFactory;
use Magento\Customer\Model\ResourceModel\Group as GroupResource;

class CustomerGroupLoader
{
    /**
     * @var GroupFactory
     */
    private $groupFactory;

    /**
     * @var GroupResource
     */
    private $groupResource;

    /**
     * @var array
     */
    private $groups = [];

    /**
     * @param GroupFactory $groupFactory
     * @param GroupResource $groupResource
     */
    public function __construct(
        GroupFactory $groupFactory,
        GroupResource $groupResource
    ) {
        $this->groupFactory = $groupFactory;
        $this->groupResource = $groupResource;
    }

    /**
     * Get customer group code by id.
     *
     * @param int $groupId
     *
     * @return string
     */
    public function getCustomerGroup(int $groupId): string
    {
        if ($groupId === GroupInterface::CUST_GROUP_ALL) {
            return 'ALL GROUPS';
        }
        if (!isset($this->groups[$groupId])) {
            $groupModel = $this->groupFactory->create();
            $this->groupResource->load($groupModel, $groupId);
            $this->groups[$groupId] = $groupModel ? $groupModel->getCode() : '';
        }
        return $this->groups[$groupId];
    }
}
