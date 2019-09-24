<?php

namespace Dotdigitalgroup\Email\Block\Recommended;

/**
 * Recently viewed block
 *
 * @api
 */
class Recentlyviewed extends \Dotdigitalgroup\Email\Block\Recommended
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
    public $recommendedHelper;

    /**
     * @var \Magento\Customer\Model\SessionFactory
     */
    public $sessionFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Catalog
     */
    public $catalog;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * Recentlyviewed constructor.
     *
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Dotdigitalgroup\Email\Block\Helper\Font $font
     * @param \Dotdigitalgroup\Email\Model\Catalog\UrlFinder $urlFinder
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Catalog $catalog
     * @param \Magento\Customer\Model\SessionFactory $sessionFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param \Dotdigitalgroup\Email\Helper\Recommended $recommended
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Dotdigitalgroup\Email\Block\Helper\Font $font,
        \Dotdigitalgroup\Email\Model\Catalog\UrlFinder $urlFinder,
        \Dotdigitalgroup\Email\Model\ResourceModel\Catalog $catalog,
        \Magento\Customer\Model\SessionFactory $sessionFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Dotdigitalgroup\Email\Helper\Recommended $recommended,
        array $data = []
    ) {
        $this->sessionFactory    = $sessionFactory;
        $this->helper            = $helper;
        $this->recommendedHelper = $recommended;
        $this->priceHelper       = $priceHelper;
        $this->storeManager      = $this->_storeManager;
        $this->catalog           = $catalog;

        parent::__construct($context, $font, $urlFinder, $data);
    }

    /**
     * Products collection.
     *
     * @return array
     */
    public function getLoadedProductCollection()
    {
        $params = $this->getRequest()->getParams();
        //check for param code and id
        if (! isset($params['customer_id']) ||
            ! isset($params['code']) ||
            ! $this->helper->isCodeValid($params['code'])
        ) {
            $this->helper->log('Recently viewed no id or valid code is set');
            return [];
        }

        $productsToDisplay = [];
        $mode = $this->getRequest()->getActionName();
        $customerId = (int) $this->getRequest()->getParam('customer_id');
        $limit = (int) $this->recommendedHelper->getDisplayLimitByMode($mode);

        //login customer to receive the recent products
        $session = $this->sessionFactory->create();
        $isLoggedIn = $session->loginById($customerId);
        $productIds = $this->catalog->getRecentlyViewed($customerId, $limit);

        //get product collection to check for salable
        $productCollection = $this->catalog->getProductCollectionFromIds($productIds);

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
     * @return string|boolean
     */
    public function getMode()
    {
        return $this->recommendedHelper->getDisplayType();
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
