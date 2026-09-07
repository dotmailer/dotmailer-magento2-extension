<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector;

use Dotdigitalgroup\Email\Helper\Data;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Magento\Framework\Stdlib\StringUtils;

class DataField
{
    private const NAME_LENGTH_LIMIT = 20;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTimeFactory
     */
    public $datetime;

    /**
     * @var StringUtils
     */
    private $stringUtils;

    /**
     * DataFields constructor.
     * @param Data $helper
     * @param DateTimeFactory $datetimeFactory
     * @param \Magento\Framework\Stdlib\StringUtils $stringUtils
     */
    public function __construct(
        Data $helper,
        DateTimeFactory $datetimeFactory,
        StringUtils $stringUtils
    ) {
        $this->helper = $helper;
        $this->datetime = $datetimeFactory;
        $this->stringUtils = $stringUtils;
    }

    /**
     * Check if a data field exists in the Dotdigital account.
     *
     * @param int $websiteId
     * @param string $name
     * @return bool
     * @throws LocalizedException
     */
    public function checkDataFieldExists(int $websiteId, string $name): bool
    {
        $client = $this->helper->getWebsiteApiClient($websiteId);
        $dataFields = $client->getDataFields();

        if (isset($dataFields->message)) {
            throw new LocalizedException(__('Error retrieving data fields: %1', $dataFields->message));
        }

        foreach ($dataFields as $dataField) {
            if (isset($dataField->name) && strtoupper($dataField->name) === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create data fields in account by type.
     *
     * @param int $website
     * @param string $datafield
     * @param string $type
     * @param string $visibility
     * @param int|boolean|string $default
     * @return object
     */
    public function createDatafield($website, $datafield, $type, $visibility = 'Private', $default = 'String')
    {
        $client = $this->helper->getWebsiteApiClient($website);
        switch ($type) {
            case 'Numeric':
                $default = (int)$default;
                break;
            case 'Date':
                $default = $this->datetime->create()->date(\DateTime::ATOM, $default);
                break;
            case 'Boolean':
                $default = (bool)$default;
                break;
            default:
                $default = (string)$default;
        }

        $response = $client->postDataFields($datafield, $type, $visibility, $default);

        return $response;
    }

    /**
     * Has valid length.
     *
     * @param string $datafieldName
     * @return bool
     */
    public function hasValidLength($datafieldName)
    {
        return $this->stringUtils->strlen($datafieldName) <= self::NAME_LENGTH_LIMIT;
    }
}
