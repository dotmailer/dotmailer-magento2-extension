<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml;

class Studio extends \Magento\Backend\Block\Widget\Form
{
	protected $_configFactory;
	protected $_helper;
	protected $_auth;
	protected $_messageManager;


	public function __construct(
		\Magento\Backend\Model\Auth $auth,
		\Dotdigitalgroup\Email\Helper\Config $configFactory,
		\Dotdigitalgroup\Email\Helper\Data $dataHelper,
		\Magento\Backend\Block\Template\Context $context,
		\Magento\Framework\Message\ManagerInterface $messageManager
	)
	{
		$this->_auth = $auth;
		$this->_helper = $dataHelper;
		$this->_configFactory = $configFactory;
		$this->messageManager = $messageManager;

		parent::__construct($context, array());
	}

    /**
     * Constructor. Initialization required variables for class instance.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_blockGroup = 'Dotdigitalgroup\Email';
        $this->_controller = 'adminhtml_studio';
        parent::_construct();
    }

    /**
     * Returns page header
     *
     * @return \Magento\Framework\Phrase
     * @codeCoverageIgnore
     */
    public function getHeader()
    {
        return __('Automation');
    }

    /**
     * Returns URL for save action
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getFormActionUrl()
    {
        return $this->getUrl('adminhtml/*/save');
    }

    /**
     * Returns website id
     *
     * @return int
     * @codeCoverageIgnore
     */
    public function getWebsiteId()
    {
        return $this->getRequest()->getParam('website');
    }

    /**
     * Returns store id
     *
     * @return int
     * @codeCoverageIgnore
     */
    public function getStoreId()
    {
        return $this->getRequest()->getParam('store');
    }



    /**
     * Returns inheritance text
     *
     * @return \Magento\Framework\Phrase
     * @codeCoverageIgnore
     */
    public function getInheritText()
    {
        return __('Use Standard');
    }

	public function getLoginUserHtml()
	{
		// authorize or create token.
		$token = $this->generatetokenAction();
		$baseUrl = $this->_configFactory
			->getLogUserUrl();

		$loginuserUrl = $baseUrl  . $token . '&suppressfooter=true';


		return $loginuserUrl;
	}


	/** * Generate new token and connect from the admin.
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
		$adminUser = $this->_auth->getUser();
		$refreshToken = $adminUser->getRefreshToken();

		if ($refreshToken) {
			$code = $this->_helper->getCode();
			$params = 'client_id=' . $this->_helper->getWebsiteConfig(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CLIENT_ID) .
			          '&client_secret=' . $this->_helper->getWebsiteConfig(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CLIENT_SECRET_ID) .
			          '&refresh_token=' . $refreshToken .
			          '&grant_type=refresh_token';

			$url = $this->_configFactory->getTokenUrl();

			$this->_helper->log('token code : ' . $code . ', params : ' . $params);

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
				$this->_helper->log("Token Error Number:" . curl_errno($ch) . "Error String:" . curl_error($ch));
			}
			curl_close($ch);
			$token = '';
			if (isset($response->access_token)) {
				//save the refresh token to the admin user
				$adminUser->setRefreshToken( $response->access_token )
				          ->save();

				$token = $response->access_token;
			}

			return $token;

		} else {
			$this->messageManager->addNotice('Please Connect To Access The Page.');
			return;
		}

	}

}
