 dotmailer for Magento 2
 ======
 
[![license](https://img.shields.io/github/license/mashape/apistatus.svg)](LICENSE.md)

## Description

Full support documentation and setup guides available here - https://support.dotmailer.com/hc/en-gb/categories/202610368-Magento

=======
## Contribution

You are welcome to contribute to dotmailer for Magento! You can either:
- Report a bug: create a [GitHub issue](https://github.com/dotmailer/dotmailer-magento2-extension/issues/new) including description, repro steps, Magento and extension version numbers
- Fix a bug: please clone and use our [Testing branch](https://github.com/dotmailer/dotmailer-magento2-extension/tree/testing) to submit your Pull Request
- Request a feature on our [community forum](https://support.dotmailer.com/hc/en-gb/community/topics/200432508-Feedback-and-feature-requests)

## V2.3.7

###### Bug fix
- We've fixed an error related to a column not found that could occur when trying to sync subscribers with sales data
- Abandoned cart table couldn't be found when upgrading from 2.3.5 to 2.3.6 - this is now fixed.

## V2.3.6

###### Bug fix
- It was possible for guests and customers to receive duplicate abandoned cart emails; this has been resolved.
- EE data can now be synced.
- An expiry days value of ‘0’ in the external dynamic content coupon code URL would set the coupon code’s expiration date and time to the coupon code’s creation date and time; this has been fixed.
- Subscriber and customer sales data fields no longer get incorrectly synced when multiple store views exist under a single website.
- Custom product attributes created by another extension no longer cause order syncs to fail.

## V2.3.5

###### Bug fix
- Errors would occur when trying to run contacts and orders sync with a database using table prefix - this has now been fixed.

## V2.3.4

- Customer sales data fields could get mixed up when multiple store views existed under a single website; this has been fixed.
- An error would occur due to the attempted retrieval of a non-object in the newsletter subscription section; this no longer happens.
- Most viewed Dynamic Content used to only return products with default attributes - it now also includes products with custom attribute sets.

## V2.3.0

###### Improvements
 - We've done a large amount of code refactoring and have implemented main Magento extension best practices.
 
###### Bug fixes 
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

###### Bug fixes

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

###### Bug fixes
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

###### Bug fixes
  - Storename fix(#215)

## V2.1.7

###### Features
 - Api endpoints for different region accounts.
 - Improved trail account creation process.
 - Improved Guests finding(separated from the order sync).
 
###### Bug fixes
 - Abandoned cart process throws error(collection).
 - Single orders type inside importer.
 - Abandoned carts timezone issue.
 - Order sync can have duplicate emails for guests.
 - Cannot automap(button) when using different websites.
 - Failed send campaign in bulk containing duplicated contact_ids(dotmailer @AndrewGretton).
 - Campaign report messages/sendId for suppresed/failed contacts.

## V2.1.6

###### Bug fixes
 - Syntax error or access violation while updating Coupon code.
 - Stop emulation when api is disabled.  
 
## V2.1.5

###### Features
 - Admin Log file viewer.
 - Subsribers now have datafields.
 
######Bug fixes
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
###### Bugs fixes
 - InvalidContactIdentifier for single orders.
 - Compatibility with catalogStaging for enterprise.
 - Fix admin configuration fancybox error.

## V2.1.0
###### Features
 - Compatible with Magento 2.1 version.
 - Coupon EDC expiration date. You can set the expiration date for coupon included into the URL
 - Improve finding guests. Guest will be added in bulk to the table.
 - Add new automation for first customer order event.
 - EDC include all product types to have an image and inlcude the price range available for the product.   

###### Bug fixes
 - EDC fixed the prefix for table names.
 - Fix unsubscribeEmail register already exists.
 - New installation do not get the customers mark as subscribers.
 - Automation program enrollment without unserialized is failing.
 - Exclution Rules conditional mapping fix.
 - Fix the case sensitive namespace.
 - Wishlist not batching.

###### Improvements
 - Allow to include Order multiselect attributes. 