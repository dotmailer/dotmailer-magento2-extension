<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config;

/**
 * Class Customdatafields
 * @package Dotdigitalgroup\Email\Block\Adminhtml\Config
 */
class Customdatafields extends
 \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    /**
     * Customer attributes.
     *
     * @var object
     */
    public $attributeRenderer;

    /**
     * Customer datafields.
     *
     * @var object
     */
    public $datafieldRenderer;

    /**
     * @var \Dotdigitalgroup\Email\Model\Config\Source\Datamapping\Datafields
     */
    public $datafieldsFactory;

    /**
     * @var \Magento\Framework\Data\Form\Element\Factory
     */
    public $elementFactory;

    /**
     * @var
     */
    public $getDatafieldRenderer;

    /**
     * @var
     */
    public $getAttributeRenderer;

    /**
     * Customdatafields constructor.
     *
     * @param \Magento\Framework\Data\Form\Element\Factory                             $elementFactory
     * @param \Dotdigitalgroup\Email\Model\Config\Source\Datamapping\DatafieldsFactory $datafields
     * @param \Magento\Backend\Block\Template\Context                                  $context
     * @param array                                                                    $data
     */
    public function __construct(
        \Magento\Framework\Data\Form\Element\Factory $elementFactory,
        \Dotdigitalgroup\Email\Model\Config\Source\Datamapping\DatafieldsFactory $datafields,
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        $this->elementFactory    = $elementFactory;
        $this->datafieldsFactory = $datafields->create();
        $this->_addAfter         = false;

        $this->_addButtonLabel = __('Add New Attribute');
        parent::__construct($context, $data);
    }

    public function _prepareToRender() //@codingStandardsIgnoreLine
    {
        $this->getDatafieldRenderer = null;
        $this->getAttributeRenderer = null;
        $this->addColumn(
            'attribute',
            [
                'label' => __('Attribute'),
                'style' => 'width:120px',
            ]
        );
        $this->addColumn(
            'datafield',
            [
                'label' => __('DataField'),
                'style' => 'width:120px',
            ]
        );
    }

    /**
     * @param string $columnName
     *
     * @return mixed|string
     */
    public function renderCellTemplate($columnName)
    {
        if ($columnName == 'attribute' && isset($this->_columns[$columnName])) {
            $options = $this->getElement()->getValues();
            $element = $this->elementFactory->create('select');
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
            $element = $this->elementFactory->create('select');
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
     * @param \Magento\Framework\DataObject $row
     */
    public function _prepareArrayRow(\Magento\Framework\DataObject $row) //@codingStandardsIgnoreLine
    {
        $options = [];

        $options['option_' . $this->_getAttributeRenderer()->calcOptionHash(
            $row->getData('attribute')
        )]
            = 'selected="selected"';
        $options['option_' . $this->_getDatafieldRenderer()->calcOptionHash(
            $row->getData('datafield')
        )]
            = 'selected="selected"';

        $row->setData('option_extra_attrs', $options);
    }

    /**
     * @return \Magento\Framework\View\Element\BlockInterface|object
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function _getAttributeRenderer() //@codingStandardsIgnoreLine
    {
        $this->attributeRenderer = $this->getLayout()->createBlock(
            'Dotdigitalgroup\Email\Block\Adminhtml\Config\Select',
            '',
            ['data' => ['is_render_to_js_template' => true]]
        );

        return $this->attributeRenderer;
    }

    /**
     * @return \Magento\Framework\View\Element\BlockInterface|object
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function _getDatafieldRenderer() //@codingStandardsIgnoreLine
    {
        $this->datafieldRenderer = $this->getLayout()->createBlock(
            'Dotdigitalgroup\Email\Block\Adminhtml\Config\Select',
            '',
            ['data' => ['is_render_to_js_template' => true]]
        );

        return $this->datafieldRenderer;
    }

    /**
     * @return string
     *
     * @throws \Exception
     */
    public function _toHtml() //@codingStandardsIgnoreLine
    {
        return '<input type="hidden" id="' . $this->getElement()->getHtmlId()
        . '"/>' . parent::_toHtml();
    }
}
