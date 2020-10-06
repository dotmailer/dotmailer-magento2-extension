<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic\Cart;

use Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic\ReadonlyFormField;

class Crosssell extends ReadonlyFormField
{
    use CartRecommendation;

    const URL_SLUG = 'crosssell';
}
