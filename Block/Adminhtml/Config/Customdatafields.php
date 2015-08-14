<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Config_Customdatafields  extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    /**
     * Customer attribute
     *
     */
    protected $_getAttributeRenderer;

    /**
     * Datafields
     */
    protected $_getDatafieldRenderer;


    /**
	 * Construct.
	 */
    public function __construct()
    {
        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('adminhtml')->__('Add New Attribute');
        parent::__construct();

    }

    protected function _prepareToRender()
    {
        $this->_getDatafieldRenderer = null;
        $this->_getAttributeRenderer = null;
        $this->addColumn('attribute',
	        array(
	            'label' => Mage::helper('adminhtml')->__('Attribute'),
                'style' => 'width:120px',
            )
        );
        $this->addColumn('datafield', array(
	        'label' => Mage::helper('adminhtml')->__('DataField'),
            'style' => 'width:120px',
			)
        );
    }

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
        } elseif ($columnName == "datafield") {
            return $this->_getDatafieldRenderer()
                ->setName($inputName)
                ->setTitle($columnName)
                ->setExtraParams('style="width:160px"')
                ->setOptions(Mage::getModel('ddg_automation/adminhtml_source_datafields')->toOptionArray())
                ->toHtml();
        }
        return parent::_renderCellTemplate($columnName);
    }

    /**
     * Assign extra parameters to row
     *
     * @param Varien_Object $row
     */
    protected function _prepareArrayRow(Varien_Object $row)
    {

        $row->setData(
            'option_extra_attr_' . $this->_getAttributeRenderer()->calcOptionHash($row->getData('attribute')),
            'selected="selected"'
        );

        $row->setData(
            'option_extra_attr_' . $this->_getDatafieldRenderer()->calcOptionHash($row->getData('datafield')),
            'selected="selected"'
        );
    }
    protected function _getAttributeRenderer()
    {
        if (!$this->_getAttributeRenderer) {
            $this->_getAttributeRenderer = $this->getLayout()
                ->createBlock('ddg_automation/adminhtml_config_select')
                ->setIsRenderToJsTemplate(true);
        }
        return $this->_getAttributeRenderer;
    }

    protected function _getDatafieldRenderer()
    {
        if (!$this->_getDatafieldRenderer) {
            $this->_getDatafieldRenderer = $this->getLayout()
                ->createBlock('ddg_automation/adminhtml_config_select')
                ->setIsRenderToJsTemplate(true);
        }
        return $this->_getDatafieldRenderer;
    }

    public function _toHtml()
    {
        return '<input type="hidden" id="'.$this->getElement()->getHtmlId().'"/>'.parent::_toHtml();

    }

}
