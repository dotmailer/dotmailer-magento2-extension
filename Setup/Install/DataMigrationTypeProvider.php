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
     * Get types associated with this provider
     * @param string|null $table
     * @return array
     * @throws \ErrorException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Statement_Exception
     */
    public function getTypes($table = null)
    {
        return $table ? $this->getTypesFromTable($table) : get_object_vars($this);
    }

    /**
     * @param string|null $table
     * @return mixed
     */
    public function getEnabledTypes($table = null)
    {
        return $this->filterTypes($this->getTypes($table));
    }

    /**
     * @param $table
     * @throws \ErrorException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Statement_Exception
     */
    private function getTypesFromTable($table)
    {
        $types = [];

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
        }

        return $types;
    }

    /**
     * @return array
     */
    public function getContactTypes()
    {
        return [
            $this->insertEmailContactTableCustomers,
            $this->insertEmailContactTableSubscribers,
            $this->updateContactsWithSubscriberCustomers,
            $this->insertEmailContactTableCustomerSales,
            $this->updateEmailContactTableCustomerSales,
        ];
    }

    /**
     * @return Type\InsertEmailOrderTable[]
     */
    public function getOrderTypes()
    {
        return [$this->insertEmailOrderTable];
    }

    /**
     * @return Type\InsertEmailReviewTable[]
     */
    public function getReviewTypes()
    {
        return [$this->insertEmailReviewTable];
    }

    /**
     * @return Type\InsertEmailWishlistTable[]
     */
    public function getWishlistTypes()
    {
        return [$this->insertEmailWishlistTable];
    }

    /**
     * @return Type\InsertEmailCatalogTable[]
     */
    public function getCatalogTypes()
    {
        return [$this->insertEmailCatalogTable];
    }

    /**
     * @param $types
     * @return mixed
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
