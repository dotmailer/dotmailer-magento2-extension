<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Queue\Coupon;

use Dotdigitalgroup\Email\Model\Queue\Data\CouponSyncData;
use Dotdigitalgroup\Email\Model\Queue\Data\CouponSyncDataFactory;
use Magento\Framework\MessageQueue\PublisherInterface;

class CouponSyncPublisher
{
    public const TOPIC_COUPON_SYNC = 'ddg.coupon.sync';

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @var CouponSyncDataFactory
     */
    private $couponSyncDataFactory;

    /**
     * @param PublisherInterface $publisher
     * @param CouponSyncDataFactory $couponSyncDataFactory
     */
    public function __construct(
        PublisherInterface $publisher,
        CouponSyncDataFactory $couponSyncDataFactory
    ) {
        $this->publisher = $publisher;
        $this->couponSyncDataFactory = $couponSyncDataFactory;
    }

    /**
     * Publish a coupon sync message for a single page of emails.
     *
     * @param int $jobId
     * @param int $websiteId
     * @param int $salesRuleId
     * @param array $emails
     * @param int $batchNumber
     * @param string|null $codeFormat
     * @param string|null $codePrefix
     * @param string|null $codeSuffix
     * @param int|null $codeLength
     * @param int|null $codeDash
     * @param string|null $expiresAt
     * @param string $dataField
     * @return void
     */
    public function publish(
        int $jobId,
        int $websiteId,
        int $salesRuleId,
        array $emails,
        int $batchNumber,
        ?string $codeFormat,
        ?string $codePrefix,
        ?string $codeSuffix,
        ?int $codeLength,
        ?int $codeDash,
        ?string $expiresAt,
        string $dataField
    ): void {
        /** @var CouponSyncData $data */
        $data = $this->couponSyncDataFactory->create();
        $data->setJobId($jobId);
        $data->setWebsiteId($websiteId);
        $data->setSalesRuleId($salesRuleId);
        $data->setEmails($emails);
        $data->setBatchNumber($batchNumber);
        $data->setCodeFormat($codeFormat);
        $data->setCodePrefix($codePrefix);
        $data->setCodeSuffix($codeSuffix);
        $data->setCodeLength($codeLength);
        $data->setCodeDash($codeDash);
        $data->setExpiresAt($expiresAt);
        $data->setDataField($dataField);

        $this->publisher->publish(self::TOPIC_COUPON_SYNC, $data);
    }
}
