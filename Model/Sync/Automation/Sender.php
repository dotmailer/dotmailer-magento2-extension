<?php

declare(strict_types=1);

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
     * @param string|int $websiteId
     * @param string $programId
     * @return void
     * @throws \Exception
     */
    public function sendAutomationEnrolments($type, $contacts, $websiteId, $programId)
    {
        if (!empty($contacts)) {
            $result = $this->sendContactsToAutomation(
                array_values($contacts),
                (int) $websiteId,
                (int) $programId
            );
            if (isset($result->message)) {
                $programMessage = $result->message;
                $programStatus = $programMessage=='Error: ERROR_PROGRAM_NOT_ACTIVE' ?
                    StatusInterface::DEACTIVATED:
                    StatusInterface::FAILED;
            }

            $this->automationResource
                ->updateStatus(
                    array_keys($contacts),
                    $programStatus ?? 'Active',
                    $programMessage ?? '',
                    $this->dateTime->formatDate(true),
                    $type
                );
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
    private function sendContactsToAutomation($contacts, $websiteId, int $programId)
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
