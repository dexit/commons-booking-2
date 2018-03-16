<?php
/**
 * CB_Codes
 *
 * @package   Commons_Booking
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */
/**
 * Interface for Codes
 */
class CB_Codes  {
	/**
	 * Array holding the codes
	 *
	 * @var array
	 */
	public $codes_array;
	/**
	 * Constructor
	 */
	public function __construct( ) {

		$this->get_codes_from_settings();

	}

	/**
	 * Get the codes string from settings, save as array
	 *
	 * @return array $codes_array
	 */
	public function get_codes_from_settings( ) {

		$codes_string = CB_Settings::get( 'codes', 'codes-pool');
		$this->codes_array = explode(',', $codes_string ); //@TODO validate

	}
	/**
	 * Get a random code from the codes pool
	 */
	public function get_random_code( ) {

		$count = count ( $this->codes_array );
		$random = rand ( 0 , $count - 1 );

		return esc_attr ( $this->codes_array[ $random ] );

	}

}
