<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/*
*   Uni_Cpo_Option_Text_Area class
*
*/

class Uni_Cpo_Option_Text_Area extends Uni_Cpo_Option implements Uni_Cpo_Option_Interface {

	/**
	 * Stores extra (specific to this) option data.
	 *
	 * @var array
	 */
	protected $extra_data = array();

	/**
	 * Constructor gets the post object and sets the ID for the loaded option.
	 *
	 */
	public function __construct( $option = 0 ) {

		parent::__construct( $option );

	}

	public static function get_type() {
		return 'text_area';
	}

	public static function get_title() {
		return __( 'Text Area', 'uni-cpo' );
	}

	/**
	 * Returns an array of special vars associated with the option
	 *
	 * @return array
	 */
	public static function get_special_vars() {
		return array( 'count', 'count_spaces' );
	}

	/**
	 * Returns an array of data used in js query builder
	 *
	 * @return array
	 */
	public static function get_filter_data() {
		$operators = array(
			'less',
			'less_or_equal',
			'equal',
			'not_equal',
			'greater_or_equal',
			'greater',
			'is_empty',
			'is_not_empty'
		);

		return array(
			'input'        => 'text',
			'operators'    => $operators,
			'special_vars' => array(
				'count'        => array(
					'type'      => 'integer',
					'input'     => 'text',
					'operators' => $operators
				),
				'count_spaces' => array(
					'type'      => 'integer',
					'input'     => 'text',
					'operators' => $operators
				)
			)
		);
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	public function get_cpo_rate() {
		$cpo_general = $this->get_cpo_general();

		return ( ! empty( $cpo_general['main']['cpo_rate'] ) ) ? floatval( $cpo_general['main']['cpo_rate'] ) : 0;
	}

	/*
    |--------------------------------------------------------------------------
    | Other Actions
    |--------------------------------------------------------------------------
    */

	public function formatted_model_data() {

		$model['pid']                                         = $this->get_id();
		$model['settings']['general']                         = $this->get_general();
		$model['settings']['general']['status']               = array(
			'sync' => array(
				'type' => 'none',
				'pid'  => 0
			)
		);
		$model['settings']['general']                         = array_reverse( $model['settings']['general'] );
		$model['settings']['style']                           = $this->get_style();
		$model['settings']['advanced']                        = $this->get_advanced();
		$model['settings']['cpo_general']                     = $this->get_cpo_general();
		$model['settings']['cpo_general']['main']['cpo_slug'] = $this->get_slug_ending();
		$model['settings']['cpo_conditional']                 = $this->get_cpo_conditional();
		$model['settings']['cpo_validation']                  = $this->get_cpo_validation();

		return stripslashes_deep( $model );
	}

	public function get_edit_field( $data, $value ) {
		$id                   = $data['id'];
		$cpo_general_main     = $data['settings']['cpo_general']['main'];
		$cpo_general_advanced = $data['settings']['cpo_general']['advanced'];
		$cpo_validation_main  = ( isset( $data['settings']['cpo_validation']['main'] ) )
			? $data['settings']['cpo_validation']['main']
			: array();
		$cpo_validation_logic = ( isset( $data['settings']['cpo_validation']['logic'] ) )
			? $data['settings']['cpo_validation']['logic']
			: array();
		$is_cart_edit         = ( isset( $cpo_general_advanced['cpo_enable_cartedit'] ) && 'yes' === $cpo_general_advanced['cpo_enable_cartedit'] )
			? true
			: false;
		$attributes           = array( 'data-parsley-trigger' => 'change focusout submit' );
		$is_required          = ( 'yes' === $cpo_general_main['cpo_is_required'] ) ? true : false;

		$slug              = $this->get_slug();
		$input_css_class[] = $slug . '-field';
		$input_css_class[] = 'cpo-cart-item-option';

		if ( $is_required ) {
			$attributes['data-parsley-required'] = 'true';
		}
		if ( ! empty( $cpo_general_main['cpo_min_chars'] ) ) {
			$attributes['data-parsley-minlength'] = $cpo_general_main['cpo_min_chars'];
		}
		if ( ! empty( $cpo_general_main['cpo_max_chars'] ) ) {
			$attributes['data-parsley-maxlength'] = $cpo_general_main['cpo_max_chars'];
		}

		if ( ! empty( $cpo_validation_main ) && isset( $cpo_validation_main['cpo_validation_msg'] )
		     && is_array( $cpo_validation_main['cpo_validation_msg'] ) ) {
			foreach ( $cpo_validation_main['cpo_validation_msg'] as $k => $v ) {
				if ( empty($v) ) {
					continue;
				}
				switch ( $k ) {
					case 'req':
						$attributes['data-parsley-required-message'] = $v;
						break;
					case 'custom' :
						$extra_validation_msgs = preg_split( '/\R/', $v );
						$attributes = uni_cpo_field_attributes_modifier( $extra_validation_msgs, $attributes );
					default :
						break;
				}
			}
		}

		if ( ! empty( $cpo_validation_logic['cpo_vc_extra'] ) ) {
			$extra_validation = preg_split( '/\R/', $cpo_validation_logic['cpo_vc_extra'] );
			$attributes = uni_cpo_field_attributes_modifier( $extra_validation, $attributes );
		}

		ob_start();
		?>
        <div class="cpo-cart-item-option-wrapper uni-node-<?php esc_attr_e($id) ?>">
            <label><?php echo uni_cpo_sanitize_label( $this->cpo_order_label() ) ?></label>
	        <?php if ( $is_cart_edit ) { ?>
                <textarea
                        class="<?php echo implode( ' ', array_map( function ( $el ) {
                            return esc_attr( $el );
                        }, $input_css_class ) ); ?>"
                        name="<?php esc_attr_e( $slug ); ?>"
                        <?php echo self::get_custom_attribute_html( $attributes ); ?>><?php esc_attr_e( $value ); ?></textarea>
	        <?php } else { ?>
                <textarea
                        class="<?php echo implode( ' ', array_map( function ( $el ) {
					        return esc_attr( $el );
				        }, $input_css_class ) ); ?>"
                        name="<?php esc_attr_e( $slug ); ?>"
                        disabled><?php esc_attr_e( $value ); ?></textarea>
                <input
                        class="cpo-cart-item-option"
                        name="<?php esc_attr_e( $slug ) ?>"
                        value="<?php esc_attr_e( $value ) ?>"
                        type="hidden" />
	        <?php } ?>
        </div>
		<?php

		return ob_get_clean();
	}

	public static function get_settings() {
		return array(
			'settings' => array(
				'general'         => array(
					'status' => array(
						'sync' => array(
							'type' => 'none',
							'pid'  => 0
						),
					),
					'main'   => array(
						'width'  => array(
							'value' => 100,
							'unit'  => '%'
						),
						'height' => array(
							'value' => 100,
							'unit'  => 'px'
						)
					)
				),
				'style'           => array(
					'font'   => array(
						'color'          => '',
						'text_align'     => '',
						'font_family'    => 'inherit',
						'font_style'     => 'inherit',
						'font_weight'    => '',
						'font_size'      => array(
							'value' => '',
							'unit'  => 'px'
						),
						'letter_spacing' => ''
					),
					'background' => array(
                        'background_color' => '#ffffff',
                    ),
					'border' => array(
						'border_unit'   => 'px',
						'border_top'    => array(
							'style' => 'solid',
							'width' => '1',
							'color' => '#d7d7d7'
						),
						'border_bottom' => array(
							'style' => 'solid',
							'width' => '1',
							'color' => '#d7d7d7'
						),
						'border_left'   => array(
							'style' => 'solid',
							'width' => '1',
							'color' => '#d7d7d7'
						),
						'border_right'  => array(
							'style' => 'solid',
							'width' => '1',
							'color' => '#d7d7d7'
						),
						'radius'        => array(
							'value' => 5,
							'unit'  => 'px'
						),
					),
					'textarea'    => array(
						'padding' => array(
							'top'    => 4,
							'right'  => 10,
							'bottom' => 4,
							'left'   => 10,
							'unit'   => 'px'
						)
					)
				),
				'advanced'        => array(
					'layout'    => array(
						'margin'  => array(
							'top'    => '',
							'right'  => '',
							'bottom' => '',
							'left'   => '',
							'unit'   => 'px'
						)
					),
					'selectors' => array(
						'id_name'    => '',
						'class_name' => ''
					)
				),
				'cpo_general'     => array(
					'main'     => array(
						'cpo_slug'        => '',
						'cpo_is_required' => 'no',
						'cpo_def_val'     => '',
						'cpo_min_chars'   => '',
						'cpo_max_chars'   => '',
						'cpo_rate'        => ''
					),
					'advanced' => array(
						'cpo_label'        => '',
						'cpo_label_tag'    => 'label',
						'cpo_order_label'  => '',
						'cpo_is_tooltip'   => 'no',
						'cpo_tooltip'      => '',
						//'cpo_tooltip_type' => 'classic'
						'cpo_enable_cartedit' => 'no'
					)
				),
				'cpo_conditional' => array(
					'main' => array(
						'cpo_is_fc'      => 'no',
						'cpo_fc_default' => 'hide',
						'cpo_fc_scheme'  => ''
					)
				),
				'cpo_validation' => array(
					'main' => array(
						'cpo_validation_msg' => array(
							'req' => '',
							'custom' => ''
						)
					)
				)
			)
		);
	}

	public static function js_template() {
		?>
        <script id="js-builderius-module-<?php echo self::get_type(); ?>-tmpl" type="text/template">
            {{ const { id, type } = data; }}
            {{ const { id_name, class_name } = data.settings.advanced.selectors; }}
            {{ const { width, height } = data.settings.general.main; }}
            {{ const { color, text_align, font_family, font_style, font_weight, font_size, letter_spacing, line_height } = data.settings.style.font; }}
            {{ const { border_unit, border_top, border_bottom, border_left, border_right, radius } = data.settings.style.border; }}
            {{ const { background_color } = data.settings.style.background; }}
            {{ const { margin } = data.settings.advanced.layout; }}
            {{ const padding = uniGet( data.settings.style, 'textarea.padding', {top:4,right:10,bottom:4,left:10,unit:'px'} ); }}
            {{ const { cpo_slug, cpo_is_required, cpo_type, cpo_def_val } = data.settings.cpo_general.main; }}
            {{ const { cpo_label_tag, cpo_label, cpo_is_tooltip, cpo_tooltip } = data.settings.cpo_general.advanced; }}
            <div
                id="{{- id_name }}"
                class="uni-module uni-module-{{- type }} uni-node-{{- id }} {{- class_name }}"
                data-node="{{- id }}"
                data-type="{{- type }}">
            <style>
            	.uni-node-{{= id }} {
            		{{ if ( margin.top !== '' ) { }} margin-top: {{= margin.top + margin.unit }}; {{ } }}
                    {{ if ( margin.bottom !== '' ) { }} margin-bottom: {{= margin.bottom + margin.unit }}; {{ } }}
                    {{ if ( margin.left !== '' ) { }} margin-left: {{= margin.left + margin.unit }}; {{ } }}
                    {{ if ( margin.right !== '' ) { }} margin-right: {{= margin.right + margin.unit }}; {{ } }}
            	}
        		.uni-node-{{= id }} textarea {
        			{{ if ( width.value !== '' ) { }} width: {{= width.value+width.unit }}!important; {{ } }}
        			{{ if ( height.value !== '' ) { }} height: {{= height.value+height.unit }}; {{ } }}
        			{{ if ( background_color !== '' ) { }} background-color: {{= background_color }}; {{ } }}
                    {{ if ( border_top.style !== 'none' && border_top.color !== '' ) { }} border-top: {{= border_top.width + 'px '+ border_top.style +' '+ border_top.color }}; {{ } }}
                    {{ if ( border_bottom.style !== 'none' && border_bottom.color !== '' ) { }} border-bottom: {{= border_bottom.width + 'px '+ border_bottom.style +' '+ border_bottom.color }}; {{ } }}
                    {{ if ( border_left.style !== 'none' && border_left.color !== '' ) { }} border-left: {{= border_left.width + 'px '+ border_left.style +' '+ border_left.color }}; {{ } }}
                    {{ if ( border_right.style !== 'none' && border_right.color !== '' ) { }} border-right: {{= border_right.width + 'px '+ border_right.style +' '+ border_right.color }}; {{ } }}
        			{{ if ( radius.value !== '' ) { }} border-radius: {{= radius.value + radius.unit }}; {{ } }}
                    {{ if ( padding.top !== '' ) { }} padding-top: {{= padding.top + padding.unit }}; {{ } }}
                    {{ if ( padding.bottom !== '' ) { }} padding-bottom: {{= padding.bottom + padding.unit }}; {{ } }}
                    {{ if ( padding.left !== '' ) { }} padding-left: {{= padding.left + padding.unit }}; {{ } }}
                    {{ if ( padding.right !== '' ) { }} padding-right: {{= padding.right + padding.unit }}; {{ } }}
                    {{ if ( color !== '' ) { }} color: {{= color }}; {{ } }}
                    {{ if ( text_align !== '' ) { }} text-align: {{= text_align }}; {{ } }}
                    {{ if ( font_family !== 'inherit' ) { }} font-family: {{= font_family }}; {{ } }}
                    {{ if ( font_style !== 'inherit' ) { }} font-style: {{= font_style }}; {{ } }}
                    {{ if ( font_size.value !== '' ) { }} font-size: {{= font_size.value+font_size.unit }}; {{ } }}
                    {{ if ( font_weight !== '' ) { }} font-weight: {{= font_weight }}; {{ } }}
                    {{ if ( letter_spacing !== '' ) { }} letter-spacing: {{= letter_spacing+'em' }}; {{ } }}
        		}
        	</style>
            {{ if ( cpo_label_tag && cpo_label !== '' ) { }}
                <{{- cpo_label_tag }} class="uni-cpo-module-{{- type }}-label {{ if ( cpo_is_required === 'yes' ) { }} uni_cpo_field_required {{ } }}">
                	{{- cpo_label }}
                	{{ if ( cpo_is_tooltip === 'yes' && cpo_tooltip !== '' ) { }} <span class="uni-cpo-tooltip" data-tip="{{- cpo_tooltip }}"></span> {{ } }}
            	</{{- cpo_label_tag }}>
        	{{ } }}
            <textarea
                class="{{- cpo_slug }}-field js-uni-cpo-field-{{- type }}"
                id="{{- cpo_slug }}-field"
                name="{{- cpo_slug }}">{{- cpo_def_val }}</textarea>
            </div>
        </script>
		<?php
	}

	public static function template( $data, $post_data = array() ) {
		$id                   = $data['id'];
		$type                 = $data['type'];
		$selectors            = $data['settings']['advanced']['selectors'];
		$cpo_general_main     = $data['settings']['cpo_general']['main'];
		$cpo_general_advanced = $data['settings']['cpo_general']['advanced'];
		$cpo_validation_main  = ( isset( $data['settings']['cpo_validation']['main'] ) )
			? $data['settings']['cpo_validation']['main']
			: array();
		$cpo_label_tag        = $cpo_general_advanced['cpo_label_tag'];
		$attributes           = array( 'data-parsley-trigger' => 'change focusout submit' );
		$wrapper_attributes   = array();
		$option               = false;
		$rules_data           = $data['settings']['cpo_conditional']['main'];
		$is_required          = ( 'yes' === $cpo_general_main['cpo_is_required'] ) ? true : false;
		$is_tooltip           = ( 'yes' === $cpo_general_advanced['cpo_is_tooltip'] ) ? true : false;
		$is_enabled           = ( 'yes' === $rules_data['cpo_is_fc'] ) ? true : false;
		$is_hidden            = ( 'hide' === $rules_data['cpo_fc_default'] ) ? true : false;

		if ( ! empty( $data['pid'] ) ) {
			$option = uni_cpo_get_option( $data['pid'] );
		}

		$slug              = ( ! empty( $data['pid'] ) && is_object( $option ) ) ? $option->get_slug() : '';
		$css_id[]          = $slug;
		$css_class         = array(
			'uni-module',
			'uni-module-' . $type,
			'uni-node-' . $id
		);
		$input_css_class[] = $slug . '-field';
		$input_css_class[] = 'js-uni-cpo-field';
		$input_css_class[] = 'js-uni-cpo-field-' . $type;
		if ( ! empty( $selectors['id_name'] ) ) {
			array_push( $css_id, $selectors['id_name'] );
		}
		if ( ! empty( $selectors['class_name'] ) ) {
			array_push( $css_class, $selectors['class_name'] );
		}

		if ( 'yes' === $cpo_general_main['cpo_is_required'] ) {
			$attributes['data-parsley-required'] = 'true';
		}

		if ( ! empty( $cpo_general_main['cpo_min_chars'] ) ) {
			$attributes['data-parsley-minlength'] = $cpo_general_main['cpo_min_chars'];
		}
		if ( ! empty( $cpo_general_main['cpo_max_chars'] ) ) {
			$attributes['data-parsley-maxlength'] = $cpo_general_main['cpo_max_chars'];
		}

		if ( ! empty( $cpo_validation_main ) && isset( $cpo_validation_main['cpo_validation_msg'] )
		     && is_array( $cpo_validation_main['cpo_validation_msg'] ) ) {
			foreach ( $cpo_validation_main['cpo_validation_msg'] as $k => $v ) {
				if ( empty($v) ) {
					continue;
				}
				switch ( $k ) {
					case 'req':
						$attributes['data-parsley-required-message'] = $v;
						break;
					case 'custom' :
						$extra_validation_msgs = preg_split( '/\R/', $v );
						$attributes = uni_cpo_field_attributes_modifier( $extra_validation_msgs, $attributes );
					default :
						break;
				}
			}
		}

		if ( $is_enabled && $is_hidden ) {
			$wrapper_attributes['style'] = 'display:none;';
			$input_css_class[]           = 'uni-cpo-excluded-field';
		}

		$default_value = ( ! empty( $post_data ) && ! empty( $slug ) && ! empty( $post_data[$slug] ) )
			? $post_data[$slug]
			: $cpo_general_main['cpo_def_val'];
		?>
    <div
            id="<?php echo implode( ' ', array_map( function ( $el ) {
				return esc_attr( $el );
			}, $css_id ) ); ?>"
            class="<?php echo implode( ' ', array_map( function ( $el ) {
				return esc_attr( $el );
			}, $css_class ) ); ?>"
		<?php echo self::get_custom_attribute_html( $wrapper_attributes ); ?>>
		<?php
		if ( ! empty( $cpo_general_advanced['cpo_label'] ) ) { ?>
            <<?php esc_attr_e( $cpo_label_tag ); ?> class="uni-cpo-module-<?php esc_attr_e( $type ); ?>-label <?php if ( $is_required ) { ?> uni_cpo_field_required <?php } ?>">
			<?php esc_html_e( $cpo_general_advanced['cpo_label'] ); ?>
			<?php if ( $is_tooltip && $cpo_general_advanced['cpo_tooltip'] !== '' ) { ?>
                <span class="uni-cpo-tooltip"
                      data-tip="<?php echo uni_cpo_sanitize_tooltip( $cpo_general_advanced['cpo_tooltip'] ); ?>"></span>
			<?php } ?>
            </<?php esc_attr_e( $cpo_label_tag ); ?>>
		<?php } ?>
        <textarea
                class="<?php echo implode( ' ', array_map( function ( $el ) {
					return esc_attr( $el );
				}, $input_css_class ) ); ?>"
                id="<?php esc_attr_e( $slug ); ?>-field"
                name="<?php esc_attr_e( $slug ); ?>"
			    <?php echo self::get_custom_attribute_html( $attributes ); ?>><?php esc_attr_e( $default_value ); ?></textarea>
        </div>
		<?php

		self::conditional_rules( $data );
	}

	public static function get_css( $data ) {
		$id            = $data['id'];
		$main          = $data['settings']['general']['main'];
		$font          = $data['settings']['style']['font'];
		$background    = $data['settings']['style']['background'];
		$border_top    = $data['settings']['style']['border']['border_top'];
		$border_bottom = $data['settings']['style']['border']['border_bottom'];
		$border_left   = $data['settings']['style']['border']['border_left'];
		$border_right  = $data['settings']['style']['border']['border_right'];
		$radius        = $data['settings']['style']['border']['radius'];
		$padding       = $data['settings']['style']['textarea']['padding'];
		$margin        = $data['settings']['advanced']['layout']['margin'];

		ob_start();
		?>
        .uni-node-<?php esc_attr_e( $id ); ?> {
		<?php if ( ! empty( $margin['top'] ) ) { ?> margin-top: <?php esc_attr_e( "{$margin['top']}{$margin['unit']}" ) ?>; <?php } ?>
		<?php if ( ! empty( $margin['bottom'] ) ) { ?> margin-bottom: <?php esc_attr_e( "{$margin['bottom']}{$margin['unit']}" ) ?>; <?php } ?>
		<?php if ( ! empty( $margin['left'] ) ) { ?> margin-left: <?php esc_attr_e( "{$margin['left']}{$margin['unit']}" ) ?>; <?php } ?>
		<?php if ( ! empty( $margin['right'] ) ) { ?> margin-right: <?php esc_attr_e( "{$margin['right']}{$margin['unit']}" ) ?>; <?php } ?>
        }
        .uni-node-<?php esc_attr_e( $id ); ?> textarea {
		<?php if ( ! empty( $main['width']['value'] ) ) { ?> width: <?php esc_attr_e( "{$main['width']['value']}{$main['width']['unit']}" ) ?>!important;<?php } ?>
		<?php if ( ! empty( $main['height']['value'] ) ) { ?> height: <?php esc_attr_e( "{$main['height']['value']}{$main['height']['unit']}" ) ?>;<?php } ?>
		<?php if ( ! empty( $font['color'] ) ) { ?> color: <?php esc_attr_e( $font['color'] ); ?>;<?php } ?>
		<?php if ( ! empty( $font['text_align'] ) ) { ?> text-align: <?php esc_attr_e( $font['text_align'] ); ?>;<?php } ?>
		<?php if ( $font['font_family'] !== 'inherit' ) { ?> font-family: <?php esc_attr_e( $font['font_family'] ); ?>;<?php } ?>
		<?php if ( $font['font_style'] !== 'inherit' ) { ?> font-style: <?php esc_attr_e( $font['font_style'] ); ?>;<?php } ?>
		<?php if ( ! empty( $font['font_weight'] ) ) { ?> font-weight: <?php esc_attr_e( $font['font_weight'] ); ?>;<?php } ?>
		<?php if ( ! empty( $font['font_size']['value'] ) ) { ?> font-size: <?php esc_attr_e( "{$font['font_size']['value']}{$font['font_size']['unit']}" ) ?>; <?php } ?>
		<?php if ( ! empty( $font['letter_spacing'] ) ) { ?> letter-spacing: <?php esc_attr_e( $font['letter_spacing'] ); ?>em;<?php } ?>
		<?php if ( ! empty( $background['background_color'] ) ) { ?> background-color: <?php esc_attr_e( $background['background_color'] ); ?>;<?php } ?>
		<?php if ( $border_top['style'] !== 'none' && ! empty( $border_top['color'] ) ) { ?> border-top: <?php esc_attr_e( "{$border_top['width']}px {$border_top['style']} {$border_top['color']}" ) ?>; <?php } ?>
		<?php if ( $border_bottom['style'] !== 'none' && ! empty( $border_bottom['color'] ) ) { ?> border-bottom: <?php esc_attr_e( "{$border_bottom['width']}px {$border_bottom['style']} {$border_bottom['color']}" ) ?>; <?php } ?>
		<?php if ( $border_left['style'] !== 'none' && ! empty( $border_left['color'] ) ) { ?> border-left: <?php esc_attr_e( "{$border_left['width']}px {$border_left['style']} {$border_left['color']}" ) ?>; <?php } ?>
		<?php if ( $border_right['style'] !== 'none' && ! empty( $border_right['color'] ) ) { ?> border-right: <?php esc_attr_e( "{$border_right['width']}px {$border_right['style']} {$border_right['color']}" ) ?>; <?php } ?>
		<?php if ( ! empty( $radius['value'] ) ) { ?> border-radius: <?php esc_attr_e( "{$radius['value']}{$radius['unit']}" ) ?>; <?php } ?>
		<?php if ( ! empty( $padding['top'] ) ) { ?> padding-top: <?php esc_attr_e( "{$padding['top']}{$padding['unit']}" ) ?>; <?php } ?>
		<?php if ( ! empty( $padding['bottom'] ) ) { ?> padding-bottom: <?php esc_attr_e( "{$padding['bottom']}{$padding['unit']}" ) ?>; <?php } ?>
		<?php if ( ! empty( $padding['left'] ) ) { ?> padding-left: <?php esc_attr_e( "{$padding['left']}{$padding['unit']}" ) ?>; <?php } ?>
		<?php if ( ! empty( $padding['right'] ) ) { ?> padding-right: <?php esc_attr_e( "{$padding['right']}{$padding['unit']}" ) ?>; <?php } ?>
        }

		<?php
		if ( ! empty( $background['background_color'] ) ) { ?>
            .uni-node-<?php esc_attr_e( $id ); ?> textarea:focus, .uni-node-<?php esc_attr_e( $id ); ?> textarea:active {
            background-color: <?php esc_attr_e( $background['background_color'] ); ?>;
            }
		<?php } ?>

		<?php
		return ob_get_clean();
	}

	public function calculate( $form_data ) {
		$post_name = trim( $this->get_slug(), '{}' );

		if ( ! empty( $form_data[ $post_name ] ) ) {
			$price = $this->get_cpo_rate();
			$count = mb_strlen( $form_data[ $post_name ] );
			$count_no_spaces = mb_strlen( preg_replace('/\s+/', '', $form_data[ $post_name ] ) );
			if ( ! empty( $price ) ) {
				return array(
					$post_name                   => array(
						'calc'       => $price,
						'cart_meta'  => $form_data[ $post_name ],
						'order_meta' => $form_data[ $post_name ]
					),
					$post_name . '_count'        => array(
						'calc'       => $count,
						'cart_meta'  => $count,
						'order_meta' => $count
					),
					$post_name . '_count_spaces' => array(
						'calc'       => $count_no_spaces,
						'cart_meta'  => $count_no_spaces,
						'order_meta' => $count_no_spaces
					)
				);
			} else {
				return array(
					$post_name                   => array(
						'calc'       => floatval( $form_data[ $post_name ] ),
						'cart_meta'  => $form_data[ $post_name ],
						'order_meta' => $form_data[ $post_name ]
					),
					$post_name . '_count'        => array(
						'calc'       => $count,
						'cart_meta'  => $count,
						'order_meta' => $count
					),
					$post_name . '_count_spaces' => array(
						'calc'       => $count_no_spaces,
						'cart_meta'  => $count_no_spaces,
						'order_meta' => $count_no_spaces
					)
				);
			}
		} else {
			return array(
				$post_name                   => array(
					'calc'       => 0,
					'cart_meta'  => '',
					'order_meta' => ''
				),
				$post_name . '_count'        => array(
					'calc'       => 0,
					'cart_meta'  => 0,
					'order_meta' => 0
				),
				$post_name . '_count_spaces' => array(
					'calc'       => 0,
					'cart_meta'  => 0,
					'order_meta' => 0
				)
			);
		}
	}

}
