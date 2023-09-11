<?php

namespace Dotdigitalgroup\Email\ViewModel\Customer\Account;

use Dotdigitalgroup\Email\Model\Customer\Account\Configuration;
use Dotdigitalgroup\Email\ViewModel\Customer\AccountSubscriptions;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;

class AddressBooks implements ArgumentInterface
{
    /**
     * @var Configuration
     */
    private $accountConfig;

    /**
     * @var AccountSubscriptions
     */
    private $containerViewModel;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Configuration $accountConfig
     * @param AccountSubscriptions $containerViewModel
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Configuration $accountConfig,
        AccountSubscriptions $containerViewModel,
        StoreManagerInterface $storeManager
    ) {
        $this->accountConfig = $accountConfig;
        $this->containerViewModel = $containerViewModel;
        $this->storeManager = $storeManager;
    }

    /**
     * Getter for additional books. Fully processed.
     *
     * @return array
     * @throws LocalizedException
     */
    public function getAdditionalBooksToShow()
    {
        $additionalBooksToShow = [];
        $processedAddressBooks = [];
        $additionalFromConfig = $this->accountConfig->getAddressBookIdsToShow(
            $this->storeManager->getWebsite()->getId()
        );
        $contactFromTable = $this->containerViewModel->getContactFromTable();
        if (! empty($additionalFromConfig) && $contactFromTable->getContactId()) {
            $contact = $this->containerViewModel->getConnectorContact();
            if (isset($contact->id) && isset($contact->status) && $contact->status !== 'PendingOptIn') {
                $addressBooks = $this->containerViewModel->getApiClient()
                    ->getContactAddressBooks(
                        $contact->id
                    );
                if (is_array($addressBooks)) {
                    foreach ($addressBooks as $addressBook) {
                        $processedAddressBooks[$addressBook->id]
                            = $addressBook->name;
                    }
                }
            }
        }

        return $this->getProcessedAdditionalBooks(
            $additionalFromConfig,
            $processedAddressBooks,
            $additionalBooksToShow
        );
    }

    /**
     * Get additional address books.
     *
     * @param array $additionalFromConfig
     * @param array $processedAddressBooks
     * @param array $additionalBooksToShow
     *
     * @return array
     * @throws \Exception
     */
    private function getProcessedAdditionalBooks($additionalFromConfig, $processedAddressBooks, $additionalBooksToShow)
    {
        foreach ($additionalFromConfig as $bookId) {
            $connectorBook = $this->containerViewModel->getApiClient()->getAddressBookById(
                $bookId
            );
            if (isset($connectorBook->id)) {
                $subscribed = 0;
                if (isset($processedAddressBooks[$bookId])) {
                    $subscribed = 1;
                }
                $additionalBooksToShow[] = [
                    'name' => $connectorBook->name,
                    'value' => $connectorBook->id,
                    'subscribed' => $subscribed,
                ];
            }
        }
        return $additionalBooksToShow;
    }
}
