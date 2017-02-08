<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Developer;

class Daterange extends \Magento\Config\Block\System\Config\Form\Field
{
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $ranges = array('from', 'to');
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

        $js = "
            <script type='application/javascript'>
                require(['jquery'], function (j) {
                    j(document).ready(function() {
                       
                        function updateUrlParameter(uri, key, value) {
                            var re = new RegExp('([?&])' + key + '=.*?(&|$)', 'i');
                            var separator = uri.indexOf('?') !== -1 ? '&' : '?';
                            if (uri.match(re)) {
                                return uri.replace(re, '$1' + key + '=' + value + '$2');
                            }
                            else {
                                return uri + separator + key + '=' + value;
                            }
                        }
                        
                        var elmToObserve = ['from', 'to'];
                        var elmToChange = 
                            [
                                '#connector_developer_settings_sync_settings_reset_orders', 
                                '#connector_developer_settings_sync_settings_reset_reviews', 
                                '#connector_developer_settings_sync_settings_reset_wishlists', 
                                '#connector_developer_settings_sync_settings_reset_catalog'
                            ];
                        j.each(elmToObserve, function( key, value ) {
                          //j('#' + value).prop('disabled', true);
                          j('#' + value).change(function() {
                              j.each(elmToChange, function( k, v ) {
                                  var str = j(v).attr('onclick');
                                  var updatedUrl = updateUrlParameter(str, value, encodeURIComponent(j('#' + value).val()));
                                  j(v).attr('onclick', updatedUrl);
                              });
                            });
                        });
                    })
                });    
            </script>
        ";

        return $dateElements . $js;
    }
}
