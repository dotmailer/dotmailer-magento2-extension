<?php

namespace Dotdigitalgroup\Email\Plugin;

class CustomerManagement
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Automation
     */
    protected $_helper;

    /**
     * NewAutomation constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Automation $automation
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Automation $automation
    ) {
        $this->_helper = $automation;
    }

    /**
     * Plugin for create function
     *
     * @param \Magento\Sales\Model\Order\CustomerManagement $subject
     * @param $customer
     * @return mixed
     */
    public function afterCreate(\Magento\Sales\Model\Order\CustomerManagement $subject, $customer)
    {
        //New Automation enrolment to queue
        $this->_helper->newCustomerAutomation($customer);
        return $customer;
    }
}