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
class Commons_Booking_Admin {
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
		if ( !apply_filters( 'commons_booking_cb_admin_initialize', true ) ) {
			return;
		}
		/*
		 * @TODO :
		 *
		 * - Uncomment following lines if the admin class should only be available for super admins
		  if( ! is_super_admin() ) {
		  return;
		  }
		 */
		require_once( CB2_PLUGIN_ROOT . 'admin/includes/CB_Enqueue_Admin.php' );
		/*
		 * Load CMB
		 */
		require_once( CB2_PLUGIN_ROOT . 'admin/includes/CB_CMB.php' );
		/*
		 * Import Export settings
		 */
		require_once( CB2_PLUGIN_ROOT . 'admin/includes/CB_ImpExp.php' );
		/*
		 * Contextual Help
		 */
		require_once( CB2_PLUGIN_ROOT . 'admin/includes/CB_ContextualHelp.php' );
		/*
		 * All the pointers
		 */
		require_once( CB2_PLUGIN_ROOT . 'admin/includes/CB_Pointers.php' );
		/*
		 * Cron
		 */
		require_once( CB2_PLUGIN_ROOT . 'admin/includes/CB_Cron.php' );
		/*
		* All the extras functions
		*/
		require_once( CB2_PLUGIN_ROOT . 'admin/includes/CB_Extras_Admin.php' );
		/*
		* Bookings Functions
		*/
		require_once( CB2_PLUGIN_ROOT . 'admin/manage/includes/CB_Bookings_Admin.php' );
		/*
		* Bookings Table
		*/
		require_once( CB2_PLUGIN_ROOT . 'admin/manage/includes/CB_Bookings_Table.php' );
		/*
		 * Timeframes Table
		 */
		require_once( CB2_PLUGIN_ROOT . 'admin/manage/includes/CB_Timeframes_Admin.php' );
		/*
		 * Timeframes Table
		 */
		require_once( CB2_PLUGIN_ROOT . 'admin/manage/includes/CB_Timeframes_Table.php' );
		/*
		 * Locations Functions
		 */
		require_once( CB2_PLUGIN_ROOT . 'admin/manage/includes/CB_Locations_Admin.php' );
	}
	/**
	 * Return an instance of this class.
	 *
	 * @since 2.0.0
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		/*
		 * @TODO :
		 *
		 * - Uncomment following lines if the admin class should only be available for super admins
		  if( ! is_super_admin() ) {
		  return;
		  }
		 */
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			try {
				self::$instance = new self;
				self::initialize();
			} catch ( Exception $err ) {
				do_action( 'commons_booking_admin_failed', $err );
				if ( WP_DEBUG ) {
					throw $err->getMessage();
				}
			}
		}
		return self::$instance;
	}
}
add_action( 'plugins_loaded', array( 'Commons_Booking_Admin', 'get_instance' ) );
