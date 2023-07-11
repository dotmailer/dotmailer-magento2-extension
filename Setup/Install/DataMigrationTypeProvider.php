<?php

namespace Dotdigitalgroup\Email\Setup\Install;

use Dotdigitalgroup\Email\Setup\SchemaInterface;

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
     * @var Type\UpdateEmailContactTableGuestSales
     */
    private $updateEmailContactTableGuestSales;

    /**
     * @var array
     */
    private array $enabledMigrationTypes;

    /**
     * @param Type\InsertEmailContactTableCustomers $insertEmailContactTableCustomers
     * @param Type\InsertEmailContactTableSubscribers $insertEmailContactTableSubscribers
     * @param Type\InsertEmailContactTableCustomerSales $insertEmailContactTableCustomerSales
     * @param Type\UpdateContactsWithSubscriberCustomers $updateContactsWithSubscriberCustomers
     * @param Type\InsertEmailOrderTable $insertEmailOrderTable
     * @param Type\InsertEmailReviewTable $insertEmailReviewTable
     * @param Type\InsertEmailWishlistTable $insertEmailWishlistTable
     * @param Type\InsertEmailCatalogTable $insertEmailCatalogTable
     * @param Type\UpdateEmailContactTableCustomerSales $updateEmailContactTableCustomerSales
     * @param Type\UpdateEmailContactTableGuestSales $updateEmailContactTableGuestSales
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
        Type\UpdateEmailContactTableCustomerSales $updateEmailContactTableCustomerSales,
        Type\UpdateEmailContactTableGuestSales $updateEmailContactTableGuestSales
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
        $this->updateEmailContactTableGuestSales = $updateEmailContactTableGuestSales;
    }

    /**
     * Get types.
     *
     * If migration is being run for a single table, get the related migration types.
     * Otherwise, get all available types.
     *
     * @param string|null $table
     * @param array $afterMigrationTypes
     *
     * @return array
     */
    public function getTypes(string $table = null, array $afterMigrationTypes = [])
    {
        switch ($table) {
            case SchemaInterface::EMAIL_CONTACT_TABLE:
                $types = $this->getContactTypes();
                break;
            case SchemaInterface::EMAIL_CATALOG_TABLE:
                $types = $this->getCatalogTypes();
                break;
            case SchemaInterface::EMAIL_ORDER_TABLE:
                $types = $this->getOrderTypes();
                break;
            case SchemaInterface::EMAIL_REVIEW_TABLE:
                $types = $this->getReviewTypes();
                break;
            case SchemaInterface::EMAIL_WISHLIST_TABLE:
                $types = $this->getWishlistTypes();
                break;
            default:
                $types = $this->getContactTypes() +
                    $this->getCatalogTypes() +
                    $this->getOrderTypes() +
                    $this->getReviewTypes() +
                    $this->getWishlistTypes();
        }

        return $types + $afterMigrationTypes;
    }

    /**
     * Filter all types for those that are enabled.
     *
     * @param string|null $table
     *
     * @return array
     */
    public function getEnabledTypes(string $table = null)
    {
        if (isset($this->enabledMigrationTypes)) {
            return $this->enabledMigrationTypes;
        }
        $this->enabledMigrationTypes = $this->filterTypes($this->getTypes($table));
        return $this->enabledMigrationTypes;
    }

    /**
     * Migration types for email_contact.
     *
     * @return array
     */
    public function getContactTypes()
    {
        return [
            'insertEmailContactTableCustomers' => $this->insertEmailContactTableCustomers,
            'insertEmailContactTableSubscribers' => $this->insertEmailContactTableSubscribers,
            'updateContactsWithSubscriberCustomers' => $this->updateContactsWithSubscriberCustomers,
            'insertEmailContactTableCustomerSales' => $this->insertEmailContactTableCustomerSales,
            'updateEmailContactTableCustomerSales' => $this->updateEmailContactTableCustomerSales,
            'updateEmailContactTableGuestSales' => $this->updateEmailContactTableGuestSales
        ];
    }

    /**
     * Migration types for email_order.
     *
     * @return array
     */
    public function getOrderTypes()
    {
        return [
            'insertEmailOrderTable' => $this->insertEmailOrderTable
        ];
    }

    /**
     * Migration types for email_review.
     *
     * @return array
     */
    public function getReviewTypes()
    {
        return [
            'insertEmailReviewTable' => $this->insertEmailReviewTable
        ];
    }

    /**
     * Migration types for email_wishlist.
     *
     * @return array
     */
    public function getWishlistTypes()
    {
        return [
            'insertEmailWishlistTable' => $this->insertEmailWishlistTable
        ];
    }

    /**
     * Migration types for email_catalog.
     *
     * @return array
     */
    public function getCatalogTypes()
    {
        return [
            'insertEmailCatalogTable' => $this->insertEmailCatalogTable
        ];
    }

    /**
     * Filter types by is_enabled flag.
     *
     * @param array $types
     *
     * @return array
     */
    private function filterTypes($types)
    {
        foreach ($types as $key => $type) {
            if (!$type->isEnabled()) {
                unset($types[$key]);
            }
        }

        return $types;
    }
}
