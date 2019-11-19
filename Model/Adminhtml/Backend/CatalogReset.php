<?php

namespace Dotdigitalgroup\Email\Model\Adminhtml\Backend;

use Magento\Framework\App\Config\Value;
use Dotdigitalgroup\Email\Model\ResourceModel\Catalog;

class CatalogReset extends Value
{
    /**
     * @var Catalog
     */
    private $catalogResource;

    public function __construct(
        Catalog $catalogResource,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->catalogResource = $catalogResource;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    public function afterSave()
    {
        if (!$this->isValueChanged()) {
            return parent::afterSave();
        }

        $this->catalogResource->resetCatalog();

        return parent::afterSave();
    }
}
