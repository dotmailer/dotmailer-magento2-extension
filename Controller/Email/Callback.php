<?php

namespace Dotdigitalgroup\Email\Controller\Email;

class Callback extends \Magento\Framework\App\Action\Action
{
	protected $_helper;
	protected $_adminUser;
	protected $_storeManager;
	public $scopeConfig;
	protected $_config;
	protected $_adminHelper;

	public function __construct(
		\Magento\Backend\Helper\Data $backendData,
		\Dotdigitalgroup\Email\Helper\Config $config,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface,
		\Magento\Store\Model\StoreManager $storeManager,
		\Magento\User\Model\UserFactory $adminUser,
		\Magento\Framework\App\Action\Context $context,
		\Dotdigitalgroup\Email\Helper\Data $helper
	)
	{
		$this->_adminHelper = $backendData;
		$this->_config = $config;
		$this->scopeConfig = $scopeConfigInterface;
		$this->_storeManager = $storeManager;
		$this->_adminUser = $adminUser;
		$this->_helper = $helper;

		parent::__construct($context);
	}


	public function execute()
	{
		$code = $this->getRequest()->getParam('code', false);
		$userId = $this->getRequest()->getParam('state');
		//load admin user
		$adminUser = $this->_adminUser->create()
			->load($userId);
		//app code and admin user must be present
		if ($code && $adminUser->getId()) {

			$clientId = $this->scopeConfig->getValue(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CLIENT_ID);
			$clientSecret = $this->scopeConfig->getValue(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CLIENT_SECRET_ID);
			//callback uri if not set custom
			$redirectUri = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB, true);
			$redirectUri .= 'connector/email/callback';

			$data = 'client_id='    .$clientId.
			        '&client_secret='   .$clientSecret.
			        '&redirect_uri='    .$redirectUri.
			        '&grant_type=authorization_code'.
			        '&code='            .$code;

			//callback url
			$url = $this->_config->getTokenUrl();

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_POST, count($data));
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array ('Content-Type: application/x-www-form-urlencoded'));


			$response = json_decode(curl_exec($ch));

			if ($response === false) {
				$this->_helper->error('Error Number: '. curl_errno($ch), array());
			}
			if (isset($response->error)){
				$this->_helper->error('OAUTH failed ' . $response->error, array());

			} elseif (isset($response->refresh_token)) {
				//save the refresh token to the admin user
				$adminUser->setRefreshToken( $response->refresh_token)
					->save();
			}
		}
		//redirect to automation index page
		$this->_redirect($this->_adminHelper->getUrl('dotdigitalgroup_email/studio'));
	}

}