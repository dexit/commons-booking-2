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
		// Immediately bootstraps CMB2 running action cmb2_admin_init
		require_once( CB2_PLUGIN_ROOT . 'framework/includes/lib/cmb2/init.php' );
		require_once( CB2_PLUGIN_ROOT . 'framework/includes/lib/cmb2-grid/Cmb2GridPluginLoad.php' );
		require_once( CB2_PLUGIN_ROOT . 'framework/includes/lib/cmb2-tabs/cmb2-tabs.php' );
		require_once( CB2_PLUGIN_ROOT . 'framework/includes/lib/cmb2-field-icon/cmb2-field-icon.php' );
		require_once( CB2_PLUGIN_ROOT . 'framework/includes/lib/cmb2-field-paragraph/cmb2-field-paragraph.php' );
		require_once( CB2_PLUGIN_ROOT . 'framework/includes/lib/cmb2-field-switch-button/cmb2-field-switch-button.php' );
		// Custom local
		require_once( CB2_PLUGIN_ROOT . 'framework/includes/cmb2-field-calendar/cmb2-field-calendar.php' );
		require_once( CB2_PLUGIN_ROOT . 'framework/includes/cmb2-field-post-link/cmb2-field-post-link.php' );

		/* @TODO: add metaboxes for items & locations */
		// add_action( 'cmb2_init', array( $this, 'cmb_demo_metaboxes' ) );
	}

	public static function factory() {
		return new self();
	}
}
CB2_Metaboxes::factory();
