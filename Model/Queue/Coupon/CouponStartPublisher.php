<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Queue\Coupon;

use Dotdigitalgroup\Email\Model\CouponJob;
use Dotdigitalgroup\Email\Model\CouponJob\Struct\JobConfiguration;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobConfigurationInterface;
use Dotdigitalgroup\Email\Model\Queue\Data\CouponStartData;
use Dotdigitalgroup\Email\Model\Queue\Data\CouponStartDataFactory;
use Magento\Framework\MessageQueue\PublisherInterface;

class CouponStartPublisher
{
    public const TOPIC_COUPON_START = 'ddg.coupon.start';

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @var CouponStartDataFactory
     */
    private $couponStartDataFactory;

    /**
     * @param PublisherInterface $publisher
     * @param CouponStartDataFactory $couponStartDataFactory
     */
    public function __construct(
        PublisherInterface $publisher,
        CouponStartDataFactory $couponStartDataFactory
    ) {
        $this->publisher = $publisher;
        $this->couponStartDataFactory = $couponStartDataFactory;
    }

    /**
     * Publish a coupon start message from a CouponJob model.
     *
     * @param CouponJob $couponJob
     * @return void
     */
    public function publish(CouponJob $couponJob): void
    {
        /** @var JobConfiguration $config */
        $config = $couponJob->getJobConfiguration();

        /** @var CouponStartData $data */
        $data = $this->couponStartDataFactory->create();
        $data->setJobId((int) $couponJob->getId());
        $data->setWebsiteId((int) $couponJob->getData('website_id'));
        $data->setFilterType((string) $config->getData(JobConfigurationInterface::FILTER_TYPE));
        $data->setFilterId((int) $config->getData(JobConfigurationInterface::FILTER_ID));
        $data->setCodeFormat($config->getData(JobConfigurationInterface::CODE_FORMAT));
        $data->setCodeLength($config->getData(JobConfigurationInterface::CODE_LENGTH));
        $data->setCodeDash($config->getData(JobConfigurationInterface::CODE_DASH));
        $data->setCodePrefix($config->getData(JobConfigurationInterface::CODE_PREFIX));
        $data->setCodeSuffix($config->getData(JobConfigurationInterface::CODE_SUFFIX));
        $data->setDataField((string) $config->getData(JobConfigurationInterface::DATA_FIELD));
        $data->setBatchSize($config->getData(JobConfigurationInterface::BATCH_SIZE));
        $data->setExpiresAt($config->getData(JobConfigurationInterface::EXPIRES_AT));

        $this->publisher->publish(self::TOPIC_COUPON_START, $data);
    }
}
