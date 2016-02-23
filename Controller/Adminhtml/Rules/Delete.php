<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Rules;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;


class Delete extends \Magento\Backend\App\AbstractAction
{

	protected $rules;
	protected $_storeManager;
	protected $logger;

	public function __construct(
		Context $context,
		\Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
		\Dotdigitalgroup\Email\Model\Rules $rules,
		\Magento\Framework\Logger\Monolog $monolog
	) {
		parent::__construct($context);
		$this->rules = $rules;
		$this->_storeManager = $storeManagerInterface;
		$this->logger = $monolog;
	}

	/**
	 * Check the permission to run it
	 *
	 * @return bool
	 */
	protected function _isAllowed()
	{
		return $this->_authorization->isAllowed(
			'Dotdigitalgroup_Email::exclusion_rules'
		);
	}

	public function execute()
	{
		if ($id = $this->getRequest()->getParam('id')) {
			try {
				$model = $this->rules;
				$model->setId($id);
				$model->delete();
				$this->messageManager->addSuccess(
					__('The rule has been deleted.')
				);
				$this->_redirect('*/*/');

				return;
			} catch (\Exception $e) {
				$this->messageManager->addError(
					__(
						'An error occurred while deleting the rule. Please review the log and try again.'
					)
				);
				$this->logger->addError($e->getMessage());
				$this->_redirect(
					'*/*/edit',
					array('id' => $this->getRequest()->getParam('id'))
				);

				return;
			}
		}
		$this->messageManager->addError(
			__('Unable to find a rule to delete.')
		);
		$this->_redirect('*/*/');
	}
}
