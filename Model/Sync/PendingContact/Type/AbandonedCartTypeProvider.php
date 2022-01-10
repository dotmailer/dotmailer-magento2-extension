<?php

namespace Dotdigitalgroup\Email\Model\Sync\PendingContact\Type;

use Dotdigitalgroup\Email\Model\ResourceModel\Abandoned;
use Dotdigitalgroup\Email\Model\ResourceModel\Abandoned\CollectionFactory;

class AbandonedCartTypeProvider implements TypeProviderInterface
{
    /**
     * @var Abandoned
     */
    private $resourceModel;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * AbandonedCartTypeProvider constructor.
     * @param Abandoned $resourceModel
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Abandoned $resourceModel,
        CollectionFactory $collectionFactory
    ) {
        $this->resourceModel = $resourceModel;
        $this->collectionFactory = $collectionFactory;
    }

    public function getCollectionFactory()
    {
        return $this->collectionFactory;
    }

    public function getResourceModel()
    {
        return $this->resourceModel;
    }
}
