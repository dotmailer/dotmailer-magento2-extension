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
    private $schemaValidatorFactory;

    /**
     * @var SchemaValidator
     */
    private $schemaValidator;

    /**
     * @param array $pattern
     */
    private function setUpValidator($pattern = [])
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
