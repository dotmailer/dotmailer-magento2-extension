<?php

namespace Dotdigitalgroup\Email\Model\Resource;

use Magento\Framework\Stdlib\DateTime as LibDateTime;

class Review extends \Magento\Framework\Model\Resource\Db\AbstractDb
{
	/**
	 * Initialize resource
	 *
	 * @return void
	 */
	public function _construct()
	{
		$this->_init('email_review', 'id');
	}

}