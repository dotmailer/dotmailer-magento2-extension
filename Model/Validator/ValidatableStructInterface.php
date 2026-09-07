<?php

namespace Dotdigitalgroup\Email\Model\Validator;

interface ValidatableStructInterface
{
    /**
     * Get validation pattern for the struct
     *
     * @return array
     */
    public function getValidationPattern(): array;
}
