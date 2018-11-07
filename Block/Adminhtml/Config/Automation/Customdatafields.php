<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Automation;

class Customdatafields extends
 \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    /**
     * @var \Dotdigitalgroup\Email\Block\Adminhtml\Config\Select
     */
    public $statusRenderer;

    /**
     * @var \Dotdigitalgroup\Email\Block\Adminhtml\Config\Select
     */
    public $automationRenderer;

    /**
     * @var \Dotdigitalgroup\Email\Model\Config\Source\Automation\ProgramFactory
     */
    public $programFactory;

    /**
     * @var \Magento\Framework\Data\Form\Element\Factory
     */
    public $elementFactory;

    /**
     * Customdatafields constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Data\Form\Element\Factory $elementFactory
     * @param \Dotdigitalgroup\Email\Model\Config\Source\Automation\ProgramFactory $programFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Data\Form\Element\Factory $elementFactory,
        \Dotdigitalgroup\Email\Model\Config\Source\Automation\ProgramFactory $programFactory,
        array $data = []
    ) {
        $this->elementFactory = $elementFactory;
        $this->programFactory = $programFactory->create();
        parent::__construct($context, $data);
    }

    /**
     * @return null
     */
    public function _prepareToRender()
    {
        $this->addColumn(
            'status',
            [
                'label' => __('Order Status')
            ]
        );
        $this->addColumn(
            'automation',
            [
                'label' => __('Automation Program')
            ]
        );
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add New Enrolment');
    }

    /**
     * @param string $columnName
     *
     * @return string
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
     *
     * @return void
     */
    public function _prepareArrayRow(\Magento\Framework\DataObject $row)
    {
        $optionExtraAttr = [];
        $optionExtraAttr['option_' . $this->getStatusRenderer()
            ->calcOptionHash($row->getData('status'))]
                         = 'selected="selected"';
        $optionExtraAttr['option_' . $this->getAutomationRenderer()
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
    public function getStatusRenderer()
    {
        $this->statusRenderer = $this->getLayout()->createBlock(
            \Dotdigitalgroup\Email\Block\Adminhtml\Config\Select::class,
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
    public function getAutomationRenderer()
    {
        $this->automationRenderer = $this->getLayout()->createBlock(
            \Dotdigitalgroup\Email\Block\Adminhtml\Config\Select::class,
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
    public function _toHtml()
    {
        return '<input type="hidden" id="' . $this->getElement()->getHtmlId()
        . '"/>' . parent::_toHtml();
    }
}
