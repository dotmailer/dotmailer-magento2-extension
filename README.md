# Dotdigital for Magento 2
[![Packagist Version](https://img.shields.io/packagist/v/dotdigital/dotdigital-magento2-extension?color=green&label=stable)](https://github.com/dotmailer/dotmailer-magento2-extension/releases)
[![Packagist Version (including pre-releases)](https://img.shields.io/packagist/v/dotdigital/dotdigital-magento2-extension?color=blue&include_prereleases&label=feature)](https://github.com/dotmailer/dotmailer-magento2-extension/releases)
[![license](https://img.shields.io/github/license/mashape/apistatus.svg)](LICENSE.md)

## Requirements
- PHP 7.2+
- Magento 2.3+ 
    - Magento 2.1.x is compatible up to version 4.2.0-p1
    - Magento 2.2.x is compatible up to version 4.13.x

## Version history
Please see our [Changelog](CHANGELOG.md) or the [Releases](https://github.com/dotmailer/dotmailer-magento2-extension/releases) page.

## Installation
We encourage merchants to install our core modules via our combined **Dotdigital - Marketing Automation** extension:
- View the listing on [Magento Marketplace](https://marketplace.magento.com/dotdigital-dotdigital-magento2-os-package.html).
- View the metapackage on [Github](https://github.com/dotmailer/dotdigital-magento2-os-package). 

**Steps:**
1. You must ‘purchase’ the [core extension](https://marketplace.magento.com/dotdigital-dotdigital-magento2-os-package.html) from the Marketplace.
2. Any existing `require` instructions in your composer.json relating to `dotmailer/*` packages must be removed.
3. Now, require the package.
```
composer require dotdigital/dotdigital-magento2-os-package
```

## Usage and support
Full support documentation and setup guides are available [here](https://support.dotdigital.com/hc/en-gb/sections/360000722900-Engagement-Cloud-for-Magento-2).

### CLI commands
#### sync
Run the sync commands on demand. Useful when troubleshooting cron issues.
```
bin/magento dotdigital:sync
```
This will yield a list of options:
```
Please select a Dotdigital sync to run
  [0 ] AbandonedCart
  [1 ] Automation
  [2 ] Campaign
  [3 ] Catalog
  [4 ] Customer
  [5 ] Guest
  [6 ] Importer
  [7 ] IntegrationInsights
  [8 ] Order
  [9 ] Review
  [10] Subscriber
  [11] Template
  [12] Wishlist
  [13] NegotiableQuote (B2B module required)
```

#### task
A task-runner for utility jobs that aren't syncs. Again, these have crons, so you wouldn't normally need to run these manually.
```
bin/magento dotdigital:task
```
This will yield a list of options:
```
Please select a dotdigital CLI task to run
  [0] Cleaner
  [1] SmsSenderManager (SMS module required)
```

#### migrate
The `migrate` command is a way to re-run the module's data installation process after initial install. 
```
bin/magento dotdigital:migrate [--table=<table_name>]
```
You may supply the following table options: 
```
email_contact
email_order
email_review
email_wishlist
email_catalog
email_b2b_quote (B2B module required)
```
Running `migrate` with no options supplied will re-run the complete data installation process. 

**Warning:** 
- `migrate` starts by truncating tables (either the table you supplied as an option, or, if no options were provided, all `email_` tables).
- You may lose previously saved data with this operation.
- `migrate` will not overwrite a previously-saved dynamic content passcode (4.13.6+). 

## Contribution
You are welcome to contribute to Dotdigital for Magento! You can either:
- Report a bug: create a [GitHub issue](https://github.com/dotmailer/dotmailer-magento2-extension/issues/new) including description, repro steps, Magento and extension version numbers
- Fix a bug: please clone and use our [Develop branch](https://github.com/dotmailer/dotmailer-magento2-extension/tree/develop) to submit your Pull Request
- Request a feature on our [roadmap](https://roadmap.dotdigital.com)
