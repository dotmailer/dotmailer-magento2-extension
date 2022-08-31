<?php

namespace Dotdigitalgroup\Email\Model\Sync\Subscriber;

use Dotdigitalgroup\Email\Model\ConsentFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Consent\CollectionFactory as ConsentCollectionFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;

class ConsentDataManager
{
    /**
     * @var ConsentFactory
     */
    private $consentFactory;

    /**
     * @var ConsentCollectionFactory
     */
    private $consentCollectionFactory;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @param ConsentFactory $consentFactory
     * @param ConsentCollectionFactory $consentCollectionFactory
     * @param DateTime $dateTime
     */
    public function __construct(
        ConsentFactory $consentFactory,
        ConsentCollectionFactory $consentCollectionFactory,
        DateTime $dateTime
    ) {
        $this->consentFactory = $consentFactory;
        $this->consentCollectionFactory = $consentCollectionFactory;
        $this->dateTime = $dateTime;
    }

    /**
     * Set subscriber consent data.
     *
     * @param array $contactIds
     * @param int $websiteId
     * @param array $columns
     *
     * @return array
     */
    public function setSubscriberConsentData($contactIds, $websiteId, $columns)
    {
        if (!isset($columns['consent_text'])) {
            return [];
        }

        $consentData = [];
        $results = $this->consentCollectionFactory->create()
            ->getMostRecentConsentDataByContactIds($contactIds);

        foreach ($results as $row) {
            if (!isset($row['consent_url'])) {
                continue;
            }
            $contactId = $row['email_contact_id'];
            $consentData[$contactId]['consent_url'] = $row['consent_url'];
            $consentData[$contactId]['consent_ip'] = $row['consent_ip'];
            $consentData[$contactId]['consent_user_agent'] = $row['consent_user_agent'];
            $consentData[$contactId]['consent_text'] = $this->consentFactory->create()
                ->getConsentTextForWebsite($row['consent_url'], $websiteId);
            $consentData[$contactId]['consent_datetime'] = $this->dateTime->date(
                \DateTime::ATOM,
                $row['consent_datetime']
            );
        }

        return $consentData;
    }
}
