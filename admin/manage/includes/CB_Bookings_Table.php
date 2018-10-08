<?php
/**
 * Bookings Table Class
 *
 * @package   CommonsBooking2
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */
if(!class_exists('WP_List_Table')){ // include list table class if not available.
    require_once( ABSPATH . 'wp-admin/includes/screen.php' );
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
class CB_Bookings_Table extends WP_List_Table
{
	public $Bookings_Admin;
    /**
     * [REQUIRED] You must declare constructor and give some basic params
     */
    function __construct()
    {
				global $status, $page;

				$this->Bookings_Admin = new CB_Bookings_Admin();

				parent::__construct( $this->Bookings_Admin->names );

				$request = $_REQUEST;

		}

    /**
     * [REQUIRED] this is a default column renderer
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
     * Format col: date
     *
     * @param $item - row (key, value array)
     * @return HTML
     */
    function column_date($item)
    {
			return CB_Gui::col_format_date( $item['date'] );
    }
    /**
     * Format col: user_id
     *
     * @param $item - row (key, value array)
     * @return HTML
     */
    function column_user_id($item)
    {
			return CB_Gui::col_format_user( $item['user_id'] );
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
    function column_booking_status($item)
    {
        // links going to /admin.php?page=[your_plugin_page][&other_params]
        // notice how we used $_REQUEST['page'], so action will be done on curren page
        // also notice how we use $this->_args['singular'] so in this example it will
        // be something like &person=2
        $actions = array(
            'edit' => sprintf(
							'<a href="?page=cb_bookings_edit&booking_id=%s">%s</a>',
							$item['booking_id'],
							__('Edit', 'commons-booking')),
            'delete' => sprintf('<a href="?page=%s&action=delete&booking_id=%s">%s</a>', $_REQUEST['page'], $item['booking_id'], __('Delete', 'commons-booking')),
        );

        return sprintf('%s %s',
            $item['booking_status'],
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
            '<input type="checkbox" name="booking_id[]" value="%s" />',
            $item['booking_id']
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
            'booking_id' => __('ID', 'commons-booking'),
            'date' => __('date', 'commons-booking'),
            'item_id' => __('Item', 'commons-booking'),
            'location_id' => __('Location', 'commons-booking'),
            'time_start' => __('Start time', 'commons-booking'),
            'time_end' => __('End time', 'commons-booking'),
            'user_id' => __('User', 'commons-booking'),
						'booking_status' => __('Status', 'commons-booking'),
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
        $table_name = $wpdb->prefix . CB_BOOKINGS_TABLE; // do not forget about tables prefix

        if ('delete' === $this->current_action()) {
            $ids = isset($_REQUEST['booking_id']) ? $_REQUEST['booking_id'] : array();
            if (is_array($ids)) $ids = implode(',', $ids);

            if (!empty($ids)) {
                $wpdb->query("DELETE FROM $table_name WHERE booking_id IN($ids)");
            }
        }
		}

    /**
     * [REQUIRED] This is the most important method
     *
     * It will get rows from database and prepare them to be showed in table
     */
    function prepare_items()
    {
        global $wpdb;
        $bookings_table = $wpdb->prefix . CB_BOOKINGS_TABLE;
        $timeframes_table = $wpdb->prefix . CB_TIMEFRAMES_TABLE;
        $slots_table = $wpdb->prefix . CB_SLOTS_TABLE;

        $per_page = 10; // constant, how much records will be shown per page

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        // here we configure table headers, defined in our methods
        $this->_column_headers = array($columns, $hidden, $sortable);

        // [OPTIONAL] process bulk action if any
        $this->process_bulk_action();

        // will be used in pagination settings
				$total_items = $this->Bookings_Admin->get_item_count();

        // prepare query params, as usual current page, order by and order direction
        $paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged']) - 1) : 0;
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'booking_id';
				$order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'asc';

				// get the base sql query from Bookings_Admin Class
				// $base_sql = $this->Bookings_Admin->prepare_sql();
				$args = array (
					// 'status' => 'booked'
				);

				// query params for timeframes, items, status...
				if ( isset( $_REQUEST['timeframe_id'] ) ) {
					$args['timeframe_id'] = $_REQUEST['timeframe_id'];
				}


				$base_sql = $this->Bookings_Admin->prepare_booking_sql( $args );


        // add the pagination an other args
        $this->items = $wpdb->get_results($wpdb->prepare(
					"{$base_sql}
				ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);


        // [REQUIRED] configure pagination
        $this->set_pagination_args(array(
            'total_items' => $total_items, // total items defined above
            'per_page' => $per_page, // per page constant defined at top of method
            'total_pages' => ceil($total_items / $per_page) // calculate pages count
        ));
    }
}
