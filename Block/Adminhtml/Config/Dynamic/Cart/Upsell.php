<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic\Cart;

use Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic\ReadonlyFormField;

class Upsell extends ReadonlyFormField
{
    use CartRecommendation;

    public const URL_SLUG = 'upsell';
}
