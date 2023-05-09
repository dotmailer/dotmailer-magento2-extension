<?php

namespace Dotdigitalgroup\Email\Helper;

use Dotdigitalgroup\Email\Model\Consent;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Store for core config data path. Keep the configuration path in one place for settings.
 */
class Config extends AbstractHelper
{
    /**
     * API SECTION.
     */
    public const XML_PATH_CONNECTOR_API_ENABLED = 'connector_api_credentials/api/enabled';
    public const XML_PATH_CONNECTOR_API_USERNAME = 'connector_api_credentials/api/username';
    public const XML_PATH_CONNECTOR_API_PASSWORD = 'connector_api_credentials/api/password';
    public const XML_PATH_CONNECTOR_API_TRIAL_TEMPORARY_PASSCODE =
        'connector_api_credentials/api/trial_temporary_passcode';
    public const XML_PATH_CONNECTOR_API_TRIAL_TEMPORARY_PASSCODE_EXPIRY =
        'connector_api_credentials/api/trial_temporary_passcode_expiry';

    /**
     * SYNC SECTION.
     */
    public const XML_PATH_CONNECTOR_SYNC_CUSTOMER_ENABLED =
        'sync_settings/sync/customer_enabled';
    public const XML_PATH_CONNECTOR_SYNC_GUEST_ENABLED =
        'sync_settings/sync/guest_enabled';
    public const XML_PATH_CONNECTOR_SYNC_SUBSCRIBER_ENABLED =
        'sync_settings/sync/subscriber_enabled';
    public const XML_PATH_CONNECTOR_SYNC_ORDER_ENABLED =
        'sync_settings/sync/order_enabled';
    public const XML_PATH_CONNECTOR_SYNC_WISHLIST_ENABLED =
        'sync_settings/sync/wishlist_enabled';
    public const XML_PATH_CONNECTOR_SYNC_REVIEW_ENABLED =
        'sync_settings/sync/review_enabled';
    public const XML_PATH_CONNECTOR_SYNC_CATALOG_ENABLED =
        'sync_settings/sync/catalog_enabled';

    public const XML_PATH_CONNECTOR_CUSTOMERS_ADDRESS_BOOK_ID =
        'sync_settings/addressbook/customers';
    public const XML_PATH_CONNECTOR_SUBSCRIBERS_ADDRESS_BOOK_ID =
        'sync_settings/addressbook/subscribers';
    public const XML_PATH_CONNECTOR_GUEST_ADDRESS_BOOK_ID =
        'sync_settings/addressbook/guests';
    public const XML_PATH_CONNECTOR_SYNC_ALLOW_NON_SUBSCRIBERS =
        'sync_settings/addressbook/allow_non_subscribers';
    // Mapping
    public const XML_PATH_CONNECTOR_MAPPING_LAST_ORDER_ID =
        'connector_data_mapping/customer_data/last_order_id';
    public const XML_PATH_CONNECTOR_MAPPING_LAST_QUOTE_ID =
        'connector_data_mapping/customer_data/last_quote_id';
    public const XML_PATH_CONNECTOR_MAPPING_CUSTOMER_ID =
        'connector_data_mapping/customer_data/customer_id';
    public const XML_PATH_CONNECTOR_MAPPING_CUSTOM_DATAFIELDS
        = 'connector_data_mapping/customer_data/custom_attributes';
    public const XML_PATH_CONNECTOR_MAPPING_CUSTOMER_STORENAME =
        'connector_data_mapping/customer_data/store_name';
    public const XML_PATH_CONNECTOR_MAPPING_CUSTOMER_TOTALREFUND =
        'connector_data_mapping/customer_data/total_refund';

    /**
     * Datafields Mapping.
     */
    public const XML_PATH_CONNECTOR_CUSTOMER_ID =
        'connector_data_mapping/customer_data/customer_id';
    public const XML_PATH_CONNECTOR_CUSTOMER_FIRSTNAME =
        'connector_data_mapping/customer_data/firstname';
    public const XML_PATH_CONNECTOR_CUSTOMER_LASTNAME =
        'connector_data_mapping/customer_data/lastname';
    public const XML_PATH_CONNECTOR_CUSTOMER_DOB =
        'connector_data_mapping/customer_data/dob';
    public const XML_PATH_CONNECTOR_CUSTOMER_GENDER =
        'connector_data_mapping/customer_data/gender';
    public const XML_PATH_CONNECTOR_CUSTOMER_WEBSITE_NAME =
        'connector_data_mapping/customer_data/website_name';
    public const XML_PATH_CONNECTOR_CUSTOMER_STORE_NAME =
        'connector_data_mapping/customer_data/store_name';
    public const XML_PATH_CONNECTOR_CUSTOMER_CREATED_AT =
        'connector_data_mapping/customer_data/created_at';
    public const XML_PATH_CONNECTOR_CUSTOMER_LAST_LOGGED_DATE =
        'connector_data_mapping/customer_data/last_logged_date';
    public const XML_PATH_CONNECTOR_CUSTOMER_CUSTOMER_GROUP =
        'connector_data_mapping/customer_data/customer_group';
    public const XML_PATH_CONNECTOR_CUSTOMER_REVIEW_COUNT =
        'connector_data_mapping/customer_data/review_count';
    public const XML_PATH_CONNECTOR_CUSTOMER_LAST_REVIEW_DATE =
        'connector_data_mapping/customer_data/last_review_date';
    public const XML_PATH_CONNECTOR_CUSTOMER_BILLING_ADDRESS_1 =
        'connector_data_mapping/customer_data/billing_address_1';
    public const XML_PATH_CONNECTOR_CUSTOMER_BILLING_ADDRESS_2 =
        'connector_data_mapping/customer_data/billing_address_2';
    public const XML_PATH_CONNECTOR_CUSTOMER_BILLING_CITY =
        'connector_data_mapping/customer_data/billing_city';
    public const XML_PATH_CONNECTOR_CUSTOMER_BILLING_STATE =
        'connector_data_mapping/customer_data/billing_state';
    public const XML_PATH_CONNECTOR_CUSTOMER_BILLING_COUNTRY =
        'connector_data_mapping/customer_data/billing_country';
    public const XML_PATH_CONNECTOR_CUSTOMER_BILLING_POSTCODE =
        'connector_data_mapping/customer_data/billing_postcode';
    public const XML_PATH_CONNECTOR_CUSTOMER_BILLING_TELEPHONE =
        'connector_data_mapping/customer_data/billing_telephone';
    public const XML_PATH_CONNECTOR_CUSTOMER_DELIVERY_ADDRESS_1 =
        'connector_data_mapping/customer_data/delivery_address_1';
    public const XML_PATH_CONNECTOR_CUSTOMER_DELIVERY_ADDRESS_2 =
        'connector_data_mapping/customer_data/delivery_address_2';
    public const XML_PATH_CONNECTOR_CUSTOMER_DELIVERY_CITY =
        'connector_data_mapping/customer_data/delivery_city';
    public const XML_PATH_CONNECTOR_CUSTOMER_DELIVERY_STATE =
        'connector_data_mapping/customer_data/delivery_state';
    public const XML_PATH_CONNECTOR_CUSTOMER_DELIVERY_COUNTRY =
        'connector_data_mapping/customer_data/delivery_country';
    public const XML_PATH_CONNECTOR_CUSTOMER_DELIVERY_POSTCODE =
        'connector_data_mapping/customer_data/delivery_postcode';
    public const XML_PATH_CONNECTOR_CUSTOMER_DELIVERY_TELEPHONE =
        'connector_data_mapping/customer_data/delivery_telephone';
    public const XML_PATH_CONNECTOR_CUSTOMER_TOTAL_NUMBER_ORDER =
        'connector_data_mapping/customer_data/number_of_orders';
    public const XML_PATH_CONNECTOR_CUSTOMER_AOV =
        'connector_data_mapping/customer_data/average_order_value';
    public const XML_PATH_CONNECTOR_CUSTOMER_TOTAL_SPEND =
        'connector_data_mapping/customer_data/total_spend';
    public const XML_PATH_CONNECTOR_CUSTOMER_LAST_ORDER_DATE =
        'connector_data_mapping/customer_data/last_order_date';
    public const XML_PATH_CONNECTOR_CUSTOMER_LAST_ORDER_ID =
        'connector_data_mapping/customer_data/last_order_id';
    public const XML_PATH_CONNECTOR_CUSTOMER_TOTAL_REFUND =
        'connector_data_mapping/customer_data/total_refund';
    public const XML_PATH_CONNECTOR_CUSTOMER_STORE_NAME_ADDITIONAL
        = 'connector_data_mapping/customer_data/store_name_additional';
    public const XML_PATH_CONNECTOR_CUSTOMER_LAST_ORDER_INCREMENT_ID =
        'connector_data_mapping/customer_data/last_increment_id';
    public const XML_PATH_CONNECTOR_CUSTOMER_MOST_PURCHASED_CATEGORY =
        'connector_data_mapping/customer_data/most_pur_category';
    public const XML_PATH_CONNECTOR_CUSTOMER_MOST_PURCHASED_BRAND =
        'connector_data_mapping/customer_data/most_pur_brand';
    public const XML_PATH_CONNECTOR_CUSTOMER_MOST_FREQUENT_PURCHASE_DAY =
        'connector_data_mapping/customer_data/most_freq_pur_day';
    public const XML_PATH_CONNECTOR_CUSTOMER_MOST_FREQUENT_PURCHASE_MONTH =
        'connector_data_mapping/customer_data/most_freq_pur_mon';
    public const XML_PATH_CONNECTOR_CUSTOMER_FIRST_CATEGORY_PURCHASED =
        'connector_data_mapping/customer_data/first_category_pur';
    public const XML_PATH_CONNECTOR_CUSTOMER_LAST_CATEGORY_PURCHASED =
        'connector_data_mapping/customer_data/last_category_pur';
    public const XML_PATH_CONNECTOR_CUSTOMER_FIRST_BRAND_PURCHASED =
        'connector_data_mapping/customer_data/first_brand_pur';
    public const XML_PATH_CONNECTOR_CUSTOMER_LAST_BRAND_PURCHASED =
        'connector_data_mapping/customer_data/last_brand_pur';
    public const XML_PATH_CONNECTOR_CUSTOMER_SUBSCRIBER_STATUS =
        'connector_data_mapping/customer_data/subscriber_status';
    public const XML_PATH_CONNECTOR_ABANDONED_PRODUCT_NAME =
        'connector_data_mapping/customer_data/abandoned_prod_name';
    public const XML_PATH_CONNECTOR_CUSTOMER_BILLING_COMPANY_NAME =
        'connector_data_mapping/customer_data/billing_company';
    public const XML_PATH_CONNECTOR_CUSTOMER_DELIVERY_COMPANY_NAME =
        'connector_data_mapping/customer_data/delivery_company';

    /**
     * Dynamic Content.
     */
    public const XML_PATH_CONNECTOR_DYNAMIC_CONTENT_PASSCODE =
        'connector_dynamic_content/external_dynamic_content_urls/passcode';
    public const XML_PATH_CONNECTOR_DYNAMIC_CONTENT_WISHLIST_DISPLAY =
        'connector_dynamic_content/products/wishlist_display_type';
    public const XML_PATH_CONNECTOR_DYNAMIC_CONTENT_REVIEW_DISPLAY_TYPE =
        'connector_dynamic_content/products/review_display_type';
    public const XML_PATH_REVIEW_DISPLAY_TYPE =
        'connector_dynamic_content/products/review_display_type';

    /**
     * CONFIGURATION SECTION.
     */
    public const XML_PATH_CONNECTOR_SYNC_DATA_FIELDS_STATUS =
        'connector_configuration/data_fields/order_status';
    public const XML_PATH_CONNECTOR_SYNC_DATA_FIELDS_BRAND_ATTRIBUTE =
        'connector_configuration/data_fields/brand_attribute';

    /**
     * IMAGE TYPES SECTION
     */
    public const XML_PATH_CONNECTOR_IMAGE_TYPES_CATALOG_SYNC =
        'connector_configuration/image_types/catalog_sync';
    public const XML_PATH_CONNECTOR_IMAGE_TYPES_ABANDONED_CART =
        'connector_configuration/image_types/abandoned_cart';
    public const XML_PATH_CONNECTOR_IMAGE_TYPES_ABANDONED_BROWSE =
        'connector_configuration/image_types/abandoned_browse';
    public const XML_PATH_CONNECTOR_IMAGE_TYPES_DYNAMIC_CONTENT =
        'connector_configuration/image_types/dynamic_content';

    //Transactional Data
    public const XML_PATH_CONNECTOR_SYNC_ORDER_STATUS =
        'connector_configuration/transactional_data/order_statuses';
    public const XML_PATH_CONNECTOR_CUSTOM_ORDER_ATTRIBUTES =
        'connector_configuration/transactional_data/order_custom_attributes';
    public const XML_PATH_CONNECTOR_SYNC_PRODUCT_ATTRIBUTES =
        'connector_configuration/transactional_data/order_product_attributes';
    public const XML_PATH_CONNECTOR_SYNC_ORDER_PRODUCT_CUSTOM_OPTIONS =
        'connector_configuration/transactional_data/order_product_custom_options';
    //Admin
    public const XML_PATH_CONNECTOR_DISABLE_NEWSLETTER_SUCCESS =
        'connector_configuration/admin/disable_newsletter_success';
    public const XML_PATH_CONNECTOR_DISABLE_CUSTOMER_SUCCESS =
        'connector_configuration/admin/disable_customer_success';
    //Dynamic Content Styling
    public const XML_PATH_CONNECTOR_DYNAMIC_STYLING =
        'connector_configuration/dynamic_content_style/dynamic_styling';
    public const XML_PATH_CONNECTOR_DYNAMIC_NAME_COLOR =
        'connector_configuration/dynamic_content_style/font_color';
    public const XML_PATH_CONNECTOR_DYNAMIC_NAME_FONT_SIZE =
        'connector_configuration/dynamic_content_style/font_size';
    public const XML_PATH_CONNECTOR_DYNAMIC_NAME_STYLE =
        'connector_configuration/dynamic_content_style/font_style';
    public const XML_PATH_CONNECTOR_DYNAMIC_PRICE_COLOR =
        'connector_configuration/dynamic_content_style/price_color';
    public const XML_PATH_CONNECTOR_DYNAMIC_PRICE_FONT_SIZE =
        'connector_configuration/dynamic_content_style/price_font_size';
    public const XML_PATH_CONNECTOR_DYNAMIC_PRICE_STYLE =
        'connector_configuration/dynamic_content_style/price_font_style';
    public const XML_PATH_CONNECTOR_DYNAMIC_LINK_COLOR =
        'connector_configuration/dynamic_content_style/product_link_color';
    public const XML_PATH_CONNECTOR_DYNAMIC_LINK_FONT_SIZE
        = 'connector_configuration/dynamic_content_style/product_link_font_size';
    public const XML_PATH_CONNECTOR_DYNAMIC_LINK_STYLE =
        'connector_configuration/dynamic_content_style/product_link_style';
    public const XML_PATH_CONNECTOR_DYNAMIC_DOC_FONT =
        'connector_configuration/dynamic_content_style/font';
    public const XML_PATH_CONNECTOR_DYNAMIC_DOC_BG_COLOR =
        'connector_configuration/dynamic_content_style/color';
    public const XML_PATH_CONNECTOR_DYNAMIC_OTHER_COLOR =
        'connector_configuration/dynamic_content_style/other_color';
    public const XML_PATH_CONNECTOR_DYNAMIC_OTHER_FONT_SIZE =
        'connector_configuration/dynamic_content_style/other_font_size';
    public const XML_PATH_CONNECTOR_DYNAMIC_OTHER_STYLE =
        'connector_configuration/dynamic_content_style/other_font_style';
    public const XML_PATH_CONNECTOR_DYNAMIC_COUPON_COLOR =
        'connector_configuration/dynamic_content_style/coupon_font_color';
    public const XML_PATH_CONNECTOR_DYNAMIC_COUPON_FONT_SIZE =
        'connector_configuration/dynamic_content_style/coupon_font_size';
    public const XML_PATH_CONNECTOR_DYNAMIC_COUPON_STYLE =
        'connector_configuration/dynamic_content_style/coupon_font_style';
    public const XML_PATH_CONNECTOR_DYNAMIC_COUPON_FONT =
        'connector_configuration/dynamic_content_style/coupon_font_picker';
    public const XML_PATH_CONNECTOR_DYNAMIC_COUPON_BG_COLOR =
        'connector_configuration/dynamic_content_style/coupon_background_color';

    //Catalog
    public const XML_PATH_CONNECTOR_SYNC_CATALOG_VALUES =
        'connector_configuration/catalog_sync/catalog_values';
    public const XML_PATH_CONNECTOR_SYNC_CATALOG_VISIBILITY =
        'connector_configuration/catalog_sync/catalog_visibility';
    public const XML_PATH_CONNECTOR_SYNC_CATALOG_TYPE =
        'connector_configuration/catalog_sync/catalog_type';

    //Abandoned Cart
    public const XML_PATH_CONNECTOR_EMAIL_CAPTURE =
        'connector_configuration/abandoned_carts/email_capture';
    public const XML_PATH_CONNECTOR_ABANDONED_CART_LIMIT =
        'connector_configuration/abandoned_carts/cart_limit';
    public const XML_PATH_CONNECTOR_EMAIL_CAPTURE_NEWSLETTER =
        'connector_configuration/abandoned_carts/easy_capture_newsletter';
    public const XML_PATH_CONNECTOR_CONTENT_LINK_ENABLED =
        'connector_configuration/abandoned_carts/link_back_to_cart';
    public const XML_PATH_CONNECTOR_CONTENT_LINK_TEXT =
        'connector_configuration/abandoned_carts/link_text';
    public const XML_PATH_CONNECTOR_CONTENT_CART_URL =
        'connector_configuration/abandoned_carts/cart_url';
    public const XML_PATH_CONNECTOR_CONTENT_LOGIN_URL =
        'connector_configuration/abandoned_carts/login_url';
    public const XML_PATH_CONNECTOR_CONTENT_ALLOW_NON_SUBSCRIBERS
        = 'connector_configuration/abandoned_carts/allow_non_subscribers';
    public const XML_PATH_CONNECTOR_AC_AUTOMATION_EXPIRE_TIME =
        'connector_configuration/abandoned_carts/expire_time';

    // Address Book Pref
    public const XML_PATH_CONNECTOR_ADDRESSBOOK_PREF_CAN_CHANGE_BOOKS =
        'connector_configuration/customer_addressbook/can_change';
    public const XML_PATH_CONNECTOR_ADDRESSBOOK_PREF_SHOW_BOOKS =
        'connector_configuration/customer_addressbook/show_books';
    public const XML_PATH_CONNECTOR_ADDRESSBOOK_PREF_CAN_SHOW_FIELDS =
        'connector_configuration/customer_addressbook/can_show_fields';
    public const XML_PATH_CONNECTOR_ADDRESSBOOK_PREF_SHOW_FIELDS =
        'connector_configuration/customer_addressbook/fields_to_show';
    public const XML_PATH_CONNECTOR_SHOW_PREFERENCES =
        'connector_configuration/customer_addressbook/show_preferences';
    //Dynamic Content
    public const XML_PATH_CONNECTOR_DYNAMIC_CONTENT_LINK_TEXT =
        'connector_configuration/dynamic_content/link_text';

    /**
     * Automation studio.
     */
    public const XML_PATH_CONNECTOR_AUTOMATION_STUDIO_CUSTOMER =
        'connector_automation/visitor_automation/customer_automation';
    public const XML_PATH_CONNECTOR_AUTOMATION_STUDIO_SUBSCRIBER =
        'connector_automation/visitor_automation/subscriber_automation';
    public const XML_PATH_CONNECTOR_AUTOMATION_STUDIO_ORDER =
        'connector_automation/visitor_automation/order_automation';
    public const XML_PATH_CONNECTOR_AUTOMATION_STUDIO_GUEST_ORDER =
        'connector_automation/visitor_automation/guest_order_automation';
    public const XML_PATH_CONNECTOR_AUTOMATION_STUDIO_REVIEW =
        'connector_automation/visitor_automation/review_automation';
    public const XML_PATH_CONNECTOR_AUTOMATION_STUDIO_WISHLIST =
        'connector_automation/visitor_automation/wishlist_automation';
    public const XML_PATH_CONNECTOR_AUTOMATION_STUDIO_ORDER_STATUS =
        'connector_automation/order_status_automation/program';
    public const XML_PATH_CONNECTOR_AUTOMATION_STUDIO_FIRST_ORDER =
        'connector_automation/visitor_automation/first_order_automation';

    /**
     * Automation > Review Settings
     */
    public const XML_PATH_REVIEWS_ENABLED =
        'connector_automation/review_settings/enabled';
    public const XML_PATH_REVIEW_ALLOW_NON_SUBSCRIBERS =
        'connector_automation/review_settings/allow_non_subscribers';
    public const XML_PATH_REVIEW_STATUS =
        'connector_automation/review_settings/status';
    public const XML_PATH_REVIEW_DELAY =
        'connector_automation/review_settings/delay';
    public const XML_PATH_REVIEW_NEW_PRODUCT =
        'connector_automation/review_settings/new_product';
    public const XML_PATH_REVIEW_CAMPAIGN =
        'connector_automation/review_settings/campaign';
    public const XML_PATH_AUTOMATION_REVIEW_PRODUCT_PAGE =
        'connector_automation/review_settings/link_to_product_page';
    public const XML_PATH_AUTOMATION_REVIEW_ANCHOR =
        'connector_automation/review_settings/anchor';
    public const XML_PATH_REVIEWS_FEEFO_LOGON =
        'connector_automation/feefo_feedback_engine/logon';
    public const XML_PATH_REVIEWS_FEEFO_REVIEWS =
        'connector_automation/feefo_feedback_engine/reviews_per_product';
    public const XML_PATH_REVIEWS_FEEFO_TEMPLATE =
        'connector_automation/feefo_feedback_engine/template';

    /**
     * Abandoned cart program enrolment.
     */
    public const XML_PATH_LOSTBASKET_ENROL_TO_PROGRAM_ID =
        'abandoned_carts/program/id';
    public const XML_PATH_LOSTBASKET_ENROL_TO_PROGRAM_INTERVAL =
        'abandoned_carts/program/send_after';

    /**
     * TRACKING SECTION.
     */
    public const XML_PATH_CONNECTOR_INTEGRATION_INSIGHTS_ENABLED =
        'connector_configuration/tracking/integration_insights';
    public const XML_PATH_CONNECTOR_ROI_TRACKING_ENABLED =
        'connector_configuration/tracking/roi_enabled';
    public const XML_PATH_CONNECTOR_PAGE_TRACKING_ENABLED =
        'connector_configuration/tracking/page_enabled';
    public const XML_PATH_CONNECTOR_TRACKING_PROFILE_ID =
        'connector_configuration/tracking/tracking_profile_id';
    public const TRACKING_HOST =
        'connector_configuration/tracking/host';
    /**
     * CONSENT SECTION.
     */
    public const XML_PATH_DOTMAILER_CONSENT_SUBSCRIBER_ENABLED =
        'connector_configuration/consent/dotmailer_consent_subscriber_enabled';
    public const XML_PATH_DOTMAILER_CONSENT_SUBSCRIBER_TEXT =
        'connector_configuration/consent/dotmailer_consent_subscriber_text';
    public const XML_PATH_DOTMAILER_CONSENT_CUSTOMER_TEXT =
        'connector_configuration/consent/dotmailer_consent_customer_text';

    /**
     * OAUTH.
     */
    public const API_CONNECTOR_OAUTH_URL_AUTHORISE = 'OAuth2/authorise.aspx?';
    public const API_CONNECTOR_OAUTH_URL_TOKEN = 'OAuth2/Tokens.ashx';
    public const API_CONNECTOR_OAUTH_URL_LOG_USER = 'oauthtoken';
    public const API_CONNECTOR_SUPPRESS_FOOTER = 'suppressfooter';

    /**
     * Developer SECTION.
     */
    public const XML_PATH_CONNECTOR_CLIENT_ID =
        'connector_developer_settings/oauth/client_id';
    public const XML_PATH_CONNECTOR_SYNC_LIMIT =
        'connector_developer_settings/import_settings/batch_size';
    public const XML_PATH_CONNECTOR_MEGA_BATCH_SIZE_ORDERS =
        'connector_developer_settings/import_settings/mega_batch_size_orders';
    public const XML_PATH_CONNECTOR_MEGA_BATCH_SIZE_CATALOG =
        'connector_developer_settings/import_settings/mega_batch_size_catalog';
    public const XML_PATH_CONNECTOR_MEGA_BATCH_SIZE_CONTACT =
        'connector_developer_settings/import_settings/mega_batch_size_contact';
    public const XML_PATH_CONNECTOR_CUSTOM_DOMAIN =
        'connector_developer_settings/oauth/custom_domain';
    public const XML_PATH_CONNECTOR_SETUP_DATAFIELDS =
        'connector_developer_settings/sync_settings/setup_data_fields';
    public const XML_PATH_CONNECTOR_CLIENT_SECRET_ID =
        'connector_developer_settings/oauth/client_key';
    public const XML_PATH_CONNECTOR_CUSTOM_AUTHORIZATION =
        'connector_developer_settings/oauth/custom_authorization';
    public const XML_PATH_CONNECTOR_ADVANCED_DEBUG_ENABLED =
        'connector_developer_settings/debug/debug_enabled';
    public const XML_PATH_CONNECTOR_DEBUG_API_REQUEST_LIMIT =
        'connector_developer_settings/debug/api_log_time';
    public const XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT =
        'connector_developer_settings/import_settings/transactional_data';
    public const XML_PATH_CONNECTOR_IP_RESTRICTION_ADDRESSES =
        'connector_developer_settings/ip_restriction/ip_addresses';
    public const XML_PATH_CONNECTOR_ENABLE_SUBSCRIBER_SALES_DATA =
        'connector_developer_settings/import_settings/subscriber_sales_data_enabled';
    public const XML_PATH_CONNECTOR_STRIP_PUB =
        'connector_developer_settings/import_settings/strip_pub_from_media_paths';
    public const XML_PATH_CONNECTOR_SYNC_BREAK_VALUE =
        'connector_developer_settings/import_settings/transactional_data_break';
    public const XML_PATH_CONNECTOR_SYSTEM_ALERTS_SYSTEM_MESSAGES =
        'connector_developer_settings/system_alerts/system_messages';
    public const XML_PATH_CONNECTOR_SYSTEM_ALERTS_EMAIL_NOTIFICATIONS =
        'connector_developer_settings/system_alerts/email_notifications';
    public const XML_PATH_CONNECTOR_SYSTEM_ALERTS_USER_ROLES =
        'connector_developer_settings/system_alerts/user_roles';
    public const XML_PATH_CONNECTOR_SYSTEM_ALERTS_FREQUENCY =
        'connector_developer_settings/system_alerts/frequency';
    public const XML_PATH_CONNECTOR_SYSTEM_ALERTS_EMAIL_NOTIFICATION_TEMPLATE =
        'connector_developer_settings/system_alerts/email_notification_template';
    public const XML_PATH_PWA_URL =
        'connector_developer_settings/pwa_settings/pwa_url';
    public const XML_PATH_PWA_URL_REWRITES =
        'connector_developer_settings/pwa_settings/use_rewrites';

    /*
     * Cron schedules
     */
    /**
     * @deprecated 4.17.0 Contact sync is now 3 separate paths.
     */
    public const XML_PATH_CRON_SCHEDULE_CONTACT =
        'connector_developer_settings/cron_schedules/contact';
    public const XML_PATH_CRON_SCHEDULE_CUSTOMER =
        'connector_developer_settings/cron_schedules/customer';
    public const XML_PATH_CRON_SCHEDULE_SUBSCRIBER =
        'connector_developer_settings/cron_schedules/subscriber';
    public const XML_PATH_CRON_SCHEDULE_GUEST =
        'connector_developer_settings/cron_schedules/guest';
    public const XML_PATH_CRON_SCHEDULE_IMPORTER =
        'connector_developer_settings/cron_schedules/importer';
    public const XML_PATH_CRON_SCHEDULE_REVIEWS =
        'connector_developer_settings/cron_schedules/review_wishlist';
    public const XML_PATH_CRON_SCHEDULE_ORDERS =
        'connector_developer_settings/cron_schedules/order';
    public const XML_PATH_CRON_SCHEDULE_CATALOG =
        'connector_developer_settings/cron_schedules/catalog';

    /**
     * API and portal endpoints
     */
    public const PATH_FOR_API_ENDPOINT = 'connector_api_credentials/api/endpoint';
    public const PATH_FOR_PORTAL_ENDPOINT = 'connector/portal/endpoint';
    public const PATH_FOR_API_ENDPOINT_SUBDOMAIN = 'connector_api_credentials/api/endpoint_subdomain';
    public const PATH_FOR_ACCOUNT_ID = 'connector_api_credentials/api/account_id';

    /**
     * Version number to append to _dmpt tracking scripts
     */
    public const XML_PATH_TRACKING_SCRIPT_VERSION =
        'connector_configuration/tracking/script_version';

    /**
     * Trial Account.
     */
    public const API_CONNECTOR_TRIAL_FORM_URL =
        'https://magentosignup.dotdigital.com';
    public const XML_PATH_CONNECTOR_TRIAL_URL_OVERRIDE =
        'connector/microsite/url';
    public const INTERNAL_SUB_DOMAIN = 'internal';

    /**
     * Chat Paths
     */
    public const MAGENTO_ROUTE =
        'connector/email/accountcallback';

    /**
     * Products back in stock section
     */
    public const XML_PATH_BACK_IN_STOCK_ENABLED =
        'connector_automation/product_notification/enable';
    public const XML_PATH_BACK_IN_STOCK_ACCOUNT_ID =
        'connector_automation/product_notification/dd_id';
    public const XML_PATH_BACK_IN_STOCK_NOTIFICATION_ID =
        'connector_automation/product_notification/notification_id';

    /**
     * @var \Magento\Framework\Stdlib\StringUtils
     */
    private $stringUtils;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var string
     */
    private $regionAwarePortalUrl;

    /**
     * Config constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * Authorization link for OAUTH.
     *
     * @param int $website
     *
     * @return string
     */
    public function getAuthorizeLink($website = 0)
    {
        //base url, check for custom oauth domain
        if ($this->isAuthorizeCustomDomain($website)) {
            $baseUrl = $this->getWebsiteConfig(self::XML_PATH_CONNECTOR_CUSTOM_DOMAIN)
                . self::API_CONNECTOR_OAUTH_URL_AUTHORISE;
        } else {
            $baseUrl = $this->getRegionAwarePortalUrl($website) . self::API_CONNECTOR_OAUTH_URL_AUTHORISE;
        }

        return $baseUrl;
    }

    /**
     * Is authorization link for custom domain set.
     *
     * @param int $website
     *
     * @return bool
     */
    private function isAuthorizeCustomDomain($website = 0)
    {
        $website = $this->storeManager->getWebsite($website);
        /** @var \Magento\Store\Model\Website $website */
        $customDomain = $website->getConfig(
            self::XML_PATH_CONNECTOR_CUSTOM_DOMAIN
        );

        return (bool)$customDomain;
    }

    /**
     * Callback authorization url.
     *
     * @return string
     */
    public function getCallbackUrl()
    {
        if ($callback = $this->scopeConfig->getValue(self::XML_PATH_CONNECTOR_CUSTOM_AUTHORIZATION)) {
            return $callback;
        }

        return $this->storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_WEB,
            true
        );
    }

    /**
     * Region-aware EC portal URL
     *
     * @param int $website
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getRegionAwarePortalUrl($website = 0)
    {
        if ($this->regionAwarePortalUrl) {
            return $this->regionAwarePortalUrl;
        }

        $website = $this->storeManager->getWebsite($website);
        $apiEndpoint = $this->getWebsiteConfig(self::PATH_FOR_API_ENDPOINT, $website);
        $apiEndpoint = $apiEndpoint ?: 'https://r1-app.dotdigital.com';

        $appSubDomain = substr_compare(
            $apiEndpoint,
            self::INTERNAL_SUB_DOMAIN,
            -strlen(self::INTERNAL_SUB_DOMAIN)
        ) === 0
            ? 'webapp'
            : 'app';

        //replace the api with the app prefix from the domain name
        return $this->regionAwarePortalUrl = str_replace(
            ['api', 'dotmailer'],
            [$appSubDomain, 'dotdigital'],
            $apiEndpoint
        ) . '/';
    }

    /**
     * Token url for OAUTH.
     *
     * @param int $website
     *
     * @return string
     */
    public function getTokenUrl($website = 0)
    {
        if ($this->isAuthorizeCustomDomain($website)) {
            $website = $this->storeManager->getWebsite($website);
            /** @var \Magento\Store\Model\Website $website */
            $tokenUrl = $website->getConfig(self::XML_PATH_CONNECTOR_CUSTOM_DOMAIN) .
                self::API_CONNECTOR_OAUTH_URL_TOKEN;
        } else {
            $tokenUrl = $this->getRegionAwarePortalUrl($website) . self::API_CONNECTOR_OAUTH_URL_TOKEN;
        }

        return $tokenUrl;
    }

    /**
     * Get login user url with for OAUTH.
     *
     * @param int $website
     *
     * @return string
     */
    public function getLoginUserUrl($website = 0)
    {
        return $this->isAuthorizeCustomDomain($website)
            ? $this->getWebsiteConfig(self::XML_PATH_CONNECTOR_CUSTOM_DOMAIN)
            : $this->getRegionAwarePortalUrl($website);
    }

    /**
     * Fetch website configuration from db.
     *
     * @param string $path
     * @param int $website
     * @param string $scope
     * @return int|string|boolean|float
     */
    public function getWebsiteConfig($path, $website = 0, $scope = ScopeInterface::SCOPE_WEBSITE)
    {
        return $this->scopeConfig->getValue(
            $path,
            $scope,
            $website
        );
    }

    /**
     * Fetch status consent subscriber option.
     *
     * @param string|int $websiteId
     * @return string|boolean
     */
    public function isConsentSubscriberEnabled($websiteId)
    {
        return $this->getWebsiteConfig(self::XML_PATH_DOTMAILER_CONSENT_SUBSCRIBER_ENABLED, $websiteId);
    }
}
