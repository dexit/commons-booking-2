<?php
/**
 * Base Admin class
 *
 * Handles includes of admin-related files
 *
 * @package   CommonsBooking2
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */
class CB2_Admin {
	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;
	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public static function initialize() {
		if ( !apply_filters( 'cb2_admin_initialize', true ) ) {
			return;
		}
		/*
		* Enqueue
		*/
		require_once(CB2_PLUGIN_ROOT . 'admin/includes/lib/wp-admin-notice/WP_Admin_Notice.php');
		require_once( CB2_PLUGIN_ROOT . 'admin/includes/CB2_Enqueue_Admin.php' );
		require_once( CB2_PLUGIN_ROOT . 'admin/includes/CB2_Options_Page.php' );
		require_once( CB2_PLUGIN_ROOT . 'admin/includes/CB2_Geocoding.php' );

		/*
		* Extra functions
		*/
		require_once( CB2_PLUGIN_ROOT . 'admin/includes/CB2_Extras_Admin.php' );
	}


	/**
	 * Return an instance of this class.
	 *
	 * @since 2.0.0
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			try {
				self::$instance = new self;
				self::initialize();
			} catch ( Exception $err ) {
				do_action( 'cb2_admin_failed', $err );
				if ( WP_DEBUG ) {
					throw $err->getMessage();
				}
			}
		}
		return self::$instance;
	}
}
add_action( 'plugins_loaded', array( 'CB2_Admin', 'get_instance' ) );


