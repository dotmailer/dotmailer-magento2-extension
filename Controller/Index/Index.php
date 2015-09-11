<?php

namespace Dotdigitalgroup\Email\Controller\Index;


class Index extends \Magento\Framework\App\Action\Action {

	/**
	 * Pass arguments for dependency injection
	 *
	 * @param \Magento\Framework\App\Action\Context $context
	 */
	public function __construct(
		\Magento\Framework\App\Action\Context $context
	) {
		parent::__construct($context);
	}

	/**
	 * Sets the content of the response
	 */
	public function execute()
	{
		$model = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Cron')->contactSync();
		$model = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Cron')->emailImporter();


	}
}