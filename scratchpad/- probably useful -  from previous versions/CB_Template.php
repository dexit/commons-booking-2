<?php
/**
 * @TODO: remove
 *
 * @package   CommonsBooking2
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */
/**
 * This class contain the Templating stuff for the frontend
 */
class CB_Template {
	/**
	 * Initialize the class
	 */
	public function initialize() {
		if ( !apply_filters( 'commons_booking_cb_template_initialize', true ) ) {
			return;
		}

		// Override the template hierarchy for load /templates/content-demo.php
		add_filter( 'template_include', array( __CLASS__, 'load_content_demo' ) );
	}
		/**
	 * Example for override the template system on the frontend
	 *
	 * @param string $original_template The original templace HTML.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public static function load_content_demo( $original_template ) {
		if ( is_singular( 'demo' ) && in_the_loop() ) {
			return wpbp_get_template_part( CB2_TEXTDOMAIN, 'content', 'demo', false );
		}
		return $original_template;
	}

}
$cb_template = new CB_Template();
$cb_template->initialize();
do_action( 'commons_booking_cb_template_instance', $cb_template );
