<?php
/**
 * Handles the Booking process
 *
 * @package   CommonsBooking2
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */
class CB2_Booking_Page {

	public $booking_page_id;
	public $booking_id = FALSE;
	public $bookings = FALSE;
	public $booking_status = '';
	public $booking_user_id;
	public $action = FALSE;
	public $booking_hash;
	public $html_content = '';
	public $user;


	public function __construct(){

		$this->booking_page_id = CB2_Settings::get('pages_page-booking');
		$this->user = wp_get_current_user();

			add_filter('query_vars', array( $this, 'add_booking_vars_filter') );
		// override content on booking page
		add_filter('the_content', array( $this, 'maybe_do_content'), 1);

	}
/**
 * Add booking id query var
 *
 * ?bid=123213
 *
 */
	public function add_booking_vars_filter( $vars ) {
    $vars[] = "bid";
    $vars[] = "action";
    return $vars;
	}
/**
 * Validate
 *
 * ?booking=1
 *
 */
	public function get_url_vars( ) {

			global $post;
			$this->booking_id = get_query_var('bid', false);
	}
	public function get_single_booking() {

		global $wpdb;
		$args = array(
				'include' => $this->booking_id,
				'post_type' => 'periodent-user',
				'author' => $this->user->ID,
				'post_status' => 'publish',
				'suppress_filters' => true,
				'fields' => '',
		);

		$bookings = get_posts($args);
		if ( ! empty ( $bookings  )) {
			return $bookings;
		} else {
			return FALSE;
		}
	}


	/**
	* Auto-create a booking page
	*
	* @TODO
  *
  */
	public function maybe_create_booking_page( ) {

		if( ! CB2_Settings::get('pages_page-booking') ) { // page set

			global $wp_query;
			// Create post object
			$booking_page = array(
					'post_title' => __('Booking', 'commmons-booking-2'),
					'post_content' => 'Bookings page',
					'post_status' => 'publish',
					'post_type' => 'page',
			);

			$booking_page_id = wp_insert_post($booking_page ); // Insert the post into the database
		return $booking_page_id;

		}

	}
	 /**
	 * The content
	 *
	 * @return mixed html
	 *
	 */
	public function maybe_do_content( $content ) {

		global $post;

		if ( ! ( $post->ID == CB2_Settings::get( 'pages_page-booking' ) ) ) { return $content; }

		$this->booking_id = intval ( get_query_var('bid', false) );

		if ( $this->booking_id ) { // booking id

			$this->booking = $this->get_single_booking();

			if ( $this->booking ) { // booking exists & user is author

				$booking_status = 'unconfirmed';


				$item_id = $this->booking[0]->item_ID;
				$location_id = $this->booking[0]->location_ID;
				$user_id = $this->booking[0]->cb2_user_ID;

				switch ($booking_status) {

					case 'unconfirmed':
						// notice



						break;

					case 'confirmed':
						$content = 'confirmed';

				}

				$content = cb2_get_template_part(CB2_TEXTDOMAIN, 'item-summary', '', array('item_id' => $item_id), true);
				$content .= cb2_get_template_part(CB2_TEXTDOMAIN, 'location-summary', '', array('location_id' => $location_id), true);
				$content .= cb2_get_template_part(CB2_TEXTDOMAIN, 'user-summary', '', array('user_id' => $user_id), true);





				// booking id

			} else { // booking exists & user is author

				echo (__('Booking not found or you are not the author.', 'commons-booking-2') );
			}
		} else { // booking exists & user is author

			echo (__('You could see a booking if you supplied an id', 'commons-booking-2'));

		}

		return $content;
	}


	 /**
	 * Prepare location
	 *
	 * @return mixed html
	 *
	 */
	public function prepare_location( ) {

		global $post;
		$this->booking_id = get_query_var('bid', false);

		if ( $this->booking_id ) { // query for a single booking
			$booking = $this->get_single_booking();
			if ( $booking ) { // exists
				var_dump($booking);
			} else {
				echo (__('booking not found or you are not the author.', 'commons-booking-2') );
			}
		} else { // try to list bookings

		}

		// we are on the booking page
		if ( $post && $post->ID == $this->booking_page_id ) {
			$content =  $content . $this->html_content . 'booking id : ' . $this->booking_id;
		}
		return $content;
	}
	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public  function enqueue_styles() {
		wp_enqueue_style( CB2_TEXTDOMAIN . '-plugin-styles', plugins_url( 'public/assets/css/public.css', CB2_PLUGIN_ABSOLUTE ), array(), CB2_VERSION );
	}
	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public  function enqueue_scripts() {
		$min = ( WP_DEBUG ? '' : '.min' );
		wp_enqueue_script( CB2_TEXTDOMAIN . '-plugin-script', plugins_url( "public/assets/js/public$min.js", CB2_PLUGIN_ABSOLUTE ), array( 'jquery' ), CB2_VERSION );
	}
}

new CB2_Booking_Page();
