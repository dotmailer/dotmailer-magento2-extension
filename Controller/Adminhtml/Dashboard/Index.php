<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Dotdigitalgroup\Email\Controller\Adminhtml\Dashboard;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Sample\News\Model\Author\Rss;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Catalog product controller
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class Index extends  \Magento\Framework\App\Action\Action
{
	protected $scopeConfig;

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
		echo 'Coming soon.';
    }
}
