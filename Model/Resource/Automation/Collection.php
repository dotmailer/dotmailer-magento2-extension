<?php

namespace Dotdigitalgroup\Email\Model\Resource\Automation;

class Collection extends \Magento\Framework\Model\Resource\Db\Collection\AbstractCollection
{
	/**
	 * Initialize resource collection
	 *
	 * @return void
	 */
	public function _construct()
	{
		$this->_init('Dotdigitalgroup\Email\Model\Automation', 'Dotdigitalgroup\Email\Model\Resource\Automation');
	}


}