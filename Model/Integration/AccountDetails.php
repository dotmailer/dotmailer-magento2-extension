<?php

namespace Dotdigitalgroup\Email\Model\Integration;

use Dotdigitalgroup\Email\Helper\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Apiconnector\Account;
use Magento\Store\Model\ScopeInterface;

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
     * Account details constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $helper
     * @param Account $account
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Data $helper,
        Account $account
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->account = $account;
    }

    /**
     * Get account info.
     *
     * @return array
     */
    public function getAccountInfo()
    {
        $website = $this->helper->getWebsiteForSelectedScopeInAdmin();
        return $this->getAccount($website->getId());
    }

    /**
     * Is connector enabled.
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isEnabled()
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
    private function getAccount($websiteId)
    {
        try {
            $accountInfo  = $this->helper->getWebsiteApiClient($websiteId)
                ->getAccountInfo();

            if (!$accountInfo || !is_object($accountInfo) || isset($accountInfo->message)) {
                return ['not_connected' => true];
            }

            $accountDetails = [
                'email' => $this->getAccountOwnerEmail($accountInfo),
                'region' => $this->getAccountRegion($accountInfo),
            ];
        } catch (\Exception $e) {
            return ['not_connected' => true];
        }

        return $accountDetails;
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
    private function getAccountOwnerEmail($accountDetails)
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
    private function getAccountRegion($accountDetails)
    {
        $apiEndpoint = $this->account->getApiEndpoint($accountDetails);
        return substr($this->account->getRegionPrefix($apiEndpoint), 1, 1);
    }
}
