<?php

namespace Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules;

class Condition
{
    /**
     * Options array.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            ['value' => 'eq', 'label' => __('is')],
            ['value' => 'neq', 'label' => __('is not')],
            ['value' => 'null', 'label' => __('is empty')],
        ];

        return $options;
    }

    /**
     * Get condition options according to type.
     *
     * @param string $type
     *
     * @return array
     */
    public function getInputTypeOptions($type)
    {
        switch ($type) {
            case 'numeric':
                return $this->optionsForNumericType();

            case 'select':
                return $this->toOptionArray();

            case 'string':
            case 'email':
                return $this->optionsForStringType();
        }

        return $this->optionsForStringType();
    }

    /**
     * Condition options for numeric type.
     *
     * @return array
     */
    private function optionsForNumericType()
    {
        $options = $this->toOptionArray();
        $options[] = [
            'value' => 'gteq',
            'label' => __('equal to or greater than'),
        ];
        $options[] = [
            'value' => 'lteq',
            'label' => __('equal to or less than'),
        ];
        $options[] = ['value' => 'gt', 'label' => __('greater than')];
        $options[] = ['value' => 'lt', 'label' => __('less than')];

        return $options;
    }

    /**
     * Condition options for string type.
     *
     * @return array
     */
    private function optionsForStringType()
    {
        $options = $this->toOptionArray();
        $options[] = [
            'value' => 'like',
            'label' => __('contains')
        ];
        $options[] = [
            'value' => 'nlike',
            'label' => __('does not contain'),
        ];

        return $options;
    }
}
