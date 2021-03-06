<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/*
* Uni_Cpo_Setting_Height_Type class
*
*/

class Uni_Cpo_Setting_Height_Type extends Uni_Cpo_Setting implements Uni_Cpo_Setting_Interface {

	/**
	 * Init
	 *
	 */
	public function __construct() {
		$this->setting_key  = 'height_type';
		$this->setting_data = array(
			'title'             => __( 'Height Type', 'uni-cpo' ),
			'custom_attributes' => array(
				'data-uni-constrainer' => 'yes'
			),
			'options'           => array(
				'auto'        => __( 'Auto', 'uni-cpo' ),
				'full-height' => __( 'Full Height', 'uni-cpo' ),
				'custom'      => __( 'Custom', 'uni-cpo' )
			),
			'js_var'            => 'data'
		);
		add_action( 'wp_footer', array( $this, 'js_template' ), 10 );
	}

	/**
	 * A template for the module
	 *
	 * @since 1.0
	 * @return string
	 */
	public function js_template() {
		?>
        <script id="js-builderius-setting-<?php echo $this->setting_key; ?>-tmpl" type="text/template">
            <div class="uni-modal-row uni-clear">
				<?php echo $this->generate_field_label_html() ?>
                <div class="uni-modal-row-second">
					<?php echo $this->generate_select_html() ?>
                </div>
            </div>
        </script>
		<?php
	}

}
