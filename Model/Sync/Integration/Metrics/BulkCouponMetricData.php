<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Integration\Metrics;

use Dotdigitalgroup\Email\Api\Model\CouponJob\CouponJobInterface;
use Dotdigitalgroup\Email\Setup\SchemaInterface;
use Magento\Framework\App\ResourceConnection;

/**
 * Provides bulk-coupon statistics for the integration insight metrics payload.
 */
class BulkCouponMetricData implements MetricProviderInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Return bulk-coupon aggregate statistics for the given website.
     *
     * @param int $websiteId
     * @return array
     */
    public function getMetricData(int $websiteId): array
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName(SchemaInterface::EMAIL_COUPON_JOB_TABLE);

        if (!$connection->isTableExists($table)) {
            return $this->getEmpty();
        }

        try {
            $status = CouponJobInterface::STATUS_COMPLETE;

            $lastCompletedAt = $connection->fetchOne(
                $connection->select()
                    ->from($table, ['v' => new \Zend_Db_Expr('MAX(updated_at)')])
                    ->where('status = ?', $status)
                    ->where('website_id = ?', $websiteId)
            );

            $totalJobs = (int) $connection->fetchOne(
                $connection->select()
                    ->from($table, ['v' => new \Zend_Db_Expr('COUNT(*)')])
                    ->where('status = ?', $status)
                    ->where('website_id = ?', $websiteId)
            );

            // $.report.total_records_imported — $ is safe in a double-quoted PHP string
            // because it is followed by '.' which is not a valid identifier character.
            $totalCoupons = (int) $connection->fetchOne(
                $connection->select()
                    ->from($table, ['v' => new \Zend_Db_Expr(
                        "SUM(JSON_EXTRACT(job_details, '$.report.total_records_imported'))"
                    )])
                    ->where('status = ?', $status)
                    ->where('website_id = ?', $websiteId)
            );

            return [
                'last_completed_at' => $lastCompletedAt ?: null,
                'total_coupon_jobs' => $totalJobs,
                'total_coupons'     => $totalCoupons,
            ];
        } catch (\Exception $e) {
            return $this->getEmpty();
        }
    }

    /**
     * Zeroed fallback returned when the table does not exist or a query fails.
     *
     * @return array
     */
    private function getEmpty(): array
    {
        return [
            'last_completed_at' => null,
            'total_coupon_jobs' => 0,
            'total_coupons'     => 0,
        ];
    }
}
