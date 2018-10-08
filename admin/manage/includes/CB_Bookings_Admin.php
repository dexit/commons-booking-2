<?php
/**
 * Booking Admin functions
 *
 * Handles editing, cancelling and detail view of bookings.
 *
 * @package   CommonsBooking2
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */
class CB_Bookings_Admin  {

	public $default_fields = array();
	// set vars
	public $list_slug = 'cb_bookings_table'; // slug for table screen
	public $edit_slug = 'cb_bookings_edit'; // slug for edit screen
	public $names = array(
    'singular' => 'booking',
    'plural' => 'bookings',
	);

	public $basename;
	public $message;
	public $booking_id;
	public $metabox;
	// DB Tables
	public $bookings_table, $timeframes_table, $slots_table, $slots_bookings_relation_table;

	/**
	 * Constructor
	 */
	public function __construct() {

		global $wpdb;

		// set default fields @TODO: Set user
		$this->default_fields = array(
			'booking_id' => 0,
			'booking_status' => ''
		);

		// set table names
		$this->bookings_table = $wpdb->prefix . CB_BOOKINGS_TABLE;
		$this->timeframes_table = $wpdb->prefix . CB_TIMEFRAMES_TABLE;
		$this->slots_table = $wpdb->prefix . CB_SLOTS_TABLE;
		$this->slots_bookings_relation_table = $wpdb->prefix . CB_SLOTS_BOOKINGS_REL_TABLE;

	}
	/**
	 * Get the booking id from the request array
	 *
	 * @param array $request
	 * @return array $booking
	 */
	public function get_booking_id_from_request( $request ) {

		return $request['booking_id'];

	}
	/**
	 * Get single booking
	 *
	 * @param array $request
	 * @return array $booking
	 */
	public function get_booking( $booking_id ) {

		if (isset( $booking_id )) {

			global $wpdb;

			$args = array (
				'booking_id' => $booking_id
			);

			$sql = $this->prepare_booking_sql( $args );

			// Note: we get an slots array here, with each having the same booking id, user_id, etc. date, item & location id etc is different
			$booking = $wpdb->get_results( $sql, ARRAY_A );

			if (! $booking ) {
				$booking = $this->default_fields['booking_id'];
				$this->message = new WP_Admin_Notice( __( 'Saved', CB2_TEXTDOMAIN ), 'updated' );
			}
			return $booking;
		}

	}
	/**
	 * Prepare the get bookings SQL statement
	 *
	 * What we will get is an array of slots.
	 *
	 * @param array $args
	 * @return array $slots
	 */
	public function prepare_booking_sql( $args = array() ) {

		global $wpdb;

		// if we have a booking id, we query only one row
		$where_args = array();
		if ( ! empty ( $args[ 'booking_id' ] ) ) {
			$where_args[] = sprintf ( ' %s.booking_id = %d', $this->bookings_table, $args[ 'booking_id' ] );
		}
		if ( ! empty ( $args[ 'status' ] ) ) {
			$where_args[] = sprintf ( " %s.booking_status = '%s'", $this->bookings_table, $args[ 'status' ] );
		}
		if ( ! empty ( $args[ 'timeframe_id' ] ) ) {
			$where_args[] = sprintf ( " %s.timeframe_id = '%d'", $this->slots_table, $args[ 'timeframe_id' ] );
		}

		// glue where
		if ( ! empty ( $where_args ) ) {
			$where = 'WHERE '. implode ( $where_args, ' AND ' );
		} else {
			$where = '';
		}

		$sql =(
			"
			SELECT
			{$this->bookings_table}.booking_id,
			{$this->bookings_table}.booking_status,
			{$this->bookings_table}.user_id,
			{$this->bookings_table}.booking_meta,
			{$this->bookings_table}.booking_time,
			{$this->slots_table}.slot_id,
			{$this->slots_table}.date,
			{$this->slots_table}.time_start,
			{$this->slots_table}.time_end,
			{$this->slots_table}.description AS slot_description,
			{$this->slots_table}.template_order AS slot_order,
			{$this->slots_table}.timeframe_id,
			{$this->timeframes_table}.timeframe_id,
			{$this->timeframes_table}.item_id,
			{$this->timeframes_table}.location_id,
			{$this->timeframes_table}.owner_id
			FROM {$this->bookings_table}
			LEFT JOIN {$this->slots_bookings_relation_table} ON {$this->bookings_table}.booking_id={$this->slots_bookings_relation_table}.booking_id
			LEFT JOIN {$this->slots_table} ON {$this->slots_bookings_relation_table}.slot_id={$this->slots_table}.slot_id
			LEFT JOIN {$this->timeframes_table} ON {$this->slots_table}.timeframe_id={$this->timeframes_table}.timeframe_id
			{$where}
		"
		);
		return $sql;
}
/**
 * Return the number of items in the db
 *
 * @return int $total_items
 */
public function get_item_count( ) {

	global $wpdb;

	// will be used in pagination settings
	$total_items = $wpdb->get_var("
	SELECT COUNT({$this->slots_table}.slot_id) FROM
	{$this->bookings_table}
	LEFT JOIN {$this->slots_bookings_relation_table} ON {$this->bookings_table}.booking_id={$this->slots_bookings_relation_table}.booking_id
	LEFT JOIN {$this->slots_table} ON {$this->slots_bookings_relation_table}.slot_id={$this->slots_table}.slot_id"
	);

	return $total_items;
}
	/**
	 * Handle the editing/creating a new entry request
	 * @TODO: creating new entries not tested with bookings
	 *
	 * @param $request
	 */
	public function handle_request( $request ) {

		if ( isset( $request['nonce'] ) && wp_verify_nonce( $request['nonce'], $this->basename ) ) { // we are trying to save

			$item = $this->merge_defaults( $request );
			$item_valid = $this->validate_form( $item );

			if ($item_valid === true) {
				if ( $item['booking_id'] == 0 ) {

					$this->add_row( $item );

				} else { // id is present, so update

					$this->update_row( $item );
				} // endif ($item_valid === true

				$this->message->output(); // diplay message(s)
			}
		} else { // we are just displaying the form

			echo("nothing to do");
		}
	}
	/**
	 * Save row in the bookings databse
	 *
	 * @param array $item
	 * @uses set_message
	 */
	public function add_row( $item ) {

		global $wpdb;

		$result = $wpdb->insert(
			$this->bookings_table,
			$item
		);
		$item['booking_id'] = $wpdb->insert_id; // save the id of the newly created entry @TODO

		$this->set_message($result);
	}
	/**
	 * Update row in the bookings database
	 *
	 * @param $item
	 * @uses set_message
	 */
	public function update_row( $item ) {

		global $wpdb;

		$result = $wpdb->update(
							$this->bookings_table,
							array( 'booking_status' => $item['booking_status'] ),
							array('booking_id' => $item['booking_id'])
						);

		$this->set_message($result);
	}
	/**
	 * Create a new admin message.
	 *
	 * @param array|bool $result
	 */
	public function set_message( $result ) {

		if ($result) {
			$this->message = new WP_Admin_Notice( __( 'Saved', CB2_TEXTDOMAIN ), 'updated' );
		} else {
			$this->message = new WP_Admin_Notice( __( 'Error saving', CB2_TEXTDOMAIN ), 'error' );
		}

	}
	/**
	 * Set the base file name (necessary to verify nonce).
	 *
	 * @param $filename
	 */
	public function set_basename( $filename ) {

		$this->basename = $filename;

	}
	/**
	 * Merge default_fields & input vars
	 *
	 * @param string $request
	 * @return array $item
	 */
	public function merge_defaults( $request ) {

		$item = shortcode_atts( $this->default_fields, $request );
		$this->booking_id = $item['booking_id'];
		return $item;
	}
/**
 * Validate @TODO
 *
 * @param $item
 * @return bool|string
 */
function validate_form( $item ){
    $messages = array();

    // if (empty($item['name'])) $messages[] = __('Name is required', 'commons-booking');
    // if (!empty($item['email']) && !is_email($item['email'])) $messages[] = __('E-Mail is in wrong format', 'commons-booking');
    // if (!ctype_digit($item['age'])) $messages[] = __('Age in wrong format', 'commons-booking');
    //if(!empty($item['age']) && !absint(intval($item['age'])))  $messages[] = __('Age can not be less than zero');
    //if(!empty($item['age']) && !preg_match('/[0-9]+/', $item['age'])) $messages[] = __('Age must be number');
    //...

    if (empty($messages)) return true;
    return implode('<br />', $messages);
	}
}
?>
