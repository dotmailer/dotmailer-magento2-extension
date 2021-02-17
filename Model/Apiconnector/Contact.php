<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector;

use Dotdigitalgroup\Email\Model\Sync\SyncInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Customer\Model\ResourceModel\Customer\Collection as CustomerCollection;

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
     * @var CustomerDataFieldProviderFactory
     */
    private $customerDataFieldProviderFactory;

    /**
     * @var array
     */
    private $customerScopeData = [];

    /**
     * @var array
     */
    private static $emailFields = [
        'email' => 'Email',
        'email_type' => 'EmailType',
    ];

    /**
     * Contact constructor.
     *
     * @param CustomerFactory $customerFactory
     * @param \Dotdigitalgroup\Email\Helper\File $file
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource
     * @param ContactImportQueueExport $contactImportQueueExport
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory $contactCollectionFactory
     * @param CustomerDataFieldProviderFactory $customerDataFieldProviderFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Apiconnector\CustomerFactory $customerFactory,
        \Dotdigitalgroup\Email\Helper\File $file,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource,
        \Dotdigitalgroup\Email\Model\Apiconnector\ContactImportQueueExport $contactImportQueueExport,
        \Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory $contactCollectionFactory,
        CustomerDataFieldProviderFactory $customerDataFieldProviderFactory
    ) {
        $this->file = $file;
        $this->helper = $helper;
        $this->emailCustomer = $customerFactory;
        $this->contactResource = $contactResource;
        $this->contactImportQueueExport = $contactImportQueueExport;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->customerDataFieldProviderFactory = $customerDataFieldProviderFactory;
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
            $customerSyncEnabled = $this->helper->isCustomerSyncEnabled($website);
            $customerAddressBook = $this->helper->getCustomerAddressBook($website);

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
     * @param WebsiteInterface $website
     * @return int
     */
    public function exportCustomersForWebsite(WebsiteInterface $website)
    {
        $contacts = $this->getContacts($website);

        // no contacts found
        if ($contacts->getSize() === 0) {
            return 0;
        }

        //customer filename
        $customersFile = strtolower(
            $website->getCode() . '_customers_' . date('d_m_Y_His') . '.csv'
        );
        $this->helper->log('Customers file : ' . $customersFile);

        // get customer IDs, custom attributes and generate export data columns
        $customerIds = $contacts->getColumnValues('customer_id');
        $columns = $this->getContactExportColumns($website);

        //customer collection
        $customerCollection = $this->contactResource->buildCustomerCollection($customerIds);

        // Set website_id and store_id as per our table
        $this->setCustomerScopeData($customerIds, $website->getId());

        //Customer sales data
        $additionalCustomerData = $this->getCustomerSalesData($customerIds, $website);

        $this->createCsvFile(
            $customerCollection,
            $columns,
            $additionalCustomerData,
            $customersFile
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
     * @param WebsiteInterface $website
     * @return \Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection
     */
    public function getContacts(WebsiteInterface $website)
    {
        $syncLimit = $this->helper->getSyncLimit($website);
        return $this->contactCollectionFactory->create()
            ->getContactsToImportByWebsite(
                $website->getId(),
                $syncLimit,
                $this->helper->isOnlySubscribersForContactSync($website->getId())
            );
    }

    /**
     * Get fields to be exported
     *
     * @param WebsiteInterface $website
     * @return array
     */
    public function getContactExportColumns(WebsiteInterface $website)
    {
        $customerDataFields = $this->customerDataFieldProviderFactory
            ->create(['data' => ['website' => $website]])
            ->getCustomerDataFields();

        $customAttributes = $this->helper->getCustomAttributes($website);

        return self::$emailFields
            + $customerDataFields
            + array_combine(
                array_column($customAttributes, 'attribute'),
                array_column($customAttributes, 'datafield')
            );
    }

    /**
     * @param CustomerCollection $customerCollection
     * @param array $columns
     * @param array $additionalCustomerData
     * @param string $customersFile
     */
    private function createCsvFile(
        CustomerCollection $customerCollection,
        array $columns,
        array $additionalCustomerData,
        string $customersFile
    ) {
        // write headings row
        $this->file->outputCSV($this->file->getFilePath($customersFile), $columns);

        foreach ($customerCollection as $customer) {
            if (isset($this->customerScopeData[$customer->getId()])) {
                $customer->setData('website_id', $this->customerScopeData[$customer->getId()]['website_id']);
                $customer->setData('store_id', $this->customerScopeData[$customer->getId()]['store_id']);
            }

            if (isset($additionalCustomerData[$customer->getId()])) {
                $this->setAdditionalDataOnCustomer(
                    $customer,
                    $additionalCustomerData[$customer->getId()]
                );
            }

            $connectorCustomer = $this->emailCustomer->create()
                ->init($customer, $columns);

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
     * @param \Magento\Customer\Model\Customer $customer
     * @param array $data
     */
    private function setAdditionalDataOnCustomer(\Magento\Customer\Model\Customer $customer, array $data)
    {
        foreach ($data as $column => $value) {
            $customer->setData($column, $value);
        }
    }

    /**
     * @param array $customerIds
     * @param WebsiteInterface $website
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getCustomerSalesData(array $customerIds, $website)
    {
        $statuses = $this->helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_DATA_FIELDS_STATUS,
            $website->getId()
        );

        return $this->contactResource->getSalesDataForCustomersWithOrderStatusesAndBrand(
            $customerIds,
            explode(',', $statuses),
            $website->getStoreIds()
        );
    }

    /**
     * Fetch the store_id for this customer and website.
     * Builds an array with scope values from our table, to be used in createCsvFile().
     * In this way we are overriding the values from customer_entity. This is required when Account Sharing
     * is set to Global, since there is one customer entity with multiple scoped rows in our table.
     *
     * @param array $customerIds
     * @param int $websiteId
     */
    private function setCustomerScopeData(array $customerIds, $websiteId = 0)
    {
        $collection = $this->contactCollectionFactory->create()
            ->getCustomerScopeData($customerIds, $websiteId);

        foreach ($collection->getItems() as $contact) {
            $this->customerScopeData[$contact->getCustomerId()] = [
                'website_id' => $websiteId,
                'store_id' => $contact->getStoreId()
            ];
        }
    }
}
