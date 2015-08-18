<?php

namespace Dotdigitalgroup\Email\Model;

class Automation extends \Magento\Framework\Model\AbstractModel
{

	/**
	 * constructor
	 */
	public function _construct()
	{
		parent::_construct();
		$this->_init('Dotdigitalgroup\Email\Model\Resource\Automation');
	}


}