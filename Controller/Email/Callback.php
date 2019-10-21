<?php

namespace Dotdigitalgroup\Email\Controller\Email;

class Callback extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\User\Api\Data\UserInterfaceFactory
     */
    private $adminUser;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Config
     */
    private $config;

    /**
     * @var \Magento\Backend\Helper\Data
     */
    private $adminHelper;

    /**
     * @var \Magento\User\Model\ResourceModel\User
     */
    private $userResource;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    private $resultPageFactory;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    private $encryptor;

    /**
     * Callback constructor.
     * @param \Magento\User\Model\ResourceModel\User $userResource
     * @param \Magento\Backend\Helper\Data $backendData
     * @param \Dotdigitalgroup\Email\Helper\Config $config
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\User\Api\Data\UserInterfaceFactory $adminUser
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     */
    public function __construct(
        \Magento\User\Model\ResourceModel\User $userResource,
        \Magento\Backend\Helper\Data $backendData,
        \Dotdigitalgroup\Email\Helper\Config $config,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\User\Api\Data\UserInterfaceFactory $adminUser,
        \Magento\Framework\App\Action\Context $context,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor
    ) {
        $this->adminHelper      = $backendData;
        $this->config           = $config;
        $this->scopeConfig      = $scopeConfigInterface;
        $this->storeManager     = $storeManager;
        $this->adminUser        = $adminUser;
        $this->userResource     = $userResource;
        $this->helper           = $helper;
        $this->resultPageFactory = $resultPageFactory;
        $this->encryptor = $encryptor;

        parent::__construct($context);
    }

    /**
     * Execute method.
     *
     * @return null
     */
    public function execute()
    {
        $code = $this->getRequest()->getParam('code', false);
        $userId = $this->getRequest()->getParam('state');
        //load admin user
        $adminUser = $this->adminUser->create();
        $this->userResource->load($adminUser, $userId);
        //app code and admin user must be present
        if ($code && $adminUser->getId()) {
            $clientId = $this->scopeConfig->getValue(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CLIENT_ID
            );
            $clientSecret = $this->scopeConfig->getValue(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CLIENT_SECRET_ID
            );
            //callback uri if not set custom
            $redirectUri = $this->storeManager->getStore()
                ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB, true);
            $redirectUri .= 'connector/email/callback';

            $data = 'client_id=' . $clientId .
                '&client_secret=' . $clientSecret .
                '&redirect_uri=' . $redirectUri .
                '&grant_type=authorization_code' .
                '&code=' . $code;

            //callback url
            $url = $this->config->getTokenUrl();

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

            $response = json_decode(curl_exec($ch));

            if ($response === false) {
                $this->helper->error('Error Number: ' . curl_errno($ch), []);
            }
            if (isset($response->error)) {
                $this->helper->error('OAUTH failed ' . $response->error, []);
            } elseif (isset($response->refresh_token)) {
                //save the refresh token to the admin user
                $adminUser->setRefreshToken(
                    $this->encryptor->encrypt($response->refresh_token)
                );

                $this->userResource->save($adminUser);
            }

            //redirect to automation index page
            return $this->_redirect($this->adminHelper->getUrl('dotdigitalgroup_email/studio'));
        }

        return $this->resultPageFactory->create()
            ->setStatusHeader(404, '1.1', 'Not Found')
            ->setHeader('Status', '404 File not found');
    }
}
