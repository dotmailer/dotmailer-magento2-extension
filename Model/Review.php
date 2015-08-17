<?php

namespace Dotdigitalgroup\Email\Model;

class Review extends \Magento\Framework\Model\AbstractModel
{
    private $_start;
    private $_countReviews;
    private $_reviews;
    private $_reviewIds;

    const EMAIL_REVIEW_IMPORTED = 1;

    /**
     * constructor
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('Dotgitalgroup\Email\Model\Resource\Review');
    }




}