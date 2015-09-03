<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Automation;

class Customdatafields  extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    protected $_statusRenderer;
    protected $_automationRenderer;
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

        $this->_addAfter = false;
	    $this->_objectManager = $objectManagerInterface;
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
	        'label' => __('Automation Program'),
            'style' => 'width:120px',
			)
        );
    }

    public function renderCellTemplate($columnName)
    {
        $inputName  = $this->getElement()->getName() . '[#{_id}][' . $columnName . ']';
        if ($columnName=="status") {
            return $this->_getStatusRenderer()
                ->setName($inputName)
                ->setTitle($columnName)
                ->setExtraParams('style="width:160px"')
                ->setOptions( $this->getElement()->getValues() )
                ->toHtml();
        } elseif ($columnName == "automation") {
            return $this->_getAutomationRenderer()
                ->setName($inputName)
                ->setTitle($columnName)
                ->setExtraParams('style="width:160px"')
                ->setOptions($this->_objectManager->create('Dotdigitalgroup\Email\Model\Config\Source\Datamapping\Datafields')->toOptionArray())
                ->toHtml();
        }
        return parent::renderCellTemplate($columnName);
    }

    /**
     * Assign extra parameters to row
     *
     */
    protected function _prepareArrayRow(\Magento\Framework\DataObject $row)
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
	    $this->_statusRenderer = $this->getLayout()->createBlock(
		    'Dotdigitalgroup\Email\Block\Adminhtml\Config\Select',
		    '',
		    ['data' => ['is_render_to_js_template' => true]]
	    );

        return $this->_statusRenderer;
    }

    protected function _getAutomationRenderer()
    {
	    $this->_automationRenderer = $this->getLayout()->createBlock(
		    'Dotdigitalgroup\Email\Block\Adminhtml\Config\Select',
		    '',
		    ['data' => ['is_render_to_js_template' => true]]
	    );

        return $this->_automationRenderer;
    }

	public function _toHtml()
	{
		return '<input type="hidden" id="'.$this->getElement()->getHtmlId().'"/>'.parent::_toHtml();

	}
}
