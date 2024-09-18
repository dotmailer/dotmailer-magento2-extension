<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\ViewModel\Customer\Account;

use Dotdigitalgroup\Email\Model\Customer\Account\Configuration;
use Dotdigitalgroup\Email\ViewModel\Customer\AccountSubscriptions;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;

class DataFields implements ArgumentInterface
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
     * @var TimezoneInterface
     */
    private $localeDate;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Configuration $accountConfig
     * @param AccountSubscriptions $containerViewModel
     * @param TimezoneInterface $localeDate
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Configuration $accountConfig,
        AccountSubscriptions $containerViewModel,
        TimezoneInterface $localeDate,
        StoreManagerInterface $storeManager
    ) {
        $this->accountConfig = $accountConfig;
        $this->containerViewModel = $containerViewModel;
        $this->localeDate = $localeDate;
        $this->storeManager = $storeManager;
    }

    /**
     * Getter for datafields to show. Fully processed.
     *
     * @return array
     * @throws LocalizedException
     */
    public function getDataFieldsToShow()
    {
        $websiteId = $this->storeManager->getWebsite()->getId();
        $dataFieldsFromConfig = $this->accountConfig->getDataFieldsToShow($websiteId);

        if (empty($dataFieldsFromConfig)) {
            return [];
        }

        $processedContactDataFields = [];
        $processedConnectorDataFields = [];
        $contactFromTable = $this->containerViewModel->getContactFromTable();
        $dataFieldsFromConfig = explode(',', $dataFieldsFromConfig);

        if ($contactFromTable) {
            $contact = $this->containerViewModel->getConnectorContact();
            if (isset($contact->id)) {
                $contactDataFields = $contact->dataFields ?? [];
                foreach ($contactDataFields as $contactDataField) {
                    $processedContactDataFields[$contactDataField->key] = $contactDataField->value;
                }
            }
        }

        return $this->getProcessedDataFieldsToShow(
            $processedConnectorDataFields,
            $dataFieldsFromConfig,
            $processedContactDataFields
        );
    }

    /**
     * Get data fields.
     *
     * @param array $processedConnectorDataFields
     * @param array $dataFieldsFromConfig
     * @param array $processedContactDataFields
     *
     * @return array
     * @throws LocalizedException
     */
    private function getProcessedDataFieldsToShow(
        $processedConnectorDataFields,
        $dataFieldsFromConfig,
        $processedContactDataFields
    ) {
        $datafieldsToShow = [];
        $connectorDataFields = $this->containerViewModel->getApiClient()->getDataFields();
        if (! isset($connectorDataFields->message)) {
            foreach ($connectorDataFields as $connectorDataField) {
                $processedConnectorDataFields[$connectorDataField->name]
                    = $connectorDataField;
            }
            foreach ($dataFieldsFromConfig as $dataFieldFromConfig) {
                if (isset($processedConnectorDataFields[$dataFieldFromConfig])) {
                    $value = '';
                    if (isset($processedContactDataFields[$processedConnectorDataFields[$dataFieldFromConfig]->name])) {
                        $value = $processedContactDataFields[
                            $processedConnectorDataFields[$dataFieldFromConfig]->name
                        ];
                        if ($processedConnectorDataFields[$dataFieldFromConfig]->type == 'Date') {
                            $value = $this->localeDate->convertConfigTimeToUtc($value, 'Y-m-d');
                        }
                    }

                    $datafieldsToShow[] = [
                        'name' => $processedConnectorDataFields[$dataFieldFromConfig]->name,
                        'type' => $processedConnectorDataFields[$dataFieldFromConfig]->type,
                        'value' => $value,
                    ];
                }
            }
        }
        return $datafieldsToShow;
    }
}
