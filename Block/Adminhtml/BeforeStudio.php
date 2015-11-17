<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml;

class Studio extends \Magento\Backend\Block\Widget\Container
{
	protected $_template = 'automation/iframe.phtml';

	protected $_sessionFactory;
	protected $_data;
	protected $_config;
	protected $_configFactory;

	public function __construct(
		\Dotdigitalgroup\Email\Helper\ConfigFactory $configFactory,
		\Dotdigitalgroup\Email\Helper\Data $data,
		//\Dotdigitalgroup\Email\Helper\Config $config,
		\Magento\Backend\Block\Widget\Context $context,
		\Magento\Backend\Model\Auth\SessionFactory $sessionFactory
	)
	{
		$this->_data = $data;
		$this->_configFactory = $configFactory;
		$this->_sessionFactory = $sessionFactory;

		parent::__construct($context, array());
	}
	/**
	 * Class constructor
	 *
	 * @return void
	 */
	protected function _construct()
	{
		$this->addData(
			[
				\Magento\Backend\Block\Widget\Container::PARAM_CONTROLLER => 'adminhtml_studio',
				\Magento\Backend\Block\Widget\Grid\Container::PARAM_BLOCK_GROUP => 'Dotdigitalgroup_Email',
				\Magento\Backend\Block\Widget\Container::PARAM_HEADER_TEXT => __('Automation Studio'),
			]
		);
		parent::_construct();
	}

	/**
	 *
	 *
	 * @return array
	 */
//	protected function _getAddButtonOptions()
//	{
//
//		$splitButtonOptions[] = [
//			'label' => __('Add New'),
//			'onclick' => "setLocation('" . $this->_getCreateUrl() . "')"
//		];
//
//		return $splitButtonOptions;
//	}

	public function getLoginUserHtml()
	{
		// authorize or create token.
		$token = $this->generatetokenAction();
		$baseUrl = $this->_configFactory->create()
			->getLogUserUrl();

		$loginuserUrl = $baseUrl  . $token . '&suppressfooter=true';


		return $loginuserUrl;
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
			//$this->messageManager->addNotice('Please Connect To Access The Page.');
		}

		//$this->('*/system_config/edit', array('section' => 'connector_developer_settings'));
	}


	/**
	 * Render grid
	 *
	 * @return string
	 */
	public function getGridHtml()
	{
		return $this->getChildHtml('grid');
	}
}
