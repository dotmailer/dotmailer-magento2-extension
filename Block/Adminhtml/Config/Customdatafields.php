<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config;

class Customdatafields  extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    /**
     * Customer attributes.
     *
     */
    protected $_attributeRenderer;

    /**
     * Customer datafields.
     */
    protected $_datafieldRenderer;


	protected $_objectManager;

	protected $datafieldsFactory;
	protected $_elementFactory;

	/**
	 * Construct.
	 */
	public function __construct(
		\Magento\Framework\Data\Form\Element\Factory $elementFactory,
		\Dotdigitalgroup\Email\Model\Config\Source\Datamapping\DatafieldsFactory $datafields,
		\Magento\Backend\Block\Template\Context $context,
		\Magento\Framework\ObjectManagerInterface $objectManagerInterface,
		$data = []
	)
	{
		$this->_elementFactory = $elementFactory;
		$this->datafieldsFactory = $datafields->create();
		$this->_objectManager = $objectManagerInterface;
		$this->_addAfter = false;

		$this->_addButtonLabel = __('Add New Attribute');
		parent::__construct($context, $data);

	}

    protected function _prepareToRender()
    {
        $this->_getDatafieldRenderer = null;
        $this->_getAttributeRenderer = null;
	    $this->addColumn('attribute',
	        array(
	            'label' => __('Attribute'),
                'style' => 'width:120px',
            )
        );
        $this->addColumn('datafield', array(
	        'label' => __('DataField'),
            'style' => 'width:120px',
			)
        );
    }

    public  function renderCellTemplate($columnName)
    {
	    if ($columnName == 'attribute' && isset($this->_columns[$columnName])) {

		    $options = $this->getElement()->getValues();
		    $element = $this->_elementFactory->create('select');
		    $element->setForm(
			    $this->getForm()
		    )->setName(
			    $this->_getCellInputElementName($columnName)
		    )->setHtmlId(
			    $this->_getCellInputElementId('<%- _id %>', $columnName)
		    )->setValues(
			    $options
		    );
		    return str_replace("\n", '', $element->getElementHtml());
	    }

	    if ($columnName == 'datafield' && isset($this->_columns[$columnName])) {

		    $options = $this->datafieldsFactory->toOptionArray();
		    $element = $this->_elementFactory->create('select');
		    $element->setForm(
			    $this->getForm()
		    )->setName(
			    $this->_getCellInputElementName($columnName)
		    )->setHtmlId(
			    $this->_getCellInputElementId('<%- _id %>', $columnName)
		    )->setValues(
			    $options
		    );
		    return str_replace("\n", '', $element->getElementHtml());
	    }

        return parent::renderCellTemplate($columnName);
    }

    /**
     * Assign extra parameters to row
     *
     */
    protected function _prepareArrayRow(\Magento\Framework\DataObject $row)
    {
	    $options = [];

	    $options['option_' . $this->_getAttributeRenderer()->calcOptionHash($row->getData('attribute'))]
            = 'selected="selected"';
	    $options['option_' . $this->_getDatafieldRenderer()->calcOptionHash($row->getData('datafield'))]
            = 'selected="selected"';

	    $row->setData('option_extra_attrs', $options);
    }
    protected function _getAttributeRenderer()
    {

	    $this->_attributeRenderer = $this->getLayout()->createBlock(
		    'Dotdigitalgroup\Email\Block\Adminhtml\Config\Select',
		    '',
		    ['data' => ['is_render_to_js_template' => true]]
	    );
	    return $this->_attributeRenderer;
    }

    protected function _getDatafieldRenderer()
    {
	    $this->_datafieldRenderer = $this->getLayout()->createBlock(
		    'Dotdigitalgroup\Email\Block\Adminhtml\Form\Datafield',
		    '',
		    ['data' => ['is_render_to_js_template' => true]]
	    );
        return $this->_datafieldRenderer;
    }

    public function _toHtml()
    {
        return '<input type="hidden" id="'.$this->getElement()->getHtmlId().'"/>'.parent::_toHtml();

    }

}
