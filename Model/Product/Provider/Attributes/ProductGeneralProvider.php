<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Product\Provider\Attributes;

use Dotdigitalgroup\Email\Api\Model\Product\Provider\Attributes\ProductGeneralProviderInterface;
use Dotdigitalgroup\Email\Api\Model\Product\Provider\ProductProviderInterface;
use Dotdigitalgroup\Email\Model\Catalog\UrlFinder;
use Magento\Framework\Filter\FilterManager;

class ProductGeneralProvider implements ProductGeneralProviderInterface
{
    /**
     * @var ProductProviderInterface
     */
    private $productProvider;

    /**
     * @var FilterManager
     */
    private $filterManager;

    /**
     * @var UrlFinder
     */
    private UrlFinder $urlFinder;

    /**
     * @param ProductProviderInterface $productProvider
     * @param FilterManager $filterManager
     * @param UrlFinder $urlFinder
     */
    public function __construct(
        ProductProviderInterface $productProvider,
        FilterManager $filterManager,
        UrlFinder $urlFinder
    ) {
        $this->productProvider = $productProvider;
        $this->filterManager = $filterManager;
        $this->urlFinder = $urlFinder;
    }

    /**
     * @inheritDoc
     */
    public function getId(): ?int
    {
        $product = $this->productProvider->getProduct();
        return $product ? (int)$product->getId() : null;
    }

    /**
     * @inheritDoc
     */
    public function getSku(): ?string
    {
        $product = $this->productProvider->getProduct();
        return $product ? $product->getSku() : null;
    }

    /**
     * @inheritDoc
     */
    public function getName(): ?string
    {
        $product = $this->productProvider->getProduct();
        return $product ? $product->getName() : null;
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        $product = $this->productProvider->getProduct();
        $description = $product ? $product->getCustomAttribute('description') : null;
        return $description ? $this->filterManager->stripTags($description->getValue()) : '';
    }

    /**
     * @inheritDoc
     */
    public function getVisibility(): int
    {
        $product = $this->productProvider->getProduct();
        if ($product) {
            return (int) $product->getVisibility();
        }
        return 0;
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        $product = $this->productProvider->getProduct();
        if ($product) {
            return $product->getTypeId();
        }
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getUrl(): ?string
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->productProvider->getProduct();
        return $this->urlFinder->fetchFor($product);
    }
}
