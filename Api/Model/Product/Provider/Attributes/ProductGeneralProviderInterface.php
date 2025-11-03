<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Api\Model\Product\Provider\Attributes;

interface ProductGeneralProviderInterface
{
    /**
     * Get the product ID.
     *
     * @return int|null Returns the product ID if available, or null if not.
     */
    public function getId(): ?int;

    /**
     * Get the product SKU.
     *
     * @return string|null Returns the product SKU if available, or null if not.
     */
    public function getSku(): ?string;

    /**
     * Get the product name.
     *
     * @return string|null Returns the product name if available, or null if not.
     */
    public function getName(): ?string;

    /**
     * Get the product description.
     *
     * @return string Returns the product description if available, or null if not.
     */
    public function getDescription(): string;

    /**
     * Get the product visibility.
     *
     * @return int Returns the product visibility.
     */
    public function getVisibility(): int;

    /**
     * Get the product type.
     *
     * @return string Returns the product type.
     */
    public function getType(): string;

    /**
     * Get the product URL.
     *
     * @return string|null Returns the product URL if available, or null if not.
     */
    public function getUrl(): ?string;
}
