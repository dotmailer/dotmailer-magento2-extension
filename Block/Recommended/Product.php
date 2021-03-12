<?php

namespace Dotdigitalgroup\Email\Block\Recommended;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Product\ImageFinder;
use Dotdigitalgroup\Email\Model\Product\ImageType\Context\DynamicContent;

/**
 * Product block
 *
 * @api
 */
class Product extends \Dotdigitalgroup\Email\Block\Recommended
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
     * @var \Magento\Sales\Model\OrderFactory
     */
    public $orderFactory;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order
     */
    private $orderResource;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Product constructor.
     *
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Dotdigitalgroup\Email\Block\Helper\Font $font
     * @param \Dotdigitalgroup\Email\Model\Catalog\UrlFinder $urlFinder
     * @param DynamicContent $imageType
     * @param ImageFinder $imageFinder
     * @param Logger $logger
     * @param \Magento\Sales\Model\ResourceModel\Order $orderResource
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Dotdigitalgroup\Email\Helper\Recommended $recommended
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Dotdigitalgroup\Email\Block\Helper\Font $font,
        \Dotdigitalgroup\Email\Model\Catalog\UrlFinder $urlFinder,
        DynamicContent $imageType,
        ImageFinder $imageFinder,
        Logger $logger,
        \Magento\Sales\Model\ResourceModel\Order $orderResource,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Dotdigitalgroup\Email\Helper\Recommended $recommended,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        array $data = []
    ) {
        $this->orderFactory      = $orderFactory;
        $this->recommendedHelper = $recommended;
        $this->helper            = $helper;
        $this->orderResource     = $orderResource;
        $this->logger = $logger;

        parent::__construct($context, $font, $urlFinder, $imageType, $imageFinder, $data);
    }

    /**
     * Get the products to display for recommendation.
     *
     * @return array
     */
    public function getLoadedProductCollection()
    {
        $params = $this->getRequest()->getParams();
        //check for param code and id
        if (! isset($params['order_id']) ||
            ! isset($params['code']) ||
            ! $this->helper->isCodeValid($params['code'])
        ) {
            $this->helper->log('Product recommendation for this order not found or invalid code');
            return [];
        }

        //products to be displayed for recommended pages
        $orderId = (int) $this->getRequest()->getParam('order_id');
        //display mode based on the action name
        $mode = $this->getRequest()->getActionName();
        $orderModel = $this->orderFactory->create();
        $this->orderResource->load($orderModel, $orderId);
        //number of product items to be displayed
        $limit = $this->recommendedHelper
            ->getDisplayLimitByMode($mode);

        try {
            $orderItems = $orderModel->getAllItems();
        } catch (\Exception $e) {
            $orderItems = [];
            $this->logger->debug(
                'Error fetching items for order ID: ' . $orderId,
                [(string) $e]
            );
        }

        $numItems = count($orderItems);

        //no product found to display
        if ($numItems == 0 || !$limit) {
            return [];
        } elseif ($numItems > $limit) {
            $maxPerChild = 1;
        } else {
            $maxPerChild = number_format($limit / $numItems);
        }

        $this->helper->log(
            'DYNAMIC PRODUCTS : limit ' . $limit . ' products : '
            . $numItems . ', max per child : ' . $maxPerChild
        );

        $productsToDisplayCounter = 0;
        $productsToDisplay = $this->recommendedHelper->getProductsToDisplay(
            $orderItems,
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

        $this->helper->log('loaded product to display ' . count($productsToDisplay));

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
}
