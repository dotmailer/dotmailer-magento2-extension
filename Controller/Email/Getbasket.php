<?php

namespace Dotdigitalgroup\Email\Controller\Email;

class Getbasket extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote
     */
    private $quoteResource;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var \Magento\Customer\Model\SessionFactory
     */
    private $checkoutSession;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    private $quote;

    /**
     * @var \Magento\Customer\Model\SessionFactory
     */
    private $customerSessionFactory;

    /**
     * Getbasket constructor.
     *
     * @param \Magento\Quote\Model\ResourceModel\Quote $quoteResource
     * @param \Magento\Checkout\Model\SessionFactory $checkoutSessionFactory
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\SessionFactory $customerSessionFactory
     */
    public function __construct(
        \Magento\Quote\Model\ResourceModel\Quote $quoteResource,
        \Magento\Checkout\Model\SessionFactory $checkoutSessionFactory,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\SessionFactory $customerSessionFactory
    ) {
        $this->checkoutSession = $checkoutSessionFactory;
        $this->quoteFactory    = $quoteFactory;
        $this->quoteResource = $quoteResource;
        $this->customerSessionFactory = $customerSessionFactory;
        parent::__construct($context);
    }

    /**
     * Wishlist page to display the user items with specific email.
     *
     * @return null
     */
    public function execute()
    {
        $quoteId = $this->getRequest()->getParam('quote_id');
        //no quote id redirect to base url
        if (!$quoteId) {
            return $this->_redirect('');
        }

        /** @var \Magento\Quote\Model\Quote $quoteModel */
        $quoteModel = $this->quoteFactory->create();

        $this->quoteResource->load($quoteModel, $quoteId);

        //no quote id redirect to base url
        if (! $quoteModel->getId()) {
            return $this->_redirect('');
        }

        //set quoteModel to _quote property for later use
        $this->quote = $quoteModel;

        if ($quoteModel->getCustomerId()) {
            return $this->handleCustomerBasket();
        } else {
            return $this->handleGuestBasket();
        }
    }

    /**
     * Process customer basket.
     *
     * @return null
     */
    private function handleCustomerBasket()
    {
        /** @var \Magento\Customer\Model\Session $customerSession */
        $customerSession = $this->customerSessionFactory->create();
        $configCartUrl = $this->quote->getStore()->getWebsite()->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CONTENT_CART_URL
        );

        //if customer is logged in then redirect to cart
        if ($customerSession->isLoggedIn()
            && $customerSession->getCustomerId() == $this->quote->getCustomerId()) {
            $checkoutSession = $this->checkoutSession->create();
            if ($checkoutSession->getQuote()
                && $checkoutSession->getQuote()->hasItems()
            ) {
                $quote = $checkoutSession->getQuote();
                if ($this->quote->getId() != $quote->getId()) {
                    $this->checkMissingAndAdd();
                }
            }

            if ($configCartUrl) {
                $url = $configCartUrl;
            } else {
                $url = $this->quote->getStore()->getUrl(
                    'checkout/cart'
                );
            }

            $this->_redirect($url);
        } else {
            //set after auth url. customer will be redirected to cart after successful login
            if ($configCartUrl) {
                $cartUrl = $configCartUrl;
            } else {
                $cartUrl = 'checkout/cart';
            }
            $customerSession->setAfterAuthUrl(
                $this->quote->getStore()->getUrl($cartUrl)
            );

            //send customer to login page
            $configLoginUrl = $this->quote->getStore()->getWebsite()
                ->getConfig(
                    \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CONTENT_LOGIN_URL
                );
            if ($configLoginUrl) {
                $loginUrl = $configLoginUrl;
            } else {
                $loginUrl = 'customer/account/login';
            }
            $this->_redirect($this->quote->getStore()->getUrl($loginUrl));
        }
    }

    /**
     * Check missing items from current quote and add.
     *
     * @return null
     */
    private function checkMissingAndAdd()
    {
        /** @var \Magento\Checkout\Model\Session $checkoutSession */
        $checkoutSession = $this->checkoutSession->create();
        $currentQuote = $checkoutSession->getQuote();

        if ($currentQuote->hasItems()) {
            $currentSessionItems = $currentQuote->getAllItems();
            $currentItemIds = [];

            foreach ($currentSessionItems as $currentSessionItem) {
                $currentItemIds[] = $currentSessionItem->getId();
            }
            /** @var \Magento\Quote\Model\Quote\Item $item */
            foreach ($this->quote->getAllItems() as $item) {
                if (!in_array($item->getId(), $currentItemIds)) {
                    $currentQuote->addItem($item);
                }
            }
            $currentQuote->collectTotals();

            $this->quoteResource->save($currentQuote);
        }
    }

    /**
     * Process guest basket.
     *
     * @return null
     */
    private function handleGuestBasket()
    {
        $configCartUrl = $this->quote->getStore()->getWebsite()->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CONTENT_CART_URL
        );

        if ($configCartUrl) {
            $url = $configCartUrl;
        } else {
            $url = 'checkout/cart';
        }
        $this->_redirect($this->quote->getStore()->getUrl($url));
    }
}
