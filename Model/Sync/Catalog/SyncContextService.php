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
     * @param $module
     */
    public function setModule($module)
    {
        $this->module = $module;
    }

    /**
     * @param $customerGroupId
     */
    public function setCustomerGroupId($customerGroupId)
    {
        $this->customerGroupId = $customerGroupId;
    }

    /**
     * @return string
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * @return int|null
     */
    public function getCustomerGroupId()
    {
        return $this->customerGroupId;
    }
}
