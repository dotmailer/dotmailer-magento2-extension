<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Rules\Edit\Tab;

/**
 * Exclusion rules main tab block
 *
 * @api
 */
class Main extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var \Magento\Store\Model\System\Store
     */
    public $systemStore;

    /**
     * Main constructor.
     *
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        array $data = []
    ) {
        $this->systemStore = $systemStore;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare content for tab.
     *
     * @return string
     */
    public function getTabLabel()
    {
        return __('Rule Information');
    }

    /**
     * Prepare title for tab.
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('Rule Information');
    }

    /**
     * Returns status flag about this tab can be showed or not.
     *
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
            ['legend' => __('Rule Information')]
        );

        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', [
                'name' => 'id',
            ]);
        }

        $fieldset->addField('name', 'text', [
            'name' => 'name',
            'label' => __('Rule Name'),
            'title' => __('Rule Name'),
            'required' => true,
        ]);

        $fieldset->addField('type', 'select', [
            'label' => __('Rule Type'),
            'title' => __('Rule Type'),
            'name' => 'type',
            'required' => true,
            'options' => [
                \Dotdigitalgroup\Email\Model\Rules::ABANDONED => 'Abandoned Cart Exclusion Rule',
                \Dotdigitalgroup\Email\Model\Rules::REVIEW => 'Review Email Exclusion Rule',
            ],
        ]);

        $fieldset->addField('status', 'select', [
            'label' => __('Status'),
            'title' => __('Status'),
            'name' => 'status',
            'required' => true,
            'options' => [
                '1' => __('Active'),
                '0' => __('Inactive'),
            ],
        ]);

        if (!$model->getId()) {
            $model->setData('status', '0');
        }

        if ($this->_storeManager->isSingleStoreMode()) {
            $websiteId = $this->_storeManager->getStore(true)->getWebsiteId();
            $fieldset->addField(
                'website_ids',
                'hidden',
                ['name' => 'website_ids[]', 'value' => $websiteId]
            );
            $model->setWebsiteIds($websiteId);
        } else {
            $field = $fieldset->addField(
                'website_ids',
                'multiselect',
                [
                    'name' => 'website_ids[]',
                    'label' => __('Websites'),
                    'title' => __('Websites'),
                    'required' => true,
                    'values' => $this->systemStore->getWebsiteValuesForForm(),
                ]
            );
            $renderer = $this->getLayout()->createBlock(
                \Magento\Backend\Block\Store\Switcher\Form\Renderer\Fieldset\Element::class
            );
            $field->setRenderer($renderer);
        }

        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
