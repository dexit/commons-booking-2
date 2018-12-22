<?php
/**
 * Library and meta boxes for items & locations
 *
 *
 * @package   CommonsBooking2
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */
/**
 * All the CMB related code.
 */
class CB2_Metaboxes {
	/**
	 * Initialize CMB2 & related libraries
	 *
	 * @since 2.0.0
	 */
	public function __construct() {

		require_once( 'lib/cmb2/init.php' );
		require_once( 'lib/cmb2-grid/Cmb2GridPluginLoad.php' );
		require_once( 'lib/cmb2-tabs/cmb2-tabs.php' );
		require_once( 'lib/cmb2-field-icon/cmb2-field-icon.php' );
		require_once( 'lib/CMB2-field-Calendar/cmb-field-calendar.php' );
		require_once( 'lib/CMB2-field-Paragraph/cmb-field-paragraph.php' );

		/* @TODO: add metaboxes for items & locations */
		// add_action( 'cmb2_init', array( $this, 'cmb_demo_metaboxes' ) );
	}
}
new CB2_Metaboxes();
