# Dotdigitalgroup_Email module

## Overview
The `Dotdigitalgroup_Email` module is the official feature-rich integration between Magento 2 and the Multichannel Marketing automation platform: dotmailer. It enables you to

- Sync your subscriber's information, sales and product data
- Helps you to set up automation programs such as abandoned cart series, welcome, post purchase, birthday, loyalty campaigns
- Empowers marketers with the tools that make it easy to get dramatic results.


## Implementation Details

The `Dotdigitalgroup_Email` module extends the following Magento core functionalities:
- EmailNotification model: Disable customer email depending on settings value
- Coupon model: Change the expiration day for dotmailer coupon codes
- CustomerManagement model: Add a new automation enrolment to queue
- Customer model: Disable new customer email notification depending on settings value
- Sales exclusion rules model: Set new validation for the coupon codes
- Newsletter Subscriber model: Disable newsletter subscriber email depending on settings value
- Mail Transport Interface: Extend SMTP mail transport


### Admin Functionality
The `Dotdigitalgroup_Email` module adds the **Stores > Configurations >DOTMAILER** section, including the following sub-sections:

- API Credentials
- Data Mapping
- Sync Settings
- Abandoned Carts
- Automation
- Dynamic Content
- Transactional Emails
- Configuration
- Developer

Additionally, the `Dotdigitalgroup_Email` module introduces new navigation options:
- Marketing
  - Marketing Automation
    - Automation Studio
      - Exclusion rules
- Reports
  - Marketing Automation
    - Importer Status
    - Automation Enrollment
    - Campaign Sends
    - Cron Tasks
    - Dashboard
    - Log Viewer

### Frontend Functionality

The `Dotdigitalgroup_Email` module extends the following frontend features in the customer view:
- **Customer account > newsletter subscriptions** section: they will be presented with the selected address books and data fields populated with that contacts info from dotmailer.
- If the **Easy Email Capture (checkout)** setting is enabled, we capture guest email asynchronously at the checkout stage (using Ajax) and update the abandoned cart table with it, so we can send abandoned cart email.
- If the **Easy Email Capture (newsletter)** setting is enabled, we capture guest email asynchronously in the newsletter input (using Ajax) and update the abandoned cart table with it, so we can send abandoned cart email


## Installation Details
The `Dotdigitalgroup_Email` module makes irreversible changes in a database during installation.
The following tables are created:

- email_catalog
- email_importer
- email_rules
- email_automation
- email_review
- email_contact
- email_wishlist
- email_order
- email_campaign

The following tables are updated:

- admin_user: the module adds the following column “refresh_token”
- salesrule_coupon: the module adds the following column “generated_by_dotmailer”

## Dependencies

You can find the list of modules that have dependencies with the `Dotdigitalgroup_Email` module in the require object of the `composer.json` file. The file is located in the same directory as this README file.

## Extension Points

The following module does not provide any extension points.

## Events

The following module does not dispatch any events.

## UI components

You can extend the view of the **Report > Marketing Automation** section using the UI components located in the `/Dotdigitalgroup/Email/view/adminhtml/ui_component` directory.

For more information, see [UI Listing/Grid Component](http://devdocs.magento.com/guides/v2.2/ui-components/ui-listing-grid.html).

## Layouts

### Admin
You can extend and override layouts of the **Report > Marketing Automation** section in the `/Dotdigitalgroup/Email/view/adminhtml/layout`  directory.

### Frontend
You can extend and override layouts of the frontend in the `/Dotdigitalgroup/Email/view/frontend/layout` directory.
For more information about layouts, see the [Layout documentation](http://devdocs.magento.com/guides/v2.2/frontend-dev-guide/layouts/layout-overview.html).

## Additional information
For more dotmailer for Magento 2 documentation, see [dotmailer for magento help center](https://support.dotmailer.com/hc/en-gb/categories/202610368-dotmailer-for-Magento). Also, you can track changes made in the extension in the following [release notes](https://github.com/dotmailer/dotmailer-magento2-extension/blob/master/README.md).
