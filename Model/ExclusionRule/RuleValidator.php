<?php

namespace Dotdigitalgroup\Email\Model\ExclusionRule;

use Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Type;

class RuleValidator
{
    /**
     * @var Type
     */
    private $ruleType;

    public function __construct(
        Type $ruleType
    ) {
        $this->ruleType = $ruleType;
    }

    /**
     * @param \Dotdigitalgroup\Email\Model\Rules $rule
     * @return RuleValidator
     * @throws \Magento\Framework\Exception\ValidatorException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validate($rule)
    {
        $valid = true;
        $conditions = $rule->getData('conditions');

        foreach ($conditions as $condition) {
            $inputType = $this->ruleType->getInputType($condition['attribute']);

            switch ($inputType) {
                case 'email':
                    if (!in_array($condition['conditions'], ['like', 'nlike'])) {
                        $valid = $this->validateEmail($condition['cvalue']);
                    }
                    break;
                case 'numeric':
                    $valid = $this->validateNumber($condition['cvalue']);
                    break;
            }
            if (!$valid) {
                throw new \Magento\Framework\Exception\ValidatorException(
                    __(
                        'Rule validation error: attribute ' .
                        $condition['attribute'] .
                        ' must be of type ' .
                        $inputType
                    )
                );
            }
        }

        return $this;
    }

    /**
     * @param string|bool $inputType
     * @param string $condition
     * @return string|bool
     */
    public function setFrontEndValidation($inputType, $condition)
    {
        switch ($inputType) {
            case 'email':
                if (!in_array($condition, ['like', 'nlike'])) {
                    return '{\'validate-email\':true}';
                }
                break;
            case 'numeric':
                return '{\'validate-number\':true}';
        }
        return false;
    }

    /**
     * @param $email
     * @return mixed
     */
    private function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * @param $number
     * @return bool
     */
    private function validateNumber($number)
    {
        return is_numeric($number);
    }
}
