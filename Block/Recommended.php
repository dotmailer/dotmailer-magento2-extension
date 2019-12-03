<?php

namespace Dotdigitalgroup\Email\Block;

/**
 * Recommended  block
 *
 * @api
 */
class Recommended extends \Magento\Catalog\Block\Product\AbstractProduct
{
    /**
     * @var \Dotdigitalgroup\Email\Model\Catalog\UrlFinder
     */
    protected $urlFinder;

    /**
     * @var \Dotdigitalgroup\Email\Block\Helper\Font
     */
    private $font;

    /**
     * Recommended constructor.
     *
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param Helper\Font $font
     * @param \Dotdigitalgroup\Email\Model\Catalog\UrlFinder $urlFinder
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Dotdigitalgroup\Email\Block\Helper\Font $font,
        \Dotdigitalgroup\Email\Model\Catalog\UrlFinder $urlFinder,
        array $data = []
    ) {
        $this->font = $font;
        $this->urlFinder = $urlFinder;
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
     * Use the UrlFinder to get a product's image URL
     *
     * @param $product
     * @param string $imageId
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductImageUrl($product, $imageId = 'product_small_image')
    {
        return $this->urlFinder->getProductImageUrl($product, $imageId);
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

    /**
     * Return a product's image URL, if it has one.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param string $imageId
     *
     * @return string
     */
    public function getSmallImageUrl($product, $imageId)
    {
        return $this->urlFinder->getProductImageUrl($product, $imageId);
    }
}
