<?php

namespace Dotdigitalgroup\Email\Controller\Email;

use Dotdigitalgroup\Email\Helper\Config;
use Magento\Backend\Helper\Data;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\View\Result\Page;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\User\Api\Data\UserInterfaceFactory;
use Magento\User\Model\ResourceModel\User;

class Callback implements HttpGetActionInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var UserInterfaceFactory
     */
    private $userInterfaceFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Data
     */
    private $adminHelper;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var User
     */
    private $userResource;

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * Callback constructor.
     *
     * @param User $userResource
     * @param Data $backendData
     * @param Config $config
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param StoreManagerInterface $storeManager
     * @param UserInterfaceFactory $userInterfaceFactory
     * @param Context $context
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param ResultFactory $resultFactory
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        User $userResource,
        Data $backendData,
        Config $config,
        ScopeConfigInterface $scopeConfigInterface,
        StoreManagerInterface $storeManager,
        UserInterfaceFactory $userInterfaceFactory,
        Context $context,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        ResultFactory $resultFactory,
        EncryptorInterface $encryptor
    ) {
        $this->adminHelper = $backendData;
        $this->config = $config;
        $this->scopeConfig = $scopeConfigInterface;
        $this->storeManager = $storeManager;
        $this->userInterfaceFactory = $userInterfaceFactory;
        $this->userResource = $userResource;
        $this->request = $context->getRequest();
        $this->helper = $helper;
        $this->resultFactory = $resultFactory;
        $this->encryptor = $encryptor;
    }

    /**
     * Execute method.
     *
     * @return ResultInterface
     * @throws AlreadyExistsException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $code = $this->request->getParam('code', false);
        $userId = $this->request->getParam('state');
        //load admin user
        $adminUser = $this->userInterfaceFactory->create();
        /** @var AbstractModel $adminUser */
        $this->userResource->load($adminUser, $userId);
        //app code and admin user must be present
        if ($code && $adminUser->getId()) {
            $clientId = $this->scopeConfig->getValue(
                Config::XML_PATH_CONNECTOR_CLIENT_ID
            );
            $clientSecret = $this->scopeConfig->getValue(
                Config::XML_PATH_CONNECTOR_CLIENT_SECRET_ID
            );
            //callback uri if not set custom
            $redirectUri = $this->storeManager->getStore()
                ->getBaseUrl(UrlInterface::URL_TYPE_WEB, true);
            $redirectUri .= 'connector/email/callback';

            $data = 'client_id=' . $clientId .
                '&client_secret=' . $clientSecret .
                '&redirect_uri=' . $redirectUri .
                '&grant_type=authorization_code' .
                '&code=' . $code;

            //callback url
            $url = $this->config->getTokenUrl();
            // @codingStandardsIgnoreStart
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

            $response = json_decode(curl_exec($ch));
            // @codingStandardsIgnoreEnd

            if ($response === false) {
                // @codingStandardsIgnoreLine
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
            $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            /** @var Redirect $redirect */
            $redirect->setPath($this->adminHelper->getUrl('dotdigitalgroup_email/studio'));
        }

        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        /** @var Page $page */
        $page->setStatusHeader(404, '1.1', 'Not Found');
        $page->setHeader('Status', '404 File not found');

        return $page;
    }
}
