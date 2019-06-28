<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Report;

class Wishlist extends AbstractConfigField
{
    /**
     * @deprecated
     *
     * @var string
     */
    public $buttonLabel = 'Wishlist Report';

    /**
     * @var string
     */
    protected $linkUrlPath = 'dotdigitalgroup_email/wishlist/index';
}
