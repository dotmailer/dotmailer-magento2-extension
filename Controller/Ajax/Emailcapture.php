<?php

namespace Dotdigitalgroup\Email\Controller\Ajax;


class Emailcapture extends \Magento\Framework\App\Action\Action
{

    protected $_helper;
    protected $_checkoutSession;

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
        $this->_helper = $data;
        $this->_checkoutSession = $session;
        parent::__construct( $context );
    }

    /*
     * easy email capture for Newsletter and Checkout
     */
    public function execute()
    {
        if($this->getRequest()->getParam('email') && $quote = $this->_checkoutSession->getQuote()){
            $email = $this->getRequest()->getParam('email');
            if($quote->hasItems()){
                try {
                    $quote->setCustomerEmail($email)->save();
                    $this->_helper->log('ajax emailCapture email: '. $email);
                }catch(\Exception $e){
                    $this->_helper->debug((string)$e, array());
                    $this->_helper->log('ajax emailCapture fail for email: '. $email);
                }
            }
        }
    }
}