<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic;

class Crosssell extends ReadonlyFormField
{
    use OrderRecommendation;

    public const URL_SLUG = 'crosssell';
}
