<?php
/**
 * Plugin Shortcodes (OLD)
 *
 *
 * @package   CommonsBooking2
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */
class CB_Shortcodes extends CB_Object {

	public function __construct () {

		$this->do_setup();

	}
	/**
	 * Render a calendar (multiple timeframes on one calendar)
	 *
	 * @param array $atts
	 */
	public function calendar_shortcode ( $atts ) {
		/*
		These fields pass in a single value or a list of comma separated values. They need to be converted to an array before merging with the default args.
		*/

		$array_atts_fields = array(
			'timeframe_id', // DONE in CB_Object
			//'owner_id', // Does this make sense as an array? There can only be one owner
			'location_id', // DONE in CB_Object
			'item_id', // DONE in CB_Object
			//'location_cat', // Didn't touch SQL in CB_Object, it's complicated
			//'item_cat' // Didn't touch SQL in CB_Object, it's complicated
		);

		foreach($array_atts_fields as $field) {
			if (isset($atts[$field])) {
				$atts[$field] = explode(',', $atts[$field]);
			}
		}

		$args = shortcode_atts( $this->default_query_args, $atts, 'cb_calendar' );

		$this->set_context('calendar');
		$timeframes = $this->get_timeframes( $args );

		cb_get_template_part(  CB2_TEXTDOMAIN, 'calendar', 'timeframes', $timeframes );
	}
	/**
	 * Render timeframe(s)
	 *
	 * @param array $atts
	 */
	public function timeframe_shortcode ( $atts ) {

		$args = shortcode_atts( $this->default_query_args, $atts, 'cb_timeframe' );

		$timeframes = $this->get_timeframes( $args );

		cb_get_template_part(  CB2_TEXTDOMAIN, 'item', 'list', $timeframes );
	}

	/**
	 * Render timeframe(s)
	 *
	 * @param array $atts
	 */
	public function map_shortcode ( $atts ) {
		/*
		These fields pass in a single value or a list of comma separated values. They need to be converted to an array before merging with the default args.
		*/

		$array_atts_fields = array(
			'timeframe_id', // DONE in CB_Object
			//'owner_id', // Does this make sense as an array? There can only be one owner
			'location_id', // DONE in CB_Object
			'item_id', // DONE in CB_Object
			//'location_cat', // Didn't touch SQL in CB_Object, it's complicated
			//'item_cat' // Didn't touch SQL in CB_Object, it's complicated
		);

		foreach($array_atts_fields as $field) {
			if (isset($atts[$field])) {
				$atts[$field] = explode(',', $atts[$field]);
			}
		}

		$args = shortcode_atts( $this->default_query_args, $atts, 'cb_calendar' );

		$this->set_context('calendar');
		$timeframes = $this->get_timeframes( $args );

		cb_get_template_part(  CB2_TEXTDOMAIN, 'map', 'timeframes', $timeframes );
	}
}
