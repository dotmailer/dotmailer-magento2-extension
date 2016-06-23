<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml;

/**
 * Class Studio.
 */
class Studio extends \Magento\Backend\Block\Widget\Form
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Config
     */
    protected $_configFactory;
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Backend\Model\Auth
     */
    protected $_auth;
    /**
     * @var
     */
    protected $_messageManager;

    /**
     * Studio constructor.
     *
     * @param \Magento\Backend\Model\Auth $auth
     * @param \Dotdigitalgroup\Email\Helper\Config $configFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $dataHelper
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        \Magento\Backend\Model\Auth $auth,
        \Dotdigitalgroup\Email\Helper\Config $configFactory,
        \Dotdigitalgroup\Email\Helper\Data $dataHelper,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->_auth = $auth;
        $this->_helper = $dataHelper;
        $this->_configFactory = $configFactory;
        $this->_messageManager = $messageManager;

        parent::__construct($context, []);
    }

    /**
     * Constructor. Initialization required variables for class instance.
     */
    protected function _construct()
    {
        $this->_blockGroup = 'Dotdigitalgroup\Email';
        $this->_controller = 'adminhtml_studio';
        parent::_construct();
    }

    /**
     * Returns page header.
     *
     * @return \Magento\Framework\Phrase
     * @codeCoverageIgnore
     */
    public function getHeader()
    {
        return __('Automation');
    }

    /**
     * Returns URL for save action.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getFormActionUrl()
    {
        return $this->getUrl('adminhtml/*/save');
    }

    /**
     * Returns website id.
     *
     * @return int
     * @codeCoverageIgnore
     */
    public function getWebsiteId()
    {
        return $this->getRequest()->getParam('website');
    }

    /**
     * Returns store id.
     *
     * @return int
     * @codeCoverageIgnore
     */
    public function getStoreId()
    {
        return $this->getRequest()->getParam('store');
    }

    /**
     * Returns inheritance text.
     *
     * @return \Magento\Framework\Phrase
     * @codeCoverageIgnore
     */
    public function getInheritText()
    {
        return __('Use Standard');
    }

    /**
     * User login url.
     *
     * @return string
     */
    public function getLoginUserHtml()
    {
        // authorize or create token.
        $token = $this->generatetokenAction();
        $baseUrl = $this->_configFactory
            ->getLogUserUrl();

        $loginuserUrl = $baseUrl . $token . '&suppressfooter=true';

        return $loginuserUrl;
    }

    /**
     * Generate new token and connect from the admin.
     *
     * @return string
     */
    public function generatetokenAction()
    {
        //check for secure url
        $adminUser = $this->_auth->getUser();
        $refreshToken = $adminUser->getRefreshToken();

        if ($refreshToken) {
            $code = $this->_helper->getCode();
            $params = 'client_id=' . $this->_helper->getWebsiteConfig(
                    \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CLIENT_ID)
                . '&client_secret=' . $this->_helper->getWebsiteConfig(
                    \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CLIENT_SECRET_ID)
                . '&refresh_token=' . $refreshToken . '&grant_type=refresh_token';

            $url = $this->_configFactory->getTokenUrl();

            $this->_helper->log('token code : ' . $code . ', params : '
                . $params);

            /*
             * Refresh Token request.
             */
            //@codingStandardsIgnoreStart
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POST, count($params));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_HTTPHEADER,
                array('Content-Type: application/x-www-form-urlencoded'));

            $response = json_decode(curl_exec($ch));

            if (isset($response->error)) {
                $this->_helper->log('Token Error Number:' . curl_errno($ch)
                    . 'Error String:' . curl_error($ch));
            }
            curl_close($ch);
            $token = '';
            if (isset($response->access_token)) {
                //save the refresh token to the admin user
                $adminUser->setRefreshToken($response->access_token)
                    ->save();

                $token = $response->access_token;
            }
            //@codingStandardsIgnoreEnd
            return $token;
        } else {
            $this->_messageManager->addNotice('Please Connect To Access The Page.');

            return '';
        }
    }
}
