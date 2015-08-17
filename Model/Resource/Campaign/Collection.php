<?php

namespace Dotdigitalgroup\Email\Model\Resource\Campaign;

class Collection extends \Magento\Framework\Model\Resource\Db\Collection\AbstractCollection
{
	/**
	 * Initialize resource collection
	 *
	 * @return void
	 */
	public function _construct()
	{
		$this->_init('Dotdigitalgroup\Email\Model\Campaign', 'Dotdigitalgroup\Email\Model\Resource\Campaign');
	}


}