<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic\Cart;

use Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic\ReadonlyFormField;

class Related extends ReadonlyFormField
{
    use CartRecommendation;

    public const URL_SLUG = 'related';
}
