<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Dashboard;

use Magento\Framework\Controller\ResultFactory;

class MassDelete extends \Magento\Backend\App\Action
{

    /**
     * @var \Magento\Cron\Model\ScheduleFactory
     */
    public $schedule;

    /**
     * MassDelete constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Cron\Model\ScheduleFactory $schedule
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Cron\Model\ScheduleFactory $schedule
    ) {
        $this->schedule = $schedule;

        parent::__construct($context);
    }
    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $ids = $this->getRequest()->getParam('id');

        if (!is_array($ids)) {
            $this->messageManager->addErrorMessage(__('Please select cron.'));
        } else {
            try {
                foreach ($ids as $id) {
                    //@codingStandardsIgnoreStart
                    $model = $this->schedule->create()
                        ->setId($id);
                    $model->delete();
                    //@codingStandardsIgnoreEnd
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
