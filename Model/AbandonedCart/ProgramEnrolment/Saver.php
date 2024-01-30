<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\AbandonedCart\ProgramEnrolment;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\AutomationFactory;
use Dotdigitalgroup\Email\Model\Queue\Sync\Automation\AutomationPublisher;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation;
use Dotdigitalgroup\Email\Model\StatusInterface;
use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationTypeHandler;
use Exception;
use Magento\Quote\Model\Quote;
use Magento\Store\Api\Data\StoreInterface;

class Saver
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var AutomationFactory
     */
    private $automationFactory;

    /**
     * @var AutomationPublisher
     */
    private $publisher;

    /**
     * @var Automation
     */
    private $automationResource;

    /**
     * Saver constructor.
     *
     * @param AutomationFactory $automationFactory
     * @param AutomationPublisher $publisher
     * @param Automation $automationResource
     * @param Data $data
     */
    public function __construct(
        AutomationFactory $automationFactory,
        AutomationPublisher $publisher,
        Automation $automationResource,
        Data $data
    ) {
        $this->automationFactory = $automationFactory;
        $this->publisher = $publisher;
        $this->automationResource = $automationResource;
        $this->helper = $data;
    }

    /**
     * Process abandoned carts for automation program enrolment
     *
     * @param Quote $quote
     * @param StoreInterface $store
     * @param int $programId
     *
     * @return void
     * @throws Exception
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

            $this->publisher->publish($automation);
        } catch (Exception $e) {
            $this->helper->debug((string)$e, []);
        }
    }
}
