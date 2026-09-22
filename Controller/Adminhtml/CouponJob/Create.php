<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Controller\Adminhtml\CouponJob;

use Dotdigitalgroup\Email\Api\Model\CouponJob\CouponJobInterface;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\CouponJob;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobConfigurationInterface;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobConfigurationInterfaceFactory;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobDetails\ReportInterface;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobDetails\ReportInterfaceFactory;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobDetailsInterfaceFactory;
use Dotdigitalgroup\Email\Model\CouponJobFactory;
use Dotdigitalgroup\Email\Model\Queue\Coupon\CouponStartPublisher;
use Dotdigitalgroup\Email\Model\ResourceModel\CouponJob as CouponJobResource;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;

class Create extends Action
{
    public const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::coupon_job';

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

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
     * @var JobConfigurationInterfaceFactory
     */
    private $jobConfigurationFactory;

    /**
     * @var JobDetailsInterfaceFactory
     */
    private $detailsInterfaceFactory;

    /**
     * @var ReportInterfaceFactory
     */
    private $reportInterfaceFactory;

    /**
     * @var RedirectFactory
     */
    private $redirectFactory;

    /**
     * @var CouponStartPublisher
     */
    private $couponStartPublisher;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param Logger $logger
     * @param CouponJobFactory $couponJobFactory
     * @param CouponJobResource $couponJobResource
     * @param JobConfigurationInterfaceFactory $jobConfigurationFactory
     * @param JobDetailsInterfaceFactory $detailsInterfaceFactory
     * @param ReportInterfaceFactory $reportInterfaceFactory
     * @param RedirectFactory $redirectFactory
     * @param CouponStartPublisher $couponStartPublisher
     * @param DateTime $dateTime
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        Logger $logger,
        CouponJobFactory $couponJobFactory,
        CouponJobResource $couponJobResource,
        JobConfigurationInterfaceFactory $jobConfigurationFactory,
        JobDetailsInterfaceFactory $detailsInterfaceFactory,
        ReportInterfaceFactory $reportInterfaceFactory,
        RedirectFactory $redirectFactory,
        CouponStartPublisher $couponStartPublisher,
        DateTime $dateTime
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->logger = $logger;
        $this->couponJobFactory = $couponJobFactory;
        $this->couponJobResource = $couponJobResource;
        $this->jobConfigurationFactory = $jobConfigurationFactory;
        $this->detailsInterfaceFactory = $detailsInterfaceFactory;
        $this->reportInterfaceFactory = $reportInterfaceFactory;
        $this->redirectFactory = $redirectFactory;
        $this->couponStartPublisher = $couponStartPublisher;
        $this->dateTime = $dateTime;
    }

    /**
     * Save a new CouponJob from the submitted form data and kick off the start queue.
     *
     * @return \Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        unset($params['form_key']);

        try {
            $couponJob = $this->saveNewCouponJob($params);
            $this->couponStartPublisher->publish($couponJob);
            $this->messageManager->addSuccessMessage(__('Coupon job created successfully.'));

            return $this->redirectFactory->create()
                ->setPath('dotdigitalgroup_email/couponjob/index');

        } catch (LocalizedException $e) {
            $this->logger->error('CouponJob save error: ' . $e->getMessage());
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error('CouponJob unexpected error: ' . $e->getMessage());
            $this->messageManager->addErrorMessage(__('An error occurred while saving the coupon job.'));
        }

        return $this->redirectFactory->create()->setRefererOrBaseUrl();
    }

    /**
     * Save a new CouponJob from the submitted form data.
     *
     * @param array $params
     * @return CouponJob
     * @throws LocalizedException
     * @throws \Dotdigitalgroup\Email\Model\Validator\Schema\Exception\PatternInvalidException
     * @throws \Dotdigitalgroup\Email\Model\Validator\Schema\Exception\RuleNotDefinedException
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function saveNewCouponJob(array $params): CouponJob
    {
        $batchSize = (int) $params['batch_size'];
        $websiteId = (int) $params['website_id'];
        $audienceMeta = $params['audienceMeta'] ?? [];
        $audienceType = strtoupper($audienceMeta['audienceType']);

        $standardizedDate = $this->normalizeExpiryDate($params['expires_at'] ?? '');

        /** @var \Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobConfigurationInterface $configuration */
        $configuration = $this->jobConfigurationFactory->create([
            'data' => [
                JobConfigurationInterface::FILTER_ID => (int) $params['audience'],
                JobConfigurationInterface::FILTER_TYPE => $audienceType,
                JobConfigurationInterface::CODE_FORMAT => $params['code_format'],
                JobConfigurationInterface::CODE_LENGTH => (int) $params['code_length'],
                JobConfigurationInterface::CODE_DASH => (int) $params['code_dash'],
                JobConfigurationInterface::CODE_PREFIX => $params['code_prefix'],
                JobConfigurationInterface::CODE_SUFFIX => $params['code_suffix'],
                JobConfigurationInterface::DATA_FIELD => $params['data_field'],
                JobConfigurationInterface::EXPIRES_AT => $standardizedDate,
                JobConfigurationInterface::BATCH_SIZE => $batchSize,
            ],
        ]);
        $totalRecords = (int) $audienceMeta['contacts'];
        $totalBatches = (int) ceil($totalRecords / $batchSize);

        /** @var \Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobDetailsInterface $details */
        $details = $this->detailsInterfaceFactory->create();
        /** @var \Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobDetails\ReportInterface $report */
        $report = $this->reportInterfaceFactory->create([
            'data' => [
                ReportInterface::TOTAL_RECORDS => $totalRecords,
                ReportInterface::TOTAL_RECORDS_IMPORTED => 0,
                ReportInterface::TOTAL_BATCHES_PROCESSED => 0,
                ReportInterface::TOTAL_BATCHES => $totalBatches,
            ],
        ]);
        $details->setReport($report);

        /** @var CouponJob $couponJob */
        $couponJob = $this->couponJobFactory->create();
        $couponJob->setStatus(CouponJobInterface::STATUS_PENDING);
        $couponJob->setData('sales_rule_id', (int) $params['sales_rule_id']);
        $couponJob->setData('website_id', $websiteId);
        $couponJob->setJobConfiguration($configuration);
        $couponJob->setJobDetails($details);

        $this->couponJobResource->save($couponJob);

        return $couponJob;
    }

    /**
     * Normalize date time
     *
     * @param string $expiresAt
     * @return string
     */
    private function normalizeExpiryDate(string $expiresAt): string
    {
        if ($expiresAt === '') {
            return '';
        }

        return $this->dateTime->date('Y-m-d', $expiresAt);
    }
}
