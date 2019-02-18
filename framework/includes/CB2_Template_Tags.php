<?php
/**
 * Template Tags
 *
 * Template tags {{template_tag}} to be used in emails and messages textareas in the backend
 *
 * wrapper for this Class: cb2_tag( $template, $post_type, $post_id )
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
	 * Post type
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
  public $parsed_template;
   /**
	 * Array of classes for the div wrapper
	 *
	 * @var string
	 */
  public $css_classes_array = array('cb2', 'cb2_template_tag');

   /**
	 * Extra template tags defined in plugin settings
	 *
	 * @var array
	 */
  public $user_defined_template_tags;

  public $booking_id = '';
  public $location_id = '';
  public $user_id = '';
  public $item_id = '';

	/**
	 * Constructor
	 *
	 * @param string $template  	The template string with {{posttype_field}} tags
	 * @param string $post_type		'item', 'location', 'periodent-user', 'user'
	 * @param int 	 $post_id 		Post id
	 *
	 * @uses CB2_Query
	 */
	public function __construct( $template, $post_type, $post_id ) {

		global $wpdb;
		global $post;

		$this->user_defined_template_tags = $this->get_user_defined_template_tags_field_list();

		if ( $template && $post_id && $post_type) {
			$this->template = $template;
			$this->post_type = $post_type;
			$this->post_id = $post_id;
			$this->queried_post = CB2_Query::get_post_with_type( $post_type, $post_id );

			$this->prepare();
			$this->parsed_template = $this->parse();
		}
	}
	/**
	 * Get an array of user-defined extra template tags from settings
	 *
	 * @uses CB2_Settings
	 *
	 * @return array $field_names
	 */
	public function get_user_defined_template_tags_field_list() {

		$field_names = array();

		$extra_fields = array();
		$extra_fields['item'] = CB2_Settings::get('usertemplatetags_item') ;
		$extra_fields['location'] = CB2_Settings::get('usertemplatetags_location') ;
		$extra_fields['user'] = CB2_Settings::get('usertemplatetags_user') ;

		if ( isset ( $extra_fields ) && is_array( $extra_fields ) ) {
			foreach ( $extra_fields as $post_type => $fields_list ) {
				$fields = explode ( ',', $fields_list );
				foreach ($fields as $field ) {
					if ( !empty ( $field )) {
						$field_names[$post_type][sanitize_text_field($field)] = ''; // do not assign a value yet
					}
				}
			}

			return apply_filters('cb2_user_defined_template_tags_list', $field_names );
		}
	}
	/**
	 * Get the replacement for extra template tags
	 *
	 * array $fields
	 */
	public function get_extra_template_tag_value( $post_id, $post_type ) {

		global $wpdb;

		$extra_tags = $this->user_defined_template_tags;

		$replacements = array();
		if ( isset( $extra_tags[$post_type] ) && is_array( $extra_tags[$post_type] ) && ! empty ( $extra_tags[$post_type] ) ) { // extra meta tags are defined for this post type
			foreach ( $extra_tags[$post_type] as $key => $value ) {
				$replacements[$key] = get_post_meta( $post_id, $key, TRUE);
			}
		}

		return apply_filters('cb2_user_defined_template_tags_replacement', $replacements );

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
		} else {
			echo ('ID of invalid post type submitted. Please provide either item, location, user or booking.');
		}
	}
	/**
	 * Echo the processed template
	 */
	public function output( ) {

		echo '<div class="' . implode ( ' ' , $this->css_classes_array ) . '">' . $this->parsed_template . '</div>';
	}
	/**
	 * Add css class to output
	 *
	 * Will be prefixed with 'cb2_'
	 *
	 */
	public function add_css_class( $class ) {
		$this->css_classes_array[] = 'cb2_' . $class;
	}
	/**
	 * Parse
	 *
	 * Parse the template for {{posttype_tag}},
	 * get the replacement(s),
	 * return resulting string
	 *
	 * @return string $result
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

		return apply_filters('cb2_template_tags_parsed', $result, 10, 3);
	}
	/**
	 * Prepare item vars
	 *
	 * Prepare generic wordpress fields
	 * Prepare extra template tags
	 * Add to replace_array
	 */
	private function prepare_item( ) {

		$wp_tags = $this->prepare_generic_wordpress_post( $this->item_id );
		$cb2_tags = array();
		$user_defined_tags =  $this->get_extra_template_tag_value( $this->item_id, 'item' );

		$tags = array_merge ( $wp_tags, $cb2_tags, $user_defined_tags );
		$this->replace_array['item'] = $tags;

	}
	/**
	 * Prepare location vars
	 *
	 * Prepare generic wordpress fields
	 * Prepare location address fields
	 * Prepare extra template tags
	 * Add to replace_array
	 */
	private function prepare_location( ) {

		$wp_tags = $this->prepare_generic_wordpress_post($this->location_id);
		$cb2_tags = array(
			'address' => get_post_meta( $this->location_id, 'geo_address', TRUE )
		);
		$user_defined_tags = $this->get_extra_template_tag_value($this->item_id, 'location');

		$this->replace_array['location'] = array_merge( $wp_tags, $cb2_tags, $user_defined_tags );

	}
	/**
	 * Prepare user vars @TODO
	 *
	 * Prepare extra template tags
	 * Add to replace_array
	 */
	private function prepare_user( ) {

		$wp_tags = array();
		$cb2_tags = array();
		$user_defined_tags = $this->get_extra_template_tag_value($this->user_id, 'user');

		$this->replace_array['user'] = array_merge($wp_tags, $cb2_tags, $user_defined_tags);

	}
	/**
	 * Prepare booking vars
	 */
	private function prepare_booking( ) {

		// set up the connected post types
		$this->item_id = $this->queried_post->item->ID;
		$this->location_id = $this->queried_post->location->ID;
		$this->user_id = $this->queried_post->user->ID;

		$wp_tags = array();
		$cb2_tags = array(
			'id' => $this->booking_id,
			'date_start' => $this->queried_post->datetime_part_period_start,
			'date_end' => $this->queried_post->datetime_part_period_end
		);
		$user_defined_tags = array();

		$this->replace_array['booking'] = array_merge($wp_tags, $cb2_tags, $user_defined_tags);

	}
	/**
	 * Prepare generic wp post vars
	 */
	private function prepare_generic_wordpress_post( $post_id ) {

		$generic_tags = array (
			'name' => get_the_title( $post_id  ),
			'url' => get_the_permalink( $post_id ),
			'link' => '<a href="' . get_the_permalink( $post_id  ) . '">' . get_the_title( $post_id  ) . '</a>',
		);
		return $generic_tags;
	}

	/**
	 * Replace matches with string
	 *
	 * @return string $replacement
	 */
	private function get_replacement( $match ) {

		$keys = explode('_', $match ); // split into posttype _ fieldname

		$posttype = $keys[0];
		$property = $keys[1];

		if ( isset( $this->replace_array[$posttype][$property] ) ) {
			$replacement = $this->replace_array[$posttype][$property];
		} else {
			$replacement = $match;
		}

		return $replacement;

	}
	/**
	 * Echo all template tags
	 *
	 * @TODO
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
