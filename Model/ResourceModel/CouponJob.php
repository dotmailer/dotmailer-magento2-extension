<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\ResourceModel;

use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobConfigurationInterface;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobDetailsInterface;
use Dotdigitalgroup\Email\Model\CouponJob\Struct\JobConfiguration;
use Dotdigitalgroup\Email\Model\CouponJob\Struct\JobDetails;
use Dotdigitalgroup\Email\Model\Importer as ImporterModel;
use Dotdigitalgroup\Email\Setup\SchemaInterface;
use Exception;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Serialize\Serializer\Json;
use Zend_Db_Expr;

/**
 * Resource model for the CouponJob entity.
 *
 * Handles the persistence, serialization, and deserialization of CouponJob data,
 * specifically the job details and job configuration fields.
 */
class CouponJob extends AbstractDb
{
    /**
     * @var Json Serializer for encoding and decoding JSON data.
     */
    protected $serializer;

    /**
     * CouponJob constructor.
     *
     * @param Context $context The database context.
     * @param Json $serializer JSON serializer instance.
     * @param string|null $connectionName Optional connection name.
     */
    public function __construct(
        Context $context,
        Json $serializer,
        $connectionName = null
    ) {
        $this->serializer = $serializer;
        parent::__construct($context, $connectionName);
    }

    /**
     * Initialize resource model.
     *
     * Sets the main table and primary key field for the CouponJob entity.
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init(SchemaInterface::EMAIL_COUPON_JOB_TABLE, 'id');
    }

    /**
     * Fetch the status of a coupon job without hydrating the full model.
     *
     * Used by consumers for a cheap, indexed short-circuit check so they can skip
     * work for a job that has already been marked as failed.
     *
     * @param int $couponJobId
     * @return string|null The status string, or null when the job does not exist.
     */
    public function getStatusById(int $couponJobId): ?string
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), ['status'])
            ->where('id = ?', $couponJobId);

        $status = $connection->fetchOne($select);

        return $status === false ? null : (string) $status;
    }

    /**
     * Mark every not-yet-sent batch of a coupon job as failed.
     *
     * Called when a coupon job is cancelled. Only linked email_importer rows still
     * sitting at NOT_IMPORTED are moved to FAILED and stamped with the supplied
     * message; those batches never reached Dotdigital, so nothing is lost by
     * abandoning them and they will not be picked up again by
     * getQueueByTypeAndMode() (NOT_IMPORTED only).
     *
     * Rows at IMPORTING are deliberately left alone. Their contacts have already
     * been accepted by Dotdigital and an import id was returned, so they must be
     * allowed to finish polling — otherwise the batch and record counts on the
     * cancellation report would under-report work that genuinely completed.
     * getItemsWithImportingStatus() keeps selecting them until the API reports a
     * final state, at which point ReportBuilder::build() folds the real numbers in.
     *
     * Performed as a single UPDATE because a large job can have thousands of
     * linked batches. The subquery reads email_coupon_job_importer while the
     * UPDATE targets email_importer, so MySQL's same-table restriction does not
     * apply. updated_at is set explicitly because a raw UPDATE bypasses
     * \Dotdigitalgroup\Email\Model\Importer::beforeSave().
     *
     * @param int $couponJobId
     * @param string $message
     *
     * @return int Number of rows affected.
     */
    public function failUnsentBatches(int $couponJobId, string $message): int
    {
        $connection = $this->getConnection();

        $linkedIds = $connection->select()
            ->from(
                $this->getTable(SchemaInterface::EMAIL_COUPON_JOB_IMPORTER_TABLE),
                ['email_importer_id']
            )
            ->where('coupon_job_id = ?', $couponJobId);

        return $connection->update(
            $this->getTable(SchemaInterface::EMAIL_IMPORTER_TABLE),
            [
                'import_status' => ImporterModel::FAILED,
                'message' => $message,
                'updated_at' => gmdate('Y-m-d H:i:s'),
            ],
            [
                'id IN (?)' => new Zend_Db_Expr((string) $linkedIds),
                'import_status = ?' => ImporterModel::NOT_IMPORTED,
            ]
        );
    }

    /**
     * Before save callback.
     *
     * Serializes job details and configuration if they are objects before saving to the database.
     *
     * @param AbstractModel $object The CouponJob model instance.
     * @return AbstractDb
     */
    protected function _beforeSave(AbstractModel $object)
    {
        $this->dehydrateJobConfiguration($object);
        $this->dehydrateJobDetails($object);
        return parent::_beforeSave($object);
    }

    /**
     * After load callback.
     *
     * Unserializes job details and configuration after loading from the database.
     *
     * @param AbstractModel $object The CouponJob model instance.
     * @return AbstractDb
     */
    protected function _afterLoad(AbstractModel $object)
    {
        $this->hydrateJobDetails($object);
        $this->hydrateJobConfiguration($object);
        return parent::_afterLoad($object);
    }

    /**
     * After save callback.
     *
     * Unserializes job details and configuration after saving to the database.
     *
     * @param AbstractModel $object The CouponJob model instance.
     * @return AbstractDb
     */
    protected function _afterSave(AbstractModel $object)
    {
        $this->hydrateJobDetails($object);
        $this->hydrateJobConfiguration($object);
        return parent::_afterSave($object);
    }

    /**
     * Serializes the job configuration if it is an object implementing JobConfigurationInterface.
     *
     * @param AbstractModel $object The CouponJob model instance.
     * @return void
     */
    private function dehydrateJobConfiguration(AbstractModel $object): void
    {
        $jobConfig = $object->getData('job_configuration');
        if ($jobConfig instanceof JobConfigurationInterface) {
            /** @var JobConfiguration $jobConfig */
            $object->setData('job_configuration', $jobConfig->toJson());
        }
    }

    /**
     * Unserializes job configuration data if it is a string.
     *
     * @param AbstractModel $object The CouponJob model instance.
     * @return void
     */
    public function hydrateJobConfiguration(AbstractModel $object): void
    {
        $jobConfig = $object->getData('job_configuration');
        if (is_string($jobConfig)) {
            try {
                $object->setData('job_configuration', $this->serializer->unserialize($jobConfig));
            } catch (Exception $e) {
                $object->setData('job_configuration', []);
            }
        }
    }

    /**
     * Serializes the job details if it is an object implementing JobDetailsInterface.
     *
     * @param AbstractModel $object The CouponJob model instance.
     * @return void
     */
    public function dehydrateJobDetails(AbstractModel $object): void
    {
        $jobDetails = $object->getData('job_details');
        if ($jobDetails instanceof JobDetailsInterface) {
            /** @var JobDetails $jobDetails */
            $object->setData('job_details', $jobDetails->toJson());
        }
    }

    /**
     * Unserializes job details data if it is a string.
     *
     * @param AbstractModel $object The CouponJob model instance.
     * @return void
     */
    public function hydrateJobDetails(AbstractModel $object): void
    {
        $jobDetails = $object->getData('job_details');
        if (is_string($jobDetails)) {
            try {
                $object->setData('job_details', $this->serializer->unserialize($jobDetails));
            } catch (Exception $e) {
                $object->setData('job_details', []);
            }
        }
    }
}
