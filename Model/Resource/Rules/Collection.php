<?php

namespace Dotdigitalgroup\Email\Model\Resource\Rules;

class Collection extends \Magento\Framework\Model\Resource\Db\Collection\AbstractCollection
{
	/**
	 * Initialize resource collection
	 *
	 * @return void
	 */
	public function _construct()
	{
		$this->_init('Dotdigitalgroup\Email\Model\Rules', 'Dotdigitalgroup\Email\Model\Resource\Rules');
	}

	/**
	 * Reset collection.
	 */
	public function reset()
	{
		$this->_reset();
	}

}