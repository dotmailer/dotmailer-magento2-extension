<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Sales\Order\Creditmemo;

class View extends \Magento\Payment\Block\Form\Container
{



    public function __construct(
	    \Magento\Framework\Registry $registry,
	    \Magento\Framework\View\Element\Template\Context $context,
	    \Magento\Payment\Helper\Data $paymentHelper,
	    \Magento\Payment\Model\Checks\SpecificationFactory $methodSpecificationFactory,
	    array $data = [],
	    \Magento\Framework\Module\Manager $moduleManager,
	    \Magento\Framework\ObjectManagerInterface $objectManagerInterface
    )
    {
	    $this->_registry = $registry;
        $this->_objectId    = 'creditmemo_id';
        $this->_controller  = 'sales_order_creditmemo';
        $this->_mode        = 'view';

        parent::__construct($context, $paymentHelper, $methodSpecificationFactory, $data);

        $this->_removeButton('save');
        $this->_removeButton('reset');
        $this->_removeButton('delete');

        if ($this->getCreditmemo()->canCancel()) {
            $this->_addButton('cancel', array(
                    'label'     => __('Cancel'),
                    'class'     => 'delete',
                    'onclick'   => 'setLocation(\''.$this->getCancelUrl().'\')'
                )
            );
        }

        if ($this->_isAllowedAction('emails')) {
            $this->addButton('send_notification', array(
                'label'     => __('Send Email'),
                'onclick'   => 'confirmSetLocation(\''
                . __('Are you sure you want to send Creditmemo email to customer?')
                . '\', \'' . $this->getEmailUrl() . '\')'
            ));
        }

        if ($this->getCreditmemo()->canRefund()) {
            $this->_addButton('refund', array(
                    'label'     => __('Refund'),
                    'class'     => 'save',
                    'onclick'   => 'setLocation(\''.$this->getRefundUrl().'\')'
                )
            );
        }

        if ($this->getCreditmemo()->canVoid()) {
            $this->_addButton('void', array(
                    'label'     => __('Void'),
                    'class'     => 'save',
                    'onclick'   => 'setLocation(\''.$this->getVoidUrl().'\')'
                )
            );
        }

        if ($this->getCreditmemo()->getId()) {
            $this->_addButton('print', array(
                    'label'     => __('Print'),
                    'class'     => 'save',
                    'onclick'   => 'setLocation(\''.$this->getPrintUrl().'\')'
                )
            );
        }
    }

    /**
     * Retrieve creditmemo model instance
     *
     */
    public function getCreditmemo()
    {
        return $this->_registry->registry('current_creditmemo');
    }

    /**
     * Retrieve text for header
     *
     * @return string
     */
    public function getHeaderText()
    {
        if ($this->getCreditmemo()->getEmailSent()) {
            $emailSent = __('the credit memo email was sent');
        }
        else {
            $emailSent = __('the credit memo email is not sent');
        }
        return __('Credit Memo #%1$s | %3$s | %2$s (%4$s)', $this->getCreditmemo()->getIncrementId(), $this->formatDate($this->getCreditmemo()->getCreatedAtDate(), 'medium', true), $this->getCreditmemo()->getStateName(), $emailSent);
    }

    /**
     * Retrieve back url
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl(
            '*/sales_order/view',
            array(
                'order_id'  => $this->getCreditmemo()->getOrderId(),
                'active_tab'=> 'order_creditmemos'
            ));
    }

    /**
     * Retrieve capture url
     *
     * @return string
     */
    public function getCaptureUrl()
    {
        return $this->getUrl('*/*/capture', array('creditmemo_id'=>$this->getCreditmemo()->getId()));
    }

    /**
     * Retrieve void url
     *
     * @return string
     */
    public function getVoidUrl()
    {
        return $this->getUrl('*/*/void', array('creditmemo_id'=>$this->getCreditmemo()->getId()));
    }

    /**
     * Retrieve cancel url
     *
     * @return string
     */
    public function getCancelUrl()
    {
        return $this->getUrl('*/*/cancel', array('creditmemo_id'=>$this->getCreditmemo()->getId()));
    }

    /**
     * Retrieve email url
     *
     * @return string
     */
    public function getEmailUrl()
    {
        return $this->getUrl('*/*/email', array(
            'creditmemo_id' => $this->getCreditmemo()->getId(),
            'order_id'      => $this->getCreditmemo()->getOrderId()
        ));
    }

    /**
     * Retrieve print url
     *
     * @return string
     */
    public function getPrintUrl()
    {
        return $this->getUrl('*/*/print', array(
            'creditmemo_id' => $this->getCreditmemo()->getId()
        ));
    }

	/**
	 * Update 'back' button url.
	 * @param $flag
	 *
	 * @return $this
	 */
    public function updateBackButtonUrl($flag)
    {
        if ($flag) {
            if ($this->getCreditmemo()->getBackUrl()) {
                return $this->_updateButton(
                    'back',
                    'onclick',
                    'setLocation(\'' . $this->getCreditmemo()->getBackUrl() . '\')'
                );
            }

            return $this->_updateButton(
                'back',
                'onclick',
                'setLocation(\'' . $this->getUrl('*/sales_creditmemo/') . '\')'
            );
        }
        return $this;
    }

    /**
     * Check whether action is allowed
     *
     * @param string $action
     * @return bool
     */
    public function _isAllowedAction($action)
    {
        return $this->_session->isAllowed('sales/order/actions/' . $action);
    }
}
