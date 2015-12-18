<?php

namespace Dotdigitalgroup\Email\Block\Recommended;

class Push extends \Magento\Catalog\Block\Product\AbstractProduct
{
	public $helper;
	public $priceHelper;
	public $scopeManager;
	public $recommnededHelper;

	protected $_localeDate;
	protected $_productFactory;


	public function __construct(
		\Magento\Catalog\Model\ProductFactory $productFactory,
		\Dotdigitalgroup\Email\Helper\Data $helper,
		\Magento\Framework\Pricing\Helper\Data $priceHelper,
		\Dotdigitalgroup\Email\Helper\Recommended $recommended,
        \Magento\Catalog\Block\Product\Context $context,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
		\Magento\Framework\ObjectManagerInterface $objectManagerInterface,
		array $data = []
	)
	{
		parent::__construct( $context, $data );
		$this->helper = $helper;
		$this->_productFactory = $productFactory;
		$this->recommnededHelper = $recommended;
		$this->priceHelper = $priceHelper;
		$this->_localeDate = $localeDate;
		$this->scopeManager = $scopeConfig;
		$this->storeManager = $this->_storeManager;
		$this->objectManager = $objectManagerInterface;
	}
    /**
     * get the products to display for table
     */
    public function getLoadedProductCollection()
    {
        $mode  = $this->getRequest()->getActionName();
        $limit = $this->recommnededHelper->getDisplayLimitByMode($mode);
        $productIds = $this->recommnededHelper->getProductPushIds();

        $productCollection = $this->_productFactory->create()->getCollection()
			->addAttributeToSelect('*')
            ->addAttributeToFilter('entity_id', array('in' => $productIds))
        ;
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
	 * Price html.
	 * @param $product
	 *
	 * @return string
	 */
	public function getPriceHtml($product)
    {
        $this->setTemplate('connector/product/price.phtml');
        $this->setProduct($product);
        return $this->toHtml();
    }

	public function getTextForUrl($store)
	{
		$store = $this->_storeManager->getStore($store);
		return $store->getConfig(
				\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_LINK_TEXT
		);
	}
}