<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Apiconnector\V3\Contact;

use Dotdigital\V3\Models\Contact as ContactModel;
use Dotdigital\V3\Models\ContactFactory as DotdigitalContactFactory;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory;
use Dotdigitalgroup\Email\Model\Contact\ContactResponseHandler;
use Dotdigitalgroup\Email\Model\Newsletter\OptInTypeFinder;

class Patcher
{
    /**
     * @var DotdigitalContactFactory
     */
    private $sdkContactFactory;

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var ContactResponseHandler
     */
    private $contactResponseHandler;

    /**
     * @var OptInTypeFinder
     */
    private $optInTypeFinder;

    /**
     * @param DotdigitalContactFactory $sdkContactFactory
     * @param ClientFactory $clientFactory
     * @param ContactResponseHandler $contactResponseHandler
     * @param OptInTypeFinder $optInTypeFinder
     */
    public function __construct(
        DotdigitalContactFactory $sdkContactFactory,
        ClientFactory $clientFactory,
        ContactResponseHandler $contactResponseHandler,
        OptInTypeFinder $optInTypeFinder
    ) {
        $this->sdkContactFactory = $sdkContactFactory;
        $this->clientFactory = $clientFactory;
        $this->contactResponseHandler = $contactResponseHandler;
        $this->optInTypeFinder = $optInTypeFinder;
    }

    /**
     * Update or create contact by email.
     *
     * @param string $email
     * @param int $websiteId
     * @param int $storeId
     *
     * @return ContactModel
     * @throws \Http\Client\Exception|\Magento\Framework\Exception\AlreadyExistsException
     */
    public function getOrCreateContactByEmail(string $email, int $websiteId, int $storeId)
    {
        $client = $this->clientFactory
            ->create(['data' => ['websiteId' => $websiteId]]);

        $contact = $this->sdkContactFactory->create();
        $contact->setMatchIdentifier('email');
        $contact->setIdentifiers(['email' => $email]);

        $optInType = $this->optInTypeFinder->getOptInType($storeId);
        if ($optInType) {
            $contact->setChannelProperties([
                'email' => [
                    'optInType' => $optInType
                ]
            ]);
        }

        $response = $client->contacts->patchByIdentifier(
            $email,
            $contact
        );

        return $this->contactResponseHandler->processV3ContactResponse(
            $response,
            $websiteId,
            $storeId
        );
    }
}
