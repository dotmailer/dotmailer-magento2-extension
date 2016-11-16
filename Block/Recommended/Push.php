<?php

namespace Dotdigitalgroup\Email\Block\Recommended;

class Push extends \Magento\Catalog\Block\Product\AbstractProduct
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
     * @var
     */
    public $scopeManager;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    public $productFactory;

    /**
     * Push constructor.
     *
     * @param \Magento\Catalog\Model\ProductFactory     $productFactory
     * @param \Dotdigitalgroup\Email\Helper\Data        $helper
     * @param \Magento\Framework\Pricing\Helper\Data    $priceHelper
     * @param \Dotdigitalgroup\Email\Helper\Recommended $recommended
     * @param \Magento\Catalog\Block\Product\Context    $context
     * @param array                                     $data
     */
    public function __construct(
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Dotdigitalgroup\Email\Helper\Recommended $recommended,
        \Magento\Catalog\Block\Product\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper            = $helper;
        $this->productFactory    = $productFactory;
        $this->recommnededHelper = $recommended;
        $this->priceHelper       = $priceHelper;
    }

    /**
     * Get the products to display for table.
     *
     * @return $this
     */
    public function getLoadedProductCollection()
    {
        $mode = $this->getRequest()->getActionName();
        $limit = $this->recommnededHelper->getDisplayLimitByMode($mode);
        $productIds = $this->recommnededHelper->getProductPushIds();

        $productCollection = $this->productFactory->create()->getCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('entity_id', ['in' => $productIds]);
        $productCollection->getSelect()->limit($limit);

        //important check the salable product in template
        return $productCollection;
    }

    /**
     * Display  type mode.
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
        /** @var \Magento\Store\Model\Store $store */
        $store = $this->_storeManager->getStore($store);

        return $store->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_LINK_TEXT
        );
    }
}
