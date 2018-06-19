<?php
/**
 * Locations
 *
 * @package   Commons_Booking
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */
/**
 * Interface for Locations
 */
class CB_Locations  {

	public $location_id;
	/**
	* Constructor
  */
	public function __construct( $location_id ) {
		$this->location_id = $location_id;
	}
	/**
	* Return true if opening hours set
	* @param int $location_id
	* @return bool
  */
	public function has_opening_times( ) {

		$meta_val = get_post_meta( $this->location_id, 'location-has-opening-times', true );

		return cb_checkbox_bool ($meta_val );
	}
	/**
	* Return pickup mode
	* @param int $location_id
	* @return string
  */
	public function get_pickup_mode( ) {

		$meta_val = get_post_meta( $this->location_id, 'location-pickup-mode', true );

		$mode = ( empty( $meta_val ) ) ? 'personal_contact' : $meta_val;

		return $mode;
	}
	/**
	* Return Lat/Long
	* @return array geocoords
  */
	public function get_lat_long( ) {

		$geocoords = array (
			'long'=> get_post_meta( $this->location_id, 'location-address-longitude', true ),
			'lat'=> get_post_meta( $this->location_id, 'location-address-latitude', true )
		);
		return $geocoords;
	}
	/**
	 * Return Address formatted
	 * @uses CB_Settings
	 * @return string $adress_formatted address formatted according to plugin settings
	 */
	public function get_address_formatted( ) {

		// @TODO:

		return $adress_formatted;
	}
	/**
	* Return an array of open days & times
	* @param int $location_id
	* @return array $opening_times
  */
	public function get_opening_times( ) {

		$opening_times = array();
		$location_id = $this->location_id;

		$weekdays = array (
			'sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'
		);

			foreach ( $weekdays as $weekday ) { // loop through days

				$day_open = get_post_meta( $location_id, 'location-open-' . $weekday, true );

				if ( cb_checkbox_bool( $day_open ) ) {
					$day_numeric = date ('w', strtotime ($weekday));

					$opening_times[ $day_numeric ] = array (
						'from' =>
						get_post_meta( $location_id, 'location-open-' . $weekday . '-from', true ),
						'till' =>
						get_post_meta( $location_id, 'location-open-' . $weekday . '-til', true )
					);
				}
			}
		// }
		return $opening_times;
	}
}
