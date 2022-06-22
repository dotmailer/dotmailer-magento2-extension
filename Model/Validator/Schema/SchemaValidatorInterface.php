<?php

namespace Dotdigitalgroup\Email\Model\Validator\Schema;

interface SchemaValidatorInterface
{
    /**
     * Set Pattern
     *
     * @param array $pattern
     * @return mixed
     */
    public function setPattern(array $pattern);

    /**
     * Run validator
     *
     * @param array $validatableStructure
     * @return bool
     */
    public function isValid(array $validatableStructure): bool;

    /**
     * Get errors
     *
     * @return mixed
     */
    public function getErrors();
}
