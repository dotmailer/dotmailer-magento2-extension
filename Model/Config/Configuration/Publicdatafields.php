<?php

namespace Dotdigitalgroup\Email\Model\Config\Configuration;

class Publicdatafields
{
	protected $_objectManager;
	protected $_helper;


	public function __construct(
		\Magento\Framework\ObjectManagerInterface $objectManagerInterface,
		\Dotdigitalgroup\Email\Helper\Data $data
	)
	{
		$this->_objectManager = $objectManagerInterface;
		$this->_helper = $data;
	}

	/**
	 * get data fields
	 *
	 * @return mixed
	 */
	private function getDataFields()
	{
		$website = $this->_helper->getWebsite();
		$client = $this->_helper->getWebsiteApiClient($website);

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
	    $apiEnabled = $this->_helper->isEnabled($this->_helper->getWebsite());
	    //get datafields options
	    if ($apiEnabled) {
			$datafields = $this->getDataFields();

		    //set the api error message for the first option
		    if ( isset( $datafields->message ) ) {
			    //message
			    $fields[] = array( 'value' => 0, 'label' => __( $datafields->message ) );
		    } else {
			    //loop for all datafields option
			    foreach ( $datafields as $datafield ) {
				    if ( isset( $datafield->name ) &&  $datafield->visibility == 'Public') {
					    $fields[] = array(
						    'value' => $datafield->name,
						    'label' => __( $datafield->name )
					    );
				    }
			    }
		    }
	    }
        return $fields;
    }
}