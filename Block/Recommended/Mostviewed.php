<?php

namespace Dotdigitalgroup\Email\Block\Recommended;

/**
 * Most viewed block
 *
 * @api
 */
class Mostviewed extends \Magento\Catalog\Block\Product\AbstractProduct
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    public $priceHelper;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Recommended
     */
    public $recommnededHelper;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\CatalogFactory
     */
    public $catalog;

    /**
     * Mostviewed constructor.
     *
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Catalog $catalog
     * @param \Dotdigitalgroup\Email\Helper\Recommended $recommended
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Dotdigitalgroup\Email\Model\ResourceModel\Catalog $catalog,
        \Dotdigitalgroup\Email\Helper\Recommended $recommended,
        array $data = []
    ) {
        $this->catalog           = $catalog;
        $this->helper                   = $helper;
        $this->recommnededHelper        = $recommended;
        $this->priceHelper              = $priceHelper;

        parent::__construct($context, $data);
    }

    /**
     * Get product collection.
     *
     * @return array
     */
    public function getLoadedProductCollection()
    {
        $params = $this->getRequest()->getParams();
        if (! isset($params['code']) || ! $this->helper->isCodeValid($params['code'])) {
            $this->helper->log('Most viewed no valid code is set');
            return [];
        }

        $productsToDisplay = [];
        $mode = $this->getRequest()->getActionName();
        $limit = $this->recommnededHelper->getDisplayLimitByMode($mode);
        $from  = $this->recommnededHelper->getTimeFromConfig($mode);
        $to = $this->_localeDate->date()->format(\Zend_Date::ISO_8601);
        $catId = $this->getRequest()->getParam('category_id');
        $catName = $this->getRequest()->getParam('category_name');

        $reportProductCollection = $this->catalog->getMostViewedProductCollection($from, $to, $limit, $catId, $catName);

        //product ids from the report product collection
        $productIds = $reportProductCollection->getColumnValues('entity_id');

        $productCollection = $this->catalog->getProductCollectionFromIds($productIds);

        //product collection
        foreach ($productCollection as $_product) {
            //add only saleable products
            if ($_product->isSalable()) {
                $productsToDisplay[] = $_product;
            }
        }

        return $productsToDisplay;
    }

    /**
     * Display mode type.
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
     * @return string|boolean
     */
    public function getTextForUrl($store)
    {
        $store = $this->_storeManager->getStore($store);

        return $store->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_LINK_TEXT
        );
    }
}
