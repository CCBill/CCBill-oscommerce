<?php
/*
  $Id$

  CCBill
  http://www.ccbill.com

  Copyright (c) 2014-2021 CCBill
*/

  chdir('../../../../');
  require('includes/application_top.php');

  if (!defined('MODULE_PAYMENT_CCBILL_STATUS') || (MODULE_PAYMENT_CCBILL_STATUS  != 'True')) {
    exit;
  }

  require(DIR_WS_LANGUAGES . $language . '/modules/payment/ccbill.php');
  require('includes/modules/payment/ccbill.php');

  $result = false;
  $ccbill = new ccbill();

  $ccbill->process_success();
    
?>
