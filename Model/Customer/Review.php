<?php

namespace Dotdigitalgroup\Email\Model\Customer;

/**
 * Transactional data for customer review.
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
     * Review constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
    ) {
        $this->helper       = $data;
        $this->storeManager = $storeManagerInterface;
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
     * Create rating on runtime.
     *
     * @param string $ratingName
     * @param \Dotdigitalgroup\Email\Model\Customer\Review\Rating $rating
     *
     * @return null
     */
    public function createRating($ratingName, $rating)
    {
        $this->$ratingName = $rating->expose();
    }

    /**
     * Set review date.
     *
     * @param string $date
     *
     * @return $this;
     */
    public function setReviewDate($date)
    {
        $this->reviewDate = $date;

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
     * @param string $name
     *
     * @return null
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
     * @param string $sku
     *
     * @return null
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
     * @param string $name
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
     * @param string $name
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
    public function expose()
    {
        return array_diff_key(
            get_object_vars($this),
            array_flip(['storeManager', 'helper'])
        );
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        $properties = array_keys(get_object_vars($this));
        $properties = array_diff($properties, ['storeManager', 'helper']);

        return $properties;
    }
}
