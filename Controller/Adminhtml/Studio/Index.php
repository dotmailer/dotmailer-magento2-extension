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
		$token = $this->generatetokenAction();
		$baseUrl = $this->_configFacotry->create()
			->getLogUserUrl();

		$loginuserUrl = $baseUrl  . $token . '&suppressfooter=true';

		$this->_view->getLayout()
              ->createBlock('Magento\Backend\Block\Template', 'connector_iframe')
              ->setText(
	              "<iframe src=" . $loginuserUrl . " width=100% height=1650 frameborder='0' scrolling='no' style='margin:0;padding: 0;display:block;'></iframe>"
              );
		$this->setPageData();

		return $this->getResultPage();
    }



	/**
	 * Generate new token and connect from the admin.
	 *
	 *   POST httpsː//my.dotmailer.com/OAuth2/Tokens.ashx HTTP/1.1
	 *   Content-Type: application/x-www-form-urlencoded
	 *   client_id=QVNY867m2DQozogTJfUmqA%253D%253D&
	 *   redirect_uri=https%3a%2f%2flocalhost%3a10999%2fcallback.aspx
	 *   &client_secret=SndpTndiSlhRawAAAAAAAA%253D%253D
	 *   &grant_type=authorization_code
	 */
	public function generatetokenAction()
	{
		//check for secure url
		$adminUser = $this->_sessionFactory->create()
			->getUser();
		$refreshToken = $adminUser->getRefreshToken();

		if ($refreshToken) {
			$code = $this->_data->getCode();
			$params = 'client_id=' . $this->_data->getWebsiteConfig(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CLIENT_ID) .
			          '&client_secret=' . $this->_data->getWebsiteConfig(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CLIENT_SECRET_ID) .
			          '&refresh_token=' . $refreshToken .
			          '&grant_type=refresh_token';

			$url = $this->_config->getTokenUrl();

			$this->_data->log('token code : ' . $code . ', params : ' . $params);

			/**
			 * Refresh Token request.
			 */
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 5);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_POST, count($params));
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));

			$response = json_decode(curl_exec($ch));

			if (isset($response->error)) {
				$this->_data->log("Token Error Num`ber:" . curl_errno($ch) . "Error String:" . curl_error($ch));
			}
			curl_close($ch);

			$token = $response->access_token;
			return $token;

		} else {
			$this->messageManager->addNotice('Please Connect To Access The Page.');
		}

		$this->_redirect('*/system_config/edit', array('section' => 'connector_developer_settings'));
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
