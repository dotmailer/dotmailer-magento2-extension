<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Rules;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;

class Delete extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::exclusion_rules';

    /**
     * @var \Dotdigitalgroup\Email\Model\Rules
     */
    private $rules;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Rules
     */
    private $rulesResource;

    /**
     * Delete constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Rules $rulesResource
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Dotdigitalgroup\Email\Model\Rules $rules
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Rules $rulesResource,
        \Magento\Backend\App\Action\Context $context,
        \Dotdigitalgroup\Email\Model\Rules $rules
    ) {
        parent::__construct($context);
        $this->rules = $rules;
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
