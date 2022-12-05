<?php

namespace Dotdigitalgroup\Email\Model\Sync\Automation;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Contact\ContactResponseHandler;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation as AutomationResource;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\DataFieldUpdateHandler;
use Dotdigitalgroup\Email\Model\StatusInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Newsletter\Model\SubscriberFactory;

class AutomationProcessor
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var ContactResponseHandler
     */
    protected $contactResponseHandler;

    /**
     * @var AutomationResource
     */
    protected $automationResource;

    /**
     * @var CollectionFactory
     */
    protected $contactCollectionFactory;

    /**
     * @var DataFieldUpdateHandler
     */
    protected $dataFieldUpdateHandler;

    /**
     * @var SubscriberFactory
     */
    protected $subscriberFactory;

    /**
     * AutomationProcessor constructor.
     *
     * @param Data $helper
     * @param ContactResponseHandler $contactResponseHandler
     * @param AutomationResource $automationResource
     * @param CollectionFactory $contactCollectionFactory
     * @param DataFieldUpdateHandler $dataFieldUpdateHandler
     * @param SubscriberFactory $subscriberFactory
     */
    public function __construct(
        Data $helper,
        ContactResponseHandler $contactResponseHandler,
        AutomationResource $automationResource,
        CollectionFactory $contactCollectionFactory,
        DataFieldUpdateHandler $dataFieldUpdateHandler,
        SubscriberFactory $subscriberFactory
    ) {
        $this->helper = $helper;
        $this->contactResponseHandler = $contactResponseHandler;
        $this->automationResource = $automationResource;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->dataFieldUpdateHandler = $dataFieldUpdateHandler;
        $this->subscriberFactory = $subscriberFactory;
    }

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process($collection)
    {
        $data = [];

        foreach ($collection as $automation) {
            $email = $automation->getEmail();
            $websiteId = $automation->getWebsiteId();
            $storeId = $automation->getStoreId();
            $customerAddressBookId = $this->helper->getCustomerAddressBook($websiteId);
            $guestAddressBookId = $this->helper->getGuestAddressBook($websiteId);
            $addressBookId = '';

            $automationContact = $this->contactCollectionFactory->create()
                ->loadByCustomerEmail($email, $websiteId);

            /** @var AbstractModel $automationContact */
            if ($automationContact->getCustomerId()) {
                $addressBookId = $customerAddressBookId;
            } elseif ($automationContact->getIsGuest()) {
                $addressBookId = $guestAddressBookId;
            }

            $contact = $addressBookId ?
                $this->pushContactToAddressBook($email, $websiteId, $addressBookId) :
                $this->helper->getOrCreateContact($email, $websiteId);

            /** @var \stdClass $contact */
            if ($contact && isset($contact->id)) {
                if ($contact->status === StatusInterface::PENDING_OPT_IN) {
                    $this->automationResource->setStatusAndSaveAutomation(
                        $automation,
                        StatusInterface::PENDING_OPT_IN
                    );
                    continue;
                }

                if ($this->shouldExitLoop($automation)) {
                    continue;
                }

                $this->pushContactToSubscriberAddressBook($email, $websiteId);
                $this->orchestrateDataFieldUpdate($automation, $email, $websiteId);

                $data[$websiteId][$storeId]['contacts'][$automation->getId()] = $contact->id;
            } else {
                // the contact is suppressed or the request failed
                $this->automationResource->setStatusAndSaveAutomation(
                    $automation,
                    StatusInterface::FAILED,
                    'Contact cannot be created or has been suppressed'
                );
            }
        }

        return $data;
    }

    /**
     * @param \Dotdigitalgroup\Email\Model\Automation $automation
     * @return bool
     */
    protected function shouldExitLoop($automation)
    {
        return false;
    }

    /**
     * @param \Dotdigitalgroup\Email\Model\Automation $automation
     * @param string $email
     * @param string|int $websiteId
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function orchestrateDataFieldUpdate($automation, $email, $websiteId)
    {
        $type = $automation->getAutomationType();
        //Set type to generic automation status if type contains constant value
        if (strpos($type, AutomationTypeHandler::ORDER_STATUS_AUTOMATION) !== false) {
            $type = AutomationTypeHandler::ORDER_STATUS_AUTOMATION;
        }

        $this->dataFieldUpdateHandler->updateDatafieldsByType(
            $type,
            $email,
            $websiteId,
            $automation->getTypeId(),
            $automation->getStoreName()
        );
    }

    /**
     * Add contact to an address book.
     *
     * @param string $email
     * @param int $websiteId
     * @param string $addressBookId
     *
     * @return bool|\stdClass
     */
    private function pushContactToAddressBook($email, $websiteId, string $addressBookId)
    {
        $client = $this->helper->getWebsiteApiClient($websiteId);
        $contactParams = [
            'Email' => $email,
            'EmailType' => 'Html',
        ];

        $response = $client->postAddressBookContacts($addressBookId, $contactParams);

        return $this->contactResponseHandler->updateContactFromResponse($response, $email, $websiteId);
    }

    /**
     * Add subscribers to subscriber address book
     *
     * @param string $email
     * @param string|int $websiteId
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function pushContactToSubscriberAddressBook($email, $websiteId): void
    {
        $subscriber = $this->subscriberFactory->create()
            ->loadBySubscriberEmail($email, $websiteId);
        if (!$subscriber->getId()) {
            return;
        }

        $subscriberAddressBookId = $this->helper->getSubscriberAddressBook($websiteId);
        if (!$subscriberAddressBookId) {
            return;
        }

        $client = $this->helper->getWebsiteApiClient($websiteId);
        $contactParams = [
            'Email' => $email,
            'EmailType' => 'Html',
        ];
        $client->postAddressBookContacts($subscriberAddressBookId, $contactParams);
    }
}
