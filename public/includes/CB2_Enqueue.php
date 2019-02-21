<?php
/**
 * Enqueues for the front end.
 *
 * Scripts, styles, etc.
 * Content filters: Overwrite items, location with templates
 *
 * @package   CommonsBooking2
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */
class CB2_Enqueue {
	/**
	 * Initialize the class
	 */
	public function initialize() {
		if ( !apply_filters( 'commons_booking_cb_enqueue_initialize', true ) ) {
			return;
		}
		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );

		add_shortcode( 'cb2_calendar',  array( 'CB2_Shortcodes', 'calendar_shortcode' ) ) ;
		add_shortcode( 'cb2_timeframe', array( 'CB2_Shortcodes', 'timeframe_shortcode' ) ) ;
		add_shortcode( 'cb2_map',       array( 'CB2_Shortcodes', 'map_shortcode' ) ) ;

		// create an API end point
		require_once(CB2_PLUGIN_ROOT . 'public/includes/CB2_API.php');
		$API = new CB2_API;

		// booking page
		require_once CB2_PLUGIN_ROOT . 'public/includes/CB2_Booking_Page.php';



	}
	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public static function enqueue_styles() {
		if ( !is_admin() ) { // prevent style from loading in admin
			wp_enqueue_style( CB2_TEXTDOMAIN . '-framework-styles', plugins_url( 'framework/assets/css/framework.css', CB2_PLUGIN_ABSOLUTE ), array(), CB2_VERSION );
			wp_enqueue_style( CB2_TEXTDOMAIN . '-plugin-styles', plugins_url( 'public/assets/css/public.css', CB2_PLUGIN_ABSOLUTE ), array(), CB2_VERSION );
		}
	}
	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public static function enqueue_scripts() {
		$min = ( WP_DEBUG ? '' : '.min' );
		wp_enqueue_script( CB2_TEXTDOMAIN . '-plugin-script', plugins_url( "public/assets/js/public$min.js", CB2_PLUGIN_ABSOLUTE ), array( 'jquery' ), CB2_VERSION );
	}
	/**
	 * Templates for cb_items and cb_locations.
	 *
	 * @since 2.0.0
	 *
	 * @return mixed html
	 *
	 * @see /templates
	 *
	 */
	public static function cb_template_chooser( $content ) {
		// items
		if ( is_post_type_archive( 'cb_item' ) && in_the_loop() ) {
			$args = array ( 'item_id' => get_the_id() );
			$timeframe_object = new CB2_Timeframes( $args );
			$CB2_Timeframes = $timeframe_object->get( );
			cb2_get_template_part(  CB2_TEXTDOMAIN, 'item', 'list', $CB2_Timeframes );
		} elseif ( is_singular( 'cb_item' ) && in_the_loop() ) {
			$args = array ( 'item_id' => get_the_id() );
			$timeframe_object = new CB2_Timeframes( $args );
			$CB2_Timeframes = $timeframe_object->get( );
			cb2_get_template_part(  CB2_TEXTDOMAIN, 'item', 'single', $CB2_Timeframes );
		// locations
		} elseif ( is_post_type_archive( 'cb_location' ) && in_the_loop() ) {
			$args = array ( 'location_id' => get_the_id() );
			$timeframe_object = new CB2_Timeframes( $args );
			$CB2_Timeframes = $timeframe_object->get( );
			cb2_get_template_part(  CB2_TEXTDOMAIN, 'location', 'list', $CB2_Timeframes );
		} elseif ( is_singular( 'cb_location') && in_the_loop() ) {
			$args = array ( 'location_id' => get_the_id() );
			$timeframe_object = new CB2_Timeframes( $args );
			$CB2_Timeframes = $timeframe_object->get( );
			cb2_get_template_part(  CB2_TEXTDOMAIN, 'location', 'single', $CB2_Timeframes );
		} else {
			return $content;
		}
	}

}
$cb2_enqueue = new CB2_Enqueue();
$cb2_enqueue->initialize();
do_action( 'cb2_enqueue_instance', $cb2_enqueue );
