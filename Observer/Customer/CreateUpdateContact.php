<?php

namespace Dotdigitalgroup\Email\Observer\Customer;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\ContactFactory;
use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\ImporterFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory;
use Dotdigitalgroup\Email\Helper\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\StoreWebsiteRelationInterface;

/**
 * Creates and updates the contact for customer. Monitor the email change for customer.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CreateUpdateContact implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Contact
     */
    private $contactResource;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @var \Dotdigitalgroup\Email\Model\ContactFactory
     */
    private $contactFactory;

    /**
     * @var CollectionFactory
     */
    private $contactCollectionFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    private $importerFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var StoreWebsiteRelationInterface
     */
    private $storeWebsiteRelation;

    /**
     * @param ContactFactory $contactFactory
     * @param CollectionFactory $contactCollectionFactory
     * @param Registry $registry
     * @param Data $data
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource
     * @param ImporterFactory $importerFactory
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $config
     * @param StoreWebsiteRelationInterface $storeWebsiteRelation
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
        CollectionFactory $contactCollectionFactory,
        \Magento\Framework\Registry $registry,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource,
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        Config $config,
        StoreWebsiteRelationInterface $storeWebsiteRelation
    ) {
        $this->contactFactory = $contactFactory;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->contactResource = $contactResource;
        $this->helper = $data;
        $this->registry = $registry;
        $this->importerFactory = $importerFactory;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->config = $config;
        $this->storeWebsiteRelation = $storeWebsiteRelation;
    }

    /**
     * Execute.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $storeManagerWebsiteId = $this->storeManager->getWebsite()->getId();
        $customer = $observer->getEvent()->getCustomer();
        $email = $customer->getEmail();
        $customerId = $customer->getEntityId();
        $websiteId = $customer->getWebsiteId();

        if (!$this->helper->isEnabled($storeManagerWebsiteId) &&
            !$this->helper->isEnabled($customer->getWebsiteId())) {
            return $this;
        }

        try {
            // fix for a multiple hit of the observer
            $emailReg = $this->registry->registry($email . '_customer_save');
            if ($emailReg) {
                return $this;
            }
            $this->registry->unregister($email . '_customer_save'); // additional measure
            $this->registry->register($email . '_customer_save', $email);

            $matchingCustomers = $this->contactCollectionFactory->create()
                ->loadCustomersById($customerId);

            if ($matchingCustomers->getSize() == 0) {
                $contactModel = $this->contactCollectionFactory->create()
                    ->loadByCustomerEmail($email, $websiteId);

                if ($contactModel) {
                    $contactModel->setCustomerId($customerId);
                } else {
                    $contactModel = $this->contactFactory->create()
                        ->setEmail($email)
                        ->setWebsiteId($customer->getWebsiteId())
                        ->setStoreId($customer->getStoreId())
                        ->setCustomerId($customerId);
                }

                $contactModel->setEmailImported(Contact::EMAIL_CONTACT_NOT_IMPORTED);
                $this->contactResource->save($contactModel);
                return $this;
            }

            foreach ($matchingCustomers as $contactModel) {
                $contactModel = $this->checkForEmailUpdate($contactModel, $email);
                $contactModel = $this->checkForWebsiteUpdate($contactModel, $websiteId);

                $contactModel->setEmailImported(Contact::EMAIL_CONTACT_NOT_IMPORTED);
                $this->contactResource->save($contactModel);
            }
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }

        return $this;
    }

    /**
     * Create new row for matching customers.
     *
     * @param Contact $contactModel
     * @param string|int $websiteId
     *
     * @return void
     * @throws AlreadyExistsException
     */
    private function createNewRowForMatchingCustomer(Contact $contactModel, $websiteId)
    {
        $contactExists = $this->contactCollectionFactory->create()
            ->loadByCustomerIdAndWebsiteId($contactModel->getCustomerId(), $websiteId);

        if (!$contactExists) {
            $newContactModel = $this->contactFactory->create();
            $newContactModel->setEmail($contactModel->getEmail())
                ->setWebsiteId($websiteId)
                ->setStoreId($this->getStoreIdFromWebsiteId($websiteId))
                ->setCustomerId($contactModel->getCustomerId());

            $this->contactResource->save($newContactModel);
        }
    }

    /**
     * Update row for matching customers.
     *
     * @param Contact $contactModel
     * @param string|int $websiteId
     *
     * @return Contact
     * @throws AlreadyExistsException
     */
    private function updateRowForMatchingCustomer(Contact $contactModel, $websiteId)
    {
        $contactModel->setWebsiteId($websiteId);
        $contactModel->setStoreId($this->getStoreIdFromWebsiteId($websiteId));
        return $contactModel;
    }

    /**
     * Check for email update.
     *
     * @param Contact $contactModel
     * @param string $newEmail
     *
     * @return Contact
     * @throws AlreadyExistsException|\Exception
     */
    private function checkForEmailUpdate(Contact $contactModel, string $newEmail)
    {
        $emailBefore = $contactModel->getEmail();
        // email change detected
        if ($newEmail != $emailBefore) {
            $contactModel->setEmail($newEmail);

            $data = [
                'emailBefore' => $emailBefore,
                'email' => $newEmail
            ];

            $this->importerFactory->create()
                ->registerQueue(
                    Importer::IMPORT_TYPE_CONTACT_UPDATE,
                    $data,
                    Importer::MODE_CONTACT_EMAIL_UPDATE,
                    $contactModel->getWebsiteId()
                );

            $this->removeAnyNonCustomerRowMatchingNewEmail($newEmail, $contactModel->getWebsiteId());
        }

        return $contactModel;
    }

    /**
     * Check for change of website association.
     *
     * @param Contact $contactModel
     * @param string|int $newWebsiteId
     *
     * @return Contact
     * @throws AlreadyExistsException
     */
    private function checkForWebsiteUpdate(Contact $contactModel, $newWebsiteId)
    {
        $websiteIdBefore = $contactModel->getWebsiteId();
        if ($newWebsiteId != $websiteIdBefore) {
            if ($this->config->isAccountSharingGlobal()) {
                $this->createNewRowForMatchingCustomer($contactModel, $newWebsiteId);
            } else {
                $contactModel = $this->updateRowForMatchingCustomer($contactModel, $newWebsiteId);
            }
        }
        return $contactModel;
    }

    /**
     * Get store id from website id.
     *
     * @param string|int $websiteId
     * @return false|mixed
     */
    private function getStoreIdFromWebsiteId($websiteId)
    {
        $storeIds = $this->storeWebsiteRelation->getStoreByWebsiteId($websiteId);
        return reset($storeIds);
    }

    /**
     * Remove any non-customer matching the updated email.
     *
     * @param string $email
     * @param string|int $websiteId
     *
     * @return void
     * @throws \Exception
     */
    private function removeAnyNonCustomerRowMatchingNewEmail($email, $websiteId)
    {
        $orphaned = $this->contactCollectionFactory->create()
            ->loadNonCustomerByEmailAndWebsiteId(
                $email,
                $websiteId
            );

        if ($orphaned->getSize()) {
            /** @var Contact $row */
            $row = $orphaned->getFirstItem();
            $this->contactResource->delete($row);
        }
    }
}
