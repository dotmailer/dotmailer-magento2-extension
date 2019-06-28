<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Report;

class Order extends AbstractConfigField
{
    /**
     * @deprecated
     *
     * @var string
     */
    public $buttonLabel = 'Order Report';

    /**
     * @var string
     */
    protected $linkUrlPath = 'dotdigitalgroup_email/order/index';
}
