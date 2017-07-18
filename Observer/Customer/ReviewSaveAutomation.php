<?php

namespace Dotdigitalgroup\Email\Observer\Customer;

/**
 * New review automation.
 */
class ReviewSaveAutomation implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Review
     */
    private $reviewResource;

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
     * @var \Magento\Customer\Model\CustomerFactory
     */
    private $customerFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ReviewFactory
     */
    private $reviewFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\AutomationFactory
     */
    private $automationFactory;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer
     */
    private $customerResource;

    /**
     * ReviewSaveAutomation constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Review $reviewResource
     * @param \Magento\Customer\Model\ResourceModel\Customer $customerResource
     * @param \Dotdigitalgroup\Email\Model\ReviewFactory     $reviewFactory
     * @param \Dotdigitalgroup\Email\Model\AutomationFactory $automationFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Automation $automationResource
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     */
    public function __construct(
        \Magento\Customer\Model\ResourceModel\Customer $customerResource,
        \Dotdigitalgroup\Email\Model\ReviewFactory $reviewFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Review $reviewResource,
        \Dotdigitalgroup\Email\Model\AutomationFactory $automationFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Automation $automationResource,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
    ) {
        $this->reviewFactory     = $reviewFactory;
        $this->reviewResource = $reviewResource;
        $this->automationResource = $automationResource;
        $this->automationFactory = $automationFactory;
        $this->customerFactory   = $customerFactory;
        $this->helper            = $data;
        $this->storeManager      = $storeManagerInterface;
        $this->customerResource  = $customerResource;
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
            $this->helper->setConnectorContactToReImport($customerId);
            //save review info in the table
            $this->registerReview($dataObject);
            $store = $this->storeManager->getStore($dataObject->getStoreId());
            $storeName = $store->getName();
            $website = $this->storeManager->getStore($store)->getWebsite();
            $customer = $this->customerFactory->create();
            $this->customerResource->load($customer, $customerId);
            //if api is not enabled
            if (!$this->helper->isEnabled($website)) {
                return $this;
            }

            $programId
                = $this->helper->getWebsiteConfig('connector_automation/visitor_automation/review_automation');
            if ($programId) {
                $automation = $this->automationFactory->create();
                $automation->setEmail($customer->getEmail())
                    ->setAutomationType(\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_TYPE_NEW_REVIEW)
                    ->setEnrolmentStatus(\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_STATUS_PENDING)
                    ->setTypeId($dataObject->getReviewId())
                    ->setWebsiteId($website->getId())
                    ->setStoreName($storeName)
                    ->setProgramId($programId);
                $this->automationResource->save($automation);
            }
        }

        return $this;
    }

    /**
     * Register review.
     *
     * @param mixed $review
     *
     * @return null
     */
    private function registerReview($review)
    {
        try {
            $reviewModel = $this->reviewFactory->create();
            $reviewModel->setReviewId($review->getReviewId())
                ->setCustomerId($review->getCustomerId())
                ->setStoreId($review->getStoreId());
            $this->reviewResource->save($reviewModel);
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }
    }
}
