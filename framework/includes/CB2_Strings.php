<?php
/**
 * Translateable string snippets.
 *
 * @package   CommonsBooking2
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */
class CB_Strings {
	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;
    /**
	 * array holding all strings
	 *
	 * @var object
	 */
  public static $cb_strings = array ();

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
				do_action( 'commons_booking_strings_failed', $err );
				if ( WP_DEBUG ) {
					throw $err->getMessage();
				}
			}
		}
		return self::$instance;
    }
	/**
	 * Initialize
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public static function initialize() {


		}
	/**
	 * Setup the interface strings
	 *
	 * @since 2.0.0
	 *
	 * @return string string
	 */
	public static function setup_strings() {
		$strings = array(
			'timeframes' => array (
				'not-defined' => __('No booking timeframes found, this item cannot be booked right now.', 'commons-booking')
        )
			);
		return $strings;
	}
	/**
	 * Retrieve a interface string, possibly overwritten by settings
	 *
	 * @since 2.0.0
	 *
	 * @param $category The string category
	 * @param $key 		Optional: The key
	 *
	 * @uses CB_Settings
	 *
	 * @return array|string string
	 */
	public static function get( $category='', $key = '' ) {

		$strings = self::setup_strings();

		// check that the string is in our pre-defined array
		if ( empty ($category) && empty ( $key ) ) { // return the whole array
			return $strings;
		} elseif ( ! empty ($category) && array_key_exists( $category, $strings ) ) { // else: query by cat/key
			if ( !empty ( $key ) && array_key_exists( $key, $strings[ $category ] ) ){

				$user_defined_string = CB_Settings::get( 'strings', $category . '_' . $key );

				if ( ! empty ( $user_defined_string ) ) {
					return $user_defined_string;
				} else {
					return $strings[ $category ][ $key ];
				}
			}
		}
	}

}
add_action( 'plugins_loaded', array( 'CB_Strings', 'get_instance' ) );
