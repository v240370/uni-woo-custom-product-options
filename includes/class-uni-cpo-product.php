<?php
/**
 * Post
 *
 * @class       Builderius_Post
 * @version     1.0.0
 * @package     Builderius/Classes/
 * @category    Class
 * @author      MooMoo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Uni_Cpo_Product Class.
 */
final class Uni_Cpo_Product {

	/**
	 * Hooks.
	 *
	 * @since 4.0.0
	 * @return void
	 */
	static public function init() {
		add_action( 'admin_bar_menu', array( __CLASS__, 'admin_bar_menu_item' ), 99 );
		add_action( 'woocommerce_before_add_to_cart_button', array( __CLASS__, 'display_options' ), 10 );
		add_filter( 'post_row_actions', array( __CLASS__, 'builder_link' ), 10, 2 );
	}

	/**
	 * Adds the page builder button to the WordPress admin bar.
	 *
	 * @since 4.0.0
	 * @return void
	 */
	static public function admin_bar_menu_item( $wp_admin_bar ) {
		global $post;

		if ( self::is_post_editable() ) {

			$wp_admin_bar->add_node( array(
				'id'    => 'cpo-admin-bar-edit-link',
				'title' => __( 'CPO builder', 'uni-cpo' ),
				'href'  => self::get_edit_url( $post->ID )
			) );
		}
	}

	/**
	 * Show the "To CPO builder" link in admin products list.
	 *
	 * @param  array $actions
	 * @param  WP_Post $post Post object
	 *
	 * @return array
	 */
	static public function builder_link( $actions, $post ) {
		if ( ! current_user_can( apply_filters( 'uni_cpo_cpo_builder_capability', 'manage_woocommerce' ) ) ) {
			return $actions;
		}

		if ( 'product' !== $post->post_type ) {
			return $actions;
		}

		$product = wc_get_product( $post->ID );

		if ( false === $product ) {
			return $actions;
		}

		if ( 'simple' !== $product->get_type() ) {
			return $actions;
		}

		$actions['cpo-builder'] = '<a href="' . esc_url( self::get_edit_url() ) . '" aria-label="'
		                          . esc_attr__( 'Go to CPO builder', 'uni-cpo' )
		                          . '" rel="permalink">' . __( 'CPO Builder', 'uni-cpo' ) . '</a>';

		return $actions;
	}

	/**
	 * Deletes content
	 *
	 * @since 4.0.0
	 *
	 * @param integer
	 *
	 * @return bool|int
	 */
	static public function delete_content( $product_id ) {
		$product = wc_get_product( $product_id );

		if ( $product ) {
			do_action( 'before_cpo_delete_content', $product_id );

			$id = delete_post_meta( $product_id, '_cpo_content' );

			// update generated CSS
			update_option( 'builder_css_cached_for' . $product_id, '' );

			do_action( 'after_cpo_delete_content', $product_id );

			return $id;
		}

		return false;
	}

	/**
	 * Display product options
	 *
	 * @since 4.0.0
	 *
	 * @param string $content
	 *
	 * @return void
	 */
	static public function display_options() {

		do_action( 'cpo_before_render_content' );

		echo '<div id="' . esc_attr( UniCpo()->get_builder_id() ) . '" class="uni-builderius-container">';

		do_action( 'cpo_before_render_form_fields' );

		if ( ! self::is_builder_active() && self::is_single_product() ) {
			$product_data = self::get_product_data();
			if ( 'on' === $product_data['settings_data']['cpo_enable']
			     && ! empty( $product_data['content'] )
			) {

				if ( ! empty( $product_data['settings_data']['price_disabled_msg'] ) ) {
					echo '<div class="js-uni-cpo-ordering-disabled-notice">';
					echo $product_data['settings_data']['price_disabled_msg'];
					echo '</div>';
				}

				echo '<input type="hidden" class="js-cpo-pid" name="uni_cpo_product_id" value="' . esc_attr( $product_data['id'] ) . '" />';
				echo '<input type="hidden" class="js-cpo-add-to-cart" name="add-to-cart" value="' . esc_attr( $product_data['id'] ) . '" />';
				echo '<input type="hidden" class="js-cpo-cart-item" name="uni_cpo_cart_item_id" value="' . current_time( 'timestamp' ) . '" />';

				foreach ( $product_data['content'] as $row_key => $row_data ) {
					$row_class = UniCpo()->module_factory::get_classname_from_module_type( $row_data['type'] );
					call_user_func( array( $row_class, 'template' ), $row_data );
				}
			}
		}

		do_action( 'cpo_after_render_form_fields' );

		echo '</div>';

		do_action( 'cpo_after_render_content' );

	}

	/**
	 * Enable the builder editor for the main post in the query.
	 *
	 * @since 4.0.0
	 * @return void
	 */
	static public function enable_editing() {
		global $wp_the_query;

		if ( self::is_post_editable() ) {

			$post = $wp_the_query->post;

			//  TODO Lock the builder
			/*if ( ! function_exists( 'wp_set_post_lock' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/post.php' );
			}
			wp_set_post_lock( $post->ID );*/
		}
	}

	/**
	 * Deletes content
	 *
	 * @since 4.0.0
	 *
	 * @param int $product_id
	 *
	 * @return bool|int
	 */
	static public function get_content( $product_id ) {
		$product = wc_get_product( $product_id );

		if ( $product ) {
			do_action( 'before_cpo_get_content', $product_id );

			$content = get_post_meta( $product_id, '_cpo_content', true );

			do_action( 'after_cpo_get_content', $product_id, $content );

			return $content;
		}

		return false;
	}

	/**
	 * Returns a builder edit URL.
	 *
	 * @since 4.0.0
	 *
	 * @param int|bool $post_id
	 *
	 * @return string
	 */
	static public function get_edit_url( $post_id = false ) {
		if ( false === $post_id ) {
			global $post;
		} else {
			$post = get_post( $post_id );
		}

		$builder_edit_mode_uri = add_query_arg( 'cpo_options', '1', get_permalink( $post->ID ) );

		return set_url_scheme( $builder_edit_mode_uri );
	}

	/**
	 * Returns the currently viewing product data
	 *
	 * @since 4.0.0
	 *
	 * @return array
	 */
	static public function get_product_data() {
		global $wp_the_query;

		$data = array();
		if ( self::is_single_product() ) {
			$data = self::get_product_data_by_id( $wp_the_query->post->ID );
		}

		return $data;
	}

	/**
	 * Checks whether the post can be edited
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	static public function is_post_editable() {
		global $wp_the_query;

		if ( is_singular( 'product' ) && isset( $wp_the_query->post ) ) {

			$product      = wc_get_product( $wp_the_query->post );
			$user_can     = current_user_can( 'edit_post', $product->get_id() );
			$product_type = $product->get_type();

			if ( $user_can && 'simple' === $product_type ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks whether UniCpo's builder mode is active
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	static public function is_builder_active() {
		if ( self::is_post_editable() && ! is_admin() && ! post_password_required() ) {
			if ( isset( $_GET['cpo_options'] ) ) {
				return true;
			}
			if ( '/?cpo_options' === $_SERVER['REQUEST_URI'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks whether we are on a single product page
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	static public function is_single_product() {
		global $wp_the_query;
		if ( is_singular( 'product' ) && is_main_query() && isset( $wp_the_query->post ) ) {
			return true;
		}

		return false;

	}

	/**
	 * Checks whether display or not a visual pointers in order
	 * to guide the user through key elements/features
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	static public function is_guide_needed() {

		if ( self::is_builder_active() ) {
			$current_user = wp_get_current_user();
			$guide_used   = get_user_meta( $current_user->ID, '_cpo_guide_used', true );

			if ( empty( $guide_used ) ) {
				update_user_meta( $current_user->ID, '_cpo_guide_used', 1 );

				return true;
			}
		}

		return false;
	}

	/**
	 * Returns the product data by product id
	 *
	 * @since 4.0.0
	 *
	 * @param int $product_id
	 *
	 * @return array
	 */
	static public function get_product_data_by_id( $product_id ) {
		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			return array();
		}

		$data['id']      = $product->get_id();
		$data['uri']     = get_permalink( $product->get_id() );
		$data['content'] = array();

		$cpo_content = get_post_meta( $product->get_id(), '_cpo_content', true );
		if ( $cpo_content ) {
			$cpo_content     = wp_unslash( uni_cpo_decode( $cpo_content ) );
			$data['content'] = $cpo_content;
		}

		$cpo_enable            = ( get_post_meta( $product->get_id(), '_cpo_enable', true ) )
			? get_post_meta( $product->get_id(), '_cpo_enable', true )
			: 'off';
		$calc_enable           = ( get_post_meta( $product->get_id(), '_cpo_calc_enable', true ) )
			? get_post_meta( $product->get_id(), '_cpo_calc_enable', true )
			: 'off';
		$calc_btn_enable       = ( get_post_meta( $product->get_id(), '_cpo_calc_btn_enable', true ) )
			? get_post_meta( $product->get_id(), '_cpo_calc_btn_enable', true )
			: 'off';
		$price_min             = ( get_post_meta( $product->get_id(), '_cpo_min_price', true ) )
			? floatval( get_post_meta( $product->get_id(), '_cpo_min_price', true ) )
			: 0;
		$price_max             = ( get_post_meta( $product->get_id(), '_cpo_max_price', true ) )
			? floatval( get_post_meta( $product->get_id(), '_cpo_max_price', true ) )
			: 0;
		$data['settings_data'] = array(
			'cpo_enable'         => $cpo_enable,
			'calc_enable'        => $calc_enable,
			'calc_btn_enable'    => $calc_btn_enable,
			'min_price'          => $price_min,
			'max_price'          => $price_max,
			'price_disabled_msg' => get_post_meta( $product->get_id(), '_cpo_price_disabled_msg', true )
		);

		$rules_enable         = ( get_post_meta( $product->get_id(), '_cpo_formula_rules_enable', true ) )
			? get_post_meta( $product->get_id(), '_cpo_formula_rules_enable', true )
			: 'off';
		$formula_scheme       = ( get_post_meta( $product->get_id(), '_cpo_formula_scheme', true ) )
			? get_post_meta( $product->get_id(), '_cpo_formula_scheme', true )
			: array();
		$data['formula_data'] = array(
			'rules_enable'   => $rules_enable,
			'formula_scheme' => $formula_scheme,
			'main_formula'   => get_post_meta( $product->get_id(), '_cpo_main_formula', true )
		);

		$data['weight_data'] = array();

		$nov_enable       = ( get_post_meta( $product->get_id(), '_cpo_nov_enable', true ) )
			? get_post_meta( $product->get_id(), '_cpo_nov_enable', true )
			: 'off';
		$wholesale_enable = ( get_post_meta( $product->get_id(), '_cpo_wholesale_enable', true ) )
			? get_post_meta( $product->get_id(), '_cpo_wholesale_enable', true )
			: 'off';
		$data['nov_data'] = array(
			'nov_enable'       => $nov_enable,
			'wholesale_enable' => $wholesale_enable,
			'nov'              => get_post_meta( $product->get_id(), '_cpo_nov', true )
		);

		return $data;
	}

	/**
	 * Saves product data
	 *
	 * @since 4.0.0
	 *
	 * @return array
	 */
	static public function save_product_data( $data, $context = 'all' ) {
		$product_id = $data['product_id'];
		$product    = wc_get_product( $product_id );

		try {
			if ( ! $product ) {
				throw new Exception( __( 'Product does not exist or not chosen', 'uni-cpo' ) );
			}

			if ( 'all' === $context ) {
				update_post_meta( $product->get_id(), '_cpo_enable', $data['settings_data']['cpo_enable'] );
				update_post_meta( $product->get_id(), '_cpo_calc_enable', $data['settings_data']['calc_enable'] );
				update_post_meta( $product->get_id(), '_cpo_calc_btn_enable', $data['settings_data']['calc_btn_enable'] );
				update_post_meta( $product->get_id(), '_cpo_min_price', $data['settings_data']['min_price'] );
				update_post_meta( $product->get_id(), '_cpo_max_price', $data['settings_data']['max_price'] );
				update_post_meta( $product->get_id(), '_cpo_price_disabled_msg', $data['settings_data']['price_disabled_msg'] );

				update_post_meta( $product->get_id(), '_cpo_formula_rules_enable', $data['formula_data']['rules_enable'] );
				update_post_meta( $product->get_id(), '_cpo_formula_scheme', $data['formula_data']['formula_scheme'] );
				update_post_meta( $product->get_id(), '_cpo_main_formula', $data['formula_data']['main_formula'] );

				update_post_meta( $product->get_id(), '_cpo_nov_enable', $data['nov_data']['nov_enable'] );
				update_post_meta( $product->get_id(), '_cpo_wholesale_enable', $data['nov_data']['wholesale_enable'] );
				update_post_meta( $product->get_id(), '_cpo_nov', $data['nov_data']['nov'] );

				return array(
					'settings_data' => $data['settings_data'],
					'formula_data'  => $data['formula_data'],
					'weight_data'   => $data['weight_data'],
					'nov_data'      => $data['nov_data'],
				);
			} elseif ( 'settings_data' === $context ) {
				update_post_meta( $product->get_id(), '_cpo_enable', $data['settings_data']['cpo_enable'] );
				update_post_meta( $product->get_id(), '_cpo_calc_enable', $data['settings_data']['calc_enable'] );
				update_post_meta( $product->get_id(), '_cpo_calc_btn_enable', $data['settings_data']['calc_btn_enable'] );
				update_post_meta( $product->get_id(), '_cpo_min_price', $data['settings_data']['min_price'] );
				update_post_meta( $product->get_id(), '_cpo_max_price', $data['settings_data']['max_price'] );
				update_post_meta( $product->get_id(), '_cpo_price_disabled_msg', $data['settings_data']['price_disabled_msg'] );

				return array( 'settings_data' => $data['settings_data'] );
			} elseif ( 'formula_data' === $context ) {
				update_post_meta( $product->get_id(), '_cpo_formula_rules_enable', $data['formula_data']['rules_enable'] );
				update_post_meta( $product->get_id(), '_cpo_formula_scheme', $data['formula_data']['formula_scheme'] );
				update_post_meta( $product->get_id(), '_cpo_main_formula', $data['formula_data']['main_formula'] );

				return array( 'formula_data' => $data['formula_data'] );
			} elseif ( 'nov_data' === $context ) {
				update_post_meta( $product->get_id(), '_cpo_nov_enable', $data['nov_data']['nov_enable'] );
				update_post_meta( $product->get_id(), '_cpo_wholesale_enable', $data['nov_data']['wholesale_enable'] );
				update_post_meta( $product->get_id(), '_cpo_nov', $data['nov_data']['nov'] );

				return array( 'nov_data' => $data['nov_data'] );
			}

			return array( 'error' => __( 'Error', 'uni-cpo' ) );
		} catch ( Exception $e ) {
			return array( 'error' => $e->getMessage() );
		}
	}

	/**
	 * Saves content
	 *
	 * @since 4.0.0
	 *
	 * @param int $product_id
	 * @param string $content
	 *
	 * @return bool
	 */
	static public function save_content( $product_id, $content ) {
		$product = wc_get_product( $product_id );

		if ( $product ) {
			do_action( 'before_cpo_save_content', $content, $product_id );

			$content = stripslashes_deep( $content );
			$content = json_decode( $content, true );
			$content = uni_cpo_encode( $content );
			$id      = update_post_meta( $product_id, '_cpo_content', $content );

			// update generated CSS
			update_option( 'builder_css_cached_for' . $product_id, '' );

			do_action( 'after_cpo_save_content', $product_id );

			return $id;
		}

		return false;
	}

	/**
	 * Updates content
	 *
	 * @since 4.0.0
	 *
	 * @param int $product_id
	 * @param string $content
	 *
	 * @return bool
	 */
	static public function update_content( $product_id, $content ) {
		$product = wc_get_product( $product_id );

		if ( $product ) {
			do_action( 'before_cpo_update_content', $content, $product_id );

			$id = update_post_meta( $product_id, '_cpo_content', $content );

			// update generated CSS
			update_option( 'builder_css_cached_for' . $product_id, '' );

			do_action( 'after_cpo_update_content', $product_id );

			return $id;
		}

		return false;
	}

}