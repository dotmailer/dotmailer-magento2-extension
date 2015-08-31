<?php

namespace Dotdigitalgroup\Email\Model\Config\Configuration;

class Orderstatus
{
	public function __construct(
		\Magento\Sales\Model\Config\Source\Order\Status $status
	){
		$this->status = $status;
	}
    /**
     * Returns the order statuses for field order_statuses
     * @return array
     */
    public function toOptionArray()
    {

		$statuses = $this->status->toOptionArray();
		
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