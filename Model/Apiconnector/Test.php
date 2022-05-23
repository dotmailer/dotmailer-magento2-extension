<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector;

use Dotdigitalgroup\Email\Helper\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\Writer;
use Magento\Store\Model\ScopeInterface;

/**
 * test class for validation of the api credentials.
 */
class Test
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var \Magento\Framework\App\Config\ReinitableConfigInterface
     */
    private $config;

    /**
     * @var \Dotdigitalgroup\Email\Model\Apiconnector\Account
     */
    private $account;

    /**
     * @var Writer
     */
    private $writer;

    /**
     * Test constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Dotdigitalgroup\Email\Model\Apiconnector\ClientFactory $clientFactory
     * @param \Magento\Framework\App\Config\ReinitableConfigInterface $config
     * @param Account $account
     * @param Writer $writer
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data,
        ClientFactory $clientFactory,
        \Magento\Framework\App\Config\ReinitableConfigInterface $config,
        Account $account,
        Writer $writer
    ) {
        $this->helper = $data;
        $this->clientFactory = $clientFactory;
        $this->config = $config;
        $this->account = $account;
        $this->writer = $writer;
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

        if (!$this->helper->isEnabled($website->getId())) {
            return false;
        }

        if ($apiPassword == '******') {
            $apiPassword = $this->helper->getApiPassword($website->getId());
        }

        if ($apiUsername && $apiPassword) {
            $accountInfo = $this->clientFactory->create()
                ->setApiUsername($apiUsername)
                ->setApiPassword($apiPassword)
                ->getAccountInfo($website->getId());

            if (isset($accountInfo->message)) {
                $this->helper->error('VALIDATION ERROR :  ' . $accountInfo->message);
                return false;
            }

            if ($apiEndpoint = $this->account->getApiEndpoint($accountInfo)) {
                $this->saveApiEndpoint($apiEndpoint, $website->getId());
                $this->config->reinit();
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
        if (!preg_match('#^https://(r[0-9]+-)?api(.*)\.(dotmailer|dotdigital)\.com$#', $apiEndpoint) &&
            !preg_match('#^https://(r[0-9]+\.)?apiconnector\.com$#', $apiEndpoint)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('The endpoint '.$apiEndpoint.' is not permitted.')
            );
        }

        return true;
    }

    /**
     * Save api endpoint into config.
     *
     * @param string $apiEndpoint
     * @param int $websiteId
     *
     * @return void
     */
    private function saveApiEndpoint($apiEndpoint, $websiteId)
    {
        if ($websiteId > 0) {
            $scope = ScopeInterface::SCOPE_WEBSITES;
        } else {
            $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        }
        $this->writer->save(
            Config::PATH_FOR_API_ENDPOINT,
            $apiEndpoint,
            $scope,
            $websiteId
        );
    }
}
