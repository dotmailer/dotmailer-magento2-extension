<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector;

/**
 * test class for validation of the api credentials.
 */
class Test
{
    public const DEFAULT_API_ENDPOINT = 'https://r1-api.dotmailer.com';

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Framework\App\Config\ReinitableConfigInterface
     */
    private $config;

    /**
     * @var \Dotdigitalgroup\Email\Model\Apiconnector\Account
     */
    private $account;

    /**
     * Test constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Framework\App\Config\ReinitableConfigInterface $config
     * @param \Dotdigitalgroup\Email\Model\Apiconnector\Account $account
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Framework\App\Config\ReinitableConfigInterface $config,
        Account $account
    ) {
        $this->helper = $data;
        $this->config = $config;
        $this->account = $account;
    }

    /**
     * Validate apiuser on save.
     *
     * @param string $apiUsername
     * @param string $apiPassword
     *
     * @return bool|mixed
     */
    public function validate($apiUsername, $apiPassword)
    {
        $website = $this->helper->getWebsiteForSelectedScopeInAdmin();

        if (!$this->helper->isEnabled($website)) {
            return false;
        }

        if ($apiPassword == '******') {
            $apiPassword = $this->helper->getApiPassword($website);
        }

        $client = $this->helper->clientFactory->create();
        if ($apiUsername && $apiPassword) {
            $client->setApiUsername($apiUsername)
                ->setApiPassword($apiPassword);

            $accountInfo = $client->getAccountInfo();

            if (isset($accountInfo->message)) {
                $this->helper->log('VALIDATION ERROR :  ' . $accountInfo->message);
                $this->helper->saveApiEndpoint(self::DEFAULT_API_ENDPOINT, $website->getId());
                $this->config->reinit();
                return false;
            }

            // If api endpoint then force save
            if ($apiEndpoint = $this->account->getApiEndpoint($accountInfo)) {
                $this->helper->saveApiEndpoint($apiEndpoint, $website->getId());
            }
            return $accountInfo;
        }

        return false;
    }

    /**
     * Check API endpoint matches permitted hosts and HTTPS scheme before storing.
     *
     * @param string $apiEndpoint
     *
     * @return bool
     */
    public function validateEndpoint($apiEndpoint)
    {
        if (!preg_match('#^https://(r[0-9]+-)?api\.(dotmailer|dotdigital)\.com$#', $apiEndpoint) &&
            !preg_match('#^https://(r[0-9]+\.)?apiconnector\.com$#', $apiEndpoint)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('The endpoint '.$apiEndpoint.' is not permitted.')
            );
        }

        return true;
    }
}
