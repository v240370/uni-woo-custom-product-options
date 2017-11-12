<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Uni_Cpo Class
 */
final class Uni_Cpo {

	/**
	 * Uni_Cpo version.
	 *
	 * @var string
	 */
	public $version = '4.0.0';

	/**
	 * The single instance of the class.
	 *
	 * @var Uni_Cpo
	 */
	protected static $_instance = null;

	/**
	 * Option factory instance.
	 *
	 * @var Uni_Cpo_Option_Factory
	 */
	public $option_factory = null;

	/**
	 * Module factory instance.
	 *
	 * @var Uni_Cpo_Module_Factory
	 */
	public $module_factory = null;

	protected $debug_mode = false;

	/**
	 *
	 */
	protected $var_slug;
	protected $nov_slug;
	protected $builder_id;

	private static $plugin_updates = array();

	/**
	 * Throw error on object clone
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'unicpo' ), '1.0.0' );
	}

	/**
	 * Disable unserializing of the class
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'unicpo' ), '1.0.0' );
	}

	/**
	 * Main Uni_Cpo Instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Uni_Cpo Constructor.
	 */
	public function __construct() {
		$this->define_constants();
		$this->includes();
		$this->init_hooks();
		add_action( 'activated_plugin', array( $this, 'activation' ) );

		$this->var_slug   = 'uni_cpo_';
		$this->nov_slug   = 'uni_nov_cpo_';
		$this->builder_id = 'uni_cpo_options';
		if (defined('WP_DEBUG') && true === WP_DEBUG) {
			$this->debug_mode = true;
		}
	}

	/**
	 *  Init hooks
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'init' ), 0 );
	}

	/**
	 * Define Uni_Cpo Constants.
	 */
	private function define_constants() {
		$upload_dir = wp_upload_dir();
		$this->define( 'UNI_CPO_PLUGIN_FILE', __FILE__ );
		$this->define( 'UNI_CPO_ABSPATH', dirname( __FILE__ ) . '/' );
		$this->define( 'UNI_CPO_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		$this->define( 'UNI_CPO_VERSION', $this->version );
		$this->define( 'UNI_CPO_CSS_DIR', trailingslashit( $upload_dir['basedir'] ) . 'cpo-css' );
		$this->define( 'UNI_CPO_CSS_URI', trailingslashit( $upload_dir['baseurl'] ) . 'cpo-css' );
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param  string $name
	 * @param  string|bool $value
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * What type of request is this?
	 *
	 * @param  string $type admin, ajax, cron or frontend.
	 *
	 * @return bool
	 */
	private function is_request( $type ) {
		switch ( $type ) {
			case 'admin' :
				return is_admin();
			case 'ajax' :
				return defined( 'DOING_AJAX' );
			case 'cron' :
				return defined( 'DOING_CRON' );
			case 'frontend' :
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
		}
	}

	/**
	 *  Includes
	 */
	public function includes() {

		//
		include_once( UNI_CPO_ABSPATH . 'includes/abstracts/abstract-uni-cpo-data.php' );
		include_once( UNI_CPO_ABSPATH . 'includes/abstracts/abstract-uni-cpo-option.php' );
		include_once( UNI_CPO_ABSPATH . 'includes/class-uni-cpo-option-factory.php' );
		include_once( UNI_CPO_ABSPATH . 'includes/abstracts/abstract-uni-cpo-module.php' );
		include_once( UNI_CPO_ABSPATH . 'includes/class-uni-cpo-module-factory.php' );
		include_once( UNI_CPO_ABSPATH . 'includes/abstracts/abstract-uni-cpo-setting.php' );

		//
		include_once( UNI_CPO_ABSPATH . 'includes/interfaces/class-uni-cpo-object-data-store-interface.php' );
		include_once( UNI_CPO_ABSPATH . 'includes/interfaces/class-uni-cpo-option-data-store-interface.php' );
		include_once( UNI_CPO_ABSPATH . 'includes/interfaces/class-uni-cpo-option-interface.php' );
		include_once( UNI_CPO_ABSPATH . 'includes/interfaces/class-uni-cpo-module-data-store-interface.php' );
		include_once( UNI_CPO_ABSPATH . 'includes/interfaces/class-uni-cpo-module-interface.php' );
		include_once( UNI_CPO_ABSPATH . 'includes/interfaces/class-uni-cpo-setting-interface.php' );

		//
		include_once( UNI_CPO_ABSPATH . 'includes/data-stores/class-uni-cpo-data-store.php' );
		include_once( UNI_CPO_ABSPATH . 'includes/data-stores/class-uni-cpo-data-store-wp.php' );
		include_once( UNI_CPO_ABSPATH . 'includes/data-stores/class-uni-cpo-option-data-store-cpt.php' );
		include_once( UNI_CPO_ABSPATH . 'includes/data-stores/class-uni-cpo-module-data-store-cpt.php' );

		//
		include_once( UNI_CPO_ABSPATH . 'includes/class-uni-cpo-data-exception.php' );
		//
		include_once( UNI_CPO_ABSPATH . 'includes/uni-cpo-option-functions.php' );

		// TODO differentiate inclusion of files when edit mode on or off
		if ( $this->is_request( 'frontend' ) || $this->is_request( 'admin' ) ) {
			// row / column / modules
			include_once( UNI_CPO_ABSPATH . 'includes/modules/class-uni-cpo-module-row.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/modules/class-uni-cpo-module-column.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/modules/class-uni-cpo-module-button.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/modules/class-uni-cpo-module-text.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/modules/class-uni-cpo-module-image.php' );

			// options
			include_once( UNI_CPO_ABSPATH . 'includes/options/class-uni-cpo-option-text-area.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/options/class-uni-cpo-option-text-input.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/options/class-uni-cpo-option-radio.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/options/class-uni-cpo-option-select.php' );
		}

		if ( $this->is_request( 'frontend' ) ) {
			// settings
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-width-type.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-width.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-content-width.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-height-type.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-height.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-vertical-align.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-color.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-hover-color.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-text-align.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-font-family.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-font-style.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-font-weight.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-font-size.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-letter-spacing.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-line-height.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-background-type.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-background-color.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-background-hover-color.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-background-image.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-border-top.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-border-bottom.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-border-left.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-border-right.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-border-unit.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-margin.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-padding.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-id-name.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-class-name.php' );

			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-float.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-content.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-align.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-href.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-target.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-rel.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-radius.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-image.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-divider-style.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-sync.php' );

			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-cpo-slug.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-cpo-is-required.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-cpo-type.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-cpo-min-val.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-cpo-max-val.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-cpo-step-val.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-cpo-def-val.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-cpo-min-chars.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-cpo-max-chars.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-cpo-rate.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-cpo-label.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-cpo-label-tag.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-cpo-order-label.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-cpo-is-tooltip.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-cpo-tooltip.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-cpo-tooltip-type.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-cpo-select-options.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-cpo-radio-options.php' );

			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-cpo-is-fc.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-cpo-fc-default.php' );
			include_once( UNI_CPO_ABSPATH . 'includes/settings/class-uni-cpo-setting-cpo-fc-scheme.php' );

			// common js templates
			include_once( UNI_CPO_ABSPATH . 'includes/class-uni-cpo-templates.php' );
			// frontend scripts and styles
			include_once( UNI_CPO_ABSPATH . 'includes/class-uni-cpo-frontend-scripts.php' );
		}

		if ( $this->is_request( 'admin' ) || $this->is_request( 'ajax' ) ) {
		}
		if ( $this->is_request( 'ajax' ) ) {
			include_once( UNI_CPO_ABSPATH . 'includes/class-uni-cpo-ajax.php' );
		}
		include_once( UNI_CPO_ABSPATH . 'includes/admin/uni-cpo-admin-functions.php' );
		include_once( UNI_CPO_ABSPATH . 'includes/admin/class-uni-cpo-plugin-settings.php' );

		include_once( UNI_CPO_ABSPATH . 'includes/class-eval-math.php' );
		include_once( UNI_CPO_ABSPATH . 'includes/class-uni-cpo-post-types.php' );
		include_once( UNI_CPO_ABSPATH . 'includes/class-uni-cpo-product.php' );
		include_once( UNI_CPO_ABSPATH . 'includes/uni-cpo-core-functions.php' );
	}

	/**
	 * Init
	 */
	public function init() {

		// Before init action.
		do_action( 'before_uni_cpo_init' );

		$this->check_version();

		//
		$this->option_factory = new Uni_Cpo_Option_Factory();
		$this->module_factory = new Uni_Cpo_Module_Factory();

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ), 10 );

		// Multilanguage support
		$this->load_plugin_textdomain();

		Uni_Cpo_Product::init();
		$settings = new Uni_Cpo_Plugin_Settings( __FILE__ );

		// Init action.
		do_action( 'uni_cpo_init' );

	}

	/**
	 *  Get the builder container CSS ID
	 */
	public function get_builder_id() {
		return $this->builder_id;
	}

	/**
	 *  get_var_slug
	 */
	public function get_var_slug() {
		return $this->var_slug;
	}

	/**
	 *  get_nov_slug
	 */
	public function get_nov_slug() {
		return $this->nov_slug;
	}

	/**
	 *  is_debug
	 */
	public function is_debug() {
		return $this->debug_mode;
	}

	/**
	 * Scripts and styles used in back end
	 * @since  1.0.0
	 */
	function admin_scripts( $hook ) {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		if ( ( $hook == 'post.php' || $hook == 'post-new.php' )
		     && in_array( get_post_type(), array( 'product', 'shop_order' ) )
		) {
			wp_enqueue_style(
				'uni-cpo-styles-admin',
				$this->plugin_url() . '/assets-dev/css/uni-cpo-styles-backend.css',
				false,
				UNI_CPO_VERSION,
				'all'
			);
		}
	}

	/**
	 * load_plugin_textdomain()
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'uni-cpo' );

		load_textdomain( 'uni-cpo', WP_LANG_DIR . '/uni-woo-custom-product-options/uni-cpo-' . $locale . '.mo' );
		load_plugin_textdomain( 'uni-cpo', false, plugin_basename( dirname( __FILE__ ) ) . "/languages" );
	}


	/**
	 * check_version()
	 */
	public function check_version() {

		$current_version = get_option( 'uni_cpo_version', null );

		if ( is_null( $current_version ) ) {
			update_option( 'uni_cpo_version', $this->version );
		}

		if ( ! defined( 'IFRAME_REQUEST' ) && ! empty( $plugin_updates ) && version_compare( $current_version, max( array_keys( self::$plugin_updates ) ), '<' ) ) {
			$this->update_plugin();
			do_action( 'uni_cpo_updated' );
		}
	}

	/**
	 * default_settings()
	 */
	function default_settings() {
		return array(
			'product_price_container' => '.summary.entry-summary .price > .amount, .summary.entry-summary .price ins .amount',
			'product_image_container' => 'figure.woocommerce-product-gallery__wrapper',
			'product_image_size'      => 'shop_single',
			'product_hide_total'      => 'on',
			'gmap_api_key'            => ''
		);
	}

	/**
	 * update_plugin()
	 */
	private function update_plugin() {
		// Silence
	}

	/**
	 * plugin_url()
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * plugin_path()
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Get Ajax URL.
	 * @return string
	 */
	public function ajax_url() {
		return admin_url( 'admin-ajax.php', 'relative' );
	}

	/**
	 * cpo_activation()
	 */
	public function activation( $plugin ) {}

}
