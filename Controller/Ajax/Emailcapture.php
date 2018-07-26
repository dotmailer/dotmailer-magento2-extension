<?php

namespace Dotdigitalgroup\Email\Controller\Ajax;

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
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Quote\Model\ResourceModel\Quote $quoteResource,
        \Magento\Checkout\Model\Session $session,
        \Magento\Framework\App\Action\Context $context
    ) {
        $this->helper          = $data;
        $this->quoteResource = $quoteResource;
        $this->checkoutSession = $session;
        parent::__construct($context);
    }

    /**
     * Easy email capture for Newsletter and Checkout.
     *
     * @return null
     */
    public function execute()
    {
        $email = $this->getRequest()->getParam('email');
        if ($email && $quote = $this->checkoutSession->getQuote()) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return null;
            }

            if ($quote->hasItems()) {
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
