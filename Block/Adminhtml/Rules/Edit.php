<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Rules;

/**
 * Exclusion rules edit block
 *
 * @api
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
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry
    ) {
        $this->registry    = $registry;
        $this->_objectId   = 'id';
        $this->_blockGroup = 'Dotdigitalgroup_Email';
        $this->_controller = 'adminhtml_rules';
        $data              = [];
        parent::__construct($context, $data);
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
            return __('Edit Rule %1', $this->escapeHtml($rule->getName()));
        } else {
            return __('New Rule');
        }
    }
}
