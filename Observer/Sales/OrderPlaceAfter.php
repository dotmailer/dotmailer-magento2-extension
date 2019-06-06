<?php

namespace Dotdigitalgroup\Email\Observer\Sales;

use Dotdigitalgroup\Email\Model\Sales\CartInsight\Update as CartInsightUpdater;

/**
 * Send cart phase flag as CartInsight for some orders.
 * New order automation for customers and guests.
 */
class OrderPlaceAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Automation
     */
    private $automationResource;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Dotdigitalgroup\Email\Model\AutomationFactory
     */
    private $automationFactory;

    /**
     * @var CartInsightUpdater
     */
    private $cartInsightUpdater;

    /**
     * OrderPlaceAfter constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\AutomationFactory $automationFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Automation $automationResource
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param CartInsightUpdater $cartInsightUpdater
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\AutomationFactory $automationFactory,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Dotdigitalgroup\Email\Model\ResourceModel\Automation $automationResource,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        CartInsightUpdater $cartInsightUpdater
    ) {
        $this->automationFactory = $automationFactory;
        $this->automationResource = $automationResource;
        $this->helper            = $data;
        $this->storeManager      = $storeManagerInterface;
        $this->cartInsightUpdater = $cartInsightUpdater;
    }

    /**
     * Execute method.
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $store = $this->storeManager->getStore($order->getStoreId());
        $website = $store->getWebsite();

        if (!$this->helper->isEnabled($website)) {
            return $this;
        }

        $this->cartInsightUpdater->updateCartPhase($order, $store);

        //automation enrolment for order
        if ($order->getCustomerIsGuest()) {
            // guest to automation mapped
            $programType = 'XML_PATH_CONNECTOR_AUTOMATION_STUDIO_GUEST_ORDER';
            $automationType
                         = \Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_TYPE_NEW_GUEST_ORDER;
        } else {
            // customer to automation mapped
            $programType = 'XML_PATH_CONNECTOR_AUTOMATION_STUDIO_ORDER';
            $automationType
                         = \Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_TYPE_NEW_ORDER;
        }
        $programId = $this->helper->getAutomationIdByType(
            $programType,
            $order->getStoreId()
        );

        //the program is not mapped
        if (!$programId) {
            return $this;
        }
        try {
            $automation = $this->automationFactory->create()
                ->setEmail($order->getCustomerEmail())
                ->setAutomationType($automationType)
                ->setEnrolmentStatus(\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_STATUS_PENDING)
                ->setTypeId($order->getIncrementId())
                ->setWebsiteId($website->getId())
                ->setStoreName($store->getName())
                ->setProgramId($programId);
            $this->automationResource->save($automation);
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }

        return $this;
    }
}
