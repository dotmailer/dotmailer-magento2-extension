<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config;

class Select extends \Magento\Framework\View\Element\Html\Select
{
    /**
     * Return output in one line.
     *
     * @return string
     */
    public function _toHtml()
    {
        return trim(preg_replace('/\s+/', ' ', parent::_toHtml()));
    }
}
