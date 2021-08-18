<?php

namespace Dotdigitalgroup\Email\Model\Connector;

use Dotdigitalgroup\Email\Helper\Data;
use Magento\Store\Model\StoreManagerInterface;

class AccountHandler
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * AccountHandler constructor.
     *
     * @param Data $helper
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Data $helper,
        StoreManagerInterface $storeManager
    ) {
        $this->helper = $helper;
        $this->storeManager = $storeManager;
    }

    /**
     * Retrieve a list of active API users with the websites they are associated with.
     *
     * @return array
     */
    public function getAPIUsersForECEnabledWebsites()
    {
        $websites = $this->storeManager->getWebsites(true);
        $apiUsers = [];
        foreach ($websites as $website) {
            $websiteId = $website->getId();
            if ($this->helper->isEnabled($websiteId)) {
                $apiUser = $this->helper->getApiUsername($websiteId);
                $apiUsers[$apiUser]['websites'][] = $websiteId;
            }
        }
        return $apiUsers;
    }
}
