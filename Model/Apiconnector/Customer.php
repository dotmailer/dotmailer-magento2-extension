<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector;

/**
 * Manages the Customer data as datafields for contact.
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Customer extends \Magento\Framework\Model\AbstractExtensibleModel
    implements \Dotdigitalgroup\Email\Model\Apiconnector\CustomerInterface
{
    /**
     * @var \Magento\Customer\Model\Customer
     */
    public $customer;

    /**
     * @var object
     */
    public $customerData;

    /**
     * @var \Magento\Review\Model\ResourceModel\Review\CollectionFactory
     */
    public $reviewCollection;

    /**
     * @var object
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
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    public $orderCollection;

    /**
     * @var object
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
     * @var \Magento\Customer\Model\ResourceModel\Group
     */
    private $groupResource;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category
     */
    private $categoryResource;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product
     */
    private $productResource;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $store;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    private $dateTime;

    /**
     * @var \Magento\Eav\Model\ConfigFactory
     */
    private $eavConfigFactory;

    /**
     * Customer constructor.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product $productResource
     * @param \Magento\Catalog\Model\ResourceModel\Category $categoryResource
     * @param \Magento\Customer\Model\ResourceModel\Group $groupResource
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param \Magento\Review\Model\ResourceModel\Review\CollectionFactory $reviewCollectionFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $collectionFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Customer\Model\GroupFactory $groupFactory
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Eav\Model\ConfigFactory $eavConfigFactory
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product $productResource,
        \Magento\Catalog\Model\ResourceModel\Category $categoryResource,
        \Magento\Customer\Model\ResourceModel\Group $groupResource,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Review\Model\ResourceModel\Review\CollectionFactory $reviewCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $collectionFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Customer\Model\GroupFactory $groupFactory,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Eav\Model\ConfigFactory $eavConfigFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->dateTime          = $dateTime;
        $this->helper            = $helper;
        $this->store             = $storeManager;
        $this->reviewCollection  = $reviewCollectionFactory;
        $this->orderCollection   = $collectionFactory;
        $this->groupFactory      = $groupFactory;
        $this->subscriberFactory = $subscriberFactory;
        $this->categoryFactory   = $categoryFactory;
        $this->productFactory    = $productFactory;
        $this->groupResource     = $groupResource;
        $this->categoryResource  = $categoryResource;
        $this->productResource   = $productResource;
        $this->productResource   = $productResource;
        $this->eavConfigFactory  = $eavConfigFactory;

        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Set customer data.
     *
     * @param \Magento\Customer\Model\Customer customer
     *
     * @return $this
     *
     */
    public function setCustomerData($customer)
    {
        $this->customer = $customer;
        $this->setReviewCollection();
        $mappingHash = array_keys($this->getMappingHash());

        foreach ($mappingHash as $key) {
            /*
             * call user function based on the attribute mapped.
             */
            $function = 'get';
            $exploded = explode('_', $key);
            foreach ($exploded as $one) {
                $function .= ucfirst($one);
            }
            $value = call_user_func(
                ['self', $function]
            );
            $this->customerData[$key] = $value;
        }

        return $this;
    }

    /**
     * Set key value data.
     *
     * @param $key
     * @param $value
     * @return $this
     */
    public function setCustomData($key, $value)
    {
        $this->customerData[$key] = $value;

        return $this;
    }

    /**
     * @param string $email
     *
     * @return null
     */
    public function setEmail($email)
    {
        $this->customerData['email'] = $email;
    }

    /**
     * @param string $emailType
     *
     * @return null
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
        $collection = $this->reviewCollection->create()
            ->addCustomerFilter($customerId)
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
            $this->reviewCollection->getSelect()->limit(1);
            $createdAt = $this->reviewCollection
                ->getFirstItem()
                ->getCreatedAt();
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

    /**
     * @param mixed $street
     * @param mixed $line
     * @return void
     */
    public function _getStreet($street, $line)
    {
        $street = explode("\n", $street);
        if (isset($street[$line - 1])) {
            return $street[$line - 1];
        }

        return '';
    }

    /**
     * @return string
     */
    public function _getWebsiteName()
    {
        $websiteId = $this->customer->getWebsiteId();
        $website = $this->store->getWebsite($websiteId);
        if ($website) {
            return $website->getName();
        }

        return '';
    }

    /**
     * @return string
     */
    public function _getStoreName()
    {
        $storeId = $this->customer->getStoreId();
        $store = $this->store->getStore($storeId);

        if ($store) {
            return $store->getName();
        }

        return '';
    }

    /**
     * @param mixed $mapping_hash
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

    /**
     * @return string
     */
    public function _getCustomerGroup()
    {
        $groupId = $this->customer->getGroupId();
        $groupModel = $this->groupFactory->create();
        $this->groupResource->load($groupModel, $groupId);
        if ($groupModel) {
            return $groupModel->getCode();
        }

        return '';
    }

    /**
     * mapping hash value.
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function setMappigHash($value)
    {
        $this->mappingHash = $value;

        return $this;
    }

    /**
     * @param string $string
     * @return void
     */
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

        return false;
    }

    /**
     * Get most purchased category.
     *
     * @return string
     */
    public function getMostPurCategory()
    {
        $categoryId = $this->customer->getMostCategoryId();
        return $this->getCategoryValue($categoryId);
    }

    /**
     * Get most purchased brand.
     *
     * @return string
     */
    public function getMostPurBrand()
    {
        $optionId = $this->customer->getMostBrand();

        //attribute mapped from the config
        $attributeCode = $this->helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_DATA_FIELDS_BRAND_ATTRIBUTE,
            $this->customer->getWebsiteId()
        );

        //if the id and attribute found
        if ($optionId && $attributeCode) {
            $attribute = $this->eavConfigFactory->create()
                ->getAttribute(\Magento\Catalog\Model\Product::ENTITY, $attributeCode);

            $value = $attribute->setStoreId($this->customer->getStoreId())
                ->getSource()
                ->getOptionText($optionId);

            //check for brand text
            if ($value) {
                return $value;
            }
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
        $categoryId = $this->customer->getFirstCategoryId();
        return $this->getCategoryValue($categoryId);
    }

    /**
     * Get last purchased category.
     *
     * @return string
     */
    public function getLastCategoryPur()
    {
        $categoryId = $this->customer->getLastCategoryId();

        return $this->getCategoryValue($categoryId);
    }

    /**
     * @param $categoryId
     * @return string
     */
    private function getCategoryValue($categoryId)
    {
        if ($categoryId) {
            $category = $this->categoryFactory->create()
                ->setStoreId($this->customer->getStoreId());
            $this->categoryResource->load($category, $categoryId);
            return $category->getName();
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
        return $this->getBrandValue($id);
    }

    /**
     * Get last purchased brand.
     *
     * @return string
     */
    public function getLastBrandPur()
    {
        $id = $this->customer->getProductIdForLastBrand();

        return $this->getBrandValue($id);
    }

    /**
     * @param mixed $id
     * @return string
     */
    private function getBrandValue($id)
    {
        //attribute mapped from the config
        $attributeCode = $this->helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_DATA_FIELDS_BRAND_ATTRIBUTE,
            $this->customer->getWebsiteId()
        );
        $storeId = $this->customer->getStoreId();

        //if the id and attribute found
        if ($id && $attributeCode) {
            $product = $this->productFactory->create();
            $product = $product->setStoreId($storeId);
            $this->productResource->load($product, $id);

            $value = $product->getResource()
                ->getAttribute($attributeCode)
                ->setStoreId($storeId)
                ->getSource()
                ->getOptionText($product->getData($attributeCode));

            //check for brand text
            if ($value) {
                return $value;
            }
        }

        return '';
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
     * {@inheritdoc}
     *
     * @return \Dotdigitalgroup\Email\Model\Apiconnector\CustomerExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        $extensionAttributes = $this->_getExtensionAttributes();
        if (!$extensionAttributes) {
            return $this->extensionAttributesFactory->create(
                'Dotdigitalgroup\Email\Model\Apiconnector\CustomerInterface'
            );
        }
        return $extensionAttributes;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Dotdigitalgroup\Email\Model\Apiconnector\CustomerExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Dotdigitalgroup\Email\Model\Apiconnector\CustomerExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
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

    /**
     * Get last used date for reward points
     *
     * @return string
     */
    public function getLastUsedDate()
    {
        $lastUsedDate = '';

        $extendedAttributes = $this->getExtensionAttributes();
        if ($extendedAttributes !== null) {
            $lastUsedDate = $extendedAttributes->getLastUsedDate();
        }

        return $lastUsedDate;
    }

    /**
     * Get customer segments
     *
     * @return string
     */
    public function getCustomerSegments()
    {
        $customerSegment = '';

        $extendedAttributes = $this->getExtensionAttributes();
        if ($extendedAttributes !== null) {
            $customerSegment = $extendedAttributes->getCustomerSegments();
        }

        return $customerSegment;
    }

    /**
     * Get expiration date for reward point
     *
     * @return string
     */
    public function getExpirationDate()
    {
        $expirationDate = '';

        $extendedAttributes = $this->getExtensionAttributes();
        if ($extendedAttributes !== null) {
            $expirationDate = $extendedAttributes->getExpirationDate();
        }

        return $expirationDate;
    }

    /**
     * Get reward amount
     *
     * @return string
     */
    public function getRewardAmmount()
    {
        $rewardAmmount = '';

        $extendedAttributes = $this->getExtensionAttributes();
        if ($extendedAttributes !== null) {
            $rewardAmmount = $extendedAttributes->getRewardAmmount();
        }

        return $rewardAmmount;
    }

    /**
     * Get reward points
     *
     * @return string
     */
    public function getRewardPoints()
    {
        $rewardPoints = '';

        $extendedAttributes = $this->getExtensionAttributes();
        if ($extendedAttributes !== null) {
            $rewardPoints = $extendedAttributes->getRewardPoints();
        }

        return $rewardPoints;
    }
}
