<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Importer;

use Dotdigitalgroup\Email\Api\Model\Sync\Importer\InProgressImportResponseHandlerInterface;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer\CollectionFactory;
use Dotdigitalgroup\Email\Model\Sync\Importer\InProgress\Config\V2ContactsConfigInterface;
use Dotdigitalgroup\Email\Model\Sync\Importer\InProgress\Config\V2TransactionalConfigInterface;
use Dotdigitalgroup\Email\Model\Sync\Importer\InProgress\Config\V3ContactsConfigInterface;
use Dotdigitalgroup\Email\Model\Sync\Importer\InProgress\Config\V3CouponJobConfigInterface;
use Dotdigitalgroup\Email\Model\Sync\Importer\InProgress\Config\V3InsightDataConfigInterface;
use Dotdigitalgroup\Email\Model\Sync\Importer\Context\InProgressImportContext;
use Dotdigitalgroup\Email\Model\Sync\Importer\Handler\InProgressImportResponseHandlerPool;

class ImporterProgressHandler
{
    public const PROGRESS_GROUP_HANDLER = 'handler';
    public const PROGRESS_GROUP_METHOD = 'method';
    public const PROGRESS_GROUP_RESOURCE = 'resource';
    public const PROGRESS_GROUP_TYPES = 'types';
    public const PROGRESS_GROUP_MODE = 'import_mode';

    public const VERSION_2 = 'v2';
    public const VERSION_3 = 'v3';

    public const TRANSACTIONAL = 'Transactional';
    public const CONTACT = 'Contact';

    public const INSIGHTDATA = 'InsightData';

    public const COUPON_JOB = 'CouponJob';

    /**
     * @var CollectionFactory
     */
    private $importerCollectionFactory;

    /**
     * @var InProgressImportResponseHandlerPool
     */
    private $handlerPool;

    /**
     * ImporterProgressHandler constructor.
     *
     * @param CollectionFactory $importerCollectionFactory
     * @param InProgressImportResponseHandlerPool $handlerPool
     */
    public function __construct(
        CollectionFactory $importerCollectionFactory,
        InProgressImportResponseHandlerPool $handlerPool
    ) {
        $this->importerCollectionFactory = $importerCollectionFactory;
        $this->handlerPool = $handlerPool;
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
                $context = $this->createContext($group);

                $items = $this->importerCollectionFactory->create()
                    ->getItemsWithImportingStatus(
                        $websiteIds,
                        $context->getImportTypes(),
                        $context->getImportMode()
                    );

                if (!$items) {
                    continue;
                }

                /** @var InProgressImportResponseHandlerInterface $handler */
                $handler = $this->handlerPool->get($context->getHandlerCode());
                $itemCount += $handler->process($context, $items);
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
            self::PROGRESS_GROUP_MODE => V2TransactionalConfigInterface::IMPORT_MODE,
            self::PROGRESS_GROUP_HANDLER => V2TransactionalConfigInterface::HANDLER,
            self::PROGRESS_GROUP_METHOD => V2TransactionalConfigInterface::METHOD,
            self::PROGRESS_GROUP_TYPES => V2TransactionalConfigInterface::IMPORT_TYPES,
        ];

        $contactsV3Bulk = [
            self::PROGRESS_GROUP_MODE => V3ContactsConfigInterface::IMPORT_MODE,
            self::PROGRESS_GROUP_TYPES => V3ContactsConfigInterface::IMPORT_TYPES,
            self::PROGRESS_GROUP_HANDLER => V3ContactsConfigInterface::HANDLER,
            self::PROGRESS_GROUP_RESOURCE => V3ContactsConfigInterface::RESOURCE,
            self::PROGRESS_GROUP_METHOD => V3ContactsConfigInterface::METHOD,
        ];

        $couponJobV3Bulk = [
            self::PROGRESS_GROUP_MODE => V3CouponJobConfigInterface::IMPORT_MODE,
            self::PROGRESS_GROUP_TYPES => V3CouponJobConfigInterface::IMPORT_TYPES,
            self::PROGRESS_GROUP_HANDLER => V3CouponJobConfigInterface::HANDLER,
            self::PROGRESS_GROUP_RESOURCE => V3CouponJobConfigInterface::RESOURCE,
            self::PROGRESS_GROUP_METHOD => V3CouponJobConfigInterface::METHOD,
        ];

        $contactsBulk = [
            self::PROGRESS_GROUP_MODE => V2ContactsConfigInterface::IMPORT_MODE,
            self::PROGRESS_GROUP_TYPES => V2ContactsConfigInterface::IMPORT_TYPES,
            self::PROGRESS_GROUP_HANDLER => V2ContactsConfigInterface::HANDLER,
            self::PROGRESS_GROUP_METHOD => V2ContactsConfigInterface::METHOD,
        ];

        $insightDataV3Bulk = [
            self::PROGRESS_GROUP_MODE => V3InsightDataConfigInterface::IMPORT_MODE,
            self::PROGRESS_GROUP_RESOURCE => V3InsightDataConfigInterface::RESOURCE,
            self::PROGRESS_GROUP_TYPES => V3InsightDataConfigInterface::IMPORT_TYPES,
            self::PROGRESS_GROUP_HANDLER => V3InsightDataConfigInterface::HANDLER,
            self::PROGRESS_GROUP_METHOD => V3InsightDataConfigInterface::METHOD,
        ];

        return [
            self::VERSION_2 => [
                self::TRANSACTIONAL => $transactionalBulk,
                self::CONTACT => $contactsBulk,
            ],
            self::VERSION_3 => [
                self::CONTACT => $contactsV3Bulk,
                self::INSIGHTDATA => $insightDataV3Bulk,
                self::COUPON_JOB => $couponJobV3Bulk,
            ]
        ];
    }

    /**
     * Create context.
     *
     * @param array $group
     * @return InProgressImportContext
     */
    private function createContext(array $group): InProgressImportContext
    {
        return new InProgressImportContext(
            $group[self::PROGRESS_GROUP_HANDLER],
            $group[self::PROGRESS_GROUP_MODE],
            $group[self::PROGRESS_GROUP_TYPES],
            $group[self::PROGRESS_GROUP_METHOD],
            $group[self::PROGRESS_GROUP_RESOURCE] ?? null
        );
    }
}
