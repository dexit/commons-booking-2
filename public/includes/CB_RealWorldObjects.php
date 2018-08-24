<?php
// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_User extends CB_PostNavigator implements JsonSerializable {
  public static $all    = array();
  public static $schema = 'with-periods'; //this-only, with-periods
  public function __toString() {return $this->ID;}
  static $static_post_type     = 'user';

  function post_type() {return self::$static_post_type;}

  protected function __construct( $ID, $user_login = NULL ) {
    $this->periods    = array();
		$this->id         = $ID;

    // WP_Post values
    $this->ID         = $ID;
    $this->user_login = $user_login;
    $this->post_title = $user_login;
    $this->post_type  = self::$static_post_type;

    parent::__construct( $this->periods );

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

  function add_period( &$period ) {
    array_push( $this->periods, $period );
  }

	function is( $user ) {
		return ( $user instanceof CB_User && $user->ID == $this->ID );
	}

  function jsonSerialize() {
    $array = array(
      'ID' => $this->ID,
      'user_login' => $this->user_login,
    );
    if ( self::$schema == 'with-periods' ) $array[ 'periods' ] = &$this->periods;

    return $array;
  }
}
CB_Query::register_schema_type( 'CB_User' );

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_Post extends CB_PostNavigator implements JsonSerializable {
  public static $all = array();
  public static $schema         = 'with-periods'; //this-only, with-periods
  public static $posts_table    = FALSE;
  public static $postmeta_table = FALSE;
  public static $database_table = FALSE;

  public function __toString() {return (string) $this->ID;}

  protected function __construct( $ID ) {
    $this->periods = array();
		$this->id = $ID;

    // WP_Post values
    $this->ID = $ID;

    parent::__construct( $this->periods );

    self::$all[$ID] = $this;
  }

  function add_period( &$period ) {
    array_push( $this->periods, $period );
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
    if ( self::$schema == 'with-periods' ) $array[ 'periods' ] = &$this->periods;

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
		'holidays',
		'thumbnail',
		'custom-fields',
		'post-formats',
	);

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

