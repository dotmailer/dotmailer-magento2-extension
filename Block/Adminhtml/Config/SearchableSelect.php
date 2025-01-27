<?php
namespace Dotdigitalgroup\Email\Block\Adminhtml\Config;

class SearchableSelect extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Get element html.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $script = " <script>
                require([
                    'jquery',
                    'select2'
                ], function ($, select2) {
                    var select2Field = $('#" . $element->getId() . "').select2({
                        width: '100%',
                        placeholder: '" . __('Select Options') . "'
                    });

                    select2Field.data('select2').\$container.addClass(' select admin__control-select');
                    $('#" . $element->getId() . "_inherit').change(function() {

                        $('#" . $element->getId() . "').prop('disabled', $(this).is(':checked'));
                          $('#" . $element->getId() . "').trigger('change.select2');
                    });
                })
            </script>";
        return parent::_getElementHtml($element) . $script;
    }
}
