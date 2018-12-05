<?php
// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB2_WordPress_Entity extends CB2_PostNavigator {
}

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB2_User extends CB2_WordPress_Entity implements JsonSerializable {
  public static $all    = array();
  public static $schema = 'with-perioditems'; //this-only, with-perioditems
  public static $posts_table    = FALSE;
  public static $postmeta_table = FALSE;
  public static $database_table = FALSE;
  static $static_post_type = 'user'; // Pseudo, but required

	static function selector_metabox() {
		return array(
			'title' => __( 'User', 'commons-booking-2' ),
			'show_names' => FALSE,
			'fields' => array(
				array(
					'name'    => __( 'User', 'commons-booking-2' ),
					'id'      => 'user_ID',
					'type'    => 'select',
					'default' => ( isset( $_GET['user_ID'] ) ? $_GET['user_ID'] : NULL ),
					'options' => CB2_Forms::user_options(),
				),
			),
		);
	}

  function post_type() {return self::$static_post_type;}
  public function __toStringFor( $column_data_type, $column_name ) {
		return (string) $this->__toIntFor( $column_data_type, $column_name );
	}

	public function __toIntFor( $column_data_type, $column_name ) {
		// CB2_Post only has 1 id for any data
		// although it should be an _ID column
		return $this->id();
	}
	function id( $why = '' ) {return $this->ID;}

  protected function __construct( $ID, $user_login = NULL ) {
    $this->perioditems    = array();

    if ( ! is_numeric( $ID ) ) throw new Exception( "[$ID] is not numeric for [" . get_class( $this ) . ']' );

    // WP_Post values
    $this->ID         = (int) $ID;
    $this->user_login = $user_login;
    $this->post_title = $user_login;
    $this->post_type  = self::$static_post_type;

    parent::__construct( $this->perioditems );

    self::$all[$ID] = $this;
  }

  static function &factory_from_properties( &$properties, &$instance_container = NULL ) {
		$object = self::factory(
			$properties['ID'],
			$properties['user_login']
		);

		self::copy_all_wp_post_properties( $properties, $object );

		return $object;
  }

  static function factory_current() {
		$cb_user = NULL;
		$wp_user = wp_get_current_user();
		if ( $wp_user instanceof WP_User && $wp_user->ID )
			$cb_user = new self( $wp_user->ID, $wp_user->user_login );
		return $cb_user;
	}

  static function factory( $ID, $user_login = NULL ) {
    // Design Patterns: Factory Singleton with Multiton
    $object = NULL;

    if ( $ID ) {
			if ( isset( self::$all[$ID] ) ) $object = self::$all[$ID];
			else $object = new self( $ID, $user_login );
		}

    return $object;
  }

  function can( $capability ) {
		return user_can( $this->ID, $capability );
  }

  function add_perioditem( &$perioditem ) {
    array_push( $this->perioditems, $perioditem );
  }

  function jsonSerialize() {
    $array = array(
      'ID' => $this->ID,
      'user_login' => $this->user_login,
    );
    if ( self::$schema == 'with-perioditems' )
			$array[ 'perioditems' ] = &$this->perioditems;

    return $array;
  }
}

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB2_Post extends CB2_WordPress_Entity implements JsonSerializable {
  public static $all = array();
  public static $PUBLISH        = 'publish';
  public static $AUTODRAFT      = 'auto-draft';
  public static $schema         = 'with-perioditems'; //this-only, with-perioditems
  public static $posts_table    = FALSE;
  public static $postmeta_table = FALSE;
  public static $database_table = FALSE;
  public static $supports = array(
		'title',
		'editor',
		'thumbnail',
	);
	static $POST_PROPERTIES = array(
		'ID' => FALSE,
		'post_author' => TRUE,     // TRUE == Relevant to native records
		'post_date' => TRUE,
		'post_date_gmt' => FALSE,
		'post_content' => TRUE,
		'post_title' => TRUE,
		'post_excerpt' => FALSE,
		'post_status' => FALSE,
		'comment_status' => FALSE,
		'ping_status' => FALSE,
		'post_password' => FALSE,
		'post_name' => TRUE,
		'to_ping' => FALSE,
		'pinged' => FALSE,
		'post_modified' => TRUE,
		'post_modified_gmt' => FALSE,
		'post_content_filtered' => FALSE,
		'post_parent' => FALSE,
		'guid' => FALSE,
		'menu_order' => FALSE,
		'post_type' => TRUE,
		'post_mime_type' => FALSE,
		'comment_count' => FALSE,
		'filter' => FALSE,
	);

  public function __toStringFor( $column_data_type, $column_name ) {
		return (string) $this->__toIntFor( $column_data_type, $column_name );
	}

	public function __toIntFor( $column_data_type, $column_name ) {
		// CB2_Post only has 1 id for any data
		// although it should be an _ID column
		return $this->id();
	}

	function id( $why = '' ) {return $this->ID;}

  protected function __construct( $ID ) {
    $this->perioditems = array();

    if ( ! is_numeric( $ID ) ) throw new Exception( "[$ID] is not numeric for [" . get_class( $this ) . ']' );

    // WP_Post values
    $this->ID = (int) $ID;

    parent::__construct( $this->perioditems );

    self::$all[$ID] = $this;
  }

  function add_perioditem( &$perioditem ) {
    array_push( $this->perioditems, $perioditem );
  }

  function get_field_this( $class = '', $date_format = 'H:i' ) {
		$permalink = get_the_permalink( $this );
		return "<a href='$permalink' class='$class' title='view $this->post_title'>$this->post_title</a>";
	}

	function summary() {
		return ucfirst( $this->post_type() ) . "($this->ID)";
	}

  function jsonSerialize() {
    $array = array(
      'ID' => $this->ID,
      'post_title' => $this->post_title,
    );
    if ( self::$schema == 'with-perioditems' )
			$array[ 'perioditems' ] = &$this->perioditems;

    return $array;
  }
}

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB2_Location extends CB2_Post implements JsonSerializable {
  static $static_post_type  = 'location';
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

	// static function metaboxes() {
	// 	return array(
	// 		array(
	// 			'title'      => __( 'Icon', 'commons-booking-2' ),
	// 			'context'    => 'side',
	// 			'show_names' => FALSE,
	// 			'fields'     => array(
	// 				array(
	// 					'name' => __( 'Icon', 'commons-booking-2' ),
	// 					'id'   => 'location_icon',
	// 					'type' => 'icon',
	// 					'desc' => 'Used in Maps.',
	// 					'options' => array(
	// 						'paths' => array(
	// 							'http://www.flaticon.com/packs/holiday-travelling-3',
	// 						),
	// 					),
	// 				),
	// 			),
	// 		),
	// 	);
	// }

  function post_type() {return self::$static_post_type;}

  protected function __construct( $ID ) {
    parent::__construct( $ID );

    // WP_Post values
    $this->post_type = self::$static_post_type;
  }

  static function &factory_from_properties( &$properties, &$instance_container = NULL ) {
		$object = self::factory(
			$properties['ID']
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

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB2_Item extends CB2_Post implements JsonSerializable {
	// TODO: Extend CB2_Item to integrate with all / new custom post_types
	// can integrate with the commonsbooking
	// For example: a separate plugin which creates a room post_type should be then bookable
	// by presenting a CB2 list of registered post_types and selecting which should be bookable
	// and eventually a CB2 UI allowing new ones with customizeable supports
  static $static_post_type   = 'item';
  public static $post_type_args = array(
		'menu_icon' => 'dashicons-video-alt',
  );
	static function selector_metabox() {
		return array(
			'title' => __( 'Item', 'commons-booking-2' ),
			'show_names' => FALSE,
			'fields' => array(
				array(
					'name'    => __( 'Item', 'commons-booking-2' ),
					'id'      => 'item_ID',
					'type'    => 'select',
					'default' => ( isset( $_GET['item_ID'] ) ? $_GET['item_ID'] : NULL ),
					'options' => CB2_Forms::item_options(),
				),
			),
		);
	}

  function post_type() {return self::$static_post_type;}

  protected function __construct( $ID ) {
    parent::__construct( $ID );

    // WP_Post values
    $this->post_type = self::$static_post_type;
  }

  static function &factory_from_properties( &$properties, &$instance_container = NULL ) {
		$object = self::factory(
			$properties['ID']
		);

		self::copy_all_wp_post_properties( $properties, $object );

		return $object;
  }

  static function &factory( $ID ) {
    // Design Patterns: Factory Singleton with Multiton
    $object = NULL;

    if ( $ID ) {
			if ( isset( self::$all[$ID] ) ) $object = self::$all[$ID];
			else $object = new self( $ID );
		}

    return $object;
  }

	function get_the_after_content() {
		// Booking form
		$Class       = get_class( $this );
		$ID          = $this->ID;
		$form_action = '';
		$do_action   = 'book';
		$submit      = __( 'book the' ) . " $this->post_title";
		$name        = __( 'Booking of' ) . " $this->post_title";
		$display_strategy  = 'CB2_SingleItemAvailability';

		// TODO: WP_Query of the shortcode needs to be configurable
		// package a form plugin with CB2, e.g. ContactForm 7
		// e.g. default period to show
		// package Query Wrangler with CB2
		// POC already done
		return "<form action='$form_action' method='POST'><div>
				<input type='hidden' name='name' value='$name' />
				<input type='hidden' name='do_action' value='$Class::$do_action' />
				<input type='hidden' name='do_action_post_ID' value='$ID' />
				<input type='submit' name='submit' value='$submit' />
				[cb2_calendar view_mode=Week display-strategy=$display_strategy]
				<input type='submit' name='submit' value='$submit' />
			</div></form>
		";
	}

  function do_action_book( CB2_User $user, Array $values ) {
		// The booking times are based on the perioditems selected
		if ( ! isset( $values['perioditem-timeframes'] ) ) {
			krumo( $values );
			throw new Exception( "perioditem-timeframes required during [$action]" );
		}

		// Book these availabilities
		// TODO: should these bookings be combined? (settings)
		$available_perioditems = $values['perioditem-timeframes'];
		$name                  = __( 'Booking' );
		$copy_period_group     = TRUE;      // Default
		$count                 = count( $available_perioditems );

		if ( isset( $values['name'] ) )
			 $name = str_replace( __( 'available' ), __( 'booking' ), $values['name'] );

		foreach ( $available_perioditems as $available_perioditem ) {
			$periodentity_booking = CB2_PeriodEntity_Timeframe_User::factory_booked_from_available_timeframe_item(
				$available_perioditem,
				$user,
				$name,
				$copy_period_group
			);
			// Create objects only (e.g. period_status_type will not be updated),
			// and fire wordpress post events
			$periodentity_booking->save();
		}

		return "<div>processed ($count) perioditem availabile in to bookings</div>";
  }

  function manage_columns( $columns ) {
		$columns['availability'] = 'Availability <a href="admin.php?page=cb2-timeframes">view all</a>';
		$columns['bookings']     = 'Bookings <a href="admin.php?page=cb2-bookings">view all</a>';
		$this->move_column_to_end( $columns, 'date' );
		return $columns;
	}

	function custom_columns( $column ) {
		$wp_query_page_name = "paged-column-$column";
		$current_page       = ( isset( $_GET[$wp_query_page_name] ) ? $_GET[$wp_query_page_name] : 1 );
		$has_locations      = count( CB2_forms::location_options() );

		switch ( $column ) {
			case 'availability':
				$wp_query           = new WP_Query( array(
					'post_type'   => 'periodent-timeframe',
					'meta_query'  => array(
						'item_ID_clause' => array(
							'key'   => 'item_ID',
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
				$page         = 'cb2-post-new';
				$add_new_text = ( 'add new item availability' );
				$post_title   = __( 'Availability of' ) . " $this->post_title";
				$add_link     = "admin.php?page=$page&item_ID=$this->ID&post_type=periodent-timeframe&period_status_type_id=1&post_title=$post_title";
				if ( $has_locations )
					print( "<a href='$add_link'>$add_new_text</a>" );
				else print( '<span class="cb2-no-data-notice">' . __( 'Add a Location first' ) . '</span>' );
				print( '</div>' );
				break;

			case 'bookings':
				$wp_query = new WP_Query( array(
					'post_type'   => 'periodent-user',
					'meta_query'  => array(
						'item_ID_clause' => array(
							'key'   => 'item_ID',
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
				$post_title = __( 'Booking of' ) . " $this->post_title";
				$add_new_booking_text = __( 'add new booking' );
				if ( $has_locations ) {
					$add_link   = "admin.php?page=$page&item_ID=$this->ID&post_type=periodent-user&period_status_type_id=2&post_title=$post_title";
					print( " <a href='$add_link'>$add_new_booking_text</a>" );
					$page       = 'cb2-calendar';
					$view_booking_text = __( 'view in calendar' );
					$view_link  = "admin.php?page=$page&item_ID=$this->ID&period_status_type_id=2";
					print( " | <a href='$view_link'>$view_booking_text</a>" );
				} else print( '<span class="cb2-no-data-notice">' . __( 'Add a Location first' ) . '</span>' );
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

	function row_actions( &$actions, $post ) {
		$wp_query = new WP_Query( array(
			'post_type'   => 'periodent-user',
			'meta_query'  => array(
				'item_clause' => array(
					'key'   => 'item_ID',
					'value' => $this->ID,
				),
				'relation' => 'AND',
				'period_status_type_clause' => array(
					'key'   => 'period_status_type_id',
					'value' => CB2_PeriodStatusType_Repair::$id,
				),
			),
		) );
		$period_count = $wp_query->post_count;

		$action = "<span style='white-space:nowrap;'><a href='admin.php?page=cb2-repairs&item_ID=$this->ID'>Repairs";
		if ( $period_count )
			$action .= " <span class='cb2-usage-count-warning' title='Number of registered repair periods'>$period_count</span> ";
		$action .= '</a></span>';

		$actions[ 'manage_repairs' ] = $action;
	}
}

