<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector;

class Customer
{

    public $customer;
    public $customerData;
    public $reviewCollection;

    public $rewardCustomer;
    public $rewardLastSpent = "";
    public $rewardLastEarned = "";
    public $rewardExpiry = "";

    protected $_mapping_hash;
    protected $_helper;
    protected $_groupFactory;
    protected $_subscriberFactory;
    protected $_categoryFactory;
    protected $_productFactory;
    //enterprise reward
    public $reward;

    protected $subscriber_status
        = array(
            \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED   => 'Subscribed',
            \Magento\Newsletter\Model\Subscriber::STATUS_NOT_ACTIVE   => 'Not Active',
            \Magento\Newsletter\Model\Subscriber::STATUS_UNSUBSCRIBED => 'Unsubscribed',
            \Magento\Newsletter\Model\Subscriber::STATUS_UNCONFIRMED  => 'Unconfirmed'
        );

    /**
     * Customer constructor.
     *
     * @param \Magento\Store\Model\StoreManagerInterface                 $storeManager
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
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Review\Model\ResourceModel\Review\Collection $reviewCollection,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $collectionFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Customer\Model\GroupFactory $groupFactory,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory
    ) {
        $this->_helper            = $helper;
        $this->_store             = $storeManager;
        $this->_objectManager     = $objectManager;
        $this->reviewCollection   = $reviewCollection;
        $this->orderCollection    = $collectionFactory;
        $this->_groupFactory      = $groupFactory;
        $this->_subscriberFactory = $subscriberFactory;
        $this->_categoryFactory   = $categoryFactory;
        $this->_productFactory    = $productFactory;

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
     */
    public function setCustomerData($customer)
    {
        $this->customer = $customer;
        $this->setReviewCollection();


        foreach ($this->getMappingHash() as $key => $field) {
            /**
             * call user function based on the attribute mapped.
             */
            $function = 'get';
            $exploded = explode('_', $key);
            foreach ($exploded as $one) {
                $function .= ucfirst($one);
            }
            try {
                $value                    = call_user_func(
                    array('self', $function)
                );
                $this->customerData[$key] = $value;
            } catch (\Exception $e) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __($e->getMessage())
                );

            }
        }

        return $this;
    }

    public function setEmail($email)
    {
        $this->customerData['email'] = $email;
    }

    public function setEmailType($emailType)
    {
        $this->customerData['email_type'] = $emailType;
    }

    /**
     * Customer reviews.
     */
    public function setReviewCollection()
    {
        $customer_id = $this->customer->getId();
        $collection  = $this->reviewCollection->addCustomerFilter($customer_id)
            ->setOrder('review_id', 'DESC');

        $this->reviewCollection = $collection;

        return $this;
    }

    public function getReviewCount()
    {
        return count($this->reviewCollection);
    }

    public function getLastReviewDate()
    {
        if (count($this->reviewCollection)) {
            return $this->reviewCollection->getFirstItem()->getCreatedAt();
        }

        return '';
    }

    /**
     * get customer id.
     *
     * @return mixed
     */
    public function getCustomerId()
    {
        return $this->customer->getId();
    }

    /**
     * get first name.
     *
     * @return mixed
     */
    public function getFirstname()
    {
        return $this->customer->getFirstname();
    }

    /**
     * get last name.
     *
     * @return mixed
     */
    public function getLastname()
    {
        return $this->customer->getLastname();
    }

    /**
     * get date of birth.
     *
     * @return mixed
     */
    public function getDob()
    {
        return $this->customer->getDob();
    }

    /**
     * get customer gender.
     *
     * @return bool|string
     */
    public function getGender()
    {
        return $this->_getCustomerGender();
    }

    /**
     * get customer prefix.
     *
     * @return mixed
     */
    public function getPrefix()
    {
        return $this->customer->getPrefix();
    }

    /**
     * get customer suffix.
     *
     * @return mixed
     */
    public function getSuffix()
    {
        return $this->customer->getSuffix();
    }

    /**
     * get website name.
     *
     * @return string
     */
    public function getWebsiteName()
    {
        return $this->_getWebsiteName();
    }

    /**
     * get store name.
     *
     * @return null|string
     */
    public function getStoreName()
    {
        return $this->_getStoreName();
    }

    /**
     * get customer created at date.
     *
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->customer->getCreatedAt();
    }

    /**
     * get customer last logged in date.
     *
     * @return mixed
     */
    public function getLastLoggedDate()
    {
        return $this->customer->getLastLoggedDate();
    }

    /**
     * get cutomer group.
     *
     * @return string
     */
    public function getCustomerGroup()
    {
        return $this->_getCustomerGroup();
    }

    /**
     * get billing address line 1.
     *
     * @return string
     */
    public function getBillingAddress1()
    {
        return $this->_getStreet($this->customer->getBillingStreet(), 1);
    }

    /**
     * get billing address line 2.
     *
     * @return string
     */
    public function getBillingAddress2()
    {
        return $this->_getStreet($this->customer->getBillingStreet(), 2);
    }

    /**
     * get billing city.
     *
     * @return mixed
     */
    public function getBillingCity()
    {
        return $this->customer->getBillingCity();
    }

    /**
     * get billing country.
     *
     * @return mixed
     */
    public function getBillingCountry()
    {
        return $this->customer->getBillingCountryCode();
    }

    /**
     * get billing state.
     *
     * @return mixed
     */
    public function getBillingState()
    {
        return $this->customer->getBillingRegion();
    }

    /**
     * get billing postcode.
     *
     * @return mixed
     */
    public function getBillingPostcode()
    {
        return $this->customer->getBillingPostcode();
    }

    /**
     * get billing phone.
     *
     * @return mixed
     */
    public function getBillingTelephone()
    {
        return $this->customer->getBillingTelephone();
    }

    /**
     * get delivery address line 1.
     *
     * @return string
     */
    public function getDeliveryAddress1()
    {
        return $this->_getStreet($this->customer->getShippingStreet(), 1);
    }

    /**
     * get delivery addrss line 2.
     *
     * @return string
     */
    public function getDeliveryAddress2()
    {
        return $this->_getStreet($this->customer->getShippingStreet(), 2);
    }

    /**
     * get delivery city.
     *
     * @return mixed
     */
    public function getDeliveryCity()
    {
        return $this->customer->getShippingCity();
    }

    /**
     * get delivery country.
     *
     * @return mixed
     */
    public function getDeliveryCountry()
    {
        return $this->customer->getShippingCountryCode();
    }

    /**
     * get delivery state.
     *
     * @return mixed
     */
    public function getDeliveryState()
    {
        return $this->customer->getShippingRegion();
    }

    /**
     * get delivery postcode.
     *
     * @return mixed
     */
    public function getDeliveryPostcode()
    {
        return $this->customer->getShippingPostcode();
    }

    /**
     * get delivery phone.
     *
     * @return mixed
     */
    public function getDeliveryTelephone()
    {
        return $this->customer->getShippingTelephone();
    }

    /**
     * get numbser of orders.
     *
     * @return mixed
     */
    public function getNumberOfOrders()
    {
        return $this->customer->getNumberOfOrders();
    }

    /**
     * get average order value.
     *
     * @return mixed
     */
    public function getAverageOrderValue()
    {
        return $this->customer->getAverageOrderValue();
    }

    /**
     * get total spend.
     *
     * @return mixed
     */
    public function getTotalSpend()
    {
        return $this->customer->getTotalSpend();
    }

    /**
     * get last order date.
     *
     * @return mixed
     */
    public function getLastOrderDate()
    {
        return $this->customer->getLastOrderDate();
    }

    /**
     * get last order id.
     *
     * @return mixed
     */
    public function getLastOrderId()
    {
        return $this->customer->getLastOrderId();
    }

    /**
     * get last quote id.
     *
     * @return mixed
     */
    public function getLastQuoteId()
    {
        return $this->customer->getLastQuoteId();
    }

    /**
     * get cutomer id.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->customer->getId();
    }

    /**
     * get customer title.
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
    protected function _getCustomerGender()
    {
        $genderId = $this->customer->getGender();
        if (is_numeric($genderId)) {
            $gender = $this->customer->getAttribute('gender')
                ->getSource()->getOptionText($genderId);

            return $gender;
        }

        return '';
    }

    protected function _getStreet($street, $line)
    {
        $street = explode("\n", $street);
        if (isset($street[$line - 1])) {
            return $street[$line - 1];
        }

        return '';
    }

    protected function _getWebsiteName()
    {
        $websiteId = $this->customer->getWebsiteId();
        $website   = $this->_store->getWebsite($websiteId);
        if ($website) {
            return $website->getName();
        }

        return '';
    }

    protected function _getStoreName()
    {
        $storeId = $this->customer->getStoreId();
        $store   = $this->_store->getStore($storeId);

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
        $this->_mapping_hash = $mapping_hash;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getMappingHash()
    {
        return $this->_mapping_hash;
    }

    protected function _getCustomerGroup()
    {
        $groupId    = $this->customer->getGroupId();
        $groupModel = $this->_groupFactory->create()
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
        $this->_mapping_hash = $value;

        return $this;
    }


    public function cleanString($string)
    {
        $cleanedString = preg_replace("/[^0-9]/", "", $string);
        if ($cleanedString != "") {
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
        $subscriberModel = $this->_subscriberFactory->create()
            ->loadByCustomerId($this->customer->getId());

        if ($subscriberModel->getCustomerId()) {
            return $this->subscriber_status[$subscriberModel->getSubscriberStatus()];
        }
    }


    /**
     * Customer segments id.
     *
     * @return string
     */
    public function getCustomerSegments()
    {
        $contactModel = $this->_objectManager->create(
            'Dotdigitalgroup\Email\Model\Contact'
        )->getCollection()
            ->addFieldToFilter('customer_id', $this->getCustomerId())
            ->addFieldToFilter('website_id', $this->customer->getWebsiteId())
            ->getFirstItem();
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
        $lastUsed = $this->_objectManager->create(
            'Magento\Reward\Model\Reward\History'
        )
            ->addCustomerFilter($this->customer->getId())
            ->addWebsiteFilter($this->customer->getWebsiteId())
            ->addFieldToFilter('points_delta', array('lt' => 0))
            ->setDefaultOrder()
            ->getFirstItem()
            ->getCreatedAt();

        //for any valid date
        if ($lastUsed) {
            return $date = $this->_helper->formatDate($lastUsed, 'short', true);
        }

        return '';
    }


    /**
     * get most purchased category
     *
     * @return string
     */
    public function getMostPurCategory()
    {
        $id = $this->customer->getMostCategoryId();
        if ($id) {
            return $this->_categoryFactory->create()->load($id)
                ->setStoreId($this->customer->getStoreId())
                ->getName();
        }

        return "";
    }

    /**
     * get most purchased brand
     *
     * @return string
     */
    public function getMostPurBrand()
    {
        $brand = $this->customer->getMostBrand();
        if ($brand) {
            return $brand;
        }

        return "";
    }

    /**
     * get most frequent day of purchase
     *
     * @return string
     */
    public function getMostFreqPurDay()
    {
        $day = $this->customer->getWeekDay();
        if ($day) {
            return $day;
        }

        return "";
    }

    /**
     * get most frequent month of purchase
     *
     * @return string
     */
    public function getMostFreqPurMon()
    {
        $month = $this->customer->getMonthDay();
        if ($month) {
            return $month;
        }

        return "";
    }

    /**
     * get first purchased category
     *
     * @return string
     */
    public function getFirstCategoryPur()
    {
        $id = $this->customer->getFirstCategoryId();
        if ($id) {
            return $this->_categoryFactory->create()->load($id)
                ->setStoreId($this->customer->getStoreId())
                ->getName();
        }

        return "";
    }

    /**
     * get last purchased category
     *
     * @return string
     */
    public function getLastCategoryPur()
    {
        $categoryId = $this->customer->getLastCategoryId();
        //customer last category id
        if ($categoryId) {
            return $this->_categoryFactory->create()
                ->setStoreId($this->customer->getStoreId())
                ->load($categoryId)
                ->getName();
        }

        return "";
    }

    /**
     * get first purchased brand
     *
     * @return string
     */
    public function getFirstBrandPur()
    {
        $id = $this->customer->getProductIdForFirstBrand();

        return $this->_getBrandValue($id);
    }

    /**
     * get last purchased brand
     *
     * @return string
     */
    public function getLastBrandPur()
    {
        $id = $this->customer->getProductIdForLastBrand();

        return $this->_getBrandValue($id);
    }

    protected function _getBrandValue($id)
    {
        //attribute mapped from the config
        $attribute = $this->_helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_DATA_FIELDS_BRAND_ATTRIBUTE,
            $this->customer->getWebsiteId()
        );
        //if the id and attribute found
        if ($id && $attribute) {
            $brand = $this->_productFactory->create()
                ->setStoreId($this->customer->getStoreId())
                ->load($id)
                ->getAttributeText($attribute);
            //check for brand text
            if ($brand) {
                return $brand;
            }
        }

        return "";
    }

    /**
     * Reward points balance.
     *
     * @return int
     */
    public function getRewardPoints()
    {
        if ( ! $this->reward) {
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
        if ( ! $this->reward) {
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
        if ( ! $this->reward) {
            $this->_setReward();
        }

        if ($this->reward !== true) {
            $expiredAt = $this->reward->getExpirationDate();

            if ($expiredAt) {
                $date = $this->_objectManager->create(
                    'Magento\Framework\Stdlib\DateTime'
                )->formatDate($expiredAt, 'short', true);
            } else {
                $date = '';
            }

            return $date;
        }

        return '';
    }

    protected function _setReward()
    {

        if ($rewardModel = $this->_objectManager->create(
            'Magento\Reward\Model\Reward\History'
        )
        ) {
            $enHelper   = $this->_objectManager->create(
                'Magento\Reward\Helper\Reward'
            );
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

            $this->reward = $item;
        } else {
            $this->reward = true;
        }
    }

    /**
     * get last increment id
     *
     * @return mixed
     */
    public function getLastIncrementId()
    {
        return $this->customer->getLastIncrementId();
    }

    /**
     * get billing company name
     *
     * @return mixed
     */
    public function getBillingCompany()
    {
        return $this->customer->getBillingCompany();
    }
    /**
     * get shipping company name
     *
     * @return mixed
     */
    public function getDeliveryCompany()
    {
        return $this->customer->getShippingCompany();
    }
}