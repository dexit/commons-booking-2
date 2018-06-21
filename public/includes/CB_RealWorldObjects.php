<?php
// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_User extends CB_PostNavigator implements JsonSerializable {
  public static $all    = array();
  public static $schema = 'with-periods'; //this-only, with-periods
  static $static_post_type     = 'user';

  function post_type() {return self::$static_post_type;}

  protected function __construct( &$location, &$item, $ID, $user_login = NULL ) {
    $this->periods    = array();
    parent::__construct( $this->periods );
    $this->location   = &$location;
    $this->item       = &$item;

    // WP_Post values
    $this->ID         = $ID;
    $this->user_login = $user_login;
    $this->post_title = $user_login;
    $this->post_type  = self::$static_post_type;
  }

  static function factory( &$location, &$item, $ID, $user_login = NULL ) {
    // Design Patterns: Factory Singleton with Multiton
    $key = $ID;

    if ( isset( self::$all[$key] ) ) $object = self::$all[$key];
    else {
      $object = new self( $location, $item, $ID, $user_login );
      self::$all[$key] = $object;
    }

    return $object;
  }

  function add_period( &$period ) {
    array_push( $this->periods, $period );
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

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_Post extends CB_PostNavigator implements JsonSerializable {
  public static $schema = 'with-periods'; //this-only, with-periods

  protected function __construct( $ID, $post_title = NULL ) {
    $this->periods = array();
    parent::__construct( $this->periods );

    // WP_Post values
    $this->ID         = $ID;
    $this->post_title = $post_title;
  }

  function add_period( &$period ) {
    array_push( $this->periods, $period );
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
  public static $all = array();
  static $static_post_type  = 'location';

  function post_type() {return self::$static_post_type;}

  protected function __construct( $ID, $post_title = NULL ) {
    parent::__construct( $ID, $post_title );
    $this->items = array();
    $this->post_type     = self::$static_post_type;
  }

  static function factory( $ID, $post_title = NULL ) {
    // Design Patterns: Factory Singleton with Multiton
    $key = $ID;

    if ( isset( self::$all[$key] ) ) $object = self::$all[$key];
    else {
      $object = new self($ID, $post_title);
      self::$all[$key] = $object;
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

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_Item extends CB_Post implements JsonSerializable {
  public  static $all = array();
  static $static_post_type   = 'item';

  function post_type() {return self::$static_post_type;}

  protected function __construct( &$location, $ID, $post_title = NULL ) {
    parent::__construct( $ID, $post_title );
    if ( $location ) {
      $this->location = &$location;
      $this->location->add_item( $this );
    }
    $this->post_type     = self::$static_post_type;
  }

  static function factory( &$location, $ID, $post_title = NULL ) {
    // Design Patterns: Factory Singleton with Multiton
    $key = $ID;

    if ( isset( self::$all[$key] ) ) $object = self::$all[$key];
    else {
      $object = new self( $location, $ID, $post_title );
      self::$all[$key] = $object;
    }

    return $object;
  }
}
