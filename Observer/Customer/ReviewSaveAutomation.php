<?php

namespace Dotdigitalgroup\Email\Observer\Customer;

class ReviewSaveAutomation implements \Magento\Framework\Event\ObserverInterface
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
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\ReviewFactory
     */
    protected $_reviewFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\AutomationFactory
     */
    protected $_automationFactory;

    /**
     * ReviewSaveAutomation constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ReviewFactory     $reviewFactory
     * @param \Dotdigitalgroup\Email\Model\AutomationFactory $automationFactory
     * @param \Magento\Customer\Model\CustomerFactory        $customerFactory
     * @param \Dotdigitalgroup\Email\Helper\Data             $data
     * @param \Magento\Store\Model\StoreManagerInterface     $storeManagerInterface
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ReviewFactory $reviewFactory,
        \Dotdigitalgroup\Email\Model\AutomationFactory $automationFactory,

        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
    ) {
        $this->_reviewFactory = $reviewFactory;
        $this->_automationFactory = $automationFactory;
        $this->_customerFactory = $customerFactory;
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
        $dataObject = $observer->getEvent()->getDataObject();

        if ($dataObject->getCustomerId()
            && $dataObject->getStatusId()
            == \Magento\Review\Model\Review::STATUS_APPROVED
        ) {
            $customerId = $dataObject->getCustomerId();
            $this->_helper->setConnectorContactToReImport($customerId);
            //save review info in the table
            $this->_registerReview($dataObject);
            $store = $this->_storeManager->getStore($dataObject->getStoreId());
            $storeName = $store->getName();
            $website = $this->_storeManager->getStore($store)->getWebsite();
            $customer = $this->_customerFactory->create()
                ->load($customerId);
            //if api is not enabled
            if (!$this->_helper->isEnabled($website)) {
                return $this;
            }

            $programId
                = $this->_helper->getWebsiteConfig('connector_automation/visitor_automation/review_automation');
            if ($programId) {
                $automation = $this->_automationFactory->create();
                $automation->setEmail($customer->getEmail())
                    ->setAutomationType(\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_TYPE_NEW_REVIEW)
                    ->setEnrolmentStatus(\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_STATUS_PENDING)
                    ->setTypeId($dataObject->getReviewId())
                    ->setWebsiteId($website->getId())
                    ->setStoreName($storeName)
                    ->setProgramId($programId);
                $automation->save();
            }
        }

        return $this;
    }

    /**
     * Register review.
     *
     * @param $review
     */
    private function _registerReview($review)
    {
        try {
            $this->_reviewFactory->create()
                ->setReviewId($review->getReviewId())
                ->setCustomerId($review->getCustomerId())
                ->setStoreId($review->getStoreId())
                ->save();
        } catch (\Exception $e) {
            $this->_helper->debug((string) $e, []);
        }
    }
}
