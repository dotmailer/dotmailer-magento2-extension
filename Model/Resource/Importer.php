<?php

namespace Dotdigitalgroup\Email\Model\Resource;

class Importer extends \Magento\Framework\Model\Resource\Db\AbstractDb
{
	/**
	 * Initialize resource
	 *
	 * @return void
	 */
	public function _construct()
	{
		$this->_init('email_importer', 'id');
	}

}