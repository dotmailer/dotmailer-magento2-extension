<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Order;

use Dotdigitalgroup\Email\Controller\Adminhtml\Order as OrderController;
use Magento\Framework\Controller\ResultFactory;

class MassDelete extends OrderController
{

    /**
     * @var \Dotdigitalgroup\Email\Model\OrderFactory
     */
    public $order;

    /**
     * MassDelete constructor.
     *
     * @param \Magento\Backend\App\Action\Context       $context
     * @param \Dotdigitalgroup\Email\Model\OrderFactory $orderFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Dotdigitalgroup\Email\Model\OrderFactory $orderFactory
    ) {
        $this->order = $orderFactory;

        parent::__construct($context);
    }
    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $ids = $this->getRequest()->getParam('email_order_id');
        if (!is_array($ids)) {
            $this->messageManager->addErrorMessage(__('Please select orders.'));
        } else {
            try {
                //@codingStandardsIgnoreStart
                foreach ($ids as $id) {
                    $model = $this->order->create()
                        ->setEmailOrderId($id);
                    $model->delete();
                }
                //@codingStandardsIgnoreEnd
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
