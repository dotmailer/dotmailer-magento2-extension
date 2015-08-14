<?php

namespace Dotdigitalgroup\Email\Model;

class Importer extends \Magento\Framework\Model\AbstractModel
{

	/**
	 * constructor
	 */
	public function _construct()
	{
		$this->_init('Dotdigitalgroup\Email\Model\Resource\Importer');
	}


}