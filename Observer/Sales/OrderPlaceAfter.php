<?php

namespace Dotdigitalgroup\Email\Observer\Sales;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\AutomationFactory;
use Dotdigitalgroup\Email\Model\Queue\Data\CartPhaseUpdateDataFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation;
use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationTypeHandler;
use Dotdigitalgroup\Email\Model\StatusInterface;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Email\Model\ContactFactory;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Send cart phase flag as CartInsight for some orders.
 * New order automation for customers and guests.
 */
class OrderPlaceAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var Automation
     */
    private $automationResource;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var CartPhaseUpdateDataFactory
     */
    private $cartPhaseUpdateDataFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Dotdigitalgroup\Email\Model\AutomationFactory
     */
    private $automationFactory;

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
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @param AutomationFactory $automationFactory
     * @param Data $data
     * @param Automation $automationResource
     * @param StoreManagerInterface $storeManagerInterface
     * @param ContactCollectionFactory $contactCollectionFactory
     * @param ContactResource $contactResource
     * @param ContactFactory $contactFactory
     * @param CartPhaseUpdateDataFactory $cartPhaseUpdateDataFactory
     * @param PublisherInterface $publisher
     */
    public function __construct(
        AutomationFactory $automationFactory,
        Data $data,
        Automation $automationResource,
        StoreManagerInterface $storeManagerInterface,
        ContactCollectionFactory $contactCollectionFactory,
        ContactResource $contactResource,
        ContactFactory $contactFactory,
        CartPhaseUpdateDataFactory $cartPhaseUpdateDataFactory,
        PublisherInterface $publisher
    ) {
        $this->automationFactory = $automationFactory;
        $this->automationResource = $automationResource;
        $this->helper = $data;
        $this->storeManager = $storeManagerInterface;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->contactResource = $contactResource;
        $this->contactFactory = $contactFactory;
        $this->cartPhaseUpdateDataFactory = $cartPhaseUpdateDataFactory;
        $this->publisher = $publisher;
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

        $this->queueCartPhaseUpdate($order);

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
     * @param OrderInterface $order
     * @param string|int $websiteId
     *
     * @return void
     */
    private function createOrUpdateGuestContact($order, $websiteId)
    {
        try {
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
        } catch (\Exception $e) {
            $this->helper->debug('Error when updating email_contact table', [(string) $e]);
        }
    }

    /**
     * Queue cart phase update.
     *
     * @param OrderInterface $order
     * @return void
     */
    private function queueCartPhaseUpdate(OrderInterface $order)
    {
        $cartPhaseUpdateData = $this->cartPhaseUpdateDataFactory->create();
        $cartPhaseUpdateData->setQuoteId((int) $order->getQuoteId());
        $cartPhaseUpdateData->setStoreId($order->getStoreId());

        $this->publisher->publish('ddg.sales.cart_phase_update', $cartPhaseUpdateData);
    }
}
