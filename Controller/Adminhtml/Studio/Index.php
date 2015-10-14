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
		\Magento\Backend\Model\Auth\SessionFactory $sessionFactory,
		\Dotdigitalgroup\Email\Helper\ConfigFactory $configFactory,
		\Dotdigitalgroup\Email\Helper\Data $data,
		\Dotdigitalgroup\Email\Helper\Config $config,
		Context $context,
		PageFactory $resultPageFactory,
		ScopeConfigInterface $scopeConfig
	)
	{
		$this->_sessionFactory = $sessionFactory;
		$this->_configFacotry = $configFactory;
		$this->_data = $data;
		$this->_config = $config;
		$this->resultPageFactory = $resultPageFactory;
		$this->scopeConfig = $scopeConfig;
		parent::__construct($context);
	}

	public function execute()
	{
		// authorize or create token.
//		$token = $this->generatetokenAction();
//		$baseUrl = $this->_configFacotry->create()
//			->getLogUserUrl();
//
//		$loginuserUrl = $baseUrl  . $token . '&suppressfooter=true';
//
//		return $this->getResponse()->setBody(
//			$this->_view->getLayout()
//	              ->createBlock('Magento\Backend\Block\Template', 'connector_iframe')
//	              ->setText(
//		              "<iframe src=" . $loginuserUrl . " width=100% height=1650 frameborder='0' scrolling='no' style='margin:0;padding: 0;display:block;'></iframe>"
//	              )->toHtml());
//
//
//		return $this->getResponse()->setBody(
//			$this->_view->getLayout()->createBlock(
//				'Magento\Bundle\Block\Adminhtml\Catalog\Product\Edit\Tab\Bundle\Option\Search\Grid',
//				'adminhtml.catalog.product.edit.tab.bundle.option.search.grid'
//			)->setIndex(
//				$this->getRequest()->getParam('index')
//			)->toHtml()
//		);
//		$this->getResponse()->setBody(
//			$this->_view->getLayout()->createBlock(
//				'Magento\Bundle\Block\Adminhtml\Catalog\Product\Edit\Tab\Bundle',
//				'admin.product.bundle.items'
//			)->setProductId(
//				$product->getId()
//			)->toHtml()
//		);

		//return $this->getResultPage();
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
		$resultPage->setActiveMenu('Dotdigitalgroup_Email::studio');
		$resultPage->getConfig()->getTitle()->set((__('Automaiton Studio')));

		return $this;
	}
}
