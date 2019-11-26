<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector;

use Dotdigitalgroup\Email\Helper\Data;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Magento\Framework\Stdlib\StringUtils;

class DataField
{
    const NAME_LENGTH_LIMIT = 20;

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
                $default = $this->datetime->create()->date(\Zend_Date::ISO_8601, $default);
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
     * @param $datafieldName
     * @return bool
     */
    public function hasValidLength($datafieldName)
    {
        return $this->stringUtils->strlen($datafieldName) <= self::NAME_LENGTH_LIMIT;
    }
}
