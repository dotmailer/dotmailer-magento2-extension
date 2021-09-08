<?php

namespace Dotdigitalgroup\Email\Model\Sync\Automation\DataField\Updater;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\DataFieldUpdater;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\Website;

class AbandonedCart extends DataFieldUpdater
{
    /**
     * Update abandoned cart data fields.
     *
     * @param string $email
     * @param int $websiteId
     * @param int $quoteId
     * @param string $storeName
     * @param Item $nominatedAbandonedCartItem
     *
     * @return bool|$this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setDataFields($email, $websiteId, $quoteId, $storeName, $nominatedAbandonedCartItem)
    {
        $this->setDefaultDataFields(
            $email,
            $websiteId,
            $storeName
        );

        /** @var Website $website */
        $website = $this->getWebsite($websiteId);

        $abandonedProductName = $website->getConfig(
            Config::XML_PATH_CONNECTOR_ABANDONED_PRODUCT_NAME
        );

        if ($lastQuoteId = $website->getConfig(
            Config::XML_PATH_CONNECTOR_MAPPING_LAST_QUOTE_ID
        )
        ) {
            $this->data[] = [
                'Key' => $lastQuoteId,
                'Value' => $quoteId,
            ];
        }
        if ($nominatedAbandonedCartItem && $abandonedProductName) {
            $this->data[] = [
                'Key' => $abandonedProductName,
                'Value' => $nominatedAbandonedCartItem->getName(),
            ];
        }

        return $this;
    }
}
