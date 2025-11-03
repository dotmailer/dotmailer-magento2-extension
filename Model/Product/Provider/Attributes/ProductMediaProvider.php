<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Product\Provider\Attributes;

use Dotdigitalgroup\Email\Api\Model\Product\Provider\Attributes\ProductMediaProviderInterface;
use Dotdigitalgroup\Email\Api\Model\Product\Provider\ProductProviderInterface;
use Dotdigitalgroup\Email\Model\Product\ImageFinder;
use Dotdigitalgroup\Email\Model\Product\ImageType\Context\AbandonedBrowse as ImageType;
use Magento\Store\Model\StoreManagerInterface;

class ProductMediaProvider implements ProductMediaProviderInterface
{
    /**
     * @var ProductProviderInterface
     */
    private $productProvider;

    /**
     * @var ImageFinder
     */
    private $imageFinder;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ImageType
     */
    private $imageType;

    /**
     * @param ProductProviderInterface $productProvider
     * @param ImageFinder $imageFinder
     * @param StoreManagerInterface $storeManager
     * @param ImageType $imageType
     */
    public function __construct(
        ProductProviderInterface $productProvider,
        ImageFinder $imageFinder,
        StoreManagerInterface $storeManager,
        ImageType $imageType
    ) {
        $this->productProvider = $productProvider;
        $this->imageFinder = $imageFinder;
        $this->storeManager = $storeManager;
        $this->imageType = $imageType;
    }

    /**
     * @inheritDoc
     */
    public function getImagePath(): ?string
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->productProvider->getProduct();
        try {
            return $this->imageFinder->getImageUrl(
                $product,
                $this->imageType->getImageType(
                    $this->storeManager->getStore()->getWebsiteId()
                )
            );
        } catch (\Exception $e) {
            return null;
        }
    }
}
