<?php

namespace Dotdigitalgroup\Email\Model\Automation\UpdateFields;

class Update
{

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    private $magentoQuoteFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\Sales\QuoteFactory
     */
    private $ddgQuoteFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * Update constructor.
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Quote\Model\QuoteFactory $magentoQuoteFactory
     * @param \Dotdigitalgroup\Email\Model\Sales\QuoteFactory $ddgQuoteFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Quote\Model\QuoteFactory $magentoQuoteFactory,
        \Dotdigitalgroup\Email\Model\Sales\QuoteFactory $ddgQuoteFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->helper = $helper;
        $this->magentoQuoteFactory = $magentoQuoteFactory;
        $this->ddgQuoteFactory = $ddgQuoteFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Update abandoned cart data fields.
     *
     * @param string $email
     * @param int $websiteId
     * @param int $quoteId
     * @param string $parentStoreName
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function updateAbandonedCartDatafields($email, $websiteId, $quoteId, $parentStoreName)
    {
        $website = $this->storeManager->getWebsite($websiteId);

        // Load the origin quote
        $quoteModel = $this->magentoQuoteFactory->create()
            ->loadByIdWithoutStore($quoteId);
        $items = $quoteModel->getAllItems();

        if (count($items) === 0) {
            return false;
        }

        // Nominate the most expensive item in the cart as the 'abandoned product'
        $nominatedAbandonedCartItem = $this->ddgQuoteFactory->create()
            ->getMostExpensiveItems($items);
        $abandonedProductName = $website->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_ABANDONED_PRODUCT_NAME
        );

        if ($lastQuoteId = $website->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_MAPPING_LAST_QUOTE_ID
        )
        ) {
            $data[] = [
                'Key' => $lastQuoteId,
                'Value' => $quoteId,
            ];
        }
        if ($nominatedAbandonedCartItem && $abandonedProductName) {
            $data[] = [
                'Key' => $abandonedProductName,
                'Value' => $nominatedAbandonedCartItem->getName(),
            ];
        }
        if ($storeName = $website->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOMER_STORE_NAME
        )
        ) {
            $data[] = [
                'Key' => $storeName,
                'Value' => $parentStoreName,
            ];
        }
        if ($websiteName = $website->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOMER_WEBSITE_NAME
        )
        ) {
            $data[] = [
                'Key' => $websiteName,
                'Value' => $website->getName(),
            ];
        }
        if (!empty($data)) {
            $client = $this->helper->getWebsiteApiClient($website);
            $client->updateContactDatafieldsByEmail(
                $email,
                $data
            );
        }

        return true;
    }
}
