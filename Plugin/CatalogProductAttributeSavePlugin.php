<?php

namespace Dotdigitalgroup\Email\Plugin;

use Dotdigitalgroup\Email\Model\ResourceModel\Catalog;
use Magento\Catalog\Controller\Adminhtml\Product\Action\Attribute\Save;
use Magento\Catalog\Helper\Product\Edit\Action\Attribute;
use Magento\Framework\Controller\Result\Redirect;

/**
 * Class CatalogProductAttributeSavePlugin - reset product in email_catalog when update attribute mass action is used.
 */
class CatalogProductAttributeSavePlugin
{
    /**
     * @var Attribute
     */
    private $attributeHelper;

    /**
     * @var Catalog
     */
    private $catalogResource;

    /**
     * CatalogProductAttributeSavePlugin constructor.
     *
     * @param Attribute $attributeHelper
     * @param Catalog $catalogResource
     */
    public function __construct(
        Attribute $attributeHelper,
        Catalog $catalogResource
    ) {
        $this->attributeHelper = $attributeHelper;
        $this->catalogResource = $catalogResource;
    }

    /**
     * After execute.
     *
     * @param Save $subject
     * @param Redirect $result
     *
     * @return Redirect
     */
    public function afterExecute(
        Save $subject,
        $result
    ) {
        $productIds = $this->attributeHelper->getProductIds();
        if (! empty($productIds)) {
            $this->catalogResource->setUnprocessedByIds($productIds);
        }
        return $result;
    }
}
