<?php

namespace Dotdigitalgroup\Email\Model\Sync\Importer;

use Dotdigitalgroup\Email\Model\Importer as ImporterModel;
use Dotdigitalgroup\Email\Model\Sync\Importer;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\Contact\BulkFactory as ContactBulkFactory;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\Contact\DeleteFactory as ContactDeleteFactory;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\Contact\UpdateFactory as ContactUpdateFactory;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\TransactionalData\BulkFactory;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\TransactionalData\DeleteFactory;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\TransactionalData\UpdateFactory;

class ImporterQueueManager
{
    /**
     * @var ContactBulkFactory
     */
    private $contactBulkFactory;

    /**
     * @var ContactUpdateFactory
     */
    private $contactUpdateFactory;

    /**
     * @var ContactDeleteFactory
     */
    private $contactDeleteFactory;

    /**
     * @var BulkFactory
     */
    private $bulkFactory;

    /**
     * @var UpdateFactory
     */
    private $updateFactory;

    /**
     * @var DeleteFactory
     */
    private $deleteFactory;

    /**
     * ImporterQueueManager constructor.
     * @param ContactBulkFactory $contactBulkFactory
     * @param ContactUpdateFactory $contactUpdateFactory
     * @param ContactDeleteFactory $contactDeleteFactory
     * @param BulkFactory $bulkFactory
     * @param UpdateFactory $updateFactory
     * @param DeleteFactory $deleteFactory
     */
    public function __construct(
        ContactBulkFactory $contactBulkFactory,
        ContactUpdateFactory $contactUpdateFactory,
        ContactDeleteFactory $contactDeleteFactory,
        BulkFactory $bulkFactory,
        UpdateFactory $updateFactory,
        DeleteFactory $deleteFactory
    ) {
        $this->contactBulkFactory = $contactBulkFactory;
        $this->contactUpdateFactory = $contactUpdateFactory;
        $this->contactDeleteFactory = $contactDeleteFactory;
        $this->bulkFactory = $bulkFactory;
        $this->updateFactory = $updateFactory;
        $this->deleteFactory = $deleteFactory;
    }

    /**
     * Set importing priority for bulk imports.
     *
     * @param array $additionalImportTypes
     * @return null
     */
    public function getBulkQueue(array $additionalImportTypes = [])
    {
        $defaultBulk = [
            'model' => '',
            'mode' => ImporterModel::MODE_BULK,
            'type' => '',
            'limit' => Importer::TOTAL_IMPORT_SYNC_LIMIT
        ];

        //Contact Bulk
        $contact = $defaultBulk;
        $contact['model'] = $this->contactBulkFactory;
        $contact['type'] = [
            ImporterModel::IMPORT_TYPE_CONTACT,
            ImporterModel::IMPORT_TYPE_GUEST,
            ImporterModel::IMPORT_TYPE_SUBSCRIBERS,
        ];
        $contact['limit'] = Importer::CONTACT_IMPORT_SYNC_LIMIT;

        //Bulk Order
        $order = $defaultBulk;
        $order['model'] = $this->bulkFactory;
        $order['type'] = ImporterModel::IMPORT_TYPE_ORDERS;

        //Bulk Other TD
        $other = $defaultBulk;
        $other['model'] = $this->bulkFactory;
        $other['type'] = [
            'Catalog',
            ImporterModel::IMPORT_TYPE_REVIEWS,
            ImporterModel::IMPORT_TYPE_WISHLIST,
        ];

        foreach ($additionalImportTypes as $type) {
            $other['type'][] = $type;
        }

        return [
            $contact,
            $order,
            $other
        ];
    }

    public function getSingleQueue()
    {
        /*
         * Update
         */
        $defaultSingleUpdate = [
            'model' => $this->contactUpdateFactory,
            'mode' => '',
            'type' => '',
            'limit' => Importer::TOTAL_IMPORT_SYNC_LIMIT
        ];

        //Subscriber resubscribe
        $subscriberResubscribe = $defaultSingleUpdate;
        $subscriberResubscribe['mode'] = ImporterModel::MODE_SUBSCRIBER_RESUBSCRIBED;
        $subscriberResubscribe['type'] = ImporterModel::IMPORT_TYPE_SUBSCRIBER_RESUBSCRIBED;

        //Subscriber update/suppressed
        $subscriberUpdate = $defaultSingleUpdate;
        $subscriberUpdate['mode'] = ImporterModel::MODE_SUBSCRIBER_UPDATE;
        $subscriberUpdate['type'] = ImporterModel::IMPORT_TYPE_SUBSCRIBER_UPDATE;

        //Email Change
        $emailChange = $defaultSingleUpdate;
        $emailChange['mode'] = ImporterModel::MODE_CONTACT_EMAIL_UPDATE;
        $emailChange['type'] = ImporterModel::IMPORT_TYPE_CONTACT_UPDATE;

        //Order Update
        $orderUpdate = $defaultSingleUpdate;
        $orderUpdate['model'] = $this->updateFactory;
        $orderUpdate['mode'] = ImporterModel::MODE_SINGLE;
        $orderUpdate['type'] = ImporterModel::IMPORT_TYPE_ORDERS;

        //CartInsight TD update
        $updateCartInsightTd = $defaultSingleUpdate;
        $updateCartInsightTd['model'] = $this->updateFactory;
        $updateCartInsightTd['mode'] = ImporterModel::MODE_SINGLE;
        $updateCartInsightTd['type'] = ImporterModel::IMPORT_TYPE_CART_INSIGHT_CART_PHASE;

        //Update Other TD
        $updateOtherTd = $defaultSingleUpdate;
        $updateOtherTd['model'] = $this->updateFactory;
        $updateOtherTd['mode'] = ImporterModel::MODE_SINGLE;
        $updateOtherTd['type'] = ImporterModel::IMPORT_TYPE_WISHLIST;

        /*
        * Delete
        */
        $defaultSingleDelete = [
            'model' => '',
            'mode' => '',
            'type' => '',
            'limit' => Importer::TOTAL_IMPORT_SYNC_LIMIT
        ];

        //Contact Delete
        $contactDelete = $defaultSingleDelete;
        $contactDelete['model'] = $this->contactDeleteFactory;
        $contactDelete['mode'] = ImporterModel::MODE_CONTACT_DELETE;
        $contactDelete['type'] = ImporterModel::IMPORT_TYPE_CONTACT;

        //TD Delete
        $tdDelete = $defaultSingleDelete;
        $tdDelete['model'] = $this->deleteFactory;
        $tdDelete['mode'] = ImporterModel::MODE_SINGLE_DELETE;
        $tdDelete['type'] = [
            'Catalog',
            ImporterModel::IMPORT_TYPE_REVIEWS,
            ImporterModel::IMPORT_TYPE_WISHLIST,
            ImporterModel::IMPORT_TYPE_ORDERS,
        ];

        return [
            $subscriberResubscribe,
            $subscriberUpdate,
            $emailChange,
            $orderUpdate,
            $updateCartInsightTd,
            $updateOtherTd,
            $contactDelete,
            $tdDelete,
        ];
    }
}
