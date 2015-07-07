<?php
namespace Dotdigitalgroup\Email\Model\Config\Source\Carts;

class Campaigns implements \Magento\Framework\Option\ArrayInterface
{

	public function __construct(
		\Magento\Framework\Registry $registry,
		\Dotdigitalgroup\Email\Model\Apiconnector\Rest $rest
	)
	{
		$this->_registry = $registry;
		$this->rest = $rest;
	}


	public function toOptionArray()
    {
        $fields = array();
		//@todo check for website, and check if enabled before continue

	    $savedCampaigns = $this->_registry->registry('campaigns');


	    if ( $savedCampaigns ) {
		    $campaigns = $savedCampaigns;
	    } else {
		    //grab the datafields request and save to register
		    $campaigns = $this->rest->getCampaigns();
		    $this->_registry->register('campaigns', $campaigns);
	    }

	    //set the api error message for the first option
	    if ( isset( $campaigns->message ) ) {
		    //message
		    $fields[] = array( 'value' => 0, 'label' => $campaigns->message);
	    } else {
		    //loop for all campaing option
		    foreach ( $campaigns as $campaign ) {
			    if ( isset( $campaign->name ) ) {
				    $fields[] = array(
					    'value' => $campaign->id,
					    'label' => addslashes($campaign->name)
				    );
			    }
		    }
	    }


        return $fields;
    }

}