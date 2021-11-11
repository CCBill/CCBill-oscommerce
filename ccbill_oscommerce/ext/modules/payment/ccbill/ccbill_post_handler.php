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
  
  $orderStatusId = 2;
  
  if(defined('MODULE_PAYMENT_CCBILL_TRANSACTIONS_ORDER_STATUS_ID')){
    $orderStatusId = MODULE_PAYMENT_CCBILL_TRANSACTIONS_ORDER_STATUS_ID;
  }// end if

  require(DIR_WS_LANGUAGES . $language . '/modules/payment/ccbill.php');
  require('includes/modules/payment/ccbill.php');

  $result = false;

  $ccbill = new ccbill();

  foreach ($_POST as $key => $value) {
    $parameters .= '&' . $key . '=' . urlencode(stripslashes($value));
  }// end for each param
           
  if(isset($_GET['Action'])){
    $myAction = $_GET['Action'];
  }// end if
  
  //logMessage('myAction: ' . $myAction);
  
  if(($myAction == 'Approval_Post' || $myAction == 'Denial_Post' )){
             
    $txId      = 0;
    $mySuccess = 0;
    $encrypted = '';
    $decrypted = '';
    $myOrderId = -1;
    $cardType  = '';
    /*
    $myLogMessage = 'myAction: ' . $myAction . '; email is set: ' . isset($_POST['email']) . '; zc_orderid is set: ' . isset($_POST['zc_orderid']);
    
    logMessage($myLogMessage);
    
    $myLogMessage = 'HTTP VARS: ';
    
    $keys = array_keys($_POST);
    
    foreach($keys as &$key) {
      $myLogMessage .= $key . ' = ' . $_POST[$key] . '; ';
    }// end while
    
    logMessage($myLogMessage);
    */
    if($myAction == 'Approval_Post' && isset($_POST['email']) && isset($_POST['subscription_id']) ){
    
      $txId = $_POST['subscription_id'];
      $mySuccess = 1;
      $result = 'VERIFIED';
    
      if(isset($_POST['customer_fname']))   $myFirstName    = $_POST['customer_fname'];
      if(isset($_POST['customer_lname']))   $myLastName     = $_POST['customer_lname'];
      if(isset($_POST['email']))            $myEmail        = $_POST['email'];
      if(isset($_POST['accountingAmount'])) $myAmount       = $_POST['accountingAmount'];
      if(isset($_POST['currencyCode']))     $myCurrencyCode = $_POST['currencyCode'];
      if(isset($_POST['zc_orderid']))       $myOrderId      = $_POST['zc_orderid'];
      if(isset($_POST['cardType']))         $cardType       = $_POST['cardType'];
      //if(isset($_POST['responseDigest'])) $myDigest       = $_POST['responseDigest'];
      
      $billingPeriodInDays = 2;
      
      $salt = MODULE_PAYMENT_CCBILL_Salt;
      
      $stringToHash = '' . $myAmount
  	                     . $billingPeriodInDays 
  	                     . $myCurrencyCode
  	                     . $salt;
  	                     
  	  $myDigest = md5($stringToHash);
  	  
  	  if($myOrderId < 0){
            
        $tcSql = 'SELECT `orders_id` FROM ' . TABLE_ORDERS . ' WHERE `ccbill_id` = "Hash:' . $myDigest . '" AND `ip_address` = "' . $_POST['ip_address'] . '" ORDER BY orders_id DESC';
        
        //logMessage('preparing to execute query: ' . $tcSql);
        
        $order_query = tep_db_query($tcSql);
        $arr = tep_db_fetch_array($order_query);
        
        if(count($arr) > 0){
          $myOrderId = $arr['orders_id'];
        }// end if
        
        //logMessage('orderID retrieved: ' . $myOrderId);
        
      }// end if order reference is not available

      if($myOrderId > 0){
        $tcSql = 'UPDATE ' . TABLE_ORDERS . ' SET `orders_status` = ' . $orderStatusId . ', `ccbill_id` = "' . $txId . '", `orders_date_finished` = CURRENT_TIMESTAMP, `cc_type` = "' . $cardType . '", `last_modified` = CURRENT_TIMESTAMP WHERE `orders_id` = ' . $myOrderId;
      
        tep_db_query($tcSql);
        
        $sql_data_array = array('orders_id' => $order_id,
                                'orders_status_id' => $orderStatusId,
                                'date_added' => 'now()',
                                'customer_notified' => '1',
                                'comments' => 'Payment Completed');

        tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
                  
      }// end if
    
    }
    else if($myAction == 'Denial_Post'){
      //if(isset($_POST['denialId'])) $txId = $_POST['denialId'];
      $mySuccess = 0;die('failure');
    }// end if/else
  
  }
  else{
    print_r('this is a test');
  }// end if response is included
  
  
  function logMessage($message)
  {
      error_log('[CCBill] ' . $message);
  }
    

  require('includes/application_bottom.php');
?>
