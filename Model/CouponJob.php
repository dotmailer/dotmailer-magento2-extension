<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model;

use Dotdigitalgroup\Email\Api\Model\CouponJob\CouponJobInterface;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobConfigurationInterface;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobConfigurationInterfaceFactory;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobDetails\ReportInterface;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobDetailsInterface;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobDetailsInterfaceFactory;
use Dotdigitalgroup\Email\Model\CouponJob\Struct\JobConfiguration;
use Dotdigitalgroup\Email\Model\CouponJob\Struct\JobDetails;
use Dotdigitalgroup\Email\Model\CouponJob\Struct\JobDetails\Report;
use Dotdigitalgroup\Email\Model\Validator\Schema\Exception\PatternInvalidException;
use Dotdigitalgroup\Email\Model\Validator\Schema\Exception\RuleNotDefinedException;
use Dotdigitalgroup\Email\Model\Validator\Schema\SchemaValidatorFactory;
use InvalidArgumentException;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use OpenSearch\Exception\JsonException;

/**
 * CouponJob Model
 */
class CouponJob extends AbstractModel implements CouponJobInterface
{
    /**
     * Event prefix for model events.
     * Causes Magento to dispatch email_coupon_job_save_before/after/commit_after.
     *
     * @var string
     */
    protected $_eventPrefix = 'email_coupon_job';

    /**
     * Event object name passed in the event data array.
     *
     * @var string
     */
    protected $_eventObject = 'coupon_job';
    /**
     * @var SchemaValidatorFactory
     */
    private $schemaValidatorFactory;

    /**
     * @var JobDetailsInterfaceFactory
     */
    private $jobDetailsFactory;

    /**
     * @var JobConfigurationInterfaceFactory
     */
    private $jobConfigurationFactory;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param SchemaValidatorFactory $schemaValidatorFactory
     * @param JobDetailsInterfaceFactory $jobDetailsFactory
     * @param JobConfigurationInterfaceFactory $jobConfigurationFactory
     * @param SerializerInterface $serializer
     * @param array $data
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @throws LocalizedException
     */
    public function __construct(
        Context $context,
        Registry $registry,
        SchemaValidatorFactory $schemaValidatorFactory,
        JobDetailsInterfaceFactory $jobDetailsFactory,
        JobConfigurationInterfaceFactory $jobConfigurationFactory,
        SerializerInterface $serializer,
        array $data = [],
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null
    ) {
        $this->schemaValidatorFactory = $schemaValidatorFactory;
        $this->jobDetailsFactory = $jobDetailsFactory;
        $this->jobConfigurationFactory = $jobConfigurationFactory;
        $this->serializer = $serializer;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Initialize resource model
     *
     * @return void
     * @throws LocalizedException
     */
    protected function _construct()
    {
        $this->_init(\Dotdigitalgroup\Email\Model\ResourceModel\CouponJob::class);
    }

    /**
     * Set Status
     *
     * @param string $status
     * @return $this
     */
    public function setStatus($status)
    {
        if (!in_array($status, self::getValidStatuses())) {
            throw new InvalidArgumentException('Invalid status: ' . $status);
        }
        return $this->setData('status', $status);
    }

    /**
     * @inheritDoc
     */
    public function getValidStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PROCESSING,
            self::STATUS_COMPLETE,
            self::STATUS_FAILED,
            self::STATUS_CANCELLED
        ];
    }

    /**
     * Set Job Details
     *
     * @param JobDetailsInterface $jobDetails
     * @return $this
     * @throws PatternInvalidException
     * @throws RuleNotDefinedException
     */
    public function setJobDetails(JobDetailsInterface $jobDetails)
    {
        $validator = $this->schemaValidatorFactory->create();
        $validator->setPattern($jobDetails->getValidationPattern());
        /** @var JobDetails $jobDetails */
        if (!$validator->isValid($jobDetails->getData())) {
            $errors = $validator->getErrors();
            throw new InvalidArgumentException('Invalid JobDetails data: ' . implode('; ', $errors));
        }
        return $this->setData('job_details', $jobDetails);
    }

    /**
     * Get Job Details
     *
     * @return JobDetailsInterface
     * @throws JsonException
     */
    /**
     * Get Job Details
     *
     * @return JobDetailsInterface
     */
    public function getJobDetails(): JobDetailsInterface
    {
        $data = $this->getData('job_details');
        if ($data instanceof JobDetailsInterface) {
            return $data;
        }

        $jobDetails = $this->jobDetailsFactory->create(['data' => is_array($data) ? $data : []]);
        $this->setData('job_details', $jobDetails);

        return $jobDetails;
    }

    /**
     * Get Job Configuration
     *
     * @param JobConfigurationInterface $configuration
     * @return CouponJob
     * @throws RuleNotDefinedException
     * @throws PatternInvalidException
     */
    public function setJobConfiguration(JobConfigurationInterface $configuration): CouponJob
    {
        $validator = $this->schemaValidatorFactory->create();
        $validator->setPattern($configuration->getValidationPattern());
        /** @var JobConfiguration $configuration */
        if (!$validator->isValid($configuration->getData())) {
            $errors = $validator->getErrors();
            throw new InvalidArgumentException('Invalid Configuration data: ' . implode('; ', $errors));
        }
        return $this->setData('job_configuration', $configuration);
    }

    /**
     * Get Job Configuration
     *
     * @return JobConfigurationInterface
     */
    public function getJobConfiguration(): JobConfigurationInterface
    {
        $data = $this->getData('job_configuration');
        if ($data instanceof JobConfigurationInterface) {
            return $data;
        }

        $configuration = $this->jobConfigurationFactory->create(['data' => $data]);
        $this->setData('job_configuration', $configuration);

        return $configuration;
    }

    /**
     * Update report statistics for the job
     *
     * Sets or replaces the report statistics (total records, imported records,
     * and batches processed) for the job details. If no job details exist,
     * creates a new empty structure.
     *
     * @param ReportInterface $report The report statistics to set
     * @return $this
     * @throws PatternInvalidException
     * @throws RuleNotDefinedException
     */
    public function updateReport(ReportInterface $report): self
    {
        $jobDetails = $this->getJobDetails();
        $jobDetails->setReport($report);
        return $this->setJobDetails($jobDetails);
    }

    /**
     * Increment the batches processed counter
     *
     * Increases the total_batches_processed count by 1 in the report statistics.
     * This is typically called by consumers when they successfully process a batch.
     * The counter provides real-time progress tracking for batch processing.
     *
     * @param int $increment The amount to increment the batches processed count by (default is 1)
     * @return $this Returns self for method chaining, or unchanged if no report exists
     * @throws PatternInvalidException
     * @throws RuleNotDefinedException
     */
    public function incrementBatchesProcessed(int $increment = 1): self
    {
        /** @var JobDetails $jobDetails */
        $jobDetails = $this->getJobDetails();
        /** @var Report $report */
        $report = $jobDetails->getReport();
        $currentCount = $report->getTotalBatchesProcessed() ?? 0;
        $report->setTotalBatchesProcessed($currentCount + $increment);
        return $this->updateReport($report);
    }

    /**
     * Increment the records imported counter
     *
     * Increases the total_records_imported count by 1 in the report statistics.
     * This is typically called by consumers when they successfully import a record.
     * The counter provides real-time progress tracking for records imported.
     *
     * @param int $increment The amount to increment the records imported count by (default is 1)
     * @return $this Returns self for method chaining, or unchanged if no report exists
     * @throws PatternInvalidException
     * @throws RuleNotDefinedException
     */
    public function incrementRecordsImported(int $increment = 1): self
    {
        /** @var JobDetails $jobDetails */
        $jobDetails = $this->getJobDetails();
        /** @var Report $report */
        $report = $jobDetails->getReport();
        $currentCount = $report->getTotalRecordsImported() ?? 0;
        $report->setTotalRecordsImported($currentCount + $increment);
        return $this->updateReport($report);
    }

    /**
     * Increment the failed-messages count
     *
     * Increases the total failed-message count stored in the job details. The
     * matching failure detail is written to the log by the caller;
     * only the authoritative count is stored here so it survives
     * even if the log files are deleted.
     *
     * @param int $increment The amount to increment the failed-messages count by (default is 1)
     * @return $this
     * @throws PatternInvalidException
     * @throws RuleNotDefinedException
     */
    public function incrementFailedMessagesCount(int $increment = 1): self
    {
        /** @var JobDetails $jobDetails */
        $jobDetails = $this->getJobDetails();
        $jobDetails->setFailedMessagesCount($jobDetails->getFailedMessagesCount() + $increment);
        return $this->setJobDetails($jobDetails);
    }

    /**
     * Prepare data to be saved to database
     *
     * @return $this
     * @throws PatternInvalidException
     * @throws RuleNotDefinedException
     */
    public function beforeSave()
    {
        parent::beforeSave();
        if ($this->isObjectNew()) {
            $this->setCreatedAt(date('Y-m-d H:i:s'));
        }
        $this->setUpdatedAt(date('Y-m-d H:i:s'));

        return $this;
    }
}
