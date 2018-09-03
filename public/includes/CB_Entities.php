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
					'default' => $_GET['user_ID'],
					'options' => CB_Forms::user_options(),
				),
			),
		);
	}

  function post_type() {return self::$static_post_type;}

  protected function __construct( $ID, $user_login = NULL ) {
    $this->perioditems    = array();
		$this->id         = $ID;

    // WP_Post values
    $this->ID         = $ID;
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
    if ( self::$schema == 'with-perioditems' ) $array[ 'periods' ] = &$this->perioditems;

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

  public function __toString() {return (string) $this->ID;}

  protected function __construct( $ID ) {
    $this->perioditems = array();
		$this->id = $ID;

    // WP_Post values
    $this->ID = $ID;

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
    if ( self::$schema == 'with-perioditems' ) $array[ 'periods' ] = &$this->perioditems;

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
  public static $supports = array(
		'title',
		'editor',
		'thumbnail',
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
					'default' => $_GET['location_ID'],
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
					'default' => $_GET['location_ID'],
					'options' => CB_Forms::location_options(),
				),
				array(
					'name'    => __( 'Holidays', 'commons-booking-2' ),
					'id'      => 'holidays',
					'type'    => 'select',
					'default' => $_GET['location_ID'],
					'options' => array(),
				),
				array(
					'name'    => __( 'Opening Hours', 'commons-booking-2' ),
					'id'      => 'opening_hours',
					'type'    => 'select',
					'default' => $_GET['location_ID'],
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

  function render_location_summary(
		CMB2_Field $field,
			$field_escaped_value,
			$object_id,
			$object_type,
			CMB2_Types $field_type_object
	) {
		// location-summary widget supports
		exit();
		return "<div>test</div>";
  }

  function post_type() {return self::$static_post_type;}

  protected function __construct( $ID ) {
    parent::__construct( $ID );
    $this->items = array();

    // WP_Post values
    $this->post_type = self::$static_post_type;
  }

  static function &factory_from_wp_post( $post ) {
		$object = self::factory(
			$post->ID
		);

		CB_Query::copy_all_properties( $post, $object );

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
		$columns['items'] = 'Item Availability';
		$this->move_column_to_end( $columns, 'date' );
		return $columns;
	}

	function custom_columns( $column ) {
		$html = '';
		switch ( $column ) {
			case 'items':
				if ( count( $this->items ) ) {
					$html .= ( '<ul>' );
					foreach ( $this->items as $item ) {
						$edit_link = "?page=cb-post-edit&post=$item->ID&post_type=item&action=edit";
						$html     .= "<li><a href='$edit_link'>" . $item->post_title . '</a></li>';
					}
					$html .= ( '</ul>' );
				} else $html .= ( 'No Items' );
				break;
		}
		return $html;
	}

	function add_item( &$item ) {
    array_push( $this->items, $item );
    return $this;
  }

  function jsonSerialize() {
    return array_merge( parent::jsonSerialize(),
      array(
        'items' => &$this->items
    ));
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
					'default' => $_GET['item_ID'],
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

		CB_Query::copy_all_properties( $post, $object );

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
}
CB_Query::register_schema_type( 'CB_Item' );

