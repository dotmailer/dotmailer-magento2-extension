<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Contact\Edit;

/**
 * Adminhtml blog post edit form
 */
class Form extends \Magento\Backend\Block\Widget\Form\Generic
{

    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    /**
     * Form constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry             $registry
     * @param \Magento\Framework\Data\FormFactory     $formFactory
     * @param \Magento\Store\Model\System\Store       $systemStore
     * @param array                                   $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        array $data = []
    ) {
        $this->_systemStore = $systemStore;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Init form
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('post_contact');
        $this->setTitle(__('Post Contact'));
    }

    /**
     * Prepare form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('email_contact');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id'     => 'edit_form',
                    'action' => $this->getData('action'),
                    'method' => 'post'
                ]
            ]
        );

        $form->setHtmlIdPrefix('post_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('General Information'), 'class' => 'fieldset-wide']
        );

        if ($model->getPostId()) {
            $fieldset->addField('post_id', 'hidden', ['name' => 'post_id']);
        }

        $fieldset->addField(
            'title',
            'text',
            [
                'name'     => 'title',
                'label'    => __('Post Title'),
                'title'    => __('Contact Title'),
                'required' => true
            ]
        );

        $fieldset->addField(
            'url_key',
            'text',
            [
                'name'     => 'url_key',
                'label'    => __('URL Key'),
                'title'    => __('URL Key'),
                'required' => true,
                'class'    => 'validate-xml-identifier'
            ]
        );

        $fieldset->addField(
            'is_active',
            'select',
            [
                'label'    => __('Status'),
                'title'    => __('Status'),
                'name'     => 'is_active',
                'required' => true,
                'options'  => ['1' => __('Enabled'), '0' => __('Disabled')]
            ]
        );
        if ( ! $model->getId()) {
            $model->setData('is_active', '1');
        }

        $fieldset->addField(
            'content',
            'editor',
            [
                'name'     => 'content',
                'label'    => __('Content'),
                'title'    => __('Content'),
                'style'    => 'height:36em',
                'required' => true
            ]
        );

        $form->setValues($model->getData());
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}