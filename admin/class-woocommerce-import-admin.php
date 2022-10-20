<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.test.com
 * @since      1.0.0
 *
 * @package    Woocommerce_Import
 * @subpackage Woocommerce_Import/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Woocommerce_Import
 * @subpackage Woocommerce_Import/admin
 * @author     Farhan Ali <farhan.a@allshorestaffing.com>
 */
class Woocommerce_Import_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woocommerce_Import_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woocommerce_Import_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/woocommerce-import-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woocommerce_Import_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woocommerce_Import_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/woocommerce-import-admin.js', array( 'jquery' ), $this->version, false );

	}

}


function enqueue_admin_files($hook){
	$hook_parts = explode('_page_', $hook);
    $menu_slug = array_pop($hook_parts);
    
    if ( ! in_array( $menu_slug, array('import_products', 'whatsapp_integration') ) ) {
        return;
    }
	wp_enqueue_style('sweetalert2', "//cdn.jsdelivr.net/npm/@sweetalert2/theme-dark@4/dark.css");
    wp_enqueue_style('bootstrap', "https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.2.2/css/bootstrap.min.css");
    // wp_enqueue_style('noty', "https://cdnjs.cloudflare.com/ajax/libs/noty/3.1.4/noty.min.css");
    // wp_enqueue_style('animate', "https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css");
    wp_enqueue_style('fontawesome', "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css");
    wp_enqueue_style('izitoast', "https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.min.css");
    // wp_enqueue_style('intlTelInput', "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css");


    wp_enqueue_script('sweetalert2', "https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.4.35/sweetalert2.min.js");
    wp_enqueue_script('bootstrap', "https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.2.2/js/bootstrap.min.js");
    wp_enqueue_script('pusher', "https://js.pusher.com/7.2/pusher.min.js");
    // wp_enqueue_script('noty', "https://cdnjs.cloudflare.com/ajax/libs/noty/3.1.4/noty.min.js");
    wp_enqueue_script('fontawesome', "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/js/all.min.js");
    wp_enqueue_script('izitoast', "https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js");
    // wp_enqueue_script('intlTelInput', "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js");
}
add_action("admin_enqueue_scripts", "enqueue_admin_files");

add_action('admin_menu', 'my_menu_pages');
function my_menu_pages(){
    add_menu_page('Import Products', 'Import Products', 'manage_options', 'import_products', 'import_products_function' );
	add_submenu_page('import_products', 'Whatsapp Integration', 'Whatsapp Integration', 'manage_options', 'whatsapp_integration', 'whatsapp_integration_menu');
}

function import_products_function(){ ?>
	<div class="container-fluid mt-5">
		<div class="row">
			<div class="col-12">
				<h2>Import WooCommerce Products</h2>
				<form id="woocommerce_products_file" enctype="multipart/form-data">
					<input type="file" value="Upload CSV file *" name="csv_file" accept=".csv" id="csv-file" class="form-control-file" required />
					<button class="submit-button btn btn-primary" id="upload-csv-file">Upload CSV File</button>
					<table id="mapping-data" class="table table-hover">
						<tr>
							<th scope="col">#</th>
							<th scope="col">Woocoommerce Columns</th>
							<th scope="col">CSV file Columns</th>
						</tr>
						<tr>
							<td><label for="ID">ID</label></td>
							<td>
								<select class="csv-columns form-control" id="ID">
								</select>
							</td>
						</tr>
						<tr>
							
							<td><label for="type">Type</label></td>
							<td>
								<select class="csv-columns form-control" id="type">
								</select>
							</td>
						</tr>
						<tr>
							
							<td><label for="sku">SKU</label></td>
							<td>
								<select class="csv-columns form-control" id="sku">
								</select>
							</td>
						</tr>
						<tr>
							
							<td><label for="name">Name</label></td>
							<td>
								<select class="csv-columns form-control" id="name">
								</select>
							</td>
						</tr>
						<tr>
							
							<td><label for="published">Published</label></td>
							<td>
								<select class="csv-columns form-control" id="published">
								</select>
							</td>
						</tr>
						<tr>
							
							<td><label for="is-featured">Is featured?</label></td>
							<td>
								<select class="csv-columns form-control" id="is-featured">
								</select>
							</td>
						</tr>
						<tr>
							
							<td><label for="visibility-in-catalog">Visibility in catalog</label></td>
							<td>
								<select class="csv-columns form-control" id="visibility-in-catalog">
								</select>
							</td>
						</tr>
						<tr>
							
							<td><label for="short-description">Short description</label></td>
							<td>
								<select class="csv-columns form-control" id="short-description">
								</select>
							</td>
						</tr>
						<tr>
							
							<td><label for="description">Description</label></td>
							<td>
								<select class="csv-columns form-control" id="description">
								</select>
							</td>
						</tr>
						<tr>
							
							<td><label for="sale-starts">Date sale price starts</label></td>
							<td>
								<select class="csv-columns form-control" id="sale-starts">
								</select>
							</td>
						</tr>
						<tr>
							
							<td><label for="sale-ends">Date sale price ends</label></td>
							<td>
								<select class="csv-columns form-control" id="sale-ends">
								</select>
							</td>
						</tr>
						<tr>
							
							<td><label for="tax-status">Tax status</label></td>
							<td>
								<select class="csv-columns form-control" id="tax-status">
								</select>
							</td>
						</tr>
						<tr>
							
							<td><label for="tax-class">Tax class</label></td>
							<td>
								<select class="csv-columns form-control" id="tax-class">
								</select>
							</td>
						</tr>
						<tr>
							
							<td><label for="in-stock">In stock?</label></td>
							<td>
								<select class="csv-columns form-control" id="in-stock">
								</select>
							</td>
						</tr>
						<tr>
							
							<td><label for="stock">Stock</label></td>
							<td>
								<select class="csv-columns form-control" id="stock">
								</select>
							</td>
						</tr>
						<tr>
							
							<td><label for="low-stock-amount">Low stock amount</label></td>
							<td>
								<select class="csv-columns form-control" id="low-stock-amount">
								</select>
							</td>
						</tr>
						<tr>
							
							<td><label for="backorders-allowed">Backorders allowed?</label></td>
							<td>
								<select class="csv-columns form-control" id="backorders-allowed">
								</select>
							</td>
						</tr>
						<tr>
							
							<td><label for="sold-individually">Sold individually?</label></td>
							<td>
								<select class="csv-columns form-control" id="sold-individually">
								</select>
							</td>
						</tr>
						<tr>
							
							<td><label for="weight-kg">Weight (kg)</label></td>
							<td>
								<select class="csv-columns form-control" id="weight-kg">
								</select>
							</td>
						</tr>
						<tr>
							
							<td><label for="length-cm">Length (cm)</label></td>
							<td>
								<select class="csv-columns form-control" id="length-cm">
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="width-cm">Width (cm)</label></td>
							<td>
								<select class="csv-columns form-control" id="width-cm">
								</select>
							</td>
						</tr>
						<tr>
							
							<td><label for="height-cm">Height (cm)</label></td>
							<td>
								<select class="csv-columns form-control" id="height-cm">
								</select>
							</td>
						</tr>
						<tr>
							
							<td><label for="allow-reviews">Allow customer reviews?</label></td>
							<td>
								<select class="csv-columns form-control" id="allow-reviews">
								</select>
							</td>
						</tr>
						<tr>
							
							<td><label for="purchase-note">Purchase note</label></td>
							<td>
								<select class="csv-columns form-control" id="purchase-note">
								</select>
							</td>
						</tr>
						<tr>
							
							<td><label for="sale-price">Sale price</label></td>
							<td>
								<select class="csv-columns form-control" id="sale-price">
								</select>
							</td>
						</tr>
						<tr>
							
							<td><label for="regular-price">Regular price</label></td>
							<td>
								<select class="csv-columns form-control" id="regular-price">
								</select>
							</td>
						</tr>
						<tr>
							
							<td><label for="categories">Categories</label></td>
							<td>
								<select class="csv-columns form-control" id="categories">
								</select>
							</td>
						</tr>
						<tr>
							
							<td><label for="tags">Tags</label></td>
							<td>
								<select class="csv-columns form-control" id="tags">
								</select>
							</td>
						</tr>
						<tr>
							
							<td><label for="shipping-class">Shipping class</label></td>
							<td>
								<select class="csv-columns form-control" id="shipping-class">
								</select>
							</td>
						</tr>
						<tr>
							
							<td><label for="images">Images</label></td>
							<td>
								<select class="csv-columns form-control" id="images">
								</select>
							</td>
						</tr>
						<tr>
							
							<td><label for="download-limit">Download limit</label></td>
							<td>
								<select class="csv-columns form-control" id="download-limit">
								</select>
							</td>
						</tr>
						<tr>
							
							<td><label for="download-expiry">Download expiry days</label></td>
							<td>
								<select class="csv-columns form-control" id="download-expiry">
								</select>
							</td>
						</tr>
						<tr>
							
							<td><label for="parent">Parent</label></td>
							<td>
								<select class="csv-columns form-control" id="parent">
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="grouped-products">Grouped products</label></td>
							<td>
								<select class="csv-columns form-control" id="grouped-products">
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="upsells">Upsells</label></td>
							<td>
								<select class="csv-columns form-control" id="upsells">
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="cross-sells">Cross-sells</label></td>
							<td>
								<select class="csv-columns form-control" id="cross-sells">
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="external-url">External URL</label></td>
							<td>
								<select class="csv-columns form-control" id="external-url">
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="button-text">Button text</label></td>
							<td>
								<select class="csv-columns form-control" id="button-text">
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="position">Position</label></td>
							<td>
								<select class="csv-columns form-control" id="position">
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="meta-product-code">Meta: Product Code</label></td>
							<td>
								<select class="csv-columns form-control" id="meta-product-code">
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="meta-product-company">Meta: Product Company</label></td>
							<td>
								<select class="csv-columns form-control" id="meta-product-company">
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="attribute-1-name">Attribute 1 name</label></td>
							<td>
								<select class="csv-columns form-control" id="attribute-1-name">
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="attribute-1-values">Attribute 1 value(s)</label></td>
							<td>
								<select class="csv-columns form-control" id="attribute-1-values">
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="attribute-1-visible">Attribute 1 visible</label></td>
							<td>
								<select class="csv-columns form-control" id="attribute-1-visible">
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="attribute-1-global">Attribute 1 global</label></td>
							<td>
								<select class="csv-columns form-control" id="attribute-1-global">
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="attribute-1-default">Attribute 1 default</label></td>
							<td>
								<select class="csv-columns form-control" id="attribute-1-default">
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="attribute-2-name">Attribute 2 name</label></td>
							<td>
								<select class="csv-columns form-control" id="attribute-2-name">
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="attribute-2-values">Attribute 2 value(s)</label></td>
							<td>
								<select class="csv-columns form-control" id="attribute-2-values">
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="attribute-2-visible">Attribute 2 visible</label></td>
							<td>
								<select class="csv-columns form-control" id="attribute-2-visible">
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="attribute-2-global">Attribute 2 global</label></td>
							<td>
								<select class="csv-columns form-control" id="attribute-2-global">
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="attribute-2-default">Attribute 2 default</label></td>
							<td>
								<select class="csv-columns form-control" id="attribute-2-default">
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="download-1-id">Download 1 ID</label></td>
							<td>
								<select class="csv-columns form-control" id="download-1-id">
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="download-1-name">Download 1 name</label></td>
							<td>
								<select class="csv-columns form-control" id="download-1-name">
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="download-1-url">Download 1 URL</label></td>
							<td>
								<select class="csv-columns form-control" id="download-1-url">
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="download-2-id">Download 2 ID</label></td>
							<td>
								<select class="csv-columns form-control" id="download-2-id">
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="download-2-name">Download 2 name</label></td>
							<td>
								<select class="csv-columns form-control" id="download-2-name">
								</select>
							</td>
						</tr>
						<tr> 
							<td><label for="download-2-url">Download 2 URL</label></td>
							<td>
								<select class="csv-columns form-control" id="download-2-url">
								</select>
							</td>
						</tr>

					</table>
					<input type="submit" value="Submit" class="submit-button btn btn-primary" id="submit-form-btn" />
				</form>
				<div id="results"></div>
				<!-- Modal -->
				<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true" data-keyboard="false" data-backdrop="static">
					<div class="modal-dialog modal-dialog-centered" role="document">
						<div class="modal-content">
						<div class="modal-header">
							<h5 class="modal-title" id="exampleModalLongTitle">Loading<span>.</span><span>.</span><span>.</span> <div id="product_progress"></div></h5>
						</div>
						<div class="modal-body">
							<div class="progress">
								<div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
							</div>
						</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
		$importedData = plugin_dir_url( __FILE__ ) . "importedData.php";
		$mappedData = plugin_dir_url( __FILE__ ) . "mappedData.php";
		// $currentDirectory = plugin_dir_url( __FILE__ );
	?>

	<script>
		jQuery(document).ready(function($){
			"use strict";
			hide_model();

			// Enable pusher logging - don't include this in production
			// Pusher.logToConsole = true;

			var pusher = new Pusher('72a97a8fccb2425a9ab0', {
				cluster: 'ap2'
			});

			var channel = pusher.subscribe('my-channel');
			channel.bind('my-event', function(data) {
				disable_buttons();
				show_model();

				if(data['row_updated'] == data['total_rows']){
					enable_buttons();
					hide_progress_bar();
					hide_model();
					sweetalert_success(data['products_added'], data['products_updated'], data['time_taken']);
				}
				else{
					show_progress_bar(data['row_updated'], data['total_rows']);
					toast_function(data['message']);
				}
				
			});

			
			$("#upload-csv-file").click(function(e){
				e.preventDefault();

				let mappedData = "<?php echo $mappedData; ?>";

				var file_data = $('#csv-file').prop('files')[0];   
    			var form_data = new FormData();
				form_data.append('file', file_data);

				$.ajax({
					url: mappedData,
					method: "POST",
					contentType: "application/json",
					// dataType: 'json',
					data: form_data,
					cache: false,
					contentType: false,
					processData: false,
					success: function(data){
						
						var result = JSON.parse(data);
						$('.csv-columns').append(
							$('<option>', { 
							value: "Do Not Import",
							text : "Do Not Import" 
							}),
							$('<option>', { 
							value: "--------------",
							text : "--------------",
							selected: true
							})
						);

						for (let val of result) {
							$('.csv-columns').append($('<option>', { 
								value: val,
								text : val
							}));
						}

						for (let val of result) {
							var labelFor = $("label:contains("+val+")").attr("for");

							let labelText = "";
							$("label").filter(function() {

								if($(this).text() === val){
									labelText = val;
								}
							});
							$("select#"+labelFor+"").val(labelText);
						}

						$("#mapping-data, #submit-form-btn").show(300);
					},
					error: function (jqXHR, textStatus, exception) {
						console.log(jqXHR);
						console.log(textStatus);
						sweetalert_error();
					}
				});
			});

			$('#woocommerce_products_file').on('submit', function(e){
				e.preventDefault();
				hide_model();
    			var form_data = new FormData();
				var file_data = $('#csv-file').prop('files')[0];
				form_data.append("file", file_data);
				form_data.append("nonce", "<?php echo wp_create_nonce('ajax-nonce'); ?>");

				$("#woocommerce_products_file select").each(function(){
					var select_id = $(this).attr("id");
					var selected_option_text = $(this).find(":selected").text();
					var label_value = $("label[for="+select_id+"]").text();
					form_data.append(label_value, selected_option_text);
				});

				let importedData = "<?php echo $importedData; ?>";
				$.ajax({
					url: importedData,
					method: "POST",
					contentType: "application/json",
					data: form_data,
					cache: false,
					contentType: false,
					processData: false,
					async: true,
					beforeSend: function(){
						disable_buttons();
						show_model();

						// Enable pusher logging - don't include this in production
						// Pusher.logToConsole = true;

						var pusher = new Pusher('72a97a8fccb2425a9ab0', {
							cluster: 'ap2'
						});

						var channel = pusher.subscribe('my-channel');
						channel.bind('my-event', function(data) {
							show_progress_bar(data['row_updated'], data['total_rows']);
							toast_function(data['message']);
						});
					},
					complete: function(){},
					success: function(data){
						hide_progress_bar();
						hide_model();
						$("#mapping-data, #submit-form-btn").hide(200);
						$("#csv-file").val('');
						
						var data = JSON.parse(data);

						console.log(data);

						sweetalert_success(data['products_added'], data['products_updated'], data['time_taken']);
					},
					error: function (err) {
						// console.log(jqXHR);
						// console.log(textStatus);
						sweetalert_error(err);
					}
				});

			});


			function show_model(){
				$("#myModal").modal('show');
			}

			function hide_model(){
				$("#myModal").modal('hide');
			}

			function show_progress_bar(row_updated, total_rows){
				var progress = Math.floor(row_updated/total_rows*100);
				$("#myModal #product_progress").text(row_updated+"/"+total_rows);
				$("#myModal .progress-bar").css("width", progress + "%");
				$("#myModal .progress-bar").text(progress+"%");
			}

			function hide_progress_bar(){
				$("#myModal .progress-bar").css("width", "0%");
				$("#myModal .progress-bar").text("");
				$("#myModal").modal('hide');
			}

			function enable_buttons(){
				$("#csv-file, #upload-csv-file, #submit-form-btn").prop("disabled", false);
			}

			function disable_buttons(){
				$("#csv-file, #upload-csv-file, #submit-form-btn").prop("disabled", true);
			}

			function toast_function(custom_message){
				iziToast.info({
					message: custom_message,
					position: 'topRight',
					timeout: 1500,
					overlay: true,
					icon: 'fa-solid fa-check',
					iconColor: '#28a745',
				});
			}

			function sweetalert_success(products_added, products_updated, time_taken){

				if(products_added > 0 && products_updated > 0){
					var data = '<strong>Products Added: </strong>'+products_added+'<br><strong>Products Updated: </strong>'+products_updated+'<br><strong>Time Elapsed: </strong>'+time_taken+'';
				}
				else if(products_added > 0 && products_updated == 0){
					var data = '<strong>Products Added: </strong>'+products_added+'<br><strong>Time Elapsed: </strong>'+time_taken+'';
				}
				else if(products_added == 0 && products_updated > 0){
					var data = '<strong>Products Updated: </strong>'+products_updated+'<br><strong>Time Elapsed: </strong>'+time_taken+'';
				}

				Swal.fire({
					position: 'center',
					icon: 'success',
					title: 'Products Imported Successfully',
					html: data,
					allowOutsideClick: false,
					allowEscapeKey: false
				}).then((result) => {
					if (result.isConfirmed) {
						$("#csv-file, #upload-csv-file, #submit-form-btn").attr("disabled", false);
					}
				});
			}

			function sweetalert_error(err){
				Swal.fire({
					position: 'center',
					icon: 'error',
					title: err,
					showConfirmButton: true,
					// timer: 1500
				});
			}

		});
	</script>
	<?php
}

function whatsapp_integration_menu(){
	?>
	<div class="container-fluid mt-5">
		<div class="row">
			<div class="col-12 col-md-3">
				<h2>WhatsApp Integration</h2>
				<form method="POST" id="whatsapp-integration-form">
				<div class="form-group">
					<label for="whatsapp-number">Enter Your WhatsApp Number <span class="text-danger">*</span></label>
					<input type="tel" name="whatsapp-number" id="whatsapp-number" class="form-control mt-2" placeholder="+923463446106" />
				</div>
				<button type="submit" class="btn btn-primary mt-3">Submit</button>
				</form>
			</div>
		</div>
	</div>

	<?php 
		$insert_number = plugin_dir_url( __FILE__ ) . "insert_number.php";
		$get_number = plugin_dir_url( __FILE__ ) . "get_number.php";
	?>
	<script>
		jQuery(document).ready(function($){
			"use-strict";

			let get_number = "<?php echo $get_number; ?>";
			$.ajax({
				url: get_number,
				method: "GET",
				contentType: "application/json",
				cache: false,
				contentType: false,
				processData: false,
				async: true,
				success: function(data){
					console.log(data);
					$("#whatsapp-number").val(data);
				}
			});

			$("#whatsapp-integration-form").on("submit", function(e){
				e.preventDefault();
				var form_data = new FormData();
				var whatsapp_number = $("#whatsapp-number").val();
				form_data.append("whatsapp_number", whatsapp_number);
				let insert_number = "<?php echo $insert_number; ?>";
				$.ajax({
					url: insert_number,
					method: "POST",
					contentType: "application/json",
					data: form_data,
					cache: false,
					contentType: false,
					processData: false,
					async: true,
					success: function(data){
						$("#whatsapp-number").val(data);
						Swal.fire({
							position: 'top-end',
							icon: 'success',
							title: 'Number Saved Successfully.',
							showConfirmButton: false,
							timer: 1500
						});
					},
					error: function (err) {
						Swal.fire({
							position: 'top-end',
							icon: 'error',
							title: 'Number cannot be saved.',
							showConfirmButton: false,
							timer: 1500
						});
					}
				});
			});
		});
	</script>
	<?php
}