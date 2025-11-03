<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Api\Model\Product\Provider\Attributes;

use Magento\Framework\Data\Collection;

interface ProductTaxonomyProviderInterface
{
    /**
     * Get the brand attribute of the product.
     *
     * @return string .
     */
    public function getBrand(): ?string;

    /**
     * Get the categories of the product.
     *
     * @return \Magento\Framework\Data\Collection Returns a collection of categories.
     */
    public function getCategories(): Collection;
}
