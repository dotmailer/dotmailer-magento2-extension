<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector;

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
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    public $orderCollection;

    /**
     * @var
     */
    public $contactFactory;

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
     * @param \Dotdigitalgroup\Email\Model\ContactFactory                $contactFactory
     * @param \Magento\Store\Model\StoreManagerInterface                 $storeManager
     * @param \Magento\Framework\Stdlib\DateTime                         $dateTime
     * @param \Magento\Framework\ObjectManagerInterface                  $objectManager
     * @param \Magento\Review\Model\ResourceModel\Review\Collection      $reviewCollection
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $collectionFactory
     * @param \Dotdigitalgroup\Email\Helper\Data                         $helper
     * @param \Magento\Customer\Model\GroupFactory                       $groupFactory
     * @param \Magento\Newsletter\Model\SubscriberFactory                $subscriberFactory
     * @param \Magento\Catalog\Model\CategoryFactory                     $categoryFactory
     * @param \Magento\Catalog\Model\ProductFactory                      $productFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Review\Model\ResourceModel\Review\Collection $reviewCollection,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $collectionFactory,
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
        $this->_contactFactory   = $contactFactory;
        $this->reviewCollection  = $reviewCollection;
        $this->orderCollection   = $collectionFactory;
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
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->customerData['email'] = $email;
    }

    /**
     * @param string $emailType
     */
    public function setEmailType($emailType)
    {
        $this->customerData['email_type'] = $emailType;
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
     * Number of reviews.
     *
     * @return int
     */
    public function getReviewCount()
    {
        return count($this->reviewCollection);
    }

    /**
     * Last review date.
     *
     * @return string
     */
    public function getLastReviewDate()
    {
        if ($this->reviewCollection->getSize()) {
            //@codingStandardsIgnoreStart
            $this->reviewCollection->getSelect()->limit(1);
            $createdAt = $this->reviewCollection
                ->getFirstItem()
                ->getCreatedAt();
            //@codingStandardsIgnoreEnd
            return $createdAt;
        }

        return '';
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
     * Get first name.
     *
     * @return mixed
     */
    public function getFirstname()
    {
        return $this->customer->getFirstname();
    }

    /**
     * Get last name.
     *
     * @return mixed
     */
    public function getLastname()
    {
        return $this->customer->getLastname();
    }

    /**
     * Get date of birth.
     *
     * @return mixed
     */
    public function getDob()
    {
        return $this->customer->getDob();
    }

    /**
     * Get customer gender.
     *
     * @return bool|string
     */
    public function getGender()
    {
        return $this->_getCustomerGender();
    }

    /**
     * Get customer prefix.
     *
     * @return mixed
     */
    public function getPrefix()
    {
        return $this->customer->getPrefix();
    }

    /**
     * Get customer suffix.
     *
     * @return mixed
     */
    public function getSuffix()
    {
        return $this->customer->getSuffix();
    }

    /**
     * Get website name.
     *
     * @return string
     */
    public function getWebsiteName()
    {
        return $this->_getWebsiteName();
    }

    /**
     * Get store name.
     *
     * @return null|string
     */
    public function getStoreName()
    {
        return $this->_getStoreName();
    }

    /**
     * Get customer created at date.
     *
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->customer->getCreatedAt();
    }

    /**
     * Get customer last logged in date.
     *
     * @return mixed
     */
    public function getLastLoggedDate()
    {
        return $this->customer->getLastLoggedDate();
    }

    /**
     * Get cutomer group.
     *
     * @return string
     */
    public function getCustomerGroup()
    {
        return $this->_getCustomerGroup();
    }

    /**
     * Get billing address line 1.
     *
     * @return string
     */
    public function getBillingAddress1()
    {
        return $this->_getStreet($this->customer->getBillingStreet(), 1);
    }

    /**
     * Get billing address line 2.
     *
     * @return string
     */
    public function getBillingAddress2()
    {
        return $this->_getStreet($this->customer->getBillingStreet(), 2);
    }

    /**
     * Get billing city.
     *
     * @return mixed
     */
    public function getBillingCity()
    {
        return $this->customer->getBillingCity();
    }

    /**
     * Get billing country.
     *
     * @return mixed
     */
    public function getBillingCountry()
    {
        return $this->customer->getBillingCountryCode();
    }

    /**
     * Get billing state.
     *
     * @return mixed
     */
    public function getBillingState()
    {
        return $this->customer->getBillingRegion();
    }

    /**
     * Get billing postcode.
     *
     * @return mixed
     */
    public function getBillingPostcode()
    {
        return $this->customer->getBillingPostcode();
    }

    /**
     * Get billing phone.
     *
     * @return mixed
     */
    public function getBillingTelephone()
    {
        return $this->customer->getBillingTelephone();
    }

    /**
     * Get delivery address line 1.
     *
     * @return string
     */
    public function getDeliveryAddress1()
    {
        return $this->_getStreet($this->customer->getShippingStreet(), 1);
    }

    /**
     * Get delivery addrss line 2.
     *
     * @return string
     */
    public function getDeliveryAddress2()
    {
        return $this->_getStreet($this->customer->getShippingStreet(), 2);
    }

    /**
     * Get delivery city.
     *
     * @return mixed
     */
    public function getDeliveryCity()
    {
        return $this->customer->getShippingCity();
    }

    /**
     * Get delivery country.
     *
     * @return mixed
     */
    public function getDeliveryCountry()
    {
        return $this->customer->getShippingCountryCode();
    }

    /**
     * Get delivery state.
     *
     * @return mixed
     */
    public function getDeliveryState()
    {
        return $this->customer->getShippingRegion();
    }

    /**
     * Get delivery postcode.
     *
     * @return mixed
     */
    public function getDeliveryPostcode()
    {
        return $this->customer->getShippingPostcode();
    }

    /**
     * Get delivery phone.
     *
     * @return mixed
     */
    public function getDeliveryTelephone()
    {
        return $this->customer->getShippingTelephone();
    }

    /**
     * Get numbser of orders.
     *
     * @return mixed
     */
    public function getNumberOfOrders()
    {
        return $this->customer->getNumberOfOrders();
    }

    /**
     * Get average order value.
     *
     * @return mixed
     */
    public function getAverageOrderValue()
    {
        return $this->customer->getAverageOrderValue();
    }

    /**
     * Get total spend.
     *
     * @return mixed
     */
    public function getTotalSpend()
    {
        return $this->customer->getTotalSpend();
    }

    /**
     * Get last order date.
     *
     * @return mixed
     */
    public function getLastOrderDate()
    {
        return $this->customer->getLastOrderDate();
    }

    /**
     * Get last order id.
     *
     * @return mixed
     */
    public function getLastOrderId()
    {
        return $this->customer->getLastOrderId();
    }

    /**
     * Get last quote id.
     *
     * @return mixed
     */
    public function getLastQuoteId()
    {
        return $this->customer->getLastQuoteId();
    }

    /**
     * Get cutomer id.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->customer->getId();
    }

    /**
     * Get customer title.
     *
     * @return mixed
     */
    public function getTitle()
    {
        return $this->customer->getPrefix();
    }

    /**
     * Total value refunded for the customer.
     *
     * @return float|int
     */
    public function getTotalRefund()
    {
        //filter by customer id
        $customerOrders = $this->orderCollection->create()
            ->addAttributeToFilter('customer_id', $this->customer->getId());

        $totalRefunded = 0;
        //calculate total refunded
        foreach ($customerOrders as $order) {
            $refunded = $order->getTotalRefunded();
            $totalRefunded += $refunded;
        }

        return $totalRefunded;
    }

    /**
     * export to CSV.
     *
     * @return mixed
     */
    public function toCSVArray()
    {
        $result = $this->customerData;

        return $result;
    }

    /**
     * customer gender.
     *
     * @return bool|string
     */
    public function _getCustomerGender()
    {
        $genderId = $this->customer->getGender();
        if (is_numeric($genderId)) {
            $gender = $this->customer->getAttribute('gender')
                ->getSource()->getOptionText($genderId);

            return $gender;
        }

        return '';
    }

    public function _getStreet($street, $line)
    {
        $street = explode("\n", $street);
        if (isset($street[$line - 1])) {
            return $street[$line - 1];
        }

        return '';
    }

    public function _getWebsiteName()
    {
        $websiteId = $this->customer->getWebsiteId();
        $website = $this->_store->getWebsite($websiteId);
        if ($website) {
            return $website->getName();
        }

        return '';
    }

    public function _getStoreName()
    {
        $storeId = $this->customer->getStoreId();
        $store = $this->_store->getStore($storeId);

        if ($store) {
            return $store->getName();
        }

        return '';
    }

    /**
     * @param $mapping_hash
     *
     * @return $this
     */
    public function setMappingHash($mapping_hash)
    {
        $this->mappingHash = $mapping_hash;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getMappingHash()
    {
        return $this->mappingHash;
    }

    public function _getCustomerGroup()
    {
        $groupId = $this->customer->getGroupId();
        $groupModel = $this->groupFactory->create()
            ->load($groupId);
        if ($groupModel) {
            return $groupModel->getCode();
        }

        return '';
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
     * Customer segments id.
     *
     * @return string
     */
    public function getCustomerSegments()
    {
        //@codingStandardsIgnoreStart
        $contactModel = $this->_contactFactory->create()
            ->getCollection()
            ->addFieldToFilter('customer_id', $this->getCustomerId())
            ->addFieldToFilter('website_id', $this->customer->getWebsiteId())
            ->setPageSize(1)
            ->getFirstItem();
        //@codingStandardsIgnoreEnd
        if ($contactModel) {
            return $contactModel->getSegmentIds();
        }

        return '';
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
