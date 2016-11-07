<?php

namespace Dotdigitalgroup\Email\Block\Recommended;

class Recentlyviewed extends \Magento\Catalog\Block\Product\AbstractProduct
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
     * @var \Magento\Reports\Block\Product\Viewed
     */
    public $viewed;
    /**
     * @var \Dotdigitalgroup\Email\Helper\Recommended
     */
    public $recommnededHelper;
    /**
     * @var \Magento\Customer\Model\SessionFactory
     */
    public $sessionFactory;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    public $productFactory;

    /**
     * Recentlyviewed constructor.
     *
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Customer\Model\SessionFactory $sessionFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param \Dotdigitalgroup\Email\Helper\Recommended $recommended
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Reports\Block\Product\Viewed $viewed
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Customer\Model\SessionFactory $sessionFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Dotdigitalgroup\Email\Helper\Recommended $recommended,
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Reports\Block\Product\Viewed $viewed,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->sessionFactory    = $sessionFactory;
        $this->helper            = $helper;
        $this->recommnededHelper = $recommended;
        $this->priceHelper       = $priceHelper;
        $this->storeManager      = $this->_storeManager;
        $this->productFactory    = $productFactory;
        $this->viewed            = $viewed;
    }

    /**
     * Products collection.
     *
     * @return array
     */
    public function getLoadedProductCollection()
    {
        $productsToDisplay = [];
        $mode = $this->getRequest()->getActionName();
        $customerId = $this->getRequest()->getParam('customer_id');
        $limit = $this->recommnededHelper->getDisplayLimitByMode($mode);
        //login customer to receive the recent products
        $session = $this->sessionFactory->create();
        $isLoggedIn = $session->loginById($customerId);
        $collection = $this->viewed;
        $productItems = $collection->getItemsCollection()
            ->setPageSize($limit);

        //get the product ids from items collection
        $productIds = $productItems->getColumnValues('product_id');
        //get product collection to check for salable
        $productCollection = $this->productFactory->create()->getCollection()
            ->addAttributeToSelect('*')
            ->addFieldToFilter('entity_id', ['in' => $productIds]);
        //show products only if is salable
        foreach ($productCollection as $product) {
            if ($product->isSalable()) {
                $productsToDisplay[$product->getId()] = $product;
            }
        }
        $this->helper->log(
            'Recentlyviewed customer  : ' . $customerId . ', mode ' . $mode
            . ', limit : ' . $limit .
            ', items found : ' . count($productItems) . ', is customer logged in : '
            . $isLoggedIn . ', products :' . count($productsToDisplay)
        );

        $session->logout();

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
