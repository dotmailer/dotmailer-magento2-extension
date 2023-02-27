<?php

namespace Dotdigitalgroup\Email\Model\Validator\Schema\Rule;

use Magento\Framework\Url\Validator;

class UrlRule implements ValidatorRuleInterface
{
    /**
     * @var Validator
     */
    private $urlValidator;

    /**
     * @param Validator $urlValidator
     */
    public function __construct(
        Validator $urlValidator
    ) {
        $this->urlValidator = $urlValidator;
    }

    /**
     * SchemaValidator Execute Rule
     *
     * @param mixed $value
     * @return bool
     */
    public function passes($value):bool
    {
        return $this->urlValidator->isValid($value);
    }
}
