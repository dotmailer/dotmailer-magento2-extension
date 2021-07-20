<?php

namespace Dotdigitalgroup\Email\Observer\Customer;

use Dotdigitalgroup\Email\Model\ContactFactory;

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
     * @var ContactFactory
     */
    private $contactFactory;

    /**
     * ReviewSaveAutomation constructor.
     *
     * @param \Magento\Customer\Model\ResourceModel\Customer $customerResource
     * @param \Dotdigitalgroup\Email\Model\ReviewFactory $reviewFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Review $reviewResource
     * @param \Dotdigitalgroup\Email\Model\AutomationFactory $automationFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Automation $automationResource
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param ContactFactory $contactFactory
     */
    public function __construct(
        \Magento\Customer\Model\ResourceModel\Customer $customerResource,
        \Dotdigitalgroup\Email\Model\ReviewFactory $reviewFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Review $reviewResource,
        \Dotdigitalgroup\Email\Model\AutomationFactory $automationFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Automation $automationResource,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        ContactFactory $contactFactory
    ) {
        $this->reviewFactory     = $reviewFactory;
        $this->reviewResource = $reviewResource;
        $this->automationResource = $automationResource;
        $this->automationFactory = $automationFactory;
        $this->customerFactory   = $customerFactory;
        $this->helper            = $data;
        $this->storeManager      = $storeManagerInterface;
        $this->customerResource  = $customerResource;
        $this->contactFactory    = $contactFactory;
    }

    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $dataObject = $observer->getEvent()->getDataObject();

        if ($dataObject->getCustomerId()
            && $dataObject->getStatusId()
            == \Magento\Review\Model\Review::STATUS_APPROVED
        ) {
            $customerId = $dataObject->getCustomerId();

            //save review info in the table
            $storeId = $dataObject->getStoreId()
                ?: $this->reviewResource->getStoreIdFromReview($dataObject->getReviewId());
            $this->registerReview($dataObject, $storeId);

            $store = $this->storeManager->getStore($storeId);
            $websiteId = $store->getWebsiteId();

            $this->contactFactory->create()
                ->setConnectorContactToReImport($customerId, $websiteId);

            if (!$this->helper->isEnabled($websiteId)) {
                return $this;
            }

            $customer = $this->customerFactory->create();
            $this->customerResource->load($customer, $customerId);

            $programId
                = $this->helper->getWebsiteConfig('connector_automation/visitor_automation/review_automation');
            if ($programId) {
                $automation = $this->automationFactory->create();
                $automation->setEmail($customer->getEmail())
                    ->setAutomationType(\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_TYPE_NEW_REVIEW)
                    ->setEnrolmentStatus(\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_STATUS_PENDING)
                    ->setTypeId($dataObject->getReviewId())
                    ->setWebsiteId($websiteId)
                    ->setStoreName($store->getName())
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
     * @param string $storeId
     *
     * @return null
     */
    private function registerReview($review, $storeId)
    {
        try {
            $reviewModel = $this->reviewFactory->create();
            $reviewModel->setReviewId($review->getReviewId())
                ->setCustomerId($review->getCustomerId())
                ->setStoreId($storeId)
                ->setReviewImported(0);
            $this->reviewResource->save($reviewModel);
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }
    }
}
