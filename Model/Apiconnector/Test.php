<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;

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
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * Test constructor.
     *
     * @param Data $data
     * @param ClientFactory $clientFactory
     * @param ReinitableConfigInterface $config
     * @param Account $account
     * @param WriterInterface $configWriter
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data,
        ClientFactory $clientFactory,
        \Magento\Framework\App\Config\ReinitableConfigInterface $config,
        Account $account,
        WriterInterface $configWriter
    ) {
        $this->helper = $data;
        $this->clientFactory = $clientFactory;
        $this->config = $config;
        $this->account = $account;
        $this->configWriter = $configWriter;
    }

    /**
     * Validate api user on save.
     *
     * @param string $apiUsername
     * @param string $apiPassword
     *
     * @return bool|mixed
     * @throws \Exception
     */
    public function validate(string $apiUsername, string $apiPassword)
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
            $scope = $website->getId() > 0 ? ScopeInterface::SCOPE_WEBSITES : ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            if ($apiEndpoint = $this->account->getApiEndpoint($accountInfo)) {
                $this->configWriter->save(
                    Config::PATH_FOR_API_ENDPOINT,
                    $apiEndpoint,
                    $scope,
                    $website->getId()
                );
            }

            if ($accountId = $this->account->getAccountId($accountInfo)) {
                $this->configWriter->save(
                    Config::PATH_FOR_ACCOUNT_ID,
                    $accountId,
                    $scope,
                    $website->getId()
                );
            }

            $this->config->reinit();
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
     * @throws LocalizedException
     */
    public function validateEndpoint(string $apiEndpoint): bool
    {
        if (!preg_match('#^https://(r[0-9]+-)?api(.*)\.(dotmailer|dotdigital)\.com$#', $apiEndpoint) &&
            !preg_match('#^https://(r[0-9]+\.)?apiconnector\.com$#', $apiEndpoint)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('The endpoint ' . $apiEndpoint . ' is not permitted.')
            );
        }

        return true;
    }
}
