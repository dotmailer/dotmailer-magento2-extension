<?php

namespace Dotdigitalgroup\Email\Model\Sync\Customer;

use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Magento\Customer\Model\ResourceModel\CustomerFactory as CustomerResourceFactory;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Review\Model\ResourceModel\ReviewFactory as ReviewResourceFactory;

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
     * @var ReviewResourceFactory
     */
    private $reviewResourceFactory;

    /**
     * CustomerDataManager constructor.
     *
     * @param CustomerResourceFactory $customerResourceFactory
     * @param ContactCollectionFactory $contactCollectionFactory
     * @param CustomerCollectionFactory $customerCollectionFactory
     * @param ReviewResourceFactory $reviewResourceFactory
     */
    public function __construct(
        CustomerResourceFactory $customerResourceFactory,
        ContactCollectionFactory $contactCollectionFactory,
        CustomerCollectionFactory $customerCollectionFactory,
        ReviewResourceFactory $reviewResourceFactory
    ) {
        $this->customerResourceFactory = $customerResourceFactory;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->reviewResourceFactory = $reviewResourceFactory;
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
     * Fetch review data by store.
     *
     * We fetch the count and the most recent review date for each customer and store,
     * and set the data fields in ContactData\Customer based on the customer's store id.
     *
     * @param array $customerIds
     * @param array $columns
     *
     * @return array
     */
    public function fetchReviewData(array $customerIds, array $columns)
    {
        if (!isset($columns['review_count']) && !isset($columns['last_review_date'])) {
            return [];
        }

        $reviewData = [];
        $reviewResource = $this->reviewResourceFactory->create();
        $results = $reviewResource->getConnection()
            ->fetchAll(
                $reviewResource->getConnection()
                    ->select()
                    ->from(
                        $reviewResource->getTable('review'),
                        [
                            'detail.customer_id',
                            'detail.store_id',
                            'COUNT(review.review_id) AS review_count',
                            'MAX(review.created_at) AS last_review_date'
                        ]
                    )
                    ->join(
                        ['detail' => $reviewResource->getTable('review_detail')],
                        'review.review_id = detail.review_id',
                        ['customer_id', 'store_id']
                    )
                    ->where('customer_id IN (?)', $customerIds)
                    ->group('customer_id')
                    ->group('store_id')
            );

        foreach ($results as $row) {
            $customerId = $row['customer_id'];
            $storeId = $row['store_id'];
            $reviewData[$customerId]['review_data'][$storeId] = [
                'review_count' => $row['review_count'],
                'last_review_date' => $row['last_review_date']
            ];
        }

        return $reviewData;
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
