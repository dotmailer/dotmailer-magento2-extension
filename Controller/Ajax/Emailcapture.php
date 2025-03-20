<?php

namespace Dotdigitalgroup\Email\Controller\Ajax;

use Dotdigitalgroup\Email\Helper\Data;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Quote\Model\ResourceModel\Quote;

class Emailcapture implements HttpPostActionInterface
{
    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote
     */
    private $quoteResource;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * Emailcapture constructor.
     *
     * @param Data $data
     * @param Quote $quoteResource
     * @param Session $session
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Quote\Model\ResourceModel\Quote $quoteResource,
        \Magento\Checkout\Model\Session $session,
        Context $context,
        JsonFactory $resultJsonFactory
    ) {
        $this->helper = $data;
        $this->quoteResource = $quoteResource;
        $this->checkoutSession = $session;
        $this->request = $context->getRequest();
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * Easy email capture for Newsletter and Checkout.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $email = $this->request->getParam('email');
        $resultJson = $this->resultJsonFactory->create();

        $responseCode = '400';

        if ($email && $quote = $this->checkoutSession->getQuote()) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $resultJson->setHttpResponseCode($responseCode);
                return $resultJson;
            }

            $responseCode = '200';

            if ($quote->hasItems() && $quote->getCustomerEmail() !== $email) {
                try {
                    $quote->setCustomerEmail($email);
                    $this->quoteResource->save($quote);
                } catch (\Exception $e) {
                    $this->helper->debug((string)$e, []);
                }
            }
        }

        $resultJson->setHttpResponseCode($responseCode);
        return $resultJson;
    }
}
