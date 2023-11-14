<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic;

class Upsell extends ReadonlyFormField
{
    use OrderRecommendation;

    public const URL_SLUG = 'upsell';
}
