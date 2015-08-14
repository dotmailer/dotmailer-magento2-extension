<?php

namespace Dotdigitalgroup\Email\Model\Resource\Catalog;

class Collection extends \Magento\Framework\Model\Resource\Db\Collection\AbstractCollection
{
	/**
	 * Initialize resource collection
	 *
	 * @return void
	 */
	public function _construct()
	{
		$this->_init('Dotdigitalgroup\Email\Model\Catalog', 'Dotdigitalgroup\Email\Model\Resource\Catalog');
	}


}