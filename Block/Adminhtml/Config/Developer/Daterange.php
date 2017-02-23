<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Developer;

class Daterange extends \Magento\Config\Block\System\Config\Form\Field
{
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $ranges = ['from', 'to'];
        $dateElements = '';
        foreach ($ranges as $range) {
            $dateElements .=
                "<div style='width: 200px; margin-bottom: 2px;'>" .
                "<p style='width:45px !important; margin: 0 !important; display: inline-block; font-weight:bold;'>"
                . ucfirst($range) . ":
                    </p>
                    <input id='" . $range . "' name='" . $range . "'data-ui-id='' 
                        value='' class='input-text admin__control-text' type='text' />
                </div>" .
                '<script type="text/javascript">
                require(["jquery", "jquery/ui"], function () {
                jQuery(document).ready(function () {
                    var el = jQuery("#' . $range . '");
                    el.datepicker({dateFormat:"yy-mm-dd"});
                    el.addClass("datepicker");
                });
            });
            </script>';
        }
        return $dateElements;
    }
}
