<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Queue\Customer;

use Dotdigitalgroup\Email\Model\Queue\Data\EmailUpdateData;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\ResourceModel\AutomationFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\CampaignFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\AbandonedFactory;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory;
use Dotdigital\V3\Models\ContactFactory as DotdigitalContactFactory;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Email\Model\Contact\ContactResponseHandler;
use Magento\Framework\Exception\LocalizedException;

class EmailUpdateConsumer
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var AutomationFactory
     */
    private $automationFactory;

    /**
     * @var CampaignFactory
     */
    private $campaignFactory;

    /**
     * @var AbandonedFactory
     */
    private $abandonedFactory;

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var DotdigitalContactFactory
     */
    private $sdkContactFactory;

    /**
     * @var ContactCollectionFactory
     */
    private $contactCollectionFactory;

    /**
     * @var ContactResource
     */
    private $contactResource;

    /**
     * @var ContactResponseHandler
     */
    private $contactResponseHandler;

    /**
     * @param ClientFactory $clientFactory
     * @param Logger $logger
     * @param AutomationFactory $automationFactory
     * @param CampaignFactory $campaignFactory
     * @param AbandonedFactory $abandonedFactory
     * @param DotdigitalContactFactory $sdkContactFactory
     * @param ContactCollectionFactory $contactCollectionFactory
     * @param ContactResource $contactResource
     * @param ContactResponseHandler $contactResponseHandler
     */
    public function __construct(
        ClientFactory $clientFactory,
        Logger $logger,
        AutomationFactory $automationFactory,
        CampaignFactory $campaignFactory,
        AbandonedFactory $abandonedFactory,
        DotdigitalContactFactory $sdkContactFactory,
        ContactCollectionFactory $contactCollectionFactory,
        ContactResource $contactResource,
        ContactResponseHandler $contactResponseHandler
    ) {
        $this->clientFactory = $clientFactory;
        $this->logger = $logger;
        $this->automationFactory = $automationFactory;
        $this->campaignFactory = $campaignFactory;
        $this->abandonedFactory = $abandonedFactory;
        $this->sdkContactFactory = $sdkContactFactory;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->contactResource = $contactResource;
        $this->contactResponseHandler = $contactResponseHandler;
    }

    /**
     * Process.
     *
     * @param EmailUpdateData $emailUpdateData
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process(EmailUpdateData $emailUpdateData)
    {
        $client = $this->clientFactory
            ->create(['data' => ['websiteId' => $emailUpdateData->getWebsiteId()]]);

        try {
            $contact = $this->sdkContactFactory->create();
            $contact->setMatchIdentifier('email');
            $contact->setIdentifiers(['email' => $emailUpdateData->getEmail()]);

            $response = $client->contacts->patchByIdentifier(
                $emailUpdateData->getEmailBefore(),
                $contact
            );

            $this->contactResponseHandler->processV3ContactResponse(
                $response,
                $emailUpdateData->getWebsiteId()
            );

            $this->updatePendingRows($emailUpdateData);
            $this->removeAnyNonCustomerRowMatchingNewEmail($emailUpdateData);
            $this->logger->info(
                'Contact email update success',
                [
                    'emailBefore' => $emailUpdateData->getEmailBefore(),
                    'emailAfter' => $emailUpdateData->getEmail(),
                ]
            );
        } catch (\Exception $e) {
            $this->logger->error(
                "Contact email update error:",
                [
                    'emailBefore' => $emailUpdateData->getEmailBefore(),
                    'emailAfter' => $emailUpdateData->getEmail(),
                    'exception' => $e,
                ]
            );
        }
    }

    /**
     * Update pending rows.
     *
     * @param EmailUpdateData $emailUpdateData
     * @return void
     * @throws LocalizedException
     */
    private function updatePendingRows(EmailUpdateData $emailUpdateData)
    {
        $this->automationFactory
            ->create()
            ->updateEmailForPendingAutomations($emailUpdateData->getEmailBefore(), $emailUpdateData->getEmail());

        $this->campaignFactory
            ->create()
            ->updateEmailForPendingCampaigns($emailUpdateData->getEmailBefore(), $emailUpdateData->getEmail());

        $this->abandonedFactory
            ->create()
            ->updateEmailForPendingAbandonedCarts($emailUpdateData->getEmailBefore(), $emailUpdateData->getEmail());
    }

    /**
     * Remove any non-customer matching the updated email.
     *
     * @param EmailUpdateData $emailUpdateData
     * @return void
     * @throws \Exception
     */
    private function removeAnyNonCustomerRowMatchingNewEmail(EmailUpdateData $emailUpdateData)
    {
        $orphaned = $this->contactCollectionFactory->create()
            ->loadNonCustomerByEmailAndWebsiteId(
                $emailUpdateData->getEmail(),
                $emailUpdateData->getWebsiteId()
            );

        if ($orphaned->getSize()) {
            /** @var Contact $row */
            $row = $orphaned->getFirstItem();
            $this->contactResource->delete($row);
        }
    }
}
