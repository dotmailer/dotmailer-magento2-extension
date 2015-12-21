<?php

namespace Dotdigitalgroup\Email\Controller\Email;

class Getbasket extends \Magento\Framework\App\Action\Action
{

	protected $_quoteFactory;
	protected $_sessionFactory;
	protected $_checkoutSession;
	//current quote
	protected $_quote;

	public function __construct(
		\Magento\Checkout\Model\SessionFactory $checkoutSessionFactory,
		\Magento\Quote\Model\QuoteFactory $quoteFactory,
		\Magento\Customer\Model\SessionFactory $sessionFactory,
		\Magento\Framework\App\Action\Context $context
	)
	{
		$this->_checkoutSession = $checkoutSessionFactory;
		$this->_sessionFactory = $sessionFactory;
		$this->_quoteFactory = $quoteFactory;
		parent::__construct($context);
	}


	/**
	 * Wishlist page to display the user items with specific email.
	 */
	public function execute()
	{

		$quoteId = $this->getRequest()->getParam('quote_id');
		//no quote id redirect to base url
		if (!$quoteId)
			return $this->_redirect('');

		$quoteModel = $this->_quoteFactory->create()->load($quoteId);

		//no quote id redirect to base url
		if (!$quoteModel->getId())
			return $this->_redirect('');

		//set quoteModel to _quote property for later use
		$this->_quote = $quoteModel;

		if ($quoteModel->getCustomerId())
			$this->_handleCustomerBasket();
		else
			$this->_handleGuestBasket();

	}


	/**
	 * process customer basket
	 */
	protected function _handleCustomerBasket()
	{
		$customerSession = $this->_sessionFactory->create();
		$configCartUrl = $this->_quote->getStore()->getWebsite()->getConfig(
			\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CONTENT_CART_URL
		);


		//if customer is logged in then redirect to cart
		if ($customerSession->isLoggedIn()){
			$checkoutSession = $this->_checkoutSession->create();
			if ($checkoutSession->getQuote() && $checkoutSession->getQuote()->hasItems()) {
				$quote = $checkoutSession->getQuote();
				if ($this->_quote->getId() != $quote->getId())
					$this->_checkMissingAndAdd();
			} else {
				$this->_loadAndReplace();
			}

			if ($configCartUrl)
				$url = $configCartUrl;
			else
				$url = $customerSession->getCustomer()->getStore()->getUrl('checkout/cart');

			$this->_redirect($url);
		} else {
			//set after auth url. customer will be redirected to cart after successful login
			if($configCartUrl)
				$cartUrl = $configCartUrl;
			else
				$cartUrl = 'checkout/cart';
			$customerSession->setAfterAuthUrl($this->_quote->getStore()->getUrl($cartUrl));

			//send customer to login page
			$configLoginUrl = $this->_quote->getStore()->getWebsite()->getConfig(
				\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CONTENT_LOGIN_URL
			);
			if ($configLoginUrl)
				$loginUrl = $configLoginUrl;
			else
				$loginUrl = 'customer/account/login';
			$this->_redirect($this->_quote->getStore()->getUrl($loginUrl));
		}
	}

	/**
	 * check missing items from current quote and add
	 */
	protected function _checkMissingAndAdd()
	{
		$checkoutSession = $this->_checkoutSession->create();
		$currentQuote = $checkoutSession->getQuote();

		if ($currentQuote->hasItems()) {
			$currentSessionItems = $currentQuote->getAllItems();
			$currentItemIds = array();

			foreach ($currentSessionItems as $currentSessionItem) {
				$currentItemIds[] = $currentSessionItem->getId();
			}
			foreach ($this->_quote->getAllItems() as $item) {
				if (!in_array($item->getId(), $currentItemIds)) {
					$currentQuote->addItem($item);
				}
			}
			$currentQuote->collectTotals()->save();
		} else {
			$this->_loadAndReplace();
		}
	}

	/**
	 * load quote and replace in session#1114
	 */
	protected function _loadAndReplace()
	{
		$checkoutSession = $this->_checkoutSession->create();
		$quote = $this->_quoteFactory->create()
			->load($this->_quote->getId());
		$quote->setIsActive(true)->save();
		$checkoutSession->replaceQuote($quote);
	}

	/**
	 * process guest basket
	 */
	protected function _handleGuestBasket()
	{
		$checkoutSession = $this->_checkoutSession->create();

		if ($checkoutSession->getQuote() && $checkoutSession->getQuote()->hasItems()) {
			$this->_checkMissingAndAdd();
		} else {
			$this->_loadAndReplace();
		}

		$configCartUrl = $this->_quote->getStore()->getWebsite()->getConfig(
			\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CONTENT_CART_URL
		);

		if ($configCartUrl)
			$url = $configCartUrl;
		else
			$url = 'checkout/cart';
		$this->_redirect($this->_quote->getStore()->getUrl($url));
	}

}