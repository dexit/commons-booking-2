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
		if ( ! is_admin() ) { // prevent style from loading in admin
			wp_enqueue_style( CB2_TEXTDOMAIN . '-framework-styles', plugins_url( 'framework/assets/css/framework.css', CB2_PLUGIN_ABSOLUTE ), array(), CB2_VERSION );
			wp_enqueue_style( CB2_TEXTDOMAIN . '-plugin-styles',    plugins_url( 'public/assets/css/public.css',       CB2_PLUGIN_ABSOLUTE ), array(), CB2_VERSION );
			$queue_legacy_styles = get_option( CB2_TEXTDOMAIN . '-include-legacy-css', TRUE );
			if ( $queue_legacy_styles ) {
				// New list templates conform to CB1 classes and layout
				// the calendar has been completely redesigned
				$theme_name = self::get_legacy_setting( 'pages', 'theme_select');
				if ( empty ( $theme_name ) ) $theme_name = 'standard';
				$url = plugins_url( "public/assets/css/legacy/css/themes/$theme_name/$theme_name.css", CB2_PLUGIN_ABSOLUTE );
				wp_enqueue_style( CB2_TEXTDOMAIN . '-plugin-legacy-themes', $url , array(), CB2_VERSION );
				wp_enqueue_style( CB2_TEXTDOMAIN . '-plugin-legacy-styles', plugins_url( 'public/assets/css/legacy/css/public.css', CB2_PLUGIN_ABSOLUTE ), array(), CB2_VERSION );
			}
		}
	}

  public static function get_legacy_setting( $setting_page, $setting_name = '' ) {
		// Copied and rationalised from CB1
		$prefix  = 'commons-booking';
		$page    = get_option( "$prefix-settings-$setting_page" );
		return CB2_Query::isset( $page, "{$prefix}_$setting_name", '' );
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
		CB2_Settings::localize();
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
