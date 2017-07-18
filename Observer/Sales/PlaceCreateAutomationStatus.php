<?php

namespace Dotdigitalgroup\Email\Observer\Sales;

/**
 * Customer and guest new order automation.
 */
class PlaceCreateAutomationStatus implements \Magento\Framework\Event\ObserverInterface
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
     * PlaceCreateAutomationStatus constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\AutomationFactory $automationFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Automation $automationResource
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\AutomationFactory $automationFactory,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Dotdigitalgroup\Email\Model\ResourceModel\Automation $automationResource,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
    ) {
        $this->automationFactory = $automationFactory;
        $this->automationResource = $automationResource;
        $this->helper            = $data;
        $this->storeManager      = $storeManagerInterface;
    }

    /**
     * Execute method.
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $email = $order->getCustomerEmail();
        $website = $this->storeManager->getWebsite($order->getWebsiteId());
        $storeName = $this->storeManager->getStore($order->getStoreId())
            ->getName();
        //if api is not enabled
        if (!$this->helper->isEnabled($website)) {
            return $this;
        }
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
                ->setEmail($email)
                ->setAutomationType($automationType)
                ->setEnrolmentStatus(\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_STATUS_PENDING)
                ->setTypeId($order->getIncrementId())
                ->setWebsiteId($website->getId())
                ->setStoreName($storeName)
                ->setProgramId($programId);
            $this->automationResource->save($automation);
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }

        return $this;
    }
}
