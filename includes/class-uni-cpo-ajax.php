<?php

/*
*   Uni_Cpo_Ajax Class
*
*/

if ( !defined( 'ABSPATH' ) ) {
    exit;
    // Exit if accessed directly
}

class Uni_Cpo_Ajax
{
    /**
     * Hook in ajax handlers.
     */
    public static function init()
    {
        add_action( 'init', array( __CLASS__, 'define_ajax' ), 0 );
        add_action( 'template_redirect', array( __CLASS__, 'do_cpo_ajax' ), 0 );
        self::add_ajax_events();
    }
    
    /**
     * Get Ajax Endpoint.
     */
    public static function get_endpoint( $request = '' )
    {
        return esc_url_raw( add_query_arg( 'cpo-ajax', $request ) );
    }
    
    /**
     * Set CPO AJAX constant and headers.
     */
    public static function define_ajax()
    {
        
        if ( !empty($_GET['cpo-ajax']) ) {
            if ( !defined( 'DOING_AJAX' ) ) {
                define( 'DOING_AJAX', true );
            }
            if ( !defined( 'CPO_DOING_AJAX' ) ) {
                define( 'CPO_DOING_AJAX', true );
            }
            
            if ( !WP_DEBUG || WP_DEBUG && !WP_DEBUG_DISPLAY ) {
                @ini_set( 'display_errors', 0 );
                // Turn off display_errors during AJAX events to prevent malformed JSON
            }
            
            $GLOBALS['wpdb']->hide_errors();
        }
    
    }
    
    /**
     * Send headers for CPO Ajax Requests
     */
    private static function cpo_ajax_headers()
    {
        send_origin_headers();
        @header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
        @header( 'X-Robots-Tag: noindex' );
        send_nosniff_header();
        nocache_headers();
        status_header( 200 );
    }
    
    /**
     * Check for CPO Ajax request and fire action.
     */
    public static function do_cpo_ajax()
    {
        global  $wp_query ;
        if ( !empty($_GET['cpo-ajax']) ) {
            $wp_query->set( 'cpo-ajax', sanitize_text_field( $_GET['cpo-ajax'] ) );
        }
        
        if ( $action = $wp_query->get( 'cpo-ajax' ) ) {
            self::cpo_ajax_headers();
            do_action( 'cpo_ajax_' . sanitize_text_field( $action ) );
            die;
        }
    
    }
    
    /**
     *   Hook in methods
     */
    public static function add_ajax_events()
    {
        $ajax_events = array(
            'uni_cpo_save_content'               => false,
            'uni_cpo_delete_content'             => false,
            'uni_cpo_save_model'                 => false,
            'uni_cpo_fetch_similar_modules'      => false,
            'uni_cpo_save_settings_data'         => false,
            'uni_cpo_fetch_similar_products'     => false,
            'uni_cpo_duplicate_product_settings' => false,
            'uni_cpo_save_discounts_data'        => false,
            'uni_cpo_save_formula_data'          => false,
            'uni_cpo_save_weight_data'           => false,
            'uni_cpo_save_dimensions_data'       => false,
            'uni_cpo_save_nov_data'              => false,
            'uni_cpo_import_matrix'              => false,
            'uni_cpo_sync_with_module'           => false,
            'uni_cpo_upload_file'                => true,
            'uni_cpo_remove_file'                => true,
            'uni_cpo_add_to_cart'                => true,
            'uni_cpo_price_calc'                 => true,
            'uni_cpo_cart_item_edit'             => true,
            'uni_cpo_cart_item_edit_inline'      => true,
            'uni_cpo_cart_item_update_inline'    => true,
            'uni_cpo_order_item_edit'            => false,
            'uni_cpo_order_item_update'          => false,
            'uni_cpo_product_settings_export'    => false,
            'uni_cpo_product_settings_import'    => false,
        );
        foreach ( $ajax_events as $ajax_event => $priv ) {
            add_action( 'wp_ajax_' . $ajax_event, array( __CLASS__, $ajax_event ) );
            if ( $priv ) {
                add_action( 'wp_ajax_nopriv_' . $ajax_event, array( __CLASS__, $ajax_event ) );
            }
        }
    }
    
    /**
     *   uni_cpo_save_content
     */
    public static function uni_cpo_save_content()
    {
        check_ajax_referer( 'uni_cpo_builder', 'security' );
        if ( !current_user_can( 'edit_products' ) ) {
            wp_die( -1 );
        }
        try {
            $data = json_decode( stripslashes_deep( $_POST['data'] ), true );
            $content = array();
            if ( is_array( $data ) && !empty($data) ) {
                array_walk( $data, function ( $row, $row_key ) use( &$content ) {
                    $content[$row_key] = $row;
                    if ( is_array( $row['columns'] ) && !empty($row['columns']) ) {
                        array_walk( $row['columns'], function ( $column, $column_key ) use( &$content, $row_key ) {
                            $content[$row_key]['columns'][$column_key] = $column;
                            if ( is_array( $column['modules'] ) && !empty($column['modules']) ) {
                                array_walk( $column['modules'], function ( $module, $module_key ) use( &$content, $row_key, $column_key ) {
                                    $content[$row_key]['columns'][$column_key]['modules'][$module_key] = $module;
                                    $module_settings = [];
                                    foreach ( $module['settings'] as $data_name => $data_data ) {
                                        $data_name = uni_cpo_clean( $data_name );
                                        $data_data = uni_cpo_get_settings_data_sanitized( $data_data, $data_name );
                                        $module_settings[$data_name] = $data_data;
                                    }
                                    $content[$row_key]['columns'][$column_key]['modules'][$module_key]['settings'] = $module_settings;
                                } );
                            }
                        } );
                    }
                } );
            }
            $result = Uni_Cpo_Product::save_content( absint( $_POST['product_id'] ), $content, false );
            
            if ( $result ) {
                wp_send_json_success();
            } else {
                wp_send_json_error();
            }
        
        } catch ( Exception $e ) {
            wp_send_json_error( array(
                'error' => $e->getMessage(),
            ) );
        }
    }
    
    /**
     *   uni_cpo_delete_content
     */
    public static function uni_cpo_delete_content()
    {
        check_ajax_referer( 'uni_cpo_builder', 'security' );
        if ( !current_user_can( 'edit_products' ) ) {
            wp_die( -1 );
        }
        try {
            $result = Uni_Cpo_Product::delete_content( absint( $_POST['product_id'] ) );
            
            if ( $result ) {
                wp_send_json_success();
            } else {
                wp_send_json_error();
            }
        
        } catch ( Exception $e ) {
            wp_send_json_error( array(
                'error' => $e->getMessage(),
            ) );
        }
    }
    
    /**
     *   uni_cpo_save_model
     */
    public static function uni_cpo_save_model()
    {
        check_ajax_referer( 'uni_cpo_builder', 'security' );
        if ( !current_user_can( 'edit_products' ) ) {
            wp_die( -1 );
        }
        try {
            $data = $_POST['model'];
            $post_id = ( !empty($data['pid']) ? absint( $data['pid'] ) : 0 );
            $model_obj_type = ( !empty($data['obj_type']) ? uni_cpo_clean( $data['obj_type'] ) : '' );
            $model_type = ( !empty($data['type']) ? uni_cpo_clean( $data['type'] ) : '' );
            if ( !$model_type ) {
                throw new Exception( __( 'Invalid model type', 'uni-cpo' ) );
            }
            if ( !$model_obj_type ) {
                throw new Exception( __( 'Invalid builder model object type', 'uni-cpo' ) );
            }
            
            if ( $post_id > 0 ) {
                $model = uni_cpo_get_model( $model_obj_type, $post_id );
                
                if ( is_object( $model ) && 'trash' === $model->get_status() ) {
                    wp_delete_post( $post_id, true );
                    $post_id = 0;
                    $model = uni_cpo_get_model( $model_obj_type, $post_id, $model_type );
                } elseif ( !is_object( $model ) ) {
                    $post_id = 0;
                    $model = uni_cpo_get_model( $model_obj_type, $post_id, $model_type );
                }
            
            } else {
                $model = uni_cpo_get_model( $model_obj_type, $post_id, $model_type );
            }
            
            if ( !$model ) {
                throw new Exception( __( 'Invalid model', 'uni-cpo' ) );
            }
            
            if ( 'option' === $model_obj_type ) {
                $cpo_general = $data['settings']['cpo_general'];
                $slug_being_saved = ( !empty($cpo_general['main']['cpo_slug']) ? uni_cpo_clean( $cpo_general['main']['cpo_slug'] ) : sanitize_title_with_dashes( uniqid( 'option_' ) ) );
                
                if ( empty($model->get_slug()) ) {
                    // slug is empty, it is a new option
                    $slug_check_result = uni_cpo_get_unique_slug( $slug_being_saved );
                } elseif ( !empty($model->get_slug()) ) {
                    
                    if ( UniCpo()->get_var_slug() . $slug_being_saved !== $model->get_slug() ) {
                        // looks like slug is going to be changed, so let's check its uniqueness
                        $slug_check_result = uni_cpo_get_unique_slug( $slug_being_saved );
                    } else {
                        $slug_check_result = array(
                            'unique' => true,
                            'slug'   => $model->get_slug(),
                        );
                    }
                
                }
                
                if ( !isset( $slug_check_result ) ) {
                    throw new Exception( __( 'Something went srong', 'uni-cpo' ) );
                }
                
                if ( $slug_check_result['unique'] && $slug_check_result['slug'] ) {
                    unset( $data['settings']['general']['status'] );
                    $data['settings']['cpo_general']['main']['cpo_slug'] = '';
                    $props = array(
                        'slug' => $slug_check_result['slug'],
                    );
                    foreach ( $data['settings'] as $data_name => $data_data ) {
                        $data_name = uni_cpo_clean( $data_name );
                        $data_data = uni_cpo_get_settings_data_sanitized( $data_data, $data_name );
                        $props[$data_name] = $data_data;
                    }
                    $model->set_props( $props );
                    $model->save();
                    $model_data = $model->formatted_model_data();
                    wp_send_json_success( $model_data );
                } elseif ( !$slug_check_result['unique'] && $slug_check_result['slug'] ) {
                    wp_send_json_error( array(
                        'error' => $slug_check_result,
                    ) );
                }
            
            } elseif ( 'module' === $model_obj_type ) {
                // TODO
            }
        
        } catch ( Exception $e ) {
            wp_send_json_error( array(
                'error' => $e->getMessage(),
            ) );
        }
    }
    
    /**
     *   uni_cpo_fetch_similar_modules
     */
    public static function uni_cpo_fetch_similar_modules()
    {
        check_ajax_referer( 'uni_cpo_builder', 'security' );
        if ( !current_user_can( 'edit_products' ) ) {
            wp_die( -1 );
        }
        try {
            $data = uni_cpo_clean( $_POST );
            if ( !isset( $data['type'] ) || !isset( $data['obj_type'] ) ) {
                throw new Exception( __( 'Type is not specified', 'uni-cpo' ) );
            }
            $result = uni_cpo_get_similar_modules( $data );
            
            if ( $result ) {
                wp_send_json_success( $result );
            } else {
                wp_send_json_error();
            }
        
        } catch ( Exception $e ) {
            wp_send_json_error( array(
                'error' => $e->getMessage(),
            ) );
        }
    }
    
    /**
     *   uni_cpo_save_settings_data
     */
    public static function uni_cpo_save_settings_data()
    {
        check_ajax_referer( 'uni_cpo_builder', 'security' );
        if ( !current_user_can( 'edit_products' ) ) {
            wp_die( -1 );
        }
        try {
            $model = $_POST['model'];
            $data['product_id'] = absint( $model['id'] );
            $data['settings_data'] = uni_cpo_clean( $model['settingsData'] );
            $result = Uni_Cpo_Product::save_product_data( $data, 'settings_data' );
            
            if ( !isset( $result['error'] ) ) {
                wp_send_json_success( $result );
            } else {
                wp_send_json_error( $result );
            }
        
        } catch ( Exception $e ) {
            wp_send_json_error( array(
                'error' => $e->getMessage(),
            ) );
        }
    }
    
    /**
     *   uni_cpo_fetch_similar_products
     */
    public static function uni_cpo_fetch_similar_products()
    {
        check_ajax_referer( 'uni_cpo_builder', 'security' );
        if ( !current_user_can( 'edit_products' ) ) {
            wp_die( -1 );
        }
    }
    
    /**
     *   uni_cpo_duplicate_product_settings
     */
    public static function uni_cpo_duplicate_product_settings()
    {
        check_ajax_referer( 'uni_cpo_builder', 'security' );
        if ( !current_user_can( 'edit_products' ) ) {
            wp_die( -1 );
        }
    }
    
    /**
     *   uni_cpo_save_discounts_data
     */
    public static function uni_cpo_save_discounts_data()
    {
        check_ajax_referer( 'uni_cpo_builder', 'security' );
        if ( !current_user_can( 'edit_products' ) ) {
            wp_die( -1 );
        }
    }
    
    /**
     *   uni_cpo_save_formula_data
     */
    public static function uni_cpo_save_formula_data()
    {
        check_ajax_referer( 'uni_cpo_builder', 'security' );
        if ( !current_user_can( 'edit_products' ) ) {
            wp_die( -1 );
        }
        try {
            $model = $_POST['model'];
            $data['product_id'] = absint( $model['id'] );
            $data['formula_data'] = uni_cpo_clean( $model['formulaData'] );
            if ( !isset( $data['formula_data']['formula_scheme'] ) ) {
                $data['formula_data']['formula_scheme'] = '';
            }
            $result = Uni_Cpo_Product::save_product_data( $data, 'formula_data' );
            
            if ( !isset( $result['error'] ) ) {
                wp_send_json_success( $result );
            } else {
                wp_send_json_error( $result );
            }
        
        } catch ( Exception $e ) {
            wp_send_json_error( array(
                'error' => $e->getMessage(),
            ) );
        }
    }
    
    /**
     *   uni_cpo_save_weight_data
     */
    public static function uni_cpo_save_weight_data()
    {
        check_ajax_referer( 'uni_cpo_builder', 'security' );
        if ( !current_user_can( 'edit_products' ) ) {
            wp_die( -1 );
        }
    }
    
    /**
     *   uni_cpo_save_dimensions_data
     */
    public static function uni_cpo_save_dimensions_data()
    {
        check_ajax_referer( 'uni_cpo_builder', 'security' );
        if ( !current_user_can( 'edit_products' ) ) {
            wp_die( -1 );
        }
    }
    
    /**
     *   uni_cpo_save_nov_data
     */
    public static function uni_cpo_save_nov_data()
    {
        check_ajax_referer( 'uni_cpo_builder', 'security' );
        if ( !current_user_can( 'edit_products' ) ) {
            wp_die( -1 );
        }
        try {
            $model = $_POST['model'];
            $data['product_id'] = absint( $model['id'] );
            $data['nov_data'] = uni_cpo_clean( $model['novData'] );
            if ( !isset( $data['nov_data']['nov'] ) ) {
                $data['nov_data']['nov'] = '';
            }
            $result = Uni_Cpo_Product::save_product_data( $data, 'nov_data' );
            
            if ( !isset( $result['error'] ) ) {
                wp_send_json_success( $result );
            } else {
                wp_send_json_error( $result );
            }
        
        } catch ( Exception $e ) {
            wp_send_json_error( array(
                'error' => $e->getMessage(),
            ) );
        }
    }
    
    /**
     *   uni_cpo_import_matrix
     */
    public static function uni_cpo_import_matrix()
    {
        check_ajax_referer( 'uni_cpo_builder', 'security' );
        if ( !current_user_can( 'edit_products' ) ) {
            wp_die( -1 );
        }
    }
    
    /**
     *   uni_cpo_sync_with_module
     */
    public static function uni_cpo_sync_with_module()
    {
        check_ajax_referer( 'uni_cpo_builder', 'security' );
        if ( !current_user_can( 'edit_products' ) ) {
            wp_die( -1 );
        }
        try {
            $data = uni_cpo_clean( $_POST );
            if ( !isset( $data['obj_type'] ) ) {
                throw new Exception( __( 'Type is not specified', 'uni-cpo' ) );
            }
            if ( !isset( $data['pid'] ) ) {
                throw new Exception( __( 'Target post is not chosen', 'uni-cpo' ) );
            }
            if ( !isset( $data['method'] ) ) {
                throw new Exception( __( 'Sync method is not chosen', 'uni-cpo' ) );
            }
            $result = uni_cpo_get_module_for_sync( $data );
            
            if ( $result ) {
                wp_send_json_success( $result );
            } else {
                wp_send_json_error();
            }
        
        } catch ( Exception $e ) {
            wp_send_json_error( array(
                'error' => $e->getMessage(),
            ) );
        }
    }
    
    /**
     *   uni_cpo_upload_file
     */
    public static function uni_cpo_upload_file()
    {
    }
    
    /**
     *   uni_cpo_remove_file
     */
    public static function uni_cpo_remove_file()
    {
    }
    
    /**
     * Get a refreshed cart fragment, including the mini cart HTML.
     */
    public static function get_refreshed_fragments()
    {
        ob_start();
        woocommerce_mini_cart();
        $mini_cart = ob_get_clean();
        $data = array(
            'fragments' => apply_filters( 'woocommerce_add_to_cart_fragments', array(
            'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>',
        ) ),
            'cart_hash' => apply_filters( 'woocommerce_add_to_cart_hash', ( WC()->cart->get_cart_for_session() ? md5( json_encode( WC()->cart->get_cart_for_session() ) ) : '' ), WC()->cart->get_cart_for_session() ),
        );
        wp_send_json_success( $data );
    }
    
    /**
     *   uni_cpo_add_to_cart
     */
    public static function uni_cpo_add_to_cart()
    {
    }
    
    /**
     *   uni_cpo_price_calc
     */
    public static function uni_cpo_price_calc()
    {
        try {
            $form_data = uni_cpo_clean( $_POST['data'] );
            if ( !isset( $form_data['product_id'] ) ) {
                throw new Exception( __( 'Product ID is not set', 'uni-cpo' ) );
            }
            $product_id = absint( $form_data['product_id'] );
            $product = wc_get_product( $product_id );
            $product_data = Uni_Cpo_Product::get_product_data_by_id( $product_id );
            $variables = array();
            $price_vars = array();
            $extra_data = array(
                'order_product' => 'enabled',
            );
            $is_calc_disabled = false;
            $formatted_vars = array();
            $nice_names_vars = array();
            $novs_nice = array();
            $price_vars['quantity'] = ( !empty($form_data['quantity']) ? absint( $form_data['quantity'] ) : 1 );
            
            if ( 'on' === $product_data['settings_data']['cpo_enable'] && 'on' === $product_data['settings_data']['calc_enable'] ) {
                $main_formula = $product_data['formula_data']['main_formula'];
                $filtered_form_data = array_filter( $form_data, function ( $k ) {
                    return false !== strpos( $k, UniCpo()->get_var_slug() );
                }, ARRAY_FILTER_USE_KEY );
                
                if ( !empty($filtered_form_data) ) {
                    $posts = uni_cpo_get_posts_by_slugs( array_keys( $filtered_form_data ) );
                    
                    if ( !empty($posts) ) {
                        $posts_ids = wp_list_pluck( $posts, 'ID' );
                        foreach ( $posts_ids as $post_id ) {
                            $option = uni_cpo_get_option( $post_id );
                            
                            if ( is_object( $option ) ) {
                                $calculate_result = $option->calculate( $filtered_form_data );
                                if ( !empty($calculate_result) ) {
                                    foreach ( $calculate_result as $k => $v ) {
                                        $variables['{' . $k . '}'] = $v['calc'];
                                    }
                                }
                            }
                        
                        }
                    }
                
                }
                
                $variables['{uni_cpo_price}'] = $product->get_price();
                $nice_names_vars['uni_cpo_price'] = $variables['{uni_cpo_price}'];
                // non option variables
                if ( 'on' === $product_data['nov_data']['nov_enable'] && !empty($product_data['nov_data']['nov']) ) {
                    $variables = uni_cpo_process_formula_with_non_option_vars( $variables, $product_data, $filtered_form_data );
                }
                $temp_variables = $variables;
                unset( $temp_variables['{uni_cpo_price}'] );
                array_walk( $temp_variables, function ( &$v, $k ) use( $filtered_form_data, &$formatted_vars ) {
                    $k = trim( $k, '{}' );
                    $formatted_vars[$k] = ( isset( $filtered_form_data[$k] ) ? $filtered_form_data[$k] : $v );
                } );
                // formula conditional logic
                
                if ( 'on' === $product_data['formula_data']['rules_enable'] && !empty($product_data['formula_data']['formula_scheme']) && is_array( $product_data['formula_data']['formula_scheme'] ) ) {
                    $conditional_formula = uni_cpo_process_formula_scheme( $formatted_vars, $product_data );
                    if ( $conditional_formula ) {
                        $main_formula = $conditional_formula;
                    }
                }
                
                if ( 'disable' === $main_formula ) {
                    $is_calc_disabled = true;
                }
                //
                
                if ( !$is_calc_disabled ) {
                    $main_formula = uni_cpo_process_formula_with_vars( $main_formula, $variables );
                    // calculates formula
                    $price_calculated = uni_cpo_calculate_formula( $main_formula );
                    $price_min = $product_data['settings_data']['min_price'];
                    $price_max = $product_data['settings_data']['max_price'];
                    // check for min price
                    if ( $price_calculated < $price_min ) {
                        $price_calculated = $price_min;
                    }
                    // check for max price
                    if ( !empty($price_max) && $price_calculated >= $price_max ) {
                        $is_calc_disabled = true;
                    }
                    
                    if ( !$is_calc_disabled ) {
                        // filter, so 3rd party scripts can hook up
                        $price_calculated = apply_filters(
                            'uni_cpo_ajax_calculated_price',
                            $price_calculated,
                            $product,
                            $filtered_form_data
                        );
                        $price_display = wc_get_price_to_display( $product, array(
                            'qty'   => 1,
                            'price' => $price_calculated,
                        ) );
                        
                        if ( $product->is_taxable() ) {
                            $price_display_tax_rev = uni_cpo_get_display_price_reversed( $product, $price_calculated );
                            // Returns the price with suffix inc/excl tax opposite to one above
                            $price_display_suffix = $product->get_price_suffix( $price_calculated, 1 );
                        }
                        
                        $price_vars['price'] = apply_filters( 'uni_cpo_ajax_calculation_price_tag_filter', uni_cpo_price( $price_display ), $price_display );
                        $price_vars['raw_price'] = $price_calculated;
                        $price_vars['raw_total'] = $price_vars['raw_price'] * $form_data['quantity'];
                        $price_vars['total'] = uni_cpo_price( $price_vars['raw_total'] );
                        
                        if ( $product->is_taxable() ) {
                            $price_vars['raw_price_tax_rev'] = $price_display_tax_rev;
                            $price_vars['raw_total_tax_rev'] = $price_vars['raw_price_tax_rev'] * $form_data['quantity'];
                            $price_vars['total_tax_rev'] = uni_cpo_price( $price_vars['raw_total_tax_rev'] );
                        }
                        
                        // price and total with suffixes
                        
                        if ( $product->is_taxable() ) {
                            // price with suffix - strips unnecessary
                            $price_display_suffix = str_replace( ' <small class="woocommerce-price-suffix">', '', $price_display_suffix );
                            $price_display_suffix = str_replace( ' </small>', '', $price_display_suffix );
                            // total with suffix
                            // creates 'with suffix' value for total
                            
                            if ( get_option( 'woocommerce_prices_include_tax' ) === 'no' && get_option( 'woocommerce_tax_display_shop' ) == 'incl' ) {
                                $total_suffix = $product->get_price_suffix( $price_vars['raw_price_tax_rev'] * $form_data['quantity'] );
                            } elseif ( get_option( 'woocommerce_prices_include_tax' ) === 'yes' && get_option( 'woocommerce_tax_display_shop' ) == 'incl' ) {
                                $total_suffix = $product->get_price_suffix( $price_vars['raw_price'] * $form_data['quantity'] );
                            } elseif ( get_option( 'woocommerce_prices_include_tax' ) === 'no' && get_option( 'woocommerce_tax_display_shop' ) == 'excl' ) {
                                $total_suffix = $product->get_price_suffix( $price_vars['raw_price'] * $form_data['quantity'] );
                            } elseif ( get_option( 'woocommerce_prices_include_tax' ) === 'yes' && get_option( 'woocommerce_tax_display_shop' ) == 'excl' ) {
                                $total_suffix = $product->get_price_suffix( $price_vars['raw_price_tax_rev'] * $form_data['quantity'] );
                            }
                            
                            $total_suffix = str_replace( ' <small class="woocommerce-price-suffix">', '', $total_suffix );
                            $total_suffix = str_replace( ' </small>', '', $total_suffix );
                            $total_suffix = str_replace( '<span class="amount">', '', $total_suffix );
                            $total_suffix = str_replace( '</span>', '', $total_suffix );
                            $price_vars['price_suffix'] = $price_display_suffix;
                            $price_vars['total_suffix'] = $total_suffix;
                        }
                    
                    } else {
                        
                        if ( $is_calc_disabled ) {
                            // ordering is disabled
                            $price_display = 0;
                            $price_vars['price'] = apply_filters( 'uni_cpo_ajax_calculation_price_tag_disabled_filter', uni_cpo_price( $price_display ), $price_display );
                            $extra_data = array(
                                'order_product' => 'disabled',
                            );
                        }
                    
                    }
                    
                    $result['formatted_vars'] = $formatted_vars;
                    $result['nice_names_vars'] = $nice_names_vars;
                    $result['price_vars'] = $price_vars;
                    $result['extra_data'] = $extra_data;
                    wp_send_json_success( $result );
                } else {
                    $price_display = 0;
                    $price_vars['price'] = apply_filters( 'uni_cpo_ajax_calculation_price_tag_disabled_filter', uni_cpo_price( $price_display ), $price_display );
                    $extra_data = array(
                        'order_product' => 'disabled',
                    );
                    $result['formatted_vars'] = $formatted_vars;
                    $result['nice_names_vars'] = $nice_names_vars;
                    $result['price_vars'] = $price_vars;
                    $result['extra_data'] = $extra_data;
                    wp_send_json_success( $result );
                }
            
            } else {
                throw new Exception( __( 'Price calculation is disabled in settings', 'uni-cpo' ) );
            }
        
        } catch ( Exception $e ) {
            wp_send_json_error( array(
                'error' => $e->getMessage(),
            ) );
        }
    }
    
    /**
     *   uni_cpo_cart_item_edit
     */
    public static function uni_cpo_cart_item_edit()
    {
    }
    
    /**
     *   uni_cpo_cart_item_edit_inline
     */
    public static function uni_cpo_cart_item_edit_inline()
    {
    }
    
    /**
     *   uni_cpo_cart_item_update_inline
     */
    public static function uni_cpo_cart_item_update_inline()
    {
    }
    
    /**
     *   uni_cpo_order_item_edit
     */
    public static function uni_cpo_order_item_edit()
    {
        check_ajax_referer( 'order-item', 'security' );
        if ( !current_user_can( 'edit_shop_orders' ) ) {
            wp_die( -1 );
        }
        try {
            $product_id = absint( $_POST['product_id'] );
            $order_item_id = absint( $_POST['order_item_id'] );
            $product_data = Uni_Cpo_Product::get_product_data_by_id( $product_id );
            $item = new WC_Order_Item_Product( $order_item_id );
            $form_data = uni_cpo_re_add_cpo_item_data( array(), $item->get_meta_data() );
            $form_field = '';
            $options_array = array();
            $filtered_form_data = array_filter( $form_data, function ( $k ) use( $form_data ) {
                return false !== strpos( $k, UniCpo()->get_var_slug() ) && !empty($form_data[$k]);
            }, ARRAY_FILTER_USE_KEY );
            array_walk( $product_data['content'], function ( $row, $row_key ) use( &$options_array ) {
                if ( is_array( $row['columns'] ) && !empty($row['columns']) ) {
                    array_walk( $row['columns'], function ( $column, $column_key ) use( &$options_array, $row_key ) {
                        if ( is_array( $column['modules'] ) && !empty($column['modules']) ) {
                            array_walk( $column['modules'], function ( $module ) use( &$options_array, $row_key, $column_key ) {
                                if ( isset( $module['pid'] ) && 'option' === $module['obj_type'] ) {
                                    $options_array[$module['pid']] = $module;
                                }
                            } );
                        }
                    } );
                }
            } );
            
            if ( !empty($options_array) ) {
                $posts = uni_cpo_get_posts_by_ids( array_keys( $options_array ) );
                
                if ( !empty($posts) ) {
                    $posts_ids = wp_list_pluck( $posts, 'ID' );
                    foreach ( $posts_ids as $post_id ) {
                        $option = uni_cpo_get_option( $post_id );
                        $post_name = trim( $option->get_slug(), '{}' );
                        
                        if ( is_object( $option ) && 'trash' !== $option->get_status() ) {
                            $field_value = ( isset( $filtered_form_data[$post_name] ) ? $filtered_form_data[$post_name] : '' );
                            $form_field .= $option->get_edit_field( $options_array[$option->get_id()], $field_value, 'order' );
                        }
                    
                    }
                }
                
                wp_send_json_success( $form_field );
            }
            
            wp_send_json_error( array(
                'error' => __( 'No options available', 'uni-cpo' ),
            ) );
        } catch ( Exception $e ) {
            wp_send_json_error( array(
                'error' => $e->getMessage(),
            ) );
        }
    }
    
    /**
     *   uni_cpo_order_item_update
     */
    public static function uni_cpo_order_item_update()
    {
        check_ajax_referer( 'order-item', 'security' );
        if ( !current_user_can( 'edit_shop_orders' ) ) {
            wp_die( -1 );
        }
        try {
            $form_data = wc_clean( $_POST );
            $product_id = $form_data['product_id'];
            $order = wc_get_order( $form_data['order_id'] );
            // is used in the template below
            $item_id = absint( $form_data['order_item_id'] );
            $item = new WC_Order_Item_Product( $item_id );
            unset( $form_data['product_id'] );
            unset( $form_data['order_item_id'] );
            unset( $form_data['security'] );
            unset( $form_data['order_id'] );
            unset( $form_data['action'] );
            unset( $form_data['dataType'] );
            $meta_data = $item->get_meta_data();
            $formatted_meta = array();
            if ( !empty($meta_data) ) {
                foreach ( $meta_data as $key => $meta_data_item ) {
                    $meta = $meta_data_item->get_data();
                    $formatted_meta[$meta['key']] = $meta['id'];
                }
            }
            
            if ( !empty($form_data) ) {
                foreach ( $form_data as $key => $value ) {
                    
                    if ( isset( $formatted_meta['_' . $key] ) ) {
                        $item->update_meta_data( '_' . $key, $value, $formatted_meta['_' . $key] );
                    } else {
                        $item->add_meta_data( '_' . $key, $value );
                    }
                
                }
                $cart_item_data['_cpo_data'] = $form_data;
                $item_price = uni_cpo_calculate_price_in_cart( $cart_item_data, $product_id );
                $item_qty = $item->get_quantity();
                $item->set_subtotal( $item_price );
                $item_total = $item_qty * $item_price;
                $item->set_total( $item_total );
                $item->calculate_taxes();
                $item->save();
            }
            
            // Updates tax totals
            //$order->update_taxes();
            // Calc totals - this also triggers save
            //$order->calculate_totals( false );
            ob_start();
            include wp_normalize_path( WP_PLUGIN_DIR . '/woocommerce/includes/admin/meta-boxes/views/html-order-item.php' );
            $html = ob_get_clean();
            wp_send_json_success( array(
                'html' => $html,
            ) );
        } catch ( Exception $e ) {
            wp_send_json_error( array(
                'error' => $e->getMessage(),
            ) );
        }
    }
    
    /**
     *   uni_cpo_product_settings_export
     */
    public static function uni_cpo_product_settings_export()
    {
        check_ajax_referer( 'uni_cpo_builder', 'security' );
    }
    
    /**
     *   uni_cpo_product_settings_import
     */
    public static function uni_cpo_product_settings_import()
    {
        check_ajax_referer( 'uni_cpo_builder', 'security' );
    }

}
Uni_Cpo_Ajax::init();