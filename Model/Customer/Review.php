<?php

namespace Dotdigitalgroup\Email\Model\Customer;

/**
 * Class Review
 * @package Dotdigitalgroup\Email\Model\Customer
 */
class Review
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
     * @var string
     */
    public $productName;

    /**
     * @var string
     */
    public $productSku;

    /**
     * @var string
     */
    public $reviewDate;

    /**
     * @var string
     */
    public $websiteName;

    /**
     * @var string
     */
    public $storeName;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    public $localeDate;

    /**
     * Review constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
    ) {
        $this->helper       = $data;
        $this->storeManager = $storeManagerInterface;
        $this->localeDate   = $localeDate;
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
     * @param $customerId
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
     * Create rating on runtime.
     *
     * @param $ratingName
     * @param $rating
     */
    public function createRating($ratingName, $rating)
    {
        $this->$ratingName = $rating->expose();
    }

    /**
     * Set review date.
     *
     * @param $date
     *
     * @return $this;
     */
    public function setReviewDate($date)
    {
        $this->reviewDate = $this->localeDate->date($date)->format(\Zend_Date::ISO_8601);

        return $this;
    }

    /**
     * @return string
     */
    public function getReviewDate()
    {
        return $this->reviewDate;
    }

    /**
     * Set product.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return $this
     */
    public function setProduct(\Magento\Catalog\Model\Product $product)
    {
        $this->setProductName($product->getName());
        $this->setProductSku($product->getSku());

        return $this;
    }

    /**
     * Set review data.
     *
     * @param \Magento\Review\Model\Review $review
     * @return $this
     */
    public function setReviewData(\Magento\Review\Model\Review $review)
    {
        $store = $this->storeManager->getStore($review->getStoreId());
        $websiteName = $store->getWebsite()->getName();
        $storeName = $store->getName();
        $this->setId($review->getReviewId())
            ->setWebsiteName($websiteName)
            ->setStoreName($storeName)
            ->setReviewDate($review->getCreatedAt())
            ->setCustomerId($review->getCustomerId())
            ->setEmail($review->getEmail());

        return $this;
    }

    /**
     * Set product name.
     *
     * @param $name
     */
    public function setProductName($name)
    {
        $this->productName = $name;
    }

    /**
     * @return string
     */
    public function getProductName()
    {
        return $this->productName;
    }

    /**
     * Set product sku.
     *
     * @param $sku
     */
    public function setProductSku($sku)
    {
        $this->productSku = $sku;
    }

    /**
     * @return string
     */
    public function getProductSku()
    {
        return $this->productSku;
    }

    /**
     * Set website name.
     *
     * @param $name
     *
     * @return $this
     */
    public function setWebsiteName($name)
    {
        $this->websiteName = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getStoreName()
    {
        return $this->storeName;
    }

    /**
     * Set store name.
     *
     * @param $name
     *
     * @return $this
     */
    public function setStoreName($name)
    {
        $this->storeName = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getWebsiteName()
    {
        return $this->websiteName;
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
     * @return array
     */
    public function expose()
    {
        return array_diff_key(
            get_object_vars($this),
            array_flip(['storeManager', 'helper', 'localeDate'])
        );
    }

    /**
     * @return string[]
     */
    public function __sleep()
    {
        $properties = array_keys(get_object_vars($this));
        $properties = array_diff($properties, ['storeManager', 'helper', 'localeDate']);

        return $properties;
    }
}
