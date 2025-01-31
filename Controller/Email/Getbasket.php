<?php

namespace Dotdigitalgroup\Email\Controller\Email;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Frontend\PwaUrlConfig;
use Magento\Checkout\Model\Session;
use Magento\Customer\Model\SessionFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Getbasket implements HttpGetActionInterface
{
    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote
     */
    private $quoteResource;

    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var SessionFactory
     */
    private $checkoutSessionFactory;

    /**
     * @var RedirectFactory
     */
    private $redirectFactory;

    /**
     * @var Quote
     */
    private $quote;

    /**
     * @var SessionFactory
     */
    private $customerSessionFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var PwaUrlConfig
     */
    private $pwaUrlConfig;

    /**
     * @var RequestInterface
     */
    private $request;

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
     * @param RedirectFactory $redirectFactory
     * @param QuoteFactory $quoteFactory
     * @param Context $context
     * @param SessionFactory $customerSessionFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param PwaUrlConfig $pwaUrlConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Quote\Model\ResourceModel\Quote $quoteResource,
        \Magento\Checkout\Model\SessionFactory $checkoutSessionFactory,
        RedirectFactory $redirectFactory,
        QuoteFactory $quoteFactory,
        Context $context,
        SessionFactory $customerSessionFactory,
        ScopeConfigInterface $scopeConfig,
        PwaUrlConfig $pwaUrlConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->checkoutSessionFactory = $checkoutSessionFactory;
        $this->quoteFactory = $quoteFactory;
        $this->quoteResource = $quoteResource;
        $this->redirectFactory = $redirectFactory;
        $this->customerSessionFactory = $customerSessionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->pwaUrlConfig = $pwaUrlConfig;
        $this->request = $context->getRequest();
        $this->storeManager = $storeManager;
    }

    /**
     * Handles redirection from e.g. /connector/email/getbasket/quote_id/1/
     * to a re-populated basket for customers,
     * and a generic basket path for guests and PWA carts.
     *
     * @return Redirect
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function execute()
    {
        $websiteId = $this->storeManager->getWebsite()->getId();
        $pwaUrl = $this->pwaUrlConfig->getPwaUrl($websiteId);

        if ($pwaUrl) {
            return $this->handlePwaBasket($pwaUrl, $websiteId);
        }

        $quoteId = $this->request->getParam('quote_id');
        //no quote id redirect to base url
        if (!$quoteId) {
            return $this->redirectFactory->create()
                ->setPath($this->getRedirectWithParams(''));
        }

        /** @var Quote $quoteModel */
        $quoteModel = $this->quoteFactory->create();

        $this->quoteResource->load($quoteModel, $quoteId);

        if (!$quoteModel->getId() || !$quoteModel->getCustomerEmail()) {
            return $this->redirectFactory->create()
                ->setPath($this->getRedirectWithParams(''));
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
     * @return Redirect
     * @throws NoSuchEntityException
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
            $checkoutSession = $this->checkoutSessionFactory->create();
            if ($checkoutSession->getQuote()
                && $checkoutSession->getQuote()->hasItems()
            ) {
                $quote = $checkoutSession->getQuote();
                if ($this->quote->getId() != $quote->getId()) {
                    $this->checkMissingAndAdd();
                }
            }

            return $this->redirectFactory->create()
                ->setPath($configCartUrl);
        } else {
            //set before auth url. customer will be redirected to cart after successful login
            $customerSession->setBeforeAuthUrl(
                $this->getRedirectWithParams($this->quote->getStore()->getUrl($configCartUrl))
            );

            //send customer to login page
            $configLoginUrl = $this->quote->getStore()
                ->getWebsite()
                ->getConfig(Config::XML_PATH_CONNECTOR_CONTENT_LOGIN_URL);

            return $this->redirectFactory->create()
                ->setPath($this->quote->getStore()->getUrl($configLoginUrl));
        }
    }

    /**
     * Check missing items from current quote and add.
     */
    private function checkMissingAndAdd()
    {
        /** @var Session $checkoutSession */
        $checkoutSession = $this->checkoutSessionFactory->create();
        $currentQuote = $checkoutSession->getQuote();

        if ($currentQuote->hasItems()) {
            foreach ($this->quote->getAllVisibleItems() as $item) {
                $found = false;

                foreach ($currentQuote->getAllItems() as $quoteItem) {
                    if ($quoteItem->compare($item)) {
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $newItem = clone $item;
                    $currentQuote->addItem($newItem);
                    if ($item->getHasChildren()) {
                        foreach ($item->getChildren() as $child) {
                            $newChild = clone $child;
                            $newChild->setParentItem($newItem);
                            $currentQuote->addItem($newChild);
                        }
                    }
                }
            }

            $currentQuote->collectTotals();

            $this->quoteResource->save($currentQuote);
        }
    }

    /**
     * Process guest basket.
     *
     * @return Redirect
     */
    private function handleGuestBasket()
    {
        /** @var Session $checkoutSession */
        $checkoutSession = $this->checkoutSessionFactory->create();
        if (!$checkoutSession->getQuoteId()) {
            $checkoutSession->setQuoteId($this->quote->getId());
        } else {
            $this->checkMissingAndAdd();
        }

        $configCartUrl = $this->quote->getStore()
            ->getWebsite()
            ->getConfig(Config::XML_PATH_CONNECTOR_CONTENT_CART_URL);

        return $this->redirectFactory->create()
            ->setPath($this->quote->getStore()->getUrl($configCartUrl));
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
        $params = array_diff_key($this->request->getParams(), ['quote_id' => null]);
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
            http_build_query($params, "", "&", PHP_QUERY_RFC3986)
        );

        if ($dm_i) {
            return $redirectWithParams .
                ($params ? '&' : '') .
                'dm_i=' . $dm_i;
        }

        return $redirectWithParams;
    }

    /**
     * Use the PWA URL to redirect to a basket.
     *
     * @param string $pwaUrl
     * @param int $websiteId
     * @return Redirect
     */
    private function handlePwaBasket($pwaUrl, $websiteId)
    {
        $cartRoute = $this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_CONTENT_CART_URL,
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );

        return $this->redirectFactory->create()
            ->setPath($pwaUrl . $cartRoute);
    }
}
