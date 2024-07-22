<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Export;

use Dotdigital\V3\Models\Contact as SdkContact;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\Connector\ContactData;
use Dotdigitalgroup\Email\Model\Sync\Subscriber;

class SdkContactBuilder
{
    /**
     * @var DataFieldMapper
     */
    private $dataFieldMapper;

    /**
     * @param DataFieldMapper $dataFieldMapper
     */
    public function __construct(
        DataFieldMapper $dataFieldMapper
    ) {
        $this->dataFieldMapper = $dataFieldMapper;
    }

    /**
     * Create a new SDK contact.
     *
     * @param ContactData $connectorModel
     * @param array $columns
     * @param int $listId
     *
     * @return SdkContact
     * @throws \Exception
     */
    public function createSdkContact(
        ContactData $connectorModel,
        array $columns,
        int $listId
    ): SdkContact {
        $sdkContact = new SdkContact([
            'matchIdentifier' => 'email'
        ]);
        $sdkContact->setIdentifiers([
            'email' => $connectorModel->getModel()->getEmail()
        ]);
        $sdkContact->setLists([$listId]);

        $sdkContact->setDataFields(
            $this->dataFieldMapper->mapFields(
                $connectorModel->getContactData(),
                $columns
            )
        );

        return $sdkContact;
    }
}
