<?php

namespace Dotdigitalgroup\Email\Model;

class Quote extends \Magento\Framework\Model\AbstractModel
{

    /**
     * constructor
     */
    public function _construct()
    {
        parent::_construct();
	    $this->_init('Dotdigitalgroup\Email\Model\Resource\Quote');
    }

	/**
	 * load quote from connector table
	 *
	 * @param $quoteId
	 * @return bool
	 */
	public function loadQuote($quoteId)
	{
		$collection = $this->getCollection();
		$collection->addFieldToFilter('quote_id', $quoteId)
		           ->setPageSize(1);

		if ($collection->count()) {
			return $collection->getFirstItem();
		}
		return false;
	}



}