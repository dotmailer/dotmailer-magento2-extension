<?php

namespace Dotdigitalgroup\Email\Model\Sync\Catalog;

class SyncContextService
{
    /**
     * @var string
     */
    private $module = 'Dotdigitalgroup_Email';

    /**
     * @var int|null
     */
    private $customerGroupId = null;

    /**
     * Set module.
     *
     * @param string $module
     */
    public function setModule($module)
    {
        $this->module = $module;
    }

    /**
     * Set customer group id.
     *
     * @param int $customerGroupId
     */
    public function setCustomerGroupId($customerGroupId)
    {
        $this->customerGroupId = $customerGroupId;
    }

    /**
     * Get module.
     *
     * @return string
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * Get customer group id.
     *
     * @return int|null
     */
    public function getCustomerGroupId()
    {
        return $this->customerGroupId;
    }
}
