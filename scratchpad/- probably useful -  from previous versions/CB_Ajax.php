<?php
/**
 * Plugin_name
 * 
 * @package   Plugin_name
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */
/**
 * AJAX in the public
 */
class Cb_Ajax {
	/**
	 * Initialize the class
	 */
	public function initialize() {
		if ( !apply_filters( 'commons_booking_cb_ajax_initialize', true ) ) {
			return;
		}
		// For logged user
		add_action( 'wp_ajax_{your_method}', array( $this, 'your_method' ) );
		// For not logged user
		add_action( 'wp_ajax_nopriv_{your_method}', array( $this, 'your_method' ) );
	}
	/**
	 * The method to run on ajax
	 * 
	 * @return void
	 */
	public function your_method() {
		$return = array(
			'message' => 'Saved',
			'ID' => 1
		);
		wp_send_json_success( $return );
		// wp_send_json_error( $return );
	}
}
$cb_ajax = new Cb_Ajax();
$cb_ajax->initialize();
do_action( 'commons_booking_cb_ajax_instance', $cb_ajax );
