<?php

namespace Dotdigitalgroup\Email\Controller\Ajax;

use Magento\Framework\App\Action\Context;

class Emailcapture extends \Magento\Framework\App\Action\Action
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
     * Emailcapture constructor.
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Quote\Model\ResourceModel\Quote $quoteResource
     * @param \Magento\Checkout\Model\Session $session
     * @param Context $context
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Quote\Model\ResourceModel\Quote $quoteResource,
        \Magento\Checkout\Model\Session $session,
        Context $context
    ) {
        $this->helper = $data;
        $this->quoteResource = $quoteResource;
        $this->checkoutSession = $session;
        parent::__construct($context);
    }

    /**
     * Easy email capture for Newsletter and Checkout.
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $email = $this->getRequest()->getParam('email');

        if ($email && $quote = $this->checkoutSession->getQuote()) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return null;
            }

            if ($quote->hasItems() && !$quote->getCustomerEmail()) {
                try {
                    $quote->setCustomerEmail($email);
                    $this->quoteResource->save($quote);
                } catch (\Exception $e) {
                    $this->helper->debug((string)$e, []);
                }
            }
        }
    }
}
