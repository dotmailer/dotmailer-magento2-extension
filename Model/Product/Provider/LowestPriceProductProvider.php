<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Product\Provider;

use Dotdigitalgroup\Email\Api\Model\Product\Provider\ProductProviderInterface;
use Magento\Catalog\Api\Data\ProductInterface;

class LowestPriceProductProvider implements ProductProviderInterface
{
    /**
     * @var LowestPriceProductFinder
     */
    private $productFinder;

    /**
     * @var ProductInterface|null
     */
    private $currentProduct = null;

    /**
     * @param LowestPriceProductFinder $productFinder
     */
    public function __construct(LowestPriceProductFinder $productFinder)
    {
        $this->productFinder = $productFinder;
    }

    /**
     * @inheritDoc
     */
    public function getProduct(): ?ProductInterface
    {
        if (!isset($this->currentProduct)) {
            $this->currentProduct = $this->productFinder->findLowestPricedProduct();
        }
        return $this->currentProduct;
    }
}
