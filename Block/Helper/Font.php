<?php

namespace Dotdigitalgroup\Email\Block\Helper;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Magento\Framework\Escaper;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;

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
    private $helper;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var int
     */
    private $websiteId;

    /**
     * @param Context $context
     * @param Data $helper
     * @param Escaper $escaper
     * @param StoreManagerInterface $storeManager
     * @param array $data
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function __construct(
        Context $context,
        Data $helper,
        Escaper $escaper,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->websiteId = $storeManager->getStore()->getWebsiteId();
        $this->helper = $helper;
        $this->escaper = $escaper;
        parent::__construct($context, $data);
    }

    /**
     * Coupon Font from config.
     *
     * @return string
     */
    public function getEscapedFontFamilyForCoupon()
    {
        $rawFont = $this->helper->getWebsiteConfig(
            Config::XML_PATH_CONNECTOR_DYNAMIC_COUPON_FONT,
            $this->websiteId
        );
        return $this->getSanitisedFont($rawFont);
    }

    /**
     * Get the sanitised font family.
     *
     * If any part of a font-family definition contains apostrophes,
     * remove them, escape the HTML inside the apostrophes, then
     * put the apostrophes back.
     *
     * @param string $rawFont
     * @return string
     */
    private function getSanitisedFont($rawFont)
    {
        $fontArray = explode(',', $rawFont);

        $escapeFont = function ($font) {
            if (strpos($font, '\'') !== false) {
                $font = str_replace('\'', '', $font);
                $font = $this->escaper->escapeHtml($font);
                return "\'$font\'";
            } else {
                return $this->escaper->escapeHtml($font);
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
        $rawDocFont = $this->helper->getWebsiteConfig(
            Config::XML_PATH_CONNECTOR_DYNAMIC_DOC_FONT,
            $this->websiteId
        );
        return [
            'nameStyle' => explode(
                ',',
                $this->helper->getWebsiteConfig(
                    Config::XML_PATH_CONNECTOR_DYNAMIC_NAME_STYLE,
                    $this->websiteId
                )
            ),
            'priceStyle' => explode(
                ',',
                $this->helper->getWebsiteConfig(
                    Config::XML_PATH_CONNECTOR_DYNAMIC_PRICE_STYLE,
                    $this->websiteId
                )
            ),
            'linkStyle' => explode(
                ',',
                $this->helper->getWebsiteConfig(
                    Config::XML_PATH_CONNECTOR_DYNAMIC_LINK_STYLE,
                    $this->websiteId
                )
            ),
            'otherStyle' => explode(
                ',',
                $this->helper->getWebsiteConfig(
                    Config::XML_PATH_CONNECTOR_DYNAMIC_OTHER_STYLE,
                    $this->websiteId
                )
            ),
            'nameColor' => $this->helper->getWebsiteConfig(
                Config::XML_PATH_CONNECTOR_DYNAMIC_NAME_COLOR,
                $this->websiteId
            ),
            'fontSize' => $this->helper->getWebsiteConfig(
                Config::XML_PATH_CONNECTOR_DYNAMIC_NAME_FONT_SIZE,
                $this->websiteId
            ),
            'priceColor' => $this->helper->getWebsiteConfig(
                Config::XML_PATH_CONNECTOR_DYNAMIC_PRICE_COLOR,
                $this->websiteId
            ),
            'priceFontSize' => $this->helper->getWebsiteConfig(
                Config::XML_PATH_CONNECTOR_DYNAMIC_PRICE_FONT_SIZE,
                $this->websiteId
            ),
            'urlColor' => $this->helper->getWebsiteConfig(
                Config::XML_PATH_CONNECTOR_DYNAMIC_LINK_COLOR,
                $this->websiteId
            ),
            'urlFontSize' => $this->helper->getWebsiteConfig(
                Config::XML_PATH_CONNECTOR_DYNAMIC_LINK_FONT_SIZE,
                $this->websiteId
            ),
            'otherColor' => $this->helper->getWebsiteConfig(
                Config::XML_PATH_CONNECTOR_DYNAMIC_OTHER_COLOR,
                $this->websiteId
            ),
            'otherFontSize' => $this->helper->getWebsiteConfig(
                Config::XML_PATH_CONNECTOR_DYNAMIC_OTHER_FONT_SIZE,
                $this->websiteId
            ),
            'docFont' => $this->getSanitisedFont($rawDocFont),
            'docBackgroundColor' => $this->helper->getWebsiteConfig(
                Config::XML_PATH_CONNECTOR_DYNAMIC_DOC_BG_COLOR,
                $this->websiteId
            ),
            'dynamicStyling' => $this->helper->getWebsiteConfig(
                Config::XML_PATH_CONNECTOR_DYNAMIC_STYLING,
                $this->websiteId
            ),
        ];
    }
}
