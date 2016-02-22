<?php

namespace Dotdigitalgroup\Email\Block;

class Product extends \Magento\Framework\View\Element\Template
{

	public $helper;
	public $priceHelper;
	public $objectManager;
	protected $_orderFactory;
	protected $_emulationFactory;
	protected $_recommendedFactory;
	protected $_recommended;

	public function __construct(
		\Dotdigitalgroup\Email\Helper\Recommended $recommended,
		\Magento\Store\Model\App\EmulationFactory $emulationFactory,
		\Magento\Sales\Model\OrderFactory $orderFactory,
		\Dotdigitalgroup\Email\Helper\Data $helper,
		\Magento\Framework\Pricing\Helper\Data $priceHelper,
		\Magento\Framework\View\Element\Template\Context $context,
		\Magento\Framework\ObjectManagerInterface $objectManagerInterface,
		array $data = []
	) {
		parent::__construct($context, $data);
		$this->_recommended      = $recommended;
		$this->_emulationFactory = $emulationFactory;
		$this->_orderFactory     = $orderFactory;
		$this->helper            = $helper;
		$this->priceHelper       = $priceHelper;
		$this->storeManager      = $this->_storeManager;
		$this->objectManager     = $objectManagerInterface;
	}

	/**
	 * get the products to display for table
	 */
	public function getRecommendedProducts()
	{
		$productsToDisplay = array();
		$orderId           = $this->getRequest()->getParam('order', false);
		$mode              = $this->getRequest()->getParam('mode', false);

		if ($orderId && $mode) {
			$orderModel = $this->_orderFactory->create()
				->load($orderId);
			if ($orderModel->getId()) {
				$storeId      = $orderModel->getStoreId();
				$appEmulation = $this->_emulationFactory->create();
				$appEmulation->startEnvironmentEmulation($storeId);
				//order products
				$recommended = $this->objectManager->create(
					'Dotdigitalgroup\Email\Model\Dynamic\Recommended'
				);
				$recommended->setOrder($orderModel);
				$recommended->setMode($mode);

				//get the order items recommendations
				$productsToDisplay = $recommended->getProducts();
			}
		}

		return $productsToDisplay;
	}


	/**
	 * Price html block.
	 *
	 * @param $product
	 *
	 * @return string
	 */
	public function getPriceHtml($product)
	{
		$this->setTemplate('connector/price.phtml');
		$this->setProduct($product);

		return $this->toHtml();
	}

	/**
	 * Display type mode.
	 *
	 * @return mixed|string
	 */
	public function getDisplayType()
	{
		return $this->_recommended->getDisplayType();
	}
}