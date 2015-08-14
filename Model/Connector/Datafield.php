<?php

class Dotdigitalgroup_Email_Model_Connector_Datafield
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
	 * @var array
	 */
	public $datafields = array();

	/**
	 * Contact default datafields.
	 *
	 * @var array
	 */
	private $_contactDatafields = array(
        'customer_id' => array(
            'name' => 'CUSTOMER_ID',
            'type' => 'numeric',
            'visibility' => 'private',
        ),
        'firstname' => array(
            'name' => 'FIRSTNAME',
            'type' => 'string',
            'visibility' => 'private',
        ),
        'lastname' => array(
            'name' => 'LASTNAME',
            'type' => 'string',
            'visibility' => 'private',
        ),
        'gender' => array(
            'name' => 'GENDER',
            'type' => 'string',
            'visibility' => 'private',
        ),
        'dob' => array(
            'name' => 'DOB',
            'type' => 'date',
            'visibility' => 'private',
        ),
        'title' => array(
            'name' => 'TITLE',
            'type' => 'string',
            'visibility' => 'private',
        ),
        'website_name' => array(
            'name' => 'WEBSITE_NAME',
            'type' => 'string',
            'visibility' => 'private',
        ),
        'store_name' => array(
            'name' => 'STORE_NAME',
            'type' => 'string',
            'visibility' => 'private',
        ),
        'created_at' => array(
            'name' => 'ACCOUNT_CREATED_DATE',
            'type' => 'date',
            'visibility' => 'private',
        ),
        'last_logged_date' => array(
            'name' => 'LAST_LOGGEDIN_DATE',
            'type' => 'date',
            'visibility' => 'private',
        ),
        'customer_group' => array(
            'name' => 'CUSTOMER_GROUP',
            'type' => 'string',
            'visibility' => 'private',
        ),
        'billing_address_1' => array(
            'name' => 'BILLING_ADDRESS_1',
            'type' => 'string',
            'visibility' => 'private',
            'defaultValue' => ''
        ),
        'billing_address_2' => array(
            'name' => 'BILLING_ADDRESS_2',
            'type' => 'string',
            'visibility' => 'private',
        ),
        'billing_state' => array(
            'name' => 'BILLING_STATE',
            'type' => 'string',
            'visibility' => 'private',
        ),
        'billing_city' => array(
            'name' => 'BILLING_CITY',
            'type' => 'string',
            'visibility' => 'private',
        ),
        'billing_country' => array(
            'name' => 'BILLING_COUNTRY',
            'type' => 'string',
            'visibility' => 'private',
        ),
        'billing_postcode' => array(
            'name' => 'BILLING_POSTCODE',
            'type' => 'string',
            'visibility' => 'private',
        ),
        'billing_telephone' => array(
            'name' => 'BILLING_TELEPHONE',
            'type' => 'string',
            'visibility' => 'private',
        ),
        'delivery_address_1' => array(
            'name' => 'DELIVERY_ADDRESS_1',
            'type' => 'string',
            'visibility' => 'private',
        ),
        'delivery_address_2' => array(
            'name' => 'DELIVERY_ADDRESS_2',
            'type' => 'string',
            'visibility' => 'private',
        ),
        'delivery_state' => array(
            'name' => 'DELIVERY_STATE',
            'type' => 'string',
            'visibility' => 'private',
        ),
        'delivery_city' => array(
            'name' => 'DELIVERY_CITY',
            'type' => 'string',
            'visibility' => 'private',
        ),
        'delivery_country' => array(
            'name' => 'DELIVERY_COUNTRY',
            'type' => 'string',
            'visibility' => 'private',
        ),
        'delivery_postcode' => array(
            'name' => 'DELIVERY_POSTCODE',
            'type' => 'string',
            'visibility' => 'private',
        ),
        'delivery_telephone' => array(
            'name' => 'DELIVERY_TELEPHONE',
            'type' => 'string',
            'visibility' => 'private',
        ),
        'number_of_orders' => array(
            'name' => 'NUMBER_OF_ORDERS',
            'type' => 'numeric',
            'visibility' => 'private',
        ),
        'total_spend' => array(
            'name' => 'TOTAL_SPEND',
            'type' => 'numeric',
            'visibility' => 'private',
        ),
        'average_order_value' => array(
            'name' => 'AVERAGE_ORDER_VALUE',
            'type' => 'numeric',
            'visibility' => 'private',
        ),
        'last_order_date' => array(
            'name' => 'LAST_ORDER_DATE',
            'type' => 'date',
            'visibility' => 'private',
        ),
        'last_order_id' => array(
            'name' => 'LAST_ORDER_ID',
            'type' => 'numeric',
            'visibility' => 'private',
        ),
        'last_increment_id' => array(
            'name' => 'LAST_INCREMENT_ID',
            'type' => 'numeric',
            'visibility' => 'private',
        ),
        'last_quote_id' => array(
            'name' => 'LAST_QUOTE_ID',
            'type' => 'numeric',
            'visibility' => 'private',
        ),
        'total_refund' => array(
	        'name' => 'TOTAL_REFUND',
	        'type' => 'numeric',
	        'visibility' => 'private',
        ),
        'review_count' => array(
            'name' => 'REVIEW_COUNT',
            'type' => 'numeric',
            'visibility' => 'private',
        ),
        'last_review_date' => array(
            'name' => 'LAST_REVIEW_DATE',
            'type' => 'date',
            'visibility' => 'private',
        ),
        'subscriber_status' => array(
            'name' => 'SUBSCRIBER_STATUS',
            'type' => 'string',
            'visibility' => 'private',
        ),
        'most_pur_category' => array(
            'name' => 'MOST_PUR_CATEGORY',
            'type' => 'string',
            'visibility' => 'private',
        ),
        'most_pur_brand' => array(
            'name' => 'MOST_PUR_BRAND',
            'type' => 'string',
            'visibility' => 'private',
        ),
        'most_freq_pur_day' => array(
            'name' => 'MOST_FREQ_PUR_DAY',
            'type' => 'string',
            'visibility' => 'private',
        ),
        'most_freq_pur_mon' => array(
            'name' => 'MOST_FREQ_PUR_MON',
            'type' => 'string',
            'visibility' => 'private',
        ),
        'first_category_pur' => array(
            'name' => 'FIRST_CATEGORY_PUR',
            'type' => 'string',
            'visibility' => 'private',
        ),
        'last_category_pur' => array(
            'name' => 'LAST_CATEGORY_PUR',
            'type' => 'string',
            'visibility' => 'private',
        ),
        'first_brand_pur' => array(
            'name' => 'FIRST_BRAND_PUR',
            'type' => 'string',
            'visibility' => 'private',
        ),
        'last_brand_pur' => array(
            'name' => 'LAST_BRAND_PUR',
            'type' => 'string',
            'visibility' => 'private',
        ),
        'abandoned_prod_name' => array(
            'name' => 'ABANDONED_PROD_NAME',
            'type' => 'string',
            'visibility' => 'private',
        ),
    );

    /**
     * @param array $contactDatafields
     */
    public function setContactDatafields($contactDatafields)
    {
        $this->_contactDatafields = $contactDatafields;
    }

    /**
     * @return array
     */
    public function getContactDatafields()
    {
        return $this->_contactDatafields;
    }

	/**
	 * set a single datafield.
	 *
	 * @param $name
	 * @param $value
	 * @param string $type
	 * @param string $visibility
	 *
	 * @return array
	 */
	public function setDatafield($name, $value, $type = 'string', $visibility = 'private')
    {
        $this->datafields[] = array(
            'name' => $name,
            'value' => $value,
            'type' => $type,
            'visibility' => $visibility
        );
        return $this->datafields;
    }

}