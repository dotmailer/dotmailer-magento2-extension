<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Observer\Sales;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\AutomationFactory;
use Dotdigitalgroup\Email\Model\Queue\Data\CartPhaseUpdateDataFactory;
use Dotdigitalgroup\Email\Model\Queue\Sync\Automation\AutomationPublisher;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation;
use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationTypeHandler;
use Dotdigitalgroup\Email\Model\StatusInterface;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Email\Model\ContactFactory;
use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Send cart phase flag as CartInsight for some orders.
 * New order automation for customers and guests.
 */
class OrderPlaceAfter implements ObserverInterface
{
    private const TOPIC_SALES_CART_PHASE_UPDATE = 'ddg.sales.cart_phase_update';

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var AutomationFactory
     */
    private $automationFactory;

    /**
     * @var CartPhaseUpdateDataFactory
     */
    private $cartPhaseUpdateDataFactory;

    /**
     * @var AutomationPublisher
     */
    private $automationPublisher;

    /**
     * @var Automation
     */
    private $automationResource;

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
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Data $data
     * @param Logger $logger
     * @param AutomationFactory $automationFactory
     * @param CartPhaseUpdateDataFactory $cartPhaseUpdateDataFactory
     * @param AutomationPublisher $automationPublisher
     * @param Automation $automationResource
     * @param ContactCollectionFactory $contactCollectionFactory
     * @param ContactResource $contactResource
     * @param ContactFactory $contactFactory
     * @param PublisherInterface $publisher
     * @param StoreManagerInterface $storeManagerInterface
     */
    public function __construct(
        Data $data,
        Logger $logger,
        AutomationFactory $automationFactory,
        CartPhaseUpdateDataFactory $cartPhaseUpdateDataFactory,
        AutomationPublisher $automationPublisher,
        Automation $automationResource,
        ContactCollectionFactory $contactCollectionFactory,
        ContactResource $contactResource,
        ContactFactory $contactFactory,
        PublisherInterface $publisher,
        StoreManagerInterface $storeManagerInterface
    ) {
        $this->automationFactory = $automationFactory;
        $this->automationResource = $automationResource;
        $this->helper = $data;
        $this->logger = $logger;
        $this->automationPublisher = $automationPublisher;
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
     * @param Observer $observer
     *
     * @return $this
     */
    public function execute(Observer $observer)
    {
        try {
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

            $this->automationPublisher->publish($automation);
        } catch (Exception $e) {
            $this->logger->error('Error in OrderPlaceAfter observer', [(string)$e]);
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
        } catch (Exception $e) {
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

        $this->publisher->publish(self::TOPIC_SALES_CART_PHASE_UPDATE, $cartPhaseUpdateData);
    }
}
