<?php

namespace Dotdigitalgroup\Email\Block\Recommended;

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
    public $catalogFactory;

    /**
     * Mostviewed constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data                             $helper
     * @param \Magento\Catalog\Block\Product\Context                         $context
     * @param \Magento\Framework\Pricing\Helper\Data                         $priceHelper
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\CatalogFactory      $catalogFactory
     * @param \Dotdigitalgroup\Email\Helper\Recommended                      $recommended
     * @param array                                                          $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Dotdigitalgroup\Email\Model\ResourceModel\CatalogFactory $catalogFactory,
        \Dotdigitalgroup\Email\Helper\Recommended $recommended,
        array $data = []
    ) {
        $this->catalogFactory           = $catalogFactory;
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
        $productsToDisplay = [];
        $mode = $this->getRequest()->getActionName();
        $limit = $this->recommnededHelper->getDisplayLimitByMode($mode);
        $from  = $this->recommnededHelper->getTimeFromConfig($mode);
        $to = $this->_localeDate->date()->format(\Zend_Date::ISO_8601);
        $catId = $this->getRequest()->getParam('category_id');
        $catName = $this->getRequest()->getParam('category_name');

        $reportProductCollection = $this->catalogFactory->create()
            ->getMostViewedProductCollection($from, $to, $limit, $catId, $catName);

        //product ids from the report product collection
        $productIds = $reportProductCollection->getColumnValues('entity_id');

        $productCollection = $this->catalogFactory->create()
            ->getProductCollectionFromIds($productIds);

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
     * @return mixed|string
     */
    public function getMode()
    {
        return $this->recommnededHelper->getDisplayType();
    }

    /**
     * @param $store
     *
     * @return mixed
     */
    public function getTextForUrl($store)
    {
        $store = $this->_storeManager->getStore($store);

        return $store->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_LINK_TEXT
        );
    }
}
