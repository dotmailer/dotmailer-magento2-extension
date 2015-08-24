<?php

namespace Dotdigitalgroup\Email\Model\Resource\Wishlist;

class Collection extends \Magento\Framework\Model\Resource\Db\Collection\AbstractCollection
{
	/**
	 * Initialize resource collection
	 *
	 * @return void
	 */
	public function _construct()
	{
		$this->_init('Dotdigitalgroup\Email\Model\Wishlist', 'Dotdigitalgroup\Email\Model\Resource\Wishlist');
	}


}