<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector;

/**
 * manages the Customer data as datafields for contact.
 */
class Customer
{
    /**
     * @var
     */
    public $customer;
    /**
     * @var
     */
    public $customerData;
    /**
     * @var \Magento\Review\Model\ResourceModel\Review\Collection
     */
    public $reviewCollection;

    /**
     * @var
     */
    public $rewardCustomer;
    /**
     * @var string
     */
    public $rewardLastSpent = '';
    /**
     * @var string
     */
    public $rewardLastEarned = '';
    /**
     * @var string
     */
    public $rewardExpiry = '';

    /**
     * @var
     */
    public $mappingHash;
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;
    /**
     * @var \Magento\Customer\Model\GroupFactory
     */
    public $groupFactory;
    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    public $subscriberFactory;
    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    public $categoryFactory;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    public $productFactory;
    /**
     * @var
     */
    public $reward;

    /**
     * @var array
     */
    public $subscriberStatus
        = [
            \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED => 'Subscribed',
            \Magento\Newsletter\Model\Subscriber::STATUS_NOT_ACTIVE => 'Not Active',
            \Magento\Newsletter\Model\Subscriber::STATUS_UNSUBSCRIBED => 'Unsubscribed',
            \Magento\Newsletter\Model\Subscriber::STATUS_UNCONFIRMED => 'Unconfirmed',
        ];

    /**
     * Customer constructor.
     *
     * @param \Magento\Store\Model\StoreManagerInterface                 $storeManager
     * @param \Magento\Framework\Stdlib\DateTime                         $dateTime
     * @param \Magento\Framework\ObjectManagerInterface                  $objectManager
     * @param \Magento\Review\Model\ResourceModel\Review\Collection      $reviewCollection
     * @param \Dotdigitalgroup\Email\Helper\Data                         $helper
     * @param \Magento\Customer\Model\GroupFactory                       $groupFactory
     * @param \Magento\Newsletter\Model\SubscriberFactory                $subscriberFactory
     * @param \Magento\Catalog\Model\CategoryFactory                     $categoryFactory
     * @param \Magento\Catalog\Model\ProductFactory                      $productFactory
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Review\Model\ResourceModel\Review\Collection $reviewCollection,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Customer\Model\GroupFactory $groupFactory,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory
    ) {
        $this->dateTime          = $dateTime;
        $this->_objectManager    = $objectManager;
        $this->helper            = $helper;
        $this->_store            = $storeManager;
        $this->reviewCollection  = $reviewCollection;
        $this->groupFactory      = $groupFactory;
        $this->subscriberFactory = $subscriberFactory;
        $this->categoryFactory   = $categoryFactory;
        $this->productFactory    = $productFactory;
    }

    /**
     * Set key value data.
     *
     * @param $data
     */
    public function setData($data)
    {
        $this->customerData[] = $data;
    }

    /**
     * Set customer data.
     *
     * @param $customer
     *
     * @return $this
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setCustomerData($customer)
    {
        $this->customer = $customer;
        $this->setReviewCollection();

        foreach ($this->getMappingHash() as $key => $field) {
            /*
             * call user function based on the attribute mapped.
             */
            $function = 'get';
            $exploded = explode('_', $key);
            foreach ($exploded as $one) {
                $function .= ucfirst($one);
            }
            try {
                //@codingStandardsIgnoreStart
                $value = call_user_func(
                    ['self', $function]
                );
                //@codingStandardsIgnoreEnd
                $this->customerData[$key] = $value;
            } catch (\Exception $e) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __($e->getMessage())
                );
            }
        }

        return $this;
    }

    /**
     * Customer reviews.
     *
     * @return $this
     */
    public function setReviewCollection()
    {
        $customerId = $this->customer->getId();
        $collection = $this->reviewCollection->addCustomerFilter($customerId)
            ->setOrder('review_id', 'DESC');

        $this->reviewCollection = $collection;

        return $this;
    }

    /**
     * Get customer id.
     *
     * @return mixed
     */
    public function getCustomerId()
    {
        return $this->customer->getId();
    }

    /**
     * @return mixed
     */
    public function getMappingHash()
    {
        return $this->mappingHash;
    }

    /**
     * mapping hash value.
     *
     * @param $value
     *
     * @return $this
     */
    public function setMappigHash($value)
    {
        $this->mappingHash = $value;

        return $this;
    }

    public function cleanString($string)
    {
        $cleanedString = preg_replace('/[^0-9]/', '', $string);
        if ($cleanedString != '') {
            return (int)number_format($cleanedString, 0, '.', '');
        }

        return 0;
    }

    /**
     * Subscriber status for Customer.
     *
     * @return mixed
     */
    public function getSubscriberStatus()
    {
        $subscriberModel = $this->subscriberFactory->create()
            ->loadByCustomerId($this->customer->getId());

        if ($subscriberModel->getCustomerId()) {
            return $this->subscriberStatus[$subscriberModel->getSubscriberStatus()];
        }
    }

    /**
     * Last used reward points.
     *
     * @return mixed
     */
    public function getLastUsedDate()
    {
        //last used from the reward history based on the points delta used
        //enterprise module
        //@codingStandardsIgnoreStart
        $lastUsed = $this->historyFactory->create()
            ->addCustomerFilter($this->customer->getId())
            ->addWebsiteFilter($this->customer->getWebsiteId())
            ->addFieldToFilter('points_delta', ['lt' => 0])
            ->setDefaultOrder()
            ->setPageSize(1)
            ->getFirstItem()
            ->getCreatedAt();
        //@codingStandardsIgnoreEnd
        //for any valid date
        if ($lastUsed) {
            return $this->helper->formatDate($lastUsed, 'short', true);
        }

        return '';
    }

    /**
     * Get most purchased category.
     *
     * @return string
     */
    public function getMostPurCategory()
    {
        $id = $this->customer->getMostCategoryId();
        if ($id) {
            return $this->categoryFactory->create()->load($id)
                ->setStoreId($this->customer->getStoreId())
                ->getName();
        }

        return '';
    }

    /**
     * Get most purchased brand.
     *
     * @return string
     */
    public function getMostPurBrand()
    {
        $brand = $this->customer->getMostBrand();
        if ($brand) {
            return $brand;
        }

        return '';
    }

    /**
     * Get most frequent day of purchase.
     *
     * @return string
     */
    public function getMostFreqPurDay()
    {
        $day = $this->customer->getWeekDay();
        if ($day) {
            return $day;
        }

        return '';
    }

    /**
     * Get most frequent month of purchase.
     *
     * @return string
     */
    public function getMostFreqPurMon()
    {
        $month = $this->customer->getMonthDay();
        if ($month) {
            return $month;
        }

        return '';
    }

    /**
     * Get first purchased category.
     *
     * @return string
     */
    public function getFirstCategoryPur()
    {
        $id = $this->customer->getFirstCategoryId();
        if ($id) {
            return $this->categoryFactory->create()->load($id)
                ->setStoreId($this->customer->getStoreId())
                ->getName();
        }

        return '';
    }

    /**
     * Get last purchased category.
     *
     * @return string
     */
    public function getLastCategoryPur()
    {
        $categoryId = $this->customer->getLastCategoryId();
        //customer last category id
        if ($categoryId) {
            return $this->categoryFactory->create()
                ->setStoreId($this->customer->getStoreId())
                ->load($categoryId)
                ->getName();
        }

        return '';
    }

    /**
     * Get first purchased brand.
     *
     * @return string
     */
    public function getFirstBrandPur()
    {
        $id = $this->customer->getProductIdForFirstBrand();

        return $this->_getBrandValue($id);
    }

    /**
     * Get last purchased brand.
     *
     * @return string
     */
    public function getLastBrandPur()
    {
        $id = $this->customer->getProductIdForLastBrand();

        return $this->_getBrandValue($id);
    }

    public function _getBrandValue($id)
    {
        //attribute mapped from the config
        $attribute = $this->helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_DATA_FIELDS_BRAND_ATTRIBUTE,
            $this->customer->getWebsiteId()
        );
        //if the id and attribute found
        if ($id && $attribute) {
            $brand = $this->productFactory->create()
                ->setStoreId($this->customer->getStoreId())
                ->load($id)
                ->getAttributeText($attribute);
            //check for brand text
            if ($brand) {
                return $brand;
            }
        }

        return '';
    }

    /**
     * Reward points balance.
     *
     * @return int
     */
    public function getRewardPoints()
    {
        if (!$this->reward) {
            $this->_setReward();
        }

        if ($this->reward !== true) {
            return $this->reward->getPointsBalance();
        }

        return '';
    }

    /**
     * Currency amount points.
     *
     * @return mixed
     */
    public function getRewardAmount()
    {
        if (!$this->reward) {
            $this->_setReward();
        }

        if ($this->reward !== true) {
            return $this->reward->getCurrencyAmount();
        }

        return '';
    }

    /**
     * Expiration date to use the points.
     *
     * @return string
     */
    public function getExpirationDate()
    {
        //set reward for later use
        if (!$this->reward) {
            $this->_setReward();
        }

        if ($this->reward !== true) {
            $expiredAt = $this->reward->getExpirationDate();

            if ($expiredAt) {
                $date = $this->dateTime->formatDate($expiredAt, 'short', true);
            } else {
                $date = '';
            }

            return $date;
        }

        return '';
    }

    /**
     * Get the customer reward.
     */
    public function _setReward()
    {
        //@codingStandardsIgnoreStart
        if ($rewardModel = $this->_objectManager->create('Magento\Reward\Model\Reward\History')) {
            $enHelper = $this->_objectManager->create('Magento\Reward\Helper\Reward');
            $collection = $rewardModel->getCollection()
                ->addCustomerFilter($this->customer->getId())
                ->addWebsiteFilter($this->customer->getWebsiteId())
                ->setExpiryConfig($enHelper->getExpiryConfig())
                ->addExpirationDate($this->customer->getWebsiteId())
                ->skipExpiredDuplicates()
                ->setDefaultOrder();

            $item = $collection->setPageSize(1)
                ->setCurPage(1)
                ->getFirstItem();
            //@codingStandardsIgnoreEnd

            $this->reward = $item;
        } else {
            $this->reward = true;
        }
    }

    /**
     * Get last increment id.
     *
     * @return mixed
     */
    public function getLastIncrementId()
    {
        return $this->customer->getLastIncrementId();
    }

    /**
     * Get billing company name.
     *
     * @return mixed
     */
    public function getBillingCompany()
    {
        return $this->customer->getBillingCompany();
    }
    /**
     * Get shipping company name.
     *
     * @return mixed
     */
    public function getDeliveryCompany()
    {
        return $this->customer->getShippingCompany();
    }
}
