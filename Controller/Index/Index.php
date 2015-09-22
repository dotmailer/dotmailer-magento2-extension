<?php

namespace Dotdigitalgroup\Email\Controller\Index;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;

class Index extends \Magento\Framework\App\Action\Action
{

	protected $pageFactory;


	/**
	 * Pass arguments for dependency injection
	 *
	 * @param \Magento\Framework\App\Action\Context $context
	 */
	public function __construct(Context $context, PageFactory $pageFactory)
	{
		$this->pageFactory = $pageFactory;
		return parent::__construct($context);
	}

	/**
	 * Sets the content of the response
	 */
	public function execute()
	{
		//$model = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Cron')->contactSync();
		//$model = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Cron')->emailImporter();
		var_dump(__METHOD__);

		return $this->pageFactory->create();


	}
}