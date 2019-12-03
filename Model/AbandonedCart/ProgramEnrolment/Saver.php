<?php

namespace Dotdigitalgroup\Email\Model\AbandonedCart\ProgramEnrolment;

class Saver
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Dotdigitalgroup\Email\Model\AutomationFactory
     */
    private $automationFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Automation
     */
    private $automationResource;

    /**
     * Saver constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\AutomationFactory $automationFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Automation $automationResource
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\AutomationFactory $automationFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Automation $automationResource,
        \Dotdigitalgroup\Email\Helper\Data $data
    ) {
        $this->automationFactory = $automationFactory;
        $this->automationResource = $automationResource;
        $this->helper = $data;
    }

    /**
     * Process abandoned carts for automation program enrolment
     *
     * @param \Magento\Quote\Model\ResourceModel\Quote
     * @param \Magento\Store\Api\Data\StoreInterface $store
     * @param int $programId
     *
     * @return void
     * @throws \Exception
     */
    public function save($quote, $store, $programId)
    {
        try {
            $automation = $this->automationFactory->create()
                ->setEmail($quote->getCustomerEmail())
                ->setAutomationType(\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_TYPE_ABANDONED_CART_PROGRAM_ENROLMENT)
                ->setEnrolmentStatus(\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_STATUS_PENDING)
                ->setTypeId($quote->getId())
                ->setWebsiteId($store->getWebsiteId())
                ->setStoreName($store->getName())
                ->setProgramId($programId);
            $this->automationResource->save($automation);
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }
    }
}
