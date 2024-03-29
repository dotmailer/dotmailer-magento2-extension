<?php

namespace Dotdigitalgroup\Email\Helper;

use Dotdigitalgroup\Email\Helper\Config as EmailConfig;
use Magento\Backend\Model\Auth;
use Magento\Framework\Encryption\EncryptorInterface;

class OauthValidator
{

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Auth
     */
    private $auth;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @param Data $helper
     * @param Auth $auth
     * @param Config $config
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        Data $helper,
        Auth $auth,
        Config $config,
        EncryptorInterface $encryptor
    ) {
        $this->helper = $helper;
        $this->auth = $auth;
        $this->config = $config;
        $this->encryptor = $encryptor;
    }

    /**
     * Create authorised url.
     *
     * @param string $url
     * @param string $suppressFooter
     * @return string
     */
    public function createAuthorisedEcUrl($url, $suppressFooter = 'true')
    {
        $generatedToken = $this->generateToken();

        $query = [
            EmailConfig::API_CONNECTOR_SUPPRESS_FOOTER => $suppressFooter,
            EmailConfig::API_CONNECTOR_OAUTH_URL_LOG_USER => $generatedToken,
        ];

        return sprintf('%s?%s', $url, http_build_query(array_filter($query)));
    }

    /**
     * Generate new token and connect from the admin.
     *
     * @return string|null
     */
    private function generateToken()
    {
        $user = $this->auth->getUser();
        /** @var \Magento\User\Model\User $user */
        $refreshToken = $user->getRefreshToken();

        if ($refreshToken) {
            $accessToken = $this->helper
                ->getWebsiteApiClient()
                ->getAccessToken(
                    $this->config->getTokenUrl(),
                    $this->buildUrlParams($this->encryptor->decrypt($refreshToken))
                );

            if (is_string($accessToken)) {
                return $accessToken;
            }
        }

        return null;
    }

    /**
     * Build url param.
     *
     * @param string $refreshToken
     *
     * @return string
     */
    private function buildUrlParams($refreshToken)
    {
        return http_build_query([
            'client_id' => $this->helper->getWebsiteConfig(Config::XML_PATH_CONNECTOR_CLIENT_ID),
            'client_secret' => $this->helper->getWebsiteConfig(Config::XML_PATH_CONNECTOR_CLIENT_SECRET_ID),
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);
    }
}
