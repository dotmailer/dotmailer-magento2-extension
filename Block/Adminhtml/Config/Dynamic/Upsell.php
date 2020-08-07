<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic;

class Upsell extends ReadonlyFormField
{
    use OrderRecommendation;

    const URL_SLUG = 'upsell';
}
