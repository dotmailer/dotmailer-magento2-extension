<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Rules;

use Dotdigitalgroup\Email\Model\ResourceModel\Rules;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Registry;

class Edit extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::exclusion_rules';

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @var \Dotdigitalgroup\Email\Model\Rules
     */
    private $rules;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Rules
     */
    private $rulesResource;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * Edit constructor.
     *
     * @param Rules $rulesResource
     * @param Context $context
     * @param \Dotdigitalgroup\Email\Model\Rules $rules
     * @param Registry $registry
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Rules $rulesResource,
        \Magento\Backend\App\Action\Context $context,
        \Dotdigitalgroup\Email\Model\Rules $rules,
        \Magento\Framework\Registry $registry
    ) {
        $this->rules = $rules;
        $this->registry = $registry;
        $this->rulesResource = $rulesResource;
        $this->request = $context->getRequest();

        parent::__construct($context);
    }

    /**
     * Execute method.
     *
     * @return void
     */
    public function execute()
    {
        $id = $this->request->getParam('id');

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

        $block = $this->_view->getLayout()->getBlock('dotdigitalgroup.email.rules.edit');
        /** @var \Dotdigitalgroup\Email\Block\Adminhtml\Rules\Edit $block */
        $block->setData('action', $this->getUrl('*/*/save'));

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
