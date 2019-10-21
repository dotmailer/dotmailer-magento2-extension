<?php

namespace Dotdigitalgroup\Email\Model\Chat\Profile;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Session;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

class Data
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * Data constructor
     *
     * @param Session $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     * @param CartRepositoryInterface $quoteRepository
     * @param StoreManagerInterface $storeManager
     * @param CollectionFactory $orderCollectionFactory
     */
    public function __construct(
        Session $customerSession,
        CustomerRepositoryInterface $customerRepository,
        CartRepositoryInterface $quoteRepository,
        StoreManagerInterface $storeManager,
        CollectionFactory $orderCollectionFactory
    ) {
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->quoteRepository = $quoteRepository;
        $this->storeManager = $storeManager;
        $this->orderCollectionFactory = $orderCollectionFactory;
    }

    /**
     * Collects data for chat user
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getDataForChatUser()
    {
        $store = $this->storeManager->getStore();

        if (!$this->customerSession->isLoggedIn()) {
            return $this->getBasePayload($store);
        }

        $loggedInCustomerId = $this->customerSession->getCustomer()->getId();
        if (!$customer = $this->customerRepository->getById($loggedInCustomerId)) {
            return $this->getBasePayload($store);
        }

        try {
            $quote = $this->quoteRepository->getForCustomer($loggedInCustomerId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $quote = null;
        }

        return $this->getCustomerPayload($store, $customer, $quote);
    }

    /**
     * Returns basic payload for all users
     *
     * @param StoreInterface $store
     * @return array
     */
    private function getBasePayload(StoreInterface $store)
    {
        return [
            "store" => [
                "id" => $store->getId(),
                "url" => $store->getBaseUrl(
                    \Magento\Framework\UrlInterface::URL_TYPE_WEB,
                    true
                )
            ],
        ];
    }

    /**
     * Returns enhanced payload array for logged-in customers
     *
     * @param StoreInterface $store
     * @param CustomerInterface $customer
     * @param CartInterface|null $quote
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCustomerPayload(StoreInterface $store, CustomerInterface $customer, CartInterface $quote = null)
    {
        $data = $this->getBasePayload($store);

        $data["customer"] = [
            "id" => $customer->getId(),
            "groupId" => $customer->getGroupId(),
            "firstName" => $customer->getFirstName(),
            "lastName" => $customer->getLastName(),
            "email" => $customer->getEmail()
        ];

        if ($quote) {
            $data["customer"]["quoteId"] = $quote->getId();
        }

        return $data;
    }
}
