<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\CouponJob\Struct;

use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobConfigurationInterface;
use Dotdigitalgroup\Email\Model\CouponJob\Struct\JobConfiguration;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class JobConfigurationTest extends TestCase
{
    /**
     * @var JobConfiguration
     */
    private $model;

    protected function setUp(): void
    {
        $this->model = new JobConfiguration();
    }

    public function testGetValidationPatternReturnsPatternFromInterface()
    {
        $this->assertSame(JobConfigurationInterface::VALIDATION_PATTERN, $this->model->getValidationPattern());
    }

    public function testDataCanBeSetAndRetrieved()
    {
        $data = [
            JobConfigurationInterface::BATCH_SIZE => 100,
            JobConfigurationInterface::FILTER_TYPE => 'LIST',
            JobConfigurationInterface::FILTER_ID => 1,
            JobConfigurationInterface::CODE_FORMAT => 'alnum',
            JobConfigurationInterface::CODE_LENGTH => 12,
            JobConfigurationInterface::CODE_DASH => 4,
            JobConfigurationInterface::CODE_PREFIX => 'PFX',
            JobConfigurationInterface::CODE_SUFFIX => 'SFX',
            JobConfigurationInterface::DATA_FIELD => 'field',
            JobConfigurationInterface::EXPIRES_AT => '2026-12-31 23:59:59'
        ];

        $this->model->setData($data);

        $this->assertSame(100, $this->model->getData(JobConfigurationInterface::BATCH_SIZE));
        $this->assertSame('LIST', $this->model->getData(JobConfigurationInterface::FILTER_TYPE));
        $this->assertSame(1, $this->model->getData(JobConfigurationInterface::FILTER_ID));
        $this->assertSame('alnum', $this->model->getData(JobConfigurationInterface::CODE_FORMAT));
        $this->assertSame(12, $this->model->getData(JobConfigurationInterface::CODE_LENGTH));
        $this->assertSame(4, $this->model->getData(JobConfigurationInterface::CODE_DASH));
        $this->assertSame('PFX', $this->model->getData(JobConfigurationInterface::CODE_PREFIX));
        $this->assertSame('SFX', $this->model->getData(JobConfigurationInterface::CODE_SUFFIX));
        $this->assertSame('field', $this->model->getData(JobConfigurationInterface::DATA_FIELD));
        $this->assertSame('2026-12-31 23:59:59', $this->model->getData(JobConfigurationInterface::EXPIRES_AT));
    }
}
