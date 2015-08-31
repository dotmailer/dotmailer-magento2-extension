<?php

namespace Dotdigitalgroup\Email\Model\Config\Source\Datamapping;

class Datafields implements \Magento\Framework\Option\ArrayInterface
{

	/**
	 * options
	 *
	 * @var array
	 */
	protected $_options = null;
	protected $_helper;
	protected $rest;


	/**
	 * Configuration structure
	 *
	 * @var \Magento\Config\Model\Config\Structure
	 */
	protected $_configStructure;

	protected $_logger;


	public function __construct(
		\Psr\Log\LoggerInterface $logger,
		\Magento\Framework\Registry $registry,
		\Dotdigitalgroup\Email\Helper\Data $data,
		\Dotdigitalgroup\Email\Model\Apiconnector\Rest $rest,
		\Magento\Store\Model\StoreManagerInterface $storeManager
	)
	{
		$this->rest = $rest;
		$this->_helper = $data;
		$this->_logger = $logger;
		$this->_registry = $registry;
		$this->_storeManager = $storeManager;
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

	    $apiEnabled = $this->_helper->isEnabled($this->_helper->getWebsite());
	    if ($apiEnabled) {
		    $savedDatafields = $this->_registry->registry( 'datafields' );

		    //get saved datafileds from registry
		    if ( $savedDatafields ) {
			    $datafields = $savedDatafields;
		    } else {
			    //grab the datafields request and save to register
			    $datafields = $this->rest->getDatafields();
			    $this->_registry->register( 'datafields', $datafields );
		    }

		    //set the api error message for the first option
		    if ( isset( $datafields->message ) ) {
			    //message
			    $fields[] = array( 'value' => 0, 'label' => $datafields->message );
		    } else {
			    //loop for all datafields option
			    foreach ( $datafields as $datafield ) {
				    if ( isset( $datafield->name ) ) {
					    $fields[] = array(
						    'value' => $datafield->name,
						    'label' => $datafield->name
					    );
				    }
			    }
		    }
	    }

        return $fields;
    }
}