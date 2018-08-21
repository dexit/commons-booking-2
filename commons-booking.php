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
define( 'CB_NAME', 'Commons Booking' );
define( 'CB_PLUGIN_ROOT', plugin_dir_path( __FILE__ ) );
define( 'CB_PLUGIN_ABSOLUTE',  __FILE__  );

define( 'CB_TIMEFRAMES_TABLE', 'cb2_timeframes' );
define( 'CB_TIMEFRAME_OPTIONS_TABLE', 'cb2_timeframe_options' );
define( 'CB_BOOKINGS_TABLE', 'cb2_bookings' );
define( 'CB_SETS_TABLE', 'cb2_sets' );
define( 'CB_SLOTS_TABLE', 'cb2_slots' );
define( 'CB_SLOTS_BOOKINGS_REL_TABLE', 'cb2_slots_bookings_relation' );
define( 'CB_SLOT_TEMPLATES_TABLE', 'cb2_slot_templates' );

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
/*
require_once( CB_PLUGIN_ROOT . 'composer/autoload.php' );
require_once( CB_PLUGIN_ROOT . 'includes/CB_PostTypes.php' );
require_once( CB_PLUGIN_ROOT . 'includes/CB_PostTypes_Metaboxes.php' );
require_once( CB_PLUGIN_ROOT . 'includes/CB_Helpers.php' );
require_once( CB_PLUGIN_ROOT . 'includes/CB_Gui.php' );
require_once( CB_PLUGIN_ROOT . 'includes/CB_Settings.php' );
*/
require_once( CB_PLUGIN_ROOT . 'public/Commons_Booking.php' );
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

// Annesley new stuffs
// add_action( 'plugins_loaded', 'cb2_plugins_loaded' );
require_once( CB_PLUGIN_ROOT . 'includes/CB_Template.php' );
require_once( CB_PLUGIN_ROOT . 'public/includes/CB_Query.php' ); // register_post_types()

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

function cb2_notification_bubble_in_admin_menu() {
  global $menu, $submenu;

  foreach ($menu as &$amenuitem) {
    if ( is_array($amenuitem) ) {
      $menuitem = &$amenuitem[0];
      if ( substr( $menuitem, -1 ) == ')' ) {
        $menuitem = preg_replace( '/\(([0-9]+)\)$/', '<span class="update-plugins count-$1"><span class="update-count">$1</span></span>', $menuitem );
      }
      else if ( substr( $menuitem, -1 ) == ']' ) {
        $menuitem = preg_replace( '/\[([0-9]+)\]$/', '<span class="menu-item-number count-$1">$1</span>', $menuitem );
      }
    }
  }

  foreach ($submenu as $menu_name => &$menuitems) {
    $first = TRUE;
    foreach ($menuitems as &$amenuitem) {
      if ( is_array($amenuitem) ) {
        $menuitem = &$amenuitem[0];
        if ( $first ) {
          $menuitem = preg_replace( '/\(([0-9]+)\)$|\[([0-9]+)\]$/', '', $menuitem );
        } else {
          if ( substr( $menuitem, -1 ) == ')' ) {
            $menuitem = preg_replace( '/\(([0-9]+)\)$/', '<span class="update-plugins count-$1"><span class="update-count">$1</span></span>', $menuitem );
          }
          else if ( substr( $menuitem, -1 ) == ']' ) {
            $menuitem = preg_replace( '/\[([0-9]+)\]$/', '<span class="menu-item-number count-$1">$1</span>', $menuitem );
          }
        }
      }
      $first = FALSE;
    }
  }
}
add_action('admin_menu', 'cb2_notification_bubble_in_admin_menu', 110 );

function cb2_admin_init_menus() {
	global $wpdb;

	$notifications_string = ' (3)';
  add_menu_page( 'CB2', "CB2$notifications_string", 'manage_options', 'cb2', 'cb2_options_page', 'dashicons-video-alt' );

	$pages = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}cb2_admin_pages", OBJECT_K );
	foreach ( $pages as $menu_slug => $details ) {
		$capability = $details->capability;
		if ( ! $capability ) $capability = 'manage_options';
		$parent_slug = $details->parent_slug;
		if ( ! $parent_slug ) $parent_slug = 'cb2';

		add_submenu_page( $parent_slug, $details->page_title, $details->menu_title, $capability, $menu_slug, 'cb2_settings_auto_page' );
	}
}
add_action( 'admin_menu', 'cb2_admin_init_menus' );

function cb2_options_page() {
	print('hello');
}

function cb2_settings_auto_page() {
	global $wpdb;

	if ( isset( $_GET[ 'page' ] ) ) {
		$page    = $_GET[ 'page' ];
		$typenow = NULL;

		// Bring stored parameters on to the query-string
		$details = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}cb2_admin_pages WHERE menu_slug = %s LIMIT 1",
			array( $page )
		), OBJECT_K );
		if ( count( $details ) ) {
			$details_page  = $details[$page];
			$wp_query_args = $details_page->wp_query_args;
			foreach ( explode( ',', $wp_query_args ) as $arg_detail_string ) {
				$arg_details   = explode( '=', $arg_detail_string, 2 );
				$name          = $arg_details[0];
				$value         = ( count( $arg_details ) > 1 ? $arg_details[1] : '' );
				$_GET[ $name ] = $value;
				if ( $name == 'post_type' ) $typenow = $value;
			}

			$screen = WP_Screen::get( $typenow );
			set_current_screen( $screen );
			require_once( get_home_path() . 'wp-admin/edit.php' );
		} else throw new Exception( 'CB2 admin page cannot find its location in the db' );
	} else throw new Exception( 'CB2 admin page does not understand its location. A querystring ?page= parameter is needed' );

	return TRUE;
}

