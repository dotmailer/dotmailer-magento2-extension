<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Rules;

class Customdatafields extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{

    /**
     * @var
     */
    public $getAttributeRenderer;

    /**
     * @var
     */
    public $getConditionsRenderer;

    /**
     * @var
     */
    public $getValueRenderer;

    /**
     * @var \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Condition
     */
    public $condition;
    /**
     * @var \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Value
     */
    public $value;

    /**
     * Customdatafields constructor.
     *
     * @param \Magento\Backend\Block\Template\Context                       $context
     * @param \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Condition $condition
     * @param \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Value     $value
     * @param array                                                         $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Condition $condition,
        \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Value $value,
        $data = []
    ) {
        $this->condition = $condition;
        $this->value     = $value;
        $this->_addAfter = false;

        $this->_addButtonLabel = __('Add New Condition');
        parent::__construct($context, $data);
    }

    public function _prepareToRender()
    {
        $this->getConditionsRenderer = null;
        $this->getAttributeRenderer  = null;
        $this->getValueRenderer      = null;
        $this->addColumn(
            'attribute',
            [
                'label' => __('Attribute'),
                'style' => 'width:120px',
            ]
        );
        $this->addColumn(
            'conditions',
            [
                'label' => __('Condition'),
                'style' => 'width:120px',
            ]
        );
        $this->addColumn(
            'cvalue',
            [
                'label' => __('Value'),
                'style' => 'width:120px',
            ]
        );
    }

    /**
     * render cell template.
     *
     * @param string $columnName
     *
     * @return string
     */
    public function renderCellTemplate($columnName)
    {
        if ($columnName == 'attribute') {
            return $this->_getAttributeRenderer()
                ->setName($this->_getCellInputElementName($columnName))
                ->setTitle($columnName)
                ->setExtraParams('style="width:160px"')
                ->setOptions(
                    $this->getElement()->getValues()
                )
                ->toHtml();
        } elseif ($columnName == 'conditions') {
            return $this->_getConditionsRenderer()
                ->setName($this->_getCellInputElementName($columnName))
                ->setTitle($columnName)
                ->setExtraParams('style="width:160px"')
                ->setOptions(
                    $this->condition->toOptionArray()
                )
                ->toHtml();
        } elseif ($columnName == 'cvalue') {
            return $this->_getValueRenderer()
                ->setName($this->_getCellInputElementName($columnName))
                ->setTitle($columnName)
                ->setExtraParams('style="width:160px"')
                ->setOptions(
                    $this->value->toOptionArray()
                )
                ->toHtml();
        }

        return parent::renderCellTemplate($columnName);
    }

    /**
     * @param \Magento\Framework\DataObject $row
     */
    public function _prepareArrayRow(\Magento\Framework\DataObject $row)
    {
        $options = [];

        $options['option_' . $this->_getAttributeRenderer()->calcOptionHash(
            $row->getData('attribute')
        )]
            = 'selected="selected"';
        $options['option_' . $this->_getConditionsRenderer()->calcOptionHash(
            $row->getData('conditions')
        )]
            = 'selected="selected"';
        $options['option_' . $this->_getValueRenderer()->calcOptionHash(
            $row->getData('cvalue')
        )]
            = 'selected="selected"';

        $row->setData('option_extra_attrs', $options);
    }

    /**
     * Get rendered for attribute field.
     *
     * @return mixed
     */
    public function _getAttributeRenderer()
    {
        if (!$this->getAttributeRenderer) {
            $this->getAttributeRenderer = $this->getLayout()
                ->createBlock(
                    'Dotdigitalgroup\Email\Block\Adminhtml\Config\Select',
                    '',
                    ['data' => ['is_render_to_js_template' => true]]
                );
        }

        return $this->getAttributeRenderer;
    }

    /**
     * Get renderer for conditions field.
     *
     * @return mixed
     */
    public function _getConditionsRenderer()
    {
        if (!$this->getConditionsRenderer) {
            $this->getConditionsRenderer = $this->getLayout()
                ->createBlock(
                    'Dotdigitalgroup\Email\Block\Adminhtml\Config\Select',
                    '',
                    ['data' => ['is_render_to_js_template' => true]]
                );
        }

        return $this->getConditionsRenderer;
    }

    /**
     * Get renderer for value field.
     *
     * @return mixed
     */
    public function _getValueRenderer()
    {
        if (!$this->getValueRenderer) {
            $this->getValueRenderer = $this->getLayout()
                ->createBlock(
                    'Dotdigitalgroup\Email\Block\Adminhtml\Config\Select',
                    '',
                    ['data' => ['is_render_to_js_template' => true]]
                );
        }

        return $this->getValueRenderer;
    }

    /**
     * @return string
     *
     * @throws \Exception
     */
    public function _toHtml()
    {
        $script
            = "<script type=\"text/javascript\">
                require([
                    'prototype',
                    'domReady!'
                ], function () {
                    $$('.admin__control-table tr td:first-child select').each(function(item) {
                        doUpdateWithValues(item);
                    });

                    $$('.admin__control-table tr td:first-child select').each(function(item) {
                        Event.observe(item,'change', function(){
                            doUpdate(item);
                        });
                    });

                    $$('.admin__control-table button.action-add').each(function(item) {
                         Event.observe(item,'click', function(){
                            $$('.admin__control-table tr td:first-child select').each(function(item) {
                                 Event.observe(item,'change', function(){
                                    doUpdate(item);
                                });
                            });
                            $$('.admin__control-table tr td select').each(function(item) {
                                Event.observe(item,'change', function(){
                                     if(item.readAttribute('title') == 'conditions'){
                                        doUpdateForCondition(item);
                                     }
                                });
                            });
                        });
                    });

                    function doUpdate(item){
                        var url = '" . $this->getUrl(
            'dotdigitalgroup_email/rules/ajax'
        ) . "';
                        var cond = item.up(1).down().next();
                        var condName = cond.down().readAttribute('name');
                        var value = item.up(1).down().next(1);
                        var valueName = value.down().readAttribute('name');
                        var attribute = item.value;
                        new Ajax.Request(url, {
                            method: 'post',
                            parameters: {
                                attribute: attribute,
                                condition: condName,
                                value: valueName
                            },
                            onSuccess: function(transport){
                                var json = transport.responseJSON;
                                cond.update(json.condition);
                                value.update(json.cvalue);

                                $$('.admin__control-table tr td select').each(function(item) {
                                    Event.observe(item,'change', function(){
                                         if(item.readAttribute('title') == 'conditions'){
                                            doUpdateForCondition(item);
                                         }
                                    });
                                });
                            }
                        });
                    }

                    function doUpdateWithValues(item){
                        var url = '" . $this->getUrl(
            'dotdigitalgroup_email/rules/selected'
        ) . "';
                        var arrayKey = item.up(1).readAttribute('id');
                        var cond = item.up(1).down().next();
                        var condName = cond.down().readAttribute('name');
                        var value = item.up(1).down().next(1);
                        var valueName = value.down().readAttribute('name');
                        var attribute = item.value;
                        var ruleId = $('rule_id').value;
                        new Ajax.Request(url, {
                            method: 'post',
                            parameters: {
                                attribute: attribute,
                                condition: condName,
                                value: valueName,
                                arraykey: arrayKey,
                                ruleid: ruleId
                            },
                            onSuccess: function(transport){
                                var json = transport.responseJSON;
                                cond.update(json.condition);
                                value.update(json.cvalue);

                                $$('.admin__control-table tr td select').each(function(item) {
                                    Event.observe(item,'change', function(){
                                         if(item.readAttribute('title') == 'conditions'){
                                            doUpdateForCondition(item);
                                         }
                                    });
                                });
                            }
                        });
                    }

                    function doUpdateForCondition(item){
                        var url = '" . $this->getUrl(
            'dotdigitalgroup_email/rules/value'
        ) . "';
                        var attribute = item.up(1).down();
                        var attributeValue = attribute.down().value;
                        var value = item.up().next();
                        var valueName = value.down().readAttribute('name');
                        var condValue = item.value;
                        new Ajax.Request(url, {
                            method: 'post',
                            parameters: {
                                attributeValue: attributeValue,
                                value: valueName,
                                condValue: condValue
                            },
                            onSuccess: function(transport){
                                var json = transport.responseJSON;
                                value.update(json.cvalue);
                            }
                        });
                    }
                });
            </script>";

        return '<input type="hidden" id="' . $this->getElement()->getHtmlId()
        . '"/>' . parent::_toHtml() . $script;
    }
}
