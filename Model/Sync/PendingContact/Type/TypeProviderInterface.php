<?php

namespace Dotdigitalgroup\Email\Model\Sync\PendingContact\Type;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollectionFactory;

interface TypeProviderInterface
{
    /**
     * Get collection factory.
     *
     * @return AbstractCollectionFactory
     */
    public function getCollectionFactory();

    /**
     * Get resource model.
     *
     * @return AbstractDb
     */
    public function getResourceModel();
}
