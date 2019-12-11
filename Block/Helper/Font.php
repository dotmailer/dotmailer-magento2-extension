<?php

namespace Dotdigitalgroup\Email\Block\Helper;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Magento\Framework\View\Element\Template\Context;

/**
 * Font block
 *
 * @api
 */
class Font extends \Magento\Framework\View\Element\Template
{
    /**
     * @var Data
     */
    public $helper;

    /**
     * Font constructor.
     *
     * @param Context $context
     * @param Data $helper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    /**
     * Coupon Font from config.
     *
     * @return string|boolean
     */
    public function getEscapedFontFamilyForCoupon()
    {
        $rawFont = $this->helper->getWebsiteConfig(
            Config::XML_PATH_CONNECTOR_DYNAMIC_COUPON_FONT
        );
        return $this->getSanitisedFont($rawFont);
    }

    /**
     * @param string $rawFont
     * @return string|boolean
     */
    private function getSanitisedFont($rawFont)
    {
        $fontArray = explode(',', $rawFont);

        $escapeFont = function ($font) {
            if (strpos($font, '\'') !== false) {
                $font = str_replace('\'', '', $font);
                $font = $this->escapeHtml($font);
                return "\"$font\"";
            } else {
                return $this->escapeHtml($font);
            }
        };

        $sanitisedFont = array_map($escapeFont, $fontArray);
        return implode(', ', $sanitisedFont);
    }

    /**
     * Dynamic styles from config.
     *
     * @return array
     */
    public function getDynamicStyles()
    {
        $rawDocFont = $this->helper->getConfigValue(Config::XML_PATH_CONNECTOR_DYNAMIC_DOC_FONT);
        return [
            'nameStyle' => explode(
                ',',
                $this->helper->getConfigValue(Config::XML_PATH_CONNECTOR_DYNAMIC_NAME_STYLE)
            ),
            'priceStyle' => explode(
                ',',
                $this->helper->getConfigValue(Config::XML_PATH_CONNECTOR_DYNAMIC_PRICE_STYLE)
            ),
            'linkStyle' => explode(
                ',',
                $this->helper->getConfigValue(Config::XML_PATH_CONNECTOR_DYNAMIC_LINK_STYLE)
            ),
            'otherStyle' => explode(
                ',',
                $this->helper->getConfigValue(Config::XML_PATH_CONNECTOR_DYNAMIC_OTHER_STYLE)
            ),
            'nameColor' => $this->helper->getConfigValue(Config::XML_PATH_CONNECTOR_DYNAMIC_NAME_COLOR),
            'fontSize' => $this->helper->getConfigValue(Config::XML_PATH_CONNECTOR_DYNAMIC_NAME_FONT_SIZE),
            'priceColor' => $this->helper->getConfigValue(Config::XML_PATH_CONNECTOR_DYNAMIC_PRICE_COLOR),
            'priceFontSize' => $this->helper->getConfigValue(Config::XML_PATH_CONNECTOR_DYNAMIC_PRICE_FONT_SIZE),
            'urlColor' => $this->helper->getConfigValue(Config::XML_PATH_CONNECTOR_DYNAMIC_LINK_COLOR),
            'urlFontSize' => $this->helper->getConfigValue(Config::XML_PATH_CONNECTOR_DYNAMIC_LINK_FONT_SIZE),
            'otherColor' => $this->helper->getConfigValue(Config::XML_PATH_CONNECTOR_DYNAMIC_OTHER_COLOR),
            'otherFontSize' => $this->helper->getConfigValue(Config::XML_PATH_CONNECTOR_DYNAMIC_OTHER_FONT_SIZE),
            'docFont' => $this->getSanitisedFont($rawDocFont),
            'docBackgroundColor' => $this->helper->getConfigValue(Config::XML_PATH_CONNECTOR_DYNAMIC_DOC_BG_COLOR),
            'dynamicStyling' => $this->helper->getConfigValue(Config::XML_PATH_CONNECTOR_DYNAMIC_STYLING),
        ];
    }
}
