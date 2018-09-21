<?php
/**
 * Admin Pointers for the backend.
 *
 * @TODO: not in use.
 *
 * @package   CommonsBooking2
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */
class CB_Pointers {
	/**
	 * Initialize the Pointers.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		if ( !apply_filters( 'commons_booking_cb_pointers_initialize', true ) ) {
			return;
		}
		new PointerPlus( array( 'prefix' => CB2_TEXTDOMAIN ) );
		add_filter( CB2_TEXTDOMAIN . '-pointerplus_list', array( $this, 'custom_initial_pointers' ), 10, 2 );
	}
	/**
	 * Add pointers.
	 * Check on https://github.com/Mte90/pointerplus/blob/master/pointerplus.php for examples
	 *
	 * @param array $pointers The list of pointers.
	 * @param array $prefix   For your pointers.
	 *
	 * @return mixed
	 */
	function custom_initial_pointers( $pointers, $prefix ) {
		return array_merge( $pointers, array(
			$prefix . '_contextual_tab' => array(
				'selector' => '#contextual-help-link',
				'title' => __( 'Boilerplate Help', CB2_TEXTDOMAIN ),
				'text' => __( 'A pointer for help tab.<br>Go to Posts, Pages or Users for other pointers.', CB2_TEXTDOMAIN ),
				'edge' => 'top',
				'align' => 'right',
				'icon_class' => 'dashicons-welcome-learn-more',
			)
				) );
	}
}
new CB_Pointers();
