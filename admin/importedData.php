<?php

require '../../../../wp-load.php';
require './parsecsv-for-php/parsecsv.lib.php';

//Pusher file
require '../vendor/autoload.php';
$options = array(
    'cluster' => 'ap2',
    'useTLS' => true
);

$pusher = new Pusher\Pusher(
    '72a97a8fccb2425a9ab0',
    'd0ff0985f9a32acd2e6d',
    '1486674',
    $options
);



// $csv_file = $_FILES;

$data = $_POST;
$csv_file = $_FILES;
$result = array();
if(!wp_verify_nonce( $_POST['nonce'], 'ajax-nonce' )){
    wp_send_json_error( "Nonce is incorrect.", 401 );
    die();
    exit();
}

foreach($csv_file as $csv_data){
    //getting csv file name
    $csvFileName = $csv_data["name"];
    $file = fopen($csvFileName, 'r');
    $file_data = fgetcsv($file);
    $fp = file($csvFileName);

    $csv = new \ParseCsv\Csv();
    $csv->auto($csvFileName);
    $csv_file_data = $csv->data;

    //Counting total number of rows in CSV file
    $csv->loadFile($csvFileName);
    $total_rows = $csv->getTotalDataRowCount();
    $data['total_rows'] = $total_rows;
    $row_updated = 0;

    // print("<pre>".print_r($csv_file_data,true)."</pre>");
 
    if (!empty($csv_file_data)):
        $i = 1;
        global $wpdb;

        $new_products = 0;
        $updated_products = 0;
        $start = microtime(true);
        foreach ($csv_file_data as $product){
            if($product[$data['Type']] === "simple" || $product[$data['Type']] === "simple, virtual" || $product[$data['Type']] === "simple, downloadable" || $product[$data['Type']] === "simple, downloadable, virtual"){
                $products = new WC_Product_Simple();
            }
            else if($product[$data['Type']] === "variable"){
                $products = new WC_Product_Variable();
                $variation = $products->get_variation_id();
            }
            else if($product[$data['Type']] === "grouped"){
                $products = new WC_Product_Grouped();
            }
            else if($product[$data['Type']] === "external"){
               $products = new WC_Product_External();
            }

            $product_id = wc_get_product_id_by_sku($product[$data['SKU']]);

            //no product exist with the given SKU so create one
            if (!$product_id){
                try {

                    if($product[$data['Type']] !== "variation" && $product[$data['Type']] !== "variation, downloadable, virtual" && $product[$data['Type']] !== "variation, downloadable" && $product[$data['Type']] !== "variation, virtual"){
                        
                        set_name($products, $product[$data['Name']] );
                        set_sku($products, $product[$data['SKU']]);
                        set_images($products, $product[$data['Images']]);
                        set_featured($products, $product[$data['Is_featured?']]);
                        set_visibility_catalog($products, $product[$data['Visibility_in_catalog']]);
                        set_description($products, $product[$data['Description']]);
                        set_short_description($products, $product[$data['Short_description']]);
                        set_regular_price($products, $product[$data['Regular_price']]);
                        set_sale_price($products, $product[$data['Sale_price']], $product[$data['Date_sale_price_starts']], $product[$data['Date_sale_price_ends']]);
                        set_stock($products, $product[$data['Stock']], $product[$data['Type']], $product[$data['Button_text']], $product[$data['External_URL']]);
                        set_categories($products, $product[$data['Categories']]);
                        set_tags($products, $product[$data['Tags']]);
                        set_upsells($products, $product[$data['Upsells']]);
                        set_crosssells($products, $product[$data['Cross-sells']], $product[$data['Type']]);
                        set_customer_reviews($products, $product[$data['Allow_customer_reviews?']]);
                        set_backorders($products, $product[$data['Backorders_allowed?']]);
                        set_sold_individually($products, $product[$data['Sold_individually?']]);
                        set_purchase_note($products, $product[$data['Purchase_note']], $product[$data['Type']]);
                        set_shipping_class($products, $product[$data['Shipping_class']]);
                        set_published($products, $product[$data['Published']]);
                        set_tax_status($products, $product[$data['Tax_status']]);
                        set_tax_class($products, $product[$data['Tax_class']]);
                        set_virtual_and_downloadable($products, $product[$data['Type']], $product[$data['Download_1_URL']], $product[$data['Download_1_name']], $product[$data['Download_2_URL']], $product[$data['Download_2_name']], $product[$data['Download_limit']], $product[$data['Download_expiry_days']], $product[$data['Weight_(kg)']], $product[$data['Length_(cm)']], $product[$data['Width_(cm)']], $product[$data['Height_(cm)']]);
                        set_attributes($products, $product[$data['Attribute_1_name']], $product[$data['Attribute_1_value(s)']], $product[$data['Attribute_1_visible']], $product[$data['Attribute_1_default']], $product[$data['Attribute_2_name']], $product[$data['Attribute_2_value(s)']], $product[$data['Attribute_2_visible']], $product[$data['Attribute_2_default']], $product[$data['Position']]);
                        set_product_dimensions($products, $product[$data['Type']], $product[$data['Weight_(kg)']], $product[$data['Length_(cm)']], $product[$data['Width_(cm)']], $product[$data['Height_(cm)']]);
                    
                        // if($product['Meta: product_code']){
                        //     add_post_meta($product_id, 'product_code', $product['Meta: product_code']);
                        // }
                        // if($product['Meta: product_company']){
                        //     add_post_meta($product_id, 'product_company', $product['Meta: product_company']);
                        // }
        
                        $products->save();
                    }
                    else{
                            $variation = new WC_Product_Variation();
                            $variation->set_parent_id( $products->get_id() );

                            set_sku($variation, $product[$data['SKU']]);
                            set_description($variation, $product[$data['Description']]);
                            set_short_description($variation, $product[$data['Short_description']]);
                            set_regular_price($variation, $product[$data['Regular_price']]);
                            set_sale_price($variation, $product[$data['Sale_price']], $product[$data['Date_sale_price_starts']], $product[$data['Date_sale_price_ends']]);
                            set_stock($variation, $product[$data['Stock']], $product[$data['Type']], $product[$data['Button_text']], $product[$data['External_URL']]);
                            set_categories($variation, $product[$data['Categories']]);
                            set_tags($variation, $product[$data['Tags']]);
                            set_upsells($variation, $product[$data['Upsells']]);
                            set_crosssells($variation, $product[$data['Cross-sells']], $product[$data['Type']]);
                            set_customer_reviews($variation, $product[$data['Allow_customer_reviews?']]);
                            set_backorders($variation, $product[$data['Backorders_allowed?']]);
                            set_sold_individually($variation, $product[$data['Sold_individually?']]);
                            set_purchase_note($variation, $product[$data['Purchase_note']], $product[$data['Type']]);
                            set_shipping_class($variation, $product[$data['Shipping_class']]);
                            set_published($variation, $product[$data['Published']]);
                            set_tax_status($variation, $product[$data['Tax_status']]);
                            set_tax_class($variation, $product[$data['Tax_class']]);
                            set_virtual_and_downloadable($variation, $product[$data['Type']], $product[$data['Download_1_URL']], $product[$data['Download_1_name']], $product[$data['Download_2_URL']], $product[$data['Download_2_name']], $product[$data['Download_limit']], $product[$data['Download_expiry_days']], $product[$data['Weight_(kg)']], $product[$data['Length_(cm)']], $product[$data['Width_(cm)']], $product[$data['Height_(cm)']]);
                            set_attributes($variation, $product[$data['Attribute_1_name']], $product[$data['Attribute_1_value(s)']], $product[$data['Attribute_1_visible']], $product[$data['Attribute_1_default']], $product[$data['Attribute_2_name']], $product[$data['Attribute_2_value(s)']], $product[$data['Attribute_2_visible']], $product[$data['Attribute_2_default']], $product[$data['Position']]);
                            set_product_dimensions($variation, $product[$data['Type']], $product[$data['Weight_(kg)']], $product[$data['Length_(cm)']], $product[$data['Width_(cm)']], $product[$data['Height_(cm)']]);

                            $variation->save();
                            $data['message'] = "".$variation->get_name()." Added.";
                    }

                    $data['message'] = "".$products->get_name()." Added.";
                    $row_updated++;
                    $data['row_updated'] = $row_updated;
                    $new_products++;
                    $pusher->trigger('my-channel', 'my-event', $data);

                }
                catch (Exception $e) {
                    echo $e->getMessage();
                    echo "<h1>Error in adding product ".$product[$data["Name"]].".</h1>";
                }
            }
                
            //product found
            else{
                try {
                    $update_product = wc_get_product($product_id);
                    if ( $update_product instanceof WC_Product) {

                        if($product[$data['Type']] !== "variation" && $product[$data['Type']] !== "variation, downloadable, virtual" && $product[$data['Type']] !== "variation, downloadable" && $product[$data['Type']] !== "variation, virtual"){
                            
                            set_name($update_product, $product[$data['Name']] );
                            set_sku($update_product, $product[$data['SKU']]);
                            set_images($update_product, $product[$data['Images']]);
                            set_featured($update_product, $product[$data['Is_featured?']]);
                            set_visibility_catalog($update_product, $product[$data['Visibility_in_catalog']]);
                            set_description($update_product, $product[$data['Description']]);
                            set_short_description($update_product, $product[$data['Short_description']]);
                            set_regular_price($update_product, $product[$data['Regular_price']]);
                            set_sale_price($update_product, $product[$data['Sale_price']], $product[$data['Date_sale_price_starts']], $product[$data['Date_sale_price_ends']]);
                            set_stock($update_product, $product[$data['Stock']], $product[$data['Type']], $product[$data['Button_text']], $product[$data['External_URL']]);
                            set_categories($update_product, $product[$data['Categories']]);
                            set_tags($update_product, $product[$data['Tags']]);
                            set_upsells($update_product, $product[$data['Upsells']]);
                            set_crosssells($update_product, $product[$data['Cross-sells']], $product[$data['Type']]);
                            set_customer_reviews($update_product, $product[$data['Allow_customer_reviews?']]);
                            set_backorders($update_product, $product[$data['Backorders_allowed?']]);
                            set_sold_individually($update_product, $product[$data['Sold_individually?']]);
                            set_purchase_note($update_product, $product[$data['Purchase_note']], $product[$data['Type']]);
                            set_shipping_class($update_product, $product[$data['Shipping_class']]);
                            set_published($update_product, $product[$data['Published']]);
                            set_tax_status($update_product, $product[$data['Tax_status']]);
                            set_tax_class($update_product, $product[$data['Tax_class']]);
                            set_virtual_and_downloadable($update_product, $product[$data['Type']], $product[$data['Download_1_URL']], $product[$data['Download_1_name']], $product[$data['Download_2_URL']], $product[$data['Download_2_name']], $product[$data['Download_limit']], $product[$data['Download_expiry_days']], $product[$data['Weight_(kg)']], $product[$data['Length_(cm)']], $product[$data['Width_(cm)']], $product[$data['Height_(cm)']]);
                            set_attributes($update_product, $product[$data['Attribute_1_name']], $product[$data['Attribute_1_value(s)']], $product[$data['Attribute_1_visible']], $product[$data['Attribute_1_default']], $product[$data['Attribute_2_name']], $product[$data['Attribute_2_value(s)']], $product[$data['Attribute_2_visible']], $product[$data['Attribute_2_default']], $product[$data['Position']]);
                            set_product_dimensions($update_product, $product[$data['Type']], $product[$data['Weight_(kg)']], $product[$data['Length_(cm)']], $product[$data['Width_(cm)']], $product[$data['Height_(cm)']]);
                            
                            // if($product['Meta: product_code']){
                            //     add_post_meta($product_id, 'product_code', $product['Meta: product_code']);
                            // }
                            // if($product['Meta: product_company']){
                            //     add_post_meta($product_id, 'product_company', $product['Meta: product_company']);
                            // }

                            $update_product->save();
        
                        
                            // $new_id = $update_product->save();
                            $data['message'] = "".$update_product->get_name()." Updated.";
                        }

                        else{
                            $variation = new WC_Product_Variation();
                            $variation->set_parent_id( $products->get_id() );

                            set_sku($variation, $product[$data['SKU']]);
                            set_description($variation, $product[$data['Description']]);
                            set_short_description($variation, $product[$data['Short_description']]);
                            set_regular_price($variation, $product[$data['Regular_price']]);
                            set_sale_price($variation, $product[$data['Sale_price']], $product[$data['Date_sale_price_starts']], $product[$data['Date_sale_price_ends']]);
                            set_stock($variation, $product[$data['Stock']], $product[$data['Type']], $product[$data['Button_text']], $product[$data['External_URL']]);
                            set_categories($variation, $product[$data['Categories']]);
                            set_tags($variation, $product[$data['Tags']]);
                            set_upsells($variation, $product[$data['Upsells']]);
                            set_crosssells($variation, $product[$data['Cross-sells']], $product[$data['Type']]);
                            set_customer_reviews($variation, $product[$data['Allow_customer_reviews?']]);
                            set_backorders($variation, $product[$data['Backorders_allowed?']]);
                            set_sold_individually($variation, $product[$data['Sold_individually?']]);
                            set_purchase_note($variation, $product[$data['Purchase_note']], $product[$data['Type']]);
                            set_shipping_class($variation, $product[$data['Shipping_class']]);
                            set_published($variation, $product[$data['Published']]);
                            set_tax_status($variation, $product[$data['Tax_status']]);
                            set_tax_class($variation, $product[$data['Tax_class']]);
                            set_virtual_and_downloadable($variation, $product[$data['Type']], $product[$data['Download_1_URL']], $product[$data['Download_1_name']], $product[$data['Download_2_URL']], $product[$data['Download_2_name']], $product[$data['Download_limit']], $product[$data['Download_expiry_days']], $product[$data['Weight_(kg)']], $product[$data['Length_(cm)']], $product[$data['Width_(cm)']], $product[$data['Height_(cm)']]);
                            set_attributes($variation, $product[$data['Attribute_1_name']], $product[$data['Attribute_1_value(s)']], $product[$data['Attribute_1_visible']], $product[$data['Attribute_1_default']], $product[$data['Attribute_2_name']], $product[$data['Attribute_2_value(s)']], $product[$data['Attribute_2_visible']], $product[$data['Attribute_2_default']], $product[$data['Position']]);
                            set_product_dimensions($variation, $product[$data['Type']], $product[$data['Weight_(kg)']], $product[$data['Length_(cm)']], $product[$data['Width_(cm)']], $product[$data['Height_(cm)']]);
                            
                            
                            $variation->save();
                            $data['message'] = "".$variation->get_name()." Updated.";
                        }
    
                        $row_updated++;
                        $data['row_updated'] = $row_updated;
                        $updated_products++;
                        $pusher->trigger('my-channel', 'my-event', $data);
                        
                    }
                }
                catch (Exception $e) {
                    echo $e->getMessage();
                    echo "<h1>Error in adding product ".$update_product["ID"].".</h1>";
                }
            }
        }
        $time_elapsed_secs = microtime(true) - $start;
        $result += ["time_taken" => gmdate("H:i:s", $time_elapsed_secs)];
    endif;

    $result += ["products_added" => $new_products];
    $result += ["products_updated" => $updated_products];

    $data["products_added"] = $new_products;
    $data["products_updated"] = $updated_products;
    $data["time_taken"] = $time_elapsed_secs;
    $pusher->trigger('my-channel', 'my-event', $result);
    echo json_encode($data);
    fclose($file);
}

function pippin_get_image_id($path){
    // detect if is a media resize, and strip resize portion of file name
    if ( preg_match( '/(-\d{1,4}x\d{1,4})\.(jpg|jpeg|png|gif)$/i', $path, $matches ) ) {
        $path = str_ireplace( $matches[1], '', $path );
    }

    // process and include the year / month folders so WP function below finds properly
    if ( preg_match( '/uploads\/(\d{1,4}\/)?(\d{1,2}\/)?(.+)$/i', $path, $matches ) ) {
        unset( $matches[0] );
        $path = implode( '', $matches );
    }

    // at this point, $path contains the year/month/file name (without resize info)

    // call WP native function to find post ID properly
    return attachment_url_to_postid( $path );
}


function get_hierarchical_category_id($parent_id, $parent_child_category){
    if(!empty($parent_id)){

		$term = get_term_by('name', $parent_child_category, 'product_cat');
		if(!empty($term)){
			return $term->term_id;
		}
		else{
			$term = wp_insert_term(
				$parent_child_category,
				'product_cat',
				array(
					'parent' => $parent_id,
				  	'description' => '',
				),
			  );
			  return $term['term_id'];
		}		
	}
	else{

        $term = get_term_by('name', $parent_child_category, 'product_cat');
        if(!empty($term)){
			return $term->term_id;
		}
        else{
            $term = wp_insert_term(
                $parent_child_category,
                'product_cat',
                array(
                  'description' => '',
                ),
              );
              return $term['term_id'];
        }
	}
}

function get_tag_id($tag_name){
	$term = get_term_by('name', $tag_name, 'product_tag');
    if(!empty($term)){
        return $term->term_id;
    }
    else{
        $term = wp_insert_term(
            $tag_name,
            'product_tag',
            array(
              'description' => '',
            ),
          );
        return $term['term_id'];
    }
}

function set_name($products, $name ){
    if(!empty($name) && ($name !== "--------------" && $name !== "Do Not Import")){
        $products->set_name( $name );
    }
}

function set_sku($products, $sku){
    if(!empty($sku) && ($sku !== "--------------" && $sku !== "Do Not Import")){
        $products->set_sku($sku);
    }
}

function set_images($products, $product_images){
    if(!empty($images) && ($product_images !== "--------------" && $product_images !== "Do Not Import")){
        $images = explode(',', $product_images);
        $image_id = pippin_get_image_id($images[0]);
        $products->set_image_id( $image_id );
    
        $images_array = array();
        foreach(array_slice($images, 1) as $img){
            $image_id = pippin_get_image_id($img);
            $images_array[] = $image_id;
        }
        $products->set_gallery_image_ids($images_array);
    }
}

function set_featured($products, $is_featured){
    if(!empty($is_featured) && ($is_featured !== "--------------" && $is_featured !== "Do Not Import")){
        if($is_featured === "1"){
            $products->set_featured(True);
        }
        else{
            $products->set_featured(False);
        }
    }
}

function set_visibility_catalog($products, $visibility){
    if(!empty($visibility) && ($visibility !== "--------------" && $visibility !== "Do Not Import")){
        $products->set_catalog_visibility($visibility);
    }
}

function set_description($products, $description){
    if(!empty($description) && ($description !== "--------------" && $description !== "Do Not Import")){
        $products->set_description($description);
    }
}

function set_short_description($products, $short_description){
    if(!empty($short_description) && ($short_description !== "--------------" && $short_description !== "Do Not Import")){
        $products->set_short_description($short_description);
    }
}

function set_regular_price($products, $regular_price){
    if(!empty($regular_price) && ($regular_price !== "--------------" && $regular_price !== "Do Not Import")){
        $products->set_price($regular_price);
        $products->set_regular_price($regular_price);
    }
}

function set_sale_price($products, $sale_price, $sale_start_date, $sale_end_date){
    if(!empty($sale_price) && ($sale_price !== "--------------" && $sale_price !== "Do Not Import")){
        $products->set_sale_price($sale_price);
        if(!empty($sale_start_date) && ($sale_start_date !== "--------------" && $sale_start_date !== "Do Not Import")){
            $products->set_date_on_sale_from($sale_start_date);
        }
        if(!empty($sale_end_date) && ($sale_end_date !== "--------------" && $sale_end_date !== "Do Not Import")){
            $products->set_date_on_sale_to($sale_end_date);
        }
    }
}

function set_stock($products, $stock_quantity, $product_type, $btn_txt, $btn_url){
    if((!empty($stock_quantity) && ($stock_quantity !== "--------------" && $stock_quantity !== "Do Not Import")) && ($product_type != "external" && ($product_type !== "--------------" && $product_type !== "Do Not Import"))){
        $products->set_stock_quantity($stock_quantity);
        $products->set_manage_stock(TRUE);
        $products->set_stock_status('instock');
    }
    else if(
        (empty($stock_quantity) || ($stock_quantity === "--------------" || $stock_quantity === "Do Not Import"))
         || ($product_type === "external" && ($product_type !== "--------------" && $product_type !== "Do Not Import"))){
        $products->set_stock_quantity(0);
        $products->set_manage_stock(FALSE);
        $products->set_stock_status('outofstock');

        if(!empty($btn_txt) && ($btn_txt !== "--------------" && $btn_txt !== "Do Not Import")){
            $products->set_button_text($btn_txt);
        }

        if(!empty($btn_url) && ($btn_url !== "--------------" && $btn_url !== "Do Not Import")){
            $products->set_product_url($btn_url);
        }
    }
}

function set_categories($products, $categories){
    if(!empty($categories) && ($categories !== "--------------" && $categories !== "Do Not Import")){
    
        if( strpos($categories, "|") !== false ) {
            $parent_categories = explode("|", $categories);
        }
        else if( strpos($categories, ",") !== false ){
            $parent_categories = explode(",", $categories);
        }
        else{
            $parent_categories = explode(",", $categories);
        }

        
        $parent_id = "";
        $categories = array();
        
        foreach ($parent_categories as $parent_category) {

            if(strpos($parent_category, ">") !== false){
                $split_parent = explode(">", $parent_category);

                foreach($split_parent as $parent_child_category){
                    $parent_id = get_hierarchical_category_id($parent_id, $parent_child_category);
                  }

                  $categories[] = $parent_id;
            }
            else{
                $parent_id = get_hierarchical_category_id(NULL, $parent_category);
                $categories[] = $parent_id;
            }
        }
        
        $new_categories = array_unique($categories);
        $products->set_category_ids( $new_categories );
    }
}

function set_tags($products, $product_tags){
    if(!empty($product_tags) && ($product_tags !== "--------------" && $product_tags !== "Do Not Import")){
        $tags_array = explode(', ', $product_tags);
        $tags = array();
        foreach($tags_array as $tag){
            $tag_ID = get_tag_id($tag);
            $tags[] = $tag_ID;
        }
        $products->set_tag_ids( $tags );
    }                    
}

function set_upsells($products, $product_upsells){
    if(!empty($product_upsells) && ($product_upsells !== "--------------" && $product_upsells !== "Do Not Import")){
        $upsell_ids = explode(',', $product_upsells);
        $upsell_array = array();
        foreach($upsell_ids as $id){
            $upsell_id = wc_get_product_id_by_sku($id);
            $upsell_array[] = $upsell_id;
        }
        $products->set_upsell_ids($upsell_array);
    }
}

function set_crosssells($products, $crosssells, $product_type){
    if((!empty($crosssells) && ($crosssells !== "--------------" && $crosssells !== "Do Not Import")) && ($product_type !== "external" && ($product_type !== "--------------" && $product_type !== "Do Not Import"))){
        $cross_sell_ids = explode(',', $crosssells);
        $cross_sell_array = array();
        foreach($cross_sell_ids as $id){
            $cross_sell_id = wc_get_product_id_by_sku($id);
            $cross_sell_array[] = $cross_sell_id;
        }
        $products->set_cross_sell_ids($cross_sell_array);
    }
}

function set_customer_reviews($products, $customer_reviews){
    if((!empty($customer_reviews) && ($customer_reviews !== "--------------" && $customer_reviews !== "Do Not Import")) && $customer_reviews === "1"){
        $products->set_reviews_allowed("1");
    }
    else{
        $products->set_reviews_allowed("0");
    }
}

function set_backorders($products, $backorders_allowed){
    if((!empty($backorders_allowed) && ($backorders_allowed !== "--------------" && $backorders_allowed !== "Do Not Import")) && $backorders_allowed === "1"){
        $products->set_backorders("1");
    }
    else if((!empty($backorders_allowed) && ($backorders_allowed !== "--------------" && $backorders_allowed !== "Do Not Import")) && $backorders_allowed === "notify"){
        $products->set_backorders("notify");
    }
    else if((!empty($backorders_allowed) && ($backorders_allowed !== "--------------" && $backorders_allowed !== "Do Not Import")) && $backorders_allowed === "0"){
        $products->set_backorders("0");
    }
}

function set_sold_individually($products, $sold_individually){
    if((!empty($sold_individually) && ($sold_individually !== "--------------" && $sold_individually !== "Do Not Import")) && $sold_individually === "1"){
        $products->set_sold_individually("1");
    }
    else{
        $products->set_sold_individually("0");
    }
}

function set_purchase_note($products, $purchase_note, $product_type){
    if((!empty($purchase_note) && ($purchase_note !== "--------------" && $purchase_note !== "Do Not Import")) && ($product_type !== "external" && ($product_type !== "--------------" && $product_type !== "Do Not Import"))){
        $products->set_purchase_note($purchase_note);
    }
}

function set_shipping_class($products, $shipping_class){
    if(!empty($shipping_class) && ($shipping_class !== "--------------" && $shipping_class !== "Do Not Import")){
        $products->set_shipping_class_id($shipping_class);
    }
}

function set_published($products, $published){
    if((!empty($published) && ($published !== "--------------" && $published !== "Do Not Import")) && $published === "1"){
        $products->set_status('publish');
    }
    else{
        $products->set_status('draft');
    }
}

function set_tax_status($products, $tax_status){
    if(!empty($tax_status) && $tax_status !== "--------------" && $tax_status !== "Do Not Import"){
        $products->set_tax_status($tax_status);
    }
    else{
        $products->set_tax_status("");
    }
}

function set_tax_class($products, $tax_class){
    if(!empty($tax_class) && $tax_class !== "--------------" && $tax_class !== "Do Not Import"){
        $products->set_tax_class($tax_class);
    }
    else{
        $products->set_tax_class("");
    }
}

function set_virtual_and_downloadable($products, $product_type, $download_1_url, $download_1_name, $download_2_url, $download_2_name, $download_limit, $download_expiry, $weight, $length, $width, $height){
    if($product_type !== "--------------" && $product_type !== "Do Not Import"){
        if(!empty($product_type) && $product_type === "simple, virtual"){
            $products->set_virtual( true );
        }
        else if(!empty($product_type) && $product_type === "simple, downloadable"){
            $products->set_downloadable( true );
            $downloads = array();
            // Creating a download with... yes, WC_Product_Download class
            if(
                (!empty($download_1_name) && !empty($download_1_url))
                &&
                ($download_1_name !== "--------------" && $download_1_name !== "Do Not Import")
                && 
                ($download_1_url !== "--------------" && $download_1_url !== "Do Not Import")
                ){
                $download = new WC_Product_Download();
                // store the image ID in a var
                $image_id = pippin_get_image_id($download_1_url);
                $file_url = wp_get_attachment_url( $image_id ); // attachmend ID should be here
                $download->set_name( $download_1_name );
                $download->set_id( md5( $file_url ) );
                $download->set_file( $file_url );
    
                $downloads[] = $download;
            }
            else if(
                !empty($download_1_url) && !empty($download_1_name) && $download_1_url !== "--------------" && $download_1_url !== "Do Not Import" && $download_1_name !== "--------------" && $download_1_name !== "Do Not Import"
                &&
                !empty($download_2_url) && !empty($download_2_name) && $download_2_url !== "--------------" && $download_2_url !== "Do Not Import" && $download_2_name !== "--------------" && $download_2_name !== "Do Not Import"
                )
                {
                $download = new WC_Product_Download();
                // store the image ID in a var
                $image_id = pippin_get_image_id($download_1_url);
                $file_url = wp_get_attachment_url( $image_id ); // attachmend ID should be here
                $download->set_name( $download_1_name );
                $download->set_id( md5( $file_url ) );
                $download->set_file( $file_url );
    
                $downloads[] = $download;
    
                $download = new WC_Product_Download();
                // store the image ID in a var
                $image_id = pippin_get_image_id($download_2_url);
                $file_url = wp_get_attachment_url( $image_id ); // attachmend ID should be here
                $download->set_name( $download_2_name );
                $download->set_id( md5( $file_url ) );
                $download->set_file( $file_url );
    
                $downloads[] = $download;
            }
    
            $products->set_downloads( $downloads );
    
            $products->set_download_limit( $download_limit ); // can be downloaded only once
            $products->set_download_expiry( $download_expiry ); // expires in a week
        }
        else if(!empty($product_type) && $product_type === "simple, downloadable, virtual" || $product_type !== "simple, downloadable"){
            $products->set_virtual( true );
            $products->set_downloadable( true );
            $downloads = array();
            // Creating a download with... yes, WC_Product_Download class
            if(!empty($download_1_url) && !empty($download_1_name) && $download_1_url !== "--------------" && $download_1_url !== "Do Not Import" && $download_1_name !== "--------------" && $download_1_name !== "Do Not Import"){
                $download = new WC_Product_Download();
                // store the image ID in a var
                $image_id = pippin_get_image_id($download_1_url);
                $file_url = wp_get_attachment_url( $image_id ); // attachmend ID should be here
                $download->set_name( $download_1_name );
                $download->set_id( md5( $file_url ) );
                $download->set_file( $file_url );
    
                $downloads[] = $download;
            }
            else if(!empty($download_1_url) && !empty($download_1_name) && $download_1_url !== "--------------" && $download_1_url !== "Do Not Import" && $download_1_name !== "--------------" && $download_1_name !== "Do Not Import" && !empty($download_2_url) && !empty($download_2_name) && $download_2_url !== "--------------" && $download_2_url !== "Do Not Import" && $download_2_name !== "--------------" && $download_2_name !== "Do Not Import"){
                $download = new WC_Product_Download();
                // store the image ID in a var
                $image_id = pippin_get_image_id($download_1_url);
                $file_url = wp_get_attachment_url( $image_id ); // attachmend ID should be here
                $download->set_name( $download_1_name );
                $download->set_id( md5( $file_url ) );
                $download->set_file( $file_url );
    
                $downloads[] = $download;
    
                $download = new WC_Product_Download();
                // store the image ID in a var
                $image_id = pippin_get_image_id($download_2_url);
                $file_url = wp_get_attachment_url( $image_id ); // attachmend ID should be here
                $download->set_name( $download_2_name );
                $download->set_id( md5( $file_url ) );
                $download->set_file( $file_url );
    
                $downloads[] = $download;
            }
    
            $products->set_downloads( $downloads );
    
            $products->set_download_limit( $download_limit ); // can be downloaded only once
            $products->set_download_expiry( $download_expiry ); // expires in a week
        }
        else {
            set_product_dimensions($products, $product_type, $weight, $length, $width, $height);
        }
    }
}

function set_attributes($products, $attribute_1_name, $attribute_1_value, $attribute_1_visible, $attribute_1_default, $attribute_2_name, $attribute_2_value, $attribute_2_visible, $attribute_2_default, $position){
    if(!empty($attribute_1_name) && $attribute_1_name !== "--------------" && $attribute_1_name !== "Do Not Import" && $attribute_1_value !== "--------------" && $attribute_1_value !== "Do Not Import" && !empty($attribute_2_name) && $attribute_2_name !== "--------------" && $attribute_2_name !== "Do Not Import" && $attribute_2_value !== "--------------" && $attribute_2_value !== "Do Not Import"){
                    
        // that's going to be an array of attributes we add to a product programmatically
        $attributes = array();

        // add the second attribute, it is predefined taxonomy-based attribute
        $attribute = new WC_Product_Attribute();
        $attribute->set_name( $attribute_1_name );
        $attribute_1_vals = preg_split ("/\,/", $attribute_1_value); 
        $attribute->set_options( $attribute_1_vals );
        
        if(!empty($position) && ($position !== "--------------" && $position !== "Do Not Import")){
            $attribute->set_position( $position );
        }

        if($attribute_1_visible == "1"){
            $attribute->set_visible( true );
        }
        else{
            $attribute->set_visible( false );
        }

        $attribute->set_variation( true );
        $attributes[] = $attribute;

        $attribute = new WC_Product_Attribute();
        $attribute->set_name( $attribute_2_name );
        $attribute_2_vals = preg_split ("/\,/", $attribute_2_value); 
        $attribute->set_options( $attribute_2_vals );
        
        if(!empty($position) && ($position !== "--------------" && $position !== "Do Not Import")){
            $attribute->set_position( $position );
        }

        if($attribute_2_visible == "1"){
            $attribute->set_visible( true );
        }
        else{
            $attribute->set_visible( false );
        }

        $attribute->set_variation( true );
        $attributes[] = $attribute;

        $products->set_attributes( $attributes );

        if(!empty($attribute_1_default) && $attribute_1_default !== "--------------" && $attribute_1_default !== "Do Not Import" && !empty($attribute_2_default) && $attribute_2_default !== "--------------" && $attribute_2_default !== "Do Not Import"){
            $attr_1_name = $attribute_1_name;
            $attr_1_default = $attribute_1_default;

            $attr_2_name = $attribute_2_name;
            $attr_2_default = $attribute_2_default;

            $default_attributes = array(
                strtolower($attr_1_name) => $attr_1_default,
                strtolower($attr_2_name) => $attr_2_default
            );

            $products->set_default_attributes( $default_attributes );
        }
        else if(!empty($attribute_1_default) && $attribute_1_default !== "--------------" && $attribute_1_default !== "Do Not Import"){
            $attr_1_name = $attribute_1_name;
            $attr_1_default = $attribute_1_default;

            $default_attributes = array(
                strtolower($attr_1_name) => $attr_1_default
            );

            $products->set_default_attributes( $default_attributes );
        }
        else if(!empty($attribute_2_default) && $attribute_2_default !== "--------------" && $attribute_2_default !== "Do Not Import"){
            $attr_2_name = $attribute_2_name;
            $attr_2_default = $attribute_2_default;

            $default_attributes = array(
                strtolower($attr_2_name) => $attr_2_default
            );

            $products->set_default_attributes( $default_attributes );
        }

        
    }
    else if(!empty($attribute_1_name) && $attribute_1_name !== "--------------" && $attribute_1_name !== "Do Not Import"){
        // that's going to be an array of attributes we add to a product programmatically
        $attributes = array();

        // add the second attribute, it is predefined taxonomy-based attribute
        $attribute = new WC_Product_Attribute();
        $attribute->set_name( $attribute_1_name );
        $attribute_1_vals = preg_split ("/\,/", $attribute_1_value); 
        $attribute->set_options( $attribute_1_vals );
        
        if(!empty($position) && $position !== "--------------" && $position !== "Do Not Import"){
            $attribute->set_position( $position );
        }
        
        if($attribute_1_visible == "1"){
            $attribute->set_visible( true );
        }
        else{
            $attribute->set_visible( false );
        }

        $attribute->set_variation( true );
        $attributes[] = $attribute;

        $products->set_attributes( $attributes );

        if(!empty($attribute_1_default) && $attribute_1_default !== "--------------" && $attribute_1_default !== "Do Not Import" && !empty($attribute_2_default) && $attribute_2_default !== "--------------" && $attribute_2_default !== "Do Not Import"){
            $attr_1_name = $attribute_1_name;
            $attr_1_default = $attribute_1_default;

            $attr_2_name = $attribute_2_name;
            $attr_2_default = $attribute_2_default;

            $default_attributes = array(
                strtolower($attr_1_name) => $attr_1_default,
                strtolower($attr_2_name) => $attr_2_default
            );

            $products->set_default_attributes( $default_attributes );
        }
        else if(!empty($attribute_1_default) && $attribute_1_default !== "--------------" && $attribute_1_default !== "Do Not Import"){
            $attr_1_name = $attribute_1_name;
            $attr_1_default = $attribute_1_default;

            $default_attributes = array(
                strtolower($attr_1_name) => $attr_1_default
            );

            $products->set_default_attributes( $default_attributes );
        }
        else if(!empty($attribute_2_default) && $attribute_2_default !== "--------------" && $attribute_2_default !== "Do Not Import"){
            $attr_2_name = $attribute_2_name;
            $attr_2_default = $attribute_2_default;

            $default_attributes = array(
                strtolower($attr_2_name) => $attr_2_default
            );

            $products->set_default_attributes( $default_attributes );
        }
    }
    else if(!empty($attribute_2_name) && $attribute_2_name !== "--------------" && $attribute_2_name !== "Do Not Import"){
        // that's going to be an array of attributes we add to a product programmatically
        $attributes = array();

        // add the second attribute, it is predefined taxonomy-based attribute
        $attribute = new WC_Product_Attribute();
        $attribute->set_name( $attribute_2_name );
        $attribute_1_vals = preg_split ("/\,/", $attribute_2_value); 
        $attribute->set_options( $attribute_1_vals );

        if(!empty($position) && $position !== "--------------" && $position !== "Do Not Import"){
            $attribute->set_position( $position );
        }

        
        if($attribute_2_visible == "1"){
            $attribute->set_visible( true );
        }
        else{
            $attribute->set_visible( false );
        }

        $attribute->set_variation( true );
        $attributes[] = $attribute;

        $products->set_attributes( $attributes );

        if(!empty($attribute_1_default) && $attribute_1_default !== "--------------" && $attribute_1_default !== "Do Not Import" && !empty($attribute_2_default) && $attribute_2_default !== "--------------" && $attribute_2_default !== "Do Not Import"){
            $attr_1_name = $attribute_1_name;
            $attr_1_default = $attribute_1_default;

            $attr_2_name = $attribute_2_name;
            $attr_2_default = $attribute_2_default;

            $default_attributes = array(
                strtolower($attr_1_name) => $attr_1_default,
                strtolower($attr_2_name) => $attr_2_default
            );

            $products->set_default_attributes( $default_attributes );
        }
        else if(!empty($attribute_1_default) && $attribute_1_default !== "--------------" && $attribute_1_default !== "Do Not Import"){
            $attr_1_name = $attribute_1_name;
            $attr_1_default = $attribute_1_default;

            $default_attributes = array(
                strtolower($attr_1_name) => $attr_1_default
            );

            $products->set_default_attributes( $default_attributes );
        }
        else if(!empty($attribute_2_default) && $attribute_2_default !== "--------------" && $attribute_2_default !== "Do Not Import"){
            $attr_2_name = $attribute_2_name;
            $attr_2_default = $attribute_2_default;

            $default_attributes = array(
                strtolower($attr_2_name) => $attr_2_default
            );

            $products->set_default_attributes( $default_attributes );
        }
    }
}

function set_product_dimensions($products, $product_type, $weight, $length, $width, $height){
    if($product_type !== "--------------" && $product_type !== "Do Not Import"){
        if($product_type !== "simple, virtual" || $product_type !== "simple, downloadable, virtual"){
            if(!empty($weight) && ($weight !== "--------------" && $weight !== "Do Not Import")){
                $products->set_weight($weight);
            }
        
            if(!empty($length) && ($length !== "--------------" && $length !== "Do Not Import")){
                $products->set_length($length);
            }
        
            if(!empty($width) && ($width !== "--------------" && $width !== "Do Not Import")){
                $products->set_width($width);
            }
        
            if(!empty($height) && ($height !== "--------------" && $height !== "Do Not Import")){
                $products->set_height($height);
            }
        }
    }
}