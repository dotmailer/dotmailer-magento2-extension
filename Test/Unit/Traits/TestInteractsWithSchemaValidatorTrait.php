<?php

namespace Dotdigitalgroup\Email\Test\Unit\Traits;

use Dotdigitalgroup\Email\Model\Validator\Schema\SchemaValidator;
use Dotdigitalgroup\Email\Model\Validator\Schema\SchemaValidatorFactory;
use PHPUnit\Framework\MockObject\MockObject;

trait TestInteractsWithSchemaValidatorTrait
{
    /**
     * @var SchemaValidatorFactory
     */
    protected $schemaValidatorFactory;

    /**
     * @var SchemaValidator
     */
    protected $schemaValidator;

    /**
     * Returns a mock object for the specified class.
     *
     * @psalm-template RealInstanceType of object
     * @psalm-param class-string<RealInstanceType> $originalClassName
     * @psalm-return MockObject&RealInstanceType
     */
    abstract protected function createMock(string $originalClassName): MockObject;

    /**
     * @param array $pattern
     */
    protected function setUpValidator($pattern = [])
    {
        $this->schemaValidator = $this->createMock(SchemaValidator::class);
        $this->schemaValidator
            ->method('isValid')
            ->willReturn(true);
        $this->schemaValidatorFactory = $this->createMock(SchemaValidatorFactory::class);
        $this->schemaValidatorFactory
            ->method('create')
            ->willReturn($this->schemaValidator);
    }
}
