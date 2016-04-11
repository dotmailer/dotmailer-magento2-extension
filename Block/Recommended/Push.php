<?php

namespace Dotdigitalgroup\Email\Block\Recommended;

class Push extends \Magento\Catalog\Block\Product\AbstractProduct
{

    public $helper;
    public $priceHelper;
    public $recommnededHelper;
    public $scopeManager;
    protected $_productFactory;


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
        //\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Dotdigitalgroup\Email\Helper\Recommended $recommended,
        \Magento\Catalog\Block\Product\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper            = $helper;
        $this->_productFactory   = $productFactory;
        $this->recommnededHelper = $recommended;
        $this->priceHelper       = $priceHelper;
        //$this->scopeManager = $scopeConfig;
        $this->storeManager      = $this->_storeManager;
    }

    /**
     * get the products to display for table
     */
    public function getLoadedProductCollection()
    {
        $mode       = $this->getRequest()->getActionName();
        $limit      = $this->recommnededHelper->getDisplayLimitByMode($mode);
        $productIds = $this->recommnededHelper->getProductPushIds();

        $productCollection = $this->_productFactory->create()->getCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('entity_id', array('in' => $productIds));
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

    public function getTextForUrl($store)
    {
        $store = $this->_storeManager->getStore($store);

        return $store->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_LINK_TEXT
        );
    }
}