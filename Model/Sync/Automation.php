<?php

namespace Dotdigitalgroup\Email\Model\Sync;

/**
 * Sync automation by type.
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class Automation
{
    const AUTOMATION_TYPE_NEW_CUSTOMER = 'customer_automation';
    const AUTOMATION_TYPE_NEW_SUBSCRIBER = 'subscriber_automation';
    const AUTOMATION_TYPE_NEW_ORDER = 'order_automation';
    const AUTOMATION_TYPE_NEW_GUEST_ORDER = 'guest_order_automation';
    const AUTOMATION_TYPE_NEW_REVIEW = 'review_automation';
    const AUTOMATION_TYPE_NEW_WISHLIST = 'wishlist_automation';
    const AUTOMATION_STATUS_PENDING = 'pending';
    const ORDER_STATUS_AUTOMATION = 'order_automation_';
    const AUTOMATION_TYPE_CUSTOMER_FIRST_ORDER = 'first_order_automation';

    /**
     * @var array
     */
    public $automationTypes = [
        self::AUTOMATION_TYPE_NEW_CUSTOMER =>
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_CUSTOMER,
        self::AUTOMATION_TYPE_NEW_SUBSCRIBER =>
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_SUBSCRIBER,
        self::AUTOMATION_TYPE_NEW_ORDER =>
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_ORDER,
        self::AUTOMATION_TYPE_NEW_GUEST_ORDER =>
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_GUEST_ORDER,
        self::AUTOMATION_TYPE_NEW_REVIEW =>
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_REVIEW,
        self::AUTOMATION_TYPE_NEW_WISHLIST =>
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_WISHLIST,
        self::AUTOMATION_TYPE_CUSTOMER_FIRST_ORDER =>
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_FIRST_ORDER
    ];

    /**
     * @var int
     */
    private $limit = 100;

    /**
     * @var string
     */
    private $typeId;

    /**
     * @var int
     */
    private $websiteId;

    /**
     * @var string
     */
    private $storeName;

    /**
     * @var string
     */
    private $programId;

    /**
     * @var string
     */
    private $programStatus = 'Active';

    /**
     * @var string
     */
    private $programMessage;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;
    
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resource;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $localeDate;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Automation\CollectionFactory
     */
    private $automationFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Automation
     */
    private $automationResource;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $orderFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\Config\Json
     */
    private $serializer;

    /**
     * Automation constructor.
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Automation\CollectionFactory $automationFactory
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Dotdigitalgroup\Email\Model\Config\Json $serializer
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Automation $automationResource
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Automation\CollectionFactory $automationFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Model\Config\Json $serializer,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Automation $automationResource
    ) {
        $this->serializer = $serializer;
        $this->automationFactory = $automationFactory;
        $this->helper            = $helper;
        $this->storeManager      = $storeManagerInterface;
        $this->resource          = $resource;
        $this->localeDate        = $localeDate;
        $this->orderFactory      = $orderFactory;
        $this->automationResource = $automationResource;
    }

    /**
     * Sync.
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @return null
     */
    public function sync()
    {
        $this->setupAutomationTypes();

        //send the campaign by each types
        foreach ($this->automationTypes as $type => $config) {
            $contacts = $this->buildFirstDimensionOfContactsArray($type, $config);
            //get collection from type
            $automationCollection = $this->automationFactory->create()
                ->getCollectionByType($type, $this->limit);

            foreach ($automationCollection as $automation) {
                $type = $automation->getAutomationType();
                $email = $automation->getEmail();
                $this->typeId = $automation->getTypeId();
                $this->websiteId = $automation->getWebsiteId();
                $this->storeName = $automation->getStoreName();
                $typeDouble = $type;
                //Set type to generic automation status if type contains constant value
                if (strpos($typeDouble, self::ORDER_STATUS_AUTOMATION) !== false) {
                    $typeDouble = self::ORDER_STATUS_AUTOMATION;
                }
                $contactId = $this->helper->getContactId(
                    $email,
                    $this->websiteId
                );
                //contact id is valid, can update datafields
                if ($contactId) {
                    //need to update datafields
                    $this->updateDatafieldsByType(
                        $typeDouble,
                        $email
                    );
                    $contacts[$automation->getWebsiteId()]['contacts'][$automation->getId()] = $contactId;
                } else {
                    // the contact is suppressed or the request failed
                    $automation->setEnrolmentStatus('Suppressed');
                    $this->automationResource->save($automation);
                }
            }
            foreach ($contacts as $websiteId => $websiteContacts) {
                if (isset($websiteContacts['contacts'])) {
                    $this->programId = $websiteContacts['programId'];
                    $contactsArray = $websiteContacts['contacts'];

                    //only for subscribed contacts
                    $this->sendSubscribedContactsToAutomation($contactsArray, $websiteId);

                    //update contacts with the new status, and log the error message if fails
                    $contactIds = array_keys($contactsArray);
                    $updatedAt = $this->localeDate->date(
                        null,
                        null,
                        false
                    )->format('Y-m-d H:i:s');
                    $this->automationResource
                        ->updateStatus(
                            $contactIds,
                            $this->programStatus,
                            $this->programMessage,
                            $updatedAt,
                            $type
                        );
                }
            }
        }
    }

    /**
     * Update single contact datafields for this automation type.
     *
     * @param string $type
     * @param string $email
     *
     * @return null
     */
    public function updateDatafieldsByType($type, $email)
    {
        switch ($type) {
            case self::AUTOMATION_TYPE_NEW_ORDER:
            case self::AUTOMATION_TYPE_NEW_GUEST_ORDER:
            case self::ORDER_STATUS_AUTOMATION:
            case self::AUTOMATION_TYPE_CUSTOMER_FIRST_ORDER:
                $this->_updateNewOrderDatafields();
                break;
            default:
                $this->_updateDefaultDatafields($email);
                break;
        }
    }

    /**
     * Update config datafield.
     *
     * @param string $email
     *
     * @return null
     */
    public function _updateDefaultDatafields($email)
    {
        $website = $this->storeManager->getWebsite($this->websiteId);
        $this->helper->updateDataFields($email, $website, $this->storeName);
    }

    /**
     * Update new order default datafields.
     *
     * @return null
     */
    public function _updateNewOrderDatafields()
    {
        $website = $this->storeManager->getWebsite($this->websiteId);
        $orderModel = $this->orderFactory->create()
            ->loadByIncrementId($this->typeId);

        //data fields
        if ($lastOrderId = $website->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOMER_LAST_ORDER_ID
        )
        ) {
            $data[] = [
                'Key' => $lastOrderId,
                'Value' => $orderModel->getId(),
            ];
        }
        if ($orderIncrementId = $website->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOMER_LAST_ORDER_INCREMENT_ID
        )
        ) {
            $data[] = [
                'Key' => $orderIncrementId,
                'Value' => $orderModel->getIncrementId(),
            ];
        }
        if ($storeName = $website->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOMER_STORE_NAME
        )
        ) {
            $data[] = [
                'Key' => $storeName,
                'Value' => $this->storeName,
            ];
        }
        if ($websiteName = $website->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOMER_WEBSITE_NAME
        )
        ) {
            $data[] = [
                'Key' => $websiteName,
                'Value' => $website->getName(),
            ];
        }
        if ($lastOrderDate = $website->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOMER_LAST_ORDER_DATE
        )
        ) {
            $data[] = [
                'Key' => $lastOrderDate,
                'Value' => $orderModel->getCreatedAt(),
            ];
        }
        if (($customerId = $website->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOMER_ID
        ))
            && $orderModel->getCustomerId()
        ) {
            $data[] = [
                'Key' => $customerId,
                'Value' => $orderModel->getCustomerId(),
            ];
        }
        if (!empty($data)) {
            //update data fields
            $client = $this->helper->getWebsiteApiClient($website);
            $client->updateContactDatafieldsByEmail(
                $orderModel->getCustomerEmail(),
                $data
            );
        }
    }

    /**
     * Program check if is valid and active.
     *
     * @param int $programId
     *
     * @return bool
     */
    public function _checkCampignEnrolmentActive($programId)
    {
        //program is not set
        if (!$programId) {
            return false;
        }
        $client = $this->helper->getWebsiteApiClient($this->websiteId);
        $program = $client->getProgramById($programId);
        //program status
        if (isset($program->status)) {
            $this->programStatus = $program->status;
        }
        if (isset($program->status) && $program->status == 'Active') {
            return true;
        }

        return false;
    }

    /**
     * Enrol contacts for a program.
     *
     * @param array $contacts
     * @param int $websiteId
     *
     * @return mixed
     */
    public function sendContactsToAutomation($contacts, $websiteId)
    {
        $client = $this->helper->getWebsiteApiClient($websiteId);
        $data = [
            'Contacts' => $contacts,
            'ProgramId' => $this->programId,
            'AddressBooks' => [],
        ];
        //api add contact to automation enrolment
        $result = $client->postProgramsEnrolments($data);

        return $result;
    }

    /**
     * Setup automation types
     *
     * @return null
     */
    private function setupAutomationTypes()
    {
        $statusTypes = $this->automationFactory->create()
            ->getAutomationStatusType();

        foreach ($statusTypes as $type) {
            $this->automationTypes[$type]
                = \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_ORDER_STATUS;
        }
    }

    /**
     * @param string $type
     * @param string $config
     *
     * @return mixed
     */
    private function buildFirstDimensionOfContactsArray($type, $config)
    {
        $contacts = [];
        $websites = $this->helper->getWebsites(true);
        foreach ($websites as $website) {
            if (strpos($type, self::ORDER_STATUS_AUTOMATION) !== false) {
                $configValue = $this->serializer->unserialize($this->helper->getWebsiteConfig($config, $website));

                if (is_array($configValue) && !empty($configValue)) {
                    foreach ($configValue as $one) {
                        if (strpos($type, $one['status']) !== false) {
                            $contacts[$website->getId()]['programId']
                                = $one['automation'];
                        }
                    }
                }
            } else {
                $contacts[$website->getId()]['programId']
                    = $this->helper->getWebsiteConfig($config, $website);
            }
        }
        return $contacts;
    }

    /**
     * @param array  $contactsArray
     * @param int  $websiteId
     *
     * @return null
     */
    private function sendSubscribedContactsToAutomation($contactsArray, $websiteId)
    {
        if (!empty($contactsArray) &&
            $this->_checkCampignEnrolmentActive($this->programId)
        ) {
            $result = $this->sendContactsToAutomation(
                array_values($contactsArray),
                $websiteId
            );
            //check for error message
            if (isset($result->message)) {
                $this->programStatus = 'Failed';
                $this->programMessage = $result->message;
            }
            //program is not active
        } elseif ($this->programMessage
            == 'Error: ERROR_PROGRAM_NOT_ACTIVE '
        ) {
            $this->programStatus = 'Deactivated';
        }
    }
}
