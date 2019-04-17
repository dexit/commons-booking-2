<?php
/**
 *
 * CB2 Location Class
 *
 * Post type config
 * Post type selection metabox
 * Location edit metaboxes
 * Content filters
 * Actions related to the item (booking)
 * Admin column setup
 *
 *
 *
 * @package   CommonsBooking2
 * @author    The CommonsBooking Team <commonsbooking@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */


class CB2_Location extends CB2_Post implements JsonSerializable {
	public static $all = array();
	static $static_post_type  = 'location';
	public static $rewrite   = array( 'slug' => 'location' );
	public static $post_type_args = array(
		'menu_icon' => 'dashicons-admin-tools',
		'supports' => array('title','thumbnail','editor','excerpt')
	);
	public $items = array();

	static function selector_metabox( String $context = 'normal', Array $classes = array(), $none = TRUE ) {
		return array(
			'title'      => __( 'Location', 'commons-booking-2' ),
			'context'    => $context,
			'classes'    => $classes,
			'show_names' => FALSE,
			'fields'     => array(
				array(
					'name'    => __( 'Location', 'commons-booking-2' ),
					'id'      => 'location_ID',
					'type'    => 'select',
					'default' => ( isset( $_GET['location_ID'] ) ? $_GET['location_ID'] : NULL ),
					'options' => CB2_Forms::location_options( $none ),
				),
				CB2_Query::metabox_nosave_indicator( 'location_ID' ),
			),
		);
	}

	static function post_link_metabox( String $context = 'normal', Array $classes = array(), $none = TRUE ) {
		return array(
			'title'      => __( 'Location', 'commons-booking-2' ),
			'context'    => $context,
			'classes'    => $classes,
			'show_names' => FALSE,
			'fields'     => array(
				array(
					'name'      => __( 'Location', 'commons-booking-2' ),
					'id'        => 'location_ID',
					'type'      => 'post_link',
					'default'   => ( isset( $_GET['location_ID'] ) ? $_GET['location_ID'] : NULL ),
					'post_type' => CB2_Location::$static_post_type,
				),
				CB2_Query::metabox_nosave_indicator( 'location_ID' ),
			),
		);
	}

	static function metaboxes() {
		$advanced   = isset( $_GET['advanced'] );
		$startdate  = ( isset( $_GET['startdate'] ) ? $_GET['startdate'] : CB2_DateTime::next_week_start()->format( CB2_Query::$datetime_format ) );
		$enddate    = ( isset( $_GET['enddate'] )   ? $_GET['enddate']   : CB2_DateTime::next_week_end()->format(   CB2_Query::$datetime_format ) );
		$bare       = ( $advanced ? NULL : 'bare' );

		$today      = CB2_DateTime::today();
		$plus1month = $today->clone()->add( 'P1M' );
		$advanced_url  = CB2_Query::pass_through_query_string( NULL, array(
			'startdate' => $today,
			'enddate'   => $plus1month,
			'advanced'  => TRUE,
		) );
		$advanced_text = __( 'advanced' );
		$advanced      = " <div class='dashicons-before dashicons-admin-tools cb2-advanced'><a href='$advanced_url'>$advanced_text</a></div>";

		$metaboxes = array(
			array(
				'title'      => __( 'Add Default Opening Hours', 'commons-booking-2' ),
				'context'    => 'side',
				'show_on_cb' => array( 'CB2', 'is_not_published' ),
				'show_names' => TRUE,
				'fields'     => array(
					array(
						'id' => 'set_default_opening_hours',
						'name' => __('Set Default Opening Hours', 'commons-booking-2'),
						'type' => 'checkbox',
						'default' => TRUE,
					),
					array(
						'id' => 'with_lunch_break',
						'name' => __('With Lunch Break', 'commons-booking-2'),
						'type' => 'checkbox',
						'default' => TRUE,
					),
					array(
						'id' => 'with_weekend_break',
						'name' => __('With Weekend Break', 'commons-booking-2'),
						'type' => 'checkbox',
						'default' => TRUE,
					),
				),
			),

			array(
				'title'      => __( 'Opening Hours', 'commons-booking-2' ) . $advanced,
				'context'    => 'normal',
				'priority' 	 => 'high',
				'show_names' => FALSE,
				'show_on_cb' => array( 'CB2', 'is_published' ),
				'fields' => array(
					array(
						'name'    => __( 'Opening Hours', 'commons-booking-2' ),
						'id'      => 'calendar',
						'type'    => 'calendar',
						'options' => array(
							'style'    => $bare, // Day TDs only
							'template' => 'overlaid',
							'template-args' => array(
								'action' => '<span class="cb2-todo">addto</span>',
							),
							'query'    => array(
								'post_status' => 'any',
								'date_query' => array(
									array(
										// post_modified_gmt is the end date of the period instance
										'column' => 'post_modified_gmt',
										'after'  => $startdate,
									),
									array(
										// post_gmt is the start date of the period instance
										'column' => 'post_date_gmt',
										'before' => $enddate,
									),
									'compare' => CB2_Week::$static_post_type,
								),
								'meta_query' => array(
									'location_ID_clause' => array(
										'key'     => 'location_ID',
										'value'   => '%ID%',
										'compare' => 'IN',
									),
									'period_status_type_ID_clause' => array(
										'key'     => 'period_status_type_ID',
										'value'   => CB2_PeriodStatusType_Open::$id,
									),
								),
							),
						),
					),
				),
			),

			array(
				'title'      => __( 'Geodata', 'commons-booking-2' ),
				'context'    => 'normal',
				'priority' 	 => 'high',
				'show_names' => TRUE,
				'fields'     => array(
					array(
						'id' => 'geo_address',
						'name' => __('Address', 'commons-booking-2'),
						'type' => 'text',
					),
					array(
						'id' => 'geo_latitude',
						'name' => __('Latitude', 'commons-booking-2'),
						'type' => 'text',
					),
					array(
						'id' => 'geo_longitude',
						'name' => __('Longitude', 'commons-booking-2'),
						'type' => 'text',
					),
				),
			),
		);

		return apply_filters('cb2_location_metaboxes', $metaboxes);
	}

  function post_type() {return self::$static_post_type;}

  protected function __construct( $ID, $geo_address = NULL, $geo_latitude = NULL, $geo_longitude = NULL ) {
		CB2_Query::assign_all_parameters( $this, func_get_args(), __class__ );
    parent::__construct( $ID );
  }

  static function factory_from_properties( Array &$properties, &$instance_container = NULL, Bool $force_properties = FALSE, Bool $set_create_new_post_properties = FALSE ) {
		$object = self::factory(
			(int) ( isset( $properties['location_ID'] )     ? $properties['location_ID'] : $properties['ID'] ),
			( isset( $properties['geo_address'] )   && $properties['geo_address']    ? $properties['geo_address'][0]     : NULL ),
			( isset( $properties['geo_latitude'] )  && $properties['geo_latitude']   ? $properties['geo_latitude'][0]    : NULL ),
			( isset( $properties['geo_longitude'] ) && $properties['geo_longitude']  ? $properties['geo_longitude'][0]   : NULL ),
			$properties, $force_properties
		);

		return $object;
  }

  static function factory( Int $ID, $geo_address = NULL, $geo_latitude = NULL, $geo_longitude = NULL, Array $properties = NULL, Bool $force_properties = FALSE, Bool $set_create_new_post_properties = FALSE ) {
		return CB2_PostNavigator::createInstance( __class__, func_get_args(), $ID, $properties, $force_properties, $set_create_new_post_properties );
  }

	function get_the_after_content() {
		return CB2_Shortcodes::calendar_shortcode( array(
				'location-ID'      => $this->ID,
				'display-strategy' => 'CB2_AllItemAvailability',
			) );
	}

	function tabs( $edit_form_advanced = FALSE ) {
		$tabs = array();
		if ( ! $edit_form_advanced ) $tabs = array(
			'cb2-tab-periodinsts'  => 'Period Instances',
			'cb2-tab-geo'          => 'Location',
			'cb2-tab-openinghours' => 'Opening Hours'
		);
		return $tabs;
	}

	function add_item( CB2_Item &$item ) {
		if ( ! in_array( $item, $this->items ) ) array_push( $this->items, $item );
	}

	public static $default_enabled_columns = array( 'cb', 'title', 'opening_hours', 'address', 'date' );

  function manage_columns( $columns ) {
		$columns['item_pickup_return'] = 'Item Pickup/Return <a href="admin.php?page=cb2-timeframes">view all</a>';
		$columns['bookings']          = 'Bookings <a href="admin.php?page=cb2-bookings">view all</a>';
		$columns['opening_hours']     = 'Opening Hours';
		$columns['address']           = 'Address <a href="admin.php?page=cb2-menu&output_type=Map&schema_type=location">view all on map</a>';
		return $columns;
	}

	function custom_columns( $column ) {
		$wp_query           = NULL;
		$wp_query_page_name = "paged-column-$column";
		$current_page       = ( isset( $_GET[$wp_query_page_name] ) ? $_GET[$wp_query_page_name] : 1 );

		switch ( $column ) {
			case 'item_pickup_return':
				$wp_query = new WP_Query( array(
					'post_type'   => 'periodent-timeframe',
					'meta_query'  => array(
						'location_ID_clause' => array(
							'key'   => 'location_ID',
							'value' => $this->ID,
						),
						'relation' => 'AND',
						'period_status_type_clause' => array(
							'key'   => 'period_status_type_id',
							'value' => CB2_PeriodStatusType_PickupReturn::$id,
						),
					),
					'posts_per_page' => CB2_ADMIN_COLUMN_POSTS_PER_PAGE,
					'page'           => $current_page,
				) );

				if ( $wp_query->have_posts() ) {
					print( '<ul class="cb2-admin-column-ul">' );
					CB2::the_inner_loop( NULL, $wp_query, 'admin', 'summary' );
					print( '</ul>' );
				} else {
					print( '<div>' . __( 'No Item Availability' ) . '</div>' );
				}
				print( "<div class='cb2-column-actions'>" );
				$post_title   = __( 'Pickup/Return for' ) . " $this->post_title";
				$add_new_text = __( 'add new pickup return times' );
				$add_link     = "admin.php?page=cb2-post-new&location_ID=$this->ID&post_type=periodent-timeframe&period_status_type_id=1&post_title=$post_title";
				print( " <a href='$add_link'>$add_new_text</a>" );
				print( '</div>' );
				break;

			case 'bookings':
				$wp_query = new WP_Query( array(
					'post_type'   => 'periodent-user',
					'meta_query'  => array(
						'location_ID_clause' => array(
							'key'   => 'location_ID',
							'value' => $this->ID,
						),
						'relation' => 'AND',
						'period_status_type_clause' => array(
							'key'   => 'period_status_type_id',
							'value' => CB2_PeriodStatusType_Booked::$id,
						),
					),
					'posts_per_page' => CB2_ADMIN_COLUMN_POSTS_PER_PAGE,
					'page'           => $current_page,
				) );

				if ( $wp_query->have_posts() ) {
					print( '<ul class="cb2-admin-column-ul">' );
					CB2::the_inner_loop( NULL, $wp_query, 'admin', 'summary' );
					print( '</ul>' );
				} else {
					print( '<div>' . __( 'No Bookings' ) . '</div>' );
				}

				print( "<div class='cb2-column-actions'>" );
				if ( current_user_can( 'edit_post', $this->ID ) ) {
					$page       = 'cb2-post-new';
					$booked_ID  = CB2_PeriodStatusType_Booked::bigID();
					$add_new_text = __( 'add new booking' );
					$post_title = __( 'Booking at' ) . " $this->post_title";
					$add_link   = "admin.php?page=$page&location_ID=$this->ID&post_type=periodent-user&period_status_type_ID=$booked_ID&post_title=$post_title";
					print( " <a href='$add_link'>$add_new_text</a>" );
					if ( $wp_query->post_count ) print( ' | ' );
				}

				if ( $wp_query->post_count ) {
					$page       = 'cb2-menu';
					$view_text  = __( 'view in calendar' );
					$view_link  = "admin.php?page=$page&location_ID=$this->ID&period_status_type_ID=$booked_ID";
					print( "<a href='$view_link'>$view_text</a>" );
				}
				print( '</div>' );

				break;

			case 'opening_hours':
				$wp_query = new WP_Query( array(
					'post_type'   => 'periodent-location',
					'meta_query'  => array(
						'location_ID_clause' => array(
							'key'   => 'location_ID',
							'value' => $this->ID,
						),
						'relation' => 'AND',
						'period_status_type_clause' => array(
							'key'   => 'period_status_type_id',
							'value' => CB2_PeriodStatusType_Open::$id,
						),
					),
				) );

				if ( $wp_query->have_posts() ) {
					print( '<ul class="cb2-admin-column-ul">' );
					CB2::the_inner_loop( NULL, $wp_query, 'admin', 'summary' );
					print( '</ul>' );
				} else {
					print( '<div>' . __( 'No Opening Hours' ) . '</div>' );
				}

				// $period_count    = CB2_Query::array_sum( $wp_query->posts, 'period_count' );
				$period_group_count = $wp_query->post_count;
				$opening_hours_text = '';
				$help_text          = '';
				$count_class        = 'ok';
				$link               = 'admin.php?page=';

				switch ( $period_group_count ) {
					case 0:
						// Directly add a / the opening hours
						$help_text          = __( 'No opening hours set yet' );
						$opening_hours_text = __( 'Set the Opening Hours' );
						$page               = 'cb2-post-new';
						$post_type          = 'periodent-location';
						$settings           = array(
							// Non-wizard setup of advanced
							'recurrence_type'       => 'D',
							'recurrence_type_show'  => 'no',
							'CB2_PeriodEntity_Location_metabox_0_show' => 'no',
							'period_status_type_id' => 4,
							'post_title'            => "Opening Hours for $this->post_title",
							'add_new_label'         => "Set Opening Hours for $this->post_title",
						);
						$settings_string    = CB2_Query::implode( '&', $settings );
						$link              .= "$page&location_ID=$this->ID&post_type=$post_type&$settings_string";
						$count_class        = 'warning';
						break;
					case 1:
						// Exactly one opening hour: edit it
						$help_text          = __( '1 opening hours set' );
						$opening_hours_text = __( 'Add a new Opening Hours set' );
						$page               = 'cb2-post-new';
						$post_type          = 'periodent-location';
						$settings           = array(
							// Non-wizard setup of advanced
							'recurrence_type'       => 'D',
							'recurrence_type_show'  => 'no',
							'CB2_PeriodEntity_Location_metabox_0_show' => 'no',
							'period_status_type_id' => 4,
							'post_title'            => "Opening Hours for $this->post_title",
							'add_new_label'         => "Set Opening Hours for $this->post_title",
						);
						$settings_string    = CB2_Query::implode( '&', $settings );
						$link              .= "$page&location_ID=$this->ID&post_type=$post_type&$settings_string";
						break;
					default:
						// Several opening hours created: manage them
						$help_text          = __( 'Number of registered opening periods' );
						$opening_hours_text = __( 'Manage Opening Hours' );
						$page               = 'cb2-opening-hours';
						$link              .= "$page&location_ID=$this->ID";
				}
				$action  = "<span style='white-space:nowrap;'>";
				$action .= "<a href='$link'>$opening_hours_text";
				$action .= " <span class='cb2-usage-count-$count_class' title='$help_text'>$period_group_count</span> ";
				$action .= '</a></span>';

				if ( current_user_can( 'edit_post', $this->ID ) )
					print( $action );
				break;

			case 'address':
				$edit_location_link = $this->get_the_edit_post_url();
				$set_address_text   = __( 'Set the Address' );
				$set_geo_text       = __( 'Set the Geo location' );
				if ( is_null( $this->geo_address ) ) {
					print( "<a href='$edit_location_link'>$set_address_text</a>" );
				} else if ( is_null( $this->geo_latitude ) || is_null( $this->geo_longitude ) ) {
					print( $this->geo_address );
					krumo($this);
					print( "<br/><a href='$edit_location_link'>$set_geo_text</a>" );
				} else {
					print( $this->geo_address );
				}
				break;
		}

		if ( $wp_query ) {
			print( '<div class="cb2-column-pagination">' . paginate_links( array(
				'base'         => 'admin.php%_%',
				'total'        => $wp_query->max_num_pages,
				'current'      => $current_page,
				'format'       => "?$wp_query_page_name=%#%",
			) ) . '</div>' );
		}
	}

	function last_opening_hours_period_group_set() {
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( "select period_group_ID
				from {$wpdb->prefix}cb2_location_period_groups
				where location_ID = %d and period_status_type_ID = %d
				order by period_group_ID desc
				limit 1",
			array( $this->ID, CB2_PeriodStatusType_Open::bigID(), )
		) );
	}

  function get_api_data($version){
		$location_data = array(
			'type' => 'Feature',
			'properties' => array(
				'uid' => get_the_guid($this),
				'name' => get_the_title($this),
				'url' => get_post_permalink($this),
				'address' => $this->geo_address,

			),
			'geometry' => array(
				'type'=> 'Point',
				'coordinates' => [(float)$this->geo_latitude, (float)$this->geo_longitude]
			)
		);
		$location_desc = $this->post_excerpt;
		if($location_desc != NULL){
			$location_data['properties']['description'] = $location_desc;
		}
		// $location_meta = get_post_meta($location);
		// $location_data['longitude'] = $location_meta['geo_longitude'];
		// $location_data['latitude'] = $location_meta['geo_latitude'];
		// $location_data['address'] = $location_meta['geo_address'];
		return $location_data;
  }

	function post_post_update() {
		global $wpdb;

		if ( isset( $_POST['set_default_opening_hours'] ) ) {
			$with_lunch_break   = isset( $_POST['with_lunch_break'] );
			$with_weekend_break = isset( $_POST['with_weekend_break'] );

			if ( CB2_DEBUG_SAVE ) {
				$Class = get_class( $this );
				print( "<div class='cb2-WP_DEBUG'>$Class::post_post_update($this->ID) dependencies:
						add default opening hours: [1/$with_lunch_break/$with_weekend_break]
					</div>"
				);
			}

			$recurrence_sequence = ( $with_weekend_break ? CB2_Week::day_mask( array( 1, 1, 1, 1, 1 ) ) : 0 );

			$opening_hours_text = __( 'Opening Hours' );
			$period_group       = CB2_PeriodGroup::factory( CB2_CREATE_NEW, $opening_hours_text );
			if ( $with_lunch_break ) {
				// Add 2 slots
				$period_group->add_period( CB2_Period::factory(
					CB2_CREATE_NEW, __( 'Morning Opening Hours' ),
					CB2_DateTime::day_start(), CB2_DateTime::lunch_start(),
					CB2_DateTime::today(),
					NULL, 'D', NULL, $recurrence_sequence
				) );
				$period_group->add_period( CB2_Period::factory(
					CB2_CREATE_NEW, __( 'Afternoon Opening Hours' ),
					CB2_DateTime::lunch_end(), CB2_DateTime::day_end(),
					CB2_DateTime::today(),
					NULL, 'D', NULL, $recurrence_sequence
				) );
			} else {
				// Add 1 slot
				$period_group->add_period( CB2_Period::factory(
					CB2_CREATE_NEW, $opening_hours_text,
					CB2_DateTime::day_start(), CB2_DateTime::day_end(),
					CB2_DateTime::today(),
					NULL, 'D', NULL, $recurrence_sequence
				) );
			}

			$location_opening_hours = CB2_PeriodEntity_Location::factory(
				CB2_CREATE_NEW,
				"$this->ID $opening_hours_text",
				$period_group,
				new CB2_PeriodStatusType_Open(),
				TRUE, NULL, NULL,
				$this
			);
			if ( CB2_DEBUG_SAVE ) krumo( $location_opening_hours );
			$location_opening_hours->save();
		}
	}

	function jsonSerialize() {
    return array_merge( parent::jsonSerialize(),
      array(
        'periodinsts' => &$this->periodinsts
    ));
  }
}
