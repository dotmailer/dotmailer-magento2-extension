<?php

namespace Dotdigitalgroup\Email\Observer\Customer;

/**
 * Register new customer automation.
 */
class NewAutomation implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Model\Automation
     */
    private $automation;

    /**
     * NewAutomation constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\Automation $automation
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Automation $automation
    ) {
        $this->automation = $automation;
    }

    /**
     * If it's configured to capture on shipment - do this.
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        //New Automation enrolment to queue
        $this->automation->newCustomerAutomation($customer);

        return $this;
    }
}
