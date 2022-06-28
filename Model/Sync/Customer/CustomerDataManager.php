<?php

namespace Dotdigitalgroup\Email\Model\Sync\Customer;

use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Magento\Customer\Model\ResourceModel\CustomerFactory as CustomerResourceFactory;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;

class CustomerDataManager
{
    /**
     * @var CustomerResourceFactory
     */
    private $customerResourceFactory;

    /**
     * @var ContactCollectionFactory
     */
    private $contactCollectionFactory;

    /**
     * @var CustomerCollectionFactory
     */
    private $customerCollectionFactory;

    /**
     * CustomerDataManager constructor.
     *
     * @param CustomerResourceFactory $customerResourceFactory
     * @param ContactCollectionFactory $contactCollectionFactory
     * @param CustomerCollectionFactory $customerCollectionFactory
     */
    public function __construct(
        CustomerResourceFactory $customerResourceFactory,
        ContactCollectionFactory $contactCollectionFactory,
        CustomerCollectionFactory $customerCollectionFactory
    ) {
        $this->customerResourceFactory = $customerResourceFactory;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->customerCollectionFactory = $customerCollectionFactory;
    }

    /**
     * Initialize collection.
     *
     * @param array $customerIds
     *
     * @return \Magento\Customer\Model\ResourceModel\Customer\Collection
     */
    public function buildCustomerCollection(array $customerIds)
    {
        $customerCollection = $this->customerCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addNameToSelect();

        $this->addBillingJoinAttributesToCustomerCollection($customerCollection);
        $this->addShippingJoinAttributesToCustomerCollection($customerCollection);

        $customerCollection->addAttributeToFilter('entity_id', ['in' => $customerIds]);

        return $customerCollection;
    }

    /**
     * Fetch the store_id for this customer and website.
     * Builds an array with scope values from our table, to be used in createCsvFile().
     * In this way we are overriding the values from customer_entity. This is required when Account Sharing
     * is set to Global, since there is one customer entity with multiple scoped rows in our table.
     *
     * @param array $customerIds
     * @param int $websiteId
     *
     * @return array
     */
    public function setCustomerScopeData(array $customerIds, $websiteId = 0)
    {
        $customerScopeData = [];
        $collection = $this->contactCollectionFactory->create()
            ->getCustomerScopeData($customerIds, $websiteId);

        foreach ($collection->getItems() as $contact) {
            $customerScopeData[$contact->getCustomerId()] = [
                'email_contact_id' => $contact->getId(),
                'website_id' => $websiteId,
                'store_id' => $contact->getStoreId()
            ];
        }

        return $customerScopeData;
    }

    /**
     * Get the last login date from the customer_log table.
     *
     * @param array $customerIds
     * @param array $columns
     *
     * @return array
     */
    public function fetchLastLoggedInDates($customerIds, $columns)
    {
        if (!isset($columns['last_logged_date'])) {
            return [];
        }

        $lastLoggedDates = [];
        $customerResource = $this->customerResourceFactory->create();
        $results = $customerResource->getConnection()
            ->fetchAll(
                $customerResource->getConnection()
                    ->select()
                    ->from(
                        $customerResource->getTable('customer_log'),
                        [
                            'customer_id',
                            'last_login_at'
                        ]
                    )
                    ->where('customer_id IN (?)', $customerIds)
            );

        foreach ($results as $row) {
            $customerId = $row['customer_id'];
            $lastLoggedDates[$customerId]['last_logged_date'] = $row['last_login_at'];
        }

        return $lastLoggedDates;
    }

    /**
     * Include shipping to collection.
     *
     * @param \Magento\Customer\Model\ResourceModel\Customer\Collection $customerCollection
     */
    private function addShippingJoinAttributesToCustomerCollection($customerCollection)
    {
        $customerCollection->joinAttribute(
            'shipping_street',
            'customer_address/street',
            'default_shipping',
            null,
            'left'
        )
            ->joinAttribute(
                'shipping_city',
                'customer_address/city',
                'default_shipping',
                null,
                'left'
            )
            ->joinAttribute(
                'shipping_country_code',
                'customer_address/country_id',
                'default_shipping',
                null,
                'left'
            )
            ->joinAttribute(
                'shipping_postcode',
                'customer_address/postcode',
                'default_shipping',
                null,
                'left'
            )
            ->joinAttribute(
                'shipping_telephone',
                'customer_address/telephone',
                'default_shipping',
                null,
                'left'
            )
            ->joinAttribute(
                'shipping_region',
                'customer_address/region',
                'default_shipping',
                null,
                'left'
            )
            ->joinAttribute(
                'shipping_company',
                'customer_address/company',
                'default_shipping',
                null,
                'left'
            );
    }

    /**
     * Include billing to collection.
     *
     * @param \Magento\Customer\Model\ResourceModel\Customer\Collection $customerCollection
     */
    private function addBillingJoinAttributesToCustomerCollection($customerCollection)
    {
        $customerCollection->joinAttribute(
            'billing_street',
            'customer_address/street',
            'default_billing',
            null,
            'left'
        )
            ->joinAttribute(
                'billing_city',
                'customer_address/city',
                'default_billing',
                null,
                'left'
            )
            ->joinAttribute(
                'billing_country_code',
                'customer_address/country_id',
                'default_billing',
                null,
                'left'
            )
            ->joinAttribute(
                'billing_postcode',
                'customer_address/postcode',
                'default_billing',
                null,
                'left'
            )
            ->joinAttribute(
                'billing_telephone',
                'customer_address/telephone',
                'default_billing',
                null,
                'left'
            )
            ->joinAttribute(
                'billing_region',
                'customer_address/region',
                'default_billing',
                null,
                'left'
            )
            ->joinAttribute(
                'billing_company',
                'customer_address/company',
                'default_billing',
                null,
                'left'
            );
    }
}
