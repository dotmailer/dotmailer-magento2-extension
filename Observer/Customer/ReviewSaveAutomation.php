<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Observer\Customer;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\AutomationFactory;
use Dotdigitalgroup\Email\Model\ContactFactory;
use Dotdigitalgroup\Email\Model\Queue\Sync\Automation\AutomationPublisher;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation;
use Dotdigitalgroup\Email\Model\ResourceModel\Review;
use Dotdigitalgroup\Email\Model\ReviewFactory;
use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationTypeHandler;
use Dotdigitalgroup\Email\Model\StatusInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

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
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var AutomationPublisher
     */
    private $publisher;

    /**
     * ReviewSaveAutomation constructor.
     *
     * @param Customer $customerResource
     * @param ReviewFactory $reviewFactory
     * @param Review $reviewResource
     * @param AutomationFactory $automationFactory
     * @param Automation $automationResource
     * @param CustomerFactory $customerFactory
     * @param Data $data
     * @param StoreManagerInterface $storeManagerInterface
     * @param ContactFactory $contactFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param AutomationPublisher $publisher
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
        ContactFactory $contactFactory,
        ScopeConfigInterface $scopeConfig,
        AutomationPublisher $publisher
    ) {
        $this->reviewFactory = $reviewFactory;
        $this->reviewResource = $reviewResource;
        $this->automationResource = $automationResource;
        $this->automationFactory = $automationFactory;
        $this->customerFactory = $customerFactory;
        $this->helper = $data;
        $this->storeManager = $storeManagerInterface;
        $this->customerResource = $customerResource;
        $this->contactFactory = $contactFactory;
        $this->scopeConfig = $scopeConfig;
        $this->publisher = $publisher;
    }

    /**
     * Execute.
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

            $programId = $this->scopeConfig->getValue(
                Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_REVIEW,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );

            if ($programId) {
                $automation = $this->automationFactory->create();
                $automation->setEmail($customer->getEmail())
                    ->setAutomationType(AutomationTypeHandler::AUTOMATION_TYPE_NEW_REVIEW)
                    ->setEnrolmentStatus(StatusInterface::PENDING)
                    ->setTypeId($dataObject->getReviewId())
                    ->setWebsiteId($websiteId)
                    ->setStoreId($store->getId())
                    ->setStoreName($store->getName())
                    ->setProgramId($programId);
                $this->automationResource->save($automation);

                $this->publisher->publish($automation);
            }
        }

        return $this;
    }

    /**
     * Register review.
     *
     * @param mixed $review
     * @param string $storeId
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
