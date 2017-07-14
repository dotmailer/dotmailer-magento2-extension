<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config;

/**
 * Class Select
 * @package Dotdigitalgroup\Email\Block\Adminhtml\Config
 */
class Select extends \Magento\Framework\View\Element\Html\Select
{
    /**
     * Return output in one line.
     *
     * @return string
     */
    public function _toHtml() //@codingStandardsIgnoreLine
    {
        return trim(preg_replace('/\s+/', ' ', parent::_toHtml()));
    }
}
