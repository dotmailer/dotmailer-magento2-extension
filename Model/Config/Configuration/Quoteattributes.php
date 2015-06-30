<?php
namespace Dotdigitalgroup\Email\Model\Config\Configuration;

class Quoteattributes
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
	    return array();
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