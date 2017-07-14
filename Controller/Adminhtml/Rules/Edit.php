<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Rules;

class Edit extends \Magento\Backend\App\AbstractAction
{
    /**
     * @var \Magento\Framework\Registry
     */
    public $registry;
    /**
     * @var \Dotdigitalgroup\Email\Model\Rules
     */
    public $rules;

    /**
     * Edit constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Dotdigitalgroup\Email\Model\Rules $rules
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Dotdigitalgroup\Email\Model\Rules $rules,
        \Magento\Framework\Registry $registry
    ) {
        parent::__construct($context);
        $this->rules = $rules;
        $this->registry = $registry;
    }

    /**
     * Check the permission to run it.
     *
     * @return bool
     */
    public function _isAllowed() //@codingStandardsIgnoreLine
    {
        return $this->_authorization->isAllowed('Dotdigitalgroup_Email::exclusion_rules');
    }

    /**
     * Execute method.
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');

        $this->_view->loadLayout();
        $this->_setActiveMenu(
            'Magento_CatalogRule::exclusion_rules'
        )->_addBreadcrumb(
            $id ? __('Edit Rule')
                : __('New Rule'),
            $id ? __('Edit Rule')
                : __('New Rule')
        );

        $emailRules = $this->rules;
        if ($id) {
            $emailRules = $emailRules->load($id);

            if (!$emailRules->getId()) {
                $this->messageManager->addErrorMessage(__('This rule no longer exists.'));
                $this->_redirect('*/*');
            }
        }
        $this->_view->getPage()->getConfig()->getTitle()->prepend(
            $emailRules->getId() ? $emailRules->getName() : __('New Rule')
        );

        // set entered data if was error when we do save
        $data = $this->_session->getPageData(true);
        if (!empty($data)) {
            $this->rules->addData($data);
        }

        $this->registry->register('current_ddg_rule', $emailRules);

        $this->_view->getLayout()->getBlock('dotdigitalgroup.email.rules.edit')
            ->setData('action', $this->getUrl('*/*/save'));
        $this->_view->renderLayout();
    }
}
