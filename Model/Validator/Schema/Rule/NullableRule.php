<?php

namespace Dotdigitalgroup\Email\Model\Validator\Schema\Rule;

/**
 * Marker rule denoting a field may be left empty/null.
 *
 * This rule itself always passes - the actual "skip remaining rules when
 * empty" behaviour is implemented in SchemaValidator, which looks for the
 * presence of this rule in a field's rule set.
 */
class NullableRule implements ValidatorRuleInterface
{
    /**
     * Always passes - presence of this rule is what matters, not its result.
     *
     * @param mixed $value
     * @return bool
     */
    public function passes($value):bool
    {
        return true;
    }
}
