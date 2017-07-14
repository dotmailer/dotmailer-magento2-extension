<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Automation;

/**
 * Class Customdatafields
 * @package Dotdigitalgroup\Email\Block\Adminhtml\Config\Automation
 */
class Customdatafields extends
 \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    public $statusRenderer;

    public $automationRenderer;

    public $programFactory;

    public $elementFactory;

    /**
     * Customdatafields constructor.
     *
     * @param \Magento\Framework\Data\Form\Element\Factory                         $elementFactory
     * @param \Dotdigitalgroup\Email\Model\Config\Source\Automation\ProgramFactory $programFactory
     * @param \Magento\Backend\Block\Template\Context                              $context
     * @param array                                                                $data
     */
    public function __construct(
        \Magento\Framework\Data\Form\Element\Factory $elementFactory,
        \Dotdigitalgroup\Email\Model\Config\Source\Automation\ProgramFactory $programFactory,
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        $this->elementFactory = $elementFactory;
        $this->programFactory = $programFactory->create();
        parent::__construct($context, $data);
    }

    public function _prepareToRender() //@codingStandardsIgnoreLine
    {
        $this->_getStatusRenderer = null;
        $this->_getAutomationRenderer = null;
        $this->addColumn(
            'status',
            [
                'label' => __('Order Status'),
                'style' => 'width:120px',
            ]
        );
        $this->addColumn(
            'automation',
            [
                'label' => __('Automation Program'),
                'style' => 'width:120px',
            ]
        );
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add New Enrolment');
    }

    /**
     * @param string $columnName
     *
     * @return mixed|string
     *
     * @throws \Exception
     */
    public function renderCellTemplate($columnName)
    {
        if ($columnName == 'status' && isset($this->_columns[$columnName])) {
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
        if ($columnName == 'automation'
            && isset($this->_columns[$columnName])
        ) {
            $options = $this->programFactory->toOptionArray();
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
        $optionExtraAttr = [];
        $optionExtraAttr['option_' . $this->_getStatusRenderer()
            ->calcOptionHash($row->getData('status'))]
                         = 'selected="selected"';
        $optionExtraAttr['option_' . $this->_getAutomationRenderer()
            ->calcOptionHash($row->getData('automation'))]
                         = 'selected="selected"';
        $row->setData(
            'option_extra_attrs',
            $optionExtraAttr
        );
    }

    /**
     * @return \Magento\Framework\View\Element\BlockInterface
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function _getStatusRenderer() //@codingStandardsIgnoreLine
    {
        $this->statusRenderer = $this->getLayout()->createBlock(
            'Dotdigitalgroup\Email\Block\Adminhtml\Config\Select',
            '',
            ['data' => ['is_render_to_js_template' => true]]
        );

        return $this->statusRenderer;
    }

    /**
     * @return \Magento\Framework\View\Element\BlockInterface
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function _getAutomationRenderer() //@codingStandardsIgnoreLine
    {
        $this->automationRenderer = $this->getLayout()->createBlock(
            'Dotdigitalgroup\Email\Block\Adminhtml\Config\Select',
            '',
            ['data' => ['is_render_to_js_template' => true]]
        );

        return $this->automationRenderer;
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
