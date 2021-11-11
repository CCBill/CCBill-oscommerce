<?php
/*
  $Id$

  CCBill
  http://www.ccbill.com

  Copyright (c) 2016-2021 CCBill

  Version 1.2.1
*/

  define('MODULE_PAYMENT_CCBILL_TEXT_TITLE', 'CCBill Payments');
  define('MODULE_PAYMENT_CCBILL_TEXT_PUBLIC_TITLE', 'CCBill (including Credit and Debit Cards)');
  define('MODULE_PAYMENT_CCBILL_TEXT_DESCRIPTION', '<img src="images/icon_popup.gif" border="0" />&nbsp;<a href="https://www.ccbill.com" target="_blank" style="text-decoration: underline; font-weight: bold;">Visit CCBill Website</a>');

  define('MODULE_PAYMENT_CCBILL_ERROR_ADMIN_CURL', 'This module requires cURL to be enabled in PHP and will not load until it has been enabled on this webserver.');
  define('MODULE_PAYMENT_CCBILL_ERROR_ADMIN_CONFIGURATION', 'This module will not load until the Seller E-Mail Address parameter has been configured. Please edit and configure the settings of this module.');

  define('MODULE_PAYMENT_CCBILL_TEXT_PAYPAL_RETURN_BUTTON', 'Back to ' . STORE_NAME); // Maximum length 60 characters, otherwise it is ignored.
  define('MODULE_PAYMENT_CCBILL_TEXT_INVALID_TRANSACTION', 'Could not verify the PayPal transaction. Please try again.');

  define('MODULE_PAYMENT_CCBILL_DIALOG_CONNECTION_LINK_TITLE', 'Test API Server Connection');
  define('MODULE_PAYMENT_CCBILL_DIALOG_CONNECTION_TITLE', 'API Server Connection Test');
  define('MODULE_PAYMENT_CCBILL_DIALOG_CONNECTION_GENERAL_TEXT', 'Testing connection to server..');
  define('MODULE_PAYMENT_CCBILL_DIALOG_CONNECTION_BUTTON_CLOSE', 'Close');
  define('MODULE_PAYMENT_CCBILL_DIALOG_CONNECTION_TIME', 'Connection Time:');
  define('MODULE_PAYMENT_CCBILL_DIALOG_CONNECTION_SUCCESS', 'Success!');
  define('MODULE_PAYMENT_CCBILL_DIALOG_CONNECTION_FAILED', 'Failed! Please review the Verify SSL Certificate settings and try again.');
  define('MODULE_PAYMENT_CCBILL_DIALOG_CONNECTION_ERROR', 'An error occurred. Please refresh the page, review your settings, and try again.');

  define('MODULE_PAYMENT_CCBILL_MARK_BUTTON_IMG', 'https://www.ccbill.com/common/img/logo_CCBill_home.png');
  define('MODULE_PAYMENT_CCBILL_MARK_BUTTON_ALT', 'Pay with Credit Card via CCBill');
  define('MODULE_PAYMENT_CCBILL_ACCEPTANCE_MARK_TEXT', 'Save time. Check out securely. <br />Pay via credit card with CCBill.');

  define('MODULE_PAYMENT_CCBILL_TEXT_CATALOG_LOGO', '<img src="' . MODULE_PAYMENT_CCBILL_MARK_BUTTON_IMG . '" alt="' . MODULE_PAYMENT_CCBILL_MARK_BUTTON_ALT . '" title="' . MODULE_PAYMENT_CCBILL_MARK_BUTTON_ALT . '" /> &nbsp;' .
                                                    '<span class="smallText">' . MODULE_PAYMENT_CCBILL_ACCEPTANCE_MARK_TEXT . '</span>');

  define('MODULE_PAYMENT_CCBILL_ENTRY_FIRST_NAME', 'First Name:');
  define('MODULE_PAYMENT_CCBILL_ENTRY_LAST_NAME', 'Last Name:');
  define('MODULE_PAYMENT_CCBILL_ENTRY_BUSINESS_NAME', 'Business Name:');
  define('MODULE_PAYMENT_CCBILL_ENTRY_ADDRESS_NAME', 'Address Name:');
  define('MODULE_PAYMENT_CCBILL_ENTRY_ADDRESS_STREET', 'Address Street:');
  define('MODULE_PAYMENT_CCBILL_ENTRY_ADDRESS_CITY', 'Address City:');
  define('MODULE_PAYMENT_CCBILL_ENTRY_ADDRESS_STATE', 'Address State:');
  define('MODULE_PAYMENT_CCBILL_ENTRY_ADDRESS_ZIP', 'Address Zip:');
  define('MODULE_PAYMENT_CCBILL_ENTRY_ADDRESS_COUNTRY', 'Address Country:');
  define('MODULE_PAYMENT_CCBILL_ENTRY_EMAIL_ADDRESS', 'Payer Email:');
  define('MODULE_PAYMENT_CCBILL_ENTRY_EBAY_ID', 'Ebay ID:');
  define('MODULE_PAYMENT_CCBILL_ENTRY_PAYER_ID', 'Payer ID:');
  define('MODULE_PAYMENT_CCBILL_ENTRY_PAYER_STATUS', 'Payer Status:');
  define('MODULE_PAYMENT_CCBILL_ENTRY_ADDRESS_STATUS', 'Address Status:');

  define('MODULE_PAYMENT_CCBILL_ENTRY_PAYMENT_TYPE', 'Payment Type:');
  define('MODULE_PAYMENT_CCBILL_ENTRY_PAYMENT_STATUS', 'Payment Status:');
  define('MODULE_PAYMENT_CCBILL_ENTRY_PENDING_REASON', 'Pending Reason:');
  define('MODULE_PAYMENT_CCBILL_ENTRY_INVOICE', 'Invoice:');
  define('MODULE_PAYMENT_CCBILL_ENTRY_PAYMENT_DATE', 'Payment Date:');
  define('MODULE_PAYMENT_CCBILL_ENTRY_CURRENCY', 'Currency:');
  define('MODULE_PAYMENT_CCBILL_ENTRY_GROSS_AMOUNT', 'Gross Amount:');
  define('MODULE_PAYMENT_CCBILL_ENTRY_PAYMENT_FEE', 'Payment Fee:');
  define('MODULE_PAYMENT_CCBILL_ENTRY_EXCHANGE_RATE', 'Exchange Rate:');
  define('MODULE_PAYMENT_CCBILL_ENTRY_CART_ITEMS', 'Cart items:');
  define('MODULE_PAYMENT_CCBILL_ENTRY_TXN_TYPE', 'Trans. Type:');
  define('MODULE_PAYMENT_CCBILL_ENTRY_TXN_ID', 'Trans. ID:');
  define('MODULE_PAYMENT_CCBILL_ENTRY_PARENT_TXN_ID', 'Parent Trans. ID:');
  define('MODULE_PAYMENT_CCBILL_ENTRY_COMMENTS', 'System Comments: ');

  define('MODULE_PAYMENT_CCBILL_PURCHASE_DESCRIPTION_TITLE', 'All the items in your shopping basket (see details in the store and on your store receipt).');
  define('MODULE_PAYMENT_CCBILL_PURCHASE_DESCRIPTION_ITEMNUM', STORE_NAME . ' Purchase');


  define('MODULE_PAYMENT_CCBILL_TEXT_TITLE', 'Pay by Credit Card with CCBill');
  define('MODULE_PAYMENT_CCBILL_TEXT_DESCRIPTION', 'Payments by Credit Card via CCBill');
  define('MODULE_PAYMENT_CCBILL_TEXT_TYPE', 'Type:');
  define('MODULE_PAYMENT_CCBILL_TEXT_CREDIT_CARD_OWNER', 'Name on Card:');
  define('MODULE_PAYMENT_CCBILL_TEXT_CREDIT_CARD_NUMBER', 'Credit Card Number:');
  define('MODULE_PAYMENT_CCBILL_TEXT_CREDIT_CARD_CVV', 'CVV Code:');
  define('MODULE_PAYMENT_CCBILL_TEXT_CREDIT_CARD_EXPIRES', 'Credit Card Expiry Date:');
  define('MODULE_PAYMENT_CCBILL_TEXT_JS_CC_OWNER', '* The owner\'s name of the credit card must be at least ' . CC_OWNER_MIN_LENGTH . ' characters.\n');
  define('MODULE_PAYMENT_CCBILL_TEXT_JS_CC_NUMBER', '* The credit card number must be at least ' . CC_NUMBER_MIN_LENGTH . ' characters.\n');
  define('MODULE_PAYMENT_CCBILL_TEXT_ERROR_MESSAGE', 'There has been an error processing your credit card. Please try again.');
  define('MODULE_PAYMENT_CCBILL_TEXT_DECLINED_MESSAGE', 'Your credit card was declined. Please try another card or contact your bank for more info.');
  define('MODULE_PAYMENT_CCBILL_TEXT_ERROR', 'Credit Card Error!');

  define('EMAIL_TEXT_SUBJECT',  ' - Your Order from ' . STORE_NAME);
  define('EMAIL_TEXT_ORDER_NUMBER', 'Order Number: ');
  define('EMAIL_TEXT_INVOICE_URL', 'Detailed Invoice: ');
  define('EMAIL_TEXT_DATE_ORDERED', 'Date Ordered: ');
  define('EMAIL_TEXT_PRODUCTS', 'Products');
  define('EMAIL_TEXT_SUBTOTAL', 'Subtotal: ');
  define('EMAIL_TEXT_TAX', 'Tax:        ');
  define('EMAIL_TEXT_SHIPPING', 'Shipping: ');
  define('EMAIL_TEXT_TOTAL', 'Total:    ');
  define('EMAIL_TEXT_DELIVERY_ADDRESS', 'Delivery Address');
  define('EMAIL_TEXT_BILLING_ADDRESS', 'Billing Address');
  define('EMAIL_TEXT_PAYMENT_METHOD', 'Payment Method');

  define('EMAIL_SEPARATOR', '------------------------------------------------------');
  define('TEXT_EMAIL_VIA', 'via');

?>
