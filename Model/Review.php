<?php

namespace Dotdigitalgroup\Email\Model;

class Review extends \Magento\Framework\Model\AbstractModel
{

    const EMAIL_REVIEW_IMPORTED = 1;

    /**
     * constructor
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('Dotdigitalgroup\Email\Model\Resource\Review');
    }




}