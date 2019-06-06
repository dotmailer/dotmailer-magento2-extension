dotdigital Engagement Cloud (formerly dotmailer) for Magento 2
 ======
 
[![license](https://img.shields.io/github/license/mashape/apistatus.svg)](LICENSE.md)

## Description

Full support documentation and setup guides available [here](https://support.dotdigital.com/hc/en-gb/sections/360000722900-Engagement-Cloud-for-Magento-2)

=======
## Contribution

You are welcome to contribute to Engagement Cloud for Magento! You can either:
- Report a bug: create a [GitHub issue](https://github.com/dotmailer/dotmailer-magento2-extension/issues/new) including description, repro steps, Magento and extension version numbers
- Fix a bug: please clone and use our [Develop branch](https://github.com/dotmailer/dotmailer-magento2-extension/tree/develop) to submit your Pull Request
- Request a feature on our [roadmap](https://roadmap.dotdigital.com)

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
- Malconfigured stores can no longer cause email capture scripts to make POST requests over an incorrect HTTP protocol; it’s now made over HTTPS
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
 - Campaign bulk setProccessing array conversion.
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
 - Campaign report messages/sendId for suppresed/failed contacts.

## V2.1.6

###### Fixes
 - Syntax error or access violation while updating Coupon code.
 - Stop emulation when api is disabled.  
 
## V2.1.5

###### Features
 - Admin Log file viewer.
 - Subsribers now have datafields.
 
######Fixes
 - ACL for reports menu items.
 - API creds always for default level.
 - Compatibility _getChildrenElementsHtml with 2.1.3.
 - Unrecognized getFirstItem for last review date.
 - Go to cart button should redirect to quote's store.
 - Get addressBooks using the website level creds.
 - DI when initializing Zend_Mail_Transport_Sendmail.
 - Fix js for the dotmailer configuration page.
 - Unserialize error for orders sync. 
 
###### Security
 - Remove modification for guest quote items.
 
###### Improvements 
 - Now can be added multiple dimention condition for Rules.

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
 - EDC include all product types to have an image and inlcude the price range available for the product.   

###### Fixes
 - EDC fixed the prefix for table names.
 - Fix unsubscribeEmail register already exists.
 - New installation do not get the customers mark as subscribers.
 - Automation program enrollment without unserialized is failing.
 - Exclution Rules conditional mapping fix.
 - Fix the case sensitive namespace.
 - Wishlist not batching.

###### Improvements
 - Allow to include Order multiselect attributes. 
