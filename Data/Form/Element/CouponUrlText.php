<?php

namespace Dotdigitalgroup\Email\Data\Form\Element;

use Magento\Framework\Data\Form\Element\Textarea;

class CouponUrlText extends Textarea
{
    /**
     * @return array|string[]
     */
    public function getHtmlAttributes()
    {
        return array_merge(parent::getHtmlAttributes(), [
            'data-baseurl',
            'data-email-merge-field',
        ]);
    }
}
