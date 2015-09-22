<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Cron;

use Dotdigitalgroup\Email\Controller\Adminhtml\Cron as CronController;
use Magento\Framework\Controller\ResultFactory;

class MassDelete extends CronController
{
	/**
	 * @return \Magento\Backend\Model\View\Result\Redirect
	 */
	public function execute()
	{
		$searchIds = $this->getRequest()->getParam('schedule_id');
		if (!is_array($searchIds)) {
			$this->messageManager->addError(__('Please select cron.'));
		} else {
			try {
				foreach ($searchIds as $searchId) {
					$model = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Cron')->load($searchId);
					$model->delete();
				}
				$this->messageManager->addSuccess(__('Total of %1 record(s) were deleted.', count($searchIds)));
			} catch (\Exception $e) {
				$this->messageManager->addError($e->getMessage());
			}
		}
		/** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
		$resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
		$resultRedirect->setPath('*/*/');
		return $resultRedirect;
	}
}
