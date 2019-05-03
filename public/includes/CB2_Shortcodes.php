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
	function __construct() {
		// All public methods are shortcodes
		$Class   = get_class();
		$class   = new ReflectionClass( $Class );
		$methods = $class->getMethods( ReflectionMethod::IS_PUBLIC );
		foreach ( $methods as $method )
			add_shortcode( "cb2_$method->name", array( $Class, $method->name ) );
	}

	// ------------------------------------------------------------------------------------
	// Private helpers
	// ------------------------------------------------------------------------------------
	protected static function attribute_is_true( Array $args, String $name ) {
		$value = ( isset( $args[$name] ) ? $args[$name] : NULL );
		return ( $value == '1'
					|| $value == 'yes'
					|| $value == 'true'
					|| $value == 'on'
					|| $value == 'enable'
		);
	}

	protected static function attribute_is_false( Array $args, String $name ) {
		$value = ( isset( $args[$name] ) ? $args[$name] : NULL );
		return ( $value == '0'
					|| $value == 'no'
					|| $value == 'false'
					|| $value == 'off'
					|| $value == 'disable'
		);
	}

	protected static function json_args( &$value, String $key ) {
		if ( is_object( $value ) && ! method_exists( $value, '__toString' ) )
			$value = get_class( $value );
		else if ( is_array( $value ) )
			array_walk( $value, array( get_class(), 'json_args' ) );
		else
			$value = strval( $value );
	}

	protected static function container_element( Array $args, String $element = 'div', Array $css_classes = array() ) {
		// ------------------------------------- localize all args
		$calendar_name    = CB2_Query::isset( $args, 'name', 'main' );
		$script_handle    = CB2_TEXTDOMAIN . "-calendar-settings-$calendar_name";
		$calendar_name_js = 'cb2_settings_calendar_' . preg_replace( '/[^a-zA-Z0-9]/', '_', $calendar_name );
		array_walk( $args, array( get_class(), 'json_args' ) );
		wp_register_script( $script_handle, plugins_url( "public/assets/js/settings.js", CB2_PLUGIN_ABSOLUTE ) );
		wp_enqueue_script(  $script_handle );
		wp_localize_script( $script_handle, $calendar_name_js, $args );

		// ------------------------------------- CSS and INPUT all args
		foreach ( $args as $name => $value ) {
			switch ( $name ) {
				case 'startdate':
				case 'enddate':
					break;
				default:
					if ( is_null( $value ) )    $value = 'null';
					else if ( empty( $value ) ) $value = 'empty';
					$name  = preg_replace( '/[^a-z0-9]/', '-', strtolower( $name  ) );
					$value = preg_replace( '/[^a-z0-9]/', '-', strtolower( $value ) );
					array_push( $css_classes, "cb2-$name-$value" ); // cb2-selection-mode-range ...
			}
		}
		$css_classes_string = implode( ' ', $css_classes );
		$html = "<$element class='cb2-selection-container cb2-content $css_classes_string $calendar_name_js'>";
		// Send all input arguments through in the form
		// this allows, for example, selection_mode to be understood by the submission PHP
		// namespace these in case their are multiple calendars in 1 page?
		$namespace_args   = CB2_Query::isset( $args, 'namespace_args' );
		foreach ( $args as $name => $value ) {
			$name  = preg_replace( '/[^a-z0-9]/', '-', strtolower( $name  ) );
			if ( $namespace_args ) $name = "$namespace_args-$name";
			$value = str_replace( "'", '&apos;', $value );
			$html .= "<input type='hidden' name='$name' value='$value'/>";
		}
		return $html;
	}

	// ------------------------------------------------------------------------------------
	// General shortcodes
	// ------------------------------------------------------------------------------------
	public static function calendar( $atts = '', String $content = '', String $tag = '', Array $passed_default_atts = array() ) {
		global $post;

		$default_atts = array(
			'display-strategy' => 'CB2_AllItemAvailability',
			'schema-type'      => CB2_Week::$static_post_type,
			'template-type'    => 'available',
		);

		// ------------------------------------- Query
		$args = shortcode_atts( array_merge( $default_atts, $passed_default_atts ), $atts, 'cb2_calendar' );
		if ( ! is_array( $atts ) ) $atts = array( $atts );
		$args             = array_merge( $atts, $args, $_REQUEST );

		$display_strategy = CB2_PeriodInteractionStrategy::factory_from_args( $args, $post );
		$context          = CB2_Query::isset( $args, 'context' );
		$template_type    = CB2_Query::isset( $args, 'template_type' );

		// ------------------------------------- Output
		$startdate = new CB2_DateTime( $args['startdate'] );
		$enddate   = new CB2_DateTime( $args['enddate'] );
		$the_calendar_pager = CB2::get_the_calendar_pager( $startdate, $enddate );
		$html  = self::container_element( $args );
		$html .= $the_calendar_pager;
		$html .= '<div class="cb2-calendar">';
		$html .= CB2::get_the_calendar_header( $display_strategy );
		$html .= '<ul class="cb2-subposts">';
		$html .= CB2::get_the_inner_loop( $args, $display_strategy, $context, $template_type );
		$html .= '</ul>';
		$html .= CB2::get_the_calendar_footer( $display_strategy );
		$html .= '</div>';
		$html .= $the_calendar_pager;

		$html .= '</div>';

		return $html;
	}

	public static function map( $atts = '', String $content = '', String $tag = '', Array $passed_default_atts = array() ) {
		global $post;

		$default_atts = array(
			'display-strategy' => 'CB2_AllItemAvailability',
			'schema-type'      => CB2_Location::$static_post_type,
			'context'          => 'hcard',
			'date'             => 'day_start',
		);

		// ------------------------------------- Query
		$args = shortcode_atts( array_merge( $default_atts, $passed_default_atts ), $atts, 'cb2_map' );
		if ( ! is_array( $atts ) ) $atts = array( $atts );
		$args             = array_merge( $atts, $args, $_REQUEST );
		$make_request_off = self::attribute_is_false( $args, 'make-request' );
		$context          = CB2_Query::isset( $args, 'context' );
		$template_type    = CB2_Query::isset( $args, 'template_type' );
		$display_strategy = NULL;
		if ( ! $make_request_off )
			$display_strategy = CB2_PeriodInteractionStrategy::factory_from_args( $args, $post );

		// Query and display the map
		$html  = self::container_element( $args );
		if ( $display_strategy ) {
			$html .= '<ul class="cb2-subposts">';
			$html .= CB2::get_the_inner_loop( $args, $display_strategy, $context, $template_type );
			$html .= '</ul>';
		}
		$html .= geo_hcard_map_shortcode_handler( NULL );
		$html .= '</div>';

		return $html;
	}

	public static function list( $atts = '', String $content = '', String $tag = '', Array $passed_default_atts = array() ) {
		global $post;

		$default_atts = array(
			// Let us default to base on availability
			// whatever is being listed
			// e.g. Locations with no available items will not be shown
			'display-strategy' => 'CB2_AllItemAvailability',
		);

		// ------------------------------------- Query
		$args = shortcode_atts( array_merge( $default_atts, $passed_default_atts ), $atts, 'cb2_list' );
		if ( ! is_array( $atts ) ) $atts = array( $atts );
		$args             = array_merge( $atts, $args, $_REQUEST );
		$display_strategy = CB2_PeriodInteractionStrategy::factory_from_args( $args, $post );
		$context          = CB2_Query::isset( $args, 'context' );
		$template_type    = CB2_Query::isset( $args, 'template_type' );

		// Query and display the map
		$html  = self::container_element( $args );
		$html .= '<ul class="cb2-subposts">';
		$html .= CB2::get_the_inner_loop( $args, $display_strategy, $context, $template_type );
		$html .= '</ul>';
		$html .= '</div>';

		return $html;
	}

	// ------------------------------------------------------------------------------------
	// Specific shortcode helpers
	// ------------------------------------------------------------------------------------
	public static function items( $atts = '', String $content = '', String $tag = '', Array $passed_default_atts = array() ) {
		// Items archive
		global $post;

		$default_atts = array(
			'display-strategy' => 'CB2_AllItemAvailability',
			'schema-type'      => CB2_Item::$static_post_type,
			'date'             => 'day_start',
			'template-type'    => 'withlocation',
		);

		// ------------------------------------- Query
		$args = shortcode_atts( array_merge( $default_atts, $passed_default_atts ), $atts, 'cb2_items' );
		if ( ! is_array( $atts ) ) $atts = array( $atts );
		$args             = array_merge( $atts, $args, $_REQUEST );
		$display_strategy = CB2_PeriodInteractionStrategy::factory_from_args( $args, $post );
		$context          = CB2_Query::isset( $args, 'context' );
		$template_type    = CB2_Query::isset( $args, 'template_type' );
		if ( WP_DEBUG && TRUE ) krumo( $display_strategy );

		// Query and display the list
		$html  = self::container_element( $args );
		$html .= '<ul class="cb2-subposts">';
		$html .= CB2::get_the_inner_loop( $args, $display_strategy, $context, $template_type );
		$html .= '</ul>';
		$html .= '</div>';

		return $html;
	}

	public static function locations( $atts = '', String $content = '', String $tag = '', Array $passed_default_atts = array() ) {
		// Items archive
		global $post;

		$default_atts = array(
			'display-strategy' => 'CB2_AllItemAvailability',
			'schema-type'      => CB2_Location::$static_post_type,
			'date'             => 'day_start',
			'template-type'    => 'withitems',
		);

		// ------------------------------------- Query
		$args = shortcode_atts( array_merge( $default_atts, $passed_default_atts ), $atts, 'cb2_items' );
		if ( ! is_array( $atts ) ) $atts = array( $atts );
		$args             = array_merge( $atts, $args, $_REQUEST );
		$display_strategy = CB2_PeriodInteractionStrategy::factory_from_args( $args, $post );
		$context          = CB2_Query::isset( $args, 'context' );
		$template_type    = CB2_Query::isset( $args, 'template_type' );

		// Query and display the list
		$html  = self::container_element( $args );
		$html .= '<ul class="cb2-subposts">';
		$html .= CB2::get_the_inner_loop( $args, $display_strategy, $context, $template_type );
		$html .= '</ul>';
		$html .= '</div>';

		return $html;
	}

	public static function item_current_location( $atts = '', String $content = '', String $tag = '', Array $passed_default_atts = array() ) {
		// For a given item, show its current location
		// CB2_SingleItemLocationAvailability requires an item
		global $post;

		$default_atts = array(
			'display-strategy' => 'CB2_SingleItemLocationAvailability',
		);

		// ------------------------------------- Query
		$args = shortcode_atts( array_merge( $default_atts, $passed_default_atts ), $atts, 'cb2_current_location' );
		if ( ! is_array( $atts ) ) $atts = array( $atts );
		$args             = array_merge( $atts, $args, $_REQUEST );
		$display_strategy = CB2_PeriodInteractionStrategy::factory_from_args( $args, $post );
		$context          = CB2_Query::isset( $args, 'context' );
		$template_type    = CB2_Query::isset( $args, 'template_type' );

		// Query and display the list
		$html  = self::container_element( $args );
		$html .= '<ul class="cb2-subposts">';
		$html .= CB2::get_the_inner_loop( $args, $display_strategy, $context, $template_type );
		$html .= '</ul>';
		$html .= '</div>';

		return $html;
	}

	public static function location_current_items( $atts = '', String $content = '', String $tag = '', Array $passed_default_atts = array() ) {
		// For a given location, show its current items
		// CB2_LocationItemsAvailability requires a location
		global $post;

		$default_atts = array(
			'display-strategy' => 'CB2_LocationItemsAvailability',
		);

		// ------------------------------------- Query
		$args = shortcode_atts( array_merge( $default_atts, $passed_default_atts ), $atts, 'cb2_current_items' );
		if ( ! is_array( $atts ) ) $atts = array( $atts );
		$args             = array_merge( $atts, $args, $_REQUEST );
		$display_strategy = CB2_PeriodInteractionStrategy::factory_from_args( $args, $post );
		$context          = CB2_Query::isset( $args, 'context' );
		$template_type    = CB2_Query::isset( $args, 'template_type' );

		// Query and display the list
		$html  = self::container_element( $args );
		$html .= '<ul class="cb2-subposts">';
		$html .= CB2::get_the_inner_loop( $args, $display_strategy, $context, $template_type );
		$html .= '</ul>';
		$html .= '</div>';

		return $html;
	}

	public static function booking_form( $atts = '', String $content = '', String $tag = '', Array $passed_default_atts = array() ) {
		global $post;
		$html = '';
		$args = shortcode_atts( $passed_default_atts, $atts, 'cb2_booking_form_shortcode' );

		// Get the single item ID
		$itemID = NULL;
		if ( isset( $args['item-id'] ) )                $itemID = (int) $args['item-id'];
		else if ( $post && $post->post_type == 'item' ) $itemID = (int) $post->ID;
		else $html .= __( 'item-id or global item post required' );

		if ( $itemID ) {
			$args['item-id'] = $itemID;
			$item            = CB2_Query::get_post_with_type( 'item', $itemID );
			$title_text      = $item->post_title;
			$form_title_text = __( 'Booking of' ) . " $title_text";
			$do_action       = 'CB2_Item::book';
			$button_text     = __('book the') . " $title_text";
			$calendar        = self::booking_calendar( $atts, $content, $tag, $args );

			$html .= <<<HTML
				<form class='cb2-form cb2-content' action='' method='POST'><div>
					<input type='hidden' name='name' value='$title_text' />
					<input type='hidden' name='do_action' value='$do_action' />
					<input type='hidden' name='do_action_post_ID' value='$itemID' />
					<input type='hidden' name='redirect' value='/periodent-user/%action_return_value%/' />
					<input type='submit' name='submit' value='$button_text' />
					$calendar
					<input type='submit' name='submit' value='$button_text' />
				</div></form>
HTML;
		}

		return $html;
	}

	public static function booking_calendar( $atts = '', String $content = '', String $tag = '', Array $passed_default_atts = array() ) {
		global $post;
		$html = '';
		$args = shortcode_atts( array(), $atts, 'cb2_booking_form_shortcode' );

		// Get the single item ID
		$itemID = NULL;
		if ( isset( $args['item-id'] ) )                $itemID = (int) $args['item-id'];
		else if ( $post && $post->post_type == 'item' ) $itemID = (int) $post->ID;
		else $html .= __( 'item-id or global item post required' );

		// Generate Calendar
		if ( $itemID )
			$html .= self::calendar( $atts, $content, $tag, array(
				'display-strategy' => 'CB2_SingleItemAvailability',
				'item_ID'          => $itemID,
				'context'          => 'list',
				'template-type'    => 'available',
				'selection-mode'   => 'range',
				'selection-periods-min' => CB2_Settings::get( 'bookingoptions_min-period-usage' ),
				'selection-periods-max' => CB2_Settings::get( 'bookingoptions_max-period-usage' ),
			) );

		return $html;
	}
}
new CB2_Shortcodes();
