<?php

namespace Dotdigitalgroup\Email\Model\Connector;

/**
 * Contact datafields information.
 *
 */
class Datafield
{
    public const FIRST_CATEGORY_PUR = 'first_category_pur';
    public const LAST_CATEGORY_PUR = 'last_category_pur';
    public const MOST_PUR_CATEGORY = 'most_pur_category';
    private const DATA_MAPPING_PATH_PREFIX = 'customer_data';

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $visibility;

    /**
     * @var string
     */
    public $defaultValue;

    /**
     * Contact datafields.
     *
     * @var array
     */
    public $datafields = [];

    /**
     * Contact default datafields.
     *
     * @var array
     */
    private $contactDatafields = [
        'customer_id' => [
            'name' => 'CUSTOMER_ID',
            'type' => 'numeric',
            'visibility' => 'private',
        ],
        'firstname' => [
            'name' => 'FIRSTNAME',
            'type' => 'string',
            'visibility' => 'private',
        ],
        'lastname' => [
            'name' => 'LASTNAME',
            'type' => 'string',
            'visibility' => 'private',
        ],
        'gender' => [
            'name' => 'GENDER',
            'type' => 'string',
            'visibility' => 'private',
        ],
        'dob' => [
            'name' => 'DOB',
            'type' => 'date',
            'visibility' => 'private',
        ],
        'title' => [
            'name' => 'TITLE',
            'type' => 'string',
            'visibility' => 'private',
        ],
        'website_name' => [
            'name' => 'WEBSITE_NAME',
            'type' => 'string',
            'visibility' => 'private',
        ],
        'store_name' => [
            'name' => 'STORE_NAME',
            'type' => 'string',
            'visibility' => 'private',
        ],
        'created_at' => [
            'name' => 'ACCOUNT_CREATED_DATE',
            'type' => 'date',
            'visibility' => 'private',
        ],
        'last_logged_date' => [
            'name' => 'LAST_LOGGEDIN_DATE',
            'type' => 'date',
            'visibility' => 'private',
        ],
        'customer_group' => [
            'name' => 'CUSTOMER_GROUP',
            'type' => 'string',
            'visibility' => 'private',
        ],
        'billing_address_1' => [
            'name' => 'BILLING_ADDRESS_1',
            'type' => 'string',
            'visibility' => 'private',
            'defaultValue' => '',
        ],
        'billing_address_2' => [
            'name' => 'BILLING_ADDRESS_2',
            'type' => 'string',
            'visibility' => 'private',
        ],
        'billing_state' => [
            'name' => 'BILLING_STATE',
            'type' => 'string',
            'visibility' => 'private',
        ],
        'billing_city' => [
            'name' => 'BILLING_CITY',
            'type' => 'string',
            'visibility' => 'private',
        ],
        'billing_country' => [
            'name' => 'BILLING_COUNTRY',
            'type' => 'string',
            'visibility' => 'private',
        ],
        'billing_postcode' => [
            'name' => 'BILLING_POSTCODE',
            'type' => 'string',
            'visibility' => 'private',
        ],
        'billing_telephone' => [
            'name' => 'BILLING_TELEPHONE',
            'type' => 'string',
            'visibility' => 'private',
        ],
        'delivery_address_1' => [
            'name' => 'DELIVERY_ADDRESS_1',
            'type' => 'string',
            'visibility' => 'private',
        ],
        'delivery_address_2' => [
            'name' => 'DELIVERY_ADDRESS_2',
            'type' => 'string',
            'visibility' => 'private',
        ],
        'delivery_state' => [
            'name' => 'DELIVERY_STATE',
            'type' => 'string',
            'visibility' => 'private',
        ],
        'delivery_city' => [
            'name' => 'DELIVERY_CITY',
            'type' => 'string',
            'visibility' => 'private',
        ],
        'delivery_country' => [
            'name' => 'DELIVERY_COUNTRY',
            'type' => 'string',
            'visibility' => 'private',
        ],
        'delivery_postcode' => [
            'name' => 'DELIVERY_POSTCODE',
            'type' => 'string',
            'visibility' => 'private',
        ],
        'delivery_telephone' => [
            'name' => 'DELIVERY_TELEPHONE',
            'type' => 'string',
            'visibility' => 'private',
        ],
        'number_of_orders' => [
            'name' => 'NUMBER_OF_ORDERS',
            'type' => 'numeric',
            'visibility' => 'private',
            'context' => 'sales',
        ],
        'total_spend' => [
            'name' => 'TOTAL_SPEND',
            'type' => 'numeric',
            'visibility' => 'private',
            'context' => 'sales',
        ],
        'average_order_value' => [
            'name' => 'AVERAGE_ORDER_VALUE',
            'type' => 'numeric',
            'visibility' => 'private',
            'context' => 'sales',
        ],
        'last_order_date' => [
            'name' => 'LAST_ORDER_DATE',
            'type' => 'date',
            'visibility' => 'private',
            'context' => 'sales',
        ],
        'last_order_id' => [
            'name' => 'LAST_ORDER_ID',
            'type' => 'numeric',
            'visibility' => 'private',
            'context' => 'sales',
        ],
        'last_increment_id' => [
            'name' => 'LAST_INCREMENT_ID',
            'type' => 'numeric',
            'visibility' => 'private',
            'context' => 'sales',
        ],
        'last_quote_id' => [
            'name' => 'LAST_QUOTE_ID',
            'type' => 'numeric',
            'visibility' => 'private',
            'context' => 'sales',
        ],
        'total_refund' => [
            'name' => 'TOTAL_REFUND',
            'type' => 'numeric',
            'visibility' => 'private',
            'context' => 'sales',
        ],
        'review_count' => [
            'name' => 'REVIEW_COUNT',
            'type' => 'numeric',
            'visibility' => 'private',
        ],
        'last_review_date' => [
            'name' => 'LAST_REVIEW_DATE',
            'type' => 'date',
            'visibility' => 'private',
        ],
        'subscriber_status' => [
            'name' => 'SUBSCRIBER_STATUS',
            'type' => 'string',
            'visibility' => 'private',
        ],
        self::MOST_PUR_CATEGORY => [
            'name' => 'MOST_PUR_CATEGORY',
            'type' => 'string',
            'visibility' => 'private',
            'context' => 'sales',
            'automap' => false
        ],
        'most_pur_brand' => [
            'name' => 'MOST_PUR_BRAND',
            'type' => 'string',
            'visibility' => 'private',
            'context' => 'sales',
            'automap' => false
        ],
        'most_freq_pur_day' => [
            'name' => 'MOST_FREQ_PUR_DAY',
            'type' => 'string',
            'visibility' => 'private',
            'context' => 'sales',
        ],
        'most_freq_pur_mon' => [
            'name' => 'MOST_FREQ_PUR_MON',
            'type' => 'string',
            'visibility' => 'private',
            'context' => 'sales',
        ],
        self::FIRST_CATEGORY_PUR => [
            'name' => 'FIRST_CATEGORY_PUR',
            'type' => 'string',
            'visibility' => 'private',
            'context' => 'sales',
        ],
        self::LAST_CATEGORY_PUR => [
            'name' => 'LAST_CATEGORY_PUR',
            'type' => 'string',
            'visibility' => 'private',
            'context' => 'sales',
        ],
        'first_brand_pur' => [
            'name' => 'FIRST_BRAND_PUR',
            'type' => 'string',
            'visibility' => 'private',
            'context' => 'sales',
            'automap' => false
        ],
        'last_brand_pur' => [
            'name' => 'LAST_BRAND_PUR',
            'type' => 'string',
            'visibility' => 'private',
            'context' => 'sales',
            'automap' => false
        ],
        'abandoned_prod_name' => [
            'name' => 'ABANDONED_PROD_NAME',
            'type' => 'string',
            'visibility' => 'private',
        ],
        'billing_company' => [
            'name' => 'BILLING_COMPANY',
            'type' => 'string',
            'visibility' => 'private',
        ],
        'delivery_company' => [
            'name' => 'DELIVERY_COMPANY',
            'type' => 'string',
            'visibility' => 'private',
        ],
    ];

    /**
     * Data fields with their XML path prefix
     *
     * @var array
     */
    private $xmlPathPrefixedFields = [];

    /**
     * Set contact data fields.
     *
     * @param array $contactDatafields
     * @param string|null $xmlPathPrefix
     *
     * @return void
     */
    public function setContactDatafields(array $contactDatafields, ?string $xmlPathPrefix = null)
    {
        if ($xmlPathPrefix) {
            $this->xmlPathPrefixedFields[$xmlPathPrefix] = $contactDatafields;
        } else {
            $this->contactDatafields += $contactDatafields;
        }
    }

    /**
     * Get contact datafields.
     *
     * @param bool $withXmlPathPrefixes
     *
     * @return array
     */
    public function getContactDatafields(bool $withXmlPathPrefixes = false)
    {
        if (!$withXmlPathPrefixes) {
            return $this->contactDatafields;
        }
        return [self::DATA_MAPPING_PATH_PREFIX => $this->contactDatafields] + $this->xmlPathPrefixedFields;
    }

    /**
     * Get sales datafields (a subset of contact data fields).
     *
     * @return array
     */
    public function getSalesDatafields()
    {
        return array_filter($this->contactDatafields, function ($array) {
            return isset($array['context']) && $array['context'] === 'sales';
        });
    }

    /**
     * Set a single datafield.
     *
     * @param string $name
     * @param string|int|boolean $value
     * @param string $type
     * @param string $visibility
     *
     * @return array
     */
    public function setDatafield(
        $name,
        $value,
        $type = 'string',
        $visibility = 'private'
    ) {
        $this->datafields[] = [
            'name' => $name,
            'value' => $value,
            'type' => $type,
            'visibility' => $visibility,
        ];

        return $this->datafields;
    }

    /**
     * Get extra data fields.
     *
     * @return array
     */
    public function getExtraDataFields()
    {
        return [];
    }
}
