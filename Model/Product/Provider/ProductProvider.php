<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Product\Provider;

use Dotdigitalgroup\Email\Api\Model\Product\Provider\ProductProviderInterface;
use Magento\Catalog\Api\Data\ProductInterface;

class ProductProvider implements ProductProviderInterface
{
    /**
     * @var \Magento\Catalog\Api\Data\ProductInterface|null
     */
    private $product;

    /**
     * Set the product.
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface|null $product
     * @return void
     */
    public function setProduct(?ProductInterface $product): void
    {
        $this->product = $product;
    }

    /**
     * @inheritDoc
     */
    public function getProduct(): ?ProductInterface
    {
        return $this->product;
    }
}
