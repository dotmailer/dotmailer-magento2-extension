<?php

namespace Dotdigitalgroup\Email\ViewModel\Customer;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\ContactFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;

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
     * @var ContactFactory
     */
    private $contactFactory;

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
     * @param ContactFactory $contactFactory
     * @param Session $customerSession
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Data $helper,
        ContactFactory $contactFactory,
        Session $customerSession,
        StoreManagerInterface $storeManager
    ) {
        $this->helper = $helper;
        $this->contactFactory = $contactFactory;
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
     */
    public function getContactFromTable()
    {
        if (!isset($this->contactFromTable) && $this->getCustomer()->getEmail()) {
            $this->contactFromTable = $this->contactFactory->create()
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
     * @return object
     */
    public function getConnectorContact()
    {
        if (!isset($this->contactFromAccount)) {
            $contact = $this->getApiClient()->getContactByEmail(
                $this->getCustomer()->getEmail()
            );
            if (isset($contact->id)) {
                $this->contactFromAccount = $contact;
                $this->customerSession->setConnectorContactId($contact->id);
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
