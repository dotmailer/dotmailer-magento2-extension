<?php

namespace Dotdigitalgroup\Email\Model\Resource\Importer;

class Collection extends \Magento\Framework\Model\Resource\Db\Collection\AbstractCollection
{
	/**
	 * Initialize resource collection
	 *
	 * @return void
	 */
	public function _construct()
	{
		$this->_init('Dotdigitalgroup\Email\Model\Importer', 'Dotdigitalgroup\Email\Model\Resource\Importer');
	}

	/**
	 * Reset collection.
	 */
	public function reset()
	{
		$this->_reset();
	}

}