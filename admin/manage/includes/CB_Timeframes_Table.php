<?php
/**
 * Timeframes Table Class
 *
 * @package   CommonsBooking2
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */
if( !class_exists('WP_List_Table')){ // include list table class if not available.
    require_once( ABSPATH . 'wp-admin/includes/screen.php' );
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
class CB_Timeframes_Table extends WP_List_Table
{
		/**
	 * Table Admin functions
	 *
	 * @var array
	 */
	public $Timeframes_Admin;
	/**
	 * Array holding the rows
	 *
	 * @var array
	 */
	public $timeframes_array;
	/**
	 * Row count
	 *
	 * @var int
	 */
	public $total_rows;
	/**
	 * Default query args
	 *
	 * @var array
	 */
	public $query_args = array (
				'per_page' => 12,
				'paged' => TRUE,
				'offset' => 0,
				'scope' => '',
				'orderby' => 'date_start',
				'order' => 'ASC',
				'item_id' => '',
				'location_id' => ''
			);
    /**
     * [REQUIRED] You must declare constructor and give some basic params
     */
    function __construct()
    {
				global $status, $page;

				$this->Timeframes_Admin = new CB_Timeframes_Admin(); // Table Editing & Admin functions
				$this->init_timeframes_object(); // init
				parent::__construct( $this->Timeframes_Admin->names );

				$this->handle_request( $_REQUEST );
		}
    /**
     * Initialise a new object for the retrieval of timeframes, set the context
     */
		public function init_timeframes_object() {
				$this->timeframes_array = new CB_Object();
				$this->timeframes_array->set_context( 'timeframe' );
		}

    /**
     * Handle request: query for items, locations, timeframes
		 *
		 * @TODO: Pagination not working right now.
     *
     * @param array $request
     */
    public function handle_request( $request ) {

			if ( isset ( $request['item_id'] ) && cb_post_exists( $request['item_id'] ) ) {
				$this->query_args['item_id'] = $request['item_id'];
			}
			if ( isset ( $request['location_id'] ) && cb_post_exists( $request['location_id'] ) ) {
				$this->query_args['location_id'] = $request['location_id'];
			}
			if ( isset ( $request['timeframe_id'] ) && cb_timeframe_exists( $request['timeframe_id'] ) ) {
				$this->query_args['timeframe_id'] = $request['timeframe_id'];
			}
			if ( isset ( $request['paged'] ) ) {
				$this->query_args['paged'] = $request['paged'];
			}
    }

    /**
     * default column renderer
     *
     * @param $item - row (key, value array)
     * @param $column_name - string (key)
     * @return HTML
     */
    function column_default($item, $column_name)
    {
        return $item[$column_name];
    }

    /**
     * Format col: date_start
     *
     * @param $item - row (key, value array)
     * @return HTML
     */
    function column_date_start( $item ) {
			return CB_Gui::col_format_date( $item['date_start'] );
    }
    /**
     * Format col: date_end
     *
     * @param $item - row (key, value array)
     * @return HTML
     */
    function column_date_end( $item ) {
			return CB_Gui::col_format_date_end( $item['date_end'], $item['has_end_date'] );
    }
    /**
     * Format col: user_id
     *
     * @param $item - row (key, value array)
     * @return HTML
     */
    function column_owner_id( $item ) {
			return CB_Gui::col_format_user( $item['owner_id'] );
    }
    /**
     * Format col: availability
     *
     * @param $item - row (key, value array)
     * @return HTML
     */
    function column_availability( $item ) {

			$html = sprintf ( __( '%d booked, %d available, %d total', 'commons-booking' ),
			$item['availability']['booked'],
			$item['availability']['available'],
			$item['availability']['total']
		);
		return $html;
    }
    /**
     * Format col: item_id
     *
     * @param $item - row (key, value array)
     * @return HTML
     */
    function column_item_id($item)
    {
        return CB_Gui::col_format_post( $item['item_id'] );
    }
    /**
     * Format col: location_id
     *
     * @param $item - row (key, value array)
     * @return HTML
     */
    function column_location_id( $item )
    {
        return CB_Gui::col_format_post( $item['location_id'] );
    }

    /**
     * [OPTIONAL] this is example, how to render column with actions,
     * when you hover row "Edit | Delete" links showed
     *
     * @param $item - row (key, value array)
     * @return HTML
     */
    function column_timeframe_id( $item ) {

        $actions = array(
            'edit' => sprintf(
							'<a href="?page=cb_timeframes_edit&timeframe_id=%s">%s</a>',
							$item['timeframe_id'],
							__('Edit', 'commons-booking')),
            'delete' => sprintf('<a href="?page=%s&action=delete&timeframe_id=%s">%s</a>', $_REQUEST['page'], $item['timeframe_id'], __('Delete', 'commons-booking')),
        );

        return sprintf('%s %s',
            $item['timeframe_id'],
            $this->row_actions($actions)
        );
    }
    /**
     * [REQUIRED] this is how checkbox column renders
     *
     * @param $item - row (key, value array)
     * @return HTML
     */
    function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="timeframe_id[]" value="%s" />',
            $item['timeframe_id']
        );
    }

    /**
     * [REQUIRED] This method return columns to display in table
     * you can skip columns that you do not want to show
     * like content, or description
     *
     * @return array
     */
    function get_columns()
    {
        $columns = array(
            'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
            'timeframe_id' => __('ID', 'commons-booking'),
            'item_id' => __('Item', 'commons-booking'),
            'location_id' => __('Location', 'commons-booking'),
            'date_start' => __('Date Start', 'commons-booking'),
            'date_end' => __('Date End', 'commons-booking'),
            'owner_id' => __('Owner', 'commons-booking'),
						'description' => __('Description', 'commons-booking'),
						'availability' => __('Availability', 'commons-booking'),
        );
        return $columns;
    }

    /**
     * [OPTIONAL] This method return columns that may be used to sort table
     * all strings in array - is column names
     * notice that true on name column means that its default sort
     *
     * @return array
     */
    function get_sortable_columns()
    {
        $sortable_columns = array(
            'booking_status' => array('booking_status', true),
            'date' => array('date', false),
            'item_id' => array('item_id', false),
            'location_id' => array('location_id', false),
            'user_id' => array('user_id', false),
        );
        return $sortable_columns;
    }

    /**
     * [OPTIONAL] Return array of bult actions if has any
     *
     * @return array
     */
    function get_bulk_actions()
    {
        $actions = array(
            'delete' => 'Delete'
        );
        return $actions;
    }

    /**
     * [OPTIONAL] This method processes bulk actions
     * it can be outside of class
     * it can not use wp_redirect coz there is output already
     * in this example we are processing delete action
     * message about successful deletion will be shown on page in next part
     */
    function process_bulk_action()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . CB_TIMEFRAMES_TABLE; // do not forget about tables prefix

        if ('delete' === $this->current_action()) {
            $ids = isset($_REQUEST['timeframe_id']) ? $_REQUEST['timeframe_id'] : array();
            if (is_array($ids)) $ids = implode(',', $ids);

            if (!empty($ids)) {
                $wpdb->query("DELETE FROM $table_name WHERE timeframe_id IN($ids)");
            }
        }
		}

    /**
     * [REQUIRED] This is the most important method
     *
     * It will get rows from database and prepare them to be showed in table
     */
    function prepare_items(){

			$columns = $this->get_columns();
			$hidden = array();
			$sortable = $this->timeframes_array->get_timeframes_sortable_columns();

			// here we configure table headers, defined in our methods
			$this->_column_headers = array($columns, $hidden, $sortable);

			// [OPTIONAL] process bulk action if any
			$this->process_bulk_action();

			// will be used in pagination settings
			$this->total_rows = $this->timeframes_array->get_timeframes_row_count();

			$timeframes_object = $this->timeframes_array->get_timeframes( $this->query_args );

			$this->items = json_decode( json_encode( $timeframes_object ), true); // convert object  to array

			// [REQUIRED] configure pagination @TODO: pagination fails.
			$this->set_pagination_args(array(
					'total_items' => $this->total_rows, // total items defined above
					'per_page' => $this->query_args['per_page'], // per page constant defined at top of method
					'total_pages' => ceil( $this->total_rows / $this->query_args['per_page']) // calculate pages count
			));
    }
}
