<?php

namespace Dotdigitalgroup\Email\Setup\Install;

class DataMigrationTypeProvider
{
    /**
     * @var Type\InsertEmailContactTableCustomers
     */
    private $insertEmailContactTableCustomers;

    /**
     * @var Type\InsertEmailContactTableSubscribers
     */
    private $insertEmailContactTableSubscribers;

    /**
     * @var Type\UpdateContactsWithSubscriberCustomers
     */
    private $updateContactsWithSubscriberCustomers;

    /**
     * @var Type\InsertEmailOrderTable
     */
    private $insertEmailOrderTable;

    /**
     * @var Type\InsertEmailReviewTable
     */
    private $insertEmailReviewTable;

    /**
     * @var Type\InsertEmailWishlistTable
     */
    private $insertEmailWishlistTable;

    /**
     * @var Type\InsertEmailCatalogTable
     */
    private $insertEmailCatalogTable;

    /**
     * @var Type\InsertEmailContactTableCustomerSales
     */
    private $insertEmailContactTableCustomerSales;

    /**
     * @var Type\UpdateEmailContactTableCustomerSales
     */
    private $updateEmailContactTableCustomerSales;

    /**
     * DataMigrationTypeProvider constructor
     * @param Type\InsertEmailContactTableCustomers $insertEmailContactTableCustomers
     * @param Type\InsertEmailContactTableSubscribers $insertEmailContactTableSubscribers
     * @param Type\InsertEmailContactTableCustomerSales $insertEmailContactTableCustomerSales
     * @param Type\UpdateContactsWithSubscriberCustomers $updateContactsWithSubscriberCustomers
     * @param Type\InsertEmailOrderTable $insertEmailOrderTable
     * @param Type\InsertEmailReviewTable $insertEmailReviewTable
     * @param Type\InsertEmailWishlistTable $insertEmailWishlistTable
     * @param Type\InsertEmailCatalogTable $insertEmailCatalogTable
     * @param Type\UpdateEmailContactTableCustomerSales $updateEmailContactTableCustomerSales
     */
    public function __construct(
        Type\InsertEmailContactTableCustomers $insertEmailContactTableCustomers,
        Type\InsertEmailContactTableSubscribers $insertEmailContactTableSubscribers,
        Type\InsertEmailContactTableCustomerSales $insertEmailContactTableCustomerSales,
        Type\UpdateContactsWithSubscriberCustomers $updateContactsWithSubscriberCustomers,
        Type\InsertEmailOrderTable $insertEmailOrderTable,
        Type\InsertEmailReviewTable $insertEmailReviewTable,
        Type\InsertEmailWishlistTable $insertEmailWishlistTable,
        Type\InsertEmailCatalogTable $insertEmailCatalogTable,
        Type\UpdateEmailContactTableCustomerSales $updateEmailContactTableCustomerSales
    ) {
        $this->insertEmailContactTableCustomers = $insertEmailContactTableCustomers;
        $this->insertEmailContactTableSubscribers = $insertEmailContactTableSubscribers;
        $this->insertEmailContactTableCustomerSales = $insertEmailContactTableCustomerSales;
        $this->updateContactsWithSubscriberCustomers = $updateContactsWithSubscriberCustomers;
        $this->insertEmailOrderTable = $insertEmailOrderTable;
        $this->insertEmailReviewTable = $insertEmailReviewTable;
        $this->insertEmailWishlistTable = $insertEmailWishlistTable;
        $this->insertEmailCatalogTable = $insertEmailCatalogTable;
        $this->updateEmailContactTableCustomerSales = $updateEmailContactTableCustomerSales;
    }

    /**
     * Get all types associated with this provider
     * @return array
     */
    public function getTypes()
    {
        $types = get_object_vars($this);
        foreach ($types as $key => $type) {
            if (!$type->isEnabled()) {
                unset($types[$key]);
            }
        }

        return $types;
    }
}
