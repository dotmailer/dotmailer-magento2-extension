<?php

namespace Dotdigitalgroup\Email\Model\Sync\Automation;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation as AutomationResource;
use Dotdigitalgroup\Email\Model\StatusInterface;
use Magento\Framework\Stdlib\DateTime;

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
     * @param array $contactsArray
     * @param int $websiteId
     * @param int $programId
     *
     * @throws \Exception
     */
    private function sendSubscribedContactsToAutomation($contactsArray, $websiteId, $programId)
    {
        if (!empty($contactsArray) &&
            $this->checkCampaignEnrolmentActive($programId, $websiteId)
        ) {
            $result = $this->sendContactsToAutomation(
                array_values($contactsArray),
                $websiteId,
                $programId
            );
            //check for error message
            if (isset($result->message)) {
                $this->programStatus = StatusInterface::FAILED;
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
     * Enrol contacts for a program.
     *
     * @param array $contacts
     * @param int $websiteId
     *
     * @return mixed
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
        //api add contact to automation enrolment
        $result = $client->postProgramsEnrolments($data);

        return $result;
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
    private function checkCampaignEnrolmentActive($programId, $websiteId)
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
}
