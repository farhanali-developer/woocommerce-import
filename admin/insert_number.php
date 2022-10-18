<?php
require '../vendor/ultramsg/whatsapp-php-sdk/ultramsg.class.php';
require '../../../../wp-load.php';
// require '../vendor/autoload.php';

global $wpdb;
$whatsapp_number = $_POST['whatsapp_number'];


// $admin_id = $wpdb->get_results(  "SELECT ID FROM WP_USERS WHERE USER_LOGIN = 'admin'"  );
$admin_id = $wpdb->get_row( $wpdb->prepare( "SELECT ID FROM wp_users WHERE user_login = 'admin'"));
$admin_id = $admin_id->ID;

update_whatsapp_number($whatsapp_number, $admin_id);


// $result = $wpdb->get_row( "SELECT * FROM WP_USERS WHERE ID = %s", $admin_id );
$result = $wpdb->get_row( $wpdb->prepare( "SELECT whatsapp_number FROM wp_users WHERE ID = %s", $admin_id));
$result = $result->whatsapp_number;
echo $result;




function update_whatsapp_number( $whatsapp_number, $admin_id) {

    global $wpdb;
	$column = $wpdb->get_results( $wpdb->prepare(
		"SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ",
		DB_NAME, 'WP_USERS', 'whatsapp_number'
	) );

    //COLUMN EXISTS
	if ( ! empty( $column ) ) {
        $row = $wpdb->get_results("SELECT whatsapp_number FROM WP_USERS WHERE ID = %d", $admin_id);
        $wpdb->update( 'WP_USERS', array( 'whatsapp_number' => $whatsapp_number ), array( 'ID' => $admin_id ) );
	}

    //COLUMN NOT EXISTS
    else{
        $wpdb->query("ALTER TABLE WP_USERS ADD whatsapp_number VARCHAR(20)");
        $wpdb->update( 'WP_USERS', array( 'whatsapp_number' => $whatsapp_number ), array( 'ID' => $admin_id ) );
    }
}

//THIS IS WORKING CODE
// $curl = curl_init();

// curl_setopt_array($curl, array(
//   CURLOPT_URL => "https://api.ultramsg.com/instance19478/messages/chat",
//   CURLOPT_RETURNTRANSFER => true,
//   CURLOPT_ENCODING => "",
//   CURLOPT_MAXREDIRS => 10,
//   CURLOPT_TIMEOUT => 30,
//   CURLOPT_SSL_VERIFYHOST => 0,
//   CURLOPT_SSL_VERIFYPEER => 0,
//   CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//   CURLOPT_CUSTOMREQUEST => "POST",
//   CURLOPT_POSTFIELDS => "token=38m9u6p26wz5hkxt&to=".$whatsapp_number."&body=WhatsApp API on UltraMsg.com works good&priority=5&referenceId=",
//   CURLOPT_HTTPHEADER => array(
//     "content-type: application/x-www-form-urlencoded"
//   ),
// ));

// $response = curl_exec($curl);
// $err = curl_error($curl);

// curl_close($curl);

// if ($err) {
//   echo "cURL Error #:" . $err;
// } else {
//   echo $response;
// }


// add_action( 'woocommerce_checkout_order_processed', 'create_invoice_for_wc_order',  1, 1  );
// function create_invoice_for_wc_order($order_id) {
//     $order = new WC_Order( $order_id );
//     //order items
//     $items = $order->get_items();
//     print_r($items);
// }


/**
 * Add a notification when a new woocommerce order is recieved.
 *
 */
// add_action('woocommerce_thankyou', 'new_order_details', 10, 1 );

// function new_order_details($order_id) {
    
//     $order = new WC_Order( $order_id );
//     $items = $order->get_items();
//     $customer_address = $order->get_billing_address();
 
//     $user_email = $user_meta->user_email;
//     $image_url = '';
//     $link = '';
    
//     $title = "new order";
//     $message = $order . ', ' . $items . ', ' . $customer_address;

//     echo "$title";
//     echo "$message";

//     print_r($title);
//     print_r($message);

//     wpmobileapp_push($title, $message , $image_url, $link, $lang_2letters = 'all', $send_timestamp = '', $driver_email);
//     error_log("WooCommerce Driver notification: sending push notification to account of $driver_email");

//     get all users with role 'driver' and an active WP session:
//     $drivers = get_users([
//     'role' => 'driver',
//     'meta_key' => 'session_tokens',
//     'meta_compare' => 'EXISTS'
//     ]);
//     notify each of these by push notification
//     foreach ($drivers as $driver) {
//     $driver_email = $driver->user_email;
//     wpmobileapp_push($title, $message , $image_url, $link, $lang_2letters = 'all', $send_timestamp = '', $driver_email);
//     error_log("WooCommerce Driver notification: sending push notification to account of $driver_email");
//     }
// }

?>