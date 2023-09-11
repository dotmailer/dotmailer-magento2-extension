<?php

namespace Dotdigitalgroup\Email\Model\Customer\Account;

use Dotdigitalgroup\Email\Helper\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Configuration
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Decide if we should redirect to connector/customer/index layout.
     *
     * @param string|int $websiteId
     *
     * @return bool
     */
    public function shouldRedirectToConnectorCustomerIndex($websiteId)
    {
        $enabled = $this->scopeConfig->isSetFlag(
            Config::XML_PATH_CONNECTOR_API_ENABLED,
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
        $dataFields = $this->canShowDataFields($websiteId);
        $addressBooks = $this->canShowAddressBooks($websiteId);
        $preferences = $this->canShowPreferences($websiteId);

        return $enabled && ($dataFields || $addressBooks || $preferences);
    }

    /**
     * Check if address books are to be shown to the customer.
     *
     * @param string|int $websiteId
     *
     * @return bool
     */
    public function canShowAddressBooks($websiteId): bool
    {
        return $this->scopeConfig->isSetFlag(
            Config::XML_PATH_CONNECTOR_ADDRESSBOOK_PREF_CAN_CHANGE_BOOKS,
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * Check if data fields are to be shown to the customer.
     *
     * @param string|int $websiteId
     *
     * @return bool
     */
    public function canShowDataFields($websiteId): bool
    {
        return $this->scopeConfig->isSetFlag(
            Config::XML_PATH_CONNECTOR_ADDRESSBOOK_PREF_CAN_SHOW_FIELDS,
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * Get the data fields to display.
     *
     * @param string|int $websiteId
     *
     * @return string
     */
    public function getDataFieldsToShow($websiteId)
    {
        return $this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_ADDRESSBOOK_PREF_SHOW_FIELDS,
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * Get address book ids to display.
     *
     * @param string|int $websiteId
     * @return array
     */
    public function getAddressBookIdsToShow($websiteId)
    {
        $bookIds = $this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_ADDRESSBOOK_PREF_SHOW_BOOKS,
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );

        if (empty($bookIds)) {
            return [];
        }

        $additionalFromConfig = explode(',', $bookIds);
        //unset the default option - for multi select
        if ($additionalFromConfig[0] == '0') {
            unset($additionalFromConfig[0]);
        }

        return $additionalFromConfig;
    }

    /**
     * Check if preferences are to be shown to the customer.
     *
     * @param string|int $websiteId
     *
     * @return bool
     */
    private function canShowPreferences($websiteId): bool
    {
        return $this->scopeConfig->isSetFlag(
            Config::XML_PATH_CONNECTOR_SHOW_PREFERENCES,
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }
}
