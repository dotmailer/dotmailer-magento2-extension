<?php

namespace Dotdigitalgroup\Email\Controller\Ajax;

class Emailcapture extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    public $checkoutSession;

    /**
     * Emailcapture constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Checkout\Model\Session $session
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Checkout\Model\Session $session,
        \Magento\Framework\App\Action\Context $context
    ) {
        $this->helper          = $data;
        $this->checkoutSession = $session;
        parent::__construct($context);
    }

    /**
     * Easy email capture for Newsletter and Checkout.
     */
    public function execute()
    {
        if ($this->getRequest()->getParam('email') && $quote = $this->checkoutSession->getQuote()) {
            $email = $this->getRequest()->getParam('email');
            if ($quote->hasItems()) {
                try {
                    $quote->setCustomerEmail($email);

                    $quote->getResource()->save($quote);

                    $this->helper->log('ajax emailCapture email: ' . $email);
                } catch (\Exception $e) {
                    $this->helper->debug((string)$e, []);
                    $this->helper->log('ajax emailCapture fail for email: ' . $email);
                }
            }
        }
    }
}
