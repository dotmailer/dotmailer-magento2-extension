<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Dashboard;

use Magento\Framework\Controller\ResultFactory;

class MassDelete extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::dashboard';

    /**
     * @var \Magento\Cron\Model\ScheduleFactory
     */
    private $schedule;

    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;
    
    /**
     * @var \Magento\Cron\Model\ResourceModel\Schedule
     */
    private $scheduleResource;

    /**
     * MassDelete constructor.
     *
     * @param \Magento\Cron\Model\ResourceModel\Schedule $scheduleResource
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Cron\Model\ScheduleFactory $schedule
     * @param \Magento\Framework\Escaper $escaper
     */
    public function __construct(
        \Magento\Cron\Model\ResourceModel\Schedule $scheduleResource,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Cron\Model\ScheduleFactory $schedule,
        \Magento\Framework\Escaper $escaper
    ) {
        $this->schedule = $schedule;
        $this->escaper = $escaper;
        $this->scheduleResource = $scheduleResource;
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
                    $model = $this->schedule->create()
                        ->setId($id);
                    $this->scheduleResource->delete($model);
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
