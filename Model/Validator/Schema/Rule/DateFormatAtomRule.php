<?php

namespace Dotdigitalgroup\Email\Model\Validator\Schema\Rule;

class DateFormatAtomRule implements ValidatorRuleInterface
{
    /**
     * Validate ATOM date format
     *
     * @param mixed $value
     * @return bool
     */
    public function passes($value):bool
    {
        $date = \DateTime::createFromFormat(\DateTimeInterface::ATOM, $value);
        if ($date === false) {
            return false;
        }
        $formattedDate = $date->format(\DateTimeInterface::ATOM);
        return ($formattedDate === $value);
    }
}
