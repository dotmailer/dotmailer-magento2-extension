<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Importer;

use Dotdigitalgroup\Email\Controller\Adminhtml\Importer as ImporterController;
use Magento\Framework\Controller\ResultFactory;

class MassDelete extends ImporterController
{
	protected $_importerFactory;

	public function __construct(
		\Magento\Backend\App\Action\Context $context,
		\Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
	)
	{
		$this->_importerFactory = $importerFactory;
		parent::__construct($context);
	}

	/**
	 * @return \Magento\Backend\Model\View\Result\Redirect
	 */
	public function execute()
	{
		$searchIds = $this->getRequest()->getParam('id');
		if (!is_array($searchIds)) {
			$this->messageManager->addError(__('Please select importer.'));
		} else {
			try {
				foreach ($searchIds as $searchId) {
					$model = $this->_importerFactory->create()
						->load($searchId);
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
