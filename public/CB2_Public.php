<?php
/**
 * Public Commons Booking Class
 *
 * Include the necessary files for the front-end.
 *
 * @package   CommonsBooking2
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */
/**
 * This class should ideally be used to work with the public-facing side of the WordPress site.
 */
class CB2_Public {
	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	private static $instance;
	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public static function initialize() {

		require_once(CB2_PLUGIN_ROOT . 'public/includes/CB2_Shortcodes.php');
		require_once(CB2_PLUGIN_ROOT . 'public/includes/CB2_Enqueue.php');

		// require_once( CB2_PLUGIN_ROOT . 'public/widgets/sample.php' );
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
		if ( null === self::$instance ) {
			try {
				self::$instance = new self;
				self::initialize();
			} catch ( Exception $err ) {
				do_action( 'commons_booking_failed', $err );
				if ( WP_DEBUG ) {
					throw $err->getMessage();
				}
			}
		}
		return self::$instance;
	}
}
/*
 * @TODO:
 *
 * - 9999 is used for load the plugin as last for resolve some
 *   problems when the plugin use API of other plugins, remove
 *   if you don' want this
 */
add_action( 'plugins_loaded', array( 'CB2_Public', 'get_instance' ), 9999);
