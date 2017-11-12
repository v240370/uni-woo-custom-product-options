<?php
/**
 * Uni Cpo Option Functions
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Main function for returning a model by its object type.
 *
 */
function uni_cpo_get_model( $model_obj_type, $model_id = 0, $model_type = false ) {
	if ( 'option' === $model_obj_type ) {
		return UniCpo()->option_factory->get_option( $model_id, $model_type );
	} elseif ( 'module' === $model_obj_type ) {
		return UniCpo()->module_factory->get_module( $model_id, $model_type );
	}
}

/**
 * Main function for returning options, uses the Uni_Cpo_Option_Factory class.
 *
 */
function uni_cpo_get_option( $option_id = 0, $option_type = false ) {
	return UniCpo()->option_factory->get_option( $option_id, $option_type );
}

/**
 * Get all registered option types.
 *
 */
function uni_cpo_get_option_types() {

	$option_types = array(
		'text_input',
		'text_area',
		'select',
		'radio'
	);

	// make it possible for third-party plugins to add new option types
	$option_types = apply_filters( 'uni_cpo_option_types', $option_types );

	$option_types = array_filter( $option_types, function ( $type ) {
		return ! in_array( $type, uni_cpo_get_reserved_option_types() );
	} );

	return $option_types;
}

/**
 * uni_cpo_get_reserved_option_types()
 *
 */
function uni_cpo_get_reserved_option_types() {
	return array( 'special_var' );
}

/**
 * uni_cpo_get_reserved_option_slugs()
 *
 */
function uni_cpo_get_reserved_option_slugs() {
	return array(
		'uni_cpo_quantity',
		'uni_cpo_list_of_attachments',
		'uni_cpo_raw_price',
		'uni_cpo_raw_price_tax_rev',
		'uni_cpo_price_tax_rev',
		'uni_cpo_price',
		'uni_cpo_price_suffix',
		'uni_cpo_price_discounted',
		'uni_cpo_raw_total',
		'uni_cpo_raw_total_tax_rev',
		'uni_cpo_total_tax_rev',
		'uni_cpo_total',
		'uni_cpo_total_suffix'
	);
}

/**
 * Get all registered module types.
 *
 */
function uni_cpo_get_module_types() {

	$module_types = array(
		'row',
		'column',
		'text',
		'button',
		'image'
	);

	// make it possible for third-party plugins to add new module types
	$module_types = apply_filters( 'uni_cpo_module_types', $module_types );

	return $module_types;
}

/**
 * Get all registered setting types.
 *
 */
function uni_cpo_get_setting_types() {

	$setting_types = array(
		'width_type',
		'width',
		'content_width',
		'height_type',
		'height',
		'vertical_align',
		'color',
		'hover_color',
		'text_align',
		'font_family',
		'font_style',
		'font_weight',
		'font_size',
		'letter_spacing',
		'line_height',
		'background_type',
		'background_color',
		'background_hover_color',
		'background_image',
		'border_top',
		'border_bottom',
		'border_left',
		'border_right',
		'border_unit',
		'margin',
		'padding',
		'id_name',
		'class_name',
		'float',
		'content',
		'align',
		'href',
		'target',
		'rel',
		'radius',
		'image',
		'divider_style',
		'sync',
		'cpo_slug',
		'cpo_is_required',
		'cpo_type',
		'cpo_min_val',
		'cpo_max_val',
		'cpo_step_val',
		'cpo_def_val',
		'cpo_min_chars',
		'cpo_max_chars',
		'cpo_rate',
		'cpo_label',
		'cpo_label_tag',
		'cpo_order_label',
		'cpo_is_tooltip',
		'cpo_tooltip',
		'cpo_tooltip_type',
		'cpo_is_fc',
		'cpo_fc_default',
		'cpo_fc_scheme',
		'cpo_select_options',
		'cpo_radio_options'
	);

	// make it possible for third-party plugins to add new module types
	$setting_types = apply_filters( 'uni_cpo_setting_types', $setting_types );

	return $setting_types;
}