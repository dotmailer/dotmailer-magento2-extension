<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Quote;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;


class Index extends   \Magento\Backend\App\AbstractAction
{
	protected $scopeConfig;
	/**
	 * @var \Magento\Framework\View\Result\PageFactory
	 */
	protected $resultPageFactory;

	/**
	 * @var \Magento\Backend\Model\View\Result\Page
	 */
	protected $resultPage;

	/**
	 * @param Context $context
	 * @param PageFactory $resultPageFactory
	 * @param ScopeConfigInterface $scopeConfig
	 */
	public function __construct(
		Context $context,
		PageFactory $resultPageFactory,
		ScopeConfigInterface $scopeConfig
	)
	{
		parent::__construct($context);
		$this->resultPageFactory = $resultPageFactory;
		$this->scopeConfig = $scopeConfig;
	}

	public function execute()
	{
		$this->setPageData();
		return $this->getResultPage();
    }

	/**
	 * instantiate result page object
	 *
	 * @return \Magento\Backend\Model\View\Result\Page|\Magento\Framework\View\Result\Page
	 */
	public function getResultPage()
	{
		if (is_null($this->resultPage)) {
			$this->resultPage = $this->resultPageFactory->create();
		}
		return $this->resultPage;
	}

	/**
	 * set page data
	 *
	 * @return $this
	 */
	protected function setPageData()
	{
		$resultPage = $this->getResultPage();
		$resultPage->setActiveMenu('Dotdigitalgroup_Email::quote');
		$resultPage->getConfig()->getTitle()->set((__('Quotes')));
		return $this;
	}
}
