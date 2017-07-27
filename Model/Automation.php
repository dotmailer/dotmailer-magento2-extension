<?php

namespace Dotdigitalgroup\Email\Model;

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
     * Automation constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param ResourceModel\Automation $automationResource
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
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
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->dateTime     = $dateTime;
        $this->automationResource = $automationResource;
        $this->helper       = $helper;
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
     * @return null
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
     *
     * @return null
     */
    public function newCustomerAutomation($customer)
    {
        $email = $customer->getEmail();
        $websiteId = $customer->getWebsiteId();
        $customerId = $customer->getId();
        $store = $this->storeManager->getStore($customer->getStoreId());
        $storeName = $store->getName();
        try {
            //Api is enabled
            $apiEnabled = $this->helper->getWebsiteConfig(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_API_ENABLED,
                $websiteId
            );

            //Automation enrolment
            $programId = $this->helper->getWebsiteConfig(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_CUSTOMER,
                $websiteId
            );

            //new contact program mapped
            if ($programId && $apiEnabled) {
                //save automation type
                $this->setEmail($email)
                    ->setAutomationType(\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_TYPE_NEW_CUSTOMER)
                    ->setEnrolmentStatus(\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_STATUS_PENDING)
                    ->setTypeId($customerId)
                    ->setWebsiteId($websiteId)
                    ->setStoreName($storeName)
                    ->setProgramId($programId);

                $this->automationResource->save($this);
            }
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }
    }
}
