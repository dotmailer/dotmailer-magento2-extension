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
    public $items = [];

    /**
     * @var float
     */
    public $totalWishlistValue;

    /**
     * @var string
     */
    public $updatedAt;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    public $localeDate;

    /**
     * Wishlist constructor.
     *
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
    ) {
        $this->localeDate = $localeDate;
    }

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
     *
     * @return $this
     */
    public function setCustomerId($customerId)
    {
        $this->customerId = (int)$customerId;

        return $this;
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
        return array_diff_key(
            get_object_vars($this),
            array_flip(['localeDate'])
        );
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
        $this->updatedAt = $this->localeDate->date($date)->format(\Zend_Date::ISO_8601);

        return $this;
    }

    /**
     * @return string
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set email
     *
     * @param $email
     *
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string[]
     */
    public function __sleep()
    {
        $properties = array_keys(get_object_vars($this));
        $properties = array_diff($properties, ['localeDate']);

        return $properties;
    }

    public function getData()
    {
        return get_object_vars($this);
    }
}
