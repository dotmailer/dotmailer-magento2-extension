<?php

namespace Dotdigitalgroup\Email\Model\Connector;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\Exception\LocalizedException;

class DataFieldAutoMapper
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Datafield
     */
    private $dataField;

    /**
     * @var ReinitableConfigInterface
     */
    private $reinitableConfig;

    /**
     * Mapping errors
     *
     * @var array
     */
    private $errors = [];

    /**
     * @param Data $helper
     * @param Datafield $dataField
     * @param ReinitableConfigInterface $reinitableConfig
     */
    public function __construct(
        Data $helper,
        Datafield $dataField,
        ReinitableConfigInterface $reinitableConfig
    ) {
        $this->helper = $helper;
        $this->dataField = $dataField;
        $this->reinitableConfig = $reinitableConfig;
    }

    /**
     * Run.
     *
     * @param int $websiteId
     *
     * @return $this
     * @throws LocalizedException
     */
    public function run(int $websiteId = 0): self
    {
        $client = $this->helper->getWebsiteApiClient($websiteId);

        foreach ($this->dataField->getContactDatafields(true) as $xmlPathPrefix => $dataFields) {
            foreach ($dataFields as $key => $dataField) {
                if (isset($dataField['automap']) && $dataField['automap'] === false) {
                    continue;
                }
                $response = $client->postDataFields($dataField);

                // ignore existing datafields message
                if (isset($response->message) && $response->message != Client::API_ERROR_DATAFIELD_EXISTS) {
                    $this->errors[] = [
                        'field' => $dataField['name'],
                        'message' => $response->message,
                    ];
                    continue;
                }

                // map the successfully created data field
                $this->helper->saveConfigData(
                    sprintf('connector_data_mapping/%s/%s', $xmlPathPrefix, $key),
                    strtoupper($dataField['name']),
                    $websiteId ? 'websites' : 'default',
                    $websiteId ?: '0'
                );

                $this->helper->log('DataFieldAutoMapper successfully mapped : ' . $dataField['name']);
            }
        }

        $this->reinitableConfig->reinit();
        return $this;
    }

    /**
     * Get mapping errors.
     *
     * @return array
     */
    public function getMappingErrors(): array
    {
        return $this->errors;
    }
}
