<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Api\Model\Product\Provider;

use Magento\Catalog\Api\Data\ProductInterface;

interface ProductProviderInterface
{
    /**
     * Interface method to get a product.
     *
     * @return \Magento\Catalog\Api\Data\ProductInterface|null
     */
    public function getProduct(): ?ProductInterface;
}
