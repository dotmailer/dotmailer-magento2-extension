<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Config_Automation_Customdatafields  extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    protected $_getStatusRenderer;
    protected $_getAutomationRenderer;


    /**
	 * Construct.
	 */
    public function __construct()
    {
        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('adminhtml')->__('Add New Enrolment');
        parent::__construct();

    }

    protected function _prepareToRender()
    {
        $this->_getStatusRenderer = null;
        $this->_getAutomationRenderer = null;
        $this->addColumn('status',
	        array(
	            'label' => Mage::helper('adminhtml')->__('Order Status'),
                'style' => 'width:120px',
            )
        );
        $this->addColumn('automation', array(
	        'label' => Mage::helper('adminhtml')->__('Automation Programme'),
            'style' => 'width:120px',
			)
        );
    }

    protected function _renderCellTemplate($columnName)
    {
        $inputName  = $this->getElement()->getName() . '[#{_id}][' . $columnName . ']';
        if ($columnName=="status") {
            return $this->_getStatusRenderer()
                ->setName($inputName)
                ->setTitle($columnName)
                ->setExtraParams('style="width:160px"')
                ->setOptions(
                    $this->getElement()->getValues()
                )
                ->toHtml();
        } elseif ($columnName == "automation") {
            return $this->_getAutomationRenderer()
                ->setName($inputName)
                ->setTitle($columnName)
                ->setExtraParams('style="width:160px"')
                ->setOptions(Mage::getModel('ddg_automation/adminhtml_source_automation_programme')->toOptionArray())
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
            'option_extra_attr_' . $this->_getStatusRenderer()->calcOptionHash($row->getData('status')),
            'selected="selected"'
        );

        $row->setData(
            'option_extra_attr_' . $this->_getAutomationRenderer()->calcOptionHash($row->getData('automation')),
            'selected="selected"'
        );
    }
    protected function _getStatusRenderer()
    {
        if (!$this->_getStatusRenderer) {
            $this->_getStatusRenderer = $this->getLayout()
                ->createBlock('ddg_automation/adminhtml_config_select')
                ->setIsRenderToJsTemplate(true);
        }
        return $this->_getStatusRenderer;
    }

    protected function _getAutomationRenderer()
    {
        if (!$this->_getAutomationRenderer) {
            $this->_getAutomationRenderer = $this->getLayout()
                ->createBlock('ddg_automation/adminhtml_config_select')
                ->setIsRenderToJsTemplate(true);
        }
        return $this->_getAutomationRenderer;
    }

    public function _toHtml()
    {
        return '<input type="hidden" id="'.$this->getElement()->getHtmlId().'"/>'.parent::_toHtml();

    }

}
