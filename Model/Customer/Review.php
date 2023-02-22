<?php

namespace Dotdigitalgroup\Email\Model\Customer;

use Dotdigitalgroup\Email\Model\Connector\AbstractConnectorModel;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Magento\Review\Model\Review as MagentoReview;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Transactional data for customer review.
 */
class Review extends AbstractConnectorModel
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
     * @var array
     */
    private $properties = [];

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;

    /**
     * @param StoreManagerInterface $storeManagerInterface
     * @param DateTimeFactory $dateTimeFactory
     */
    public function __construct(
        StoreManagerInterface $storeManagerInterface,
        DateTimeFactory $dateTimeFactory
    ) {
        $this->storeManager = $storeManagerInterface;
        $this->dateTimeFactory = $dateTimeFactory;
    }

    /**
     * Set customer.
     *
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
     * Set customer id.
     *
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
     * Get customer id.
     *
     * @return int
     */
    public function getCustomerId()
    {
        return (int)$this->customerId;
    }

    /**
     * Set id.
     *
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
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return (int)$this->id;
    }

    /**
     * Set a custom property for rating, keyed on the rating code.
     *
     * @param string $ratingCode
     * @param string|int $ratingScore
     *
     * @return void
     */
    public function addRating(string $ratingCode, $ratingScore): void
    {
        $this->properties[$ratingCode] = [
            'ratingScore' => (int) $ratingScore
        ];
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
     * @param MagentoReview $review
     * @return $this
     */
    public function setReviewData(MagentoReview $review)
    {
        $store = $this->storeManager->getStore($review->getStoreId());
        /** @var Store $store */
        $websiteName = $store->getWebsite()->getName();
        $storeName = $store->getName();
        $this->setId($review->getReviewId())
            ->setWebsiteName($websiteName)
            ->setStoreName($storeName)
            ->setReviewDate(
                $this->dateTimeFactory->create()->date(
                    \DateTime::ATOM,
                    $review->getCreatedAt()
                )
            )
            ->setCustomerId($review->getCustomerId())
            ->setEmail($review->getEmail());

        return $this;
    }

    /**
     * Set product name.
     *
     * @param string $name
     *
     * @return void
     */
    public function setProductName($name)
    {
        $this->productName = $name;
    }

    /**
     * Get product name.
     *
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
     * @return void
     */
    public function setProductSku($sku)
    {
        $this->productSku = $sku;
    }

    /**
     * Get product sku.
     *
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
     * Get store name.
     *
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
     * Get website name.
     *
     * @return string
     */
    public function getWebsiteName()
    {
        return $this->websiteName;
    }

    /**
     * Set email.
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
     * __sleep
     *
     * @return array
     */
    public function __sleep()
    {
        $properties = array_keys(get_object_vars($this));
        $properties = array_diff($properties, ['storeManager', 'dateTimeFactory']);

        return $properties;
    }

    /**
     * Returns any additional properties.
     *
     * @return array
     */
    public function getAdditionalProperties()
    {
        return $this->properties;
    }
}
