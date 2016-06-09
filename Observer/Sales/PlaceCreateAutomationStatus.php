<?php

namespace Dotdigitalgroup\Email\Observer\Sales;

class PlaceCreateAutomationStatus implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Dotdigitalgroup\Email\Model\AutomationFactory
     */
    protected $_automationFactory;

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
        $this->_automationFactory = $automationFactory;
        $this->_helper = $data;
        $this->_storeManager = $storeManagerInterface;
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
        $website = $this->_storeManager->getWebsite($order->getWebsiteId());
        $storeName = $this->_storeManager->getStore($order->getStoreId())
            ->getName();
        //if api is not enabled
        if (!$this->_helper->isEnabled($website)) {
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
        $programId = $this->_helper->getAutomationIdByType($programType,
            $order->getWebsiteId());

        //the program is not mapped
        if (!$programId) {
            return $this;
        }
        try {
            $this->_automationFactory->create()
                ->setEmail($email)
                ->setAutomationType($automationType)
                ->setEnrolmentStatus(\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_STATUS_PENDING)
                ->setTypeId($order->getId())
                ->setWebsiteId($website->getId())
                ->setStoreName($storeName)
                ->setProgramId($programId)
                ->save();
        } catch (\Exception $e) {
            $this->_helper->debug((string)$e, []);
        }

        return $this;
    }
}
