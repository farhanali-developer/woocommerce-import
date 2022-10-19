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
                        if(!empty($product[$data['Name']])){
                            $products->set_name( $product[$data['Name']] );
                        }
                    
                        if(!empty($product[$data['SKU']])){
                            $products->set_sku($product[$data['SKU']]);
                            $products->set_slug($product[$data['SKU']]);
                        }
        
                        if(!empty($product[$data['Images']])){
        
                            $images = explode(',', $product[$data['Images']]);
                            $image_id = pippin_get_image_id($images[0]);
                            $products->set_image_id( $image_id );
    
                            $images_array = array();
                            foreach(array_slice($images, 1) as $img){
                                $image_id = pippin_get_image_id($img);
                                $images_array[] = $image_id;
                            }
                            $products->set_gallery_image_ids($images_array);
                        }
                    
                        if(!empty($product[$data['Is_featured?']]) && $product[$data['Is_featured?']] === "1"){
                            $products->set_featured(True);
                        }
                        else{
                            $products->set_featured(False);
                        }
                        
                        if(!empty($product[$data['Visibility_in_catalog']])){
                            $products->set_catalog_visibility($product[$data['Visibility_in_catalog']]);
                        }
                    
                        if(!empty($product[$data['Description']])){
                            $products->set_description($product[$data['Description']]);
                        }
                    
                        if(!empty($product[$data['Short_description']])){
                            $products->set_short_description($product[$data['Short_description']]);
                        }
                    
                        if(!empty($product[$data['Regular_price']])){
                            $products->set_price($product[$data['Regular_price']]);
                            $products->set_regular_price($product[$data['Regular_price']]);
                        }
                    
                        if(!empty($product[$data['Sale_price']])){
                            $products->set_sale_price($product[$data['Sale_price']]);
                            if(!empty($product[$data['Date_sale_price_starts']])){
                                $products->set_date_on_sale_from($product[$data['Date_sale_price_starts']]);
                            }
                            if(!empty($product[$data['Date_sale_price_ends']])){
                                $products->set_date_on_sale_to($product[$data['Date_sale_price_ends']]);
                            }
                        }
                        
                        if(!empty($product[$data['Stock']]) && $product[$data['Type']] != "external"){
                            $products->set_stock_quantity($product[$data['Stock']]);
                            $products->set_manage_stock(TRUE);
                            $products->set_stock_status('instock');
                        }
                        else if(empty($product[$data['Stock']]) || $product[$data['Type']] === "external"){
                            $products->set_stock_quantity(0);
                            $products->set_manage_stock(FALSE);
                            $products->set_stock_status('outofstock');
    
                            if(!empty($product[$data['Button_text']])){
                                $products->set_button_text($product[$data['Button_text']]);
                            }
    
                            if(!empty($product[$data['External_URL']])){
                                $products->set_product_url( $product[$data['External_URL']] );
                            }
                        }
                        
        
                        //Set Categories
                        if(!empty($product[$data['Categories']])){
    
                            if( strpos($product[$data['Categories']], "|") !== false ) {
                                $parent_categories = explode("|", $product[$data['Categories']]);
                            }
                            else if( strpos($product[$data['Categories']], ",") !== false ){
                                $parent_categories = explode(",", $product[$data['Categories']]);
                            }
                            else{
                                $parent_categories = explode(",", $product[$data['Categories']]);
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
        
                        //Set Tags
                        if(!empty($product[$data['Tags']])){
                            $tags_array = explode(', ', $product[$data['Tags']]);
                            $tags = array();
                            foreach($tags_array as $tag){
                                $tag_ID = get_tag_id($tag);
                                $tags[] = $tag_ID;
                            }
                            $products->set_tag_ids( $tags );
                        }                    
                    
                        if(!empty($product[$data['Upsells']])){
                            $upsell_ids = explode(',', $product[$data['Upsells']]);
                            $upsell_array = array();
                            foreach($upsell_ids as $id){
                                $upsell_id = wc_get_product_id_by_sku($id);
                                $upsell_array[] = $upsell_id;
                            }
                            $products->set_upsell_ids($upsell_array);
                        }
    
                        // if($product($data['Type']) === "grouped" && !empty($product[$data['Grouped products']])){
                        //     $grouped_product_ids = explode(',', $product[$data['Grouped products']]);
                        //     $products->set_children('children', $grouped_product_ids);
                        //     $grouped_product_array = array();
                        //     foreach($grouped_product_ids as $id){
                        //         $grouped_product_id = wc_get_product_id_by_sku($id);
                        //         $grouped_product_array[] = $grouped_product_id;
                        //     }
                            
                        // }
                    
                        if(!empty($product[$data['Cross-sells']]) && $product[$data['Type']] !== "external"){
                            $cross_sell_ids = explode(',', $product[$data['Cross-sells']]);
                            $cross_sell_array = array();
                            foreach($cross_sell_ids as $id){
                                $cross_sell_id = wc_get_product_id_by_sku($id);
                                $cross_sell_array[] = $cross_sell_id;
                            }
                            $products->set_cross_sell_ids($cross_sell_array);
                        }
                    
                        //Allow customer reviews
                        if(!empty($product[$data['Allow_customer_reviews?']]) && $product[$data['Allow_customer_reviews?']] === "1"){
                            $products->set_reviews_allowed("1");
                        }
                        else{
                            $products->set_reviews_allowed("0");
                        }
                    
                        //Backorders allowed
                        if(!empty($product[$data['Backorders_allowed?']]) && $product[$data['Backorders_allowed?']] === "1"){
                            $products->set_backorders("1");
                        }
                        else if(!empty($product[$data['Backorders_allowed?']]) && $product[$data['Backorders_allowed?']] === "notify"){
                            $products->set_backorders("notify");
                        }
                        else if(!empty($product[$data['Backorders_allowed?']]) && $product[$data['Backorders_allowed?']] === "0"){
                            $products->set_backorders("0");
                        }
                    
                        if(!empty($product[$data['Sold_individually?']]) && $product[$data['Sold_individually?']] === "1"){
                            $products->set_sold_individually("1");
                        }
                        else{
                            $products->set_sold_individually("0");
                        }
                    
                        if(!empty($product[$data['Purchase_note']]) && $product[$data['Type']] !== "external"){
                            $products->set_purchase_note($product[$data['Purchase_note']]);
                        }
                    
                        if(!empty($product[$data['Shipping_class']])){
                            $products->set_shipping_class_id( $product[$data['Shipping_class']] );
                        }
                    
                        if(!empty($product[$data['Published']]) && $product[$data['Published']] === "1"){
                            $products->set_status('publish');
                        }
                        else{
                            $products->set_status('draft');
                        }
    
                        if(!empty($product[$data['Tax_status']])){
                            $products->set_tax_status($product[$data['Tax_status']]);
                        }
                        else{
                            $products->set_tax_status("");
                        }
    
                        if(!empty($product[$data['Tax_class']])){
                            $products->set_tax_class($product[$data['Tax_class']]);
                        }
                        else{
                            $products->set_tax_class("");
                        }
                    
                        if(!empty($product[$data['Type']]) && $product[$data['Type']] === "simple, virtual"){
                            $products->set_virtual( true );
                        }
                        else if(!empty($product[$data['Type']]) && $product[$data['Type']] === "simple, downloadable"){
                            $products->set_downloadable( true );
                            $downloads = array();
                            // Creating a download with... yes, WC_Product_Download class
                            if(!empty($product[$data['Download_1_URL']])){
                                $download = new WC_Product_Download();
                                // store the image ID in a var
                                $image_id = pippin_get_image_id($product[$data['Download_1_URL']]);
                                $file_url = wp_get_attachment_url( $image_id ); // attachmend ID should be here
                                $download->set_name( $product[$data['Download_1_name']] );
                                $download->set_id( md5( $file_url ) );
                                $download->set_file( $file_url );
                    
                                $downloads[] = $download;
                            }
                            else if(!empty($product[$data['Download_1_URL']]) && !empty($product[$data['Download_2_URL']])){
                                $download = new WC_Product_Download();
                                // store the image ID in a var
                                $image_id = pippin_get_image_id($product[$data['Download_1_URL']]);
                                $file_url = wp_get_attachment_url( $image_id ); // attachmend ID should be here
                                $download->set_name( $product[$data['Download_1_name']] );
                                $download->set_id( md5( $file_url ) );
                                $download->set_file( $file_url );
                    
                                $downloads[] = $download;
                    
                                $download = new WC_Product_Download();
                                // store the image ID in a var
                                $image_id = pippin_get_image_id($product[$data['Download_2_URL']]);
                                $file_url = wp_get_attachment_url( $image_id ); // attachmend ID should be here
                                $download->set_name( $product[$data['Download_2_name']] );
                                $download->set_id( md5( $file_url ) );
                                $download->set_file( $file_url );
                    
                                $downloads[] = $download;
                            }
                    
                            $products->set_downloads( $downloads );
                    
                            $products->set_download_limit( $product[$data['Download_limit']] ); // can be downloaded only once
                            $products->set_download_expiry( $product[$data['Download_expiry_days']] ); // expires in a week
                        }
                        else if(!empty($product[$data['Type']]) && $product[$data['Type']] === "simple, downloadable, virtual" || $product[$data['Type']] !== "simple, downloadable"){
                            $products->set_virtual( true );
                            $products->set_downloadable( true );
                            $downloads = array();
                            // Creating a download with... yes, WC_Product_Download class
                            if(!empty($product[$data['Download_1_URL']])){
                                $download = new WC_Product_Download();
                                // store the image ID in a var
                                $image_id = pippin_get_image_id($product[$data['Download_1_URL']]);
                                $file_url = wp_get_attachment_url( $image_id ); // attachmend ID should be here
                                $download->set_name( $product[$data['Download_1_name']] );
                                $download->set_id( md5( $file_url ) );
                                $download->set_file( $file_url );
                    
                                $downloads[] = $download;
                            }
                            else if(!empty($product[$data['Download_1_URL']]) && !empty($product[$data['Download_2_URL']])){
                                $download = new WC_Product_Download();
                                // store the image ID in a var
                                $image_id = pippin_get_image_id($product[$data['Download_1_URL']]);
                                $file_url = wp_get_attachment_url( $image_id ); // attachmend ID should be here
                                $download->set_name( $product[$data['Download_1_name']] );
                                $download->set_id( md5( $file_url ) );
                                $download->set_file( $file_url );
                    
                                $downloads[] = $download;
                    
                                $download = new WC_Product_Download();
                                // store the image ID in a var
                                $image_id = pippin_get_image_id($product[$data['Download_2_URL']]);
                                $file_url = wp_get_attachment_url( $image_id ); // attachmend ID should be here
                                $download->set_name( $product[$data['Download_2_name']] );
                                $download->set_id( md5( $file_url ) );
                                $download->set_file( $file_url );
                    
                                $downloads[] = $download;
                            }
                    
                            $products->set_downloads( $downloads );
                    
                            $products->set_download_limit( $product[$data['Download_limit']] ); // can be downloaded only once
                            $products->set_download_expiry( $product[$data['Download_expiry_days']] ); // expires in a week
                        }
                        else {
                            if(!empty($product[$data['Weight_(kg)']])){
                                $products->set_weight($product[$data['Weight_(kg)']]);
                            }
                        
                            if(!empty($product[$data['Length_(cm)']])){
                                $products->set_length($product[$data['Length_(cm)']]);
                            }
                        
                            if(!empty($product[$data['Width_(cm)']])){
                                $products->set_width($product[$data['Width_(cm)']]);
                            }
                        
                            if(!empty($product[$data['Height_(cm)']])){
                                $products->set_height($product[$data['Height_(cm)']]);
                            }
                        }
                    
                    
                        if(!empty($product[$data['Attribute_1_name']]) && !empty($product[$data['Attribute_2_name']])){
                    
                            // that's going to be an array of attributes we add to a product programmatically
                            $attributes = array();
                    
                            // add the second attribute, it is predefined taxonomy-based attribute
                            $attribute = new WC_Product_Attribute();
                            $attribute->set_name( $product[$data['Attribute_1_name']] );
                            $attribute_1_vals = preg_split ("/\,/", $product[$data['Attribute_1_value(s)']]); 
                            $attribute->set_options( $attribute_1_vals );
                            
                            if(!empty($product[$data['Position']])){
                                $attribute->set_position( $product[$data['Position']] );
                            }

                            if(!empty($product[$data['Attribute_1_visible']]) && $product[$data['Attribute_1_visible']] == "1"){
                                $attribute->set_visible( true );
                            }
                            else{
                                $attribute->set_visible( false );
                            }

                            $attribute->set_variation( true );
                            $attributes[] = $attribute;
                    
                            $attribute = new WC_Product_Attribute();
                            $attribute->set_name( $product[$data['Attribute_2_name']] );
                            $attribute_2_vals = preg_split ("/\,/", $product[$data['Attribute_2_value(s)']]); 
                            $attribute->set_options( $attribute_2_vals );
                            
                            if(!empty($product[$data['Position']])){
                                $attribute->set_position( $product[$data['Position']] );
                            }

                            if(!empty($product[$data['Attribute_2_visible']]) && $product[$data['Attribute_2_visible']] == "1"){
                                $attribute->set_visible( true );
                            }
                            else{
                                $attribute->set_visible( false );
                            }

                            $attribute->set_variation( true );
                            $attributes[] = $attribute;
                    
                            $products->set_attributes( $attributes );

                            $attr_1_name = $product[$data['Attribute_1_name']];
                            $attr_1_default = $product[$data['Attribute_1_default']];
            
                            $attr_2_name = $product[$data['Attribute_2_name']];
                            $attr_2_default = $product[$data['Attribute_2_default']];
            
                            $default_attributes = array(
                                strtolower($attr_1_name) => $attr_1_default,
                                strtolower($attr_2_name) => $attr_2_default
                            );

                            $products->set_default_attributes( $default_attributes );
                        }
                        else if(!empty($product[$data['Attribute_1_name']])){
                            // that's going to be an array of attributes we add to a product programmatically
                            $attributes = array();
                    
                            // add the second attribute, it is predefined taxonomy-based attribute
                            $attribute = new WC_Product_Attribute();
                            $attribute->set_name( $product[$data['Attribute_1_name']] );
                            $attribute_1_vals = preg_split ("/\,/", $product[$data['Attribute_1_value(s)']]); 
                            $attribute->set_options( $attribute_1_vals );
                            
                            if(!empty($product[$data['Position']])){
                                $attribute->set_position( $product[$data['Position']] );
                            }
                            
                            if(!empty($product[$data['Attribute_1_visible']]) && $product[$data['Attribute_1_visible']] == "1"){
                                $attribute->set_visible( true );
                            }
                            else{
                                $attribute->set_visible( false );
                            }

                            $attribute->set_variation( true );
                            $attributes[] = $attribute;
                    
                            $products->set_attributes( $attributes );

                            $attr_1_name = $product[$data['Attribute_1_name']];
                            $attr_1_default = $product[$data['Attribute_1_default']];
            
                            $default_attributes = array(
                                strtolower($attr_1_name) => $attr_1_default
                            );

                            $products->set_default_attributes( $default_attributes );
                        }
                        else if(!empty($product[$data['Attribute_2_name']])){
                            // that's going to be an array of attributes we add to a product programmatically
                            $attributes = array();
                    
                            // add the second attribute, it is predefined taxonomy-based attribute
                            $attribute = new WC_Product_Attribute();
                            $attribute->set_name( $product[$data['Attribute_2_name']] );
                            $attribute_1_vals = preg_split ("/\,/", $product[$data['Attribute_2_value(s)']]); 
                            $attribute->set_options( $attribute_1_vals );

                            if(!empty($product[$data['Position']])){
                                $attribute->set_position( $product[$data['Position']] );
                            }

                            
                            if(!empty($product[$data['Attribute_2_visible']]) && $product[$data['Attribute_2_visible']] == "1"){
                                $attribute->set_visible( true );
                            }
                            else{
                                $attribute->set_visible( false );
                            }

                            $attribute->set_variation( true );
                            $attributes[] = $attribute;
                    
                            $products->set_attributes( $attributes );

                            $attr_2_name = $product[$data['Attribute_2_name']];
                            $attr_2_default = $product[$data['Attribute_2_default']];
            
                            $default_attributes = array(
                                strtolower($attr_2_name) => $attr_2_default
                            );

                            $products->set_default_attributes( $default_attributes );
                        }
    
                        if($product[$data['Type']] !== "simple, virtual" || $product[$data['Type']] !== "simple, downloadable, virtual"){
                            if(!empty($product[$data['Weight_(kg)']])){
                                $products->set_weight($product[$data['Weight_(kg)']]);
                            }
                        
                            if(!empty($product[$data['Length_(cm)']])){
                                $products->set_length($product[$data['Length_(cm)']]);
                            }
                        
                            if(!empty($product[$data['Width_(cm)']])){
                                $products->set_width($product[$data['Width_(cm)']]);
                            }
                        
                            if(!empty($product[$data['Height_(cm)']])){
                                $products->set_height($product[$data['Height_(cm)']]);
                            }
                        }
                    
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

                            if(!empty($product[$data['SKU']])){
                                $variation->set_sku( $product[$data['SKU']] );
                            }
            
                            $attr_1_name = $product[$data['Attribute_1_name']];
                            $attr_1_val = $product[$data['Attribute_1_value(s)']];
            
                            $attr_2_name = $product[$data['Attribute_2_name']];
                            $attr_2_val = $product[$data['Attribute_2_value(s)']];
            
                            $attributes = array(
                                strtolower($attr_1_name) => $attr_1_val,
                                strtolower($attr_2_name) => $attr_2_val
                            );
                                            
                            $variation->set_attributes( $attributes );

                            if(!empty($product[$data['Description']])){
                                $variation->set_description($product[$data['Description']]);
                            }
                            
                            if(!empty($product[$data['Regular_price']])){
                                $variation->set_price($product[$data['Regular_price']]);
                                $variation->set_regular_price($product[$data['Regular_price']]);
                            }
                            
                            if(!empty($product[$data['Sale_price']])){
                                $variation->set_sale_price($product[$data['Sale_price']]);
                                if(!empty($product[$data['Date_sale_price_starts']])){
                                    $variation->set_date_on_sale_from($product[$data['Date_sale_price_starts']]);
                                }
                                if(!empty($product[$data['Date_sale_price_ends']])){
                                    $variation->set_date_on_sale_to($product[$data['Date_sale_price_ends']]);
                                }
                            }
                            

                            if(!empty($product[$data['Stock']])){
                                $variation->set_stock_quantity($product[$data['Stock']]);
                                $variation->set_manage_stock(TRUE);
                                $variation->set_stock_status('instock');
                            }
                            else{
                                $variation->set_stock_quantity(0);
                                $variation->set_manage_stock(FALSE);
                                $variation->set_stock_status('outofstock');
                            }

                            if(!empty($product[$data['Shipping_class']])){
                                $variation->set_shipping_class_id( $product[$data['Shipping_class']] );
                            }
                            
                            if(!empty($product[$data['Published']]) && $product[$data['Published']] === "1"){
                                $variation->set_status('publish');
                            }
                            else{
                                $variation->set_status('draft');
                            }

                            if(!empty($product[$data['Images']])){
                                // $images = explode(',', $product[$data['Images']]);
                                $image_id = pippin_get_image_id($data['Images']);
                                $variation->set_image_id( $image_id );
                            }

                            //Backorders allowed
                            if(!empty($product[$data['Backorders_allowed?']]) && $product[$data['Backorders_allowed?']] === "1"){
                                $variation->set_backorders("1");
                            }
                            else if(!empty($product[$data['Backorders_allowed?']]) && $product[$data['Backorders_allowed?']] === "notify"){
                                $variation->set_backorders("notify");
                            }
                            else if(!empty($product[$data['Backorders_allowed?']]) && $product[$data['Backorders_allowed?']] === "0"){
                                $variation->set_backorders("0");
                            }

                            if($product[$data['Type']] !== "variation, virtual" ){
                                if(!empty($product[$data['Weight_(kg)']])){
                                    $variation->set_weight($product[$data['Weight_(kg)']]);
                                }
                            
                                //Dimensions
                                if(!empty($product[$data['Length_(cm)']])){
                                    $variation->set_length($product[$data['Length_(cm)']]);
                                }
                            
                                if(!empty($product[$data['Width_(cm)']])){
                                    $variation->set_width($product[$data['Width_(cm)']]);
                                }
                            
                                if(!empty($product[$data['Height_(cm)']])){
                                    $variation->set_height($product[$data['Height_(cm)']]);
                                }
                            }
                            else if($product[$data['Type']] === "variation, virtual"){
                                $variation->set_virtual( true );
                            }
                            else if($product[$data['Type']] === "variation, downloadable"){
                                $variation->set_downloadable( true );
                                $downloads = array();
                                // Creating a download with... yes, WC_Product_Download class
                                if(!empty($product[$data['Download_1_URL']])){
                                    $download = new WC_Product_Download();
                                    // store the image ID in a var
                                    $image_id = pippin_get_image_id($product[$data['Download_1_URL']]);
                                    $file_url = wp_get_attachment_url( $image_id ); // attachmend ID should be here
                                    $download->set_name( $product[$data['Download_1_name']] );
                                    $download->set_id( md5( $file_url ) );
                                    $download->set_file( $file_url );
                            
                                    $downloads[] = $download;
                                }
                                else if(!empty($product[$data['Download_1_URL']]) && !empty($product[$data['Download_2_URL']])){
                                    $download = new WC_Product_Download();
                                    // store the image ID in a var
                                    $image_id = pippin_get_image_id($product[$data['Download_1_URL']]);
                                    $file_url = wp_get_attachment_url( $image_id ); // attachmend ID should be here
                                    $download->set_name( $product[$data['Download_1_name']] );
                                    $download->set_id( md5( $file_url ) );
                                    $download->set_file( $file_url );
                            
                                    $downloads[] = $download;
                            
                                    $download = new WC_Product_Download();
                                    // store the image ID in a var
                                    $image_id = pippin_get_image_id($product[$data['Download_2_URL']]);
                                    $file_url = wp_get_attachment_url( $image_id ); // attachmend ID should be here
                                    $download->set_name( $product[$data['Download_2_name']] );
                                    $download->set_id( md5( $file_url ) );
                                    $download->set_file( $file_url );
                            
                                    $downloads[] = $download;
                                }
                            
                                $variation->set_downloads( $downloads );
                            
                                $variation->set_download_limit( $product[$data['Download_limit']] ); // can be downloaded only once
                                $variation->set_download_expiry( $product[$data['Download_expiry_days']] ); // expires in a week
                            }
                            else if($product[$data['Type']] === "variation, downloadable, virtual" || $product[$data['Type']] !== "variation, downloadable"){
                                $variation->set_virtual( true );
                                $variation->set_downloadable( true );
                                $downloads = array();
                                // Creating a download with... yes, WC_Product_Download class
                                if(!empty($product[$data['Download_1_URL']])){
                                    $download = new WC_Product_Download();
                                    // store the image ID in a var
                                    $image_id = pippin_get_image_id($product[$data['Download_1_URL']]);
                                    $file_url = wp_get_attachment_url( $image_id ); // attachmend ID should be here
                                    $download->set_name( $product[$data['Download_1_name']] );
                                    $download->set_id( md5( $file_url ) );
                                    $download->set_file( $file_url );
                            
                                    $downloads[] = $download;
                                }
                                else if(!empty($product[$data['Download_1_URL']]) && !empty($product[$data['Download_2_URL']])){
                                    $download = new WC_Product_Download();
                                    // store the image ID in a var
                                    $image_id = pippin_get_image_id($product[$data['Download_1_URL']]);
                                    $file_url = wp_get_attachment_url( $image_id ); // attachmend ID should be here
                                    $download->set_name( $product[$data['Download_1_name']] );
                                    $download->set_id( md5( $file_url ) );
                                    $download->set_file( $file_url );
                            
                                    $downloads[] = $download;
                            
                                    $download = new WC_Product_Download();
                                    // store the image ID in a var
                                    $image_id = pippin_get_image_id($product[$data['Download_2_URL']]);
                                    $file_url = wp_get_attachment_url( $image_id ); // attachmend ID should be here
                                    $download->set_name( $product[$data['Download_2_name']] );
                                    $download->set_id( md5( $file_url ) );
                                    $download->set_file( $file_url );
                            
                                    $downloads[] = $download;
                                }
                            
                                $variation->set_downloads( $downloads );
                            
                                $variation->set_download_limit( $product[$data['Download_limit']] ); // can be downloaded only once
                                $variation->set_download_expiry( $product[$data['Download_expiry_days']] ); // expires in a week
                            }
                            $variation_id = $variation->save();
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
                    echo "<h1>Error in adding product: ".$product[$data["Name"]].".</h1>";
                }
            }
                
            //product found
            else{
                try {
                    $update_product = wc_get_product($product_id);
                    // $variation = $update_product->get_variation_id();
                    // $update_parent_product = wc_get_product($variation);
                    if ( $update_product instanceof WC_Product) {

                        if($product[$data['Type']] !== "variation" && $product[$data['Type']] !== "variation, downloadable, virtual" && $product[$data['Type']] !== "variation, downloadable" && $product[$data['Type']] !== "variation, virtual"){
                            
                            if(!empty($product[$data['Name']])){
                                $update_product->set_name( $product[$data['Name']] );
                            }
                            
                            if(!empty($product[$data['SKU']])){
                                $update_product->set_sku($product[$data['SKU']]);
                                $update_product->set_slug($product[$data['SKU']]);
                            }
                            
                            if(!empty($product[$data['Images']])){
                            
                                $images = explode(',', $product[$data['Images']]);
                                $image_id = pippin_get_image_id($images[0]);
                                $update_product->set_image_id( $image_id );
                            
                                $images_array = array();
                                foreach(array_slice($images, 1) as $img){
                                    $image_id = pippin_get_image_id($img);
                                    $images_array[] = $image_id;
                                }
                                $update_product->set_gallery_image_ids($images_array);
                            }
                            
                            if(!empty($product[$data['Is_featured?']]) && $product[$data['Is_featured?']] === "1"){
                                $update_product->set_featured(True);
                            }
                            else{
                                $update_product->set_featured(False);
                            }
                            
                            if(!empty($product[$data['Visibility_in_catalog']])){
                                $update_product->set_catalog_visibility($product[$data['Visibility_in_catalog']]);
                            }
                            
                            if(!empty($product[$data['Description']])){
                                $update_product->set_description($product[$data['Description']]);
                            }
                            
                            if(!empty($product[$data['Short_description']])){
                                $update_product->set_short_description($product[$data['Short_description']]);
                            }
                            
                            if(!empty($product[$data['Regular_price']])){
                                $update_product->set_price($product[$data['Regular_price']]);
                                $update_product->set_regular_price($product[$data['Regular_price']]);
                            }
                            
                            if(!empty($product[$data['Sale_price']])){
                                $update_product->set_sale_price($product[$data['Sale_price']]);
                                if(!empty($product[$data['Date_sale_price_starts']])){
                                    $update_product->set_date_on_sale_from($product[$data['Date_sale_price_starts']]);
                                }
                                if(!empty($product[$data['Date_sale_price_ends']])){
                                    $update_product->set_date_on_sale_to($product[$data['Date_sale_price_ends']]);
                                }
                            }
                            
                            if(!empty($product[$data['Stock']]) && $product[$data['Type']] != "external"){
                                $update_product->set_stock_quantity($product[$data['Stock']]);
                                $update_product->set_manage_stock(TRUE);
                                $update_product->set_stock_status('instock');
                            }
                            else if(empty($product[$data['Stock']]) || $product[$data['Type']] === "external"){
                                $update_product->set_stock_quantity(0);
                                $update_product->set_manage_stock(FALSE);
                                $update_product->set_stock_status('outofstock');
                            
                                if(!empty($product[$data['Button_text']])){
                                    $update_product->set_button_text($product[$data['Button_text']]);
                                }
                            
                                if(!empty($product[$data['External_URL']])){
                                    $update_product->set_product_url( $product[$data['External_URL']] );
                                }
                            }
                            
                            
                            //Set Categories
                            if(!empty($product[$data['Categories']])){
                            
                                if( strpos($product[$data['Categories']], "|") !== false ) {
                                    $parent_categories = explode("|", $product[$data['Categories']]);
                                }
                                else if( strpos($product[$data['Categories']], ",") !== false ){
                                    $parent_categories = explode(",", $product[$data['Categories']]);
                                }
                                else{
                                    $parent_categories = explode(",", $product[$data['Categories']]);
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
                                $update_product->set_category_ids( $new_categories );
                            }
                            
                            //Set Tags
                            if(!empty($product[$data['Tags']])){
                                $tags_array = explode(', ', $product[$data['Tags']]);
                                $tags = array();
                                foreach($tags_array as $tag){
                                    $tag_ID = get_tag_id($tag);
                                    $tags[] = $tag_ID;
                                }
                                $update_product->set_tag_ids( $tags );
                            }                    
                            
                            if(!empty($product[$data['Upsells']])){
                                $upsell_ids = explode(',', $product[$data['Upsells']]);
                                $upsell_array = array();
                                foreach($upsell_ids as $id){
                                    $upsell_id = wc_get_product_id_by_sku($id);
                                    $upsell_array[] = $upsell_id;
                                }
                                $update_product->set_upsell_ids($upsell_array);
                            }
                            
                            // if($product($data['Type']) === "grouped" && !empty($product[$data['Grouped products']])){
                            //     $grouped_product_ids = explode(',', $product[$data['Grouped products']]);
                            //     $update_product->set_children('children', $grouped_product_ids);
                            //     $grouped_product_array = array();
                            //     foreach($grouped_product_ids as $id){
                            //         $grouped_product_id = wc_get_product_id_by_sku($id);
                            //         $grouped_product_array[] = $grouped_product_id;
                            //     }
                                
                            // }
                            
                            if(!empty($product[$data['Cross-sells']]) && $product[$data['Type']] !== "external"){
                                $cross_sell_ids = explode(',', $product[$data['Cross-sells']]);
                                $cross_sell_array = array();
                                foreach($cross_sell_ids as $id){
                                    $cross_sell_id = wc_get_product_id_by_sku($id);
                                    $cross_sell_array[] = $cross_sell_id;
                                }
                                $update_product->set_cross_sell_ids($cross_sell_array);
                            }
                            
                            //Allow customer reviews
                            if(!empty($product[$data['Allow_customer_reviews?']]) && $product[$data['Allow_customer_reviews?']] === "1"){
                                $update_product->set_reviews_allowed("1");
                            }
                            else{
                                $update_product->set_reviews_allowed("0");
                            }
                            
                            //Backorders allowed
                            if(!empty($product[$data['Backorders_allowed?']]) && $product[$data['Backorders_allowed?']] === "1"){
                                $update_product->set_backorders("1");
                            }
                            else if(!empty($product[$data['Backorders_allowed?']]) && $product[$data['Backorders_allowed?']] === "notify"){
                                $update_product->set_backorders("notify");
                            }
                            else if(!empty($product[$data['Backorders_allowed?']]) && $product[$data['Backorders_allowed?']] === "0"){
                                $update_product->set_backorders("0");
                            }
                            
                            if(!empty($product[$data['Sold_individually?']]) && $product[$data['Sold_individually?']] === "1"){
                                $update_product->set_sold_individually("1");
                            }
                            else{
                                $update_product->set_sold_individually("0");
                            }
                            
                            if(!empty($product[$data['Purchase_note']]) && $product[$data['Type']] !== "external"){
                                $update_product->set_purchase_note($product[$data['Purchase_note']]);
                            }
                            
                            if(!empty($product[$data['Shipping_class']])){
                                $update_product->set_shipping_class_id( $product[$data['Shipping_class']] );
                            }
                            
                            if(!empty($product[$data['Published']]) && $product[$data['Published']] === "1"){
                                $update_product->set_status('publish');
                            }
                            else{
                                $update_product->set_status('draft');
                            }
                            
                            if(!empty($product[$data['Tax_status']])){
                                $update_product->set_tax_status($product[$data['Tax_status']]);
                            }
                            else{
                                $update_product->set_tax_status("");
                            }
                            
                            if(!empty($product[$data['Tax_class']])){
                                $update_product->set_tax_class($product[$data['Tax_class']]);
                            }
                            else{
                                $update_product->set_tax_class("");
                            }
                            
                            if(!empty($product[$data['Type']]) && $product[$data['Type']] === "simple, virtual"){
                                $update_product->set_virtual( true );
                            }
                            else if(!empty($product[$data['Type']]) && $product[$data['Type']] === "simple, downloadable"){
                                $update_product->set_downloadable( true );
                                $downloads = array();
                                // Creating a download with... yes, WC_Product_Download class
                                if(!empty($product[$data['Download_1_URL']])){
                                    $download = new WC_Product_Download();
                                    // store the image ID in a var
                                    $image_id = pippin_get_image_id($product[$data['Download_1_URL']]);
                                    $file_url = wp_get_attachment_url( $image_id ); // attachmend ID should be here
                                    $download->set_name( $product[$data['Download_1_name']] );
                                    $download->set_id( md5( $file_url ) );
                                    $download->set_file( $file_url );
                            
                                    $downloads[] = $download;
                                }
                                else if(!empty($product[$data['Download_1_URL']]) && !empty($product[$data['Download_2_URL']])){
                                    $download = new WC_Product_Download();
                                    // store the image ID in a var
                                    $image_id = pippin_get_image_id($product[$data['Download_1_URL']]);
                                    $file_url = wp_get_attachment_url( $image_id ); // attachmend ID should be here
                                    $download->set_name( $product[$data['Download_1_name']] );
                                    $download->set_id( md5( $file_url ) );
                                    $download->set_file( $file_url );
                            
                                    $downloads[] = $download;
                            
                                    $download = new WC_Product_Download();
                                    // store the image ID in a var
                                    $image_id = pippin_get_image_id($product[$data['Download_2_URL']]);
                                    $file_url = wp_get_attachment_url( $image_id ); // attachmend ID should be here
                                    $download->set_name( $product[$data['Download_2_name']] );
                                    $download->set_id( md5( $file_url ) );
                                    $download->set_file( $file_url );
                            
                                    $downloads[] = $download;
                                }
                            
                                $update_product->set_downloads( $downloads );
                            
                                $update_product->set_download_limit( $product[$data['Download_limit']] ); // can be downloaded only once
                                $update_product->set_download_expiry( $product[$data['Download_expiry_days']] ); // expires in a week
                            }
                            else if(!empty($product[$data['Type']]) && $product[$data['Type']] === "simple, downloadable, virtual" || $product[$data['Type']] !== "simple, downloadable"){
                                $update_product->set_virtual( true );
                                $update_product->set_downloadable( true );
                                $downloads = array();
                                // Creating a download with... yes, WC_Product_Download class
                                if(!empty($product[$data['Download_1_URL']])){
                                    $download = new WC_Product_Download();
                                    // store the image ID in a var
                                    $image_id = pippin_get_image_id($product[$data['Download_1_URL']]);
                                    $file_url = wp_get_attachment_url( $image_id ); // attachmend ID should be here
                                    $download->set_name( $product[$data['Download_1_name']] );
                                    $download->set_id( md5( $file_url ) );
                                    $download->set_file( $file_url );
                            
                                    $downloads[] = $download;
                                }
                                else if(!empty($product[$data['Download_1_URL']]) && !empty($product[$data['Download_2_URL']])){
                                    $download = new WC_Product_Download();
                                    // store the image ID in a var
                                    $image_id = pippin_get_image_id($product[$data['Download_1_URL']]);
                                    $file_url = wp_get_attachment_url( $image_id ); // attachmend ID should be here
                                    $download->set_name( $product[$data['Download_1_name']] );
                                    $download->set_id( md5( $file_url ) );
                                    $download->set_file( $file_url );
                            
                                    $downloads[] = $download;
                            
                                    $download = new WC_Product_Download();
                                    // store the image ID in a var
                                    $image_id = pippin_get_image_id($product[$data['Download_2_URL']]);
                                    $file_url = wp_get_attachment_url( $image_id ); // attachmend ID should be here
                                    $download->set_name( $product[$data['Download_2_name']] );
                                    $download->set_id( md5( $file_url ) );
                                    $download->set_file( $file_url );
                            
                                    $downloads[] = $download;
                                }
                            
                                $update_product->set_downloads( $downloads );
                            
                                $update_product->set_download_limit( $product[$data['Download_limit']] ); // can be downloaded only once
                                $update_product->set_download_expiry( $product[$data['Download_expiry_days']] ); // expires in a week
                            }
                            else {
                                if(!empty($product[$data['Weight_(kg)']])){
                                    $update_product->set_weight($product[$data['Weight_(kg)']]);
                                }
                            
                                if(!empty($product[$data['Length_(cm)']])){
                                    $update_product->set_length($product[$data['Length_(cm)']]);
                                }
                            
                                if(!empty($product[$data['Width_(cm)']])){
                                    $update_product->set_width($product[$data['Width_(cm)']]);
                                }
                            
                                if(!empty($product[$data['Height_(cm)']])){
                                    $update_product->set_height($product[$data['Height_(cm)']]);
                                }
                            }
                            
                            
                            if(!empty($product[$data['Attribute_1_name']]) && !empty($product[$data['Attribute_2_name']])){
                            
                                // that's going to be an array of attributes we add to a product programmatically
                                $attributes = array();
                            
                                // add the second attribute, it is predefined taxonomy-based attribute
                                $attribute = new WC_Product_Attribute();
                                $attribute->set_name( $product[$data['Attribute_1_name']] );
                                $attribute_1_vals = preg_split ("/\,/", $product[$data['Attribute_1_value(s)']]); 
                                $attribute->set_options( $attribute_1_vals );
                                
                                if(!empty($product[$data['Position']])){
                                    $attribute->set_position( $product[$data['Position']] );
                                }

                                if(!empty($product[$data['Attribute_1_visible']]) && $product[$data['Attribute_1_visible']] == "1"){
                                    $attribute->set_visible( true );
                                }
                                else{
                                    $attribute->set_visible( false );
                                }

                                $attribute->set_variation( true );
                                $attributes[] = $attribute;
                            
                                $attribute = new WC_Product_Attribute();
                                $attribute->set_name( $product[$data['Attribute_2_name']] );
                                $attribute_2_vals = preg_split ("/\,/", $product[$data['Attribute_2_value(s)']]); 
                                $attribute->set_options( $attribute_2_vals );
                                
                                if(!empty($product[$data['Position']])){
                                    $attribute->set_position( $product[$data['Position']] );
                                }

                                if(!empty($product[$data['Attribute_2_visible']]) && $product[$data['Attribute_2_visible']] == "1"){
                                    $attribute->set_visible( true );
                                }
                                else{
                                    $attribute->set_visible( false );
                                }

                                $attribute->set_variation( true );
                                $attributes[] = $attribute;
                            
                                $update_product->set_attributes( $attributes );

                                $attr_1_name = $product[$data['Attribute_1_name']];
                                $attr_1_default = $product[$data['Attribute_1_default']];
                
                                $attr_2_name = $product[$data['Attribute_2_name']];
                                $attr_2_default = $product[$data['Attribute_2_default']];
                
                                $default_attributes = array(
                                    strtolower($attr_1_name) => $attr_1_default,
                                    strtolower($attr_2_name) => $attr_2_default
                                );

                                $update_product->set_default_attributes( $default_attributes );
                            }
                            else if(!empty($product[$data['Attribute_1_name']])){
                                // that's going to be an array of attributes we add to a product programmatically
                                $attributes = array();
                            
                                // add the second attribute, it is predefined taxonomy-based attribute
                                $attribute = new WC_Product_Attribute();
                                $attribute->set_name( $product[$data['Attribute_1_name']] );
                                $attribute_1_vals = preg_split ("/\,/", $product[$data['Attribute_1_value(s)']]); 
                                $attribute->set_options( $attribute_1_vals );
                                
                                if(!empty($product[$data['Position']])){
                                    $attribute->set_position( $product[$data['Position']] );
                                }

                                if(!empty($product[$data['Attribute_1_visible']]) && $product[$data['Attribute_1_visible']] == "1"){
                                    $attribute->set_visible( true );
                                }
                                else{
                                    $attribute->set_visible( false );
                                }

                                $attribute->set_variation( true );
                                $attributes[] = $attribute;
                            
                                $update_product->set_attributes( $attributes );

                                $attr_1_name = $product[$data['Attribute_1_name']];
                                $attr_1_default = $product[$data['Attribute_1_default']];
                
                                $default_attributes = array(
                                    strtolower($attr_1_name) => $attr_1_default
                                );
    
                                $update_product->set_default_attributes( $default_attributes );
                            }
                            else if(!empty($product[$data['Attribute_2_name']])){
                                // that's going to be an array of attributes we add to a product programmatically
                                $attributes = array();
                            
                                // add the second attribute, it is predefined taxonomy-based attribute
                                $attribute = new WC_Product_Attribute();
                                $attribute->set_name( $product[$data['Attribute_2_name']] );
                                $attribute_1_vals = preg_split ("/\,/", $product[$data['Attribute_2_value(s)']]); 
                                $attribute->set_options( $attribute_1_vals );
                                
                                if(!empty($product[$data['Position']])){
                                    $attribute->set_position( $product[$data['Position']] );
                                }

                                if(!empty($product[$data['Attribute_2_visible']]) && $product[$data['Attribute_2_visible']] == "1"){
                                    $attribute->set_visible( true );
                                }
                                else{
                                    $attribute->set_visible( false );
                                }
                                
                                $attribute->set_variation( true );
                                $attributes[] = $attribute;
                            
                                $update_product->set_attributes( $attributes );

                                $attr_2_name = $product[$data['Attribute_2_name']];
                                $attr_2_default = $product[$data['Attribute_2_default']];
                
                                $default_attributes = array(
                                    strtolower($attr_2_name) => $attr_2_default
                                );
    
                                $products->set_default_attributes( $default_attributes );
                            }
                            
                            if($product[$data['Type']] !== "simple, virtual" || $product[$data['Type']] !== "simple, downloadable, virtual"){
                                if(!empty($product[$data['Weight_(kg)']])){
                                    $update_product->set_weight($product[$data['Weight_(kg)']]);
                                }
                            
                                if(!empty($product[$data['Length_(cm)']])){
                                    $update_product->set_length($product[$data['Length_(cm)']]);
                                }
                            
                                if(!empty($product[$data['Width_(cm)']])){
                                    $update_product->set_width($product[$data['Width_(cm)']]);
                                }
                            
                                if(!empty($product[$data['Height_(cm)']])){
                                    $update_product->set_height($product[$data['Height_(cm)']]);
                                }
                            }
                            
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

                            if(!empty($product[$data['SKU']])){
                                $variation->set_sku( $product[$data['SKU']] );
                            }
            
                            $attr_1_name = $product[$data['Attribute_1_name']];
                            $attr_1_val = $product[$data['Attribute_1_value(s)']];
            
                            $attr_2_name = $product[$data['Attribute_2_name']];
                            $attr_2_val = $product[$data['Attribute_2_value(s)']];
            
                            $attributes = array(
                                strtolower($attr_1_name) => $attr_1_val,
                                strtolower($attr_2_name) => $attr_2_val
                            );
                                            
                            $variation->set_attributes( $attributes );

                            if(!empty($product[$data['Description']])){
                                $variation->set_description($product[$data['Description']]);
                            }
                            
                            if(!empty($product[$data['Regular_price']])){
                                $variation->set_price($product[$data['Regular_price']]);
                                $variation->set_regular_price($product[$data['Regular_price']]);
                            }
                            
                            if(!empty($product[$data['Sale_price']])){
                                $variation->set_sale_price($product[$data['Sale_price']]);
                                if(!empty($product[$data['Date_sale_price_starts']])){
                                    $variation->set_date_on_sale_from($product[$data['Date_sale_price_starts']]);
                                }
                                if(!empty($product[$data['Date_sale_price_ends']])){
                                    $variation->set_date_on_sale_to($product[$data['Date_sale_price_ends']]);
                                }
                            }
                            

                            if(!empty($product[$data['Stock']])){
                                $variation->set_stock_quantity($product[$data['Stock']]);
                                $variation->set_manage_stock(TRUE);
                                $variation->set_stock_status('instock');
                            }
                            else{
                                $variation->set_stock_quantity(0);
                                $variation->set_manage_stock(FALSE);
                                $variation->set_stock_status('outofstock');
                            }

                            if(!empty($product[$data['Shipping_class']])){
                                $variation->set_shipping_class_id( $product[$data['Shipping_class']] );
                            }
                            
                            if(!empty($product[$data['Published']]) && $product[$data['Published']] === "1"){
                                $variation->set_status('publish');
                            }
                            else{
                                $variation->set_status('draft');
                            }

                            if(!empty($product[$data['Images']])){
                                // $images = explode(',', $product[$data['Images']]);
                                $image_id = pippin_get_image_id($data['Images']);
                                $variation->set_image_id( $image_id );
                            }

                            //Backorders allowed
                            if(!empty($product[$data['Backorders_allowed?']]) && $product[$data['Backorders_allowed?']] === "1"){
                                $variation->set_backorders("1");
                            }
                            else if(!empty($product[$data['Backorders_allowed?']]) && $product[$data['Backorders_allowed?']] === "notify"){
                                $variation->set_backorders("notify");
                            }
                            else if(!empty($product[$data['Backorders_allowed?']]) && $product[$data['Backorders_allowed?']] === "0"){
                                $variation->set_backorders("0");
                            }

                            if($product[$data['Type']] !== "variation, virtual" ){
                                if(!empty($product[$data['Weight_(kg)']])){
                                    $variation->set_weight($product[$data['Weight_(kg)']]);
                                }
                            
                                //Dimensions
                                if(!empty($product[$data['Length_(cm)']])){
                                    $variation->set_length($product[$data['Length_(cm)']]);
                                }
                            
                                if(!empty($product[$data['Width_(cm)']])){
                                    $variation->set_width($product[$data['Width_(cm)']]);
                                }
                            
                                if(!empty($product[$data['Height_(cm)']])){
                                    $variation->set_height($product[$data['Height_(cm)']]);
                                }
                            }
                            else if($product[$data['Type']] === "variation, virtual"){
                                $variation->set_virtual( true );
                            }
                            else if($product[$data['Type']] === "variation, downloadable"){
                                $variation->set_downloadable( true );
                                $downloads = array();
                                // Creating a download with... yes, WC_Product_Download class
                                if(!empty($product[$data['Download_1_URL']])){
                                    $download = new WC_Product_Download();
                                    // store the image ID in a var
                                    $image_id = pippin_get_image_id($product[$data['Download_1_URL']]);
                                    $file_url = wp_get_attachment_url( $image_id ); // attachmend ID should be here
                                    $download->set_name( $product[$data['Download_1_name']] );
                                    $download->set_id( md5( $file_url ) );
                                    $download->set_file( $file_url );
                            
                                    $downloads[] = $download;
                                }
                                else if(!empty($product[$data['Download_1_URL']]) && !empty($product[$data['Download_2_URL']])){
                                    $download = new WC_Product_Download();
                                    // store the image ID in a var
                                    $image_id = pippin_get_image_id($product[$data['Download_1_URL']]);
                                    $file_url = wp_get_attachment_url( $image_id ); // attachmend ID should be here
                                    $download->set_name( $product[$data['Download_1_name']] );
                                    $download->set_id( md5( $file_url ) );
                                    $download->set_file( $file_url );
                            
                                    $downloads[] = $download;
                            
                                    $download = new WC_Product_Download();
                                    // store the image ID in a var
                                    $image_id = pippin_get_image_id($product[$data['Download_2_URL']]);
                                    $file_url = wp_get_attachment_url( $image_id ); // attachmend ID should be here
                                    $download->set_name( $product[$data['Download_2_name']] );
                                    $download->set_id( md5( $file_url ) );
                                    $download->set_file( $file_url );
                            
                                    $downloads[] = $download;
                                }
                            
                                $variation->set_downloads( $downloads );
                            
                                $variation->set_download_limit( $product[$data['Download_limit']] ); // can be downloaded only once
                                $variation->set_download_expiry( $product[$data['Download_expiry_days']] ); // expires in a week
                            }
                            else if($product[$data['Type']] === "variation, downloadable, virtual" || $product[$data['Type']] !== "variation, downloadable"){
                                $variation->set_virtual( true );
                                $variation->set_downloadable( true );
                                $downloads = array();
                                // Creating a download with... yes, WC_Product_Download class
                                if(!empty($product[$data['Download_1_URL']])){
                                    $download = new WC_Product_Download();
                                    // store the image ID in a var
                                    $image_id = pippin_get_image_id($product[$data['Download_1_URL']]);
                                    $file_url = wp_get_attachment_url( $image_id ); // attachmend ID should be here
                                    $download->set_name( $product[$data['Download_1_name']] );
                                    $download->set_id( md5( $file_url ) );
                                    $download->set_file( $file_url );
                            
                                    $downloads[] = $download;
                                }
                                else if(!empty($product[$data['Download_1_URL']]) && !empty($product[$data['Download_2_URL']])){
                                    $download = new WC_Product_Download();
                                    // store the image ID in a var
                                    $image_id = pippin_get_image_id($product[$data['Download_1_URL']]);
                                    $file_url = wp_get_attachment_url( $image_id ); // attachmend ID should be here
                                    $download->set_name( $product[$data['Download_1_name']] );
                                    $download->set_id( md5( $file_url ) );
                                    $download->set_file( $file_url );
                            
                                    $downloads[] = $download;
                            
                                    $download = new WC_Product_Download();
                                    // store the image ID in a var
                                    $image_id = pippin_get_image_id($product[$data['Download_2_URL']]);
                                    $file_url = wp_get_attachment_url( $image_id ); // attachmend ID should be here
                                    $download->set_name( $product[$data['Download_2_name']] );
                                    $download->set_id( md5( $file_url ) );
                                    $download->set_file( $file_url );
                            
                                    $downloads[] = $download;
                                }
                            
                                $variation->set_downloads( $downloads );
                            
                                $variation->set_download_limit( $product[$data['Download_limit']] ); // can be downloaded only once
                                $variation->set_download_expiry( $product[$data['Download_expiry_days']] ); // expires in a week
                            }
                            $variation_id = $variation->save();
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