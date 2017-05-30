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
     * @var \Dotdigitalgroup\Email\Helper\Recommended
     */
    public $recommnededHelper;
    /**
     * @var \Magento\Customer\Model\SessionFactory
     */
    public $sessionFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\CatalogFactory
     */
    public $catalogFactory;

    /**
     * Recentlyviewed constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\CatalogFactory $catalogFactory
     * @param \Magento\Customer\Model\SessionFactory $sessionFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param \Dotdigitalgroup\Email\Helper\Recommended $recommended
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param array $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\CatalogFactory $catalogFactory,
        \Magento\Customer\Model\SessionFactory $sessionFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Dotdigitalgroup\Email\Helper\Recommended $recommended,
        \Magento\Catalog\Block\Product\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->sessionFactory    = $sessionFactory;
        $this->helper            = $helper;
        $this->recommnededHelper = $recommended;
        $this->priceHelper       = $priceHelper;
        $this->storeManager      = $this->_storeManager;
        $this->catalogFactory    = $catalogFactory;
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
        $catalogFactory = $this->catalogFactory->create();

        //login customer to receive the recent products
        $session = $this->sessionFactory->create();
        $isLoggedIn = $session->loginById($customerId);

        $productIds = $catalogFactory->getRecentlyViewed($limit);

        //get product collection to check for salable
        $productCollection = $catalogFactory->getProductCollectionFromIds($productIds);

        //show products only if is salable
        foreach ($productCollection as $product) {
            if ($product->isSalable()) {
                $productsToDisplay[$product->getId()] = $product;
            }
        }
        $this->helper->log(
            'Recentlyviewed customer  : ' . $customerId . ', mode ' . $mode
            . ', limit : ' . $limit .
            ', items found : ' . count($productIds) . ', is customer logged in : '
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
