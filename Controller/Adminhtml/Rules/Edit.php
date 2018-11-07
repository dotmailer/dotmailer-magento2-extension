<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Rules;

class Edit extends \Magento\Backend\App\AbstractAction
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::exclusion_rules';

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @var \Dotdigitalgroup\Email\Model\Rules
     */
    private $rules;

    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Rules
     */
    private $rulesResource;

    /**
     * Edit constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Rules $rulesResource
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Dotdigitalgroup\Email\Model\Rules $rules
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Escaper $escaper
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Rules $rulesResource,
        \Magento\Backend\App\Action\Context $context,
        \Dotdigitalgroup\Email\Model\Rules $rules,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Escaper $escaper
    ) {
        parent::__construct($context);
        $this->rules = $rules;
        $this->registry = $registry;
        $this->escaper = $escaper;
        $this->rulesResource = $rulesResource;
    }

    /**
     * Execute method.
     *
     * @return void
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');

        $this->_view->loadLayout();
        $this->_setActiveMenu(
            'Magento_CatalogRule::exclusion_rules'
        )->_addBreadcrumb(
            $id
            ? __('Edit Rule')
            : __('New Rule'),
            $id
            ? __('Edit Rule')
            : __('New Rule')
        );

        $emailRules = $this->rules;
        $this->checkRuleExistAndLoad($id, $emailRules);

        $this->registry->unregister('current_ddg_rule'); // additional measure
        $this->registry->register('current_ddg_rule', $emailRules);

        $this->_view->getLayout()->getBlock('dotdigitalgroup.email.rules.edit')
            ->setData('action', $this->getUrl('*/*/save'));
        $this->_view->renderLayout();
    }

    /**
     * Check rule exist
     *
     * @param int|null $id
     * @param \Dotdigitalgroup\Email\Model\Rules $emailRules
     * @return void
     */
    private function checkRuleExistAndLoad($id, $emailRules)
    {
        if ($id) {
            $this->rulesResource->load($emailRules, $id);

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
    }
}
