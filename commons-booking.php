<?php
/**
 * @package   Commons_Booking
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 *
 * Plugin Name:       Commons Booking
 * Plugin URI:        @TODO
 * Description:       @TODO
 * Version:           2.0.0
 * Author:            Florian Egermann
 * Author URI:        http://commonsbooking.wielebenwir.de
 * Text Domain:       commons-booking
 * License:           GPL 2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * commons-booking: v2.0.5
 */
// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
	die;
}
define( 'CB_VERSION', '2.0.0' );
define( 'CB_DEV_BUILD', '180516' );
define( 'CB_TEXTDOMAIN', 'commons-booking-2' );
define( 'CB_NAME', 'CommonsBooking' );
define( 'CB_PLUGIN_ROOT', plugin_dir_path( __FILE__ ) );
define( 'CB_PLUGIN_ABSOLUTE',  __FILE__  );
define( 'CB2_MENU_SLUG', 'cb2_menu');

/* DB Tables @TODO */
define( 'CB_TIMEFRAME_OPTIONS_TABLE', 'cb2_timeframe_options' );

/**
 * Load the textdomain of the plugin
 *
 * @return void
 */
function cb_load_plugin_textdomain() {
	$locale = apply_filters( 'plugin_locale', get_locale(), CB_TEXTDOMAIN );
	load_textdomain( CB_TEXTDOMAIN, trailingslashit( WP_PLUGIN_DIR ) . CB_TEXTDOMAIN . '/languages/' . CB_TEXTDOMAIN . '-' . $locale . '.mo' );
}
add_action( 'plugins_loaded', 'cb_load_plugin_textdomain', 1 );


require_once(CB_PLUGIN_ROOT . 'framework/CB2_framework.php');
require_once(CB_PLUGIN_ROOT . 'public/Commons_Booking.php');
require_once(CB_PLUGIN_ROOT . 'public/includes/CB_Shortcodes.php');
require_once(CB_PLUGIN_ROOT . 'wp-admin/WP_admin_integration.php'); // admin screens
/*
require_once( CB_PLUGIN_ROOT . 'composer/autoload.php' );
require_once( CB_PLUGIN_ROOT . 'includes/CB_PostTypes.php' );
require_once( CB_PLUGIN_ROOT . 'includes/CB_PostTypes_Metaboxes.php' );
require_once( CB_PLUGIN_ROOT . 'includes/CB_Helpers.php' );
require_once( CB_PLUGIN_ROOT . 'includes/CB_Gui.php' );
require_once( CB_PLUGIN_ROOT . 'includes/CB_Settings.php' );
*/

/*
require_once( CB_PLUGIN_ROOT . 'classes/CB_Object.php' );
require_once( CB_PLUGIN_ROOT . 'classes/CB_Timeframes.php' );
require_once( CB_PLUGIN_ROOT . 'classes/CB_Timeframe_Options.php' );
require_once( CB_PLUGIN_ROOT . 'classes/CB_Calendar.php' );
require_once( CB_PLUGIN_ROOT . 'classes/CB_Slots.php' );
require_once( CB_PLUGIN_ROOT . 'classes/CB_Slot_Templates.php' );
require_once( CB_PLUGIN_ROOT . 'classes/CB_Codes.php' );
require_once( CB_PLUGIN_ROOT . 'classes/CB_Locations.php' );
require_once( CB_PLUGIN_ROOT . 'includes/CB_Strings.php' );
require_once( CB_PLUGIN_ROOT . 'includes/CB_Holidays.php' );
require_once( CB_PLUGIN_ROOT . 'includes/CB_FakePage.php' );
require_once( CB_PLUGIN_ROOT . 'includes/CB_API.php' );
require_once( CB_PLUGIN_ROOT . 'includes/CB_Template.php' );
require_once( CB_PLUGIN_ROOT . 'includes/CB_Shortcodes.php' );
require_once( CB_PLUGIN_ROOT . 'includes/lib/yasumi/src/Yasumi/Yasumi.php' );
// if ( defined( 'WP_CLI' ) && WP_CLI ) {
// 	require_once( CB_PLUGIN_ROOT . 'includes/CB_WPCli.php' );
// }

if ( is_admin() ) {
	if (
			(function_exists( 'wp_doing_ajax' ) && !wp_doing_ajax() ||
			(!defined( 'DOING_AJAX' ) || !DOING_AJAX ) )
	) {
		require_once( CB_PLUGIN_ROOT . 'admin/Commons_Booking_Admin.php' );
	}
}
*/

/*
function cb2_plugins_loaded() {
	if ( ! function_exists( 'qw_init_frontend' ) ) {
		require_once( CB_PLUGIN_ROOT . 'plugins/query-wrangler/query-wrangler.php' );
		if ( ! CB_Database::has_table( 'query_wrangler' ) ) {
			qw_query_wrangler_table();
			qw_query_override_terms_table();
		}
	}

	if ( ! function_exists( 'wpcf7_init' ) ) {
		require_once( CB_PLUGIN_ROOT . 'plugins/contact-form-7/wp-contact-form-7.php' );
		if ( ! CB_Database::has_table( 'contact_form_7' ) ) {
			wpcf7_install();
		}
		wpcf7(); // CF7 plugins_loaded hook
	}
}
*/

// Annesley new stuffs
// add_action( 'plugins_loaded', 'cb2_plugins_loaded' );
// require_once( CB_PLUGIN_ROOT . 'admin/includes/CB2_Metaboxes.php' );
// require_once(CB_PLUGIN_ROOT . 'includes/CB_Template.php');

