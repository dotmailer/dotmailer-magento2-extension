<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\CouponJob;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\CouponJob;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobUpdaterInterface;
use Dotdigitalgroup\Email\Model\CouponJobFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\CouponJob as CouponJobResource;

/**
 * Serialises concurrent writes to a CouponJob's job_details blob via a row lock.
 */
class JobUpdater implements JobUpdaterInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var CouponJobFactory
     */
    private $couponJobFactory;

    /**
     * @var CouponJobResource
     */
    private $couponJobResource;

    /**
     * @param Logger $logger
     * @param CouponJobFactory $couponJobFactory
     * @param CouponJobResource $couponJobResource
     */
    public function __construct(
        Logger $logger,
        CouponJobFactory $couponJobFactory,
        CouponJobResource $couponJobResource
    ) {
        $this->logger = $logger;
        $this->couponJobFactory = $couponJobFactory;
        $this->couponJobResource = $couponJobResource;
    }

    /**
     * Load the CouponJob under a FOR UPDATE lock, apply $mutator, then save.
     *
     * @param int $couponJobId
     * @param callable $mutator function(CouponJob $couponJob): void
     * @return bool True when the job was found, mutated and saved.
     */
    public function update(int $couponJobId, callable $mutator): bool
    {
        $connection = $this->couponJobResource->getConnection();
        $connection->beginTransaction();

        try {
            $select = $connection->select()
                ->from($this->couponJobResource->getMainTable(), ['id'])
                ->where('id = ?', $couponJobId)
                ->forUpdate(true);

            if (!$connection->fetchOne($select)) {
                $connection->rollBack();
                $this->logger->error(
                    sprintf('CouponJob JobUpdater: CouponJob %d not found.', $couponJobId)
                );
                return false;
            }

            /** @var CouponJob $couponJob */
            $couponJob = $this->couponJobFactory->create();
            $this->couponJobResource->load($couponJob, $couponJobId);

            $mutator($couponJob);

            $this->couponJobResource->save($couponJob);
            $connection->commit();
            return true;
        } catch (\Throwable $e) {
            $connection->rollBack();
            $this->logger->error(
                sprintf(
                    'CouponJob JobUpdater: Error updating job %d: %s',
                    $couponJobId,
                    $e->getMessage()
                )
            );
            return false;
        }
    }
}
