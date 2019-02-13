<?php
/**
 * Template Tags
 *
 * Template tags {{template_tag}} to be used in emails and messages textareas in the backend
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
  public $allowed_post_types = array ('item', 'location', 'periodent-user', 'user');
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
	 * The processed template
	 *
	 * @var string
	 */
  public $output;
   /**
	 * Extra template tags defined in plugin settings
	 *
	 * @var array
	 */
  public $extra_template_tags;

  public $booking_id = '';
  public $location_id = '';
  public $user_id = '';
  public $item_id = '';

	/**
	 * Constructor
	 *
	 * @param $template string 	The template
	 * @param $post_id 					Post id
	 */
	public function __construct( $template=FALSE, $post_id=FALSE ) {

		$this->extra_template_tags = $this->get_extra_template_tags();

		if ( $template && $post_id ) {
			$this->template = $template;
			$this->post_id = $post_id;
			$this->queried_post = get_post( $post_id ); //@TODO fix queried post once get_post is working again
			$this->post_type = get_post_type( $post_id );

			$this->prepare();
			$this->output = $this->parse();
		}
	}
	/**
	 * Get user-defined extra template tags from settings
	 *
	 * @uses CB2_Settings
	 */
	public function get_extra_template_tags() {

		$field_names = array();
		$extra_fields = CB2_Settings::get('extra_meta_fields') ;

		if ( isset ( $extra_fields ) && is_array( $extra_fields ) ) {
			foreach ( $extra_fields as $post_type => $fields_list ) {
				$fields = explode ( ',', $fields_list );
				foreach ($fields as $field ) {
					$field_names[$post_type][sanitize_text_field($field)] = '';
				}
			}
		}
		return apply_filters('cb2_extra_template_tags', $field_names );
	}

	/**
	 * Prepare by post type
	 */
	private function prepare() {

		if ( $this->post_type == 'periodent-user' ) { // if booking we need to check for items, locations, users too
			$this->booking_id = $this->post_id;
			$this->prepare_booking();
			$this->prepare_item(); // sets up other ids
			$this->prepare_location();
			$this->prepare_user();

		} else if ( $this->post_type == 'item' ) {
			$this->item_id = $this->post_id;
			$this->prepare_item();
		} else if ( $this->post_type == 'location' ) {
			$this->location_id = $this->post_id;
			$this->prepare_location();
		} else if ( $this->post_type == 'user' ) {
			$this->user_id = $this->post_id;
			$this->prepare_user();
		}
	}
	/**
	 * Output
	 */
	public function output() {
		echo $this->output;
	}
	/**
	 * Validate template @TODO
	 */
	private function is_valid( ) {
		if ( ! empty($this->template) && ( cb2_post_exists($this->post_id) ) && ( in_array( $allowed_post_types, $this->post_type ) ) ) {
			return TRUE;
		}
	}
	/**
	 * Parse the template for {{tags}}
	 */
	public function parse() {

		preg_match_all('/{{(\w+)}}/', $this->template, $matches);

		$replaces = array();
		$full_matches = array();

		foreach ($matches[1] as $key => $result) { // result = item_name

				$replace = '';
				$full_match = $matches[0][$key]; // {{item_name}}

				$replace = $this->get_replacement($result);

				$replaces[] = $replace;
				$full_matches[] = $full_match;
		}
		$result = str_replace($full_matches, $replaces, $this->template);

		return apply_filters('cb2_extra_template_tags', $result, 10, 3);
	}
	/**
	 * Prepare item vars
	 */
	private function prepare_item( ) {

		$system_tags = array(
			'name' => get_the_title( $this->item_id ),
			'categories' => 'cat'
		);
		$extra_meta_tags =  (array) $this->extra_template_tags['item'];
		$this->replace_array['item'] = array_merge ( $system_tags, $extra_meta_tags );

	}
	/**
	 * Prepare location vars
	 */
	private function prepare_location( ) {

		$system_tags = array(
			'name' => get_the_title( $this->location_id ),
			'address' => get_post_meta( $this->location_id, 'geo_address', TRUE )
		);
		$extra_meta_tags = (array) $this->extra_template_tags['location'];
		$this->replace_array['location'] = array_merge($system_tags, $extra_meta_tags);

	}
	/**
	 * Prepare user vars
	 */
	private function prepare_user( ) {

		$this->replace_array['user'] = array(
			'name' => get_the_title( $this->user_id ),
			'email' => get_user_meta( $this->user_id, 'geo_address', TRUE )
		);
	}
	/**
	 * Prepare booking vars
	 */
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
	/**
	 * Replace matches with string
	 *
	 * @return string $replacement
	 */
	private function get_replacement( $match ) {

		$keys = explode('_', $match ); // split into item _ name

		$posttype = $keys[0];
		$property = $keys[1];
		if ( isset( $this->replace_array[$posttype][$property] ) ) {
			$replacement = $this->replace_array[$posttype][$property];
		} else {
			$replacement = $match;
		}

		return $replacement ;

	}
	/**
	 * Echo all template tags
	 */
	public function list_template_tags() {

		$this->prepare_booking('-1');
		$this->prepare_item('-1'); // sets up other ids
		$this->prepare_location('-1');
		$this->prepare_user('-1');

		$return = '<h3>Plugin template tags</h3>';
		$return .= ('<dl class="cb2_template_tags">');
		foreach ( $this->replace_array as $posttype => $term_array) {
			$return .=  ('<dt>' . $posttype . '</dt>');
			foreach ( $term_array as $key => $val ) {
				$return .=  ('<dd><code>{{' . $posttype . '_' . $key . '}}</code></dd>');
			}
		}
		$return .= ('</dl>');
		return $return;
	}
}
