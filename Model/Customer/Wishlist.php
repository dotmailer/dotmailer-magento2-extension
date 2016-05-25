<?php

namespace Dotdigitalgroup\Email\Model\Customer;

class Wishlist
{
    /**
     * @var int
     */
    public $id;
    /**
     * @var int
     */
    public $customerId;
    /**
     * @var string
     */
    public $email;

    /**
     * wishlist items.
     *
     * @var array
     */
    public $items = array();

    /**
     * @var float
     */
    protected $totalWishlistValue;

    /**
     * @var string
     */
    public $updatedAt;

    /**
     * @param $customer
     *
     * @return $this
     */
    public function setCustomer($customer)
    {
        $this->setCustomerId($customer->getId());
        $this->email = $customer->getEmail();

        return $this;
    }

    /**
     * @param mixed $customerId
     */
    public function setCustomerId($customerId)
    {
        $this->customerId = (int)$customerId;
    }

    /**
     * @return mixed
     */
    public function getCustomerId()
    {
        return (int)$this->customerId;
    }

    /**
     * @param $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (int)$id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return (int)$this->id;
    }

    /**
     * Set wishlist item.
     *
     * @param $item
     */
    public function setItem($item)
    {
        $this->items[] = $item->expose();

        $this->totalWishlistValue += $item->getTotalValueOfProduct();
    }

    /**
     * @return array
     */
    public function expose()
    {
        return get_object_vars($this);
    }

    /**
     * Set wishlist date.
     *
     * @param $date
     *
     * @return $this;
     */
    public function setUpdatedAt($date)
    {
        $date = new \Zend_Date($date, \Zend_Date::ISO_8601);

        $this->updatedAt = $date->toString(\Zend_Date::ISO_8601);

        return $this;
    }

    /**
     * @return string
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
}
