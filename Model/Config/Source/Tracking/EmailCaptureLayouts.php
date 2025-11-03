<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Config\Source\Tracking;

/**
 * Source model for frontend layout options
 */
class EmailCaptureLayouts
{
    /**
     * List of allowed frontend layout handles for email capture
     * This can be extended by developers to add custom layouts
     *
     * @var array
     */
    private static $layouts = [
        'default' => 'Default',
        'catalog_category_view' => 'Category Page',
        'catalog_product_view' => 'Product Page',
        'cms_page_view' => 'CMS Page',
        'cms_index_index' => 'CMS Home Page',
        'checkout_index_index' => 'Checkout Page',
        'checkout_cart_index'=> 'Shopping Cart Page',
        'customer_account_index' => 'Customer Account Dashboard',
        'customer_account_login' => 'Customer Login Page',
        'customer_account_create' => 'Customer Registration Page',
        'catalogsearch_result_index'=> 'Search Results Page',
        'contact_index_index' => 'Contact Us Page',
        'sales_order_history' => 'Order History Page',
        'sales_order_view' => 'Order View Page',
        'wishlist_index_index' => 'Wishlist Page',
        'newsletter_subscribe' => 'Newsletter Subscription Page',
    ];

    /**
     * Frontend layout options.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];

        foreach ($this->getLayouts() as $layout) {
            // Convert handle to readable format
            $readableLabel = ucwords(str_replace('_', ' ', $layout));
            $options[] = [
                'value' => $layout,
                'label' => __('%1 [%2]', $readableLabel, $layout),
            ];
        }

        return $options;
    }

    /**
     * Get layouts as key-value array for Select renderer
     *
     * @return array
     */
    public function toArray()
    {
        $options = [];
        foreach ($this->getLayouts() as $layout => $label) {
            $options[$layout] = __('%1 [%2]', __($label), $layout);
        }

        return $options;
    }

    /**
     * Get allowed frontend layouts
     *
     * @return array
     */
    public function getLayouts()
    {
        return self::$layouts;
    }
}
