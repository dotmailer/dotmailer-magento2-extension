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
     * @var \Dotdigitalgroup\Email\Block\Helper\Font
     */
    private $font;

    /**
     * Recommended constructor.
     *
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param Helper\Font $font
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Dotdigitalgroup\Email\Block\Helper\Font $font,
        array $data = []
    ) {
        $this->font                     = $font;
        parent::__construct($context, $data);
    }

    /**
     * @return array
     */
    public function getDynamicStyles()
    {
        return $this->font->getDynamicStyles();
    }
}
