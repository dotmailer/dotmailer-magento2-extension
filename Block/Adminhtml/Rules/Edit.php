<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Rules;

/**
 * Shopping cart rule edit form block.
 */
class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * @var \Magento\Framework\Registry
     */
    public $registry;

    /**
     * Initialize form
     * Add standard buttons
     * Add "Save and Continue" button.
     *
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Backend\Block\Widget\Context $context
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Backend\Block\Widget\Context $context
    ) {
        $this->registry    = $registry;
        $this->_objectId   = 'id';
        $this->_blockGroup = 'Dotdigitalgroup_Email';
        $this->_controller = 'adminhtml_rules';
        $data              = [];
        parent::__construct($context, $data);

        $this->addButton('save_and_continue_edit', [
            'class' => 'save',
            'label' => __('Save and Continue Edit'),
            'onclick' => 'editForm.submit($(\'edit_form\').action + \'back/edit/\')',
        ], 10);
    }

    /**
     * Getter for form header text.
     *
     * @return string
     */
    public function getHeaderText()
    {
        $rule = $this->registry->registry('current_ddg_rule');

        if ($rule->getId()) {
            return __('Edit Rule ' . $this->escapeHtml($rule->getName()));
        } else {
            return __('New Rule');
        }
    }
}
