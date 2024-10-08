<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Queue\Sync\Automation\AutomationPublisher;
use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationTypeHandler;
use Exception;
use Magento\Customer\Model\Customer;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Automation extends AbstractModel
{
    /**
     * @var ResourceModel\Automation
     */
    private $automationResource;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var AutomationPublisher
     */
    private $publisher;

    /**
     * Automation constructor.
     *
     * @param Logger $logger
     * @param Context $context
     * @param Registry $registry
     * @param DateTime $dateTime
     * @param ResourceModel\Automation $automationResource
     * @param StoreManagerInterface $storeManagerInterface
     * @param ScopeConfigInterface $scopeConfig
     * @param AutomationPublisher $publisher
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Logger $logger,
        Context $context,
        Registry $registry,
        DateTime $dateTime,
        ResourceModel\Automation $automationResource,
        StoreManagerInterface $storeManagerInterface,
        ScopeConfigInterface $scopeConfig,
        AutomationPublisher $publisher,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->logger = $logger;
        $this->dateTime = $dateTime;
        $this->automationResource = $automationResource;
        $this->scopeConfig = $scopeConfig;
        $this->publisher = $publisher;
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
        $this->_init(ResourceModel\Automation::class);
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
     * @param Customer $customer
     */
    public function newCustomerAutomation($customer)
    {
        try {
            $email = $customer->getEmail();
            $websiteId = $customer->getWebsiteId();
            $storeId = $customer->getStoreId();
            $customerId = $customer->getId();
            $store = $this->storeManager->getStore($storeId);
            $storeName = $store->getName();

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

                $this->publisher->publish($this);
            }
        } catch (Exception $e) {
            $this->logger->error('Error creating new customer automation', [(string) $e]);
        }
    }
}
