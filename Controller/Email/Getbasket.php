<?php

namespace Dotdigitalgroup\Email\Controller\Email;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Frontend\PwaUrlConfig;
use Magento\Store\Model\StoreManagerInterface;

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
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var PwaUrlConfig
     */
    private $pwaUrlConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var array
     */
    private $params = [];

    /**
     * Getbasket constructor.
     *
     * @param \Magento\Quote\Model\ResourceModel\Quote $quoteResource
     * @param \Magento\Checkout\Model\SessionFactory $checkoutSessionFactory
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\SessionFactory $customerSessionFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param PwaUrlConfig $pwaUrlConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Quote\Model\ResourceModel\Quote $quoteResource,
        \Magento\Checkout\Model\SessionFactory $checkoutSessionFactory,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\SessionFactory $customerSessionFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        PwaUrlConfig $pwaUrlConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->checkoutSession = $checkoutSessionFactory;
        $this->quoteFactory    = $quoteFactory;
        $this->quoteResource = $quoteResource;
        $this->customerSessionFactory = $customerSessionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->pwaUrlConfig = $pwaUrlConfig;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * Handles redirection from e.g. /connector/email/getbasket/quote_id/1/
     * to a re-populated basket for customers,
     * and a generic basket path for guests and PWA carts.
     */
    public function execute()
    {
        $websiteId = $this->storeManager->getWebsite()->getId();
        $pwaUrl = $this->pwaUrlConfig->getPwaUrl($websiteId);

        if ($pwaUrl) {
            return $this->handlePwaBasket($pwaUrl, $websiteId);
        }

        $quoteId = $this->getRequest()->getParam('quote_id');
        //no quote id redirect to base url
        if (!$quoteId) {
            return $this->_redirect($this->getRedirectWithParams(''));
        }

        /** @var \Magento\Quote\Model\Quote $quoteModel */
        $quoteModel = $this->quoteFactory->create();

        $this->quoteResource->load($quoteModel, $quoteId);

        //no quote id redirect to base url
        if (! $quoteModel->getId()) {
            return $this->_redirect($this->getRedirectWithParams(''));
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
        $configCartUrl = $this->quote->getStore()
            ->getWebsite()
            ->getConfig(Config::XML_PATH_CONNECTOR_CONTENT_CART_URL);

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

            $this->_redirect(
                $this->getRedirectWithParams($url)
            );
        } else {
            if ($configCartUrl) {
                $cartUrl = $configCartUrl;
            } else {
                $cartUrl = 'checkout/cart';
            }
            //set before auth url. customer will be redirected to cart after successful login
            $customerSession->setBeforeAuthUrl(
                $this->getRedirectWithParams($this->quote->getStore()->getUrl($cartUrl))
            );

            //send customer to login page
            $configLoginUrl = $this->quote->getStore()
                ->getWebsite()
                ->getConfig(Config::XML_PATH_CONNECTOR_CONTENT_LOGIN_URL);

            if ($configLoginUrl) {
                $loginUrl = $configLoginUrl;
            } else {
                $loginUrl = 'customer/account/login';
            }

            $this->_redirect(
                $this->getRedirectWithParams($this->quote->getStore()->getUrl($loginUrl))
            );
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
        $configCartUrl = $this->quote->getStore()
            ->getWebsite()
            ->getConfig(Config::XML_PATH_CONNECTOR_CONTENT_CART_URL);

        if ($configCartUrl) {
            $url = $configCartUrl;
        } else {
            $url = 'checkout/cart';
        }

        $this->_redirect(
            $this->getRedirectWithParams($this->quote->getStore()->getUrl($url))
        );
    }

    /**
     * Get the URL to redirect, maintaining any query string parameters passed
     *
     * @param string $path
     * @return string
     */
    private function getRedirectWithParams(string $path)
    {
        // params already processed, proceed
        if (!empty($this->params)) {
            return $path;
        }

        // get any params without quote_id
        $params = array_diff_key($this->getRequest()->getParams(), ['quote_id' => null]);
        if (empty($params)) {
            return $path;
        }

        $this->params = $params;

        // dm_i params are exceptional because they cannot be altered in the process of encoding
        $dm_i = null;
        if (isset($params['dm_i'])) {
            $dm_i = $params['dm_i'];
            unset($params['dm_i']);
        }

        $redirectWithParams = sprintf(
            '%s%s%s',
            $path,
            strpos($path, '?') !== false ? '&' : '?',
            http_build_query($params, null, "&", PHP_QUERY_RFC3986)
        );

        if ($dm_i) {
            return $redirectWithParams .
                ($params ? '&' : '') .
                'dm_i=' . $dm_i;
        }

        return $redirectWithParams;
    }

    /**
     * @param string $pwaUrl
     * @param int $websiteId
     * @return \Magento\Framework\App\ResponseInterface
     */
    private function handlePwaBasket($pwaUrl, $websiteId)
    {
        $cartRoute = $this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_CONTENT_CART_URL,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );

        return $this->_redirect(
            $this->getRedirectWithParams($pwaUrl . $cartRoute)
        );
    }
}
