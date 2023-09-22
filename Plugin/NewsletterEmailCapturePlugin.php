<?php

namespace Dotdigitalgroup\Email\Plugin;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\ResourceModel\Quote;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Webapi\Rest\Request;
use Dotdigitalgroup\Email\Logger\Logger;

class NewsletterEmailCapturePlugin
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface $storeManager
     */
    private $storeManager;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var Quote
     */
    private $quoteResource;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param Session $session
     * @param Quote $quoteResource
     * @param Request $request
     * @param Logger $logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        Session $session,
        Quote $quoteResource,
        Request $request,
        Logger $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->checkoutSession = $session;
        $this->quoteResource = $quoteResource;
        $this->request = $request;
        $this->logger = $logger;
    }

    /**
     * After execute.
     *
     * @param \Magento\Newsletter\Controller\Subscriber\NewAction $subject
     * @param Redirect $result
     * @return Redirect|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterExecute(
        \Magento\Newsletter\Controller\Subscriber\NewAction $subject,
        $result
    ) {
        if ($this->isEasyEmailCaptureForNewsletterEnabled()) {
            if ($quote = $this->checkoutSession->getQuote()) {
                if ($quote->hasItems() && !$quote->getCustomerEmail()) {
                    try {
                        $email = (string) $this->request->getPost('email');
                        $quote->setCustomerEmail($email);
                        $this->quoteResource->save($quote);
                    } catch (\Exception $e) {
                        $this->logger->debug((string)$e);
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Is email capture for newsletter enabled.
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function isEasyEmailCaptureForNewsletterEnabled()
    {
        return $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_EMAIL_CAPTURE_NEWSLETTER,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $this->storeManager->getWebsite()->getId()
        );
    }
}
