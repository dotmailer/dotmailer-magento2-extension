<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Rules;

use Dotdigitalgroup\Email\Model\ResourceModel\Rules as RulesResource;
use Dotdigitalgroup\Email\Model\Rules;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;

class Delete extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::exclusion_rules';

    /**
     * @var Rules
     */
    private $rules;

    /**
     * @var RulesResource
     */
    private $rulesResource;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * Delete constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Rules $rulesResource
     * @param \Dotdigitalgroup\Email\Model\Rules $rules
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        RulesResource $rulesResource,
        Rules $rules,
        Context $context
    ) {
        $this->rules = $rules;
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
        if ($id) {
            try {
                $model = $this->rules;
                $model->setId($id);
                $this->rulesResource->delete($model);
                $this->messageManager->addSuccessMessage(
                    __('The rule has been deleted.')
                );
                $this->_redirect('*/*/');

                return;
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(
                    __('An error occurred while deleting the rule. Please review the log and try again.')
                );
                $this->_redirect(
                    '*/*/edit',
                    ['id' => $id]
                );

                return;
            }
        }
        $this->messageManager->addErrorMessage(
            __('Unable to find a rule to delete.')
        );
        $this->_redirect('*/*/');
    }
}
