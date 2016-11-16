<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Review;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;

class MassDelete extends Action
{

    /**
     * @var \Dotdigitalgroup\Email\Model\ReviewFactory
     */
    public $review;

    /**
     * MassDelete constructor.
     *
     * @param Action\Context                             $context
     * @param \Dotdigitalgroup\Email\Model\ReviewFactory $reviewFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Dotdigitalgroup\Email\Model\ReviewFactory $reviewFactory
    ) {
        $this->review = $reviewFactory;

        parent::__construct($context);
    }
    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $searchIds = $this->getRequest()->getParam('selected');
        if (!is_array($searchIds)) {
            $this->messageManager->addErrorMessage(__('Please select reviews.'));
        } else {
            try {
                foreach ($searchIds as $searchId) {
                    $model = $this->review->create()
                        ->setId($searchId);
                    $model->delete();
                }
                $this->messageManager->addSuccessMessage(
                    __('Total of %1 record(s) were deleted.', count($searchIds))
                );
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(
            ResultFactory::TYPE_REDIRECT
        );
        $resultRedirect->setPath('*/*/');

        return $resultRedirect;
    }
}
