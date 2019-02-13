<?php
/**
 * Translateable string snippets.
 *
 * Allows admins to change frontend strings in settings
 * without editing the plugin code.
 *
 * Text with placholder example:
 *  echo CB2_Strings::get('general', 'test-variable', 'i am replacing this');
 *
 *
 *
 * @package   CommonsBooking2
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */
class CB2_Strings {
	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;
    /**
	 * array holding all strings
	 *
	 * @var array
	 */
  public static $strings = array ();
   /**
	 * placholder
	 *
	 * @var string
	 */
  public static $placeholder = '%%';

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

		self::$strings = array(
			'nomenclature' => array(
				'item-singular' => __('item', 'commons-booking-2'),
				'item-plural' => __('items', 'commons-booking-2'),
				'location-singular' => __('location', 'commons-booking-2'),
				'location-plural' => __('locations', 'commons-booking-2'),
				'booking-singular' => __('booking', 'commons-booking-2'),
				'booking-plural' => __('bookings', 'commons-booking-2'),
			),
			'general' => array(
				'not-defined' => __('No booking timeframes found, this item cannot be booked right now.', 'commons-booking-2'),
				'general-error' => __('Something went wrong.', 'commons-booking-2'),
				'test-variable' => __('This contains a placeholder, the string "%%".', 'commons-booking-2'),
			)
		);


		}
	/**
	 * Retrieve a interface string
	 *
	 * Can be replaced by user defined string via plugin settings->strings
	 * Support for placeholders
	 *
	 * @since 2.0.0
	 *
	 * @param string 	$category The string category
	 * @param string 	$key 		 The key
	 * @param string 	$replace
	 *
	 * @uses CB2_Settings
	 *
	 * @return array|string string
	 */
	public static function get( $category='', $key = '', $replace='' ) {

		if ( empty ($category) && empty ( $key ) ) { // return the whole array
			return self::$strings;
		} elseif ( ! empty ($category) && array_key_exists( $category, self::$strings ) ) { // else: query by cat/key
			if ( !empty ( $key ) && array_key_exists( $key, self::$strings[ $category ] ) ) { // exists in array

				$user_defined_string = CB2_Settings::get( 'strings', $category . '_' . $key ); // check for string overwrite in settings

				if ( ! empty ( $user_defined_string ) ) { // user definition is not empty
					$string =  $user_defined_string;
				} else {
					$string = self::$strings[ $category ][ $key ];
				}

				if ( ! empty( $replace ) ) { // we are replacing placeholder
					return str_replace( self::$placeholder, $replace, $string );
				} else {
					return $string;
				}
			}
		}
	}


}
add_action( 'plugins_loaded', array( 'CB2_Strings', 'get_instance' ) );
