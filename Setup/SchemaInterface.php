<?php

namespace Dotdigitalgroup\Email\Setup;

interface SchemaInterface
{
    public const EMAIL_CONTACT_TABLE = 'email_contact';
    public const EMAIL_ORDER_TABLE = 'email_order';
    public const EMAIL_CAMPAIGN_TABLE = 'email_campaign';
    public const EMAIL_REVIEW_TABLE = 'email_review';
    public const EMAIL_WISHLIST_TABLE = 'email_wishlist';
    public const EMAIL_CATALOG_TABLE = 'email_catalog';
    public const EMAIL_RULES_TABLE = 'email_rules';
    public const EMAIL_IMPORTER_TABLE = 'email_importer';
    public const EMAIL_AUTOMATION_TABLE = 'email_automation';
    public const EMAIL_ABANDONED_CART_TABLE = 'email_abandoned_cart';
    public const EMAIL_CONTACT_CONSENT_TABLE = 'email_contact_consent';
    public const EMAIL_FAILED_AUTH_TABLE = 'email_failed_auth';
    public const EMAIL_COUPON_TABLE = 'email_coupon_attribute';
}
