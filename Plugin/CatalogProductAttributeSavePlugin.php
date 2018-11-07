<?php

namespace Dotdigitalgroup\Email\Plugin;

/**
 * Class CatalogProductAttributeSavePlugin - reset product in email_catalog when update attribute mass action is used.
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class CatalogProductAttributeSavePlugin
{
    /**
     * @var \Magento\Catalog\Helper\Product\Edit\Action\Attribute
     */
    private $attributeHelper;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Catalog
     */
    private $catalogResource;

    public function __construct(
        \Magento\Catalog\Helper\Product\Edit\Action\Attribute $attributeHelper,
        \Dotdigitalgroup\Email\Model\ResourceModel\Catalog $catalogResource
    ) {
        $this->attributeHelper = $attributeHelper;
        $this->catalogResource = $catalogResource;
    }

    /**
     * @param \Magento\Catalog\Controller\Adminhtml\Product\Action\Attribute\Save $subject
     * @param $result
     *
     * @return mixed
     */
    public function afterExecute(
        \Magento\Catalog\Controller\Adminhtml\Product\Action\Attribute\Save $subject,
        $result
    ) {
        $productIds = $this->attributeHelper->getProductIds();
        if (! empty($productIds)) {
            $this->catalogResource->setModified($productIds);
        }
        return $result;
    }
}
