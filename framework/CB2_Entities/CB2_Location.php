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
	static $static_post_type  = 'location';
	public static $rewrite   = array( 'slug' => 'location' );
  public static $post_type_args = array(
		'menu_icon' => 'dashicons-admin-tools',
	);

	static function selector_metabox() {
		return array(
			'title' => __( 'Location', 'commons-booking-2' ),
			'show_names' => FALSE,
			'fields' => array(
				array(
					'name'    => __( 'Location', 'commons-booking-2' ),
					'id'      => 'location_ID',
					'type'    => 'select',
					'default' => ( isset( $_GET['location_ID'] ) ? $_GET['location_ID'] : NULL ),
					'options' => CB2_Forms::location_options(),
				),
			),
		);
	}

	static function metaboxes() {

		$metaboxes = array(
			array(
				'title'      => __( 'Icon', 'commons-booking-2' ),
				'context'    => 'side',
				'show_names' => FALSE,
				'fields'     => array(
					array(
						'name' => __( 'Icon', 'commons-booking-2' ),
						'id'   => 'location_icon',
						'type' => 'icon',
						'desc' => 'Used in Maps.',
						'options' => array(
							'paths' => array(
								'http://www.flaticon.com/packs/holiday-travelling-3',
							),
						),
					),
				),
			),
		);
		return apply_filters('cb2_location_metaboxes', $metaboxes);
	}

  function post_type() {return self::$static_post_type;}

  protected function __construct( $ID ) {
    parent::__construct( $ID );

    // WP_Post values
    $this->post_type = self::$static_post_type;
  }

  static function &factory_from_properties( &$properties, &$instance_container = NULL, $force_properties = FALSE ) {
		$object = self::factory(
			( isset( $properties['location_ID'] ) ? $properties['location_ID'] : $properties['ID'] )
		);

		self::copy_all_wp_post_properties( $properties, $object );

		return $object;
  }

  static function factory( $ID ) {
    // Design Patterns: Factory Singleton with Multiton
    $object = NULL;
    $key    = $ID;

    if ( $ID ) {
			if ( isset( self::$all[$ID] ) ) $object = self::$all[$ID];
			else $object = new self( $ID );
		}

    return $object;
  }

	function get_the_after_content() {
		$ID     = $this->ID;
		return "[cb2_calendar location_id=$ID]";
	}

  function manage_columns( $columns ) {
		$columns['item_availability'] = 'Item Availability <a href="admin.php?page=cb2-timeframes">view all</a>';
		$columns['bookings']          = 'Bookings <a href="admin.php?page=cb2-bookings">view all</a>';
		$this->move_column_to_end( $columns, 'date' );
		return $columns;
	}

	function custom_columns( $column ) {
		$wp_query_page_name = "paged-column-$column";
		$current_page       = ( isset( $_GET[$wp_query_page_name] ) ? $_GET[$wp_query_page_name] : 1 );

		switch ( $column ) {
			case 'item_availability':
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
							'value' => CB2_PeriodStatusType_Available::$id,
						),
					),
					'posts_per_page' => CB2_ADMIN_COLUMN_POSTS_PER_PAGE,
					'page'           => $current_page,
				) );

				if ( $wp_query->have_posts() ) {
					print( '<ul class="cb2-admin-column-ul">' );
					CB2::the_inner_loop( $wp_query, 'admin', 'summary' );
					print( '</ul>' );
				} else {
					print( '<div>' . __( 'No Item Availability' ) . '</div>' );
				}
				print( "<div class='cb2-column-actions'>" );
				$post_title   = __( 'Availability at' ) . " $this->post_title";
				$add_new_text = __( 'add new item availability' );
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
					CB2::the_inner_loop( $wp_query, 'admin', 'summary' );
					print( '</ul>' );
				} else {
					print( '<div>' . __( 'No Bookings' ) . '</div>' );
				}
				print( "<div class='cb2-column-actions'>" );
				$page       = 'cb2-post-new';
				$add_new_text = __( 'add new booking' );
				$post_title = __( 'Booking at' ) . " $this->post_title";
				$add_link   = "admin.php?page=$page&location_ID=$this->ID&post_type=periodent-user&period_status_type_id=2&post_title=$post_title";
				print( " <a href='$add_link'>$add_new_text</a>" );
				$page       = 'cb2-calendar';
				$view_text  = __( 'view in calendar' );
				$view_link  = "admin.php?page=$page&location_ID=$this->ID&period_status_type_id=2";
				print( " | <a href='$view_link'>$view_text</a>" );
				print( '</div>' );
				break;
		}

		print( '<div class="cb2-column-pagination">' . paginate_links( array(
			'base'         => 'admin.php%_%',
			'total'        => $wp_query->max_num_pages,
			'current'      => $current_page,
			'format'       => "?$wp_query_page_name=%#%",
		) ) . '</div>' );
	}

  function jsonSerialize() {
    return array_merge( parent::jsonSerialize(),
      array(
        'perioditems' => &$this->perioditems
    ));
  }

	function row_actions( &$actions, $post ) {
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
		$period_count = $wp_query->post_count;
		$count_ok           = 'warning';
		if ( $period_count ) $count_ok = 'ok';
		$page               = 'cb2-opening-hours';
		$help_text          = __( 'Number of registered opening periods' );
		$opening_hours_text = __( 'Opening Hours' );
		$action  = "<span style='white-space:nowrap;'>";
		$action .= "<a href='admin.php?page=$page&location_ID=$this->ID'>$opening_hours_text";
		$action .= " <span class='cb2-usage-count-$count_ok' title='$help_text'>$period_count</span> ";
		$action .= '</a></span>';

		$actions[ 'manage_opening_hours' ] = $action;
	}
}
