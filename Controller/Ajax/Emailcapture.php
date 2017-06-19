<?php

namespace Dotdigitalgroup\Email\Controller\Ajax;

class Emailcapture extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;
    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;

    /**
     * Emailcapture constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Checkout\Model\Session $session
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Escaper $escaper
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Checkout\Model\Session $session,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Escaper $escaper
    ) {
        $this->helper          = $data;
        $this->checkoutSession = $session;
        $this->escaper         = $escaper;
        parent::__construct($context);
    }

    /**
     * Easy email capture for Newsletter and Checkout.
     */
    public function execute()
    {
        $email = $this->escaper->escapeHtml($this->getRequest()->getParam('email'));
        if ($email && $quote = $this->checkoutSession->getQuote()) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
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
