<?php

namespace Dotdigitalgroup\Email\Model\Sync\Importer;

use Dotdigitalgroup\Email\Model\Importer as ImporterModel;
use Dotdigitalgroup\Email\Model\Sync\Importer;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\Contact\BulkFactory as ContactBulkFactory;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\Contact\BulkJsonFactory as ContactBulkJsonFactory;
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
     * @var ContactBulkJsonFactory
     */
    private $contactBulkJsonFactory;

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
     *
     * @param ContactBulkFactory $contactBulkFactory
     * @param ContactBulkJsonFactory $contactBulkJsonFactory
     * @param ContactUpdateFactory $contactUpdateFactory
     * @param ContactDeleteFactory $contactDeleteFactory
     * @param BulkFactory $bulkFactory
     * @param UpdateFactory $updateFactory
     * @param DeleteFactory $deleteFactory
     */
    public function __construct(
        ContactBulkFactory $contactBulkFactory,
        ContactBulkJsonFactory $contactBulkJsonFactory,
        ContactUpdateFactory $contactUpdateFactory,
        ContactDeleteFactory $contactDeleteFactory,
        BulkFactory $bulkFactory,
        UpdateFactory $updateFactory,
        DeleteFactory $deleteFactory
    ) {
        $this->contactBulkFactory = $contactBulkFactory;
        $this->contactBulkJsonFactory = $contactBulkJsonFactory;
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
     * @return array
     */
    public function getBulkQueue(array $additionalImportTypes = [])
    {
        $defaultBulk = [
            'model' => $this->bulkFactory,
            'mode' => ImporterModel::MODE_BULK,
            'type' => '',
            'limit' => Importer::TOTAL_IMPORT_SYNC_LIMIT
        ];

        //Contact Bulk
        $contactDeprecated = $defaultBulk;
        $contactDeprecated['model'] = $this->contactBulkFactory;
        $contactDeprecated['type'] = [
            ImporterModel::IMPORT_TYPE_CONTACT,
            ImporterModel::IMPORT_TYPE_CUSTOMER,
            ImporterModel::IMPORT_TYPE_GUEST,
            ImporterModel::IMPORT_TYPE_SUBSCRIBERS,
        ];
        $contactDeprecated['limit'] = Importer::CONTACT_IMPORT_SYNC_LIMIT;
        $contactDeprecated['useFile'] = true;

        //Contact JSON Bulk
        $contactJson = $defaultBulk;
        $contactJson['model'] = $this->contactBulkJsonFactory;
        $contactJson['type'] = [
            ImporterModel::MODE_CONSENT,
            ImporterModel::IMPORT_TYPE_CUSTOMER,
            ImporterModel::IMPORT_TYPE_GUEST,
            ImporterModel::IMPORT_TYPE_SUBSCRIBERS,
        ];
        $contactJson['limit'] = Importer::CONTACT_IMPORT_SYNC_LIMIT;

        //Bulk Order
        $order = $defaultBulk;
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
            $contactDeprecated,
            $contactJson,
            $order,
            $other
        ];
    }

    /**
     * Set importing priority for single imports.
     *
     * @deprecated Single updates have been moved to message queues.
     * @see \Dotdigitalgroup\Email\Model\Queue
     *
     * @return array
     */
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
        $subscriberUnsubscribe = $defaultSingleUpdate;
        $subscriberUnsubscribe['mode'] = [
            ImporterModel::MODE_SUBSCRIBER_UPDATE,
            ImporterModel::MODE_SUBSCRIBER_UNSUBSCRIBE
        ];
        $subscriberUnsubscribe['type'] = ImporterModel::IMPORT_TYPE_SUBSCRIBER_UPDATE;

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
            $subscriberUnsubscribe,
            $emailChange,
            $orderUpdate,
            $updateCartInsightTd,
            $contactDelete,
            $tdDelete,
        ];
    }
}
