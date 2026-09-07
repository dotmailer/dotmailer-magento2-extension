<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Importer\Type\CouponJob;

use Dotdigital\Exception\ResponseValidationException;
use Dotdigital\V3\Models\Contact as SdkContact;
use Dotdigitalgroup\Email\Api\Model\CouponJob\CouponJobInterface;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobCancellerInterface;
use Dotdigitalgroup\Email\Model\Importer as ImporterModel;
use Dotdigitalgroup\Email\Model\ResourceModel\CouponJob as CouponJobResource;
use Dotdigitalgroup\Email\Model\Sync\Batch\Sender\SenderStrategyFactory;
use Dotdigitalgroup\Email\Model\Sync\Importer\ImporterItemStatusManager;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\AbstractItemSyncer;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\V3ItemPostProcessorFactory;
use InvalidArgumentException;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Importer sync type for CouponJob bulk JSON rows.
 *
 * Handles retried / reset CouponJob importer rows picked up by the bulk queue.
 * import_data is stored in the wrapped format:
 *   {"coupon_job_id":<id>,"records":{"email@...":{ contact data },...}}
 *
 * The records sub-array is hydrated back into SdkContact objects before the
 * wrapped batch is passed to CouponJobSenderStrategy, which expects exactly
 * this structure.
 */
class BulkJson extends AbstractItemSyncer
{
    /**
     * @var V3ItemPostProcessorFactory
     */
    protected $postProcessor;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var SenderStrategyFactory
     */
    private $senderStrategyFactory;

    /**
     * @var CouponJobResource
     */
    private $couponJobResource;

    /**
     * @var ImporterItemStatusManager
     */
    private $importerItemStatusManager;

    /**
     * @param V3ItemPostProcessorFactory $postProcessor
     * @param SerializerInterface $serializer
     * @param SenderStrategyFactory $senderStrategyFactory
     * @param Logger $logger
     * @param CouponJobResource $couponJobResource
     * @param ImporterItemStatusManager $importerItemStatusManager
     * @param array $data
     */
    public function __construct(
        V3ItemPostProcessorFactory $postProcessor,
        SerializerInterface $serializer,
        SenderStrategyFactory $senderStrategyFactory,
        Logger $logger,
        CouponJobResource $couponJobResource,
        ImporterItemStatusManager $importerItemStatusManager,
        array $data = []
    ) {
        $this->postProcessor = $postProcessor;
        $this->serializer = $serializer;
        $this->senderStrategyFactory = $senderStrategyFactory;
        $this->couponJobResource = $couponJobResource;
        $this->importerItemStatusManager = $importerItemStatusManager;
        parent::__construct($logger, $data);
    }

    /**
     * Run the sync, skipping any batch that belongs to a cancelled coupon job.
     *
     * Cancelled rows are removed from the collection rather than filtered inside
     * process(), because AbstractItemSyncer::sync() unconditionally hands every
     * item to V3ItemPostProcessor, which marks a falsy result as FAILED with no
     * message. Skipped rows are instead stamped with the shared cancellation
     * message so the grid explains why they stopped, and nothing is sent.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Importer\Collection $collection
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function sync($collection)
    {
        $cancelledKeys = [];
        $statusCache = [];

        foreach ($collection as $key => $item) {
            $couponJobId = $this->extractCouponJobId($item);
            if (!$couponJobId) {
                continue;
            }

            if (!array_key_exists($couponJobId, $statusCache)) {
                $statusCache[$couponJobId] = $this->couponJobResource->getStatusById($couponJobId);
            }

            if ($statusCache[$couponJobId] !== CouponJobInterface::STATUS_CANCELLED) {
                continue;
            }

            $this->importerItemStatusManager->markFailed($item, JobCancellerInterface::CANCELLED_MESSAGE);
            $this->importerItemStatusManager->save($item);

            $this->logger->info(sprintf(
                'CouponJob BulkJson: skipping importer row %s; coupon job %d is cancelled.',
                $item->getId(),
                $couponJobId
            ));

            $cancelledKeys[] = $key;
        }

        foreach ($cancelledKeys as $key) {
            $collection->removeItemByKey($key);
        }

        parent::sync($collection);
    }

    /**
     * Read the coupon job id from an importer row's serialized import_data.
     *
     * @param ImporterModel $item
     * @return int
     */
    private function extractCouponJobId($item): int
    {
        try {
            $importData = $this->serializer->unserialize($item->getImportData());
        } catch (\Exception $e) {
            return 0;
        }

        return isset($importData['coupon_job_id']) ? (int) $importData['coupon_job_id'] : 0;
    }

    /**
     * Process a single CouponJob importer row.
     *
     * Deserializes the wrapped import_data, reconstructs SdkContact objects
     * inside the records sub-array, and re-sends the batch via
     * CouponJobSenderStrategy.
     *
     * @param ImporterModel $item
     * @return string The Dotdigital import ID, or empty string on failure.
     * @throws InvalidArgumentException|ResponseValidationException|\Exception
     */
    public function process($item): string
    {
        $importData = $this->serializer->unserialize($item->getImportData());

        if (empty($importData['records']) || !is_array($importData['records'])) {
            $this->logger->warning(sprintf(
                'CouponJob BulkJson: importer row %s has no records to process.',
                $item->getId()
            ));
            return '';
        }

        foreach ($importData['records'] as $email => $data) {
            $importData['records'][$email] = new SdkContact($data);
        }

        return $this->senderStrategyFactory->create($item->getImportType())
            ->setBatch($importData)
            ->setWebsiteId((int) $item->getWebsiteId())
            ->process();
    }
}
