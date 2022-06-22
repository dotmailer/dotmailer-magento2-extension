<?php

namespace Dotdigitalgroup\Email\Model\Integration;

use Dotdigitalgroup\Email\Helper\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Apiconnector\Account;
use Magento\Store\Model\ScopeInterface;
use Dotdigitalgroup\Email\Logger\Logger;

class AccountDetails
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Account
     */
    private $account;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var bool
     */
    private $isConnected = false;

    /**
     * @var array
     */
    private $accountDetails = [];

    /**
     * Account details constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $helper
     * @param Account $account
     * @param Logger $logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Data $helper,
        Account $account,
        Logger $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->account = $account;
        $this->logger = $logger;
    }

    /**
     * Get account info.
     *
     * @return array
     */
    public function getAccountInfo(): array
    {
        $website = $this->helper->getWebsiteForSelectedScopeInAdmin();
        if (!empty($this->accountDetails)) {
            return $this->accountDetails;
        }
        return $this->getAccount($website->getId());
    }

    /**
     * Is API connected check
     *
     * @return bool
     */
    public function getIsConnected(): bool
    {
        if (!$this->isConnected) {
            $this->getAccountInfo();
        };
        return $this->isConnected;
    }

    /**
     * Is connector enabled.
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isEnabled(): bool
    {
        $website = $this->helper->getWebsiteForSelectedScopeInAdmin();
        return $this->scopeConfig->isSetFlag(
            Config::XML_PATH_CONNECTOR_API_ENABLED,
            ScopeInterface::SCOPE_WEBSITE,
            $website->getId()
        );
    }

    /**
     * Get account details.
     *
     * @param int|string $websiteId
     * @return array
     */
    private function getAccount($websiteId): array
    {
        try {
            $accountInfo = $this->helper->getWebsiteApiClient($websiteId)
                ->getAccountInfo();
            if (!$accountInfo || !is_object($accountInfo) || isset($accountInfo->message)) {
                $this->accountDetails = [];
                return $this->accountDetails;
            }
            $this->isConnected = true;
            $this->accountDetails = [
                'email' => $this->getAccountOwnerEmail($accountInfo),
                'region' => $this->getAccountRegion($accountInfo),
            ];
        } catch (\Exception $e) {
            $this->logger->debug($e);
            $this->isConnected = false;
        }
        return $this->accountDetails;
    }

    /**
     * Returns account owner email
     *
     * If not valid dotdigital account exception thrown.
     *
     * @param object $accountDetails
     * @return void|string
     * @throws \Exception
     */
    private function getAccountOwnerEmail(object $accountDetails)
    {
        return $this->account->getAccountOwnerEmail($accountDetails);
    }

    /**
     * Get account region.
     *
     * @param object $accountDetails
     * @return string
     * @throws \Exception
     */
    private function getAccountRegion(object $accountDetails): string
    {
        $apiEndpoint = $this->account->getApiEndpoint($accountDetails);
        return substr($this->account->getRegionPrefix($apiEndpoint), 1, 1);
    }
}
