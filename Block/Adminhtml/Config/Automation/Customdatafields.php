<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Automation;

class Customdatafields  extends \Magento\Config\Block\System\Config\Form\Field
{
    protected $_getStatusRenderer;
    protected $_getAutomationRenderer;

    /**
	 * Construct.
	 */
    public function __construct(
	    \Magento\Backend\Block\Template\Context $context,
		$data = []
    )
    {
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add New Enrolment');
        parent::__construct($context, $data);
    }

    protected function _prepareToRender()
    {
        $this->_getStatusRenderer = null;
        $this->_getAutomationRenderer = null;
        $this->addColumn('status',
	        array(
	            'label' => __('Order Status'),
                'style' => 'width:120px',
            )
        );
        $this->addColumn('automation', array(
	        'label' => __('Automation Programme'),
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
     */
    protected function _prepareArrayRow( $row)
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
                ->createBlock('Dotdigitalgroup\Email\Block\Adminhtml\Config\Select')
                ->setIsRenderToJsTemplate(true);
        }
        return $this->_getStatusRenderer;
    }

    protected function _getAutomationRenderer()
    {
        if (!$this->_getAutomationRenderer) {
            $this->_getAutomationRenderer = $this->getLayout()
                ->createBlock('Dotdigitalgroup\Email\Block\Adminhtml\Config\Select')
                ->setIsRenderToJsTemplate(true);
        }
        return $this->_getAutomationRenderer;
    }

    public function _toHtml()
    {
        return '<input type="hidden" id="'.$this->getElement()->getHtmlId().'"/>'.parent::_toHtml();

    }

}
