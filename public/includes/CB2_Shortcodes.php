<?php
/**
 *
 * @package   CommonsBooking2
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */
/**
 * This class contains the shortcode for map and calendar
 */
class CB2_Shortcodes {
	public static function calendar_shortcode ( $atts ) {
		global $post;

		$default_atts = array(
			'display-strategy' => 'CB2_AllItemAvailability',
			'schema-type'      => CB2_Week::$static_post_type,
			'context'          => 'list',
			'template-type'    => 'available',
		);

		// ------------------------------------- Query
		$args = shortcode_atts( $default_atts, $atts, 'cb_calendar' );
		if ( ! is_array( $atts ) ) $atts = array( $atts );
		$args             = array_merge( $atts, $args, $_REQUEST );
		$display_strategy = CB2_PeriodInteractionStrategy::factory_from_args( $args );
		if ( WP_DEBUG && FALSE ) krumo( $display_strategy );

		// ------------------------------------- CSS and INPUT all args
		$css_classes = '';
		foreach ( $args as $name => $value ) {
			$name = str_replace( '_', '-', $name );
			if ( $value ) $css_classes .= "cb2-$name-$value "; // cb2-selection-mode-range ...
		}
		$html = "<div class='cb2-selection-container $css_classes'>";
		// Send all input arguments through in the form
		// this allows, for example, selection_mode to be understood by the submission PHP
		// namespace these in case their are multiple calendars in 1 page?
		$namespace_args   = CB2_Query::isset( $args, 'namespace_args' );
		foreach ( $args as $name => $value ) {
			$name = str_replace( '-', '_', $name );
			if ( $namespace_args ) $name = "$namespace_args-$name";
			$html .= "<input type='hidden' name='$name' value='$value'/>";
		}

		// ------------------------------------- Output
		$startdate = new CB2_DateTime( $args['startdate'] );
		$enddate   = new CB2_DateTime( $args['enddate'] );
		$the_calendar_pager = CB2::get_the_calendar_pager( $startdate, $enddate );
		$html .= $the_calendar_pager;
		$html .= '<div class="cb2-calendar">';
		$html .= CB2::get_the_calendar_header( $display_strategy );
		$html .= '<ul class="cb2-subposts">';
		$html .= CB2::get_the_inner_loop( $args, $display_strategy, $args['context'], $args['template_type'] );
		$html .= '</ul>';
		$html .= CB2::get_the_calendar_footer( $display_strategy );
		$html .= '</div>';
		$html .= $the_calendar_pager;

		$html .= '</div>';

		return $html;
	}

	public static function map_shortcode ( $atts ) {
		global $post;

		$default_atts = array(
			'display-strategy' => 'CB2_AllItemAvailability',
			'schema-type'      => CB2_Location::$static_post_type,
			'context'          => 'hcard',
			'template-type'    => '',
		);

		// ------------------------------------- Query
		$args = shortcode_atts( $default_atts, $atts, 'cb_map' );
		if ( ! is_array( $atts ) ) $atts = array( $atts );
		$args             = array_merge( $atts, $args, $_REQUEST );
		$display_strategy = CB2_PeriodInteractionStrategy::factory_from_args( $args );
		if ( WP_DEBUG ) krumo( $display_strategy );

		// ------------------------------------- CSS and INPUT all args
		$css_classes = '';
		foreach ( $args as $name => $value ) {
			$name = str_replace( '_', '-', $name );
			if ( $value ) $css_classes .= "cb2-$name-$value "; // cb2-selection-mode-range ...
		}
		$html = "<div class='cb2-selection-container $css_classes'>";
		// Send all input arguments through in the form
		// this allows, for example, selection_mode to be understood by the submission PHP
		// namespace these in case their are multiple calendars in 1 page?
		$namespace_args   = CB2_Query::isset( $args, 'namespace_args' );
		foreach ( $args as $name => $value ) {
			$name = str_replace( '-', '_', $name );
			if ( $namespace_args ) $name = "$namespace_args-$name";
			$html .= "<input type='hidden' name='$name' value='$value'/>";
		}

		// Query and display the map
		$html .= '<ul class="cb2-subposts">';
		$html .= CB2::get_the_inner_loop( $args, $display_strategy, $args['context'], $args['template_type'] );
		$html .= '</ul>';
		$html .= geo_hcard_map_shortcode_handler( NULL );
		$html .= '</div>';

		return $html;
	}
}
