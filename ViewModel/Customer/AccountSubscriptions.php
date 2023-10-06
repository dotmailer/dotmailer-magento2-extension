<?php

namespace Dotdigitalgroup\Email\ViewModel\Customer;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use stdClass;

class AccountSubscriptions implements ArgumentInterface
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var ContactCollectionFactory
     */
    private $contactCollectionFactory;

    /**
     * @var Contact
     */
    private $contactFromTable;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var object
     */
    private $contactFromAccount;

    /**
     * @param Data $helper
     * @param ContactCollectionFactory $contactCollectionFactory
     * @param Session $customerSession
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Data $helper,
        ContactCollectionFactory $contactCollectionFactory,
        Session $customerSession,
        StoreManagerInterface $storeManager
    ) {
        $this->helper = $helper;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
    }

    /**
     * Get api client.
     *
     * @return \Dotdigitalgroup\Email\Model\Apiconnector\Client
     */
    public function getApiClient()
    {
        if (empty($this->client)) {
            $this->client = $this->helper->getWebsiteApiClient(
                $this->storeManager->getWebsite()->getId()
            );
        }

        return $this->client;
    }

    /**
     * Get contact from table.
     *
     * @return Contact
     * @throws LocalizedException
     */
    public function getContactFromTable()
    {
        if (!isset($this->contactFromTable) && $this->getCustomer()->getEmail()) {
            $this->contactFromTable = $this->contactCollectionFactory->create()
                ->loadByCustomerEmail(
                    $this->getCustomer()->getEmail(),
                    $this->storeManager->getWebsite()->getId()
                );
        }

        return $this->contactFromTable;
    }

    /**
     * Get connector contact.
     *
     * @return stdClass
     * @throws LocalizedException
     */
    public function getConnectorContact()
    {
        if (!isset($this->contactFromAccount)) {
            $contact = $this->getApiClient()->getContactByEmail(
                $this->getCustomer()->getEmail()
            );
            if (isset($contact->id)) {
                $this->contactFromAccount = $contact;
            }
        }

        return $this->contactFromAccount;
    }

    /**
     * Get customer from session.
     *
     * @return \Magento\Customer\Model\Customer
     */
    private function getCustomer()
    {
        return $this->customerSession->getCustomer();
    }
}
