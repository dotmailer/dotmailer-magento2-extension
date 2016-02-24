<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Importer;

use Dotdigitalgroup\Email\Controller\Adminhtml\Importer as ImporterController;
use Magento\Framework\Controller\ResultFactory;

class MassResend extends ImporterController
{

	protected $_importerFactory;
	protected $_importerResource;

	public function __construct(
		\Magento\Backend\App\Action\Context $context,
		\Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
		\Dotdigitalgroup\Email\Model\Resource\Importer $importer
	)
	{
		$this->_importerFactory = $importerFactory;
		$this->_importerResource = $importer;
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
				$num = $this->_importerResource->massResend($searchIds);
				$this->messageManager->addSuccess(__('Total of %1 record(s) were reset.', $num));
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
