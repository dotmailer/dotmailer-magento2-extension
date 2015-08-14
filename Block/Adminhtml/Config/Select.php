<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Config_Select extends Mage_Core_Block_Html_Select
{
    /**
     * Return output in one line
     *
     * @return string
     */
    public function _toHtml()
    {
        return trim(preg_replace('/\s+/', ' ',parent::_toHtml()));
    }
}