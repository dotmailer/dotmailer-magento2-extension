<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Cron;

use Magento\Framework\Controller\ResultFactory;

class MassDelete extends \Magento\Backend\App\Action
{
    protected $schelduleFactory;

    /**
     * MassDelete constructor.
     *
     * @param \Magento\Backend\App\Action\Context                     $context
     * @param \Magento\Ui\Component\MassAction\Filter                 $filter
     * @param \Magento\Cms\Model\ResourceModel\Page\CollectionFactory $collectionFactory
     * @param \Magento\Cron\Model\ScheduleFactory                     $scheduleFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Ui\Component\MassAction\Filter $filter,
        \Magento\Cms\Model\ResourceModel\Page\CollectionFactory $collectionFactory,
        \Magento\Cron\Model\ScheduleFactory $scheduleFactory
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->schelduleFactory = $scheduleFactory;

        parent::__construct($context);
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $searchIds = $this->getRequest()->getParam('id');

        if (!is_array($searchIds)) {
            $this->messageManager->addError(__('Please select task(s).'));
        } else {
            try {
                foreach ($searchIds as $searchId) {
                    $scheldule = $this->schelduleFactory->create()->load($searchId);
                    $scheldule->delete();
                }
                $this->messageManager->addSuccess(
                    __('Total of %1 record(s) were deleted.', count($searchIds))
                );
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
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
