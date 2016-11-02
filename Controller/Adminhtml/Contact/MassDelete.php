<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Contact;

use Magento\Framework\Controller\ResultFactory;

class MassDelete extends \Magento\Backend\App\Action
{

    /**
     * @var \Dotdigitalgroup\Email\Model\ContactFactory
     */
    protected $contact;

    /**
     * MassDelete constructor.
     *
     * @param \Magento\Backend\App\Action\Context         $context
     * @param \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory
    )
    {
        $this->contact = $contactFactory;
        parent::__construct($context);
    }
    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $ids = $this->getRequest()->getParam('id');

        if (!is_array($ids)) {
            $this->messageManager->addErrorMessage(__('Please select contact.'));
        } else {
            try {
                foreach ($ids as $id) {
                    $model = $this->contact->create()
                        ->setEmailContactId($id);
                    $model->delete();
                }
                $this->messageManager->addSuccessMessage(__('Total of %1 record(s) were deleted.', count($ids)));
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('*/*/');

        return $resultRedirect;
    }
}
