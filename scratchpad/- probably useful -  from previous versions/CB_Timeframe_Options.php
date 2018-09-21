<?php
/**
 * CB Timeframe Options
 *
 * Options for timeframes.
 * Overwrites the cmb2 save/retrieve functions.
 *
 * @package   Commons_Booking
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */
class CB_Timeframe_Options {
	/**
	 * Timeframe id
	 *
	 * @var object
	 */
	public $timeframe_id;
	/**
	 * Timeframe Options
	 *
	 * @var array
	 */
	public $timeframe_options;
	/**
	 * Constructor
	 */
	public function __construct( ) {

		global $wpdb;
		$this->timeframe_options_table = $wpdb->prefix . CB_TIMEFRAME_OPTIONS_TABLE;

	}
	/**
	 * Get the timeframe option, fall back on plugin setting if not available
	 *
	 * @param string $option_name
	 * @return string $value
	 *
	 * @uses CB_Settings
	 */
	public function set_timeframe_id( $timeframe_id='' ) {

		if ( isset ( $timeframe_id ) && cb_timeframe_exists( $timeframe_id ) ) { // make sure timeframe exists
			$this->timeframe_id = $timeframe_id;
		} else {
			echo ("timeframe not found"); //@TODO: use messages
		}

	}
	/**
	 * Get the timeframe options as an array for use with the cmb2 form, fall back on plugin setting if option is not set
	 *
	 * @return string $option
	 *
	 * @uses CB_Settings
	 */
	public function get_timeframe_option_cmb2_form( $data, $id, $arguments, $field ) {

		global $wpdb;

		// We only have the full option name (including the plugin settings prefix), so remove the prefix from the string so we can use CB_Settings::get the usual way. @TODO: Could be nicer.
		$slug = CB_Settings::get_plugin_settings_slug();
		$options_page = $arguments['id'];
		$option_group = str_replace ( $slug, '',  $options_page );

		// construct query
		$query = sprintf( "SELECT option_name, option_value
		FROM %s
		WHERE timeframe_id = %d AND option_name = '%s'"
		, $this->timeframe_options_table, $this->timeframe_id, $arguments['field_id'] ) ;

		$results =  $wpdb->get_results( $query, 'OBJECT' );

		if ( isset( $results[0]->option_value ) ) { // the option is set in the timeframe_options table

			$option = $results[0]->option_value;
			return $option;

		} else { // option not set, fall back on general plugin settings

			return CB_Settings::get( $option_group , $arguments['field_id'] );
		}

	}

	/**
	 * Get the timeframe options as an array, fall back on plugin setting if setting is not set
	 * expects $timeframe_id to be set
	 *
	 * @return array $result
	 *
	 * @uses CB_Settings
	 */
	public function get_timeframe_options( $timeframe_id ) {

		if ( ! cb_timeframe_exists ( $timeframe_id ) ) {
			return __('Timeframe id has not been set or timeframe does not exist.', 'commons-booking' );
		}

	$settings_options = CB_Settings::get_timeframe_option_group_fields();
		global $wpdb;

		// get data from the timeframe options table
		$query = sprintf( 'SELECT option_name, option_value
		FROM %s
		WHERE timeframe_id = %d
		', $this->timeframe_options_table, $timeframe_id ) ;

		$results =  $wpdb->get_results( $query, 'OBJECT' );

		$timeframe_options = array();

		if ( $results ) { // there are timeframe options configured
			$timeframe_options = array();
			foreach ($results as $result){
				$timeframe_options[$result->option_name] = $result->option_value;
			}
			$merged = shortcode_atts( $settings_options, $timeframe_options);
			return $merged ; // merge settings & options
		} else { // return the settings
			return $settings_options;
		}
	}
	/**
	 * Save the timeframe option to the db table
	 *
	 * @param string $data
	 * @return array $values
	 *
	 * @uses cmb2
	 */
	public function save_timeframe_option( $data, $values ) {

		global $wpdb;

		$option_name = $values['field_id'];

		//handle checkbox empty values
		if ( isset ( $values['value'] ) ) {
			$value = is_null( $values['value'] ) ? 0 : $values['value'];
		} else {
			$value = 0;
		}

		// get a previously saved option_id
		$option_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT option_id FROM " . $this->timeframe_options_table . "
										WHERE timeframe_id = %d AND option_name = %s LIMIT 1",
				$this->timeframe_id,
				$option_name
			)
		);

		if ( $option_id ) {
			$result = $wpdb->update(
				$this->timeframe_options_table,
				array(
					'timeframe_id' => $this->timeframe_id,
					'option_name' => $option_name,
					'option_value' => $value
				),
				array ( 'option_id' => $option_id ),
				array('%d', '%s', '%s'),
				array( '%d' )
			);
		} else {
			$result = $wpdb->replace(
				$this->timeframe_options_table,
					array(
						'timeframe_id' => $this->timeframe_id,
						'option_name' => $option_name,
						'option_value' => $value
					),
					array( '%d', '%s', '%s' )
				);
		}

		// do action
		do_action( 'commons_booking_cb_timeframe_option_updated', $result );
		return true;
	}


}
