<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Connector\ContactData;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Connector\ContactData;
use Dotdigitalgroup\Email\Model\Customer\DataField\Date;
use Dotdigitalgroup\Email\Model\Newsletter\BackportedSubscriberLoader;
use Magento\Catalog\Api\Data\CategoryInterfaceFactory;
use Magento\Catalog\Model\ResourceModel\Category;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Manages the Customer data as datafields for contact.
 */
class Customer extends ContactData
{
    /**
     * @var \Magento\Customer\Model\Customer
     */
    public $model;

    /**
     * @var array
     */
    public $columns;

    /**
     * @var Logger
     */
    public $logger;

    /**
     * @var CategoryInterfaceFactory
     */
    public $categoryFactory;

    /**
     * @var object
     */
    public $contactFactory;

    /**
     * @var BackportedSubscriberLoader
     */
    private $backportedSubscriberLoader;

    /**
     * @var CustomerGroupLoader
     */
    private $customerGroupLoader;

    /**
     * Customer constructor.
     *
     * @param Category $categoryResource
     * @param StoreManagerInterface $storeManager
     * @param CategoryInterfaceFactory $categoryFactory
     * @param Config $configHelper
     * @param Logger $logger
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param Date $dateField
     * @param BackportedSubscriberLoader $backportedSubscriberLoader
     * @param ProductLoader $productLoader
     * @param CustomerGroupLoader $customerGroupLoader
     */
    public function __construct(
        Category $categoryResource,
        StoreManagerInterface $storeManager,
        CategoryInterfaceFactory $categoryFactory,
        Config $configHelper,
        Logger $logger,
        \Magento\Eav\Model\Config $eavConfig,
        Date $dateField,
        BackportedSubscriberLoader $backportedSubscriberLoader,
        ProductLoader $productLoader,
        CustomerGroupLoader $customerGroupLoader
    ) {
        $this->backportedSubscriberLoader = $backportedSubscriberLoader;
        $this->dateField = $dateField;
        $this->customerGroupLoader = $customerGroupLoader;

        parent::__construct(
            $storeManager,
            $categoryFactory,
            $categoryResource,
            $configHelper,
            $logger,
            $dateField,
            $eavConfig,
            $productLoader
        );
    }

    /**
     * Initialize the model.
     *
     * @param AbstractModel $model
     * @param array $columns
     * @param array $categoryNames
     * @param AbstractAttribute|false $brandAttribute
     *
     * @return $this|ContactData
     * @throws LocalizedException
     */
    public function init(AbstractModel $model, array $columns, array $categoryNames = [], $brandAttribute = null)
    {
        parent::init($model, $columns, $categoryNames, $brandAttribute);
        $this->setContactData();

        return $this;
    }

    /**
     * Set email.
     *
     * @param string $email
     *
     * @return void
     *
     * @deprecated email is an identifier, not a data field.
     * @see \Dotdigitalgroup\Email\Model\Sync\Export\SdkContactBuilder::createSdkContact
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
     *
     * @deprecated email_type is a channel property, not a data field.
     * @see \Dotdigitalgroup\Email\Model\Sync\Export\SdkContactBuilder::createSdkContact
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
        $reviewData = $this->model->getReviewData();
        return isset($reviewData[$this->model->getStoreId()]['review_count']) ?
            (int) $reviewData[$this->model->getStoreId()]['review_count'] :
            0;
    }

    /**
     * Last review date.
     *
     * @return string
     */
    public function getLastReviewDate()
    {
        $reviewData = $this->model->getReviewData();
        return isset($reviewData[$this->model->getStoreId()]['last_review_date']) ?
            $reviewData[$this->model->getStoreId()]['last_review_date'] :
            '';
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
     * @throws LocalizedException
     */
    public function getGender()
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
        return $this->customerGroupLoader->getCustomerGroup((int) $this->model->getGroupId());
    }

    /**
     * Subscriber status for Customer.
     *
     * @return string
     */
    public function getSubscriberStatus()
    {
        $subscriberModel = $this->backportedSubscriberLoader->loadByCustomer(
            $this->model->getId(),
            $this->model->getWebsiteId()
        );

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
}
