<?php

namespace Dotdigitalgroup\Email\Model\Adminhtml\Source;

 class Addressbooks extends \Magento\Framework\Model\AbstractModel
{
	protected $_helper;
	protected $_objectManager;
	public function __construct(
		 \Magento\Framework\Model\Context $context,
		 \Magento\Framework\Registry $registry,
		 \Magento\Framework\App\RequestInterface $requestInterface,
		 \Magento\Framework\Model\Resource\AbstractResource $resource = null,
		 \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
		 array $data = [],
		 \Dotdigitalgroup\Email\Helper\Data $data,
	     \Magento\Framework\ObjectManagerInterface $objectManagerInterface
	)
	{
		$this->_helper  = $data;
		$this->_request = $requestInterface;
		$this->_objectManager = $objectManagerInterface;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
	}
	/**
	* Returns the address books options.
	*
	* @return array
	*/
	public function toOptionArray()
	{
        $fields = array();
	    // Add a "Do Not Map" Option
	    $fields[] = array('value' => 0, 'label' => __('-- Please Select --'));
        $website = $this->_request->getParam('website');

		$apiEnabled = $this->_helper->isEnabled($website);

		//get address books options
		if ($apiEnabled) {
			$client = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Apiconnector\Client');
			$client->setApiUsername( $this->_helper->getApiUsername( $website ) )
			       ->setApiPassword( $this->_helper->getApiPassword( $website ) );

			$savedAddressBooks = Mage::registry( 'addressbooks' );
			//get saved address books from registry
			if ( $savedAddressBooks ) {
				$addressBooks = $savedAddressBooks;
			} else {
				// api all address books
				$addressBooks = $client->getAddressBooks();
				Mage::register( 'addressbooks', $addressBooks );
			}

			//set up fields with book id and label
			foreach ( $addressBooks as $book ) {
				if ( isset( $book->id ) ) {
					$fields[] = array( 'value' => $book->id, 'label' => $book->name );
				}
			}
		}

        return $fields;
    }

}