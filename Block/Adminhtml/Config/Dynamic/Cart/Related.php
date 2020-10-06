<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic\Cart;

use Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic\ReadonlyFormField;

class Related extends ReadonlyFormField
{
    use CartRecommendation;

    const URL_SLUG = 'related';
}
