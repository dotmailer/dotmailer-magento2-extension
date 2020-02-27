<?php

namespace Dotdigitalgroup\Email\Setup;

interface SchemaInterface
{
    const EMAIL_CONTACT_TABLE = 'email_contact';
    const EMAIL_ORDER_TABLE = 'email_order';
    const EMAIL_CAMPAIGN_TABLE = 'email_campaign';
    const EMAIL_REVIEW_TABLE = 'email_review';
    const EMAIL_WISHLIST_TABLE = 'email_wishlist';
    const EMAIL_CATALOG_TABLE = 'email_catalog';
    const EMAIL_RULES_TABLE = 'email_rules';
    const EMAIL_IMPORTER_TABLE = 'email_importer';
    const EMAIL_AUTOMATION_TABLE = 'email_automation';
    const EMAIL_ABANDONED_CART_TABLE = 'email_abandoned_cart';
    const EMAIL_CONTACT_CONSENT_TABLE = 'email_contact_consent';
    const EMAIL_FAILED_AUTH_TABLE = 'email_failed_auth';
    const EMAIL_COUPON_TABLE = 'email_coupon_attribute';
}
