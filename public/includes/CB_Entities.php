<?php
// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_User extends CB_PostNavigator implements JsonSerializable {
  public static $all    = array();
  public static $schema = 'with-perioditems'; //this-only, with-perioditems
  public function __toString() {return $this->ID;}
  static $static_post_type     = 'user';
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
					'options' => CB_Forms::user_options(),
				),
			),
		);
	}

  function post_type() {return self::$static_post_type;}
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

  static function factory( $ID, $user_login = NULL ) {
    // Design Patterns: Factory Singleton with Multiton
    $object = NULL;

    if ( ! is_null( $ID ) ) {
			if ( isset( self::$all[$ID] ) ) $object = self::$all[$ID];
			else $object = new self( $ID, $user_login );
		}

    return $object;
  }

  function add_perioditem( &$perioditem ) {
    array_push( $this->perioditems, $perioditem );
  }

	function is( $user ) {
		return ( $user instanceof CB_User && $user->ID == $this->ID );
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
CB_Query::register_schema_type( 'CB_User' );

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_Post extends CB_PostNavigator implements JsonSerializable {
  public static $all = array();
  public static $schema         = 'with-perioditems'; //this-only, with-perioditems
  public static $posts_table    = FALSE;
  public static $postmeta_table = FALSE;
  public static $database_table = FALSE;
  public static $supports = array(
		'title',
		'editor',
		'thumbnail',
	);

  public function __toString() {return (string) $this->ID;}
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

	function get_the_content() {
		return property_exists( $this, 'post_content' ) ? $this->post_content : '';
	}

	function is( $post ) {
		return ( $post instanceof CB_Post && $post->ID == $this->ID );
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
class CB_Location extends CB_Post implements JsonSerializable {
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
					'options' => CB_Forms::location_options(),
				),
			),
		);
	}

	static function summary_metabox() {
		// TODO: CB_Location::summary_metabox()
		return array(
			'title' => __( 'Location Summary', 'commons-booking-2' ),
			'context' => 'side',
			'show_names' => FALSE,
			'fields' => array(
				array(
					'name'    => __( 'Location', 'commons-booking-2' ),
					'id'      => 'location_ID',
					'type'    => 'select',
					'default' => ( isset( $_GET['location_ID'] ) ? $_GET['location_ID'] : NULL ),
					'options' => CB_Forms::location_options(),
				),
				array(
					'name'    => __( 'Holidays', 'commons-booking-2' ),
					'id'      => 'holidays',
					'type'    => 'select',
					'default' => ( isset( $_GET['location_ID'] ) ? $_GET['location_ID'] : NULL ),
					'options' => array(),
				),
				array(
					'name'    => __( 'Opening Hours', 'commons-booking-2' ),
					'id'      => 'opening_hours',
					'type'    => 'select',
					'default' => ( isset( $_GET['location_ID'] ) ? $_GET['location_ID'] : NULL ),
					'options' => array(),
				),
				array(
					'name'    => __( '<a href="#">edit</a>', 'commons-booking-2' ),
					'id'      => 'edit',
					'type'    => 'title',
				),
			),
		);
	}

	static function metaboxes() {
		return array(
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
	}

  function post_type() {return self::$static_post_type;}

  protected function __construct( $ID ) {
    parent::__construct( $ID );

    // WP_Post values
    $this->post_type = self::$static_post_type;
  }

  static function &factory_from_wp_post( $post ) {
		$object = self::factory(
			$post->ID
		);

		CB_Query::copy_all_wp_post_properties( $post, $object );

		return $object;
  }

  static function factory( $ID ) {
    // Design Patterns: Factory Singleton with Multiton
    $object = NULL;
    $key    = $ID;

    if ( ! is_null( $ID ) ) {
			if ( isset( self::$all[$ID] ) ) $object = self::$all[$ID];
			else $object = new self( $ID );
		}

    return $object;
  }

  function manage_columns( $columns ) {
		$columns['item_availability'] = 'Item Availability';
		$columns['bookings']          = 'Bookings';
		$this->move_column_to_end( $columns, 'date' );
		return $columns;
	}

	function custom_columns( $column ) {
		global $post;

		$html = '';
		switch ( $column ) {
			case 'item_availability':
				$wp_query = new WP_Query( array(
					'post_type'   => 'periodent-timeframe',
					'meta_query'  => array(
						'location_clause' => array(
							'key'   => 'location_ID',
							'value' => $this->ID,
						),
						/*
						// TODO: this does not work because the ID can change
						// and we have no _id metadata
						'period_status_type_clause' => array(
							'key'   => 'period_status_type_ID',
							'value' => CB2_PERIOD_STATUS_TYPE_AVAILABLE,
						),
						*/
					),
				) );

				if ( $wp_query->have_posts() ) {
					$html      .= '<ul>';
					$outer_post = $post;
					while ( $wp_query->have_posts() ) {
						$wp_query->the_post();
						$html .= '<li>' . the_summary() . '</li>';
					}
					$post  = &$outer_post;
					$html .= '</ul>';
				} else {
					$html .= __( 'No Item Availability' );
				}
				$html .= " <a href='?page=cb-post-new&location_ID=$this->ID&post_type=periodent-timeframe&period_status_type_ID=100000001'>add</a>";
				break;

			case 'bookings':
				$wp_query = new WP_Query( array(
					'post_type'   => 'periodent-user',
					'meta_query'  => array(
						'location_clause' => array(
							'key'   => 'location_ID',
							'value' => $this->ID,
						),
						/*
						// TODO: this does not work because the ID can change
						// and we have no _id metadata
						'period_status_type_clause' => array(
							'key'   => 'period_status_type_id',
							'value' => CB2_PERIOD_STATUS_TYPE_AVAILABLE,
						),
						*/
					),
				) );

				if ( $wp_query->have_posts() ) {
					$html      .= '<ul>';
					$outer_post = $post;
					while ( $wp_query->have_posts() ) {
						$wp_query->the_post();
						$html .= '<li>' . the_summary() . '</li>';
					}
					$post  = &$outer_post;
					$html .= '</ul>';
				} else {
					$html .= __( 'No Bookings' );
				}
				$html .= " <a href='?page=cb-post-new&location_ID=$this->ID&post_type=periodent-user&period_status_type_ID=100000002'>add</a>";
				break;
		}
		return $html;
	}

  function jsonSerialize() {
    return array_merge( parent::jsonSerialize(),
      array(
        'perioditems' => &$this->perioditems
    ));
  }

	function add_actions( &$actions, $post ) {
		$wp_query = new WP_Query( array(
			'post_type'   => 'periodent-location',
			'meta_query'  => array(
				'item_clause' => array(
					'key'   => 'location_ID',
					'value' => $this->ID,
				),
				'period_status_type_clause' => array(
					'key'   => 'period_status_type_name',
					'value' => 'open',
				),
			),
		) );
		$period_count = $wp_query->post_count;

		$action = "<span style='white-space:nowrap;'><a href='admin.php?page=cb2-opening-hours&location_ID=$this->ID'>Opening Hours";
		if ( $period_count != 1 )
			$action .= " <span class='cb2-usage-count' title='Number of registered opening periods'>$period_count</span> ";
		$action .= '</a></span>';

		$actions[ 'manage_opening_hours' ] = $action;
	}
}
CB_Query::register_schema_type( 'CB_Location' );

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_Item extends CB_Post implements JsonSerializable {
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
					'options' => CB_Forms::item_options(),
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

  static function &factory_from_wp_post( $post ) {
		$object = self::factory(
			$post->ID
		);

		CB_Query::copy_all_wp_post_properties( $post, $object );

		return $object;
  }

  static function &factory( $ID ) {
    // Design Patterns: Factory Singleton with Multiton
    $object = NULL;

    if ( ! is_null( $ID ) ) {
			if ( isset( self::$all[$ID] ) ) $object = self::$all[$ID];
			else $object = new self( $ID );
		}

    return $object;
  }

  function manage_columns( $columns ) {
		$columns['availability'] = 'Availability';
		$columns['bookings']     = 'Bookings';
		$this->move_column_to_end( $columns, 'date' );
		return $columns;
	}

	function custom_columns( $column ) {
		global $post;

		$html = '';
		switch ( $column ) {
			case 'availability':
				$wp_query = new WP_Query( array(
					'post_type'   => 'periodent-timeframe',
					'meta_query'  => array(
						'location_clause' => array(
							'key'   => 'item_ID',
							'value' => $this->ID,
						),
						/*
						// TODO: this does not work because the ID can change
						// and we have no _id metadata
						'period_status_type_clause' => array(
							'key'   => 'period_status_type_id',
							'value' => CB2_PERIOD_STATUS_TYPE_AVAILABLE,
						),
						*/
					),
				) );

				if ( $wp_query->have_posts() ) {
					$html      .= '<ul>';
					$outer_post = $post;
					while ( $wp_query->have_posts() ) {
						$wp_query->the_post();
						$html .= '<li>' . the_summary() . '</li>';
					}
					$post  = &$outer_post;
					$html .= '</ul>';
				} else {
					$html .= __( 'No Item Availability' );
				}
				$html .= " <a href='?page=cb-post-new&item_ID=$this->ID&post_type=periodent-timeframe&period_status_type_ID=100000001'>add</a>";
				break;

			case 'bookings':
				$wp_query = new WP_Query( array(
					'post_type'   => 'periodent-user',
					'meta_query'  => array(
						'item_clause' => array(
							'key'   => 'item_ID',
							'value' => $this->ID,
						),
						/*
						// TODO: this does not work because the ID can change
						// and we have no _id metadata
						'period_status_type_clause' => array(
							'key'   => 'period_status_type_id',
							'value' => CB2_PERIOD_STATUS_TYPE_AVAILABLE,
						),
						*/
					),
				) );

				if ( $wp_query->have_posts() ) {
					$html      .= '<ul>';
					$outer_post = $post;
					while ( $wp_query->have_posts() ) {
						$wp_query->the_post();
						$html .= '<li>' . the_summary() . '</li>';
					}
					$post  = &$outer_post;
					$html .= '</ul>';
				} else {
					$html .= __( 'No Bookings' );
				}
				$html .= " <a href='?page=cb-post-new&item_ID=$this->ID&post_type=periodent-user&period_status_type_ID=100000002'>add</a>";
				break;
		}
		return $html;
	}

	function add_actions( &$actions, $post ) {
		$wp_query = new WP_Query( array(
			'post_type'   => 'periodent-user',
			'meta_query'  => array(
				'item_clause' => array(
					'key'   => 'item_ID',
					'value' => $this->ID,
				),
				'period_status_type_clause' => array(
					'key'   => 'period_status_type_name',
					'value' => 'repair',
				),
			),
		) );
		$period_count = $wp_query->post_count;

		$action = "<span style='white-space:nowrap;'><a href='admin.php?page=cb2-repairs&item_ID=$this->ID'>Repairs";
		if ( $period_count )
			$action .= " <span class='cb2-usage-count' title='Number of registered repair periods'>$period_count</span> ";
		$action .= '</a></span>';

		$actions[ 'manage_repairs' ] = $action;
	}
}
CB_Query::register_schema_type( 'CB_Item' );

