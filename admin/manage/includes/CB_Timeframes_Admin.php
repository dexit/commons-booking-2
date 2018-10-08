<?php
/**
 * Timeframes Admin functions
 *
 * Handles "edit", "generate_slots" and "view"-view of timeframes.
 *
 * @package   CommonsBooking2
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */
class CB_Timeframes_Admin  {
	/**
	 * Slots object
	 *
	 * @var object
	 */
	public $slots_object;
	/**
	 * Slots array
	 *
	 * @var object
	 */
	public $slots_array;
	/**
	 * Current screen ('timeframe_calendar', 'generate_slots', 'view')
	 *
	 * @var string
	 */
	public $screen;
	/**
	 * Form action
	 *
	 * @var string
	 */
	public $cb_form_action = '';
	public $form_footer;
	public $redirect;
	public $settings_args_defaults = array();
	public $settings_args = array();
	/**
	 * WP Admin slug, list
	 *
	 * @var string
	 */
	public $list_slug = 'cb_timeframes_table'; // slug for table screen
	/**
	 * WP Admin slug, edit
	 *
	 * @var string
	 */
	public $edit_slug = 'cb_timeframes_edit'; // slug for edit screen
	/**
	 * WP Admin slug, edit
	 *
	 * @var array
	 */
	public $names = array(
    'singular' => 'timeframe',
    'plural' => 'timeframes',
	);
	/**
	 * Possible screens
	 *
	 * @var array
	 */
	public $screens_array = array (
		'timeframe_calendar',
		'generate_slots',
		'view'
	);
	/**
	 * Return from db
	 *
	 * @var bool|int
	 */
	public $sql_result = FALSE;

	public $timeframes_array;
	public $basename;
	public $message;

	public $timeframe_id;
	public $timeframe;
	public $timeframe_options;
	public $timeframe_slots = array();

	// DB Tables
	public $bookings_table, $timeframes_table, $slots_table, $slots_bookings_relation_table;

	/**
	 * Constructor
	 */
	public function __construct() {

		global $wpdb;

		// set default settings_args
		$this->settings_args_defaults = array(
			'timeframe_id' => '',
			'booking_enabled' => 1,
			'codes_enabled' => 0,
			'calendar_enabled' => 0,
			'exclude_location_closed' => 0,
			'exclude_holiday_closed' => 0,
			'has_end_date' => 0,
			'item_id' => '',
			'location_id' => '',
			'date_start' => '',
			'date_end' => '',
			'description' => '',
			'owner_id' => '',
			'slot_template_group_id' => 0,
			'cb_form_action' => '',
			'modified' => ''
		);
		$this->timeframes_table = $wpdb->prefix . CB_TIMEFRAMES_TABLE;

		$this->screen = 'view'; // start screen

		$this->timeframe_options = new CB_Timeframe_Options( );


		// add filters for timeframe options custom saving/retrival function
		add_filter('cmb2_override_meta_value', array( $this->timeframe_options, 'get_timeframe_option_cmb2_form'), 10, 4);
		// saving data
		add_filter('cmb2_override_meta_save', array( $this->timeframe_options, 'save_timeframe_option'), 10, 2);
		// saving data: empty values (like checkboxes)
		add_filter('cmb2_override_meta_remove', array( $this->timeframe_options, 'save_timeframe_option'), 10, 2);

	}
	/**
	 * Initialise a new object for the retrieval of timeframes, set the context
	 *
	 * @since 2.0.0
	*/
	public function init_timeframes_object() {
			$this->timeframes_array = new CB_Object();
			$this->timeframes_array->set_context( 'timeframe' );
	}
	/**
	 * Get the timeframes id from the request array
	 *
	 * @since 2.0.0
	 *
	 * @param array $request
	 * @return array $timeframes
	 */
	public function get_timeframe_id( $request ) {

		if ( isset ( $request['timeframe_id'] ) ) {
			return $request['timeframe_id'];
		} else {
			return $this->timeframe_id;
		}

	}
	/**
	 * Get single timeframe
	 *
	 * @since 2.0.0
	 *
	 * @param array $request
	 * @return array $timeframe
	 */
	public function get_single_timeframe( $id ) {

		$this->init_timeframes_object();

		if (isset( $id )) { // we have a timeframe id

			$args = array (
				'timeframe_id' => $id,
				'scope' => '', // ignore dates
		 );
			$timeframe = $this->timeframes_array->get_timeframes( $args );

			$array = cb_obj_to_array( $timeframe );
			return $array[0];
		}
	}
/**
 * Return the number of timeframes in the db
 *
 * @since 2.0.0
 *
 * @return int $total_timeframes
 */
public function get_item_count( ) {

	global $wpdb;

	// will be used in pagination settings
	$total_timeframes = $wpdb->get_var("
	SELECT COUNT({$this->slots_table}.slot_id) FROM
	{$this->bookings_table}
	LEFT JOIN {$this->slots_bookings_relation_table} ON {$this->bookings_table}.booking_id={$this->slots_bookings_relation_table}.booking_id
	LEFT JOIN {$this->slots_table} ON {$this->slots_bookings_relation_table}.slot_id={$this->slots_table}.slot_id"
	);

	return $total_timeframes;
}
	/**
	 * Handle the request
	 *
	 * Creating timeframes with generating of slots and bookings
	 * Editing timeframes
	 *
	 * @since 2.0.0
	 *
	 * @param $request
	 */
	public function handle_request( $request ) {

		$this->setup_vars( $request );

		$item = $this->merge_defaults( $request );

		if ( isset( $request['nonce'] ) && wp_verify_nonce( $request['nonce'], $this->basename ) ) { // we are submitting the form

			if ( isset($request['cb_form_action'] ) && $request['cb_form_action'] == 'save_timeframe' ) { // we saving the settings

				$this->item_valid = $this->validate_timeframe_calendar_form( $item );

				if ( $this->item_valid === true) { // validation passed

					// handle checkboxes
					$item['has_end_date'] = $this->prepare_checkbox_value(
						$item['has_end_date']
					);
					$item['booking_enabled'] = $this->prepare_checkbox_value(
						$item['booking_enabled']
					);
					$item['calendar_enabled'] = $this->prepare_checkbox_value(
						$item['calendar_enabled']
					);
					$item['exclude_location_closed'] = $this->prepare_checkbox_value(
						$item['exclude_location_closed']
					);
					$item['exclude_holiday_closed'] = $this->prepare_checkbox_value(
						$item['exclude_holiday_closed']
					);
					$item['codes_enabled'] = $this->prepare_checkbox_value(
						$item['codes_enabled']
					);

					// PREPARE Date end -> end date or infinite timeframes
					$item['date_end'] = $this->maybe_set_end_date( $item );

					if ( $this->timeframe_id == '' ) { // no id, so add new
						$sql_timeframe_result = $this->add_row( $item );
					} else { // id is present, so update
						$sql_timeframe_result = $this->update_row( $item );
					} // endif ($item_valid === true

					$this->message->output(); // display message(s)
					$this->maybe_set_next_screen( $sql_timeframe_result, 'generate_slots' );
				} // end if validation passed
			} elseif ( isset( $request['cb_form_action'] ) && $request['cb_form_action'] == 'generate_slots' ) { // we are creating the slots

				$timeframe = $this->get_single_timeframe( $this->timeframe_id );

				$sql_slots_result = $this->slots_object->re_generate_slots_function( $timeframe, $request );

				$this->set_message( $sql_slots_result, __('Slots generated.'));

				$this->message->output(); // diplay message(s)
				$this->maybe_set_next_screen( $sql_slots_result, 'view' );
			}
		}

		$this->timeframe = $this->get_single_timeframe( $this->timeframe_id );
		$this->timeframe_options->set_timeframe_id( $this->timeframe_id );

		$options = $this->timeframe_options->get_timeframe_options( $this->timeframe_id );

		// setup the meta box
		$this->setup_metaboxes( );
		return $this->timeframe_id;

	}
	/**
	 * Set up vars
	 *
	 * Handle default/settings args and request args
	 * Setup the screens
	 *
	 * @since 2.0.0
	 *
	 * @param string $request
	 */
	public function setup_vars( $request ) {

		$this->settings_args = $this->merge_defaults( $request, $this->settings_args_defaults );

		if ( isset ( $this->timeframe_id ) && $this->timeframe_id != ''  ) { //  timeframe_id set
			$this->slots_object = new CB_Slots( $this->timeframe_id );
			$this->slots_array = $this->slots_object->get_slots(); //get previously created slots
		}

		// setup the screens
		// default view is "view" (timeframe_calendar)
		if ( isset( $request['view'] ) && isset ( $request['timeframe_id'] ) ) {
			$this->screen = 'view';
		} elseif ( isset( $request['generate_slots']) && $request['generate_slots'] == 1 ) {
			$this->screen = 'generate_slots';
		} elseif ( isset( $request['edit']) && $request['edit'] == 1 ) {
			$this->screen = 'timeframe_calendar';
		} elseif ( isset( $request['timeframe_options'] ) ) {
			$this->screen = 'timeframe_options';
		}

	}
	/**
	 * Set the next screen, if sql-result is positive
	 *
	 * @since 2.0.0
	 *
	 * @param int $result
	 * @param string $target
	 */
	public function maybe_set_next_screen( $result, $target ) {

		if ( $result !== FALSE ) { // operation successful, so set next screen
			$this->screen = $target;
		}
	}
	/**
	 * Set the next screen
	 *
	 * @since 2.0.0
	 */
	public function set_screen( $screen ) {

			$this->screen = $screen;
	}
	/**
	 * Set up the meta boxes for admin functions
	 *
	 * @since 2.0.0
	 *
	 * @uses add_meta_box()
	 */
	public function setup_metaboxes( ) {

		$this->form_fields_hidden = "";

		switch ( $this->screen ) {

			case 'timeframe_calendar':
				// Metabox: Timeframe settings (Screen 1)
				add_meta_box('timeframe_form_meta_box', __('General settings', 'commons-booking') , 'render_timeframe_calendar_meta_box' , 'timeframe', 'normal', 'default');

				$form_fields_redirect_action = '<input type="hidden" name="cb_form_action" value="save_timeframe">';
				$form_fields_hidden = sprintf ('<input type="hidden" name="modified" value="%s">',
				date("Y-m-d H:i:s") );

				$form_buttons = sprintf ('
					<button type="submit" value="submit" id="submit" class="button-primary" name="submit">%s</button>',
				__('Save and continue >>', 'commons-booking' ) );

				$this->form_footer = $form_fields_redirect_action . $form_fields_hidden . $form_buttons;
				break;

			case 'generate_slots':
				// Metabox: Timeframe generate slots (Screen 2)
				add_meta_box('timeframe_form_meta_box',  __('Review calendar settings', 'commons-booking') , 'render_timeframe_generate_slots_meta_box' , 'timeframe', 'normal', 'default');
				$this->form_footer = sprintf ('
				<input type="hidden" name="cb_form_action" value="generate_slots">
				<a href="javascript:history.back();" class="button-secondary">%s</a>
				<input type="submit" value="%s" id="submit" class="button-primary" name="submit">',
				__('<< Edit timeframe', 'commons-booking'),
				__('Generate calendar >>', 'commons-booking' ) );
				break;

			case 'view':
				// Metabox: Timeframe detail (Screen 3)
				add_meta_box('timeframe_form_meta_box', __('Timeframe information', 'commons-booking') , 'render_timeframe_view_meta_box' , 'timeframe', 'normal', 'default');

				break;

			case 'timeframe_options':

				add_meta_box('timeframe_options_meta_box', __('Advanced options: Overwrite plugin settings for this timeframe.', 'commons-booking') , 'render_timeframe_options_meta_box' , 'timeframe', 'normal', 'default');

				break;
		}
	}
	/**
	 * Return the meta box save/generate form_footer for each screen
	 *
	 * @since 2.0.0
	 *
	 * @return mixed
	 *
	 */
	public function do_form_footer( ) {

		echo $this->form_footer;

	}
	/**
	 * Return the meta box title for each screen
	 *
	 * @since 2.0.0
	 *
	 * @uses CB_Gui
	 *
	 * @return mixed $title
	 */
	public function do_subtitle( ) {

		if ( $this->timeframe_id ) {

			$item = 	CB_Gui::col_format_post( $this->timeframe['item_id'] );
			$location = 	CB_Gui::col_format_post( $this->timeframe['location_id'] );
			$date_start = CB_Gui::col_format_date ( $this->timeframe['date_start'] );
			$date_end = CB_Gui::col_format_date_end ( $this->timeframe['date_end'], $this->timeframe['has_end_date'] );

			$subtitle = sprintf (
				__('%s at %s, %s - %s', 'commons-booking' ),
				$item,
				$location,
				$date_start,
				$date_end
			);
		} else {
			$subtitle = '';
		}
		return $subtitle;

	}
	/**
	 * Return the meta box title for each screen
	 *
	 * @since 2.0.0
	 *
	 * @uses CB_Gui
	 *
	 * @return mixed $title
	 */
	public function do_title( ) {

		if ( $this->timeframe_id ) {

			$title = sprintf (
				__('<a href="%s">Timeframe (%d)</a>', 'commons-booking' ),
				get_admin_url( get_current_blog_id(), 'admin.php?page=cb_timeframes_edit&timeframe_id=' . $this->timeframe_id ),
				$this->timeframe_id
			);
		} else {
			$title = __('New timeframe', 'commons-booking');
		}
		return $title;

	}
	/**
	 * Save row in the timeframes databse
	 *
	 * @since 2.0.0
	 *
	 * @param array $item
	 * @uses set_message
	 * @uses $wpdb
	 * @return bool $result
	 */
	public function add_row( $item ) {

		global $wpdb;

		$result = $wpdb->insert(
			$this->timeframes_table,
				array(
					'booking_enabled' => $item['booking_enabled'],
					'codes_enabled' => $item['codes_enabled'],
					'calendar_enabled' => $item['calendar_enabled'],
					'exclude_location_closed' => $item['exclude_location_closed'],
					'exclude_holiday_closed' => $item['exclude_holiday_closed'],
					'has_end_date' => $item['has_end_date'],
					'item_id' => $item['item_id'],
					'location_id' => $item['location_id'],
					'date_start' => $item['date_start'],
					'date_end' => $item['date_end'],
					'description' => $item['description'],
					'owner_id' => $item['owner_id'],
					'slot_template_group_id' => $item['slot_template_group_id'],
					'modified' => $item['modified']
				),
					array(
						'%d',	// booking_enabled
						'%d',	// codes_enabled
						'%d',	// calendar_enabled
						'%d',	// exclude_location_closed
						'%d',	// exclude_holiday_closed
						'%d',	// has_end_date
						'%d',	// location_id
						'%s',	// date_start
						'%s',	// date_end
						'%s',	// description
						'%d',	// owner_id
						'%s' // modified
					)
			);
		$this->timeframe_id = $wpdb->insert_id;
		$this->settings_args['timeframe_id'] = $wpdb->insert_id;

		$this->set_message( $result, __('Timeframe created.') );
		return ($result);
	}
	/**
	 * Update row in the timeframes database
	 *
	 * @since 2.0.0
	 *
	 * @param $item
	 * @uses set_message
	 * @uses $wpdb
	 * @return bool $result
	 */
	public function update_row( $item ) {

		global $wpdb;

		$result = $wpdb->update(
			$this->timeframes_table,
				array(
					'booking_enabled' => $item['booking_enabled'],
					'codes_enabled' => $item['codes_enabled'],
					'calendar_enabled' => $item['calendar_enabled'],
					'exclude_location_closed' => $item['exclude_location_closed'],
					'exclude_holiday_closed' => $item['exclude_holiday_closed'],
					'has_end_date' => $item['has_end_date'],
					'item_id' => $item['item_id'],
					'location_id' => $item['location_id'],
					'date_start' => $item['date_start'],
					'date_end' => $item['date_end'],
					'description' => $item['description'],
					'owner_id' => $item['owner_id'],
					'slot_template_group_id' => $item['slot_template_group_id'],
					'modified' => $item['modified']
				),
				array(
					'timeframe_id' => $item['timeframe_id']),
					array(
						'%d',	// booking_enabled
						'%d',	// codes_enabled
						'%d',	// calendar_enabled
						'%d',	// exclude_location_closed
						'%d',	// exclude_holiday_closed
						'%d',	// has_end_date
						'%d',	// item_id
						'%d',	// location_id
						'%s',	// date_start
						'%s',	// date_end
						'%s',	// description
						'%d',	// owner_id
						'%d',	// slot_template_group_id
						'%s' // modified
					),
				array( '%d' )
			);

		$this->set_message( $result, __('Timeframe updated.'));

		return ($result);
	}
	/**
	 * Create a new admin message.
	 *
	 * @since 2.0.0
	 *
	 * @param array|bool $result
	 * @param string $info
	 */
	public function set_message( $result, $info ) {

		$string = '';
		if ( ! empty ( $info ) ) {
			$string = ' ' . $info ;
		}

		if ( $result === FALSE ) {
			$this->message = new WP_Admin_Notice( __( 'Error: ', 'commons-booking') . $string, 'error' );
		} elseif ( $result === 0 ) {
			$this->message = new WP_Admin_Notice( __( 'Nothing to update.', 'commons-booking' ) . $string, 'updated' );
		} else {
			$this->message = new WP_Admin_Notice( __( 'Success!', 'commons-booking' ) . $string, 'updated' );
		}

	}
	/**
	 * Set the base file name (necessary to verify nonce).
	 *
	 * @since 2.0.0
	 *
	 * @param $filename
	 */
	public function set_basename( $filename ) {

		$this->basename = $filename;

	}
	/**
	 * Merge settings_args_defaults & input vars
	 *
	 * @since 2.0.0
	 *
	 * @param string $request
	 * @return array $item
	 */
	public function merge_defaults( $request ) {

		$item = shortcode_atts( $this->settings_args_defaults, $request );
		$this->timeframe_id = $item['timeframe_id'];

		return $item;
	}
/**
 * Validate @TODO
 *
 * @since 2.0.0
 *
 * @param $item
 * @return bool|string
 */
function validate_timeframe_calendar_form( $item ){

	// @TODO: Validaton fails if end date empty

		$message = '';
		// start date
		if (empty($item['date_start'])) $message .= __('Start date is required. ', 'commons-booking');
		// end date
		if ( $item['has_end_date'] == 1 && empty( $item['date_end'] ) ) $message .= __('End date is required. ', 'commons-booking' );

		// if codes endabled, check if codes are set up in the backend
		if (  $item['codes_enabled'] ) {
			$codes = new CB_Codes;
			$valid = $codes->validate_enough_codes();
			if ( ! $valid ) {
				$message .= sprintf ( __('Insufficient codes. Please add at least 5 codes in %s.', 'commons-booking' ), CB_Gui::settings_admin_url('codes') );
			}
		}

    if ( ($message === '') ) return true;

		$this->set_message( FALSE , $message );
		$this->message->output(); // diplay message(s)

    return FALSE;
	}
/**
 * Handle the checkbox values
 *
 * @param $checkbox
 * @return
 */
function prepare_checkbox_value( $checkbox ){

		if ( ! empty ( $checkbox ) ) {
			return 1;
		} else {
			return 0;
		}
	}
/**
 * Handle timeframes without an end date
 *
 * @param $item
 * @uses CB_Settings
 * @return $end_date
 */
function maybe_set_end_date( $item ){

		if ( $item['has_end_date'] == 0 ) { // no end date, so use the date setting

			$cal_limit = CB_Settings::get( 'calendar', 'limit');

			$end_date =  date("Y-m-d", strtotime( "+".$cal_limit." days", strtotime( $item['date_start'] ) ) );
		} else {
			$end_date = $item['date_end'];
		}
    return $end_date;
	}
}
?>
