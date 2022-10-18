<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://www.test.com
 * @since      1.0.0
 *
 * @package    Woocommerce_Import
 * @subpackage Woocommerce_Import/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Woocommerce_Import
 * @subpackage Woocommerce_Import/includes
 * @author     Farhan Ali <farhan.a@allshorestaffing.com>
 */
class Woocommerce_Import {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Woocommerce_Import_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'WOOCOMMERCE_IMPORT_VERSION' ) ) {
			$this->version = WOOCOMMERCE_IMPORT_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'woocommerce-import';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Woocommerce_Import_Loader. Orchestrates the hooks of the plugin.
	 * - Woocommerce_Import_i18n. Defines internationalization functionality.
	 * - Woocommerce_Import_Admin. Defines all hooks for the admin area.
	 * - Woocommerce_Import_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-woocommerce-import-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-woocommerce-import-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-woocommerce-import-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-woocommerce-import-public.php';

		$this->loader = new Woocommerce_Import_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Woocommerce_Import_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Woocommerce_Import_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Woocommerce_Import_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Woocommerce_Import_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'vendor/ultramsg/whatsapp-php-sdk/ultramsg.class.php';
		$path = preg_replace( '/wp-content(?!.*wp-content).*/', '', __DIR__ );
		require_once( $path . 'wp-load.php' );

		/**
		 * Add a notification when a new woocommerce order is recieved.
		 *
		 */
		add_action('woocommerce_thankyou', 'new_order_details' );

		function new_order_details($order_id) {
			global $woocommerce;
			$order = new WC_Order( $order_id );
		
			$currency = $order->get_currency();
			// $currency_code = $order->get_currency();
			// $currency_symbol = get_woocommerce_currency_symbol($currency_code);

			$product_info = "";
			// Get and Loop Over Order Items
			foreach ( $order->get_items() as $item_id => $item ) {
				$product_name = $item->get_name();
				$quantity = $item->get_quantity();
				$sub_total = $item->get_subtotal();
				$tax = $item->get_subtotal_tax();

				if(!empty($product_name)){
					$product_info .= `%0a`;
					$product_info .= "*Product Name:* $product_name";
					$product_info .= `%0a`;	
				}
				
				if(!empty($quantity)){
					$product_info .= "*Quantity:* $quantity";
					$product_info .= `%0a`;
				}

				if(!empty($tax)){
					$product_info .= "*Product Tax:* $currency $tax";
					$product_info .= `%0a`;
				}

				if(!empty($sub_total)){
					$product_info .= "*Subtotal:* $currency $sub_total";
					$product_info .= `%0a`;
				}
			}

			// Get Order Shipping	

			// Get Billing Address
			if(!empty($order->get_customer_note())){
				$customer_note = $order->get_customer_note();
			}
			else{
				$customer_note = "No notes.";
			}

			$billing_first_name = $order->get_billing_first_name();
			$billing_last_name = $order->get_billing_last_name();

			if(!empty($order->get_billing_company())){
				$billing_company = $order->get_billing_company();
			}
			else{
				$billing_company = "Not provided.";
			}

			$billing_address_1 = $order->get_billing_address_1();

			if(!empty($order->get_billing_address_2())){
				$billing_address_2 = $order->get_billing_address_2();
			}
			else{
				$billing_address_2 = "Not provided.";
			}

			$billing_city = $order->get_billing_city();
			$billing_state = $order->get_billing_state();
			$billing_postcode = $order->get_billing_postcode();
			$billing_country = $order->get_billing_country();
			$billing_email = $order->get_billing_email();
			$billing_phone = $order->get_billing_phone();

			// Get Shipping Address
			// $shipping_first_name = $order->get_shipping_first_name();
			// $shipping_last_name = $order->get_shipping_last_name();
			// $shipping_company = $order->get_shipping_company();
			// $shipping_address_1 = $order->get_shipping_address_1();
			// $shipping_address_2 = $order->get_shipping_address_2();
			// $shipping_city = $order->get_shipping_city();
			// $shipping_state = $order->get_shipping_state();
			// $shipping_postcode = $order->get_shipping_postcode();
			// $shipping_country = $order->get_shipping_country();

			// Get Order Payment Details
			$payment_method = $order->get_payment_method_title();
			$total = $order->get_total();
			$shipping_charges = $order->get_shipping_total();
			$total_tax = $order->get_cart_tax();

			$products_data = [
				[
					"type" => "text",
					"text" => $product_info
				],
	
				[
					"type" => "text",
					"text" => $currency.''.$total_tax
				],
	
				[
					"type" => "text",
					"text" => $currency.''.$shipping_charges
				],
	
				[
					"type" => "text",
					"text" => $currency.''.$total
				],
	
				[
					"type" => "text",
					"text" => $payment_method
				],
	
				[
					"type" => "text",
					"text" => $customer_note
				],
	
				[
					"type" => "text",
					"text" => $billing_first_name
				],
	
				[
					"type" => "text",
					"text" => $billing_last_name
				],
	
				[
					"type" => "text",
					"text" => $billing_company
				],
	
				[
					"type" => "text",
					"text" => $billing_address_1
				],
	
				[
					"type" => "text",
					"text" => $billing_address_2
				],
	
				[
					"type" => "text",
					"text" => $billing_city
				],
	
				[
					"type" => "text",
					"text" => $billing_state
				],
	
				[
					"type" => "text",
					"text" => $billing_postcode
				],
	
				[
					"type" => "text",
					"text" => $billing_country
				],
	
				[
					"type" => "text",
					"text" => $billing_email
				],
	
				[
					"type" => "text",
					"text" => $billing_phone
				]
			];

			global $wpdb;
			$admin_id = $wpdb->get_row( $wpdb->prepare( "SELECT ID FROM wp_users WHERE user_login = 'admin'"));
			$admin_id = $admin_id->ID;
	
	
			$result = $wpdb->get_row( $wpdb->prepare( "SELECT whatsapp_number FROM wp_users WHERE ID = %s", $admin_id));
			$whatsapp_number = $result->whatsapp_number;

			if(!empty($whatsapp_number)){

				$messageData = '{
					"messaging_product": "whatsapp",
					"recipient_type": "individual",
					"to": '.(int)$whatsapp_number.',
					"type": "template",
					"template": {
					  "name": "woocommerce_order",
					  "language": {
						"code": "en_GB"
					  },
					  "components": [
						{
						  "type": "body",
						  "parameters": '.json_encode($products_data).'
						}
					  ]
					}
				  }';

				
				$ch = curl_init();

				curl_setopt($ch, CURLOPT_URL, 'https://graph.facebook.com/v14.0/104907332408432/messages');
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $messageData);

				$headers = array();
				$headers[] = 'Authorization: Bearer EAASHhDx1ZBEUBAKbheN2xRWoNsMsL5KwmHbXQyQu7DNjeHPXhStP6Ow6QcKsZBl9De7tyjkdKJq7PywjJ0vLvozoS3p3Luuda1TgLRcVvZC92xIE4sR8LnWi1zBaPx7guaZBDR6bIl4jkN1HeivMGBXZB8bTOl8SG1Dp23NbGAp93SpEbTyQbCKjlX9YTyYqJHF9jaEtTUwZDZD';
				$headers[] = 'Content-Type: application/json';
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

				$result = curl_exec($ch);
				if (curl_errno($ch)) {
					echo 'Error:' . curl_error($ch);
				}
				else{
					// print_r($result);
				}
				curl_close($ch);

			}

		}
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Woocommerce_Import_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
