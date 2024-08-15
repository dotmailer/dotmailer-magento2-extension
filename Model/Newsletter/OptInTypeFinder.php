<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Newsletter;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Newsletter\Model\Subscriber;
use Magento\Store\Model\ScopeInterface;

class OptInTypeFinder
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
     * Get opt in type by store.
     *
     * @param int $storeId
     *
     * @return string|null
     */
    public function getOptInType($storeId)
    {
        $needToConfirm = $this->scopeConfig->getValue(
            Subscriber::XML_PATH_CONFIRMATION_FLAG,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $needToConfirm ? 'double' : null;
    }
}
