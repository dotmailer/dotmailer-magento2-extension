<?php

namespace Dotdigitalgroup\Email\Observer\Customer;

use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

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
     * @var \Magento\Framework\Stdlib\DateTime
     */
    private $dateTime;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * CreateUpdateContact constructor.
     * @param \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory
     * @param CollectionFactory $contactCollectionFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
        CollectionFactory $contactCollectionFactory,
        \Magento\Framework\Registry $registry,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource,
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        StoreManagerInterface $storeManager
    ) {
        $this->contactFactory = $contactFactory;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->contactResource = $contactResource;
        $this->helper = $data;
        $this->registry = $registry;
        $this->importerFactory = $importerFactory;
        $this->dateTime = $dateTime;
        $this->storeManager = $storeManager;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $storeManagerWebsiteId = $this->storeManager->getWebsite()->getId();
        $customer = $observer->getEvent()->getCustomer();
        $email = $customer->getEmail();
        $customerId = $customer->getEntityId();

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

            // Create
            if ($matchingCustomers->getSize() == 0) {
                // Contact exists, is not yet a customer
                $contactModel = $this->contactCollectionFactory->create()
                    ->loadByCustomerEmail($email, $customer->getWebsiteId());

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

            // Update matching customers
            foreach ($matchingCustomers as $contactModel) {
                $emailBefore = $contactModel->getEmail();
                // email change detected
                if ($email != $emailBefore) {
                    $contactModel->setEmail($email);

                    $data = [
                        'emailBefore' => $emailBefore,
                        'email' => $email
                    ];

                    $this->importerFactory->create()
                        ->registerQueue(
                            Importer::IMPORT_TYPE_CONTACT_UPDATE,
                            $data,
                            Importer::MODE_CONTACT_EMAIL_UPDATE,
                            $contactModel->getWebsiteId()
                        );
                }

                $contactModel->setEmailImported(Contact::EMAIL_CONTACT_NOT_IMPORTED);
                $this->contactResource->save($contactModel);
            }
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }

        return $this;
    }
}
