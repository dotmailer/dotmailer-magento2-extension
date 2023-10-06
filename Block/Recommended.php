<?php

namespace Dotdigitalgroup\Email\Block;

use Dotdigitalgroup\Email\Block\Helper\Font;
use Dotdigitalgroup\Email\Model\Catalog\UrlFinder;
use Dotdigitalgroup\Email\Model\Product\ImageFinder;
use Dotdigitalgroup\Email\Model\Product\ImageType\Context\DynamicContent;
use Magento\Catalog\Block\Product\Context;

/**
 * Recommended  block
 *
 * @api
 */
class Recommended extends \Magento\Catalog\Block\Product\AbstractProduct
{
    /**
     * @var UrlFinder
     */
    protected $urlFinder;

    /**
     * @var Font
     */
    private $font;

    /**
     * @var DynamicContent
     */
    protected $imageType;

    /**
     * @var ImageFinder
     */
    protected $imageFinder;

    /**
     * Recommended constructor.
     *
     * @param Context $context
     * @param Helper\Font $font
     * @param UrlFinder $urlFinder
     * @param DynamicContent $imageType
     * @param ImageFinder $imageFinder
     * @param array $data
     */
    public function __construct(
        Context $context,
        Font $font,
        UrlFinder $urlFinder,
        DynamicContent $imageType,
        ImageFinder $imageFinder,
        array $data = []
    ) {
        $this->font = $font;
        $this->urlFinder = $urlFinder;
        $this->imageType = $imageType;
        $this->imageFinder = $imageFinder;
        parent::__construct($context, $data);
    }

    /**
     * @return array
     */
    public function getDynamicStyles()
    {
        return $this->font->getDynamicStyles();
    }

    /**
     * Use the ImageFinder to get a product's image URL.
     * If image types configuration for Dynamic Content is set to 'Default',
     * we use the id specified in the block (or the fallback product_small_image).
     *
     * @param $product
     * @param string $imageId
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductImageUrl($product, $imageId = 'product_small_image')
    {
        $imageTypeFromConfig = $this->imageType->getImageType(
            $this->_storeManager->getStore()->getWebsiteId()
        );

        return $this->imageFinder->getImageUrl(
            $product,
            $imageTypeFromConfig['id']
                ? $imageTypeFromConfig
                : ['id' => $imageId, 'role' => null]
        );
    }

    /**
     * Return a product's parent URL, if it has one.
     *
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return string
     */
    public function getConfigurableParentUrl($product)
    {
        return $this->urlFinder->fetchFor($product);
    }
}
