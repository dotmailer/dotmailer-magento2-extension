<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\ResourceModel\CouponJob;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Resource model for the email_coupon_job_importer table.
 *
 * This link table maps a coupon job to each of its email_importer batch rows,
 * removing the need to scan the serialized import_data JSON with a LIKE query.
 */
class ImporterLink extends AbstractDb
{
    /**
     * Initialize resource model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('email_coupon_job_importer', 'id');
    }

    /**
     * Link an email_importer batch row to a coupon job.
     *
     * Uses insertOnDuplicate against the unique email_importer_id key so the
     * call is idempotent if a batch is ever re-linked.
     *
     * @param int $couponJobId
     * @param int $importerId
     * @return void
     */
    public function linkImporter(int $couponJobId, int $importerId): void
    {
        $connection = $this->getConnection();
        $connection->insertOnDuplicate(
            $this->getMainTable(),
            [
                'coupon_job_id' => $couponJobId,
                'email_importer_id' => $importerId,
            ],
            ['coupon_job_id']
        );
    }
}
