<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Rules;

class Delete extends \Magento\Backend\App\AbstractAction
{
    /**
     * @var \Dotdigitalgroup\Email\Model\Rules
     */
    protected $rules;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Dotdigitalgroup\Email\Model\Rules $rules
    ) {
        parent::__construct($context);
        $this->rules = $rules;
        $this->_storeManager = $storeManagerInterface;
    }

    /**
     * Check the permission to run it.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(
            'Dotdigitalgroup_Email::exclusion_rules'
        );
    }

    /**
     * Execute method.
     */
    public function execute()
    {
        if ($id = $this->getRequest()->getParam('id')) {
            try {
                $model = $this->rules;
                $model->setId($id);
                $model->delete();
                $this->messageManager->addSuccess(
                    __('The rule has been deleted.')
                );
                $this->_redirect('*/*/');

                return;
            } catch (\Exception $e) {
                $this->messageManager->addError(
                    __('An error occurred while deleting the rule. Please review the log and try again.')
                );
                $this->_redirect(
                    '*/*/edit',
                    ['id' => $this->getRequest()->getParam('id')]
                );

                return;
            }
        }
        $this->messageManager->addError(
            __('Unable to find a rule to delete.')
        );
        $this->_redirect('*/*/');
    }
}
