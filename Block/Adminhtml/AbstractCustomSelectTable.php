<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\DataObject;
use Dotdigitalgroup\Email\Block\Adminhtml\Config\Select;
use Magento\Framework\View\Element\BlockInterface;

abstract class AbstractCustomSelectTable extends AbstractFieldArray
{
    /**
     * Override default 'Add after' button
     *
     * @var bool
     */
    protected $_addAfter = false;

    /**
     * Overridable button label
     *
     * @var string
     */
    protected $buttonLabel = 'Add';

    /**
     * @var BlockInterface
     */
    private $fieldRenderer;

    /**
     * @var Factory
     */
    private $elementFactory;

    /**
     * @var array
     */
    private $columnLayout;

    /**
     * @param Context $context
     * @param Factory $elementFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        Factory $elementFactory,
        array $data = []
    ) {
        $this->elementFactory = $elementFactory;
        parent::__construct($context, $data);
    }

    /**
     * @param string $columnName
     * @return mixed|string
     * @throws \Exception
     */
    public function renderCellTemplate($columnName)
    {
        if (!array_key_exists($columnName, $this->getColumnLayout()) || !isset($this->_columns[$columnName])) {
            return parent::renderCellTemplate($columnName);
        }

        $options = $this->getColumnLayout()[$columnName]['options'];
        $element = $this->elementFactory->create('select')
            ->setForm($this->getForm())
            ->setName($this->_getCellInputElementName($columnName))
            ->setHtmlId($this->_getCellInputElementId('<%- _id %>', $columnName))
            ->setValues($options);

        return str_replace(PHP_EOL, '', $element->getElementHtml());
    }

    /**
     * @param DataObject $row
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function _prepareArrayRow(DataObject $row)
    {
        $options = [];
        foreach (array_keys($this->getColumnLayout()) as $field) {
            $options['option_' . $this->getFieldRenderer()->calcOptionHash($row->getData($field))] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function _toHtml()
    {
        return parent::_toHtml() . <<<EOT
<input type="hidden" id="{$this->getElement()->getHtmlId()}">
EOT;
    }

    /**
     * Render the block
     */
    protected function _prepareToRender()
    {
        $this->_addButtonLabel = __($this->buttonLabel);

        foreach ($this->getColumnLayout() as $field => $columnParams) {
            $this->addColumn($field, [
                'label' => __($columnParams['label']),
                'style' => $columnParams['style'] ?? null,
            ]);
        }
    }

    /**
     * Get the column layout for this block, in the format:
     *
     * 'fieldId' => [
     *    'label' => '',
     *    'style' => '',
     *    'options' => [],
     * ]
     *
     * @return array
     */
    abstract protected function columnLayout(): array;

    /**
     * Get the generated column layout
     *
     * @return array
     */
    private function getColumnLayout(): array
    {
        if ($this->columnLayout) {
            return $this->columnLayout;
        }

        return $this->columnLayout = $this->columnLayout();
    }

    /**
     * @return BlockInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getFieldRenderer()
    {
        if ($this->fieldRenderer) {
            return $this->fieldRenderer;
        }

        return $this->fieldRenderer = $this->getLayout()->createBlock(Select::class, '', [
            'data' => ['is_render_to_js_template' => true],
        ]);
    }
}
