<?php

namespace Dotdigitalgroup\Email\Plugin;

/**
 * New customer automation plugin.
 */
class CustomerManagementPlugin
{
    /**
     * @var \Dotdigitalgroup\Email\Model\Automation
     */
    private $automation;

    /**
     * CustomerManagementPlugin constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\Automation $automation
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Automation $automation
    ) {
        $this->automation = $automation;
    }

    /**
     * Plugin for create function.
     *
     * @param \Magento\Sales\Model\Order\CustomerManagement $subject
     * @param mixed $customer
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCreate(\Magento\Sales\Model\Order\CustomerManagement $subject, $customer)
    {
        //New Automation enrolment to queue
        $this->automation->newCustomerAutomation($customer);

        return $customer;
    }
}
