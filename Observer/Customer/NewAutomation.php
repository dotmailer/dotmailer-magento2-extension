<?php

namespace Dotdigitalgroup\Email\Observer\Customer;

class NewAutomation implements \Magento\Framework\Event\ObserverInterface
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
     * NewAutomation constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\AutomationFactory $automationFactory
     * @param \Dotdigitalgroup\Email\Helper\Data             $data
     * @param \Magento\Store\Model\StoreManagerInterface     $storeManagerInterface
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
     * If it's configured to capture on shipment - do this.
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        $email = $customer->getEmail();
        $websiteId = $customer->getWebsiteId();
        $customerId = $customer->getId();
        $store = $this->_storeManager->getStore($customer->getStoreId());
        $storeName = $store->getName();
        try {

            //Api is not enabled
            $apiEnabled = $this->_helper->getWebsiteConfig(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_API_ENABLED, $websiteId);
            if (!$apiEnabled) {
                return $this;
            }
            //Automation enrolment
            $programId = $this->_helper->getWebsiteConfig(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_CUSTOMER, $websiteId);

            //new contact program mapped
            if ($programId) {
                $automation = $this->_automationFactory->create();
                //save automation type
                $automation->setEmail($email)
                    ->setAutomationType(\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_TYPE_NEW_CUSTOMER)
                    ->setEnrolmentStatus(\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_STATUS_PENDING)
                    ->setTypeId($customerId)
                    ->setWebsiteId($websiteId)
                    ->setStoreName($storeName)
                    ->setProgramId($programId);
                $automation->save();
            }
        } catch (\Exception $e) {
            $this->_helper->debug((string) $e, []);
        }

        return $this;
    }
}
