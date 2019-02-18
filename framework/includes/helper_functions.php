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

 /**
 *  Current template file name/path
 *
 * @return string  wordpress user roles as [rolename][rolename]
 */
function cb2_debug_maybe_print_path( $file_path ) {
	if ( cb2_is_debug() ) {
		echo ( '<pre>' . $file_path . '</pre>' );
	}
}
/**
 * Fixed checkbox issue with default is true.
 *
 * @param  mixed $override_value Sanitization/Validation override value to return.
 * @param  mixed $value          The value to be saved to this field.
 * @return mixed
 */
function cmb2_sanitize_checkbox($override_value, $value)
{
    // Return 0 instead of false if null value given. This hack for
    // checkbox or checkbox-like can be setting true as default value.
    return is_null($value) ? 0 : $value;
}


/**
 *  Display debug info.
 *
 * @TODO currently equals to WP_DEBUG, which may not be wanted.
 * Add another condition.
 *
 * @return bool  wordpress user roles as [rolename][rolename]
 */
function cb2_is_debug()
{
    if (WP_DEBUG) {
			return true;
    } else {
			return false;
		}
}
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
 * @param bool $names_only return only the field names
 *
 * @return Array  wordpress user roles as [rolename][rolename]
 */
function cb2_form_get_user_roles( $keys_only=false )
{
	// make sure wp user is available
	if (! function_exists('get_editable_roles')) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
	}
	// dropdown for page select
	$wp_roles = get_editable_roles();
	$user_roles_formatted = array();

	foreach ($wp_roles as $role_name => $role_info) {
		if ( $keys_only ) {
			$user_roles_formatted[] = $role_name;
		} else {
			$user_roles_formatted[$role_name] = $role_info['name'];
		}
	}
	return $user_roles_formatted;
}
/**
 * Determines if a post, identified by the specified ID, exist
 * within the WordPress database.
 *
 *
 * @param    int    $id    The ID of the post to check
 * @return   bool          True if the post exists; otherwise, false.
 */
function cb2_post_exists($id) {
    return is_string(get_post_status($id));
}
/**
 * Echo a template tag
 *
 * Wrapper for CB2_Template_Tags
 *
 * @uses CB2_Template_Tags
 *
 * @param string $template  	The template string with {{posttype_field}} tags
 * @param string $post_type		'item', 'location', 'periodent-user', 'user'
 * @param int 	 $post_id 		Post id
 * @param string $css_class 	Additional css class
 */
function cb2_tag( $template, $post_type, $post_id, $css_class ) {
		$tt = new CB2_Template_Tags( $template, $post_type, $post_id );
		if ( ! empty ( $css_class )) { $tt->add_css_class( $css_class ); }
		$tt->output();
}
/**
 * Checks to see if a given string exists at the start of another string.
 *
 * @param $haystack The string to search in.
 * @param $needle The string we are looking for.
 * @param bool $caseSensitive Whether we want our search to be case sensitive or not.
 * @return bool
 */
function strStartsWith($haystack, $needle, $caseSensitive = true){
    //Get the length of the needle.
    $length = strlen($needle);
    //Get the start of the haystack.
    $startOfHaystack = substr($haystack, 0, $length);
    //If we want our check to be case sensitive.
    if($caseSensitive){
        //Strict comparison.
        if($startOfHaystack === $needle){
            return true;
        }
    } else{
        //Case insensitive.
        //If the needle and the start of the haystack are the same.
        if(strcasecmp($startOfHaystack, $needle) == 0){
            return true;
        }
    }
    //No matches. Return FALSE.
    return false;
}
/**
 * Convert object to array
 *
 * @param object
 *
 * @return array
 */
function cb2_obj_to_array( $object ) {

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
function cb2_checkbox_bool( $value ) {

	if ( isset ( $value ) && $value  == 'on' ) {
		return true;
	}	else {
		return false;
	}
}
