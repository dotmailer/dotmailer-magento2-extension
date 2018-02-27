<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Rules;

class Delete extends \Magento\Backend\App\AbstractAction
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::exclusion_rules';

    /**
     * @var \Dotdigitalgroup\Email\Model\Rules
     */
    private $rules;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Rules
     */
    private $rulesResource;

    /**
     * Delete constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Rules $rulesResource
     * @param \Magento\Backend\App\Action\Context        $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \Dotdigitalgroup\Email\Model\Rules         $rules
     * @param \Magento\Framework\Escaper                 $escaper
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Rules $rulesResource,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Dotdigitalgroup\Email\Model\Rules $rules,
        \Magento\Framework\Escaper $escaper
    ) {
        parent::__construct($context);
        $this->rules        = $rules;
        $this->storeManager = $storeManagerInterface;
        $this->escaper      = $escaper;
        $this->rulesResource = $rulesResource;
    }

    /**
     * Execute method.
     *
     * @return null
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
