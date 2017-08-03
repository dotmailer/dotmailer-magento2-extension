<?php

namespace Dotdigitalgroup\Email\Plugin;

/**
 * Newsletter disable susbcriber email depending on settings value.
 */
class SubscriberPlugin
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * SubscriberPlugin constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     * @param callable $proceed
     *
     * @return mixed
     */
    public function aroundSendConfirmationSuccessEmail(
        \Magento\Newsletter\Model\Subscriber $subscriber,
        callable $proceed
    ) {
        $storeId = $subscriber->getStoreId();
        //scope config for sending newsletter is disabled
        if (! $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DISABLE_NEWSLETTER_SUCCESS,
            'store',
            $storeId
        )
        ) {
            return $proceed();
        }
    }
}
