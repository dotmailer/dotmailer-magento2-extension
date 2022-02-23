<?php

namespace Dotdigitalgroup\Email\Observer\Sales;

use Dotdigitalgroup\Email\Model\Sales\CartInsight\Update as CartInsightUpdater;
use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationTypeHandler;
use Dotdigitalgroup\Email\Model\StatusInterface;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Email\Model\ContactFactory;

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
     * @var ContactCollectionFactory
     */
    private $contactCollectionFactory;

    /**
     * @var ContactResource
     */
    private $contactResource;

    /**
     * @var ContactFactory
     */
    private $contactFactory;

    /**
     * @param \Dotdigitalgroup\Email\Model\AutomationFactory $automationFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Automation $automationResource
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param CartInsightUpdater $cartInsightUpdater
     * @param ContactCollectionFactory $contactCollectionFactory
     * @param ContactResource $contactResource
     * @param ContactFactory $contactFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\AutomationFactory $automationFactory,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Dotdigitalgroup\Email\Model\ResourceModel\Automation $automationResource,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        CartInsightUpdater $cartInsightUpdater,
        ContactCollectionFactory $contactCollectionFactory,
        ContactResource $contactResource,
        ContactFactory $contactFactory
    ) {
        $this->automationFactory = $automationFactory;
        $this->automationResource = $automationResource;
        $this->helper            = $data;
        $this->storeManager      = $storeManagerInterface;
        $this->cartInsightUpdater = $cartInsightUpdater;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->contactResource = $contactResource;
        $this->contactFactory = $contactFactory;
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
        $websiteId = $store->getWebsiteId();

        if (!$this->helper->isEnabled($websiteId)) {
            return $this;
        }

        $this->cartInsightUpdater->updateCartPhase($order, $store);

        if ($order->getCustomerIsGuest()) {
            $this->createOrUpdateGuestContact($order, $websiteId);

            $programType = 'XML_PATH_CONNECTOR_AUTOMATION_STUDIO_GUEST_ORDER';
            $automationType = AutomationTypeHandler::AUTOMATION_TYPE_NEW_GUEST_ORDER;
        } else {
            $programType = 'XML_PATH_CONNECTOR_AUTOMATION_STUDIO_ORDER';
            $automationType = AutomationTypeHandler::AUTOMATION_TYPE_NEW_ORDER;
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
                ->setEnrolmentStatus(StatusInterface::PENDING)
                ->setTypeId($order->getIncrementId())
                ->setWebsiteId($websiteId)
                ->setStoreId($store->getId())
                ->setStoreName($store->getName())
                ->setProgramId($programId);
            $this->automationResource->save($automation);
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }

        return $this;
    }

    /**
     * Add a new guest contact or update existing.
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param string|int $websiteId
     *
     * @return void
     */
    private function createOrUpdateGuestContact($order, $websiteId)
    {
        $matchingContact = $this->contactCollectionFactory->create()
            ->addFieldToFilter('email', $order->getCustomerEmail())
            ->addFieldToFilter('website_id', $websiteId)
            ->setPageSize(1);

        if ($matchingContact->getSize()) {
            $this->contactResource->setContactsAsGuest(
                [$matchingContact->getFirstItem()->getEmail()],
                $websiteId
            );
        } else {
            $guestToInsert = $this->contactFactory->create()
                ->setEmail($order->getCustomerEmail())
                ->setWebsiteId($websiteId)
                ->setStoreId($order->getStoreId())
                ->setIsGuest(1);

            $this->contactResource->save($guestToInsert);
        }
    }
}
