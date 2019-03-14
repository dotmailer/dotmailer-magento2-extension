<?php

namespace Dotdigitalgroup\Email\Block\Recommended;

use Magento\Store\Model\Store;

/**
 * Bestsellers block
 *
 * @api
 */
class Bestsellers extends \Dotdigitalgroup\Email\Block\Recommended
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Recommended
     */
    private $recommnededHelper;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Catalog
     */
    private $catalog;

    /**
     * @var \Dotdigitalgroup\Email\Model\Catalog\UrlFinder
     */
    private $urlFinder;

    /**
     * Bestsellers constructor.
     *
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Dotdigitalgroup\Email\Block\Helper\Font $font
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Catalog $catalog
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Dotdigitalgroup\Email\Helper\Recommended $recommended
     * @param \Dotdigitalgroup\Email\Model\Catalog\UrlFinder $urlFinder
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Dotdigitalgroup\Email\Block\Helper\Font $font,
        \Dotdigitalgroup\Email\Model\ResourceModel\Catalog $catalog,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Helper\Recommended $recommended,
        \Dotdigitalgroup\Email\Model\Catalog\UrlFinder $urlFinder,
        array $data = []
    ) {
        $this->helper             = $helper;
        $this->recommnededHelper  = $recommended;
        $this->catalog            = $catalog;
        $this->urlFinder          = $urlFinder;
        parent::__construct($context, $font, $data);
    }

    /**
     * Collection
     *
     * @return array|\Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function getLoadedProductCollection()
    {
        $params = $this->getRequest()->getParams();
        if (! isset($params['code']) || ! $this->helper->isCodeValid($params['code'])) {
            $this->helper->log('Best sellers no valid code is set');
            return [];
        }

        //mode param grid/list
        $mode = $this->getRequest()->getActionName();
        //limit of the products to display
        $limit = $this->recommnededHelper->getDisplayLimitByMode($mode);
        //date range
        $from = $this->recommnededHelper->getTimeFromConfig($mode);
        $to = $this->_localeDate->date()->format(\Zend_Date::ISO_8601);
        $storeId = $this->_storeManager->getStore()->getId();

        return $this->catalog->getBestsellerCollection($from, $to, $limit, $storeId);
    }

    /**
     * Display type mode.
     *
     * @return string|boolean
     */
    public function getMode()
    {
        return $this->recommnededHelper->getDisplayType();
    }

    /**
     * @param null|string|bool|int|\Magento\Store\Api\Data\StoreInterface $store
     *
     * @return string|null
     */
    public function getTextForUrl($store)
    {
        /** @var Store $store */
        $store = $this->_storeManager->getStore($store);

        return $store->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_LINK_TEXT
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
