<?php

namespace Dotdigitalgroup\Email\Model\Product;

use Dotdigitalgroup\Email\Api\Product\CurrentProductInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Data as CatalogHelper;

class CurrentProduct implements CurrentProductInterface
{
    /**
     * @var CatalogHelper
     */
    private $catalogHelper;

    /**
     * @var ProductInterface|null
     */
    private $currentProduct = null;

    /**
     * Constructor
     *
     * @param CatalogHelper $catalogHelper
     */
    public function __construct(
        CatalogHelper $catalogHelper
    ) {
        $this->catalogHelper = $catalogHelper;
    }

    /**
     * @inheritDoc
     */
    public function getProduct(): ?ProductInterface
    {
        if (!isset($this->currentProduct)) {
            $this->currentProduct = $this->catalogHelper->getProduct();
        }
        return $this->currentProduct;
    }

    /**
     * @inheritDoc
     */
    public function getProductVisibility(): int
    {
        $product = $this->getProduct();
        if ($product) {
            return (int) $product->getVisibility();
        }
        return 0;
    }

    /**
     * @inheritDoc
     */
    public function getProductType(): string
    {
        $product = $this->getProduct();
        if ($product) {
            return $product->getTypeId();
        }
        return '';
    }
}
