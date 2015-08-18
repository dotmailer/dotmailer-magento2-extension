<?php

namespace Dotdigitalgroup\Email\Model\Resource\Order;

class Collection extends \Magento\Framework\Model\Resource\Db\Collection\AbstractCollection
{
	/**
	 * Initialize resource collection
	 *
	 * @return void
	 */
	public function _construct()
	{
		$this->_init('Dotdigitalgroup\Email\Model\Order', 'Dotdigitalgroup\Email\Model\Resource\Order');
	}


}