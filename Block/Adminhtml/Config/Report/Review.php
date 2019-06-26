<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Report;

class Review extends AbstractConfigField
{
    /**
     * @deprecated
     *
     * @var string
     */
    public $buttonLabel = 'Review Report';

    /**
     * @var string
     */
    protected $linkUrlPath = 'dotdigitalgroup_email/review/index';
}
