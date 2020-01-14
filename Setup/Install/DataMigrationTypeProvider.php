<?php

namespace Dotdigitalgroup\Email\Setup\Install;

use Dotdigitalgroup\Email\Setup\Install\Type;

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
     * DataMigrationTypeProvider constructor
     * @param Type\InsertEmailContactTableCustomers $insertEmailContactTableCustomers
     * @param Type\InsertEmailContactTableSubscribers $insertEmailContactTableSubscribers
     * @param Type\UpdateContactsWithSubscriberCustomers $updateContactsWithSubscriberCustomers
     * @param Type\InsertEmailOrderTable $insertEmailOrderTable
     * @param Type\InsertEmailReviewTable $insertEmailReviewTable
     * @param Type\InsertEmailWishlistTable $insertEmailWishlistTable
     * @param Type\InsertEmailCatalogTable $insertEmailCatalogTable
     */
    public function __construct(
        Type\InsertEmailContactTableCustomers $insertEmailContactTableCustomers,
        Type\InsertEmailContactTableSubscribers $insertEmailContactTableSubscribers,
        Type\UpdateContactsWithSubscriberCustomers $updateContactsWithSubscriberCustomers,
        Type\InsertEmailOrderTable $insertEmailOrderTable,
        Type\InsertEmailReviewTable $insertEmailReviewTable,
        Type\InsertEmailWishlistTable $insertEmailWishlistTable,
        Type\InsertEmailCatalogTable $insertEmailCatalogTable
    ) {
        $this->insertEmailContactTableCustomers = $insertEmailContactTableCustomers;
        $this->insertEmailContactTableSubscribers = $insertEmailContactTableSubscribers;
        $this->updateContactsWithSubscriberCustomers = $updateContactsWithSubscriberCustomers;
        $this->insertEmailOrderTable = $insertEmailOrderTable;
        $this->insertEmailReviewTable = $insertEmailReviewTable;
        $this->insertEmailWishlistTable = $insertEmailWishlistTable;
        $this->insertEmailCatalogTable = $insertEmailCatalogTable;
    }

    /**
     * Get all types associated with this provider
     * @return array
     */
    public function getTypes()
    {
        return get_object_vars($this);
    }
}
