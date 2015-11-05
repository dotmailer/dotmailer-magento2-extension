<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Automation;

use Magento\Framework\Controller\ResultFactory;

class MassDelete extends \Magento\Backend\App\Action
{
	/**
	 * @return \Magento\Backend\Model\View\Result\Redirect
	 */
	public function executeInternal()
	{
		$searchIds = $this->getRequest()->getParam('id');
		if (!is_array($searchIds)) {
			$this->messageManager->addError(__('Please select automation.'));
		} else {
			try {
				foreach ($searchIds as $searchId) {
					$model = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Automation')->load($searchId);
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