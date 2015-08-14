<?php

class Dotdigitalgroup_Email_Model_Customer_Review
{
    /**
     * @var int
     */
    public  $id;

    /**
     * @var int
     */
    public  $customer_id;

    /**
     * @var string
     */
    public $email;

    /**
     * @var string
     */
    public $product_name;

    /**
     * @var string
     */
    public $product_sku;

    /**
     * @var string
     */
    public $review_date;

    /**
     * @var string
     */
    public $website_name;

    /**
     * @var string
     */
    public $store_name;


    /**
     * constructor.
     *
     * @param Mage_Customer_Model_Customer $customer
     */
    public function __construct(Mage_Customer_Model_Customer $customer)
    {

        $this->setCustomerId($customer->getId());
        $this->email = $customer->getEmail();
    }

    /**
     * @param mixed $customer_id
     */
    public function setCustomerId($customer_id)
    {
        $this->customer_id = (int) $customer_id;
    }

    /**
     * @return mixed
     */
    public function getCustomerId()
    {
        return (int) $this->customer_id;
    }

    /**
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (int) $id;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return (int) $this->id;
    }

    /**
     * create rating on runtime
     *
     * @param $rating_name
     * @param $rating
     */
    public function createRating($rating_name, $rating)
    {
        $this->$rating_name = $rating->expose();
    }

    /**
     * set review date.
     *
     * @param $date
     * @return $this;
     */
    public function setReviewDate($date)
    {
        $created_at = new Zend_Date($date, Zend_Date::ISO_8601);

        $this->review_date = $created_at->toString(Zend_Date::ISO_8601);;
        return $this;
    }

    /**
     * @return string
     */
    public function getReviewDate()
    {
        return $this->review_date;
    }

    /**
     * set product
     *
     * @param Mage_Catalog_Model_Product $product
     * @return $this
     */
    public function setProduct(Mage_Catalog_Model_Product $product)
    {
        $this->setProductName($product->getName());
        $this->setProductSku($product->getSku());
        return $this;
    }

    /**
     * set review data
     *
     * @param Mage_Review_Model_Review $review $product
     * @return $this
     */
    public function setReviewData(Mage_Review_Model_Review $review)
    {
        $store = Mage::app()->getStore($review->getStoreId());
        $websiteName = $store->getWebsite()->getName();
        $storeName = $store->getName();
        $this->setId($review->getReviewId())
            ->setWebsiteName($websiteName)
            ->setStoreName($storeName)
            ->setReviewDate($review->getCreatedAt());

        return $this;
    }

    /**
     * set product name
     *
     * @param $name
     */
    public function setProductName($name)
    {
        $this->product_name = $name;
    }

    /**
     * @return string
     */
    public function getProductName()
    {
        return $this->product_name;
    }

    /**
     * set product sku
     *
     * @param $sku
     */
    public function setProductSku($sku)
    {
        $this->product_sku = $sku;
    }

    /**
     * @return string
     */
    public function getProductSku()
    {
        return $this->product_sku;
    }

    /**
     * set website name
     *
     * @param $name
     * @return $this
     */
    public function setWebsiteName($name)
    {
        $this->website_name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getStoreName()
    {
        return $this->store_name;
    }

    /**
     * set store name
     *
     * @param $name
     * @return $this
     */
    public function setStoreName($name)
    {
        $this->store_name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getWebsiteName()
    {
        return $this->website_name;
    }

    /**
     * @return array
     */
    public function expose()
    {
        return get_object_vars($this);
    }
}