<?php
/*
  $Id$

  CCBill, Payment Solutions
  http://www.ccbill.com

  Copyright (c) 2016-2021 CCBill

*/

  class ccbill {
    var $code, $title, $description, $enabled;

    // class constructor
    function ccbill() {

      global $HTTP_GET_VARS, $PHP_SELF, $order;

      $this->signature = 'ccbill|1.3.0';

      $this->code = 'ccbill';
      $this->title = MODULE_PAYMENT_CCBILL_TEXT_TITLE;
      $this->public_title = MODULE_PAYMENT_CCBILL_TEXT_PUBLIC_TITLE;
      $this->description = MODULE_PAYMENT_CCBILL_TEXT_DESCRIPTION;
      $this->sort_order = defined('MODULE_PAYMENT_CCBILL_SORT_ORDER') ? MODULE_PAYMENT_CCBILL_SORT_ORDER : 0;
      $this->enabled = defined('MODULE_PAYMENT_CCBILL_STATUS') && (MODULE_PAYMENT_CCBILL_STATUS == 'True') ? true : false;
      $this->order_status = defined('MODULE_PAYMENT_CCBILL_PREPARE_ORDER_STATUS_ID') && ((int)MODULE_PAYMENT_CCBILL_PREPARE_ORDER_STATUS_ID > 0) ? (int)MODULE_PAYMENT_CCBILL_PREPARE_ORDER_STATUS_ID : 0;

      $this->FormName = MODULE_PAYMENT_CCBILL_FormName;

      // Change made by using ADC Direct Connection
      $this->form_action_url = 'https://bill.ccbill.com/jpost/signup.cgi';

      $this->IsFlexForm       = MODULE_PAYMENT_CCBILL_IsFlexForm != 'No';
      $this->priceVarName     = 'formPrice';
      $this->periodVarName    = 'formPeriod';

      if($this->IsFlexForm){
        $this->form_action_url  = 'https://api.ccbill.com/wap-frontflex/flexforms/' . $this->FormName;
        $this->priceVarName     = 'initialPrice';
        $this->periodVarName    = 'initialPeriod';
      }// end if


      if ( $this->enabled === true ) {
        if ( !tep_not_null(MODULE_PAYMENT_CCBILL_ID) ) {
          $this->description = '<div class="secWarning">' . MODULE_PAYMENT_CCBILL_ERROR_ADMIN_CONFIGURATION . '</div>' . $this->description;

          $this->enabled = false;
        }
      }

      if ( $this->enabled === true ) {
        if ( isset($order) && is_object($order) ) {
          $this->update_status();
        }
      }

      if ( defined('FILENAME_MODULES') && ($PHP_SELF == FILENAME_MODULES) && isset($HTTP_GET_VARS['action']) && ($HTTP_GET_VARS['action'] == 'install') && isset($HTTP_GET_VARS['subaction']) && ($HTTP_GET_VARS['subaction'] == 'conntest') ) {
        echo $this->getTestConnectionResult();
        exit;
      }
    }

    function update_status() {
      global $order;

      if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_CCBILL_ZONE > 0) ) {
        $check_flag = false;
        $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_CCBILL_ZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");
        while ($check = tep_db_fetch_array($check_query)) {
          if ($check['zone_id'] < 1) {
            $check_flag = true;
            break;
          } elseif ($check['zone_id'] == $order->billing['zone_id']) {
            $check_flag = true;
            break;
          }
        }

        if ($check_flag == false) {
          $this->enabled = false;
        }
      }
    }

    function javascript_validation() {
      return false;
    }

    function selection() {

      $selection = array('id' => $this->code,
                   'module' => $this->public_title);

      return $selection;
    }

    function pre_confirmation_check() {
      return false;
    }

    function confirmation() {
      return false;
    }

    function process_success(){

      global $cart, $cartID, $customer_id, $languages_id, $order, $order_total_modules,
             $_POST, $HTTP_SERVER_VARS, $cart_CCBill_Standard_ID, $cart_CCBill_Order_ID, $$payment;


      if (tep_session_is_registered('cart_CCBill_Standard_ID') &&
          tep_session_is_registered('cart_CCBill_Order_ID') &&
          $cart_CCBill_Order_ID > 0) {

        $orderStatusId = 2;

        if(defined('MODULE_PAYMENT_CCBILL_TRANSACTIONS_ORDER_STATUS_ID')){
          $orderStatusId = MODULE_PAYMENT_CCBILL_TRANSACTIONS_ORDER_STATUS_ID;
        }// end if

        // Get order
        $myQuery = tep_db_query('SELECT * FROM ' . TABLE_ORDERS . ' WHERE `orders_id` = "' . $cart_CCBill_Order_ID . '" AND `customers_id` = ' . $customer_id . ' AND `orders_status` = ' . $orderStatusId . ' ORDER BY `date_purchased` DESC');

        $arr = tep_db_fetch_array($myQuery);

        $order_id_new = $cart_CCBill_Order_ID;

        $customer_notification = (SEND_EMAILS == 'true') ? '1' : '0';

        //die('customer id: ' . $customer_id . '; order id: ' . $cart_CCBill_Order_ID . '; query row count: ' . tep_db_num_rows($myQuery) . '; order status id: ' . $orderStatusId);

        if(tep_db_num_rows($myQuery) > 0){

          //------------------------------------------------------------------


          for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {

            // Update Stock
            if (STOCK_LIMITED == 'true') {
              if (DOWNLOAD_ENABLED == 'true') {
                $stock_query_raw = "SELECT products_quantity, pad.products_attributes_filename
                                    FROM " . TABLE_PRODUCTS . " p
                                    LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                    ON p.products_id=pa.products_id
                                    LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                                    ON pa.products_attributes_id=pad.products_attributes_id
                                    WHERE p.products_id = '" . tep_get_prid($order->products[$i]['id']) . "'";

                // Will work with only one option for downloadable products
                // otherwise, we have to build the query dynamically with a loop
                $products_attributes = $order->products[$i]['attributes'];

                if (is_array($products_attributes)) {
                  $stock_query_raw .= " AND pa.options_id = '" . $products_attributes[0]['option_id'] . "' AND pa.options_values_id = '" . $products_attributes[0]['value_id'] . "'";
                }// end if

                $stock_query = tep_db_query($stock_query_raw);

              }
              else {
                $stock_query = tep_db_query("select products_quantity from " . TABLE_PRODUCTS . " where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");
              }// end if/else

              if (tep_db_num_rows($stock_query) > 0) {

                $stock_values = tep_db_fetch_array($stock_query);

                // do not decrement quantities if products_attributes_filename exists
                if ((DOWNLOAD_ENABLED != 'true') || (!$stock_values['products_attributes_filename'])) {
                  $stock_left = $stock_values['products_quantity'] - $order->products[$i]['qty'];
                }
                else {
                  $stock_left = $stock_values['products_quantity'];
                }// end if/else

                tep_db_query("update " . TABLE_PRODUCTS . " set products_quantity = '" . $stock_left . "' where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");

                if ( ($stock_left < 1) && (STOCK_ALLOW_CHECKOUT == 'false') ) {
                  tep_db_query("update " . TABLE_PRODUCTS . " set products_status = '0' where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");
                }// end if
              }// end if
            }// end if stock limited

            // Update products_ordered (for bestsellers list)
            tep_db_query("update " . TABLE_PRODUCTS . " set products_ordered = products_ordered + " . sprintf('%d', $order->products[$i]['qty']) . " where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");

            //------ Insert customer chosen option to order --------
            $attributes_exist = '0';
            $products_ordered_attributes = '';

            if (isset($order->products[$i]['attributes'])) {

              $attributes_exist = '1';

              for ($j=0, $n2=sizeof($order->products[$i]['attributes']); $j<$n2; $j++) {

                if (DOWNLOAD_ENABLED == 'true') {

                  $attributes_query = "select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix, pad.products_attributes_maxdays, pad.products_attributes_maxcount , pad.products_attributes_filename
                                       from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                       left join " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                                       on pa.products_attributes_id=pad.products_attributes_id
                                       where pa.products_id = '" . $order->products[$i]['id'] . "'
                                       and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "'
                                       and pa.options_id = popt.products_options_id
                                       and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "'
                                       and pa.options_values_id = poval.products_options_values_id
                                       and popt.language_id = '" . $languages_id . "'
                                       and poval.language_id = '" . $languages_id . "'";

                  $attributes = tep_db_query($attributes_query);

                }
                else {
                  $attributes = tep_db_query("select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa where pa.products_id = '" . $order->products[$i]['id'] . "' and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "' and pa.options_id = popt.products_options_id and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "' and pa.options_values_id = poval.products_options_values_id and popt.language_id = '" . $languages_id . "' and poval.language_id = '" . $languages_id . "'");
                }// end if/else

                $attributes_values = tep_db_fetch_array($attributes);

                $products_ordered_attributes .= "\n\t" . $attributes_values['products_options_name'] . ' ' . $attributes_values['products_options_values_name'];

              }// end for

            }// end if

            //------ Insert customer chosen option eof ----
            $total_weight += ($order->products[$i]['qty'] * $order->products[$i]['weight']);
            $total_tax += tep_calculate_tax($total_products_price, $products_tax) * $order->products[$i]['qty'];
            $total_cost += $total_products_price;

            $products_ordered .= $order->products[$i]['qty'] . ' x ' . $order->products[$i]['name'] . ' (' . $order->products[$i]['model'] . ') = ' . $currencies->display_price($order->products[$i]['final_price'], $order->products[$i]['tax'], $order->products[$i]['qty']) . $products_ordered_attributes . "\n";
          }// end for

          $sql_data_array = array('orders_id' => $insert_id,
                          'orders_status_id' => $order->info['order_status'],
                          'date_added' => 'now()',
                          'customer_notified' => $customer_notification,
                          'comments' => $order->info['comments']);
          tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

          // Begin email confirmation
          $email_order = STORE_NAME . "\n" .
                         EMAIL_SEPARATOR . "\n" .
                         EMAIL_TEXT_ORDER_NUMBER . ' ' . $order_id . "\n" .
                         EMAIL_TEXT_INVOICE_URL . ' ' . tep_href_link(FILENAME_ACCOUNT_HISTORY_INFO, 'order_id=' . $order_id, 'SSL', false) . "\n" .
                         EMAIL_TEXT_DATE_ORDERED . ' ' . strftime(DATE_FORMAT_LONG) . "\n\n";

          if ($order->info['comments']) {
            $email_order .= tep_db_output($order->info['comments']) . "\n\n";
          }// end if

          $email_order .= EMAIL_TEXT_PRODUCTS . "\n" .
                          EMAIL_SEPARATOR . "\n" .
                          $products_ordered .
                          EMAIL_SEPARATOR . "\n";

          for ($i=0, $n=sizeof($order_totals); $i<$n; $i++) {
            $email_order .= strip_tags($order_totals[$i]['title']) . ' ' . strip_tags($order_totals[$i]['text']) . "\n";
          }// end for

          if ($order->content_type != 'virtual') {
            $email_order .= "\n" . EMAIL_TEXT_DELIVERY_ADDRESS . "\n" .
                            EMAIL_SEPARATOR . "\n" .
                            tep_address_label($customer_id, $sendto, 0, '', "\n") . "\n";
          }// end if

          $email_order .= "\n" . EMAIL_TEXT_BILLING_ADDRESS . "\n" .
                          EMAIL_SEPARATOR . "\n" .
                          tep_address_label($customer_id, $billto, 0, '', "\n") . "\n\n";

          if (is_object($$payment)) {

            $email_order .= EMAIL_TEXT_PAYMENT_METHOD . "\n" .
                            EMAIL_SEPARATOR . "\n";

            $payment_class = $$payment;
            $email_order .= $payment_class->title . "\n\n";

            if ($payment_class->email_footer) {
              $email_order .= $payment_class->email_footer . "\n\n";
            }// end if

          }// end if

          tep_mail($order->customer['firstname'] . ' ' . $order->customer['lastname'], $order->customer['email_address'], EMAIL_TEXT_SUBJECT, $email_order, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);

          // send emails to other people
          if (SEND_EXTRA_ORDER_EMAILS_TO != '') {
            tep_mail('', SEND_EXTRA_ORDER_EMAILS_TO, EMAIL_TEXT_SUBJECT, $email_order, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
          }

          //------------------------------------------------------------------

          $cart->reset(true);

          // unregister session variables used during checkout
          tep_session_unregister('sendto');
          tep_session_unregister('billto');
          tep_session_unregister('shipping');
          tep_session_unregister('payment');
          tep_session_unregister('comments');

          tep_session_unregister('cart_CCBill_Standard_ID');
          tep_session_unregister('cart_CCBill_Order_ID');

          tep_redirect(tep_href_link(FILENAME_ACCOUNT_HISTORY_INFO . '?order_id=' . $order_id_new, '', 'SSL'));

        }// end if

      }// end if


      die('You do not have access to view this page');

    }// end process_success


    /**
     * Returns an encrypted & utf8-encoded
     */
    function encrypt_string($pure_string, $encryption_key) {
        $iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $encrypted_string = mcrypt_encrypt(MCRYPT_BLOWFISH, $encryption_key, utf8_encode($pure_string), MCRYPT_MODE_ECB, $iv);
        return $encrypted_string;
    }

    /**
     * Returns decrypted original string
     */
    function decrypt_string($encrypted_string, $encryption_key) {
        $iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $decrypted_string = mcrypt_decrypt(MCRYPT_BLOWFISH, $encryption_key, $encrypted_string, MCRYPT_MODE_ECB, $iv);
        return $decrypted_string;
    }

    function process_button() {

      global $cartID, $customer_id, $languages_id, $order, $sendto, $currency, $shipping, $order_total_modules,
             $_POST, $HTTP_SERVER_VARS, $cart_CCBill_Standard_ID, $cart_CCBill_Order_ID;

      if ( !($order->info['total'] > 0) )
        die("<script type=\"text/javascript\">alert('Invalid amount');</script>");

      $insert_id = -1;

      $total_tax = $order->info['tax'];

      $myOptions    = array();
      $buttonArray  = array();

		  // A.NET INVOICE NUMBER FIX
		  // find the next order_id to pass as x_Invoice_Num
		  $next_inv = '';
		  $inv_id = tep_db_query("select orders_id from " . TABLE_ORDERS . " order by orders_id DESC limit 1");

		  $arr = tep_db_fetch_array($inv_id);

		  $last_inv = $arr['orders_id'];
		  $next_inv = $last_inv+1;
		  // END A.NET INVOICE NUMBER FIX

      $this->ClientAccountNo    = MODULE_PAYMENT_CCBILL_ClientAccountNo;
      $this->ClientSubAccountNo = MODULE_PAYMENT_CCBILL_ClientSubAccountNo;
      //$this->FormName           = MODULE_PAYMENT_CCBILL_FormName;
      //$this->IsFlexForm         = MODULE_PAYMENT_CCBILL_IsFlexForm == 'Yes';
      $this->Currency           = MODULE_PAYMENT_CCBILL_Currency;
      $this->Salt               = MODULE_PAYMENT_CCBILL_Salt;
      $this->OrderStatusId      = MODULE_PAYMENT_CCBILL_TRANSACTIONS_ORDER_STATUS_ID;

      $this->TransactionAmount = number_format($order->info['total'], 2, '.', '');
      $billingPeriodInDays = 2;

      $stringToHash = '' . $this->TransactionAmount
  	                     . $billingPeriodInDays
  	                     . $this->Currency
  	                     . $this->Salt;

  	  $this->Hash = md5($stringToHash);

      $ccbill_addr = $this->form_action_url;

      //$this->logMessage('creating form: ' . $ccbill_addr);

      // remove shipping tax in total tax value
      if ( isset($shipping['cost']) ) {
        $total_tax -= ($order->info['shipping_cost'] - $shipping['cost']);
      }

      // Remove any pending orders for this cart
      $existingOrdersQueryString = "SELECT * FROM " . TABLE_ORDERS . " WHERE customers_id = '" . (int)$customer_id . "' AND ORDERS_STATUS = 1 AND ccbill_id = 'Hash:" . $this->Hash . "' ORDER BY orders_id DESC";
      $myQuery = tep_db_query($existingOrdersQueryString);
      $arr = tep_db_fetch_array($myQuery);

      // Remove each order id

      while(count($arr) > 0 && (int)$arr['orders_id'] > 0){

        $order_id = $arr['orders_id'];

        $check_query = tep_db_query('select orders_id from ' . TABLE_ORDERS_STATUS_HISTORY . ' where orders_id = "' . (int)$order_id . '" limit 1');

        if (tep_db_num_rows($check_query) < 1) {
          tep_db_query('delete from ' . TABLE_ORDERS                      . ' where orders_id = "' . (int)$order_id . '"');
          tep_db_query('delete from ' . TABLE_ORDERS_TOTAL                . ' where orders_id = "' . (int)$order_id . '"');
          tep_db_query('delete from ' . TABLE_ORDERS_STATUS_HISTORY       . ' where orders_id = "' . (int)$order_id . '"');
          tep_db_query('delete from ' . TABLE_ORDERS_PRODUCTS             . ' where orders_id = "' . (int)$order_id . '"');
          tep_db_query('delete from ' . TABLE_ORDERS_PRODUCTS_ATTRIBUTES  . ' where orders_id = "' . (int)$order_id . '"');
          tep_db_query('delete from ' . TABLE_ORDERS_PRODUCTS_DOWNLOAD    . ' where orders_id = "' . (int)$order_id . '"');
        }// end if

        $myQuery  = tep_db_query($existingOrdersQueryString);
        $arr      = tep_db_fetch_array($myQuery);

      }// end while

      // prepare order totals for insertion
      $order_totals = array();
      if (is_array($order_total_modules->modules)) {
        foreach ($order_total_modules->modules as $value) {
          $class = substr($value, 0, strrpos($value, '.'));
          if ($GLOBALS[$class]->enabled) {
            for ($i=0, $n=sizeof($GLOBALS[$class]->output); $i<$n; $i++) {
              if (tep_not_null($GLOBALS[$class]->output[$i]['title']) && tep_not_null($GLOBALS[$class]->output[$i]['text'])) {
                $order_totals[] = array('code'        => $GLOBALS[$class]->code,
                                        'title'       => $GLOBALS[$class]->output[$i]['title'],
                                        'text'        => $GLOBALS[$class]->output[$i]['text'],
                                        'value'       => $GLOBALS[$class]->output[$i]['value'],
                                        'sort_order'  => $GLOBALS[$class]->sort_order);
              }// end if tep not null
            }// end for size of globals[class] output
          }// end if globals[class] enabled
        }// end foreach order total module
      }// end if order totals array is present

      // Insert order into tables and set as preliminary status with
      // ccbill_id 0
      $sql_data_array = array('customers_id'                => $customer_id,
                              'customers_name'              => $order->customer['firstname'] . ' ' . $order->customer['lastname'],
                              'customers_company'           => $order->customer['company'],
                              'customers_street_address'    => $order->customer['street_address'],
                              'customers_suburb'            => $order->customer['suburb'],
                              'customers_city'              => $order->customer['city'],
                              'customers_postcode'          => $order->customer['postcode'],
                              'customers_state'             => $order->customer['state'],
                              'customers_country'           => $order->customer['country']['title'],
                              'customers_telephone'         => $order->customer['telephone'],
                              'customers_email_address'     => $order->customer['email_address'],
                              'customers_address_format_id' => $order->customer['format_id'],
                              'delivery_name'               => $order->delivery['firstname'] . ' ' . $order->delivery['lastname'],
                              'delivery_company'            => $order->delivery['company'],
                              'delivery_street_address'     => $order->delivery['street_address'],
                              'delivery_suburb'             => $order->delivery['suburb'],
                              'delivery_city'               => $order->delivery['city'],
                              'delivery_postcode'           => $order->delivery['postcode'],
                              'delivery_state'              => $order->delivery['state'],
                              'delivery_country'            => $order->delivery['country']['title'],
                              'delivery_address_format_id'  => $order->delivery['format_id'],
                              'billing_name'                => $order->billing['firstname'] . ' ' . $order->billing['lastname'],
                              'billing_company'             => $order->billing['company'],
                              'billing_street_address'      => $order->billing['street_address'],
                              'billing_suburb'              => $order->billing['suburb'],
                              'billing_city'                => $order->billing['city'],
                              'billing_postcode'            => $order->billing['postcode'],
                              'billing_state'               => $order->billing['state'],
                              'billing_country'             => $order->billing['country']['title'],
                              'billing_address_format_id'   => $order->billing['format_id'],
                              'payment_method'              => $order->info['payment_method'],
                              'date_purchased'              => 'getdate()',
                              'orders_status'               => 1,
                              'currency'                    => $order->info['currency'],
                              'currency_value'              => $order->info['currency_value'],
                              'ip_address'                  => $_SERVER['REMOTE_ADDR'],
                              'ccbill_id'                   => 'Hash:' . $this->Hash);

      tep_db_perform(TABLE_ORDERS, $sql_data_array);

      $insert_id = tep_db_insert_id();

      // Insert order totals
      for ($i=0, $n=sizeof($order_totals); $i<$n; $i++) {
        $sql_data_array = array('orders_id'   => $insert_id,
                                'title'       => $order_totals[$i]['title'],
                                'text'        => $order_totals[$i]['text'],
                                'value'       => $order_totals[$i]['value'],
                                'class'       => $order_totals[$i]['code'],
                                'sort_order'  => $order_totals[$i]['sort_order']);

        tep_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);
      }// end for

      // Insert each product
      for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {

        $sql_data_array = array('orders_id'         => $insert_id,
                                'products_id'       => tep_get_prid($order->products[$i]['id']),
                                'products_model'    => $order->products[$i]['model'],
                                'products_name'     => $order->products[$i]['name'],
                                'products_price'    => $order->products[$i]['price'],
                                'final_price'       => $order->products[$i]['final_price'],
                                'products_tax'      => $order->products[$i]['tax'],
                                'products_quantity' => $order->products[$i]['qty']);

        tep_db_perform(TABLE_ORDERS_PRODUCTS, $sql_data_array);

        $order_products_id = tep_db_insert_id();

        $attributes_exist = '0';

        // If product attributes exist
        if (isset($order->products[$i]['attributes'])) {
          $attributes_exist = '1';

          // For each attribute
          for ($j=0, $n2=sizeof($order->products[$i]['attributes']); $j<$n2; $j++) {
            if (DOWNLOAD_ENABLED == 'true') {
              $attributes_query = "select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix, pad.products_attributes_maxdays, pad.products_attributes_maxcount , pad.products_attributes_filename
                                   from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                   left join " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                                   on pa.products_attributes_id=pad.products_attributes_id
                                   where pa.products_id = '" . $order->products[$i]['id'] . "'
                                   and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "'
                                   and pa.options_id = popt.products_options_id
                                   and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "'
                                   and pa.options_values_id = poval.products_options_values_id
                                   and popt.language_id = '" . $languages_id . "'
                                   and poval.language_id = '" . $languages_id . "'";
              $attributes = tep_db_query($attributes_query);
            }
            else {
              $attributes = tep_db_query("select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa where pa.products_id = '" . $order->products[$i]['id'] . "' and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "' and pa.options_id = popt.products_options_id and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "' and pa.options_values_id = poval.products_options_values_id and popt.language_id = '" . $languages_id . "' and poval.language_id = '" . $languages_id . "'");
            }// end if/else

            $attributes_values = tep_db_fetch_array($attributes);

            // Insert attributes
            $sql_data_array = array('orders_id'               => $insert_id,
                                    'orders_products_id'      => $order_products_id,
                                    'products_options'        => $attributes_values['products_options_name'],
                                    'products_options_values' => $attributes_values['products_options_values_name'],
                                    'options_values_price'    => $attributes_values['options_values_price'],
                                    'price_prefix'            => $attributes_values['price_prefix']);

            tep_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $sql_data_array);

            if ((DOWNLOAD_ENABLED == 'true') && isset($attributes_values['products_attributes_filename']) && tep_not_null($attributes_values['products_attributes_filename'])) {
              $sql_data_array = array('orders_id'                 => $insert_id,
                                      'orders_products_id'        => $order_products_id,
                                      'orders_products_filename'  => $attributes_values['products_attributes_filename'],
                                      'download_maxdays'          => $attributes_values['products_attributes_maxdays'],
                                      'download_count'            => $attributes_values['products_attributes_maxcount']);

              tep_db_perform(TABLE_ORDERS_PRODUCTS_DOWNLOAD, $sql_data_array);
            }// end if download enabled
          }// end for each attribute
        }// end if attributes exist
      }// end for each product


      $cart_CCBill_Standard_ID = $cartID . '-' . $insert_id;
      tep_session_register('cart_CCBill_Standard_ID');

      $cart_CCBill_Order_ID = $insert_id;

      tep_session_register('cart_CCBill_Order_ID');

      $myOptions['clientAccnum']        = $this->ClientAccountNo;
      $myOptions['clientSubacc']        = $this->ClientSubAccountNo;
      $myOptions['formName']            = $this->FormName;
      $myOptions[$this->priceVarName]   = $this->TransactionAmount;
      $myOptions[$this->periodVarName]  = $billingPeriodInDays;
      $myOptions['currencyCode']        = $this->Currency;

      $myOptions['customer_fname']  = $order->billing['firstname'];
      $myOptions['customer_lname']  = $order->billing['lastname'];
      $myOptions['email']           = $order->customer['email_address'];
      $myOptions['zipcode']         = $order->billing['postcode'];
      $myOptions['country']         = $order->billing['country']['iso_code_2'];
      $myOptions['city']            = $order->billing['city'];
      $myOptions['state']           = $this->getStateCodeFromName($order->billing['state']);
      $myOptions['address1']        = $order->billing['street_address'];
      $myOptions['zc_orderid']      = $insert_id;
      $myOptions['formDigest']      = $this->Hash;

      $_SESSION['CCBILL_AMOUNT'] = $this->TransactionAmount;

      // build the button fields
      foreach ($myOptions as $name => $value) {
        // remove quotation marks
        $value = str_replace('"', '', $value);
        // check for invalid characters
        if (preg_match('/[^a-zA-Z_0-9]/', $name)) {
          ipn_debug_email('datacheck - ABORTING - preg_match found invalid submission key: ' . $name . ' (' . $value . ')');
          break;
        }// end if

        //$process_button_string .= tep_draw_hidden_field($name, $value);
        $buttonArray[] = tep_draw_hidden_field($name, $value);
      }// end foreach

      $process_button_string = "\n" . implode("\n", $buttonArray) . "\n";

      $_SESSION['ccbill_transaction_info'] = array($this->transaction_amount, $this->transaction_currency);

      return $process_button_string;

    }// end process_button



    function getStateCodeFromName($stateName){

      $rVal = $stateName;

      switch($rVal){
        case 'Alabama':         $rVal = 'AL';
          break;
        case 'Alaska':          $rVal = 'AK';
          break;
        case 'Arizona':         $rVal = 'AZ';
          break;
        case 'Arkansas':        $rVal = 'AR';
          break;
        case 'California':      $rVal = 'CA';
          break;
        case 'Colorado':        $rVal = 'CO';
          break;
        case 'Connecticut':     $rVal = 'CT';
          break;
        case 'Delaware':        $rVal = 'DE';
          break;
        case 'Florida':         $rVal = 'FL';
          break;
        case 'Georgia':         $rVal = 'GA';
          break;
        case 'Hawaii':          $rVal = 'HI';
          break;
        case 'Idaho':           $rVal = 'ID';
          break;
        case 'Illinois':        $rVal = 'IL';
          break;
        case 'Indiana':         $rVal = 'IN';
          break;
        case 'Iowa':            $rVal = 'IA';
          break;
        case 'Kansas':          $rVal = 'KS';
          break;
        case 'Kentucky':        $rVal = 'KY';
          break;
        case 'Louisiana':       $rVal = 'LA';
          break;
        case 'Maine':           $rVal = 'ME';
          break;
        case 'Maryland':        $rVal = 'MD';
          break;
        case 'Massachusetts':   $rVal = 'MA';
          break;
        case 'Michigan':        $rVal = 'MI';
          break;
        case 'Minnesota':       $rVal = 'MN';
          break;
        case 'Mississippi':     $rVal = 'MS';
          break;
        case 'Missouri':        $rVal = 'MO';
          break;
        case 'Montana':         $rVal = 'MT';
          break;
        case 'Nebraska':        $rVal = 'NE';
          break;
        case 'Nevada':          $rVal = 'NV';
          break;
        case 'New York':        $rVal = 'NY';
          break;
        case 'Ohio':            $rVal = 'OH';
          break;
        case 'Oklahoma':        $rVal = 'OK';
          break;
        case 'Oregon':          $rVal = 'OR';
          break;
        case 'Pennsylvania':    $rVal = 'PN';
          break;
        case 'Rhode Island':    $rVal = 'RI';
          break;
        case 'South Carolina':  $rVal = 'SC';
          break;
        case 'South Dakota':    $rVal = 'SD';
          break;
        case 'Tennessee':       $rVal = 'TN';
          break;
        case 'Texas':           $rVal = 'TX';
          break;
        case 'Utah':            $rVal = 'UT';
          break;
        case 'Virginia':        $rVal = 'VA';
          break;
        case 'Vermont':         $rVal = 'VT';
          break;
        case 'Washington':      $rVal = 'WA';
          break;
        case 'Wisconsin':       $rVal = 'WI';
          break;
        case 'West Virginia':   $rVal = 'WV';
          break;
        case 'Wyoming':         $rVal = 'WY';
          break;
      }// end switch

      return $rVal;

    }// end getStateCodeFromName


    // Return the CCBill currency code
    // based on user selection
    function setCurrencyCode(){
      switch($this->Currency){
        case "USD": $this->CurrencyCode = 840;
          break;
        case "EUR": $this->CurrencyCode = 978;
          break;
        case "AUD": $this->CurrencyCode = 036;
          break;
        case "CAD": $this->CurrencyCode = 124;
          break;
        case "GBP": $this->CurrencyCode = 826;
          break;
        case "JPY": $this->CurrencyCode = 392;
          break;
      }// end switch
    }// end getCurrencyCode


    function before_process() {

      global $customer_id, $order, $order_totals, $sendto, $billto, $languages_id, $payment, $currencies, $cart, $cart_CCBill_Standard_ID, $$payment, $HTTP_GET_VARS, $HTTP_POST_VARS, $messageStack;

      $myAction = '';

      if(isset($_GET['Action'])){

        $myAction = $_GET['Action'];
        if($myAction == 'CheckoutSuccess'
           && isset($_SESSION['CCBILL_AMOUNT'])
           && strlen('' . $_SESSION['CCBILL_AMOUNT']) > 2){

           $myDigest = $_SESSION['CCBILL_AMOUNT'];

           $tcSql = "SELECT * FROM ccbill WHERE email = '" . $order->customer['email_address'] . "' AND amount = '" . $myDigest . "' AND success = 1 AND order_created = 0";

           $check_query = tep_db_query($tcSql);
           $rowCount = tep_db_num_rows($check_query);

           if($rowCount > 0){

             $tcSql = "UPDATE ccbill SET order_created = 1 WHERE email = '" . $order->customer['email_address'] . "' AND amount = '" . $myDigest . "'";

             tep_db_query($tcSql);

             unset($_SESSION['CCBILL_AMOUNT']);
             return true;
           }
           else{
            $this->notify('NOTIFY_PAYMENT_CCBILL_CANCELLED_DURING_CHECKOUT');
            tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
           }// end if/else
        }
        else{
          $this->notify('NOTIFY_PAYMENT_CCBILL_CANCELLED_DURING_CHECKOUT');
          tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
        }// end if/else

      }
      else{
        $this->notify('NOTIFY_PAYMENT_CCBILL_CANCELLED_DURING_CHECKOUT');
        tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
      }// end if response is included

    }// end before_process

    function after_process() {
      return false;
    }

    function get_error() {
      return false;
    }

    function check() {
      if (!isset($this->_check)) {
        $check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_CCBILL_STATUS'");
        $this->_check = tep_db_num_rows($check_query);
      }
      return $this->_check;
    }

    function install($parameter = null) {

      $params = $this->getParams();

      if (isset($parameter)) {
        if (isset($params[$parameter])) {
          $params = array($parameter => $params[$parameter]);
        }
        else {
          $params = array();
        }// end if/else
      }// end if

      foreach ($params as $key => $data) {
        $sql_data_array = array('configuration_title'       => $data['title'],
                                'configuration_key'         => $key,
                                'configuration_value'       => (isset($data['value']) ? $data['value'] : ''),
                                'configuration_description' => $data['desc'],
                                'configuration_group_id'    => '6',
                                'sort_order'                => '0',
                                'date_added'                => 'now()');

        if (isset($data['set_func'])) {
          $sql_data_array['set_function'] = $data['set_func'];
        }

        if (isset($data['use_func'])) {
          $sql_data_array['use_function'] = $data['use_func'];
        }

        tep_db_perform(TABLE_CONFIGURATION, $sql_data_array);
      }// end foreach

      $tcSql = 'ALTER TABLE orders ADD (ccbill_id varchar(50), ip_address varchar(20))';

      tep_db_query($tcSql);

    }// end install

    function remove() {

      tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");

      tep_db_query('ALTER TABLE orders DROP COLUMN `ccbill_id`, DROP COLUMN `ip_address`');

    }// end remove

    // Return all configuration setting keys defined in install()
    function keys() {

      $keys = array_keys($this->getParams());

      if ($this->check()) {
        foreach ($keys as $key) {
          if (!defined($key)) {
            $this->install($key);
          }// end if
        }// end foreach
      }// end if

      return $keys;
    }// end keys

    function getParams() {
      if (!defined('MODULE_PAYMENT_CCBILL_PREPARE_ORDER_STATUS_ID')) {
        $check_query = tep_db_query("select orders_status_id from " . TABLE_ORDERS_STATUS . " where orders_status_name = 'Preparing [CCBill Standard]' limit 1");

        if (tep_db_num_rows($check_query) < 1) {
          $status_query = tep_db_query("select max(orders_status_id) as status_id from " . TABLE_ORDERS_STATUS);
          $status = tep_db_fetch_array($status_query);

          $status_id = $status['status_id']+1;

          $languages = tep_get_languages();

          foreach ($languages as $lang) {
            tep_db_query("insert into " . TABLE_ORDERS_STATUS . " (orders_status_id, language_id, orders_status_name) values ('" . $status_id . "', '" . $lang['id'] . "', 'Preparing [CCBill Standard]')");
          }

          $flags_query = tep_db_query("describe " . TABLE_ORDERS_STATUS . " public_flag");
          if (tep_db_num_rows($flags_query) == 1) {
            tep_db_query("update " . TABLE_ORDERS_STATUS . " set public_flag = 0 and downloads_flag = 0 where orders_status_id = '" . $status_id . "'");
          }
        } else {
          $check = tep_db_fetch_array($check_query);

          $status_id = $check['orders_status_id'];
        }
      }
      else {
        $status_id = MODULE_PAYMENT_CCBILL_PREPARE_ORDER_STATUS_ID;
      }// end if prepare status id is defined

      if (!defined('MODULE_PAYMENT_CCBILL_TRANSACTIONS_ORDER_STATUS_ID')) {
        $check_query = tep_db_query("select orders_status_id from " . TABLE_ORDERS_STATUS . " where orders_status_name = 'CCBill [Transactions]' limit 1");

        if (tep_db_num_rows($check_query) < 1) {
          $status_query = tep_db_query("select max(orders_status_id) as status_id from " . TABLE_ORDERS_STATUS);
          $status = tep_db_fetch_array($status_query);

          $tx_status_id = $status['status_id']+1;

          $languages = tep_get_languages();

          foreach ($languages as $lang) {
            tep_db_query("insert into " . TABLE_ORDERS_STATUS . " (orders_status_id, language_id, orders_status_name) values ('" . $tx_status_id . "', '" . $lang['id'] . "', 'CCBill [Transactions]')");
          }

          $flags_query = tep_db_query("describe " . TABLE_ORDERS_STATUS . " public_flag");
          if (tep_db_num_rows($flags_query) == 1) {
            tep_db_query("update " . TABLE_ORDERS_STATUS . " set public_flag = 0 and downloads_flag = 0 where orders_status_id = '" . $tx_status_id . "'");
          }
        }
        else {
          $check = tep_db_fetch_array($check_query);

          $tx_status_id = $check['orders_status_id'];
        }// end if/else
      }
      else {
        $tx_status_id = MODULE_PAYMENT_CCBILL_TRANSACTIONS_ORDER_STATUS_ID;
      }// end if/else order status id is defined



      $params = array();

      $params['MODULE_PAYMENT_CCBILL_STATUS'] = array('title' => 'Enable CCBill Payments Standard',
                                                                       'desc' => 'Do you want to accept CCBill payments?',
                                                                       'value' => 'True',
                                                                       'set_func' => 'tep_cfg_select_option(array(\'True\', \'False\'), ');

      $params['MODULE_PAYMENT_CCBILL_STATUS'] = array('title' => 'Enable CCBill Payments Standard',
                                                                       'desc' => 'Do you want to accept CCBill payments?',
                                                                       'value' => 'True',
                                                                       'set_func' => 'tep_cfg_select_option(array(\'True\', \'False\'), ');

      $params['MODULE_PAYMENT_CCBILL_ClientAccountNo'] = array('title' => 'Client Account Number',
                                                            'desc' => 'Please enter your six-digit CCBill client account number; this is needed in order to take payment via CCBill.');

      $params['MODULE_PAYMENT_CCBILL_ClientSubAccountNo'] = array('title' => 'Client SubAccount Number',
                                                            'desc' => 'Please enter your four-digit CCBill client account number; this is needed in order to take payment via CCBill.');
      // $params['MODULE_PAYMENT_CCBILL_FormName'] = array('title' => 'Form Name',
      //                                                     'desc' => 'The name of the CCBill form used to collect payment');


      $params['MODULE_PAYMENT_CCBILL_FormName'] = array('title' => 'FlexForm ID',
                                                            'desc' => 'The ID of the CCBill FlexForm used to collect payment');


      $params['MODULE_PAYMENT_CCBILL_IsFlexForm'] = array('title' => 'Is FlexForm',
                                                       'desc' => 'Select Yes if using a CCBill FlexForm.  Otherwise, select No. <i>Note: Only FlexForms will be supported in future versions.  Classic forms are deprecated.</i>',
                                                       'value' => 'Yes',
                                                       'set_func' => 'tep_cfg_select_option(array(\'Yes\', \'No\'), ');


      $params['MODULE_PAYMENT_CCBILL_Currency'] = array('title' => 'Currency Code',
                                                           'desc' => 'The three-digit CCBill currency code in which payments will be made.',
                                                           'value' => '840');

      $params['MODULE_PAYMENT_CCBILL_Salt'] = array('title' => 'Salt',
                                                           'desc' => 'The salt value is used by CCBill to verify the hash and can be obtained in one of two ways: (1) Contact client support and receive the salt value, OR (2) Create your own salt value (up to 32 alphanumeric characters) and provide it to client support.');

      $params['MODULE_PAYMENT_CCBILL_TRANSACTIONS_ORDER_STATUS_ID'] = array('title' => 'Order Status',
                                                           'desc' => 'Set the status of orders processed (payment complete) with this payment module to this value.',
                                                           'value' => '1',
                                                           'set_func' => 'tep_cfg_pull_down_order_statuses(');

      $params['MODULE_PAYMENT_CCBILL_SORT_ORDER'] = array('title' => 'Sort order of display.',
                                                           'desc' => 'Sort order of display. Lowest is displayed first.',
                                                           'value' => '0');

      return $params;
    }// end getParams

    function sendDebugEmail($response = '', $ipn = false) {
      global $HTTP_POST_VARS, $HTTP_GET_VARS;

      if (tep_not_null(MODULE_PAYMENT_CCBILL_DEBUG_EMAIL)) {
        $email_body = '';

        if (!empty($response)) {
          $email_body .= 'RESPONSE:' . "\n\n" . print_r($response, true) . "\n\n";
        }

        if (!empty($HTTP_POST_VARS)) {
          $email_body .= '$HTTP_POST_VARS:' . "\n\n" . print_r($HTTP_POST_VARS, true) . "\n\n";
        }

        if (!empty($HTTP_GET_VARS)) {
          $email_body .= '$HTTP_GET_VARS:' . "\n\n" . print_r($HTTP_GET_VARS, true) . "\n\n";
        }

        if (!empty($email_body)) {
          tep_mail('', MODULE_PAYMENT_CCBILL_DEBUG_EMAIL, 'CCBill Standard Debug E-Mail' . ($ipn == true ? ' (IPN)' : ''), trim($email_body), STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
        }
      }
    }// end sendDebugEmail


    function logMessage($message)
    {
        error_log('[CCBill] ' . $message);
    }

  }// end class
?>
