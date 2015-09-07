<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Rules;

class Customdatafields  extends \Magento\Config\Block\System\Config\Form\Field
{
    protected $_getAttributeRenderer;
    protected $_getConditionsRenderer;
    protected $_getValueRenderer;

	protected $_objectManager;
    /**
	 * Construct.
	 */
    public function __construct(
	    \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManagerInterface,
		$data = []
    )
    {
	    $this->_objectManager = $objectManagerInterface;
        $this->_addAfter = false;

        $this->_addButtonLabel = __('Add New Condition');
        parent::__construct($context, $data);

    }

    /**
     * prepare render
     */
    protected function _prepareToRender()
    {
        $this->_getConditionsRenderer = null;
        $this->_getAttributeRenderer = null;
        $this->_getValueRenderer = null;
        $this->addColumn('attribute',
	        array(
	            'label' => __('Attribute'),
                'style' => 'width:120px',
            )
        );
        $this->addColumn('conditions',
            array(
                'label' => __('Condition'),
                'style' => 'width:120px',
			)
        );
        $this->addColumn('cvalue',
            array(
                'label' => __('Value'),
                'style' => 'width:120px',
            )
        );
    }

    /**
     * render cell template
     *
     * @param string $columnName
     * @return string
     */
    protected function _renderCellTemplate($columnName)
    {
        $inputName  = $this->getElement()->getName() . '[#{_id}][' . $columnName . ']';
        if ($columnName=="attribute") {
            return $this->_getAttributeRenderer()
                ->setName($inputName)
                ->setTitle($columnName)
                ->setExtraParams('style="width:160px"')
                ->setOptions(
                    $this->getElement()->getValues()
                )
                ->toHtml();
        }elseif ($columnName == "conditions") {
            return $this->_getConditionsRenderer()
                ->setName($inputName)
                ->setTitle($columnName)
                ->setExtraParams('style="width:160px"')
                ->setOptions($this->_objectManager->create('Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Condition')->toOptionArray())
                ->toHtml();
        }elseif ($columnName == "cvalue") {
            return $this->_getValueRenderer()
                ->setName($inputName)
                ->setTitle($columnName)
                ->setExtraParams('style="width:160px"')
                ->setOptions($this->_objectManager->create('Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Value')->toOptionArray())
                ->toHtml();
        }
        return parent::renderCellTemplate($columnName);
    }

    /**
     * Assign extra parameters to row
     *
     */
    protected function _prepareArrayRow($row)
    {
        $row->setData(
            'option_extra_attr_' . $this->_getAttributeRenderer()->calcOptionHash($row->getData('attribute')),
            'selected="selected"'
        );
    }

    /**
     * get rendered for attribute field
     *
     * @return mixed
     */
    protected function _getAttributeRenderer()
    {
        if (!$this->_getAttributeRenderer) {
            $this->_getAttributeRenderer = $this->getLayout()
                ->createBlock('Dotdigitalgroup\Email\Block\Adminhtml\Config\Select')
                ->setIsRenderToJsTemplate(true);
        }
        return $this->_getAttributeRenderer;
    }

    /**
     * get renderer for conditions field
     *
     * @return mixed
     */
    protected function _getConditionsRenderer()
    {
        if (!$this->_getConditionsRenderer) {
            $this->_getConditionsRenderer = $this->getLayout()
                ->createBlock('Dotdigitalgroup\Email\Block\Adminhtml\Config\Select')
                ->setIsRenderToJsTemplate(true);
        }
        return $this->_getConditionsRenderer;
    }

    /**
     * get renderer for value field
     *
     * @return mixed
     */
    protected function _getValueRenderer()
    {
        if (!$this->_getValueRenderer) {
            $this->_getValueRenderer = $this->getLayout()
                ->createBlock('ddg_automation/adminhtml_config_select')
                ->setIsRenderToJsTemplate(true);
        }
        return $this->_getValueRenderer;
    }

    public function _toHtml()
    {
        $script =
            "<script type=\"text/javascript\">
                document.observe('dom:loaded', function() {
                    $$('tr#row_rule_condition tr td:first-child select').each(function(item) {
                        doUpdateWithValues(item);
                    });

                    $$('tr#row_rule_condition tr td:first-child select').each(function(item) {
                        Event.observe(item,'change', function(){
                            doUpdate(item);
                        });
                    });

                    $$('tr#row_rule_condition button.add').each(function(item) {
                         Event.observe(item,'click', function(){
                            $$('tr#row_rule_condition tr td:first-child select').each(function(item) {
                                 Event.observe(item,'change', function(){
                                    doUpdate(item);
                                });
                            });
                            $$('tr#row_rule_condition tr td select').each(function(item) {
                                Event.observe(item,'change', function(){
                                     if(item.readAttribute('title') == 'conditions'){
                                        doUpdateForCondition(item);
                                     }
                                });
                            });
                        });
                    });

                    function doUpdate(item){
                        var url = '". $this->getUrl('connector/rules/ajax')."';
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

                                $$('tr#row_rule_condition tr td select').each(function(item) {
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
                        var url = '". $this->getUrl('connector/rules/selected')."';
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

                                $$('tr#row_rule_condition tr td select').each(function(item) {
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
                        var url = '". $this->getUrl('connector/rules/value')."';
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
        return '<input type="hidden" id="'.$this->getElement()->getHtmlId().'"/>'.parent::_toHtml().$script;

    }

}
