<?php

namespace Dotdigitalgroup\Email\Model\Resource;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime as LibDateTime;
use Magento\Store\Model\Store;
use Magento\Catalog\Model\Product;

class Automation extends \Magento\Framework\Model\Resource\Db\AbstractDb
{
	/**
	 * Initialize resource
	 *
	 * @return void
	 */
	public function _construct()
	{
		$this->_init('email_automation', 'id');
	}

}