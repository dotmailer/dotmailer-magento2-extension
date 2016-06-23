<?php

namespace Dotdigitalgroup\Email\Model\Sync;

class Automation
{
    const AUTOMATION_TYPE_NEW_CUSTOMER = 'customer_automation';
    const AUTOMATION_TYPE_NEW_SUBSCRIBER = 'subscriber_automation';
    const AUTOMATION_TYPE_NEW_ORDER = 'order_automation';
    const AUTOMATION_TYPE_NEW_GUEST_ORDER = 'guest_order_automation';
    const AUTOMATION_TYPE_NEW_REVIEW = 'review_automation';
    const AUTOMATION_TYPE_NEW_WISHLIST = 'wishlist_automation';
    const AUTOMATION_STATUS_PENDING = 'pending';
    public $automationTypes = array(
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
        'order_automation_' =>
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_ORDER_STATUS
    );

    /**
     * @var int
     */
    public $limit = 100;
    /**
     * @var
     */
    public $email;
    /**
     * @var
     */
    public $typeId;
    /**
     * @var
     */
    public $websiteId;
    /**
     * @var
     */
    public $storeName;
    /**
     * @var
     */
    public $programId;
    /**
     * @var string
     */
    public $programStatus = 'Active';
    /**
     * @var
     */
    public $programMessage;
    /**
     * @var
     */
    public $automationType;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var
     */
    protected $_objectManager;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resource;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_localeDate;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Automation\CollectionFactory
     */
    protected $_automationFactory;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * Automation constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Automation\CollectionFactory $automationFactory
     * @param \Magento\Framework\App\ResourceConnection                          $resource
     * @param \Dotdigitalgroup\Email\Helper\Data                                 $helper
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface               $localeDate
     * @param \Magento\Store\Model\StoreManagerInterface                         $storeManagerInterface
     * @param \Magento\Sales\Model\OrderFactory                                  $orderFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Automation\CollectionFactory $automationFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Sales\Model\OrderFactory $orderFactory
    ) {
        $this->_automationFactory = $automationFactory;
        $this->_helper = $helper;
        $this->_storeManager = $storeManagerInterface;
        $this->_resource = $resource;
        $this->_localeDate = $localeDate;
        $this->_orderFactory = $orderFactory;
    }

    /**
     * Sync.
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function sync()
    {
        //send the campaign by each types
        foreach ($this->automationTypes as $type => $config) {
            $contacts = [];
            $websites = $this->_helper->getWebsites(true);
            foreach ($websites as $website) {
                $contacts[$website->getId()]['programId'] = $this->_helper->getWebsiteConfig($config, $website);
            }
            //get collection from type
            $automationCollection = $this->_automationFactory->create()
                ->addFieldToFilter(
                    'enrolment_status', self::AUTOMATION_STATUS_PENDING
                );
            if ($type == 'order_automation_') {
                $automationCollection->addFieldToFilter(
                    'automation_type',
                    array('like' => '%' . $type . '%')
                );
            } else {
                $automationCollection->addFieldToFilter(
                    'automation_type', $type
                );
            }
            //limit because of the each contact request to get the id
            $automationCollection->getSelect()->limit($this->limit);
            foreach ($automationCollection as $automation) {
                $type = $automation->getAutomationType();
                //customerid, subscriberid, wishlistid..
                $email = $automation->getEmail();
                $this->typeId = $automation->getTypeId();
                $this->websiteId = $automation->getWebsiteId();
                $this->storeName = $automation->getStoreName();
                $contactId = $this->_helper->getContactId(
                    $email, $this->websiteId
                );
                //contact id is valid, can update datafields
                if ($contactId) {
                    //need to update datafields
                    $this->updateDatafieldsByType(
                        $this->automationType, $email
                    );
                    $contacts[$automation->getWebsiteId()]['contacts'][$automation->getId()] = $contactId;
                } else {
                    // the contact is suppressed or the request failed
                    $automation->setEnrolmentStatus('Suppressed')
                        ->save();
                }
            }
            foreach ($contacts as $websiteId => $websiteContacts) {
                if (isset($websiteContacts['contacts'])) {
                    $this->programId = $websiteContacts['programId'];
                    $contactsArray = $websiteContacts['contacts'];
                    //only for subscribed contacts
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
                    //update contacts with the new status, and log the error message if failes
                    $coreResource = $this->_resource;
                    $conn = $coreResource->getConnection('core_write');
                    try {
                        $contactIds = array_keys($contactsArray);
                        $bind = [
                            'enrolment_status' => $this->programStatus,
                            'message' => $this->programMessage,
                            'updated_at' => $this->_localeDate->date(
                                null, null, false
                            )->format('Y-m-d H:i:s'),
                        ];
                        $where = ['id IN(?)' => $contactIds];
                        $num = $conn->update(
                            $coreResource->getTableName('email_automation'),
                            $bind,
                            $where
                        );
                        //number of updated records
                        if ($num) {
                            $this->_helper->log(
                                'Automation type : ' . $type . ', updated : ' . $num
                            );
                        }
                    } catch (\Exception $e) {
                        throw new \Magento\Framework\Exception\LocalizedException(
                            __($e->getMessage())
                        );
                    }
                }
            }
        }
    }

    /**
     * Update single contact datafields for this automation type.
     *
     * @param $type
     * @param $email
     */
    public function updateDatafieldsByType($type, $email)
    {
        switch ($type) {
            case self::AUTOMATION_TYPE_NEW_CUSTOMER :
                $this->_updateDefaultDatafields($email);
                break;
            case self::AUTOMATION_TYPE_NEW_SUBSCRIBER :
                $this->_updateDefaultDatafields($email);
                break;
            case self::AUTOMATION_TYPE_NEW_ORDER :
                $this->_updateNewOrderDatafields();
                break;
            case self::AUTOMATION_TYPE_NEW_GUEST_ORDER:
                $this->_updateNewOrderDatafields();
                break;
            case self::AUTOMATION_TYPE_NEW_REVIEW :
                $this->_updateNewOrderDatafields();
                break;
            case self::AUTOMATION_TYPE_NEW_WISHLIST:
                $this->_updateDefaultDatafields($email);
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
     */
    protected function _updateDefaultDatafields($email)
    {
        $website = $this->_storeManager->getWebsite($this->websiteId);
        $this->_helper->updateDataFields($email, $website, $this->storeName);
    }

    /**
     * Update new order default datafields.
     */
    protected function _updateNewOrderDatafields()
    {
        $website = $this->_storeManager->getWebsite($this->websiteId);
        $orderModel = $this->_orderFactory->create()
            ->load($this->typeId);
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
            $client = $this->_helper->getWebsiteApiClient($website);
            $client->updateContactDatafieldsByEmail(
                $orderModel->getCustomerEmail(), $data
            );
        }
    }

    /**
     * Program check if is valid and active.
     *
     * @param $programId
     *
     * @return bool
     */
    protected function _checkCampignEnrolmentActive($programId)
    {
        //program is not set
        if (!$programId) {
            return false;
        }
        $client = $this->_helper->getWebsiteApiClient($this->websiteId);
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
     * @param $contacts
     * @param $websiteId
     */
    public function sendContactsToAutomation($contacts, $websiteId)
    {
        $client = $this->_helper->getWebsiteApiClient($websiteId);
        $data = [
            'Contacts' => $contacts,
            'ProgramId' => $this->programId,
            'AddressBooks' => [],
        ];
        //api add contact to automation enrolment
        $result = $client->postProgramsEnrolments($data);

        return $result;
    }
}
