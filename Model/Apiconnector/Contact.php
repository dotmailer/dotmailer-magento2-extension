<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector;

use Dotdigitalgroup\Email\Model\Sync\SyncInterface;

/**
 * manages the sync of dotmailer Contact.
 */
class Contact implements SyncInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Contact
     */
    private $contactResource;

    /**
     * @var mixed
     */
    private $start;

    /**
     * @var int
     */
    private $countCustomers = 0;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Dotdigitalgroup\Email\Model\Apiconnector\Customer
     */
    private $emailCustomer;

    /**
     * @var \Dotdigitalgroup\Email\Helper\File
     */
    private $file;

    /**
     * @var \Dotdigitalgroup\Email\Model\Apiconnector\ContactImportQueueExport
     */
    public $contactImportQueueExport;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory
     */
    private $contactCollectionFactory;

    /**
     * Contact constructor.
     *
     * @param CustomerFactory $customerFactory
     * @param \Dotdigitalgroup\Email\Helper\File $file
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource
     * @param ContactImportQueueExport $contactImportQueueExport
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory $contactCollectionFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Apiconnector\CustomerFactory $customerFactory,
        \Dotdigitalgroup\Email\Helper\File $file,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource,
        \Dotdigitalgroup\Email\Model\Apiconnector\ContactImportQueueExport $contactImportQueueExport,
        \Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory $contactCollectionFactory
    ) {
        $this->file = $file;
        $this->helper = $helper;
        //email contact
        $this->emailCustomer = $customerFactory;
        $this->contactResource = $contactResource;
        $this->contactImportQueueExport = $contactImportQueueExport;
        $this->contactCollectionFactory = $contactCollectionFactory;
    }

    /**
     * Contact sync.
     *
     * @return array
     */
    public function sync(\DateTime $from = null)
    {
        //result message
        $result = ['success' => true, 'message' => ''];
        //starting time for sync
        $this->start = microtime(true);
        //export bulk contacts
        foreach ($this->helper->getWebsites() as $website) {
            $apiEnabled = $this->helper->isEnabled($website);
            $customerSyncEnabled = $this->helper->isCustomerSyncEnabled(
                $website
            );
            $customerAddressBook = $this->helper->getCustomerAddressBook(
                $website
            );

            //api, customer sync and customer address book must be enabled
            if ($apiEnabled && $customerSyncEnabled && $customerAddressBook) {
                //start log
                $contactsUpdated = $this->exportCustomersForWebsite($website);

                // show message for any number of customers
                if ($contactsUpdated) {
                    $result['message'] .=  $website->getName()
                        . ', updated contacts ' . $contactsUpdated;
                }
            }
        }
        //sync processed
        $message = '----------- Customer sync ----------- : '
            . gmdate('H:i:s', microtime(true) - $this->start)
            . ', Total contacts = ' . $this->countCustomers;

        if ($this->countCustomers) {
            $this->helper->log($message);
        }

        $result['message'] .= $message;

        return $result;
    }

    /**
     * @param \Magento\Store\Api\Data\WebsiteInterface $website
     *
     * @return int
     */
    public function exportCustomersForWebsite(\Magento\Store\Api\Data\WebsiteInterface $website)
    {
        $allMappedHash = [];
        //admin sync limit of batch size for contacts
        $syncLimit = $this->helper->getSyncLimit($website);
        //address book id mapped
        $customerAddressBook = $this->helper->getCustomerAddressBook($website);

        //skip website if address book not mapped
        if (!$customerAddressBook) {
            return 0;
        }

        $onlySubscribers = $this->helper->isOnlySubscribersForContactSync($website->getId());
        $contacts = $this->contactCollectionFactory->create();
        $contacts = ($onlySubscribers) ? $contacts->getContactsToImportByWebsite($website->getId(), $syncLimit, true) :
            $contacts->getContactsToImportByWebsite($website->getId(), $syncLimit);

        // no contacts found
        if (!$contacts->getSize()) {
            return 0;
        }
        //customer filename
        $customersFile = strtolower(
            $website->getCode() . '_customers_' . date('d_m_Y_His') . '.csv'
        );
        $this->helper->log('Customers file : ' . $customersFile);
        //get customers ids
        $customerIds = $contacts->getColumnValues('customer_id');
        /*
         * HEADERS.
         */
        $mappedHash = $this->helper->getWebsiteCustomerMappingDatafields(
            $website
        );
        $headers = $mappedHash;

        //custom customer attributes
        $customAttributes = $this->helper->getCustomAttributes($website);

        foreach ($customAttributes ?: [] as $data) {
            $headers[] = $data['datafield'];
            $allMappedHash[$data['attribute']] = $data['datafield'];
        }
        $headers[] = 'Email';
        $headers[] = 'EmailType';

        $this->file->outputCSV(
            $this->file->getFilePath($customersFile),
            $headers
        );
        /*
         * END HEADERS.
         */

        //customer collection
        $customerCollection = $this->contactResource->buildCustomerCollection($customerIds);

        //Customer sales data
        $salesData = $this->getCustomerSalesData($customerIds, $website->getId());

        $this->createCsvFile(
            $customerCollection,
            $mappedHash,
            $customAttributes,
            $customersFile,
            $salesData
        );

        $customerNum = count($customerIds);
        $this->helper->log(
            'Website : ' . $website->getName() . ', customers = ' . $customerNum .
            ', execution time :' . gmdate('H:i:s', microtime(true) - $this->start)
        );

        //file was created - continue to queue the export
        $this->contactImportQueueExport->enqueueForExport(
            $website,
            $customersFile,
            $customerNum,
            $customerIds,
            $this->contactResource
        );

        $this->countCustomers += $customerNum;

        return $customerNum;
    }

    /**
     * @param \Magento\Customer\Model\ResourceModel\Customer\Collection $customerCollection
     * @param array $mappedHash
     * @param array $customAttributes
     * @param string $customersFile
     * @param array $salesData
     */
    private function createCsvFile(
        $customerCollection,
        $mappedHash,
        $customAttributes,
        $customersFile,
        $salesData
    ) {
        foreach ($customerCollection as $customer) {
            if (isset($salesData[$customer->getId()])) {
                $customer = $this->setSalesDataOnCustomer($salesData[$customer->getId()], $customer);
            }
            $connectorCustomer = $this->emailCustomer->create();
            $connectorCustomer->setMappingHash($mappedHash);
            $connectorCustomer->setContactData($customer);

            if ($connectorCustomer) {
                foreach ($customAttributes ?: [] as $data) {
                    $attribute = $data['attribute'];
                    $value = $customer->getData($attribute);
                    $connectorCustomer->setData($value);
                }
            }

            //contact email and email type
            $connectorCustomer->setData($customer->getEmail());
            $connectorCustomer->setData('Html');

            // save csv file data for customers
            $this->file->outputCSV(
                $this->file->getFilePath($customersFile),
                $connectorCustomer->toCSVArray()
            );

            //clear collection and free memory
            $customer->clearInstance();
        }
    }

    /**
     * @param array $salesData
     * @param \Magento\Customer\Model\Customer $customer
     *
     * @return \Magento\Customer\Model\Customer
     */
    private function setSalesDataOnCustomer($salesData, $customer)
    {
        foreach ($salesData as $column => $value) {
            $customer->setData($column, $value);
        }
        return $customer;
    }

    /**
     * @param array $customerIds
     * @param int $websiteId
     *
     * @return array
     */
    private function getCustomerSalesData($customerIds, $websiteId = 0)
    {
        $statuses = $this->helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_DATA_FIELDS_STATUS,
            $websiteId
        );
        $statuses = explode(',', $statuses);
        return $this->contactResource
            ->getSalesDataForCustomersWithOrderStatusesAndBrand($customerIds, $statuses);
    }
}
