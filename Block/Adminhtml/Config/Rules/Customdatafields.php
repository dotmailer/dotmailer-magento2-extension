<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Rules;

use Dotdigitalgroup\Email\Block\Adminhtml\Config\Select;
use Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Condition;
use Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Value;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

/**
 * Class Customdatafields
 * This custom block sets default data for the three columns in the 'Condition' fieldset.
 * These are then overridden via AJAX, either on load (loading saved data)
 * or when <select> elements are manipulated.
 */
class Customdatafields extends AbstractFieldArray
{
    /**
     * @var Select
     */
    protected $getAttributeRenderer;

    /**
     * @var Select
     */
    protected $getConditionsRenderer;

    /**
     * @var Select
     */
    private $getValueRenderer;

    /**
     * @var Condition
     */
    private $condition;

    /**
     * @var Value
     */
    private $value;

    /**
     * @var string
     */
    private $className;

    /**
     * Customdatafields constructor.
     *
     * @param Context $context
     * @param Condition $condition
     * @param Value $value
     * @param array $data
     */
    public function __construct(
        Context $context,
        Condition $condition,
        Value $value,
        $data = []
    ) {
        $this->condition = $condition;
        $this->value = $value;
        $this->_addAfter = false;
        $this->className = 'ddg-rules-conditions';

        $this->_addButtonLabel = __('Add New Condition');
        parent::__construct($context, $data);
    }

    /**
     * @return void
     */
    protected function _prepareToRender()
    {
        $this->getConditionsRenderer = null;
        $this->getAttributeRenderer = null;
        $this->getValueRenderer = null;
        $this->addColumn(
            'attribute',
            [
                'label' => __('Attribute'),
                'class' => $this->className,
            ]
        );
        $this->addColumn(
            'conditions',
            [
                'label' => __('Condition'),
                'class' => $this->className,
            ]
        );
        $this->addColumn(
            'cvalue',
            [
                'label' => __('Value'),
                'class' => $this->className,
            ]
        );
    }

    /**
     * Render cell template.
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
                ->setClass($this->className)
                ->setOptions(
                    $this->getElement()->getValues()
                )
                ->toHtml();
        } elseif ($columnName == 'conditions') {
            return $this->_getConditionsRenderer()
                ->setName($this->_getCellInputElementName($columnName))
                ->setTitle($columnName)
                ->setClass($this->className)
                ->setOptions(
                    $this->condition->toOptionArray()
                )
                ->toHtml();
        } elseif ($columnName == 'cvalue') {
            return $this->_getValueRenderer()
                ->setName($this->_getCellInputElementName($columnName))
                ->setTitle($columnName)
                ->setClass($this->className)
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
    protected function _prepareArrayRow(\Magento\Framework\DataObject $row)
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
     * @return Select
     */
    private function _getAttributeRenderer()
    {
        if (!$this->getAttributeRenderer) {
            $this->getAttributeRenderer = $this->getLayout()
                ->createBlock(
                    Select::class,
                    '',
                    ['data' => ['is_render_to_js_template' => true]]
                );
        }

        return $this->getAttributeRenderer;
    }

    /**
     * Get renderer for conditions field.
     *
     * @return Select
     */
    private function _getConditionsRenderer()
    {
        if (!$this->getConditionsRenderer) {
            $this->getConditionsRenderer = $this->getLayout()
                ->createBlock(
                    Select::class,
                    '',
                    ['data' => ['is_render_to_js_template' => true]]
                );
        }

        return $this->getConditionsRenderer;
    }

    /**
     * Get renderer for value field.
     *
     * @return Select
     */
    private function _getValueRenderer()
    {
        if (!$this->getValueRenderer) {
            $this->getValueRenderer = $this->getLayout()
                ->createBlock(
                    Select::class,
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
    protected function _toHtml()
    {
        return '<input type="hidden" id="' . $this->getElement()->getHtmlId()
        . '"/>' . parent::_toHtml();
    }
}
