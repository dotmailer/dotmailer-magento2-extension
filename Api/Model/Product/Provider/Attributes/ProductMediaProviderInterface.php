<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Api\Model\Product\Provider\Attributes;

interface ProductMediaProviderInterface
{
    /**
     * Get the image path of the product.
     *
     * @return string|null Returns the image path if available, or null if not.
     */
    public function getImagePath(): ?string;
}
