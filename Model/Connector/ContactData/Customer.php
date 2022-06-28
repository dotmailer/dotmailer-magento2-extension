<?php

namespace Dotdigitalgroup\Email\Model\Connector\ContactData;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Connector\ContactData;
use Dotdigitalgroup\Email\Model\Customer\DataField\Date;
use Magento\Framework\Model\AbstractModel;
use Magento\Review\Model\Review;
use Magento\Store\Model\App\Emulation;

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
    private $reviewCollectionFactory;

    /**
     * @var array
     */
    public $columns;

    /**
     * @var Logger
     */
    public $logger;

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
     * @var object
     */
    public $contactFactory;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Group
     */
    private $groupResource;

    /**
     * @var Emulation
     */
    private $appEmulation;

    /**
     * @var array
     */
    private $customerReviews;

    /**
     * Customer constructor.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product $productResource
     * @param \Magento\Catalog\Model\ResourceModel\Category $categoryResource
     * @param \Magento\Customer\Model\ResourceModel\Group $groupResource
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Review\Model\ResourceModel\Review\CollectionFactory $reviewCollectionFactory
     * @param \Magento\Customer\Model\GroupFactory $groupFactory
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param \Magento\Catalog\Api\Data\CategoryInterfaceFactory $categoryFactory
     * @param \Magento\Catalog\Api\Data\ProductInterfaceFactory $productFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Sales\Model\ResourceModel\Order $resourceOrder
     * @param \Dotdigitalgroup\Email\Helper\Config $configHelper
     * @param Logger $logger
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param Date $dateField
     * @param Emulation $appEmulation
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product $productResource,
        \Magento\Catalog\Model\ResourceModel\Category $categoryResource,
        \Magento\Customer\Model\ResourceModel\Group $groupResource,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Review\Model\ResourceModel\Review\CollectionFactory $reviewCollectionFactory,
        \Magento\Customer\Model\GroupFactory $groupFactory,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Magento\Catalog\Api\Data\CategoryInterfaceFactory $categoryFactory,
        \Magento\Catalog\Api\Data\ProductInterfaceFactory $productFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\ResourceModel\Order $resourceOrder,
        \Dotdigitalgroup\Email\Helper\Config $configHelper,
        Logger $logger,
        \Magento\Eav\Model\Config $eavConfig,
        Date $dateField,
        Emulation $appEmulation
    ) {
        $this->reviewCollectionFactory = $reviewCollectionFactory;
        $this->groupFactory      = $groupFactory;
        $this->subscriberFactory = $subscriberFactory;
        $this->groupResource     = $groupResource;
        $this->appEmulation = $appEmulation;
        $this->dateField = $dateField;

        parent::__construct(
            $storeManager,
            $productFactory,
            $productResource,
            $orderFactory,
            $resourceOrder,
            $categoryFactory,
            $categoryResource,
            $configHelper,
            $logger,
            $dateField,
            $eavConfig
        );
    }

    /**
     * Initialize the model.
     *
     * @param AbstractModel $model
     * @param array $columns
     * @return $this|ContactData
     */
    public function init(AbstractModel $model, array $columns)
    {
        parent::init($model, $columns);
        $this->setContactData();

        return $this;
    }

    /**
     * Set email.
     *
     * @param string $email
     *
     * @return void
     */
    public function setEmail($email)
    {
        $this->contactData['email'] = $email;
    }

    /**
     * Set email type.
     *
     * @param string $emailType
     *
     * @return void
     */
    public function setEmailType($emailType)
    {
        $this->contactData['email_type'] = $emailType;
    }

    /**
     * Number of reviews.
     *
     * @return int
     */
    public function getReviewCount()
    {
        return count($this->getReviewsForCustomer());
    }

    /**
     * Last review date.
     *
     * @return string
     */
    public function getLastReviewDate()
    {
        $reviews = $this->getReviewsForCustomer();
        $lastReview = reset($reviews);

        return $lastReview instanceof Review ? $lastReview->getCreatedAt() : '';
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
     * @return string
     */
    protected function getGender()
    {
        $genderId = $this->model->getGender();
        if (is_numeric($genderId)) {
            return $this->eavConfig->getAttribute('customer', 'gender')
                ->getSource()->getOptionText($genderId);
        }

        return '';
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
     * Return street address by line number.
     *
     * @param string $street
     * @param int $line
     *
     * @return string
     */
    public function getStreet($street, $line)
    {
        if (!is_string($street)) {
            return '';
        }
        $street = explode("\n", $street);
        return (isset($street[$line - 1])) ? $street[$line - 1] : '';
    }

    /**
     * Get customer group.
     *
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
     * @return string
     */
    public function getSubscriberStatus()
    {
        $this->appEmulation->startEnvironmentEmulation($this->model->getStoreId());

        $subscriberModel = $this->subscriberFactory->create()
            ->loadByCustomerId($this->model->getId());

        $this->appEmulation->stopEnvironmentEmulation();

        if ($subscriberModel->getCustomerId()) {
            try {
                return $this->getSubscriberStatusString($subscriberModel->getSubscriberStatus());
            } catch (\InvalidArgumentException $e) {
                return '';
            }
        }

        return '';
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
     * Get reviews for customer.
     *
     * @return array
     */
    private function getReviewsForCustomer()
    {
        if (!is_array($this->customerReviews)) {
            $this->setReviewsForCustomer();
        }

        return $this->customerReviews;
    }
    /**
     * Set reviews for customer.
     *
     * @return void
     */
    private function setReviewsForCustomer()
    {
        $collection = $this->reviewCollectionFactory->create()
            ->addCustomerFilter($this->model->getId())
            ->addStoreFilter($this->model->getStoreId())
            ->addFieldToSelect(['review_id', 'created_at'])
            ->setOrder('created_at');

        $this->customerReviews = $collection->getItems();
    }
}
