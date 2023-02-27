# 4.20.1

### Bug fixes
- We resolved some PHP errors surfaced during DI compile on versions of PHP 7.

# 4.20.0

### What's new
- Contacts are now pushed to the expected address books, with appropriate data fields, prior to automation enrolment.
- New keys (`product_id`, `parent_id` and `price_inc_tax`) have been added to our insight schema for orders.

### Improvements
- We've repaired a number of critical errors flagged by the Magento Upgrade Compatibility tool. These were mostly to do with our use of certain classes and methods, in older code.
- The number of API calls used when updating data fields for a contact by email has been reduced.
- Catalog sync level (**Configuration > Catalog Sync Settings > Catalog Values**) is now a global setting.
- Orders with no `customer_email` value will be skipped during data migration.
- We replaced `Zend_Uri` with the Magento framework’s URL validator.

### Bug fixes
- We fixed a problem with customer sync for merchants with prefixed database tables.
- We fixed an error with our CLI `sync` command relating to a change in Symfony 4.4.
- We updated some legacy jQuery that prevents saving admin configuration in Magento 2.4.6.

# 4.19.5

### Bug fixes
- Our getConvertedPrice method now sets a default price if the product price is not numeric.
- We fixed an issue with `last_imported_at` not being updated for all orders in a batch, during order sync.
- The external dynamic endpoint for Feefo reviews will now return a 204 No Content if no reviews are found.

# 4.19.4

### Improvements
- There is now a fallback store id set in our OrderSaveAfter observer. [External contribution](https://github.com/dotmailer/dotmailer-magento2-extension/pull/596).

### Bug fixes
- We fixed a PHP error in subscriber sync that occurred if consent data syncing was enabled but no consent text had been entered for either customers or subscribers.

# 4.19.3

### Bug fixes
- We fixed an error in the ROI block that occurred if an order had no grand total.

# 4.19.2

### Improvements
- Guest sync will now not sync contacts with a customer id.
- We are now using factories when updating data fields in Abandoned Cart and Automation syncs.

### Bug fixes
- We fixed an issue with sending incorrect data field values when syncing abandoned cart automations.
- We were fetching the program id for new wishlist automation using an incorrect method; this has been fixed.

# 4.19.1

### Bug fixes
- The 'API valid' check in the Dotdigital dashboard now respects the current website scope.
- We fixed a bug with running catalog sync at Default Level.
- Some single imports were (incorrectly) reporting as Failed / Error Unknown. This has been fixed.
- We reverted a change to the order insight data schema that was blocking order imports for some merchants.
- We fixed a PHP 8.1 compatibility error when following the 'Take me to my basket' link from the Cart Content External Dynamic URL.
- Empty address 'street' values would throw a PHP 8.1 deprecation exception in `explode()`; this has been fixed.
- Exclusion rule conditions on values from product attributes with the input type of select or multiple select now function correctly.

# 4.19.0

### What's new
- Customer, Subscriber and Guest syncs have been refactored for speed and efficiency.

### Bug fixes
- We've replaced the requirement for Symfony\Component\HttpFoundation in our Server Sent Events helper.
- We fixed the UpdateApiEndpointPath data patch for merchants with old and new endpoint paths stored in the same scope.

# 4.18.2

### Improvements
- Product thumbnails for configurable products in cart insight data now work as expected even if the parent has customizable options.
- Our resubscribe sync will now handle missing `change_status_at`  values in the Magento subscriber table.
- Disabled account connections will no longer log failed authorization errors.

### Bug fixes
- We fixed a regression in 4.18.0 relating to a change to the API endpoint config path.
- We fixed a bug with some custom fonts not displaying correctly in external dynamic content.

# 4.18.1

### Bug fixes
- Products removed from Magento will now queue a Single_Delete import if they have ever previously been imported.
- Multiple contains / not contains conditions are now supported in an ANY-type exclusion rule.

# 4.18.0

### What's new
- Exported data for order and catalog syncs is now validated for mandatory attributes before being sent to the importer.

### Improvements
- We made some small improvements to our Integration Setup code.
- Our connector now supports all internal (staging) API endpoints, to facilitate QA.

### Bug fixes
- Dummy record data was not being sent to Dotdigital when saving account credentials at website level; this has been fixed.
- We're now try / catching unserialize errors for all types of import.
- We fixed a bug affecting order and catalog syncs, when processing product attributes whose data was stored as nested arrays.
- We fixed a PHP 8.1-related error when passing `null` to `trim()`.
- 'Create In' has been removed from the list of mappable custom attributes ('Store View Name' should be used instead).

# 4.17.0

### What's new
- Merchants can now set up their integration with a single button click.

### Improvements
- The Log Viewer now has better error handling, and will display log contents however small the log size.

### Bug fixes
- We fixed a bug when saving values in **Dotdigital > Transactional Emails** at website or store level.

# 4.16.2

### Bug fixes
- The 'use offset' flag has been removed from the subscriber insert migration type.

# 4.16.1

### Bug fixes
- One of our data migration scripts was inserting duplicate rows for guest purchasers in `email_contact`. This was a regression from 4.15.0.
- We fixed a fault in the importer sync that occurred if the cron schedule for contact sync was set to 'Every 60 Minutes'.
- We fixed a bug with the catalog reset that happens when image type values are changed, in **Dotdigital > Configuration > Image Types**.
- Some stored image types values could cause a blank screen in **Dotdigital > Configuration**; this has been fixed.

# 4.16.0

### What's new
- Support for Multi Source Inventory is now provided via a separate Inventory module.

### Improvements
- Our StockUpdatePlugin now only responds to Web API requests; we have an observer for all other product updates.
- We improved our handling of review data fields in customer sync.
- Our MessagePlugin has been removed, since Dotdigital now supports the required encoding for transactional sends.
- Order sync now only logs when it has batched some orders, or if debug mode is enabled.
- An API space id and token are no longer saved when a Dotdigital trial account is created via the 'chat' route.

### Bug fixes
- We fixed an error when saving image type configurations.
- We added extra checks to prevent ‘undefined index’ errors in the `ddg_automation_resubscribe` cron.
- Product URLs for PWA storefronts were not making use of custom rewrites; these now work by setting an additional config key.

# 4.15.1

### Improvements
- We now prevent cart insight being posted to Dotdigital if any sort of error (including missing product names) is met when preparing the data.

### Bug fixes
- We fixed a bug in our cron offset data patch, which affected merchants with cron timings set to ‘Every 60 Minutes’.

# 4.15.0

### What's new
- Rows in the Import Report that have already been imported can now be reset, either singly or in bulk.

### Improvements
- Order sync performance has been improved. The sync now runs continuously until all orders are processed.
- Insight data imports that are rejected if contacts don’t yet exist in Dotdigital will now be automatically retried, up to two times.
- We've updated our code for compatibility with PHP 8.1.
- Errors relating to unsubscribing contacts who are already suppressed in Dotdigital will now not trigger a System Alert.
- We've improved the performance of the `InsertEmailContactTableCustomerSales` migration type.
- The structure of our data migration code has been refactored to use additional abstract classes.
- PHP 7.2 is now a minimum requirement for running this module.

# 4.14.1

##### Improvements
- We removed some redundant logging for the catalog sync.

##### Bug fixes
- In 4.14.0 `connectorVersion` was missing from integration insight data; this is now fixed.
- We removed a trailing comma in method arguments that broke compilation in PHP 7.1.
- The 'use offset' flag is removed from the customer sales data migration type.

# 4.14.0

##### What's new
- Contacts who resubscribe in Dotdigital will have their subscription status updated in Magento.
- The unsubscribe and resubscribe steps of the subscriber sync have been moved to a separate cron group.
- Guest abandoned baskets are now restored when a guest clicks the ‘Take me to my basket’ link.
- Catalog sync now supports Multi Source Inventory in Magento.
- Merchants can now sync all types of automations by store (previously the sync worked by website). We've modified the code for automation enrolment, including the handling of pending contacts, and updating data fields.
- We have adopted declarative schema for our module's database structure.
- The ‘cleaner’ cron can now be run on demand from the CLI.
- This module has been renamed `dotdigital/dotdigital-magento2-extension`.

##### Improvements
- Catalog sync performance has been improved. The sync now runs continuously until all products are processed.
- Product rows in the Catalog Report can now be set to 'Processed' or 'Not processed' either individually or in bulk.
- The configurable Dotdigital cron jobs are now scheduled using a random offset. 
- Integration insight data has been extended to collect additional dotdigital configuration values, via a weekly cron.
- Data migration can now be performed on a per-table basis via the CLI.
- `setup_version` has been removed from module.xml; in the Dashboard, we now use composer.json to provide the current active module versions.
- We've improved our handling of the contact import report; to be resubscribed, contacts must now be subscribed, and not suppressed.
- Unused Fancybox JS files have been removed.
- We've removed, or updated, various bits of code relating to the Zend Framework.
- Menus and ACL resources are now translatable. [External contribution](https://github.com/dotmailer/dotmailer-magento2-extension/pull/574).
- `ImporterReportHandler` now uses a newer `unsubscribeByWebsiteAndStore()` method, for scope-aware unsubscribes.
- Our `InstallData` and `UpgradeData` scripts have now been removed and replaced with data patches.
- We've updated a regex pattern to allow our extension to use ‘dotdigital.com’ API endpoints in the future.
- We've updated the naming of Dotdigital throughout the connector.
- We've fixed date-based resetting in **Developer > Sync Settings**.
- We’ve streamlined the _Import Report_ (it could fail to load records when some types of filter were set).
- We've removed some repetition when generating the dynamic content passcode.
- We removed some deprecated Logger methods.
- We removed a redundant `curl_setopt` line.
- Our third-party ‘Color Picker’ JS file has been moved from `node_modules` to `lib`.

##### Bug fixes
- We patched a problem relating to logging SMTP failures, affecting Magento versions 2.3.0-2.3.2.
- We fixed a bug with the catalog reset that happens when the Catalog sync image type value is changed, in **Configuration > Image Types**.

# 4.13.8

##### Bug fixes
- We patched a regression in 4.13.7, which caused an exception in the `TransportPlugin`.

# 4.13.7

##### Bug fixes
- We fixed a bug in our `TransportPlugin` that sent duplicate transactional emails when our relay was enabled.

# 4.13.6

##### Improvements
- Menus and ACL resources are now translatable. [External contribution](https://github.com/dotmailer/dotmailer-magento2-extension/pull/574)
- For data migrations using `dotdigital:migrate`, we now won’t override an existing dynamic content passcode.

##### Bug fixes
- The Dotdigital Coupon URL Builder is now useable if Dotdigital is enabled at any level. 
- We fixed a bug that could affect merchants changing their Dotdigital API region.
- Dynamic content styling now uses correctly-scoped style values.
- We are merging child product categories with parent product categories for line items in order insight data.

# 4.13.5

##### Bug fixes
- Contacts enrolled in automations or queued for abandoned cart campaigns, who were initially ‘Pending Opt-in’ but who soon afterwards became unsubscribed or suppressed, were causing an error in the related syncs. We fixed it.
- Pending contacts in dotdigital are no longer marked as ‘Suppressed’.
- We fixed a bug in the order sync related to marking ordered products as unprocessed.
- We’ve improved our handling of missing campaign data in the template sync.

# 4.13.4

###### Bug fixes
- We now send review dates as correctly formatted date strings in review sync, to enable full use of segmentation in dotdigital.
- We fixed a problem with our web behaviour tracking script collecting product data outside of product pages. 

# 4.13.3

###### Bug fixes
- We fixed an exception thrown error during contact import sync.

# 4.13.2

###### Bug fixes
- Compile error for php versions prior to 7.3 has been fixed.

# 4.13.1

###### Bug fixes
- The subscriber sync's unsubscribe step could globally unsubscribe an email address subscribed on multiple websites; it now respects the website scope. 
- Order sync will now not break if product custom options have no `option_id`  key.
- Abandoned cart automation enrolments now function correctly if a time limit is configured, and the same contact drops a cart on multiple websites. 
- Migrating subscribers at install (or `dotdigital:migrate`) could create duplicate contacts instead of updating an existing row; this has been fixed.

# 4.13.0

###### Improvements
- A specific error exception, relating to transactional email sends to invalid email addresses, will no longer be surfaced in system messages.

###### Bug fixes
- We've added a _shipping_ property to cart insight data.
- Subscriber automation was failing for contacts who enrolled across multiple websites; this has been fixed.
- We've fixed a problem with automapping data fields if the connector wasn't enabled at default level.
- We added a workaround for displaying configurable products with customizable options in cart insight data.
- We've added try/catch to all the times we call `quote→getAllItems()` in our code.
- We've fixed a problem with mismatching types when syncing custom customer attributes.
- Catalog insight data now sends a correctly-formatted date string for date-type product attributes.

# 4.12.0

###### What's new
- This release brings support for merchants running Magento with **Customers > Customer Configuration > Account Sharing Options > Share Customer Accounts** set to 'Global'.
- We've added an extra configuration field (in **Developer > PWA Settings**) to allow merchants running a PWA storefront to define a separate storefront url. This can be used to re-route the 'Take me to my basket' link in the basket EDC and the ‘Leave a review’ link in the review EDC.
- Product prices in cart, catalog and web insight data now have separate figures that include tax. 

###### Improvements
- ‘Leave a review’ links in the Reviews EDC can now link to product pages instead of review pages. Configurable via **Stores > Configuration > dotdigital > Automation > Review Settings**.
- We’ve updated the handling of coupon codes to better enable recurring campaigns.  
- Line items in cart insight data now display prices in the quote currency, rather than the website’s base currency.
- We’ve added error handling when calling `$order→getAllItems()`. This protects order and contact syncs against problems related to legacy product option data.
- Custom OAuth domains configured in **Stores > Configuration > dotdigital > Developer** must now start 'https://'.

###### Bug fixes
- We fixed a problem with creating address books in the correct admin scope.

# 4.11.4

###### What's new
- We’ve updated the default list of IP addresses for **Developer > Dynamic Pages IP Restriction**. 

###### Bug fixes
- We fixed a bug in order sync where the current date was returned for date-type order custom attributes with a `null` value.

# 4.11.3

###### Bug fixes
- We fixed an admin user permissions problem, caused by our ParentFinder class when saving products.  

# 4.11.2

###### Bug fixes
- We've replaced the `ItemProductResolver` class constants in our `ImageFinder` class with older constants, for compatibility with Magento v2.2.0 to v2.2.6.

# 4.11.1

###### Bug fixes
- We fixed a bug which could cause incorrect prices for simple and grouped products in order insight data. 
- Some client methods were generating `Apiconnector Client` warning and error logs, inaccurately. This has been fixed.
- The duplicated column for _Import Started_ in the Import Report has been amended.

# 4.11.0

###### What's new
- We have a new feature that enables merchants to choose which image size they prefer to use for different contexts in Magento. Configurable via **Stores > Configuration > dotdigital > Configuration > Image Types**.

###### Improvements
- We’ve added the `email_coupon_attribute` table to the list of tables to drop in the module's `Uninstall` class.
- We removed some unnecessary configuration steps from our install script. 
- The UI component XML for our report grids has been restructured in line with Magento’s latest standards. 
- We removed a legacy plugin for handling 'most viewed' product report data.
- We removed some unused methods relating to fetching a customer’s last order id and last quote id.

###### Bug fixes
- Wishlist insight data was showing the wrong updated at date, and modified wishlists were syncing without product data. Both these problems have been addressed. This release simplifies wishlist sync by removing 'single' wishlist imports, and handling all required updates in bulk.
- We made a small change to improve our error handling when retrieving lists of programs from Engagement Cloud.
- The OAuth Connect button in **Stores > Configuration > dotdigital > Developer > OAuth Credentials** is now fixed.
- We fixed a regression introduced in 4.5.1, which excluded bundle products with dynamic SKUs from cart insight data.
- Customers who subscribe during registration, but need to confirm their subscription, will now be added to a subscriber automation, if configured.
- Failed cURL requests will now always return an object.
- Data field mappings are now retrieved in website scope, instead of store scope.
- We now fetch values for _Disable Customer Success_ and _Disable Newsletter Success_ in the correct (website) scope. 

# 4.9.3

###### Bug fixes
- We've changed our code to fetch data fields in website (not store) scope, to match the `system.xml` configuration.

# 4.9.2

###### Improvements
- We've swapped `TimezoneInterface` for `DateTime` when setting the wishlist `updatedAt` date in the wishlist sync. This appeared to be causing erratic data in some versions of Magento.

###### Bug fixes
- We’ve added caching to our coupon block. This fixes a bug that prevented coupon code generation in some setups.
- We now respect the configuration of Stores > Configuration > Sales > Checkout > Grouped Product Image, and retrieve the parent grouped product image for cart insight data, where necessary.
- Our cart insight dummy data schema has been updated to use the correct data types.
- The currency code in order insight data now indicates the store currency, in line with the synced prices.

# 4.9.1

###### Bug fixes
- The value synced in the store name column in guest sync is now the store view name instead of the website name. We are now syncing website name, store name and store view name in guest sync - all with the correct values.
- We fixed a problem that prevented contacts subscribing to multiple stores in the same subscriber sync.
- We added an array check before looping over order status automations after an order is saved.
- We adjusted the module's dependency on `magento/module-authorization`; this fixes composer upgrades for merchants on Magento 2.4.
- We’ve added try/catch to methods in the `Serialized` backend model. This swallows exceptions thrown by unreadable data stored for any field in our configuration that stores data in JSON format.  

# 4.9.0

###### What’s new
- System alerts now report on transactional email send failures.
- In-app system messages for system alerts are now enabled by default.
- dotdigital forms embedded in CMS pages and blocks can now add email addresses to Magento’s newsletter subscribers list. [requires Enterprise module 1.2.0+]

###### Improvements
- Cart insight data is now sent for the Engagement Cloud account owner’s email address when validating API credentials, and at upgrade. This allows the Abandoned cart block to be visible in EasyEditor before customer data is received.
- All active dotdigital modules are now displayed with their version numbers in the Email module dashboard (Reports > Customer Engagement > Dashboard).
- Easy Email Capture for newsletter subscriptions now happens on the server side after submit (this supports our forms integration for Page Builder).
- Programs listed in dotdigital > Abandoned Carts > Abandoned Cart Program now list all programs, even those whose status is Draft or Deactivated. 
- We've added a helper method to check if Magento has an active Engagement Cloud account enabled at any level.
- The timing for the automation sync has been changed to every 16 minutes, so it coincides less often with the contact sync.
- We’ve fixed a small omission in the install schema script, by adding a `nullable` property to the `last_subscribed_at`  column in `email_contact`.
- Module dependencies have been updated in line with Magento 2.4 requirements.
- Our unit tests have been updated to be compatible with PHPUnit v9+. This change was required for Magento 2.4.
- Our MFTF test suite has been updated.

###### Bug fixes
- We fixed a problem with finding the wrong product IDs from a batch of SKUs, when importing or updating products in bulk.
- All types of tracking (Page, ROI and Web Behaviour) now function at website level if tracking is disabled at default level.
- Newsletter preferences captured in date format are now synced to Engagement Cloud correctly for non-UTC locales.

# 4.7.1

###### Bug fixes
- We fixed a problem with unserialize exception errors when processing `product_options` for order items in order sync.

# 4.7.0

###### Improvements
- Our composer dependencies have been updated to support Magento 2.4.
- Our MFTF tests have been updated to be compatible with v3.0.0 of the Magento Functional Testing Framework.

###### Bug fixes
- We've fixed empty product categories in web insight data.
- Web Behaviour Tracking was not working for some merchants with certain theme configurations; we’ve added a fallback selector to fix this.
- The subscriber status data field could have an empty value when customer sync was run using cron. We fixed this using App Emulation.
- We've fixed an issue with address book mapping if a dotdigital account is enabled at default level but disabled for the main website.

# 4.5.7

###### Bug fixes
- We fixed a problem with order sync breaking if an order contained product SKUs that no longer exist in the catalog.

# 4.5.6

###### Improvements
- We are moving the `ddg_automation_abandonedcarts` cron job to its own cron group, in order to protect it from scheduling delays caused by over-running sync jobs. 
- We've added a Content Security Policy whitelist for specific domains used by this module.

# 4.5.5

###### Improvements
- We've improved the performance of catalog sync by optimising our `StockFinder` class. Batches of configurable products are now processed up to 25% quicker.
- The timing for the automation sync has been changed to every 16 minutes, so it coincides less often with the contact sync.

###### Bug fixes
- We've fixed a regression introduced in 4.5.3, relating to using a magic method to obtain the subscriber status when preparing subscriber export.
- The total figure for synced subscribers, presented in the logs and on screen, is now correctly calculated.

# 4.5.4

###### Improvements
- We're revising the order insight data schema to show bundle products as distinct items with their child components listed as `sub_items`.
- For system alerts relating to pending automations, we now limit these to automations that have been pending for longer than an hour, but whose created date still falls within the alert time window.

###### Bug fixes
- Coupons are now generated (using the external dynamic content URL for coupon generation) for email addresses containing plus ('+') signs.
- We fixed an issue with contacts being resubscribed even if their `last_subscribed_at` value was `null`.
- System alert email notifications now work as expected in Magento 2.2.
- We've fixed an upgrade error (dating from 4.5.2) affecting Magento versions 2.2.0 - 2.2.4.

# 4.5.3

###### Improvements
- The _Subscriber Status_ data field is now synced during all types of subscriber sync.  

###### Bug fixes
- We fixed an error relating to type-hinting, introduced with our system alerts feature in 4.5.2.
- We've restored success messages missing from the admin screen after running specific syncs in **dotdigital > Developer** (orders, reviews, contacts and subscribers).  

# 4.5.2

###### What's new
- We have introduced diagnostic system alerts via two channels: in-app system messages and email notifications. At this stage these are set to disabled by default.

###### Bug fixes
- Data migration now functions correctly in a split-database setup.
- We’ve improved the way we parse Engagement Cloud contact import report faults.
- Deletion of automation enrolments and abandoned carts from their respective report grids now works as expected.
- Automations with the status _Cancelled_ are now labelled as such in the Automation Report.
- We’ve improved our handling of the API response we receive when processing resubscribes.
- We resolved some access control issues relating to non-admin user accounts.
- We removed some excessive logging from the `Cron` class and catalog sync.
- Deprecated `imported` and `modified` columns are now dropped from the `email_catalog` table.
- We’ve fixed a possible insight data error by ensuring website name defaults to string in catalog sync.
- We’re catching exceptions thrown by `unserialize()` to protect against unserialisable data stored for custom attributes.
- Our syntax for `where` clauses has been updated to use question mark placeholders.
- The configurable product thumbnail used in cart insight data when the cart image is set to be _Product Thumbnail Itself_ now uses the correct store scope. 

# 4.5.1

###### What’s new
- Customer attribute values captured via any input type (dropdown, multiple select, etc.) are now correctly synced as data fields.
- We're now handling an exception thrown if the API user is locked when mapping data fields.
- Security is improved for SMTP configuration; SMTP host is now set via a dropdown list of options.  
- Cart insight data is now sent for all active quotes, even if they have no items. This allows merchants to exit contacts from a program if they empty their cart.
- Merchants can now sync website name, store name and store view name via individual data fields.
- Wishlist, Review and Order syncs now look up the transactional data sync limit once, prior to looping over websites.
- Logging output from the `Client` class has been improved, and is now consistent across all methods in the API wrapper. 

###### Bug fixes
- We fixed the exception thrown when trying to fetch stock during catalog sync.
- Text compression for saved templates is now restored. 
- Unit price for some products (simple, with configurable parents) was 0 in cart insight data; this has been fixed.  
- We improved the way we fetch customer attribute values for data fields; attribute codes containing numbers (for example,  `title_123`) won't now break the contact sync.
- We fixed an issue with inaccurate log output for wishlist sync.
- An invalid return type was breaking web API processing for Swagger; this has been fixed. [External contribution](https://github.com/dotmailer/dotmailer-magento2-extension/pull/558) 

# 4.5.0

###### What's new
- Configurable products now have a stock figure that is the sum of their child products.
- We have added a plugin to detect stock updates that are performed outside of the Magento admin, that is, by third-party code.
- Resource names across all dotdigital admin pages are now consistent. 
- We added MFTF tests for our abandoned cart and review exclusion rules.

###### Bug fixes
- Missing or defunct product attribute sets now won't break the order sync. 
- We added an extra check to our `MessagePlugin`  class to fix a potential `setEncoding` cron error.
- We fixed a bug with the wishlist cron that could result in wishlists syncing without products.
- `Single_Delete` importer jobs would always be marked as _Failed_; this has been fixed.
- We now apply CSS styles to our coupon EDC inline, to ensure they are correctly added in Gmail.

# 4.3.6

###### What's new
- We've made a handful of changes to support our forthcoming B2B module. These are architectural changes that facilitate the creation of plugin code, and have not added new functionality. 
- Codes for campaign tracking and ROI tracking applied to the **Get basket** link in the abandoned cart EDC are now preserved and applied at their destination.
- We've added MFTF tests to test our dashboard and various report layouts.

###### Bug fixes
- Exclusion rules can now have conditions relating to product attribute sets.
- The imported / not imported filter on some of our report grids now functions as expected. 
- We fixed a regression introduced in 4.3.4 that prevented new exclusion rules being saved.
- We now limit product short descriptions to 1000 characters as opposed to 250.
- We've resolved an issue with filtering the _Manage Coupon Codes_ grid of a cart price rule.

# 4.3.5

###### Bug fixes
- We fixed an issue whereby redeeming EDC coupon codes generated in versions prior to 4.3.0, in version 4.3.4, caused a fatal error. 

# 4.3.4

###### What's new
- Individual coupons generated by the external dynamic endpoint can now have expiry dates independent from the sales rule expiry.
- We've improved our integration test coverage with two new test suites for the Importer and Contact syncs.
- We've added MFTF tests for the module when it is initially enabled. 
- Contacts with an invalid store id are now prevented from breaking the sync when we retrieve a store name.
- Subscriber resubscribes that trigger an automated resubscribe email from Engagement Cloud now have the correct _Contact Challenged_ response status marked against the matching importer row. 
- Billing City was missing from the list of mappable fields in **dotdigital > Data Mapping**, and has now been added. 
- We've added guest sync to our list of available `dotdigital` CLI methods.
- Our own custom serializer class is removed in favour of Magento's `SerializerInterface` .
- We've added some code to allow the retrieval of tier prices during catalog sync. This won’t affect this module's functionality; it supports our forthcoming B2B module. 

###### Bug fixes
- We fixed a regression introduced in 4.3.1, which stopped some data fields from being captured when syncing customers, subscribers and guests.
- Deleting customers in the Magento admin was triggering two `Contact_Delete` rows in the importer table. This has been fixed.
- A single unsubscribed, hard bounced, or otherwise rejected contact could prevent a batch of campaign sends going out; this has been fixed.

# 4.3.3

###### What’s new
- We've increased the sync limits for bulk imports into Engagement Cloud. The importer sync now imports up to 100 bulk items on each execution (25 of which can be for contacts), plus up to 100 single items.
- Additional data about the Magento installation is now sent to our sign up microsite when merchants register for Engagement Cloud Chat.  
- When fetching a scoped date of birth, we now use store ID instead of website ID, in line with Magento core. 

###### Bug fixes
- Merchants upgrading from 4.2.0 to 4.3.x now have an `email_coupon_attribute` table created as expected.
- We've fixed a bug in the Web Behavior Tracking script, triggered by empty product descriptions. 
- We've fixed an error when fetching the API endpoint for a given store scope.

# 4.3.2

###### Bug fixes
- We've fixed an issue which caused an order sync failure if an order contains a reference to a product which is no longer in the database.

# 4.3.1

###### What's new
- The classes responsible for syncing contacts and their data fields have been refactored to allow better extensibility by other dotdigital modules.
- We've added the ability to run the wishlist sync from the command line.
- Values entered when adding conditions to exclusion rules (**Marketing > Exclusion Rules**) are now validated both before and after submission.
- The success message displayed after subscriber sync is now more clearer. 
- Two new commands are now available for the `dotdigital` CLI:
    - `dotdigital:connector:enable` can configure and enable a connection to Engagement Cloud.
    - `dotdigital:connector:automap` automaps data fields. 
- A duplicated call to create the `email_coupon_attribute` table has been removed.
- We've added integration test coverage for the review sync.
- _Engagement Cloud_ has been changed to _dotdigital_ in various admin menus. 
- In our upgrade schema script, we've removed a redundant method and added some exception handling when dropping indexes. 
- We've made some improvements to bring our code into closer alignment with Magento coding standards.

###### Bug fixes
- Subscribers confirming their subscription, when **Configuration > Customers > Newsletter > Need to Confirm** is turned on) are now enrolled onto _New Subscriber automations_ as expected. 
- We've amended the data synced for store name in order sync, so that it matches the store view name sent with other sync types.
- We've prevented the date of birth (DOB) data field from syncing today's date if a Magento account holder leaves this field empty.  
- Catalog sync now returns an empty array on failure. The previous void return was generating a warning that Magento logs as a critical error.  
- We fixed a problem where Magento email templates became unmapped when our connector was installed.
- Exception handling has been added to show a warning if the Magento area code has already been set to a mismatching area when running `dotdigital` CLI commands.  

## 4.3.0

###### What’s new
- We now provide Engagement Cloud Chat via a separate Magento module. For upgrade instructions, see [here](https://gist.github.com/sta1r/f22128fc1d37e6f08076ec59cf315724).
- Merchants can now add Engagement Cloud's Web Behavior Tracking in the connector configuration.
- The data sent via our Web Behaviour Tracking script will now include product data and search data where available.
- The insight data schema for orders has changed. Configurable and bundle products no longer output as separate line items; instead, parent data is used to augment child products.
- We've added parent_id to catalog insight data schema. 
- We've added a new tool to improve the way you create dynamic content links for coupon codes. Coupon codes can now be resent to customers if they have not yet been redeemed. 
- We've added the ability to run the review sync from the command line. 
- We've removed the 'Script version' field from Engagement Cloud > Configuration > Tracking. Merchants can still set the config key, if required, via CLI. 
- If subscribers are deleted in Magento, we will now try to update Engagement Cloud only if subscriber sync is enabled.
- We've simplified subscriber sync by sourcing a store id from the store currently being synced, rather than from a Magento table.
- When creating new data fields via **Engagement Cloud > Data Mapping**, we now show an error if your data field name is longer than 20 characters. 
- We have deprecated some of our External Dynamic Content URLs. We’ve added notes on how to replace these in your campaigns.
- Data entered into fieldsets designed to post data direct to Engagement Cloud (i.e. _dynamic datafield_ and _dynamic addressbook_) will now not save any data to the database. 
- We made some changes following a Magento architectural review: `create()` methods have been removed from class constructors, and we’ve added a virtual type to replace an empty block class.
- Changes to our log messages introduced in 3.4.1 have been reverted for now, in preparation for the Magento 2.3.4 patch release.
- `email_catalog` table columns changed in 3.4.2 have been restored as deprecated columns, prior to the 2.3.4 submission.
- We've added extra logic to our SMTP MessagePlugin to set the correct encoding on pre-assembled Zend messages.

###### Bug fixes
- We've fixed a bug affecting Engagement Cloud accounts in 'GMT minus' timezones, where customer birthdays would fall a day early.  
- We fixed a problem with reviews being saved without a valid store id.
- A single suppressed contact could prevent a batch of abandoned cart email sends going out; this has now been fixed.

## 4.2.0

###### Bug fixes
- We now confirm a contact exists in Engagement Cloud before sending _cartInsight_ data during the abandoned cart syncs for campaigns and program enrolment.
- We fixed a problem where catalog sync was breaking while trying to load a non-existent parent product.
- Private marketing preferences are now hidden from the customer, even when nested inside a category.
- We’ve fixed a bug in the order sync that happened if the connector couldn't find the products for an order.

## 4.0.0

###### What’s new
- Our connector now ships with live chat functionality. Existing clients can enable chat via **Engagement Cloud > Chat Settings** to start using this new channel.  
- Merchants can now sync additional campaigns from Engagement Cloud to Magento. This enables merchants to map custom templates to any transactional email in Magento.  
- We’ve improved the way we set _from name_ and _from email_ values during transactional email sends. All versions of Magento 2, including 2.2.8, now send using the friendly from name that is set in Engagement Cloud, and that is stored in the email_template table. 
- Sale price and total price figures in cart insight data now include tax, in line with the prices presented in the basket EDC.  

###### Bug fixes
- We’ve made a small change to our migration scripts to ensure that only wishlists with valid customers are migrated.
- We’ve written a workaround to handle duplicate rows in the `email_contact_consent` table.
- Products without website IDs now won't break the catalog sync.
- We’ve patched a potential cross-site scripting exploit in **Marketing > Exclusion Rules**.
- We’ve updated the way we set a refresh token in our OAuth Connect block, in order to fix a Magento integration test. 
- Subscribers who unsubscribe will now be updated so that their _SUBSCRIBER_STATUS_ data field in Engagement Cloud is set to 'Unsubscribed'.

## 3.4.2

###### What's new
- We've introduced a new _Integrations_ Insight data collection that gathers metadata relating to the Magento installation currently connected to the Engagement Cloud account.
- We've added a _type_ field to the schema for catalog Insight data.
- In catalog sync, child products without an image will now try to use their parent's image.
- We've updated our module dependencies in preparation for the release of Magento 2.3.3.

###### Bug fixes
- We've fixed a bug with catalog sync which prevented a complete sync for merchants syncing a filtered subset of products. Products are now marked as _processed_ even if they are not imported.
- Private marketing preferences will now not be displayed to logged-in subscribers.
- We've fixed an error relating to invalid custom attribute values. 
- We've fixed an issue with the friendly from name not being added to transactional emails in certain Magento versions.
- Date range filters for resetting data in **Developer > Reset Sync Options** have been repaired.
- We've fixed a number of spelling errors in the codebase, including CSS selectors in some view templates. Merchants who have extended or modified our templates should review these before upgrading.

## 3.4.1

###### What's new
- We've improved the consistency of the log messages we output for API connections.
- It's now possible to remove the /pub folder from synced URLs. Do this in Developer > Import Settings of the admin.
- You can now configure abandoned cart campaigns for guests at the store level.

###### Bug fixes
- A missing label was preventing custom attributes from saving updates in Magento 2.3.0 and above. We fixed this.
- Purchased products are now marked as modified. This means the stock level updates the next time the catalog syncs.
- Easy email capture now works correctly at website level. 
- Easy email capture for newsletter sign-ups no longer overrides an email address that is stored against an active quote.

## 3.4.0

###### Improvements 
- Date format columns added to the `sales_order` table will now be treated as ISO 8601 dates when added to Insight Data in Engagement Cloud.
- We have changed the way the Request object is handled outside controller classes, in line with Magento design principles.
- Abandoned cart program enrollment is now handled in batches.
- We have fixed a bug which, in certain conditions, could have resulted in deleted Exclusion Rules.
- We have updated the connector's MFTF tests.
- We have added total price and sale price to Cart Insight data sent to Engagement Cloud.
- We have made the output and log messages consistent across all syncs when they have completed.
- We have updated the connector's logging to use Monolog as per Magento 2 guidelines, and add PSR-3 compliance.
- We have updated template name references to use a canonical format recommended in Magento design.
- We have removed the 'Use default' checkbox from button inputs in the connector config to improve user experience.

###### Fixes
- We have fixed an issue whereby media URLs were prefixed with /pub, causing a 404 error.
- We have fixed an issue with the importer attempting to archive CSV files which had already been removed.
- Product attributes with multiple values which are mapped to data fields in Engagement Cloud are now passed as a comma-separated list, rather than ‘Array’.
- We have fixed an issue with the abandoned cart automation cron not exiting if the cart is empty, which was previously throwing an exception.
- We have updated the Feefo dynamic content block to use the newer JSON API, as the block previously used the discontinued Feefo reviews XML feed.
- The importer sync will no longer process imports for websites configured to have Engagement Cloud integration disabled.
- External dynamic content blocks will now return an HTTP 204 status if no content is available.

## 3.2.4

###### Improvements
- Yes/No data fields defined in Engagement Cloud will now be displayed as option lists in a customer's account.  
- Upon installing our module, both Page Tracking and ROI Tracking will be enabled by default.
- We've improved our automated test coverage.
- We've removed a legacy package.json file.

###### Fixes
- We've modified the 'sent at' date stored when a campaign is sent from Engagement Cloud. We now store the date the campaign was sent from the platform (as opposed to the current time in Magento), in a timezone that matches the store locale. 
- We've repaired a problem affecting order sync if no order attributes had been selected.
- We've fixed a bug with the handling of Abandoned Cart Limits. 
- We've fixed a bug with images not displaying in external dynamic content in older versions of Magento.
- Marketing preference checkbox ID attributes are no longer invalid if their label has more than one word.

## 3.2.3 

###### Fixes
- We've fixed a regression in the constructor of the importer model that was affecting all syncs for older versions of Magento (2.1.x and 2.2.x)
- We've resolved some "mixed content" browser warnings that would occur when using the 'Service Score' dynamics content of the Feefo integration
- An unnecessary dependency on the Magento Sitemap module has now been removed

## 3.2.2

###### Improvements
- When an order is placed following a cart abandonment, we now send a 'cartPhase' flag to Engagement Cloud to enable merchants to exit customers from an abandoned cart program. 
- To prevent connector syncs overwriting opt-in status data set in Engagement Cloud, we now only send opt-in status data if a) Configuration > Customers > Newsletter > Subscription Options > Need to Confirm is set to 'Yes' and b) the subscriber is marked as confirmed in newsletter_subscriber.
- We've updated dynamic content endpoints to ensure that, if a child product is missing an image, we use its parent's image instead.
- We've improved the coverage of catalog sync by allowing selected custom attributes to be included in the synced data.
- We are now cleaning any custom transactional data keys prior to import, removing invalid (non-alphanumeric) characters, but not skipping records as was previously happening.
- The class structure relating to the importer sync has been tidied up, and is now consistent with other 'sync' models.
- We’ve improved security by validating the Engagement Cloud API endpoint value prior to storage.
- The abandoned cart sync can now be run via the dotdigital:sync console command.
- Some legacy configuration code has been removed.
- We’ve renamed some of our observers for clarity.

###### Fixes
- In some situations the ‘Automation settings’ page was not rendering correctly owing to the type of data retrieved from the API; this has been resolved.
- Product data imported as CSV via the System > Import tool will now be added to the email_catalog table and synced in due course
- We’ve fixed a bug that could affect customers resubscribing via Magento; they can now not be accidentally unsubscribed again in a subsequent sync.
- If a contact is already subscribed in Engagement Cloud, and a Subscriber_Resubscribe job is sent for them, we now mark the job with ‘Contact is already subscribed’ as opposed to marking it as failed with the 'Error Unknown' message.
- We’ve repaired some invalid HTML on the ‘Developer settings’ page
- Access control for the abandoned carts report is now consistent with other module-specific views.
- Before the second and third campaign runs in an abandoned cart series, we will now re-confirm that the original quote is still active.
- We’ve fixed an error on the customer preferences page in Magento 2.1.
- We’ve fixed an error with the catalog sync in Magento 2.1.
- We’ve fixed an incorrect node name in the crontab.xml file. [External contribution](https://github.com/dotmailer/dotmailer-magento2-extension/pull/542) 
- We've fixed a bug relating to the display of opt-in status on the user account preferences page.

## 3.2.1

###### Improvements
- We've added the option to use a specific transactional email template for "Reset Password" emails.
- We've clarified the wording beneath the field "Delay Period (Days)" in Engagement Cloud > Automation > Review Settings.

###### Fixes
- We've fixed a problem with scheduled campaign sends, arising from campaigns stuck in a "Processing" state on Engagement Cloud. In such cases, we will expire campaigns that have been "Processing" for longer than two hours. 
- In the settings under Engagement Cloud > Configuration > Transactional Data, we now show all existing order statuses in the select box for "Import Order with Status", rather than a subset.
- We've resolved a problem with special characters not displaying correctly in some transactional email sends. 
- We've fixed a bug affecting upgrades from older versions of the module to version 3.2.0.
- The grand total for an abandoned cart - accessed via a dynamic content page - will now be visible at all screen widths.
- Subscriber data now includes an accurate website_id when migrated using the console command.
- We've removed the stockStateInterface field from synced catalog data.
- We've tidied up the layout of the Feefo Feedback Engine fieldset.
- We've added a fix to catalog sync so that products without a status do not block the process.

## 3.2.0

###### Improvements
- Abandoned carts can now trigger automation program enrolments in Engagement Cloud, in addition to regular campaign sends.
- All abandoned cart flows will now send cartInsight data in advance, to enable use of the abandoned cart block in Engagement Cloud email templates.
- We've made a number of improvements to our catalog sync. The sync has been refactored for speed and efficiency, products of all types now show correct prices, and product URLs are now presented with the correct rewrite rules.
- We resolved a limitation of the Engagement Cloud API that restricted campaign option lists to 1000 campaigns.
- We ensure that re-subscribing subscribers are mapped to the appropriate address book, if one has been set.
- Products presented on the dynamic content page for abandoned carts will now show images that match the customer's original selection.
- We've shipped a new console command that populates SQL tables for this extension, to be used when enabling the module in an established Magento installation.

###### Fixes
- Remote deep-links to saved baskets now resolve correctly after customer login.
- Stock figures will now be accurate for synced products.
- We've fixed a small regression where campaign option lists were displaying campaigns from the wrong account scope.
- We've optimised and strengthened some key security points.

## 3.1.1

###### Improvements:
- We've added additional MFTF tests

###### Fixes:
- We've removed a plugin that wasn't required.
- We've fixed an issue affecting the review sync in the context where rating table used a prefix or suffix

## 3.1.0

###### Improvements
- 'dotmailer' has been renamed to 'dotdigital Engagement Cloud' (see why [here](https://blog.dotdigital.com/the-story-behind-dotdigital/))
- We've added some MTFT tests to cover marketing preferences functionality
- We now correctly escape the iframe URL of the Engagement Cloud page (formerly Automation studio)
- We've added a way to automatically copy the dynamic content URLs in one click

###### Fixes
- Email addresses containing the '+' sign weren't being captured properly by the guest abandoned cart process; this has been fixed
- Restricted IPs are now properly challenged when using the 'Coupon Codes' dynamic content
- We've fixed an issue which caused the custom 'from' address (CFA) from the Engagement Cloud to be wrongly used for all email templates when at least one template was mapped
- We've removed another instance of the legacy serializer class property that was removed in 3.0.3
- We've fixed an issue which caused cancelled campaign sends to get stuck in a "Processing" state in our campaign table
- We've fixed an issue where one suppressed contact could prevent a campaign send going out

## V3.0.3

###### New Features
- The abandoned cart and automation process now benefit from a retry function in case contacts are pending in Engagement Cloud
- We've added support for Marketing preferences in the customer's account dashboard area
- If enabled, we now display the customer consent text in the customer's account dashboard area as the general subscription text.
    
###### Fixes

- We've fixed a styling issue in the Abandoned cart dynamics content that was affecting product name labels
- We've fixed a problem in our coupon code generator plugin which caused the link between a coupon and a rule to be wrong in the database (issue #526)
- When the extension is disabled, auto-generated coupon codes are no longer marked as having been generated by Engagement Cloud (issue #526)

## V3.0.2

###### New Features
- We’ve added compatibility to Magento version 2.3.0
- We now support a split database solution for Magento 2 Commerce editions

###### Improvements
- We now surface all the first and last purchase categories in customers' sales data fields

###### Fixes
- We’ve fixed an issue that caused an error in the checkout page when page tracking and JS minification was enabled
- When transactional emails were set up on two separate stores, emails weren't being sent with the correct sender details from their respective stores; they are now
- We've fixed an issue which caused products to have incorrect URLs when the catalog was synced at store level
- We now ignore any product attributes that don't match our transactional data key requirements
- Saving Engagement Cloud email template settings with no changes would reset any previously configured email to the default templates; this has now been fixed
- We’ve fixed the signature of our MessageInterface plugin to allow custom implementation
- The importer no longer fails to reset/resend contact imports (including archived folders)
- We’ve reduced the performance degradation for wishlists when the extension isn’t enabled
- We’ve fixed an issue that caused non-opted-in customers to be sent abandoned cart emails regardless of the global sync settings
- We’ve fixed an issue whereby the automation cron would attempt to enrol on the wrong Engagement Cloud account in a multi-website context


## V3.0.1
This version has been released in Magento's own repository and is available within [Magento 2.3.0](https://devdocs.magento.com/availability.html).

## V3.0.0
This version has been released in Magento's own repository and is available within [Magento 2.3.0 beta](https://devdocs.magento.com/availability.html).

## V2.5.4

###### Fixes
- We've fixed an error which, depending on the PHP setting could become an exception and cause the importer process to be stuck

## V2.5.3

###### Improvements
- We've implemented prevention against cross-site scripting in the TrialController.php
- We've implemented an improved retry process after a failed attempt to access EDC

###### Fixes
- ROI reporting is working again 
- We've fixed an error that was being caused by the importer 

## V2.5.2

###### New Features
- You're now able to record your customers and guests' consent and store it using Engagement Cloud's new ConsentInsight

## V2.5.1

###### Improvements
- Users can now import only those Magento contacts who've opted-in (customer subscribers, guest subscribers, and other subscribers)
- Users now get warned when they're about to sync non-subscribers into their Engagement Cloud account

###### Fixes
- We've fixed the catalog sync so it now syncs all products across all created collections when it's configured to sync on store level 
- We’ve changed validation for new subscribers so that it's no longer possible for them to get enrolled multiple times into the new subscriber program
- We've fixed occurrences of unexpected errors during subscriber and/or customer creation

## V2.5.0

###### Improvements:
 - We've added a new option in 'Configuration' > 'Abandoned Carts' that allows to send abandoned cart emails to subscribed contacts only. On fresh installation contacts who haven't opted in will no longer be included.
 - We've added a new option in 'Automation' > 'Review Settings' that allows to send review reminder emails to subscribed contacts only. On fresh installation contacts who haven't opted in will no longer be included.

## V2.4.9

###### Fixes
- We've fixed a dependency issue which prevented composer from installing the extension

## V2.4.8

###### Improvements
- We now import new subscribers with the correct opt-in type (single or double) depending upon Magento's "Need to confirm" setting
- We've changed the observed wishlist events to comply with upcoming Magento versions
- We no longer load any scripts on Magento's customer view when the tracking settings are disabled


###### Fixes
- We've fixed a regression issue which caused the second and third abandoned cart emails to be skipped
- Some products with individual visibilities were getting ignored by the importer; this has been fixed
- We've fixed a configuration issue which caused the transactional email settings to be enabled by default
- As a security update, we now provide the correct public IP as part of the request when creating a new trial account from the extension
- Campaign and program names with special characters would display incorrectly in the Magento store configuration; this has been fixed

## V2.4.7

###### Fixes
- We've fixed a regression whereby the email from address was empty in the message object when email templates were not mapped in the extension
- We've fixed a compatibility issue which caused fatal email template errors when using Magento 2.1.9 and below with the version 2.4.4 of the extension
- An error would occur while sending registration email to customer created via script in Magento 2.2.3 - this is no longer happens [#13888](https://github.com/magento/magento2/issues/13888)
- We've fixed an issue which caused any default email template settings to not display properly in the configuration panel

## V2.4.5

###### Improvements
- We've done some code refactoring to comply with Magento extension best practices
- On installation, we now auto generate a unique secret key used to access extension dynamic content
- We've changed our dynamic content blocks to be non-cacheable


###### Fixes
- We've fixed an issue in the upgrade script whereby the abandoned cart report table was not created for version 2.3.8
- Page Tracking data wasn't sent to Engagement Cloud accounts located in region 2 or 3 ; this is now fixed 
- The abandoned cart process wasn't stop when all items had been removed from the cart ; this no longer happens
- We've fixed some issues related to saving the "Customer Addressbook Preference" setting (#501, #502)

## V2.4.4

###### New Features
- Transactional email templates: You're now able to create, edit, translate and test Magento transactional emails in Engagement Cloud and map them at default, website or store level.
- Transactional email settings can now be set at the website level

###### Improvements
- We've improved the password encryption using Magento's encryption framework

###### Fixes
- We've fixed an issue related to email capture causing an infinite loop during checkout when form field is auto filled by browser
- Date type attributes in transactional data were using the wrong locale time;this is now fixed
- We've fixed an error related to importing orders having both virtual and physical products
- In the case where Magento's double opt-in setting ('Need to confirm') was enabled, we used to import subscribers before they confirmed; this is now fixed
- We now update send status in the email campaign report which prevents the processing of them multiple times
- Unset attributes are now excluded from the imported record for all transactional entities
- The update of the last quote ID from the abandoned cart cron has been moved to campaign cron
- The importer didn't have any transactional data limit set for the initial sync upon a fresh installation; we now set a default limit of 50
- An exception would occur when clicking on the 'Link to cart' in the abandoned cart external dynamic content block; this has been fixed
- We've corrected some typos in the abandoned cart report section
- We've fixed an uncaught JS error that would occur when using ad blocking extensions
- When 'Developer' > 'Debug Mode' is enabled, we now set a default threshold value and only log any API calls that take longer than 60 seconds
- An exception would occur because the subscriber cron job was attempting to initialise the API client without checking whether it was enabled; this no longer happens #495

## V2.4.3
- Disabling a module using a command line would generate different dependency errors related to a non-existing class - we've now fixed this problem

## V2.4.2
- We've fixed an issue which caused the importer to process Subscriber_Resubscribed records multiple times

## V2.4.1
- We've fixed an issue which caused duplicate campaign sends to go out
- We've fixed an issue which caused subscribers to not be marked as imported
- We've fixed an issue which caused entries in our email_contact table to not be correctly marked as subscribers on installation of the module.

## V2.4.0

###### Improvements
- We’ve introduced new validation when deleting cron job CSV files
- Subscriber's sales data is now synced only if the sales data option is enabled in config

###### Fixes
- We’ve removed the MailCheck feature
- We’ve fixed an encoding issue for the product short description
- We’ve fixed generating a token for an admin user
- Magento guests are now getting imported into the 'Guests' address book in Engagement Cloud as expected
- Counts of imported catalogs and reviews weren’t changing in Engagement Cloud when products and reviews were deleted in Magento; they are now
- Subscribers are now added to the ‘Subscribers’ address book when they resubscribe
- Wishlists weren’t batching; this has been fixed
- We’ve implemented a fix to prevent duplications for first customer order automation programs
- We’ve implemented a fix to prevent duplicate cron jobs running at the same time
- We now use getStatuses to prevent errors on upgrade to v2.2.2
- We’ve fixed an importer error when a contact doesn’t have an ID; they now register with the importer after they’re saved and will have an ID
- We’ve fixed single deletes for wishlists when adding or removing products to a wishlist
- We’ve fixed duplication of emails, including those sent when updating the guest on an order sync
- Using a table prefix for the cleaner cron job now works; previously it was showing the error that the table didn’t exist
- We now force the default type for data field values to match the type
- A customer’s import status wasn’t getting reset after making an order; it is now
- We’ve fixed the process for abandoned carts when the first one is disabled
- It wasn’t possible to save a condition in exclusion rules in Magento lower than v2.1.6
- Misconfigured stores can no longer cause email capture scripts to make POST requests over an incorrect HTTP protocol; it’s now made over HTTPS
- When saving API credentials, the value in config for connector/API/endpoint was getting saved for ‘website’ scope and not ‘default’ scope; this is no longer the case
- Subscriber status wasn’t getting updated in Magento when changed in Engagement Cloud; it is now
- Catalog report mass delete action no longer shows an exception.
- An expiry days value of ‘0’ in the external dynamic content coupon code URL would set the coupon code’s expiration date and time to the coupon code’s creation date and time; this has been fixed 
- We've fixed various typos throughout the extension UI
- A success message is now displayed when clicking Disconnect OAUTH credentials successfully
- We've fixed the way OAUTH refresh token was stored and used for the Automation Studio

## V2.3.7

###### Fixes
- We've fixed an error related to a column not found that could occur when trying to sync subscribers with sales data
- Abandoned cart table couldn't be found when upgrading from 2.3.5 to 2.3.6 - this is now fixed.

## V2.3.6

###### Fixes
- It was possible for guests and customers to receive duplicate abandoned cart emails; this has been resolved.
- EE data can now be synced.
- An expiry days value of ‘0’ in the external dynamic content coupon code URL would set the coupon code’s expiration date and time to the coupon code’s creation date and time; this has been fixed.
- Subscriber and customer sales data fields no longer get incorrectly synced when multiple store views exist under a single website.
- Custom product attributes created by another extension no longer cause order syncs to fail.

## V2.3.5

###### Fixes
- Errors would occur when trying to run contacts and orders sync with a database using table prefix - this has now been fixed.

## V2.3.4

- Customer sales data fields could get mixed up when multiple store views existed under a single website; this has been fixed.
- An error would occur due to the attempted retrieval of a non-object in the newsletter subscription section; this no longer happens.
- Most viewed Dynamic Content used to only return products with default attributes - it now also includes products with custom attribute sets.

## V2.3.0

###### Improvements
 - We've done a large amount of code refactoring and have implemented main Magento extension best practices.
 
###### Fixes 
 - The order sync no longer gets stuck due to missing additional info that’s required.
 - Coupon codes no longer expire after an hour despite the expiration being set beyond an hour.
 - We’ve fixed the response that’s returned when Feefo authorisation fails.
 - Security has been enhanced for external dynamic content so that links typecast the expected input.
 - Support is no longer provided for PHP 5.5.
 - The cron default config for orders has been fixed.
 - JavaScript ‘DOM ready’ fixes have been implemented for easy email capture, MailCheck, ROI and page tracking code, fancyBox and log viewer.
 - A problem with disabling the customer registration email has been fixed.
 - As a security update, we’ve removed usage of ‘serialize/unserialize’ and using json_encode/json_decode instead.
 - A security update has been implemented for the permission in var/export/email and /var/export/email/archive folders, plus usage of ‘umask’ has been removed.
 - The transport file from the Zend Mail library is now compatible with Magento 2.2.
 - Foreign keys have been added to the email_catalog table’s catalog_product_entity, and to email_order table’s sales_order.
  
## V2.2.1

###### Fixes

 - We've fixed a styling issue that was not visible in the trial version pop-up window.
 - We've fixed a problem that had been adversely affecting the exclusion rules report table upon execution of a mass delete action.
 - We've refactored the code for trial accounts.
 - A fix has been implemented to prevent problems that were being experienced with OAUTH redirections.
 - Changes in 'Subscriber' status weren't being sent back to Magento from dotmailer; this has been fixed.
 - ‘First customer order’ automation programs were incorrectly firing more than once for customers; this no longer happens
 - Email capturing has been fixed to observe input for the entry fields; previously this hadn't been working as expected.
 
## V2.2.0

###### Improvements
 - Bulk order sync will have a delay(60min) before being imported.
 - Improve install script for customers that are subscribed.

###### Fixes
 - Subscribers with datafields issues.
 - Campaign bulk setProcessing array conversion.
 - Abandoned cart price fetch from quote.
 - Send id is set for all failed campaign records.
 - Abandoned cart with wrong email contact_id.
 - Update Product/Orders transactional data schema.
 - Trail account creation process refactoring and tests.
 - Fix automap datafields for different websites.
 - Api endpoint for multiple accounts. 
 - Easy email capture not updating is_guest field.
 - Abandoned carts time issue.
 - Single orders inside importer will have full data object.
 - Importer fix for delete contact type.
 - Check for api enabled before creating contact.
 - Subscriber guest is not triggered to be removed.
 - Customer subscription when email is changed.
 - Unsubscribe subscribers not getting removed from the address book.
 - Contact subscription status not being changed.
 - Massdelete action for report tables when select all.
 - Automation report table status not displayed.
 - Importer status is saved in wrong column.
 - Newsletter subscription with default option selected will make api call.
 - Contact saving suppression is using wrong key.
 - Revert guest finding feature.
 - Duplicate guests email fix.
 - Importer with no website id for orders.
 
## V2.1.8

###### Fixes
  - Storename fix(#215)

## V2.1.7

###### Features
 - Api endpoints for different region accounts.
 - Improved trail account creation process.
 - Improved Guests finding(separated from the order sync).
 
###### Fixes
 - Abandoned cart process throws error(collection).
 - Single orders type inside importer.
 - Abandoned carts timezone issue.
 - Order sync can have duplicate emails for guests.
 - Cannot automap(button) when using different websites.
 - Failed send campaign in bulk containing duplicated contact_ids(dotmailer @AndrewGretton).
 - Campaign report messages/sendId for suppressed/failed contacts.

## V2.1.6

###### Fixes
 - Syntax error or access violation while updating Coupon code.
 - Stop emulation when api is disabled.  
 
## V2.1.5

###### Features
 - Admin Log file viewer.
 - Subscribers now have datafields.
 
######Fixes
 - ACL for reports menu items.
 - API credentials always for default level.
 - Compatibility _getChildrenElementsHtml with 2.1.3.
 - Unrecognized getFirstItem for last review date.
 - Go to cart button should redirect to quote's store.
 - Get addressBooks using the website level credentials.
 - DI when initializing Zend_Mail_Transport_Sendmail.
 - Fix js for the dotmailer configuration page.
 - Unserialize error for orders sync. 
 
###### Security
 - Remove modification for guest quote items.
 
###### Improvements 
 - Now can be added multiple dimension condition for Rules.

## V2.1.4
###### Bugs Fixes
 - InvalidContactIdentifier for single orders.
 - Compatibility with catalogStaging.
 - Fix admin configuration fancybox error.

## V2.1.0
###### Features
 - Compatible with Magento 2.1 version.
 - Coupon EDC expiration date. You can set the expiration date for coupon included into the URL
 - Improve finding guests. Guest will be added in bulk to the table.
 - Add new automation for first customer order event.
 - EDC include all product types to have an image and include the price range available for the product.   

###### Fixes
 - EDC fixed the prefix for table names.
 - Fix unsubscribeEmail register already exists.
 - New installation do not get the customers mark as subscribers.
 - Automation program enrollment without unserialized is failing.
 - Exclusion Rules conditional mapping fix.
 - Fix the case sensitive namespace.
 - Wishlist not batching.

###### Improvements
 - Allow to include Order multiselect attributes.
