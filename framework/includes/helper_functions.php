<?php
/**
 * Helper Functions
 *
 * @package   CommonsBooking2
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */
/**
 * This file contains helper functions
 */
/**
 * Return an array of all wordpress pages
 *
 * For use in meta box forms dropdown selects.
 *
 * @return Array of wordpress pages as [pagedID][title]
 */

function cb2_form_get_pages() {
  // dropdown for page select
  $pages = get_pages();
  $dropdown = array();

  foreach ( $pages as $page ) {
    $dropdown[$page->ID] = $page->post_title;
  }
  return $dropdown;
}
/**
 * Return an array of all wordpress user roles
 *
 * For use in meta box forms.
 *
 * @return Array  wordpress user roles as [rolename][rolename]
 */

function cb2_form_get_user_roles()
{
	// make sure wp user is available
	if (! function_exists('get_editable_roles')) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
	}
	// dropdown for page select
	$roles = get_editable_roles();
	$return = array();


	foreach ($roles as $role_name => $role_info) {
		$return[$role_name] = $role_info['name'];
	}
	return $return;
}

/**
 * Get a List of slot templates for use in dropdown selects.
 * @TODO Hardcoded for now
 *
 * @return Array of wordpress pages as [slot_template_group_id][title]
 */

function cb_get_slot_templates_dropdown() {
	// dropdown for page select

	$obj = new CB2_Slot_Templates();
	$dropdown = array();

	$slot_templates = $obj->get_slot_templates();

  foreach ( $slot_templates as $key => $val ) {
		$descriptions = array();
		foreach ($val as $slot) {
			$descriptions[] = $slot['description'];
		}
    $dropdown[$key] = implode (', ', $descriptions );
	}
  return $dropdown;
}
/**
 * Get array of post types @TODO: Apply filters to let users only add their own post types
 *
 * @param string post type single name
 *
 * @return array of wordpress post types as [postID][title]
 */

function cb_get_post_types_list( $post_type_name ) {

	$args = array(
		'post_type'        => $post_type_name,
		'author'	   			 => '',
		'suppress_filters' => true
	);

	$posts = get_posts( $args  );
  $dropdown = array();

  foreach ( $posts as $post ) {
    $dropdown[$post->ID] = $post->post_title;
  }
  return apply_filters('cb_get_post_types_list', $dropdown );
}
/**
 * Get array of users.
 *
 * @param string post type single name
 *
 * @return array of wordpress post types as [postID][title]
 */

function cb_get_users_list( $roles=array() ) {

	$users = get_users( [ 'role__in' => $roles ] );

  $list = array();

  foreach ( $users as $user ) {
    $list[$user->ID] = $user->display_name;
  }
  return apply_filters('cb_get_users_list', $list );
}
/**
 * Create a date Range
 *
 * @return array dates in the format
 */
function cb_dateRange( $first, $last, $step = '+1 day', $format = 'Y-m-d' ) {

	$dates = array();
	$current = strtotime( $first );
	$last = strtotime( $last );

	while( $current <= $last ) {

		$dates[] = date( $format, $current );
		$current = strtotime( $step, $current );
	}

	return $dates;
}
/**
 * Convert object to array
 *
 * @param object
 *
 * @return array
 */
function cb_obj_to_array( $object ) {

	$array = json_decode( json_encode( $object), true);
	return $array;
}
	/**
	 *  A method for inserting multiple rows into the specified table
	 *  Updated to include the ability to Update existing rows by primary key
	 *
	 *  Usage Example for insert:
	 *
	 *  $insert_arrays = array();
	 *  foreach($assets as $asset) {
	 *  $time = current_time( 'mysql' );
	 *  $insert_arrays[] = array(
	 *  'type' => "multiple_row_insert",
	 *  'status' => 1,
	 *  'name'=>$asset,
	 *  'added_date' => $time,
	 *  'last_update' => $time);
	 *
	 *  }
	 *
	 *
	 *  wp_insert_rows($insert_arrays, $wpdb->tablename);
	 *
	 *  Usage Example for update:
	 *
	 *  wp_insert_rows($insert_arrays, $wpdb->tablename, true, "primary_column");
	 *
	 *
	 * @param array $row_arrays
	 * @param string $wp_table_name
	 * @param boolean $update
	 * @param string $primary_key
	 * @return false|int
	 *
	 * @author	Ugur Mirza ZEYREK
	 * @contributor Travis Grenell
	 * @source http://stackoverflow.com/a/12374838/1194797
	 */

function wp_insert_rows($row_arrays = array(), $wp_table_name, $update = false, $primary_key = null) {
	global $wpdb;
	$wp_table_name = esc_sql($wp_table_name);
	// Setup arrays for Actual Values, and Placeholders
	$values        = array();
	$place_holders = array();
	$query         = "";
	$query_columns = "";

	$query .= "INSERT INTO `{$wp_table_name}` (";
	foreach ($row_arrays as $count => $row_array) {
		foreach ($row_array as $key => $value) {
			if ($count == 0) {
				if ($query_columns) {
					$query_columns .= ", " . $key . "";
				} else {
					$query_columns .= "" . $key . "";
				}
			}

			$values[] = $value;

			$symbol = "%s";
			if (is_numeric($value)) {
				if (is_float($value)) {
					$symbol = "%f";
				} else {
					$symbol = "%d";
				}
			}
			if (isset($place_holders[$count])) {
				$place_holders[$count] .= ", '$symbol'";
			} else {
				$place_holders[$count] = "( '$symbol'";
			}
		}
		// mind closing the GAP
		$place_holders[$count] .= ")";
	}

	$query .= " $query_columns ) VALUES ";

	$query .= implode(', ', $place_holders);

	if ($update) {
		$update = " ON DUPLICATE KEY UPDATE $primary_key=VALUES( $primary_key ),";
		$cnt    = 0;
		foreach ($row_arrays[0] as $key => $value) {
			if ($cnt == 0) {
				$update .= "$key=VALUES($key)";
				$cnt = 1;
			} else {
				$update .= ", $key=VALUES($key)";
			}
		}
		$query .= $update;
	}

	$sql = $wpdb->prepare($query, $values);
	if ($wpdb->query($sql)) {
		return true;
	} else {
		return false;
	}
}
/**
 * Only return default value if we don't have a page ID (in the 'page' query variable) @TODO: works only on settings page
 *
 * @param  bool  $default On/Off (true/false)
 * @return mixed  Returns true or '', the blank default
 */
function cmb2_set_checkbox_default_for_new_post( $default ) {

	return isset( $_GET['page'] ) ? '' : ( $default ? (string) $default : '' );
}
/**
 * Format checkbox value as bool
 *
 * @param string $value
 * @return bool  Returns true or '', the blank default
 *
 */
function cb_checkbox_bool( $value ) {

	if ( isset ( $value ) && $value  == 'on' ) {
		return true;
	}	else {
		return false;
	}
}
/**
 * Filter dates array by opening_times (days of the week)
 *
 * @param  string $date_start
 * @param  string $date_end
 * @param  array $opening_times
 * @param bool $inverse
 * @return array $matching dates
 */
function cb_filter_dates_by_opening_times( $date_start, $date_end, $opening_times, $inverse = FALSE ) {

	$matches = array();
	$misses = array();

	$dates_array = cb_dateRange(  $date_start, $date_end );

	foreach ($dates_array as $date) {
			$day_number = date('w', strtotime( $date ) );
			if ( array_key_exists( $day_number, $opening_times ) ) {
				$matches[] = $date;
			} else {
				$misses[] = $date;
			}
	}
	if ( $inverse ) {
		return $misses;
	} else {
		return $matches;
	}
}
/**
 * Determines if a post, identified by the specified ID, exist
 * within the WordPress database.
 *
 * @param    int    $id    The ID of the post to check
 * @return   bool          True if the post exists; otherwise, false.
 * @since    2.0.0
 */
function cb_post_exists( $id ) {
  return is_string( get_post_status( $id ) );
}
/**
 * Determines if a post, identified by the specified ID, exist
 * within the WordPress database. @TODO
 *
 * @param    int    $id    The ID of the timeframe to check
 * @return   bool          True if the post exists; otherwise, false.
 * @since    2.0.0
 */
function cb_timeframe_exists( $id ) {

	if ( isset( $id ) && ( ! empty ( $id ) ) ) {
		return TRUE;

	} else {

		return FALSE;
	}

}
/**
 * Return the formatted settings field name including prefix
 *
 * @param    string   $group    the settings group
 * @return   string   $field_name  Name of the settings field
 * @since    2.0.0
 */
function cb_get_settings_field_name_prefixed( $group, $field_name ) {

	$plugin_slug = CB2_Settings::get_plugin_settings_slug();

	$field_name_prefixed = $plugin_slug . $group . '-' . $field_name;

	return $field_name_prefixed;

}
