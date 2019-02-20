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

	private $default_query_args = array(
		// TODO: Entities
		'period-group-ID'  => FALSE,
		'location-ID'      => FALSE,
		'item-ID'          => FALSE,
		'user-ID'          => FALSE,

		// Settings
		'start-date'       => '',
		'end-date'         => '',
		'view-mode'        => NULL,    // Reorganise the posts in to a week hierarchy
		'display-strategy' => NULL,
		'selection-mode'   => 'none',  // e.g. range
		'context'          => NULL,    // => templates/list-week-available.php, week = post_type
		'template-type'    => NULL,    // => templates/list-week-available.php, week = post_type

		'namespace-args'   => '',      // form <input @name=<namespace_args>-<argname>
	);

	/**
	 * Render a calendar (multiple periods on one calendar)
	 *
	 * @param array $atts
	 */
	public function calendar_shortcode ( $atts ) {
		global $post;

		// These fields pass in a single value or a list of comma separated values.
		// they need to be converted to an array before merging with the default args.
		$array_atts_fields = array(
			'period-group-ID',
			'location-ID',
			'item-ID',
			'user-ID',

			'start-date',
			'end-date',
			'view-mode',
			'display-strategy',
			'selection-mode',
			'context',
			'template-type',

			'namespace-args',
		);

		$args = shortcode_atts( $this->default_query_args, $atts, 'cb_calendar' );
		if ( ! is_array( $atts ) ) $atts = array( $atts );
		$template_args   = array_merge( $atts, $args );
		$period_group_ID = ( isset( $args['period-group-ID'] ) ? $args['period-group-ID'] : NULL );
		$location_ID     = ( isset( $args['location-ID'] )     ? $args['location-ID']     : NULL );
		$item_ID         = ( isset( $args['item-ID'] )         ? $args['item-ID']         : NULL );
		$user_ID         = ( isset( $args['user-ID'] )         ? $args['user-ID']         : NULL );
		$startdate       = ( isset( $args['start-date'] )      ? $args['start-date']      : NULL );
		$enddate         = ( isset( $args['end-date'] )        ? $args['end-date']        : NULL );
		$view_mode       = ( isset( $args['view-mode'] )       ? $args['view-mode']       : 'week' );
		$display_strategy_classname = ( isset( $args['display-strategy'] ) ? $args['display-strategy'] : 'CB2_Everything' );
		$selection_mode  = ( isset( $args['selection-mode'] )  ? $args['selection-mode']  : 'none' );
		$context         = ( isset( $args['context'] )         ? $args['context']         : 'list' );
		$template_type   = ( isset( $args['template-type'] )   ? $args['template-type']   : 'available' );
		$namespace_args  = ( isset( $args['namespace-args'] )  ? $args['namespace-args']  : '' );

		$startdate = ( $startdate ? new CB2_DateTime( $startdate ) : NULL );
		$enddate   = ( $enddate   ? new CB2_DateTime( $enddate )   : NULL );

		// TODO: Implement shortcode Entity args
		if ( $period_group_ID || $location_ID || $item_ID || $user_ID )
			throw new Exception( 'Entity args not implemented yet' );
		// TODO: Implement variable Display Strategies in shortcode
		if ( $display_strategy_classname != 'CB2_SingleItemAvailability' )
			throw new Exception( "[$display_strategy_classname] Display Strategy not implemented yet. Please set to CB2_SingleItemAvailability" );

		// CSS all args
		$css_classes = '';
		foreach ( $args as $name => $value ) {
			if ( $value ) $css_classes .= "cb2-$name-$value "; // cb2-selection-mode-range ...
		}
		$html = "<div class='cb2-selection-container $css_classes'>";

		// Send all input arguments through in the form
		// this allows, for example, selection_mode to be understood by the submission PHP
		// namespace these in case their are multiple calendars in 1 page?
		foreach ( $args as $name => $value ) {
			$name = str_replace( '-', '_', $name );
			if ( $namespace_args ) $name = "$namespace_args-$name";
			$html .= "<input type='hidden' name='$name' value='$value'/>";
		}

		// Query and display the calendar
		// TODO: Move to $display_strategy_classname::factory( $args )
		$display_strategy = new $display_strategy_classname( $post, $startdate, $enddate, $view_mode );
		// if ( WP_DEBUG ) krumo( $display_strategy );
		$the_calendar_pager = CB2::get_the_calendar_pager( $startdate, $enddate );
		$html .= $the_calendar_pager;
		$html .= '<div class="cb2-calendar">';
		$html .= CB2::get_the_calendar_header( $display_strategy );
		$html .= '<ul class="cb2-subposts">';
		$html .= CB2::get_the_inner_loop( $template_args, $display_strategy, $context, $template_type );
		$html .= '</ul>';
		$html .= CB2::get_the_calendar_footer( $display_strategy );
		$html .= '</div>';
		$html .= $the_calendar_pager;

		$html .= '</div>';

		return $html;
	}

	public function map_shortcode ( $atts ) {
		global $post;

		// These fields pass in a single value or a list of comma separated values. They need to be converted to an array before merging with the default args.
		$array_atts_fields = array(
			'period-group-ID',
			'location-ID',
			'item-ID',
			'user-ID',

			'start-date',
			'end-date',
			'view-mode',
			'display-strategy',
			'selection-mode',
			'context',
			'template-type',

			'namespace-args',
		);
		$args = shortcode_atts( $this->default_query_args, $atts, 'cb_map' );
		if ( ! is_array( $atts ) ) $atts = array( $atts );
		$template_args   = array_merge( $atts, $args );
		$period_group_ID = ( isset( $args['period-group-ID'] ) ? $args['period-group-ID'] : NULL );
		$location_ID     = ( isset( $args['location-ID'] )     ? $args['location-ID']     : NULL );
		$item_ID         = ( isset( $args['item-ID'] )         ? $args['item-ID']         : NULL );
		$user_ID         = ( isset( $args['user-ID'] )         ? $args['user-ID']         : NULL );
		$startdate       = ( isset( $args['start-date'] )      ? $args['start-date']      : NULL );
		$enddate         = ( isset( $args['end-date'] )        ? $args['end-date']        : NULL );
		$view_mode       = ( isset( $args['view-mode'] )       ? $args['view-mode']       : 'location' );
		$display_strategy_classname = ( isset( $args['display-strategy'] ) ? $args['display-strategy'] : 'CB2_AllItemAvailability' );
		$selection_mode  = ( isset( $args['selection-mode'] )  ? $args['selection-mode']  : 'none' );
		$context         = ( isset( $args['context'] )         ? $args['context']         : 'hcard' );
		$template_type   = ( isset( $args['template-type'] )   ? $args['template-type']   : '' );
		$namespace_args  = ( isset( $args['namespace-args'] )  ? $args['namespace-args']  : '' );

		$startdate = ( $startdate ? new CB2_DateTime( $startdate ) : NULL );
		$enddate   = ( $enddate   ? new CB2_DateTime( $enddate )   : NULL );

		// TODO: Implement shortcode Entity args
		if ( $period_group_ID || $location_ID || $item_ID || $user_ID )
			throw new Exception( 'Entity args not implemented yet' );
		// TODO: Implement variable Display Strategies in shortcode
		if ( $display_strategy_classname != 'CB2_AllItemAvailability' )
			throw new Exception( "[$display_strategy_classname] Display Strategy not implemented yet. Please set to CB2_SingleItemAvailability" );

		// CSS all args
		$css_classes = '';
		foreach ( $args as $name => $value ) {
			if ( $value ) $css_classes .= "cb2-$name-$value "; // cb2-selection-mode-range ...
		}
		$html = "<div class='cb2-selection-container $css_classes'>";

		// Send all input arguments through in the form
		// this allows, for example, selection_mode to be understood by the submission PHP
		// namespace these in case their are multiple calendars in 1 page?
		foreach ( $args as $name => $value ) {
			$name = str_replace( '-', '_', $name );
			if ( $namespace_args ) $name = "$namespace_args-$name";
			$html .= "<input type='hidden' name='$name' value='$value'/>";
		}

		// Query and display the map
		// TODO: Move to $display_strategy_classname::factory( $args )
		$display_strategy = new $display_strategy_classname( $startdate, $enddate, $view_mode );
		//if ( WP_DEBUG ) krumo( $display_strategy );
		$html .= '<ul class="cb2-subposts">';
		$html .= CB2::get_the_inner_loop( $template_args, $display_strategy, $context, $template_type );
		$html .= '</ul>';
		$html .= geo_hcard_map_shortcode_handler( NULL );
		$html .= '</div>';

		return $html;
	}
}
