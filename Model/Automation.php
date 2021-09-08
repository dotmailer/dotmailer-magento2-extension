<?php

namespace Dotdigitalgroup\Email\Model;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationTypeHandler;
use Dotdigitalgroup\Email\Model\StatusInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Automation extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var ResourceModel\Automation
     */
    private $automationResource;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    private $dateTime;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Automation constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param ResourceModel\Automation $automationResource
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Model\ResourceModel\Automation $automationResource,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->dateTime     = $dateTime;
        $this->automationResource = $automationResource;
        $this->helper       = $helper;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManagerInterface;
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Constructor.
     *
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init(\Dotdigitalgroup\Email\Model\ResourceModel\Automation::class);
    }

    /**
     * Prepare data to be saved to database.
     *
     * @return $this
     */
    public function beforeSave()
    {
        parent::beforeSave();
        if ($this->isObjectNew()) {
            $this->setCreatedAt($this->dateTime->formatDate(true));
        }
        $this->setUpdatedAt($this->dateTime->formatDate(true));

        return $this;
    }

    /**
     * New customer automation
     *
     * @param \Magento\Customer\Model\Customer $customer
     */
    public function newCustomerAutomation($customer)
    {
        $email = $customer->getEmail();
        $websiteId = $customer->getWebsiteId();
        $storeId = $customer->getStoreId();
        $customerId = $customer->getId();
        $store = $this->storeManager->getStore($storeId);
        $storeName = $store->getName();
        try {
            $apiEnabled = $this->scopeConfig->getValue(
                Config::XML_PATH_CONNECTOR_API_ENABLED,
                ScopeInterface::SCOPE_WEBSITE,
                $websiteId
            );

            $programId = $this->scopeConfig->getValue(
                Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_CUSTOMER,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );

            //new contact program mapped
            if ($programId && $apiEnabled) {
                //save automation type
                $this->setEmail($email)
                    ->setAutomationType(AutomationTypeHandler::AUTOMATION_TYPE_NEW_CUSTOMER)
                    ->setEnrolmentStatus(StatusInterface::PENDING)
                    ->setTypeId($customerId)
                    ->setWebsiteId($websiteId)
                    ->setStoreId($storeId)
                    ->setStoreName($storeName)
                    ->setProgramId($programId);

                $this->automationResource->save($this);
            }
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }
    }
}
