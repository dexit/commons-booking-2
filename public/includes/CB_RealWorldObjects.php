<?php
// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_User extends CB_PostNavigator implements JsonSerializable {
  public static $all    = array();
  public static $schema = 'with-periods'; //this-only, with-periods

  protected function __construct( &$location, &$item, $ID, $user_login = NULL ) {
    $this->periods    = array();
    parent::__construct( $this->periods );
    $this->location   = &$location;
    $this->item       = &$item;
    $this->ID         = $ID;
    $this->user_login = $user_login;
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

  function get_the_content( $more_link_text = null, $strip_teaser = false ) {
    return "<span>$this->user_login</span>";
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
    $this->ID         = $ID;
    $this->post_title = $post_title;
  }

  function get_the_content( $more_link_text = null, $strip_teaser = false ) {
    return "<span>$this->post_title</span>";
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

  protected function __construct( $ID, $post_title = NULL ) {
    parent::__construct( $ID, $post_title );
    $this->items = array();
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

  protected function __construct( &$location, $ID, $post_title = NULL ) {
    parent::__construct( $ID, $post_title );
    if ( $location ) {
      $this->location = &$location;
      $this->location->add_item( $this );
    }
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
