<?php

namespace Dotdigitalgroup\Email\Model\AbandonedCart\ProgramEnrolment;

use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationTypeHandler;
use Dotdigitalgroup\Email\Model\StatusInterface;
use Magento\Quote\Model\Quote;

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
     * @param Quote $quote
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
                ->setAutomationType(AutomationTypeHandler::AUTOMATION_TYPE_ABANDONED_CART_PROGRAM_ENROLMENT)
                ->setEnrolmentStatus(StatusInterface::PENDING)
                ->setTypeId($quote->getId())
                ->setWebsiteId($store->getWebsiteId())
                ->setStoreId($store->getId())
                ->setStoreName($store->getName())
                ->setProgramId($programId);
            $this->automationResource->save($automation);
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }
    }
}
