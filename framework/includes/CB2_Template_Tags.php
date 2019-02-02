<?php
/**
 * Template Tags & Replacement
 *
 *
 *
 *
 * @package   CommonsBooking2
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */
class CB2_Template_Tags {
	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected $instance = null;
   /**
	 * Item
	 *
	 * @var object
	 */
  public $item;
   /**
	 * Location
	 *
	 * @var object
	 */
  public $location;
   /**
	 * User
	 *
	 * @var object
	 */
  public $user;
   /**
	 * Template
	 *
	 * @var string
	 */
  public $template;
   /**
	 * Post ID
	 *
	 * @var string
	 */
  public $post_type;
   /**
	 * Post ID
	 *
	 * @var string
	 */
  public $post_id;
   /**
	 * Allowed post types
	 *
	 * @var array
	 */
  public $allowed_post_types = array ('item', 'location', 'booking', 'user');
   /**
	 * Matches
	 *
	 * @var array
	 */
  public $matches;
   /**
	 * Replace array with all items/properties
	 *
	 * @var array
	 */
  public $replace_array;

	/**
	 * Constructor
	 *
	 */
	public function __construct( $template, $post_id ) {

		$this->template = $template;
		$this->post_id = $post_id;
		$this->queried_post = get_post( $post_id );
		$this->post_type = get_post_type( $post_id );
		var_dump ( $this->queried_post );

		// if ( $this->is_valid() ) {


		// }

		$this->prepare();
		var_dump( $this->replace_array );
		$this->text = $this->parse();


	}
	private function prepare() {

		if ( $this->post_type == 'periodent-user' ) { // if booking we need to check for items, locations, users too
			echo ("preparing booking");

			$this->prepare_booking(); // sets up other ids
			$this->prepare_item();
			$this->prepare_location();
			$this->prepare_user();

		} else if ( $this->post_type == 'item' ) {
			echo ("preparing item");
			$this->item_id = $this->post_id;
			$this->prepare_item();
		} else if ( $this->post_type == 'location' ) {
			$this->location_id = $this->post_id;
			$this->prepare_location();
		}
	}

	public function echo() {
		echo $this->text;
	}

	private function is_valid( ) {
		if ( ! empty($this->template) && ( cb2_post_exists($this->post_id) ) && ( in_array( $allowed_post_types, $this->post_type ) ) ) {
			return TRUE;
		}
	}

	public function parse() {

		preg_match_all('/{{(\w+)}}/', $this->template, $matches);

		$replaces = array();
		$full_matches = array();

		foreach ($matches[1] as $key => $result) { // result = item_name

				$replace = '';
				$full_match = $matches[0][$key]; // {{item_name}}

				// split into item _ name
				$replace = $this->get_replacement($result);

				$replaces[] = $replace;
				$full_matches[] = $full_match;
		}
		$result = str_replace($full_matches, $replaces, $this->template);

		return apply_filters('cb2_template_tags', $result, 10, 3);


	}


	private function replace_matches() {


	}

	private function prepare_item(  ) {

		$this->replace_array['item'] = array(
			'name' => get_the_title( $this->item_id ),
		);
	}
	private function prepare_location( ) {

		$this->replace_array['location'] = array(
			'name' => get_the_title( $this->location_id ),
			'address' => get_post_meta( $this->location_id, 'geo_address', TRUE )
		);
	}
	private function prepare_user( ) {

		$this->replace_array['user'] = array(
			'name' => get_the_title( $this->user_id ),
			'email' => get_post_meta( $this->user_id, 'geo_address', TRUE )
		);
	}
	private function prepare_booking( ) {

		// set up others post type info
		$this->item_id = $this->queried_post->item_ID;
		$this->user_id = $this->queried_post->user_ID;
		$this->location_id = $this->queried_post->location_ID;

		$this->replace_array['booking'] = array(
			'id' => $this->booking_id,
			'date_start' => $this->queried_post->datetime_part_period_start,
			'date_end' => $this->queried_post->datetime_part_period_end
		);
	}
	private function get_replacement( $match ) {

		$keys = explode('_', $match ); // split into item _ name

		$posttype = $keys[0];
		$property = $keys[1];

		$replacement = $this->replace_array[$posttype][$property];

		return ( $replacement );

	}
}
