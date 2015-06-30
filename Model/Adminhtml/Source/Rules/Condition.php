<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Rules_Condition
{
    /**
     * options array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = array(
            array('value' => 'eq', 'label' => Mage::helper('ddg')->__('is')),
            array('value' => 'neq', 'label' => Mage::helper('ddg')->__('is not')),
            array('value' => 'null', 'label' => Mage::helper('ddg')->__('is empty')),
        );
        return $options;
    }

    /**
     * get condition options according to type
     *
     * @param $type
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
                return $this->optionsForStringType();
        }
    }

    /**
     * condition options for numeric type
     *
     * @return array
     */
    public function optionsForNumericType()
    {
        $options = $this->toOptionArray();
        $options[] = array('value' => 'gteq', 'label' => Mage::helper('ddg')->__('equals or greater than'));
        $options[] = array('value' => 'lteq', 'label' => Mage::helper('ddg')->__('equals or less then'));
        $options[] = array('value' => 'gt', 'label' => Mage::helper('ddg')->__('greater than'));
        $options[] = array('value' => 'lt', 'label' => Mage::helper('ddg')->__('less than'));
        return $options;
    }

    /**
     * condition options for string type
     *
     * @return array
     */
    public function optionsForStringType()
    {
        $options = $this->toOptionArray();
        $options[] = array('value' => 'like', 'label' => Mage::helper('ddg')->__('contains'));
        $options[] = array('value' => 'nlike', 'label' => Mage::helper('ddg')->__('does not contains'));
        return $options;
    }
}