<?php

namespace Dotdigitalgroup\Email\Model\Sync\PendingContact\Type;

use Dotdigitalgroup\Email\Model\ResourceModel\Automation;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation\CollectionFactory;

class AutomationTypeProvider implements TypeProviderInterface
{
    /**
     * @var Automation
     */
    private $resourceModel;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * AutomationTypeProvider constructor.
     *
     * @param Automation $resourceModel
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Automation $resourceModel,
        CollectionFactory $collectionFactory
    ) {
        $this->resourceModel = $resourceModel;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Get collection factory.
     *
     * @return CollectionFactory
     */
    public function getCollectionFactory()
    {
        return $this->collectionFactory;
    }

    /**
     * Get resource model.
     *
     * @return Automation
     */
    public function getResourceModel()
    {
        return $this->resourceModel;
    }
}
