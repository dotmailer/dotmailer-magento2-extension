<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Studio;

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
	protected $configFactory;

	protected $_sessionFactory;


	public function __construct(
		Context $context
	)
	{

		parent::__construct($context);
	}


	public function executeInternal()
	{
		$this->_view->loadLayout();

		$this->_view->renderLayout();

    }


}
