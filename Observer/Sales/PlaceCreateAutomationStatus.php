<?php

namespace Dotdigitalgroup\Email\Observer\Sales;

class PlaceCreateAutomationStatus implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $storeManager;
    /**
     * @var \Dotdigitalgroup\Email\Model\AutomationFactory
     */
    public $automationFactory;

    /**
     * PlaceCreateAutomationStatus constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\AutomationFactory $automationFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\AutomationFactory $automationFactory,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
    ) {
        $this->automationFactory = $automationFactory;
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
            $this->automationFactory->create()
                ->setEmail($email)
                ->setAutomationType($automationType)
                ->setEnrolmentStatus(\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_STATUS_PENDING)
                ->setTypeId($order->getIncrementId())
                ->setWebsiteId($website->getId())
                ->setStoreName($storeName)
                ->setProgramId($programId)
                ->save();
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }

        return $this;
    }
}
