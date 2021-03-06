<?php
/**
 * @package   CommonsBooking2
 * @author    The CommonsBooking Team <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 *
 * Plugin Name:       CommonsBooking 2
 * Plugin URI:        @TODO
 * Description:       @TODO
 * Version:           2.0.0
 * Author:            The CommonsBooking Team
 * Author URI:        http://commonsbooking.wielebenwir.de
 * Text Domain:       commons-booking
 * License:           GPL 2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * commons-booking:   v2.0.5
 */
// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
	die;
}

define( 'CB2_VERSION', '2.0.0' );
define( 'CB2_DEV_BUILD', '181120' );
define( 'CB2_TEXTDOMAIN', 'commons-booking-2' );
define( 'CB2_NAME', 'CommonsBooking 2' );
define( 'CB2_MENU_SLUG', 'cb2-menu');
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

/* Plugin de/activation */
require_once(CB2_PLUGIN_ROOT . 'public/includes/CB2_ActDeact.php');
register_activation_hook(CB2_PLUGIN_ROOT . '/commons-booking.php', array( 'CB2_ActDeact', 'activate' ));
register_deactivation_hook(CB2_PLUGIN_ROOT . '/commons-booking.php', array( 'CB2_ActDeact', 'deactivate' ));

/* Load dependent plugins */
require_once(CB2_PLUGIN_ROOT . 'plugins/geo-hcard-map/geo-hcard-map.php');

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
function cb2_load_plugin_textdomain()
{
	$locale = apply_filters('plugin_locale', get_locale(), CB2_TEXTDOMAIN);
	load_textdomain(CB2_TEXTDOMAIN, trailingslashit(WP_PLUGIN_DIR) . CB2_TEXTDOMAIN . '/languages/' . CB2_TEXTDOMAIN . '-' . $locale . '.mo');
}
add_action('plugins_loaded', 'cb2_load_plugin_textdomain', 1);

function cb2_body_class_WP_DEBUG( $classes ) {
	if ( WP_DEBUG ) {
		// admin_body_class sends a string, body_class sends an array!
		if ( is_string( $classes ) ) $classes .= ' cb2-WP_DEBUG-on';
		else if ( is_array( $classes ) ) array_push( $classes, 'cb2-WP_DEBUG-on' );
	}
	if ( CB2_DEBUG_SAVE ) {
		// admin_body_class sends a string, body_class sends an array!
		if ( is_string( $classes ) ) $classes .= ' cb2-CB2_DEBUG_SAVE-on';
		else if ( is_array( $classes ) ) array_push( $classes, 'cb2-CB2_DEBUG_SAVE-on' );
	}
	return $classes;
}
add_filter( 'body_class',       'cb2_body_class_WP_DEBUG' );
add_filter( 'admin_body_class', 'cb2_body_class_WP_DEBUG' );

if ( WP_DEBUG && $_SERVER['HTTP_HOST'] == 'kozteherbringa.hu' ) {
	function cb2_wp_get_attachment_image_src( $image, Int $attachment_id, $size, Bool $icon ) {
		// TODO: remove kozteherbringa specific thing wp_get_attachment_image_src
		if ( is_array( $image ) && count( $image ) ) {
			$image[0] = str_replace( '.localhost', '.hu', $image[0] );
		}
		return $image;
	}
	add_filter( 'wp_get_attachment_image_src', 'cb2_wp_get_attachment_image_src', 10, 4 );
}
