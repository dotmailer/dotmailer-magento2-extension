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
class Customer extends ContactData
{
    /**
     * @var \Magento\Customer\Model\Customer
     */
    public $model;

    /**
     * @var \Magento\Review\Model\ResourceModel\Review\CollectionFactory
     */
    public $reviewCollection;

    /**
     * @var array
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
     * @var \Magento\Catalog\Api\Data\CategoryInterfaceFactory
     */
    public $categoryFactory;

    /**
     * @var \Magento\Catalog\Api\Data\ProductInterfaceFactory
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
     * Customer constructor.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product $productResource
     * @param \Magento\Catalog\Model\ResourceModel\Category $categoryResource
     * @param \Magento\Customer\Model\ResourceModel\Group $groupResource
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Review\Model\ResourceModel\Review\CollectionFactory $reviewCollectionFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $collectionFactory
     * @param \Magento\Customer\Model\GroupFactory $groupFactory
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param \Magento\Catalog\Api\Data\CategoryInterfaceFactory $categoryFactory
     * @param \Magento\Catalog\Api\Data\ProductInterfaceFactory $productFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Sales\Model\ResourceModel\Order $resourceOrder
     * @param \Magento\Eav\Model\ConfigFactory $eavConfigFactory
     * @param \Dotdigitalgroup\Email\Helper\Config $configHelper
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product $productResource,
        \Magento\Catalog\Model\ResourceModel\Category $categoryResource,
        \Magento\Customer\Model\ResourceModel\Group $groupResource,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Review\Model\ResourceModel\Review\CollectionFactory $reviewCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $collectionFactory,
        \Magento\Customer\Model\GroupFactory $groupFactory,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Magento\Catalog\Api\Data\CategoryInterfaceFactory $categoryFactory,
        \Magento\Catalog\Api\Data\ProductInterfaceFactory $productFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\ResourceModel\Order $resourceOrder,
        \Magento\Eav\Model\ConfigFactory $eavConfigFactory,
        \Dotdigitalgroup\Email\Helper\Config $configHelper
    ) {
        $this->reviewCollection  = $reviewCollectionFactory;
        $this->orderCollection   = $collectionFactory;
        $this->groupFactory      = $groupFactory;
        $this->subscriberFactory = $subscriberFactory;
        $this->groupResource     = $groupResource;

        parent::__construct(
            $storeManager,
            $productFactory,
            $productResource,
            $orderFactory,
            $resourceOrder,
            $categoryFactory,
            $categoryResource,
            $eavConfigFactory,
            $configHelper
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
    public function setContactData($customer)
    {
        $this->model = $customer;
        $this->setReviewCollection();
        parent::setContactData($customer);

        return $this;
    }

    /**
     * @param string $email
     *
     * @return null
     */
    public function setEmail($email)
    {
        $this->contactData['email'] = $email;
    }

    /**
     * @param string $emailType
     *
     * @return null
     */
    public function setEmailType($emailType)
    {
        $this->contactData['email_type'] = $emailType;
    }

    /**
     * Customer reviews.
     *
     * @return $this
     */
    public function setReviewCollection()
    {
        $customerId = $this->model->getId();
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
     * @return int
     */
    public function getCustomerId()
    {
        return $this->model->getId();
    }

    /**
     * Get first name.
     *
     * @return string
     */
    public function getFirstname()
    {
        return $this->model->getFirstname();
    }

    /**
     * Get last name.
     *
     * @return string
     */
    public function getLastname()
    {
        return $this->model->getLastname();
    }

    /**
     * Get date of birth.
     *
     * @return string
     */
    public function getDob()
    {
        return $this->model->getDob();
    }

    /**
     * Get customer gender.
     *
     * @return bool|string
     */
    public function getGender()
    {
        return $this->getCustomerGender();
    }

    /**
     * Get customer prefix.
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->model->getPrefix();
    }

    /**
     * Get customer suffix.
     *
     * @return string
     */
    public function getSuffix()
    {
        return $this->model->getSuffix();
    }

    /**
     * Get customer created at date.
     *
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->model->getCreatedAt();
    }

    /**
     * Get customer last logged in date.
     *
     * @return string
     */
    public function getLastLoggedDate()
    {
        return $this->model->getLastLoggedDate();
    }

    /**
     * Get billing address line 1.
     *
     * @return string
     */
    public function getBillingAddress1()
    {
        return $this->getStreet($this->model->getBillingStreet(), 1);
    }

    /**
     * Get billing address line 2.
     *
     * @return string
     */
    public function getBillingAddress2()
    {
        return $this->getStreet($this->model->getBillingStreet(), 2);
    }

    /**
     * Get billing city.
     *
     * @return string
     */
    public function getBillingCity()
    {
        return $this->model->getBillingCity();
    }

    /**
     * Get billing country.
     *
     * @return string
     */
    public function getBillingCountry()
    {
        return $this->model->getBillingCountryCode();
    }

    /**
     * Get billing state.
     *
     * @return string
     */
    public function getBillingState()
    {
        return $this->model->getBillingRegion();
    }

    /**
     * Get billing postcode.
     *
     * @return string
     */
    public function getBillingPostcode()
    {
        return $this->model->getBillingPostcode();
    }

    /**
     * Get billing phone.
     *
     * @return string
     */
    public function getBillingTelephone()
    {
        return $this->model->getBillingTelephone();
    }

    /**
     * Get delivery address line 1.
     *
     * @return string
     */
    public function getDeliveryAddress1()
    {
        return $this->getStreet($this->model->getShippingStreet(), 1);
    }

    /**
     * Get delivery address line 2.
     *
     * @return string
     */
    public function getDeliveryAddress2()
    {
        return $this->getStreet($this->model->getShippingStreet(), 2);
    }

    /**
     * Get delivery city.
     *
     * @return string
     */
    public function getDeliveryCity()
    {
        return $this->model->getShippingCity();
    }

    /**
     * Get delivery country.
     *
     * @return string
     */
    public function getDeliveryCountry()
    {
        return $this->model->getShippingCountryCode();
    }

    /**
     * Get delivery state.
     *
     * @return string
     */
    public function getDeliveryState()
    {
        return $this->model->getShippingRegion();
    }

    /**
     * Get delivery postcode.
     *
     * @return string
     */
    public function getDeliveryPostcode()
    {
        return $this->model->getShippingPostcode();
    }

    /**
     * Get delivery phone.
     *
     * @return string
     */
    public function getDeliveryTelephone()
    {
        return $this->model->getShippingTelephone();
    }

    /**
     * Get last quote id.
     *
     * @return int
     */
    public function getLastQuoteId()
    {
        return $this->model->getLastQuoteId();
    }

    /**
     * Get customer title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->model->getPrefix();
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
            ->addAttributeToFilter('customer_id', $this->model->getId());

        $totalRefunded = 0;
        //calculate total refunded
        foreach ($customerOrders as $order) {
            $refunded = $order->getTotalRefunded();
            $totalRefunded += $refunded;
        }

        return $totalRefunded;
    }

    /**
     * customer gender.
     *
     * @return bool|string
     */
    public function getCustomerGender()
    {
        $genderId = $this->model->getGender();
        if (is_numeric($genderId)) {
            $gender = $this->model->getAttribute('gender')
                ->getSource()->getOptionText($genderId);

            return $gender;
        }

        return '';
    }

    /**
     * @param string $street
     * @param int $line
     * @return void
     */
    public function getStreet($street, $line)
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
    public function getCustomerGroup()
    {
        $groupId = $this->model->getGroupId();
        $groupModel = $this->groupFactory->create();
        $this->groupResource->load($groupModel, $groupId);
        if ($groupModel) {
            return $groupModel->getCode();
        }

        return '';
    }

    /**
     * Subscriber status for Customer.
     *
     * @return boolean|string
     */
    public function getSubscriberStatus()
    {
        $subscriberModel = $this->subscriberFactory->create()
            ->loadByCustomerId($this->model->getId());

        if ($subscriberModel->getCustomerId()) {
            return $this->subscriberStatus[$subscriberModel->getSubscriberStatus()];
        }

        return false;
    }

    /**
     * Get billing company name.
     *
     * @return string
     */
    public function getBillingCompany()
    {
        return $this->model->getBillingCompany();
    }

    /**
     * Get shipping company name.
     *
     * @return string
     */
    public function getDeliveryCompany()
    {
        return $this->model->getShippingCompany();
    }

    /**
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        return call_user_func_array([$this->model, $method], $args);
    }
}
