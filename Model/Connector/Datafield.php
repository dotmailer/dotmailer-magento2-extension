<?php

namespace Dotdigitalgroup\Email\Model\Connector;

/**
 * Contact datafields information.
 *
 */
class Datafield
{
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
    public $contactDatafields
        = [
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
            ],
            'total_spend' => [
                'name' => 'TOTAL_SPEND',
                'type' => 'numeric',
                'visibility' => 'private',
            ],
            'average_order_value' => [
                'name' => 'AVERAGE_ORDER_VALUE',
                'type' => 'numeric',
                'visibility' => 'private',
            ],
            'last_order_date' => [
                'name' => 'LAST_ORDER_DATE',
                'type' => 'date',
                'visibility' => 'private',
            ],
            'last_order_id' => [
                'name' => 'LAST_ORDER_ID',
                'type' => 'numeric',
                'visibility' => 'private',
            ],
            'last_increment_id' => [
                'name' => 'LAST_INCREMENT_ID',
                'type' => 'numeric',
                'visibility' => 'private',
            ],
            'last_quote_id' => [
                'name' => 'LAST_QUOTE_ID',
                'type' => 'numeric',
                'visibility' => 'private',
            ],
            'total_refund' => [
                'name' => 'TOTAL_REFUND',
                'type' => 'numeric',
                'visibility' => 'private',
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
            'most_pur_category' => [
                'name' => 'MOST_PUR_CATEGORY',
                'type' => 'string',
                'visibility' => 'private',
            ],
            'most_pur_brand' => [
                'name' => 'MOST_PUR_BRAND',
                'type' => 'string',
                'visibility' => 'private',
            ],
            'most_freq_pur_day' => [
                'name' => 'MOST_FREQ_PUR_DAY',
                'type' => 'string',
                'visibility' => 'private',
            ],
            'most_freq_pur_mon' => [
                'name' => 'MOST_FREQ_PUR_MON',
                'type' => 'string',
                'visibility' => 'private',
            ],
            'first_category_pur' => [
                'name' => 'FIRST_CATEGORY_PUR',
                'type' => 'string',
                'visibility' => 'private',
            ],
            'last_category_pur' => [
                'name' => 'LAST_CATEGORY_PUR',
                'type' => 'string',
                'visibility' => 'private',
            ],
            'first_brand_pur' => [
                'name' => 'FIRST_BRAND_PUR',
                'type' => 'string',
                'visibility' => 'private',
            ],
            'last_brand_pur' => [
                'name' => 'LAST_BRAND_PUR',
                'type' => 'string',
                'visibility' => 'private',
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
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * Datafield constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * Set contact datafields.
     *
     * @param array $contactDatafields
     *
     * @return null
     */
    public function setContactDatafields($contactDatafields)
    {
        $this->contactDatafields = $contactDatafields;
    }

    /**
     * Get contact datafields.
     *
     * @return array
     */
    public function getContactDatafields()
    {
        $contactDataFields = $this->contactDatafields;
        $extraDataFields = $this->getExtraDataFields();
        if (! empty($extraDataFields)) {
            $contactDataFields = array_merge($extraDataFields, $contactDataFields);
        }

        return $contactDataFields;
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
     * @return array
     */
    public function getExtraDataFields()
    {
        return [];
    }
}
