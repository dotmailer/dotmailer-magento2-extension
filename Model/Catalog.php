<?php

namespace Dotdigitalgroup\Email\Model;

class Catalog extends \Magento\Framework\Model\AbstractModel
{
    /**
     * constructor
     */
    public function _construct()
    {
	    $this->_init('Dotdigitalgroup\Email\Model\Resource\Catalog');
    }

}