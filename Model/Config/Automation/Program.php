<?php

namespace Dotdigitalgroup\Email\Model\Config\Automation;

class Program
{

	public function toOptionArray()
	{
		$fields = array();


		return $fields;
		$websiteName = Mage::app()->getRequest()->getParam('website', false);

        $website = Mage::app()->getRequest()->getParam('website', false);
        if ($website)
            $website = Mage::app()->getWebsite($website);
        else
            $website = 0;

		$fields[] = array('value' => '0', 'label' => Mage::helper('ddg')->__('-- Disabled --'));
		if ($websiteName) {
			$website = Mage::app()->getWebsite($websiteName);
		}

		if (Mage::helper('ddg')->isEnabled($website)) {

			$client = Mage::helper( 'ddg' )->getWebsiteApiClient( $website );
			$programmes = $client->getPrograms();

			foreach ( $programmes as $one ) {
				if ( isset( $one->id ) ) {
                    if($one->status == 'Active'){
					    $fields[] = array( 'value' => $one->id, 'label' => Mage::helper( 'ddg' )->__( $one->name ) );
                    }
				}
			}
		}

		return $fields;
	}

}