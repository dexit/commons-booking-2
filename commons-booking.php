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

/* Plugin de/activation */
require_once(CB2_PLUGIN_ROOT . 'public/includes/CB2_ActDeact.php');
register_activation_hook(CB2_PLUGIN_ROOT . '/commons-booking.php', array( 'CB2_ActDeact', 'activate' ));
register_deactivation_hook(CB2_PLUGIN_ROOT . '/commons-booking.php', array( 'CB2_ActDeact', 'deactivate' ));



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
	return $classes;
}
add_filter( 'body_class',       'cb2_body_class_WP_DEBUG' );
add_filter( 'admin_body_class', 'cb2_body_class_WP_DEBUG' );

// TODO: remove this debug checking code
if ( WP_DEBUG ) {
	function cb2_save_post_periodent_user_booked_example( $post_id, $post ) {
		// post_status is always published here
		krumo( 'cb2_save_post_periodent_user_booked_example', $post );
	}
	// Custom CB2 action save_post_{post_type}_{period_status}
	add_action( 'save_post_periodent-user_booked', 'cb2_save_post_periodent_user_booked_example', 10, 2 );

	function cb2_save_post_booked( $cb2_post ) {
		$Class = get_class( $cb2_post );
		print( "<div class='cb2-WP_DEBUG-small'>{$Class}[$cb2_post->ID] fired event [cb2_save_post_booked]</div>" );
	}
	add_action( 'cb2_save_post_booked', 'cb2_save_post_booked' );

	function cb2_save_post_example( $post_id, $post ) {
		// Any post_status including auto-draft
		if ( property_exists( $post, 'post_status' ) && $post->post_status == 'publish' && $post->post_type == 'periodent-location' ) {
			$cb2_post = CB2_Query::ensure_correct_class( $post );
			krumo( 'cb2_save_post_periodent_location_debug', $cb2_post );
		}
	}
	// WordPress action save_post
	add_action( 'save_post', 'cb2_save_post_example', 1000, 2 );
}
