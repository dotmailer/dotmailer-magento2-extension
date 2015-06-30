<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Orderstatus
{
    /**
     * Returns the order statuses for field order_statuses
     * @return array
     */
    public function toOptionArray()
    {
        $source = Mage::getModel('adminhtml/system_config_source_order_status');
		$statuses = $source->toOptionArray();
		
		// Remove the "please select" option if present
		if(count($statuses) > 0 && $statuses[0]['value'] == '')
			array_shift($statuses);
		
        $options = array();
		
		foreach($statuses as $status) {
            $options[] = array(
                   'value' => $status['value'],
                   'label' => $status['label']
                );
		}
		return $options;
    }
}