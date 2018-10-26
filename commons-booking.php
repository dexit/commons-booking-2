<?php
/**
 * @package   CommonsBooking2
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 *
 * Plugin Name:       CommonsBooking 2
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

define( 'CB2_VERSION', '2.0.0' );
define( 'CB2_TEXTDOMAIN', 'commons-booking-2' );
define( 'CB2_NAME', 'CommonsBooking 2' );
define( 'CB2_MENU_SLUG', 'cb2_menu');
/* Paths */
define( 'CB2_PLUGIN_ROOT', plugin_dir_path( __FILE__ ) );
define( 'CB2_PLUGIN_ABSOLUTE',  __FILE__  );
define( 'CB2_PLUGIN_URI',  plugin_dir_url( __FILE__ ) );


/* DB Tables @TODO – move to CB2_Database.php ? */
define( 'CB2_AVAILABILITY_OPTIONS_TABLE', 'cb2_availability_options' );
/* Load framework */
require_once(CB2_PLUGIN_ROOT . 'framework/CB2_framework.php');
/* Load public */
require_once(CB2_PLUGIN_ROOT . 'public/CB2_Public.php');
/* Load admin */
require_once(CB2_PLUGIN_ROOT . 'admin/WP_admin_integration.php');

if ( is_admin() ) {
	if (
			(function_exists( 'wp_doing_ajax' ) && !wp_doing_ajax() ||
			(!defined( 'DOING_AJAX' ) || !DOING_AJAX ) )
	) {
		require_once( CB2_PLUGIN_ROOT . 'admin/CB2_Admin.php' );
	}
}

/**
 * Load the textdomain of the plugin
 *
 * @return void
 */
function cb_load_plugin_textdomain()
{
	$locale = apply_filters('plugin_locale', get_locale(), CB2_TEXTDOMAIN);
	load_textdomain(CB2_TEXTDOMAIN, trailingslashit(WP_PLUGIN_DIR) . CB2_TEXTDOMAIN . '/languages/' . CB2_TEXTDOMAIN . '-' . $locale . '.mo');
}
add_action('plugins_loaded', 'cb_load_plugin_textdomain', 1);


//print( '<pre>' . CB2_DatabaseTable_PostNavigator::install_SQL() . '</pre>' );
//exit();
