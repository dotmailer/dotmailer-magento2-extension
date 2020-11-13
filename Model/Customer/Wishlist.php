<?php

namespace Dotdigitalgroup\Email\Model\Customer;

use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Transactional data for customer wishlist.
 */
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
     * Wishlist items.
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
     * @var DateTime
     */
    private $dateTime;

    /**
     * Wishlist constructor.
     * @param DateTime $dateTime
     */
    public function __construct(
        DateTime $dateTime
    ) {
        $this->dateTime = $dateTime;
    }
    /**
     * @param \Magento\Customer\Model\Customer $customer
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
     * @param int $customerId
     *
     * @return $this
     */
    public function setCustomerId($customerId)
    {
        $this->customerId = (int)$customerId;

        return $this;
    }

    /**
     * @return int
     */
    public function getCustomerId()
    {
        return (int)$this->customerId;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (int)$id;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return (int)$this->id;
    }

    /**
     * Set wishlist item.
     *
     * @param \Dotdigitalgroup\Email\Model\Customer\Wishlist\Item $item
     *
     * @return null
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
        $properties = array_diff_key(
            get_object_vars($this),
            array_flip(['dateTime'])
        );

        //remove null/0/false values
        $properties = array_filter($properties);

        return $properties;
    }

    /**
     * Set wishlist date.
     *
     * @param string $date
     *
     * @return $this;
     */
    public function setUpdatedAt($date)
    {
        $this->updatedAt = $this->dateTime->date(\Zend_Date::ISO_8601, $date);

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
     * @param string $email
     *
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return get_object_vars($this);
    }
}
