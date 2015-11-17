<?php

namespace Dotdigitalgroup\Email\Model\Config\Configuration;

class Attributes
{

	public function __construct(
		\Dotdigitalgroup\Email\Helper\Data $dataHelper
	)
	{
		$this->_dataHelper = $dataHelper;
	}

	/**
     * Returns custom order attributes
     * @return array
     */
    public function toOptionArray()
    {
        $fields = $this->_dataHelper->getOrderTableDescription();

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