<?php
require '../../../../wp-load.php';

global $wpdb;

$admin_id = $wpdb->get_row( $wpdb->prepare( "SELECT ID FROM wp_users WHERE user_login = 'admin'"));
$admin_id = $admin_id->ID;


$result = $wpdb->get_row( $wpdb->prepare( "SELECT whatsapp_number FROM wp_users WHERE ID = %s", $admin_id));
$result = $result->whatsapp_number;
echo $result;