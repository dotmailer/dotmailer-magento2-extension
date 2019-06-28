<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Report;

class Catalog extends AbstractConfigField
{
    /**
     * @deprecated
     * @var string
     */
    public $buttonLabel = 'Catalog Report';

    /**
     * @var string
     */
    protected $linkUrlPath = 'dotdigitalgroup_email/catalog/index';
}
