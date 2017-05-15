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
            $email = filter_var($email, FILTER_SANITIZE_EMAIL);

            //regular expressions from http://regexlib.com.
            // Match formats joe@aol.com | joe@wrox.co.uk | joe@domain.info
            if (! preg_match('/^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/', $email)) {
                return null;
            }

            if ($quote->hasItems()) {
                try {
                    $quote->setCustomerEmail($email);
                    $quote->getResource()->save($quote);
                } catch (\Exception $e) {
                    $this->helper->debug((string)$e, []);
                }
            }
        }
    }
}
