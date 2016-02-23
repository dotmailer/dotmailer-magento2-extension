<?php


namespace Dotdigitalgroup\Email\Observer\Sales;


class PlaceCreateAutomationStatus
    implements \Magento\Framework\Event\ObserverInterface
{

    protected $_helper;
    protected $_registry;
    protected $_logger;
    protected $_scopeConfig;
    protected $_storeManager;
    protected $_orderFactory;
    protected $_automationFactory;

    /**
     * PlaceCreateAutomationStatus constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\AutomationFactory     $automationFactory
     * @param \Magento\Sales\Model\OrderFactory                  $orderFactory
     * @param \Magento\Framework\Registry                        $registry
     * @param \Dotdigitalgroup\Email\Helper\Data                 $data
     * @param \Psr\Log\LoggerInterface                           $loggerInterface
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface         $storeManagerInterface
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\AutomationFactory $automationFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Registry $registry,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Psr\Log\LoggerInterface $loggerInterface,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
    ) {
        $this->_automationFactory = $automationFactory;
        $this->_helper            = $data;
        $this->_orderFactory      = $orderFactory;
        $this->_scopeConfig       = $scopeConfig;
        $this->_logger            = $loggerInterface;
        $this->_storeManager      = $storeManagerInterface;
        $this->_registry          = $registry;
    }


    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order     = $observer->getEvent()->getOrder();
        $email     = $order->getCustomerEmail();
        $website   = $this->_storeManager->getWebsite($order->getWebsiteId());
        $storeName = $this->_storeManager->getStore($order->getStoreId())
            ->getName();
        //if api is not enabled
        if ( ! $this->_helper->isEnabled($website)) {
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
        if ( ! $programId) {
            return $this;
        }
        try {
            $automation = $this->_automationFactory->create()
                ->setEmail($email)
                ->setAutomationType($automationType)
                ->setEnrolmentStatus(\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_STATUS_PENDING)
                ->setTypeId($order->getId())
                ->setWebsiteId($website->getId())
                ->setStoreName($storeName)
                ->setProgramId($programId)
                ->save();
        } catch (\Exception $e) {
            $this->_helper->debug((string)$e, array());
        }

        return $this;
    }
}
