<?php

namespace Dotdigitalgroup\Email\Block\Recommended;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Product\ImageFinder;
use Dotdigitalgroup\Email\Model\Product\ImageType\Context\DynamicContent;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\ResourceModel\Quote;

/**
 * Quote products block
 *
 * @api
 */
class Quoteproducts extends \Dotdigitalgroup\Email\Block\Recommended
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Recommended
     */
    private $recommendedHelper;

    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var Quote
     */
    private $quoteResource;

    /**
     * Quoteproducts constructor.
     *
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Dotdigitalgroup\Email\Block\Helper\Font $font
     * @param \Dotdigitalgroup\Email\Model\Catalog\UrlFinder $urlFinder
     * @param DynamicContent $imageType
     * @param ImageFinder $imageFinder
     * @param Logger $logger
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Dotdigitalgroup\Email\Helper\Recommended $recommendedHelper
     * @param QuoteFactory $quoteFactory
     * @param Quote $quoteResource
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Dotdigitalgroup\Email\Block\Helper\Font $font,
        \Dotdigitalgroup\Email\Model\Catalog\UrlFinder $urlFinder,
        DynamicContent $imageType,
        ImageFinder $imageFinder,
        Logger $logger,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Helper\Recommended $recommendedHelper,
        QuoteFactory $quoteFactory,
        Quote $quoteResource,
        array $data = []
    ) {
        $this->logger = $logger;
        $this->helper            = $helper;
        $this->recommendedHelper = $recommendedHelper;
        $this->quoteFactory = $quoteFactory;
        $this->quoteResource = $quoteResource;

        parent::__construct($context, $font, $urlFinder, $imageType, $imageFinder, $data);
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
        $quoteItems = $this->getQuoteAllItemsFor($quoteId);
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
     * Display mode type.
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

    /**
     * @param int $quoteId
     * @return array
     */
    private function getQuoteAllItemsFor($quoteId)
    {
        try {
            $quoteModel = $this->quoteFactory->create();
            $this->quoteResource->load($quoteModel, $quoteId);
            return $quoteModel->getAllItems();
        } catch (\Exception $e) {
            $this->logger->debug(
                sprintf('Error fetching items for quote ID: %s', $quoteId),
                [(string) $e]
            );
            return [];
        }
    }
}
