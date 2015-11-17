<?php

namespace Dotdigitalgroup\Email\Model\Config\Configuration;

class Quoteattributes
{

	public function __construct(
		\Dotdigitalgroup\Email\Helper\Data $dataHelper
	)
	{
		$this->_dataHelper = $dataHelper;
	}
    /**
     * @return array
     */
    public function toOptionArray()
    {

        $fields = $this->_dataHelper->getQuoteTableDescription();

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