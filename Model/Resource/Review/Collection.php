<?php

namespace Dotdigitalgroup\Email\Model\Resource\Review;

class Collection extends \Magento\Framework\Model\Resource\Db\Collection\AbstractCollection
{
	/**
	 * Initialize resource collection
	 *
	 * @return void
	 */
	public function _construct()
	{
		$this->_init('Dotdigitalgroup\Email\Model\Review', 'Dotdigitalgroup\Email\Model\Resource\Review');
	}


}