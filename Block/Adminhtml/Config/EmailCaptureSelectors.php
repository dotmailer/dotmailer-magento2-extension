<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\View\Element\Html\Select;
use Magento\Backend\Block\Template\Context;
use Dotdigitalgroup\Email\Model\Config\Source\Tracking\EmailCaptureLayouts;

/**
 * Email capture selectors admin configuration field
 */
class EmailCaptureSelectors extends AbstractFieldArray
{
    /**
     * @var Select
     */
    private $layoutRenderer;

    /**
     * @var EmailCaptureLayouts
     */
    private $layoutsSource;

    /**
     * @param Context $context
     * @param EmailCaptureLayouts $layoutsSource
     * @param array $data
     */
    public function __construct(
        Context             $context,
        EmailCaptureLayouts $layoutsSource,
        array               $data = []
    ) {
        $this->layoutsSource = $layoutsSource;
        parent::__construct($context, $data);
    }

    /**
     * Get the grid and scripts contents with custom wrapper
     *
     * @param AbstractElement $element
     * @return string
     * @throws ValidatorException
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $this->setElement($element);
        $this->setData('field_label', $element->getLabel());
        $this->setData('field_html', $this->_toHtml());
        $this->_template ='Dotdigitalgroup_Email::system/config/email-capture-selectors.phtml';

        return $this->fetchView($this->getTemplateFile());
    }

    /**
     * Prepare to render
     *
     * @return void
     * @throws LocalizedException
     */
    protected function _prepareToRender(): void
    {
        $this->addColumn(
            'layout',
            [
                'label' => __('Layout'),
                'class' => 'required-entry',
                'renderer' => $this->getLayoutRenderer(),
                'style' => 'min-width:30rem !important'
            ]
        );
        $this->addColumn(
            'selectors',
            [
                'label' => __('CSS Selectors (comma separated)'),
                'class' => 'required-entry',
                'style' => 'min-width:50rem !important'
            ]
        );
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Selector');
    }

    /**
     * Prepare existing row data object
     *
     * @param DataObject $row
     * @throws LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];
        $layout = $row->getLayout();

        if ($layout !== null && $layout !== '') {
            $options['option_' . $this->getLayoutRenderer()->calcOptionHash($layout)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);

        if (!$row->getData('selectors')) {
            $row->setData('selectors', '');
        }

        if (!$row->getData('layout')) {
            $row->setData('layout', '');
        }
    }

    /**
     * Get layout renderer
     *
     * @return Select
     * @throws LocalizedException
     */
    private function getLayoutRenderer(): Select
    {
        if ($this->layoutRenderer === null) {
            $this->layoutRenderer = $this->getLayout()->createBlock(
                Select::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }

        /** @var Select $block */
        $block = $this->layoutRenderer;
        $block->setClass('layout_select');
        $block->setName($this->getElement()->getName() . '[<%- _id %>][layout]');
        $block->setId($this->getElement()->getHtmlId() . '_layout_select');

        $options = $this->layoutsSource->toArray();
        if (empty($options)) {
            $optionArray = $this->layoutsSource->toOptionArray();
            $options = [];
            foreach ($optionArray as $option) {
                $options[$option['value']] = $option['label'];
            }
        }

        $block->setOptions($options);
        return $block;
    }
}
