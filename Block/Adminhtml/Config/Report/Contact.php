<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Report;

class Contact extends AbstractConfigField
{
    /**
     * @deprecated
     *
     * @var string
     */
    public $buttonLabel = 'Contact Report';

    /**
     * @var string
     */
    protected $linkUrlPath = 'dotdigitalgroup_email/contact/index';
}
