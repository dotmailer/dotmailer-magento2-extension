<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Publicdatafields
{
	private function getWebsite()
	{
		$website = Mage::app()->getWebsite();
		$websiteParam = Mage::app()->getRequest()->getParam('website');
		if($websiteParam)
			$website = Mage::app()->getWebsite($websiteParam);
		return $website;
	}

	/**
	 * get data fields
	 *
	 * @return mixed
	 */
	private function getDataFields()
	{
		$helper = Mage::helper('ddg');
		$website = $this->getWebsite();
		$client = $helper->getWebsiteApiClient($website);

		//grab the datafields request and save to register
		$datafields = $client->getDataFields();

		return $datafields;
	}

    /**
     *  Datafields option.
     * @return array
     */
    public function toOptionArray()
    {
        $fields = array();
        $helper = Mage::helper('ddg');
		$website = $this->getWebsite();

	    //get datafields options
	    if ($helper->isEnabled($website)) {
			$datafields = $this->getDataFields();

		    //set the api error message for the first option
		    if ( isset( $datafields->message ) ) {
			    //message
			    $fields[] = array( 'value' => 0, 'label' => Mage::helper( 'ddg' )->__( $datafields->message ) );
		    } else {
			    //loop for all datafields option
			    foreach ( $datafields as $datafield ) {
				    if ( isset( $datafield->name ) &&  $datafield->visibility == 'Public') {
					    $fields[] = array(
						    'value' => $datafield->name,
						    'label' => Mage::helper( 'ddg' )->__( $datafield->name )
					    );
				    }
			    }
		    }
	    }
        return $fields;
    }
}