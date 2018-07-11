<?php

namespace Dotdigitalgroup\Email\Block\Recommended;

/**
 * Product block
 *
 * @api
 */
class Product extends \Dotdigitalgroup\Email\Block\Recommended\Quoteproducts
{
    /**
     * @var \Magento\Sales\Api\Data\OrderInterfaceFactory
     */
    public $orderFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\Apiconnector\ClientFactory
     */
    public $clientFactory;

    /**
     * @var \Magento\Sales\Model\Spi\OrderResourceInterface
     */
    private $orderResource;

    /**
     * Product constructor.
     *
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Catalog $catalog
     * @param \Dotdigitalgroup\Email\Helper\Recommended $recommendedHelper
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param \Magento\Sales\Api\Data\OrderInterfaceFactory $orderFactory
     * @param \Dotdigitalgroup\Email\Model\Apiconnector\ClientFactory $clientFactory
     * @param \Magento\Sales\Model\Spi\OrderResourceInterface $orderResource
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Model\ResourceModel\Catalog $catalog,
        \Dotdigitalgroup\Email\Helper\Recommended $recommendedHelper,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Magento\Sales\Api\Data\OrderInterfaceFactory $orderFactory,
        \Dotdigitalgroup\Email\Model\Apiconnector\ClientFactory $clientFactory,
        \Magento\Sales\Model\Spi\OrderResourceInterface $orderResource,
        array $data = []
    ) {
        parent::__construct($context, $helper, $catalog, $recommendedHelper, $priceHelper, $data);
        $this->orderFactory  = $orderFactory;
        $this->clientFactory = $clientFactory;
        $this->orderResource = $orderResource;
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
        $orderItems = $orderModel->getAllItems();
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
        $productsToDisplay = $this->getProductsToDisplay(
            $orderItems,
            $mode,
            $productsToDisplayCounter,
            $limit,
            $maxPerChild
        );

        //check for more space to fill up the table with fallback products
        if ($productsToDisplayCounter < $limit) {
            $productsToDisplay = $this->fillProductsToDisplay($productsToDisplay, $productsToDisplayCounter, $limit);
        }

        $this->helper->log('loaded product to display ' . count($productsToDisplay));

        return $productsToDisplay;
    }
}
