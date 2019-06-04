<?php

namespace Dotdigitalgroup\Email\Block\Recommended;

/**
 * Quote products block
 *
 * @api
 */
class Quoteproducts extends \Dotdigitalgroup\Email\Block\Recommended
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Recommended
     */
    public $recommendedHelper;

    /**
     * Quoteproducts constructor.
     *
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Dotdigitalgroup\Email\Block\Helper\Font $font
     * @param \Dotdigitalgroup\Email\Model\Catalog\UrlFinder $urlFinder
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Dotdigitalgroup\Email\Helper\Recommended $recommendedHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Dotdigitalgroup\Email\Block\Helper\Font $font,
        \Dotdigitalgroup\Email\Model\Catalog\UrlFinder $urlFinder,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Helper\Recommended $recommendedHelper,
        array $data = []
    ) {
        $this->helper            = $helper;
        $this->recommendedHelper = $recommendedHelper;

        parent::__construct($context, $font, $urlFinder, $data);
    }

    /**
     * Get the products to display for table.
     *
     * @return array
     */
    public function getLoadedProductCollection()
    {
        $params = $this->getRequest()->getParams();
        //check for param code and id
        if (! isset($params['quote_id']) ||
            ! isset($params['code']) ||
            ! $this->helper->isCodeValid($params['code'])
        ) {
            $this->helper->log('Quote recommendation no id or valid code is set');
            return [];
        }

        //products to be displayed for recommended pages
        $quoteId = (int) $this->getRequest()->getParam('quote_id');
        //display mode based on the action name
        $mode = $this->getRequest()->getActionName();
        $quoteItems = $this->helper->getQuoteAllItemsFor($quoteId);
        //number of product items to be displayed
        $limit = $this->recommendedHelper->getDisplayLimitByMode($mode);
        $numItems = count($quoteItems);

        //no product found to display
        if ($numItems == 0 || !$limit) {
            return [];
        } elseif ($numItems > $limit) {
            $maxPerChild = 1;
        } else {
            $maxPerChild = number_format($limit / $numItems);
        }

        $this->helper->log(
            'DYNAMIC QUOTE PRODUCTS : limit ' . $limit . ' products : '
            . $numItems . ', max per child : ' . $maxPerChild
        );

        $productsToDisplayCounter = 0;
        $productsToDisplay = $this->recommendedHelper->getProductsToDisplay(
            $quoteItems,
            $mode,
            $productsToDisplayCounter,
            $limit,
            $maxPerChild
        );

        //check for more space to fill up the table with fallback products
        if ($productsToDisplayCounter < $limit) {
            $productsToDisplay = $this->recommendedHelper->fillProductsToDisplay(
                $productsToDisplay,
                $productsToDisplayCounter,
                $limit
            );
        }

        $this->helper->log(
            'quote - loaded product to display ' . count($productsToDisplay)
        );

        return $productsToDisplay;
    }

    /**
     * Diplay mode type.
     *
     * @return mixed|string
     */
    public function getMode()
    {
        return $this->recommendedHelper->getDisplayType();
    }

    /**
     * Number of the columns.
     *
     * @return int|mixed
     */
    public function getColumnCount()
    {
        return $this->recommendedHelper->getDisplayLimitByMode(
            $this->getRequest()
                 ->getActionName()
        );
    }

    /**
     * @param null|string|bool|int|\Magento\Store\Api\Data\StoreInterface $store
     *
     * @return string
     */
    public function getTextForUrl($store)
    {
        $store = $this->_storeManager->getStore($store);

        return $store->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_LINK_TEXT
        );
    }
}
