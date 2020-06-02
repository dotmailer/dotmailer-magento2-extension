<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel\User;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'user_id';

    /**
     * Initialize resource collection
     */
    public function _construct()
    {
        $this->_init(\Magento\User\Model\User::class, \Magento\User\Model\ResourceModel\User::class);
    }

    /**
     * Return admin_user rows matching the supplied ids from authorization_role.
     * Note this JOIN is baked into Magento core from 2.3, we include it here for 2.2. support.
     *
     * @param string $roles
     * @return $this
     */
    public function fetchUsersByRole($roles)
    {
        $this->getSelect()->joinLeft(
            ['user_role' => $this->getTable('authorization_role')],
            'main_table.user_id = user_role.user_id AND user_role.parent_id != 0',
            []
        )->joinLeft(
            ['detail_role' => $this->getTable('authorization_role')],
            'user_role.parent_id = detail_role.role_id',
            ['role_name']
        );

        return $this->addFieldToFilter(
            'user_role.parent_id',
            ['in' => explode(',', $roles)]
        );
    }
}
