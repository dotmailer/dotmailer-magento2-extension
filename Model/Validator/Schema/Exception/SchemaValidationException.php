<?php

namespace Dotdigitalgroup\Email\Model\Validator\Schema\Exception;

use Dotdigitalgroup\Email\Model\Validator\Schema\SchemaValidator;

class SchemaValidationException extends \Exception
{

    /**
     * @var int
     */
    protected $code = 422;

    /**
     * @var SchemaValidator
     */
    private $validator;

    /**
     * Create a new exception instance.
     *
     * @param SchemaValidator $validator
     * @param string|null $message
     */
    public function __construct(SchemaValidator $validator, string $message = null)
    {
        parent::__construct($message, $this->code);
        $this->validator = $validator;
    }

    /**
     * Get all of the validation error messages.
     *
     * @return array
     */
    public function errors(): array
    {
        return $this->validator->getErrors();
    }
}
