<?php
/**
 * Format common snippets
 *
 * Provides snippets for items, locations, etc in the desired formatting
 * Example Usage: CB_Gui::col_format_post( $id );
 *
 * @package   CommonsBooking2
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */
class CB_Gui {
	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;
    /**
	 * Object holding all strings
	 *
	 * @var object
	 */
    public $strings = array ();
    /**
	 * Date format
	 *
	 * @var string
	 */
    public static $date_format = 'd.m.y';
    /**
	 * Datetime format
	 *
	 * @var string
	 */
    public static $date_time_format = 'j.n.y. - H';

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
				do_action( 'commons_booking_admin_failed', $err );
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
	 * Retrieve a interface string
	 *
	 * @TODO: not in use, maybe depreciate
	 *
	 * @since 2.0.0
	 *
	 * @param $category The string category
	 * @param $key 		Optional: The key
	 *
	 * @return array string
	 */
	public static function get_gui( $id, $args ) {

    $get_gui =  array(
			'cal' => array (
				'weekday_names' => array(
					__('Monday', 'commons-booking'),
					__('Tuesday', 'commons-booking'),
					__('Wednesday', 'commons-booking'),
					__('Thursday', 'commons-booking'),
					__('Friday', 'commons-booking'),
					__('Saturday', 'commons-booking'),
					__('Sunday', 'commons-booking'),
				)
			),
			'category' => array (
				'key' => 'testing this'
            )
		);

	}
	/**
	 * Format WP posts as clickable links with title
	 *
	 * @since 2.0.0
 *
 * @param int $id
 * @param string $title
 * @return mixed $html
 */
public static function col_format_post( $id, $title = '') {

	if ( empty ( $title ) ) {
		$title = get_the_title( $id );
	}

	$html = sprintf ( '<a href="%s">%s</a>',
		get_edit_post_link( $id ),
		$title
	);

	return $html;
}
	/**
	 * Get date format
	 *
	 * @since 2.0.0
 *
 * @param string $date
 * @return mixed $html
 */
public static function get_date_format( ) {
	$date_format = self::$date_format;
	return $date_format;
}
	/**
	 * Format Dates @TODO: localize
	 *
	 * @since 2.0.0
 *
 * @param string $date
 * @return mixed $html
 */
public static function col_format_date( $date ) {

	return date ( self::get_date_format(), strtotime($date) );
}
/**
 * Get date/time formatted to use in column
 *
 * @since 2.0.0
 *
 * @param string $datetime
 * @return string $datetime
 */
public static function col_format_date_time( $date ) {

  return date ( self::date_time_format, strtotime( $date  )) ;

}
	/**
	 * Format End date, return either date or ∞
	 *
	 * @since 2.0.0
 *
 * @param string $date
 * @return mixed $html
 */
public static function col_format_date_end( $date, $has_end_date ) {
	if ( ! $has_end_date ) {
		return '&#8734; ' . __('(automatically extended)', 'commons-booking');
	} else {
		return CB_Gui::col_format_date( $date );
	}
}
	/**
	 * Frontend format location date info
	 *
	 * @since 2.0.0
 *
 * @param string $id post id
 * @uses col_format_post
 * @return mixed $html
 */
public static function timeframe_format_location_dates( $start_date, $end_date, $has_end_date ) {

	$date_string = '';

	if ( $has_end_date == 0 ) { // infinite timeframe
		if (  strtotime( 'today' ) > strtotime ( $start_date ) ) { // we are past start date - no need to show it
			// void
		} else { // start date not yet reached, so show it
			$date_string = sprintf ( __('From: %s', 'commons-booking'),  CB_Gui::col_format_date( $start_date ) );
		} // endif  $start_date > strtotime( self::date_format, 'today')
	} else { // end date set, so show Start & End dates
		$date_string = sprintf ( '%s - %s ',
			CB_Gui::col_format_date( $start_date ),
			CB_Gui::col_format_date( $end_date ) );
	}
	return $date_string;

}

	/**
	 * Frontend format wp post link
	 *
	 * @since 2.0.0
 *
 * @param string $id post id
 * @uses col_format_post
 * @return mixed $html
 */
public static function post_link( $post_id ) {

	$html = '';
	$title = get_the_title( $post_id );
	$url = get_the_permalink ( $post_id );
	$html = sprintf( '<a href="%s">%s</a>', $url, $title  );

	return $html ;

}


	/**
	 * Display a front-facing message
	 *
	 * @since 2.0.0
	 *
	 * @param $string 		The message
	 * @param $category Optional The message category
	 * @param $args array 		Optional: The key
	 *
	 * @return string
	 */
	public static function maybe_do_message( $string, $category='notice' ) {

		$message = '';
		if ( ! empty ( $string ) ) {
		$message = sprintf (
			'<div class="cb-message cb-%s">%s</div>',
			$category,
			$string
		);
			return $message;
		}
	}
/**
* Renders a dropdown menu for slot templates
*
* @param string $slot_templates array
* @param string $selected id of the pre-selected item
*
* @uses cb_get_post_types_list
* @return mixed html dropdown
*/
public static function edit_table_slot_template_select_html( $field_name, $selected ) {

	$html = '';

	$slot_templates_array = cb_get_slot_templates_dropdown();

	if ( isset ( $slot_templates_array ) && is_array ( $slot_templates_array ) ) {

		$html .= '<select name="' . $field_name .'" size="1" class="cb_'. $field_name .'">';

		if ( ! $selected ) {
			$new = "selected disabled"; } else { $new = ""; } // if new entry, set pre-selected

			foreach ( $slot_templates_array as $key => $value ) { // loop through posts array

				if ( $key == $selected ) {
					$s = ' selected'; } else { $s = '';
				}
				$html .= '<option value="' . $key . '"' . $s .' >' . $key . ' - ' . $value . '</option>';
			} // endforeach

			$html .= '</select>';
		} else {
			$html .= sprintf( __('<span class="cb-notice">No slot templates found.</span>', 'commons-booking' ));
		}

		return $html;
}
/**
* Renders a dropdown menu for items and locations
*
* @param string $post_type_name
* @param string $field_name
* @param string $selected id of the pre-selected item
*
* @uses cb_get_post_types_list
* @return mixed html dropdown
*/
public static function edit_table_post_select_html( $post_type_name, $field_name, $selected ) {

	$html = '';
	$post_types_array = cb_get_post_types_list( $post_type_name );

	if ( isset ( $post_types_array ) && is_array ( $post_types_array ) ) {

		$html .= '<select name="' . $field_name .'" size="1" class="cb_'. $field_name .'">';

		if ( ! $selected ) {
			$new = "selected disabled"; } else { $new = ""; } // if new entry, set pre-selected

			foreach ( $post_types_array as $key => $value ) { // loop through posts array

				if ( $key == $selected ) {
					$s = ' selected'; } else { $s = '';
				}
				$html .= '<option value="' . $key . '"' . $s .' >' . $value . '</option>';
			} // endforeach

			$html .= '</select>';
		} else {
			$html .= sprintf( __('<span class="cb-notice">No items of type %s found. Please create at least one.</span>', 'commons-booking' ), $post_type_name);
		}

		return $html;
}
/**
* Renders a dropdown menu users
*
* @param string $post_type_name
* @param string $field_name
* @param string $selected id of the pre-selected item
*
* @uses cb_get_post_types_list
* @return mixed html dropdown
*/
public static function edit_table_owner_select_html( $roles = array(), $selected ) {

	$html = '';
	$users_array = cb_get_users_list( );

	if ( isset ( $users_array ) && is_array ( $users_array ) ) {

		$html .= '<select name="owner_id" size="1" class="cb_owner_select">';

		if ( ! $selected ) {
			$new = "selected disabled"; } else { $new = ""; } // if new entry, set pre-selected

			foreach ( $users_array as $key => $value ) { // loop through posts array

				if ( $key == $selected ) {
					$s = ' selected'; } else { $s = '';
				}
				$html .= '<option value="' . $key . '"' . $s .' >' . $value . '</option>';
			} // endforeach

			$html .= '</select>';
		} else {
			$html .= sprintf( __('<span class="cb-notice">No users found. Please create at least one.</span>', 'commons-booking' ));
		}

		return $html;
}
/**
 * Get user info formatted to use in column
 *
 * @param int $id
 * @return string $user
 */
public static function col_format_user( $id ) {

	$user = get_user_by( 'id',  $id );

	$user_nicename = $user->user_nicename;
	$user_edit_link = get_edit_user_link( $user->ID );

	$user_html = sprintf ( '<a href="%s"> %s </a>', $user_edit_link, $user_nicename);

	return $user_html;
}
/**
 * Get slot availability formatted to use in column
 *
 * @param array $item
 * @return mixed $html
 */
public static function col_format_availability( $availability = '' ) {

	$html = '';

	if ( isset ( $availability ) ) {
		$html = sprintf( __( 'Slots: %d total, %d booked, %d available ', 'commons-booking'),
					$availability['total'],
					$availability['booked'],
					$availability['available']
					);
		}	else {
			$html = __('No slots configured', 'commons-booking');
		}
	return $html;
}
/**
 * Get timeframe for display in admin tables
 *
 * @param array $item
 * @return mixed $html
 */
public static function col_format_timeframe( $post_id, $echo=false ) {

	$html = '';

	$args = array (
		'item_id' => $post_id, // This template is called by item, so you need to supply the id
		'order_by' => 'date_start',
		'order' => 'ASC'
	);

	$timeframe_object = new CB_Timeframes( $args );
	$timeframes = $timeframe_object->get( );

	if ( isset( $timeframes ) && is_array( $timeframes ) ) {

		foreach ($timeframes as $timeframe) {

			$date_start = self::col_format_date( $timeframe->date_start);
			$date_end = self::col_format_date_end( $timeframe->date_end, $timeframe->has_end_date);
			$availability = self::col_format_availability( $timeframe->availability);
			$edit_link =  self::timeframes_admin_url(
				array ( 'timeframe_id' => $timeframe->timeframe_id ) );
			$location = get_the_title( $timeframe->location_id );

			$html .= sprintf( '<strong>%s - %s</strong> %s<br>%s<br>%s<hr>',$date_start, $date_end, $edit_link, $location, $availability );
		} // endforeach

	} else {

		$html .=  __( 'No timeframes configured.', 'commons-booking' );

	} // end if isset timeframes

	$html .= ' ' . self::timeframes_admin_url( array ( 'item_id' => $post_id ) );

	return $html;
}
/**
 * List slot templates
 *
 * @param array $slot_template_group_id
 * @return mixed $html
 */
public static function list_slot_templates_html( $slot_template_group_id, $list_format=TRUE ) {

	$html = '';
	$html_rows = '';

	$slots = new CB_Slots();
	$slots->set_slot_template_group( $slot_template_group_id );
	$templates = $slots->get_slot_template_group( );

	foreach ( $templates as $template_group ) {
		foreach ( $template_group as $template_slot) {
			$row =  sprintf (
				'%s ( %s - %s) ',
				$template_slot['description'],
				$template_slot['time_start'],
				$template_slot['time_end']
			);
			$html_rows .= ( $list_format ) ? '<li>' . $row . '</li>' : $row;
		}
	}
	$html .= ( $list_format ) ? '<ol>' . $html_rows . '</ol>' : $html_rows;

	return $html;
}
/**
 * List location opening times (days + hours)
 *
 * @param array $location_id
 * @return mixed $html
 */
public static function list_location_opening_times_html( $location_id ) {
	$location = new CB_Locations ( $location_id );
	$opening_times = $location->get_opening_times();
	$pickup_mode = $location->get_pickup_mode();

	$html = '';

	if ( $pickup_mode == 'opening_times' && is_array( $opening_times ) && ! empty ( $opening_times ) ){
		$html .= '<ul class="opening_hours_list">';
		foreach ( $opening_times as $day => $hours_array ) {
			$html .=  sprintf ('<li>%s<ul>', jddayofweek( $day, 2 ) );

			// from / till may or may not be set.
			$from = ( ! empty ($hours_array ) ) ? $hours_array['from'] : '';
			$till = ( ! empty ($hours_array ) ) ? $hours_array['till'] : '';

			$html .= sprintf( '<li>%s</li><li>%s</li>',
				$from,
				$till
			);

			$html .= '</ul></li>';

		} // end foreach
		$html .= '</ul>';
	} elseif ( $pickup_mode == 'personal_contact' ) {
		$html = __('No fixed opening times, contact the location after booking.');
	} else {
		$html = __('No fixed opening times defined for this location.');
	}
	return $html;
}
/**
 * Location in timeframes
 *
 * @param object $location
 * @return mixed $html
 *
 */
public static function location_short( $location ) {

		$html = '
		<div class="cb-location">
		<h3 class="cb-location-title">' . CB_Gui::post_link( $location->location_id ) . '</h3>
		<span class="cb-location-dates">' . CB_Gui::timeframe_format_location_dates( $location->date_start, $location->date_end, $location->has_end_date ) . '</span>
		<span class="cb-location-opening-times">' . CB_Gui::list_location_opening_times_html( $location->location_id ) . '</span>
		</div>
		';

		return $html;
}


/**
 * Return item/location description from metabox
 *
 * @param string $options_page
 * @return mixed $html
 *
 * @usage CB_Gui::cb_post_excerpt();
 */
public static function cb_post_excerpt() {

	global $post;
	$id = $post->ID;
	$meta = get_post_meta( $post->ID, 'cb-post-excerpt', true );
	return $meta;

}

/**
 * Return settings url html
 *
 * @param string $options_page
 * @return mixed $html
 */
public static function settings_admin_url( $options_page = '' ) {

	if ( $options_page ) {
		$url =  admin_url( 'admin.php?page=cb_settings_page#tabs-' . $options_page );
	} else {
		$url =  admin_url( 'admin.php?page=cb_settings_page' );
	}

	$link = sprintf ( '<a href="%s" target="_blank">' . __( 'Settings', 'commons-booking') . '</a>', $url );

	return $link;
}
/**
 * Return timeframe classes string for use in template
 *
 * @param object $timeframe
 * @return mixed $html
 */
public static function timeframe_classes( $timeframe  ) {

	$classes = '';

	return $classes;
}
/**
 * Return date classes string for use in template
 *
 * @param object $timeframe
 * @return mixed $html
 */
public static function date_classes( $date  ) {

	$classes = '';

	return $classes;
}
/**
 * Return html attributes for slots
 *
 * @param object $timeframe
 * @return mixed $html
 */
public static function slot_attributes( $slot  ) {

	$html = '';
	$html .= sprintf ( ' data-start="%s"', $slot['time_start']);
	$html .= sprintf ( ' data-end="%s"', $slot['time_end']);
	$html .= sprintf ( ' data-description="%s"', sanitize_title_with_dashes( $slot['description'] ));
	$html .= sprintf ( ' data-state="%s"', $slot['state'] ) ;
	$html .= sprintf ( ' data-location="%s"', $slot['location_id'] ) ;
	$html .= sprintf ( ' data-item="%s"', $slot['item_id'] ) ;

	$html .= sprintf ( ' data-item-title="%s"', get_the_title($slot['item_id'] )) ;
	$html .= sprintf ( ' data-item-thumbnail="%s"', get_the_post_thumbnail_url($slot['item_id'] )) ;
	$html .= sprintf ( ' data-item-url="%s"', get_permalink($slot['item_id'] )) ;

	return $html;
}
/**
 * Return timeframe admin url(s)
 *
 * @TODO enable targets: table, edit(with id), view, add new. does not make sense right now.
 *
 * @param string $target view, create, edit, list
 * @param array $args
 *
 * @return mixed $html
 */
public static function timeframes_admin_url( $args=array(), $target='', $title='' ) {

	$base_url = 'admin.php?page='; // url to to backend
	$base_slug = 'cb_timeframes_edit'; // timeframes edit slug
	$target_slug = '';

	$link_title = __( 'Add or edit', 'commons-booking' );

	if ( isset( $args ) && is_array ( $args ) ) {
		if ( isset ( $args['timeframe_id'] ) ) { 	// timeframe_id, timeframe exists, so view

			$target_slug = '&timeframe_id=' . $args['timeframe_id'] . '&view=1';
			$link_title = __('View timeframe', 'commons-booking');

		} elseif ( isset ( $args['item_id'] ) OR isset ( $args['location_id'] ) ) { // no timeframe id, but item/location id, so either create or list
			$item_id = isset ( $args['item_id'] ) ? $args['item_id'] : '';
			$location_id = isset ( $args['location_id'] ) ? $args['location_id'] : '';
			$item_location_slug = sprintf ( '&edit=1&item_id=%d&location_id=%d', $item_id, $location_id );

			if ( $target == 'table' ) { // goto a filtered timeframe_table view

				$base_slug = 'cb_timeframes_table';
				$target_slug = $item_location_slug;
				$link_title = __('View timeframes', 'commons-booking');


			} else { // goto timeframe settings to create a new timeframe with  item / location pre-filled

				$target_slug = $item_location_slug;
				$link_title = __('Add timeframe', 'commons-booking');

			}
		}
	}

	if ( ! empty ( $title ) ) { $link_title = $title; }
	$url = admin_url( $base_url . $base_slug . $target_slug );
	$link = sprintf ( '<a href="%s" style="float:right">' .$link_title . '</a>', $url );

	return $link;
}

}
add_action( 'plugins_loaded', array( 'CB_Gui', 'get_instance' ) );
