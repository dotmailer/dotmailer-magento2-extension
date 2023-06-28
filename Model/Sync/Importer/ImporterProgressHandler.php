<?php

namespace Dotdigitalgroup\Email\Model\Sync\Importer;

use Dotdigitalgroup\Email\Model\Importer as ImporterModel;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer\CollectionFactory;
use Dotdigitalgroup\Email\Model\Sync\Importer\V2InProgressImportResponseHandlerFactory as V2HandlerFactory;
use Dotdigitalgroup\Email\Model\Sync\Importer\V3InProgressImportResponseHandlerFactory as V3HandlerFactory;

class ImporterProgressHandler
{
    public const PROGRESS_GROUP_MODEL = 'model';
    public const PROGRESS_GROUP_METHOD = 'method';
    public const PROGRESS_GROUP_RESOURCE = 'resource';
    public const PROGRESS_GROUP_TYPES = 'types';

    public const VERSION_2 = 'v2';
    public const VERSION_3 = 'v3';

    public const TRANSACTIONAL = 'Transactional';
    public const CONTACT = 'Contact';

    /**
     * @var CollectionFactory
     */
    private $importerCollectionFactory;

    /**
     * @var V2HandlerFactory
     */
    private $v2HandlerFactory;

    /**
     * @var V3HandlerFactory
     */
    private $v3HandlerFactory;

    /**
     * ImporterProgressHandler constructor.
     *
     * @param CollectionFactory $importerCollectionFactory
     * @param V2HandlerFactory $v2HandlerFactory
     * @param V3HandlerFactory $v3HandlerFactory
     */
    public function __construct(
        CollectionFactory $importerCollectionFactory,
        V2HandlerFactory $v2HandlerFactory,
        V3HandlerFactory $v3HandlerFactory
    ) {
        $this->importerCollectionFactory = $importerCollectionFactory;
        $this->v2HandlerFactory = $v2HandlerFactory;
        $this->v3HandlerFactory = $v3HandlerFactory;
    }

    /**
     * Check imports in progress for an array of website ids.
     *
     * Note this will only pick up bulk imports.
     *
     * @param array $websiteIds
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function checkImportsInProgress($websiteIds)
    {
        $itemCount = 0;

        foreach ($this->getInProgressGroups() as $groups) {
            foreach ($groups as $group) {
                $items = $this->importerCollectionFactory->create()
                    ->getItemsWithImportingStatus(
                        $websiteIds,
                        $group[ self::PROGRESS_GROUP_TYPES ]
                    );

                if (!$items) {
                    continue;
                }

                $handler = $group['model']->create();
                /** @var AbstractInProgressImportResponseHandler $handler */
                $itemCount += $handler->process($group, $items);
            }
        }

        return $itemCount;
    }

    /**
     * Get in progress groups.
     *
     * @return array[]
     */
    public function getInProgressGroups()
    {
        $transactionalBulk = [
            self::PROGRESS_GROUP_MODEL => $this->v2HandlerFactory,
            self::PROGRESS_GROUP_METHOD => 'getContactsTransactionalDataImportByImportId',
            self::PROGRESS_GROUP_TYPES => [
                ImporterModel::IMPORT_TYPE_ORDERS,
                ImporterModel::IMPORT_TYPE_REVIEWS,
                ImporterModel::IMPORT_TYPE_WISHLIST,
                'Catalog'
            ]
        ];

        $transactionalV3Bulk = [
            self::PROGRESS_GROUP_TYPES => [
                ImporterModel::MODE_CONSENT
            ],
            self::PROGRESS_GROUP_MODEL => $this->v3HandlerFactory,
            self::PROGRESS_GROUP_RESOURCE => 'contacts',
            self::PROGRESS_GROUP_METHOD => 'getImportById'
        ];

        $contactsBulk = [
            self::PROGRESS_GROUP_TYPES => [
                ImporterModel::IMPORT_TYPE_CONTACT,
                ImporterModel::IMPORT_TYPE_CUSTOMER,
                ImporterModel::IMPORT_TYPE_GUEST,
                ImporterModel::IMPORT_TYPE_SUBSCRIBERS,
            ],
            self::PROGRESS_GROUP_MODEL => $this->v2HandlerFactory,
            self::PROGRESS_GROUP_METHOD => 'getContactsImportByImportId'
        ];

        return [
            self::VERSION_2 => [
                self::TRANSACTIONAL => $transactionalBulk,
                self::CONTACT => $contactsBulk
            ],
            self::VERSION_3 => [
                self::CONTACT => $transactionalV3Bulk,
            ]
        ];
    }
}
