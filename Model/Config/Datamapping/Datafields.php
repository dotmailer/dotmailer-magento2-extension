<?php

namespace Dotdigitalgroup\Email\Model\Config\Datamapping;

class Datafields implements \Magento\Framework\Option\ArrayInterface
{

	/**
	 * options
	 *
	 * @var array
	 */
	protected $_options = null;

	/**
	 * Configuration structure
	 *
	 * @var \Magento\Config\Model\Config\Structure
	 */
	protected $_configStructure;

	protected $logger;


	/**
	 * @param \Magento\Config\Model\Config\Structure $configStructure
	 */
	public function __construct(\Magento\Config\Model\Config\Structure $configStructure,
		\Psr\Log\LoggerInterface $logger,
		\Dotdigitalgroup\Email\Helper\Data $helper
	)
	{
		$this->_configStructure = $configStructure;
		$this->logger = $logger;
		$this->helper = $helper;
		//$this->request = $request;
	}
    /**
     *  Datafields option.
     * @return array
     */
    public function toOptionArray()
    {
        $fields = array();
	    //default data option
	    $fields[] = array('value' => 0, 'label' => '-- Please Select --');

	    return $fields;

	    $website = $this->request->getParam('website', 0);
        $client = $this->helper->getWebsiteApiClient($website);

	    //get datafields options
	    if ($this->helper->isEnabled($website)) {

		    $savedDatafields = Mage::registry( 'datafields' );

		    //get saved datafileds from registry
		    if ( $savedDatafields ) {
			    $datafields = $savedDatafields;
		    } else {
			    //grab the datafields request and save to register
			    $datafields = $client->getDataFields();
			    Mage::register( 'datafields', $datafields );
		    }

		    //set the api error message for the first option
		    if ( isset( $datafields->message ) ) {

			    //message
			    $fields[] = array( 'value' => 0, 'label' => Mage::helper( 'ddg' )->__( $datafields->message ) );

		    } else {

			    //loop for all datafields option
			    foreach ( $datafields as $datafield ) {
				    if ( isset( $datafield->name ) ) {
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