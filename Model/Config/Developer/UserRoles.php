<?php

namespace Dotdigitalgroup\Email\Model\Config\Developer;

use Magento\Authorization\Model\ResourceModel\Role\CollectionFactory;
use Magento\Authorization\Model\Acl\Role\Group;

class UserRoles implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var CollectionFactory
     */
    private $roleCollection;

    /**
     * Orderstatus constructor.
     *
     * @param CollectionFactory $roleCollection
     */
    public function __construct(
        CollectionFactory $roleCollection
    ) {
        $this->roleCollection = $roleCollection;
    }

    /**
     * Returns the order statuses for field order_statuses.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $roles = $this->roleCollection->create()
            ->toArray();

        $options = [];

        foreach ($roles['items'] as $role) {
            if ($role['role_type'] === Group::ROLE_TYPE) {
                $options[] = ['value' => $role['role_id'], 'label' => $role['role_name']];
            }
        }

        return $options;
    }
}
