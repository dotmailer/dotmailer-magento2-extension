<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Advanced_Quoteattributes
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $fields = Mage::helper('ddg')->getQuoteTableDescription();

        $customFields = array();
        foreach($fields as $key => $field){
            $customFields[] = array(
                'value' => $field['COLUMN_NAME'],
                'label' => $field['COLUMN_NAME']
            );
        }
        return $customFields;
    }
}