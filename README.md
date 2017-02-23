 dotmailer for Magento 2
 ======
 
[![license](https://img.shields.io/github/license/mashape/apistatus.svg)](LICENSE.md)
[![Build Status](https://travis-ci.org/dotmailer/dotmailer-magento2-extension.svg?branch=master)](dotmailer/dotmailer-magento2-extension)

## Description

Full support documentation and setup guides available here - https://support.dotmailer.com/hc/en-gb/categories/202610368-Magento

##V2.1.7

######Features
 - Api endpoints for different region accounts.
 - Improved trail account creation process.
 - Improved Guests finding(separated from the order sync).
 
######Bug fixes
 - Abandoned cart process throws error(collection).
 - Single orders type inside importer.
 - Abandoned carts timezone issue.
 - Order sync can have duplicate emails for guests.
 - Cannot automap(button) when using different websites.
 - Failed send campaign in bulk containing duplicated contact_ids(dotmailer @AndrewGretton).
 - Campaign report messages/sendId for suppresed/failed contacts.

##V2.1.6

######Bug fixes
 - Syntax error or access violation while updating Coupon code.
 - Stop emulation when api is disabled.  
 
##V2.1.5

######Features
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
 
######Security
 - Remove modification for guest quote items.
 
######Improvements 
 - Now can be added multiple dimention condition for Rules.

##V2.1.4

###### Bugs fixes
 - InvalidContactIdentifier for single orders.
 - Compatibility with catalogStaging for enterprise.
 - Fix admin configuration fancybox error.

##V2.1.0
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