<?php

namespace Dotdigitalgroup\Email\Model\Sync;

/**
 * Sync automation by type.
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class Automation implements SyncInterface
{
    const AUTOMATION_TYPE_NEW_CUSTOMER = 'customer_automation';
    const AUTOMATION_TYPE_NEW_SUBSCRIBER = 'subscriber_automation';
    const AUTOMATION_TYPE_NEW_ORDER = 'order_automation';
    const AUTOMATION_TYPE_NEW_GUEST_ORDER = 'guest_order_automation';
    const AUTOMATION_TYPE_NEW_REVIEW = 'review_automation';
    const AUTOMATION_TYPE_NEW_WISHLIST = 'wishlist_automation';
    const AUTOMATION_STATUS_PENDING = 'pending';
    const AUTOMATION_TYPE_CUSTOMER_FIRST_ORDER = 'first_order_automation';
    const AUTOMATION_TYPE_ABANDONED_CART_PROGRAM_ENROLMENT = 'abandoned_cart_automation';
    const ORDER_STATUS_AUTOMATION = 'order_automation_';
    const CONTACT_STATUS_PENDING = "PendingOptIn";
    const CONTACT_STATUS_CONFIRMED = "Confirmed";
    const CONTACT_STATUS_EXPIRED = "Expired";

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
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_FIRST_ORDER,
        self::AUTOMATION_TYPE_ABANDONED_CART_PROGRAM_ENROLMENT =>
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_LOSTBASKET_ENROL_TO_PROGRAM_ID,
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
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resource;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    private $dateTime;

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
     * @var \Dotdigitalgroup\Email\Model\DateIntervalFactory
     */
    private $dateIntervalFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $timeZone;

    // this doesn't seem right but how else to access core Quote methods as well as custom ones defined in our module?

    /**
     * @var \Dotdigitalgroup\Email\Model\Automation\UpdateFields\Update
     */
    private $updateAbandoned;

    /**
     * Automation constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Automation\CollectionFactory $automationFactory
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Automation $automationResource
     * @param \Dotdigitalgroup\Email\Model\DateIntervalFactory $dateIntervalFactory,
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone,
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Automation\CollectionFactory $automationFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Automation $automationResource,
        \Dotdigitalgroup\Email\Model\DateIntervalFactory $dateIntervalFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone,
        \Dotdigitalgroup\Email\Model\Automation\UpdateFields\Update $updateAbandoned
    ) {
        $this->automationFactory = $automationFactory;
        $this->helper            = $helper;
        $this->resource          = $resource;
        $this->dateTime          = $dateTime;
        $this->orderFactory      = $orderFactory;
        $this->automationResource = $automationResource;
        $this->dateIntervalFactory = $dateIntervalFactory;
        $this->timeZone = $timeZone;
        $this->updateAbandoned = $updateAbandoned;
    }

    /**
     * Sync.
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @param \DateTime|null $from
     * @return null
     */
    public function sync(\DateTime $from = null)
    {
        $this->checkStatusForPendingContacts();
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
                $websiteId = $automation->getWebsiteId();
                $this->storeName = $automation->getStoreName();
                $typeDouble = $type;
                //Set type to generic automation status if type contains constant value
                if (strpos($typeDouble, self::ORDER_STATUS_AUTOMATION) !== false) {
                    $typeDouble = self::ORDER_STATUS_AUTOMATION;
                }
                $contact = $this->helper->getContact($email, $websiteId);
                //contact id is valid, can update datafields
                if ($contact && isset($contact->id)) {
                    if ($contact->status === self::CONTACT_STATUS_PENDING) {
                        $automation->setEnrolmentStatus(self::CONTACT_STATUS_PENDING);
                        $this->automationResource->save($automation);
                        continue;
                    }

                    //need to update datafields
                    $this->updateDatafieldsByType(
                        $typeDouble,
                        $email,
                        $websiteId
                    );
                    $contacts[$automation->getWebsiteId()]['contacts'][$automation->getId()] = $contact->id;
                } else {
                    // the contact is suppressed or the request failed
                    $automation->setEnrolmentStatus('Suppressed');
                    $this->automationResource->save($automation);
                }
            }
            $this->sendAutomationEnrolements($contacts, $type);
        }
    }

    /**
     * check automation entries for pending contacts
     */
    private function checkStatusForPendingContacts()
    {
        $updatedAt = $this->dateTime->formatDate(true);

        if ($this->isItTimeToCheckPendingContact()) {
            $collection = $this->automationFactory->create()
                ->getCollectionByPendingStatus();
            $idsToUpdateStatus = [];
            $idsToUpdateDate = [];

            foreach ($collection as $item) {
                $contact = $this->helper->getContact($item->getEmail(), $item->getWebsiteId());
                if (isset($contact->id) && $contact->status !== self::CONTACT_STATUS_PENDING) {
                    //add to array for update status
                    $idsToUpdateStatus[] = $item->getId();
                } else {
                    //add to array for update date
                    $idsToUpdateDate[] = $item->getId();
                }
            }

            if (! empty($idsToUpdateStatus)) {
                $this->automationResource
                    ->update(
                        $idsToUpdateStatus,
                        $updatedAt,
                        self::CONTACT_STATUS_CONFIRMED
                    );
            }

            if (! empty($idsToUpdateDate)) {
                $this->automationResource
                    ->update(
                        $idsToUpdateDate,
                        $updatedAt
                    );
            }
        }

        //Get pending with 24 house delay and expire it
        $collection = $this->automationFactory->create()
            ->getCollectionByPendingStatus($this->getDateTimeForExpiration());
        $ids = $collection->getColumnValues('id');
        if (! empty($ids)) {
            $this->automationResource
                ->update(
                    $ids,
                    $updatedAt,
                    self::CONTACT_STATUS_EXPIRED
                );
        }
    }

    /**
     * @return string
     */
    private function getDateTimeForExpiration()
    {
        $hours = (int) $this->helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_AC_AUTOMATION_EXPIRE_TIME
        );
        $interval = $this->dateIntervalFactory->create(
            ['interval_spec' => sprintf('PT%sH', $hours)]
        );
        $dateTime = $this->timeZone->date();
        $dateTime->sub($interval);
        return $dateTime->format('Y-m-d H:i:s');
    }

    /**
     * @return boolean
     */
    private function isItTimeToCheckPendingContact()
    {
        $dateTimeFromDb = $this->automationFactory->create()->getLastPendingStatusCheckTime();
        if (! $dateTimeFromDb) {
            return false;
        }

        $lastCheckTime = $this->timeZone->date($dateTimeFromDb);
        $interval = $this->dateIntervalFactory->create(['interval_spec' => 'PT30M']);
        $lastCheckTime->add($interval);
        $now = $this->timeZone->date();
        return ($now->format('Y-m-d H:i:s') > $lastCheckTime->format('Y-m-d H:i:s'));
    }

    /**
     * Update single contact datafields for this automation type.
     *
     * @param string $type
     * @param string $email
     * @param int $websiteId
     *
     * @return null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function updateDatafieldsByType($type, $email, $websiteId)
    {
        switch ($type) {
            case self::AUTOMATION_TYPE_NEW_ORDER:
            case self::AUTOMATION_TYPE_NEW_GUEST_ORDER:
            case self::ORDER_STATUS_AUTOMATION:
            case self::AUTOMATION_TYPE_CUSTOMER_FIRST_ORDER:
                $this->updateNewOrderDatafields($websiteId);
                break;
            case self::AUTOMATION_TYPE_ABANDONED_CART_PROGRAM_ENROLMENT:
                $this->updateAbandoned->updateAbandonedCartDatafields($email, $websiteId, $this->typeId, $this->storeName);
            default:
                $this->updateDefaultDatafields($email, $websiteId);
                break;
        }
    }

    /**
     * Update config datafield.
     *
     * @param string $email
     * @param int $websiteId
     *
     * @return null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function updateDefaultDatafields($email, $websiteId)
    {
        $website = $this->helper->storeManager->getWebsite($websiteId);
        $this->helper->updateDataFields($email, $website, $this->storeName);
    }

    /**
     * Update new order default datafields.
     *
     * @param int $websiteId
     *
     * @return null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function updateNewOrderDatafields($websiteId)
    {
        $website = $this->helper->storeManager->getWebsite($websiteId);
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
     * @param int $websiteId
     *
     * @return bool
     * @throws \Exception
     */
    private function checkCampignEnrolmentActive($programId, $websiteId)
    {
        //program is not set
        if (!$programId) {
            return false;
        }
        $client = $this->helper->getWebsiteApiClient($websiteId);
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
     * @throws \Exception
     */
    private function sendContactsToAutomation($contacts, $websiteId)
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
     * @return array
     */
    private function buildFirstDimensionOfContactsArray($type, $config)
    {
        $contacts = [];
        $websites = $this->helper->getWebsites(true);
        foreach ($websites as $website) {
            if (strpos($type, self::ORDER_STATUS_AUTOMATION) !== false) {
                $configValue = $this->helper->serializer->unserialize(
                    $this->helper->getWebsiteConfig($config, $website)
                );

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
     * @param array $contactsArray
     * @param int $websiteId
     *
     * @return null
     * @throws \Exception
     */
    private function sendSubscribedContactsToAutomation($contactsArray, $websiteId)
    {
        if (!empty($contactsArray) &&
            $this->checkCampignEnrolmentActive($this->programId, $websiteId)
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

    /**
     * @param $contacts
     * @param $type
     */
    private function sendAutomationEnrolements($contacts, $type)
    {
        foreach ($contacts as $websiteId => $websiteContacts) {
            if (isset($websiteContacts['contacts'])) {
                $this->programId = $websiteContacts['programId'];
                $contactsArray = $websiteContacts['contacts'];

                //only for subscribed contacts
                $this->sendSubscribedContactsToAutomation($contactsArray, $websiteId);

                //update contacts with the new status, and log the error message if fails
                $contactIds = array_keys($contactsArray);
                $updatedAt = $this->dateTime->formatDate(true);
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
