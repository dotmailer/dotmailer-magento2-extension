<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Rules\Edit\Tab;

/**
 * Exclusion rules conditions tab block
 *
 * @api
 */
class Conditions extends \Magento\Backend\Block\Widget\Form\Generic implements
    \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Type
     */
    public $options;

    /**
     * Conditions constructor.
     *
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Type $options
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Type $options,
        array $data = []
    ) {
        $this->options = $options;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare content for tab.
     *
     * @return string
     */
    public function getTabLabel()
    {
        return __('Conditions');
    }

    /**
     * Prepare title for tab.
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('Conditions');
    }

    /**
     * @return true
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Returns status flag about this tab hidden or not.
     *
     * @return true
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * @return \Magento\Backend\Block\Widget\Form\Generic
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('current_ddg_rule');
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('rule_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Exclusion Rule Conditions')]
        );

        $fieldset->addField('combination', 'select', [
            'label' => __('Conditions Combination Match'),
            'title' => __('Conditions Combination Match'),
            'name' => 'combination',
            'required' => true,
            'options' => [
                '1' => __('ALL'),
                '2' => __('ANY'),
            ],
            'after_element_html' => '<small>Choose ANY if using multi line conditions 
for same attribute. If multi line conditions for same attribute is used and ALL is chosen 
then multiple lines for same attribute will be ignored.</small>',
        ]);

        $field = $fieldset->addField('condition', 'select', [
            'name' => 'condition',
            'label' => __('Condition'),
            'title' => __('Condition'),
            'required' => true,
            'options' => $this->options->toOptionArray(),
        ]);
        $renderer = $this->getLayout()
            ->createBlock(\Dotdigitalgroup\Email\Block\Adminhtml\Config\Rules\Customdatafields::class);
        $field->setRenderer($renderer);

        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
