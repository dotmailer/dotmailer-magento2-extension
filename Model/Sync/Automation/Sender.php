<?php

namespace Dotdigitalgroup\Email\Model\Sync\Automation;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation as AutomationResource;
use Dotdigitalgroup\Email\Model\StatusInterface;
use Magento\Framework\Stdlib\DateTime;
use stdClass;

class Sender
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var AutomationResource
     */
    private $automationResource;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var string
     */
    private $programStatus = 'Active';

    /**
     * @var string
     */
    private $programMessage;

    /**
     * Sender constructor.
     *
     * @param Data $helper
     * @param AutomationResource $automationResource
     * @param DateTime $dateTime
     */
    public function __construct(
        Data $helper,
        AutomationResource $automationResource,
        DateTime $dateTime
    ) {
        $this->helper = $helper;
        $this->automationResource = $automationResource;
        $this->dateTime = $dateTime;
    }

    /**
     * Send automation enrolments.
     *
     * @param string $type
     * @param array $contacts
     * @param int $websiteId
     * @param string $programId
     * @throws \Exception
     */
    public function sendAutomationEnrolments($type, $contacts, $websiteId, $programId)
    {
        if ($contacts) {

            // only for subscribed contacts
            $this->sendSubscribedContactsToAutomation(
                $contacts,
                $websiteId,
                $programId
            );

            // update rows with the new status, and log the error message if fails
            $this->automationResource
                ->updateStatus(
                    array_keys($contacts),
                    $this->programStatus,
                    $this->programMessage,
                    $this->dateTime->formatDate(true),
                    $type
                );
        }
    }

    /**
     * Send subscribed contacts to automation.
     *
     * @param array $contactsArray
     * @param int $websiteId
     * @param int $programId
     *
     * @throws \Exception
     */
    private function sendSubscribedContactsToAutomation($contactsArray, $websiteId, $programId)
    {
        if (!empty($contactsArray)) {
            $result = $this->sendContactsToAutomation(
                array_values($contactsArray),
                $websiteId,
                $programId
            );
            if (isset($result->message)) {
                $this->programMessage = $result->message;
                $this->programStatus = $this->programMessage == 'Error: ERROR_PROGRAM_NOT_ACTIVE' ?
                    StatusInterface::DEACTIVATED :
                    StatusInterface::FAILED;
            }
        }
    }

    /**
     * Enrol contacts for a program.
     *
     * @param array $contacts
     * @param int $websiteId
     * @param int $programId
     *
     * @return stdClass
     * @throws \Exception
     */
    private function sendContactsToAutomation($contacts, $websiteId, $programId)
    {
        $client = $this->helper->getWebsiteApiClient($websiteId);
        $data = [
            'Contacts' => $contacts,
            'ProgramId' => $programId,
            'AddressBooks' => [],
        ];

        return $client->postProgramsEnrolments($data);
    }
}
