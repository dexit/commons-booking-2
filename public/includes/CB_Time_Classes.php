<?php
// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_TimePostNavigator extends CB_PostNavigator implements JsonSerializable {
  public static $posts_table    = FALSE;
  public static $postmeta_table = FALSE;
  public static $database_table = FALSE;

  function classes() {
    $classes = '';
    if ( $this->is_current ) $classes .= 'cb2-current';
    return $classes;
	}

	function jsonSerialize() {
		throw new Exception( 'CB_TimePostNavigator::jsonSerialize() is a pure abstract method' );
	}
}

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_Year extends CB_TimePostNavigator {
  static $all                   = array();
  static $static_post_type      = 'year';
  public $is_current            = FALSE;
  public static $post_type_args = array(
		'public' => FALSE,
  );

  function post_type() {return self::$static_post_type;}
  public function __toString() {return $this->post_title;}

  protected function __construct( $day ) {
    $this->days = array();
    $this->year = (int) $day->year;
    $this->first_day_num = 365;
    $this->add_day( $day );

    // WP_Post values
    $this->post_title    = $day->year;
    $this->ID            = $day->year;
    $this->post_type     = self::$static_post_type;
    parent::__construct( $this->days );
  }

  static function factory( $day ) {
    // Design Patterns: Factory Singleton with Multiton
    $key = $day->year;
    if ( isset( self::$all[$key] ) ) {
      $object = self::$all[$key];
      $object->add_day( $day );
    } else {
      $object = new self( $day );
      self::$all[$key] = $object;
    }

    return $object;
  }

  function add_day( $day ) {
    if ( $day->year != $this->year ) throw Up();
    $this->days[ $day->dayofyear ] = $day;
    if ( $day->dayofyear < $this->first_day_num ) $this->first_day_num = $day->dayofyear;
    if ( $day->is_current ) $this->is_current = TRUE;
    return $day;
  }

  function pre_days() {
    return $this->first_day_num;
  }

  function jsonSerialize() {
    return [
      'year'          => &$this->year,
      'first_day_num' => &$this->first_day_num,
      'days'          => &$this->days,
    ];
  }
}
CB_Query::register_schema_type( 'CB_Year' );

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_Month extends CB_TimePostNavigator {
  static $all = array();
  static $static_post_type = 'month';
  public $is_current = FALSE;

  function post_type() {return self::$static_post_type;}
  public function __toString() {return $this->post_title;}

  protected function __construct( $day ) {
    $this->days          = array();
    $this->monthinyear   = (int) $day->monthinyear;
    $this->monthname     = $day->monthname;
    $this->first_day_num = 31;
    $this->add_day( $day );

    // WP_Post values
    $this->post_title    = $this->monthname;
    $this->ID            = $day->monthinyear;
    $this->post_type     = self::$static_post_type;
    parent::__construct( $this->days );
  }

  static function factory( $day ) {
    // Design Patterns: Factory Singleton with Multiton
    $key = $day->monthinyear;
    if ( isset( self::$all[$key] ) ) {
      $object = self::$all[$key];
      $object->add_day( $day );
    } else {
      $object = new self( $day );
      self::$all[$key] = $object;
    }

    return $object;
  }

  function pre_days() {
    return $this->first_day_num;
  }

  function add_day( $day ) {
    if ( $day->monthinyear != $this->monthinyear ) throw Up();
    $this->days[ $day->dayinmonth ] = $day;
    if ( $day->dayinmonth < $this->first_day_num ) $this->first_day_num = $day->dayinmonth;
    if ( $day->is_current ) $this->is_current = TRUE;
    return $day;
  }

  function jsonSerialize() {
    return [
      'monthinyear'   => &$this->monthinyear,
      'first_day_num' => &$this->first_day_num,
      'days'          => &$this->days,
    ];
  }
}
CB_Query::register_schema_type( 'CB_Month' );

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_Week extends CB_TimePostNavigator {
  static $all = array();
  static $static_post_type = 'week';
  public $is_current = FALSE;

  function post_type() {return self::$static_post_type;}
  public function __toString() {return $this->post_title;}

  protected function __construct( $day ) {
    $this->days = array();

    $this->weekinyear = (int) $day->weekinyear;
    $this->first_day_num = 7;

    $this->add_day( $day );

    // WP_Post values
    $this->post_title    = "Week $this->weekinyear";
    $this->ID            = $this->weekinyear;
    $this->post_type     = self::$static_post_type;
    parent::__construct( $this->days );
  }

  static function factory( $day ) {
    // Design Patterns: Factory Singleton with Multiton
    $key = $day->weekinyear;
    if ( isset( self::$all[$key] ) ) {
      $object = self::$all[$key];
      $object->add_day( $day );
    } else {
      $object = new self( $day );
      self::$all[$key] = $object;
    }

    return $object;
  }

  function pre_days() {
    return $this->first_day_num;
  }

  function add_day( $day ) {
    if ( $day->weekinyear != $this->weekinyear ) throw new Exception( "day in wrong week [$this->weekinyear]" );
    $this->days[ $day->dayofweek ] = $day;
    if ( $day->dayofweek < $this->first_day_num ) $this->first_day_num = $day->dayofweek;
    if ( $day->is_current ) $this->is_current = TRUE;
    return $day;
  }

  function jsonSerialize() {
    return [
      'weekinyear'    => $this->weekinyear,
      'first_day_num' => $this->first_day_num,
      'days'          => &$this->days
    ];
  }
}
CB_Query::register_schema_type( 'CB_Week' );

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_Day extends CB_TimePostNavigator {
  static $all = array();
  static $static_post_type = 'day';
  public $is_current = FALSE;

  function post_type() {return self::$static_post_type;}
  public function __toString() {return $this->post_title;}

  protected function __construct( $date, $title_format = 'D, M-d' ) {
    $this->periods     = array();

    $this->date         = $date;
    $this->year         = (int) $date->format( 'Y' );
    $this->weekinyear   = (int) $date->format( 'W' ); // Monday start day
    $this->monthinyear  = (int) $date->format( 'n' ); // 1-12
    $this->monthname    = $date->format( 'F' ); // January - December
    $this->dayinmonth   = (int) $date->format( 'j' ); // 0-31 day in month
    $this->dayofweek    = (int) $date->format( 'w' ); // Sunday start day (see below)
    $this->dayofyear    = (int) $date->format( 'z' ); // 0-365
    $this->today        = ( $date->format( 'Y-m-d' ) == (new DateTime())->format( 'Y-m-d' ) );
    $this->is_current   = $this->today;
    $this->title        = $date->format( $title_format );

    // format( 'w' ) is Sunday start day based:
    // http://php.net/manual/en/function.date.php
    if ( $this->dayofweek == 0 ) $this->dayofweek = 7;

    $this->week  = CB_Week::factory(  $this );
    $this->month = CB_Month::factory( $this );
    $this->year  = CB_Year::factory(  $this );

    // WP_Post values
    $this->post_title    = $date->format( $title_format );
    $this->ID            = $this->dayofyear;
    $this->post_type     = self::$static_post_type;
    parent::__construct( $this->periods );
  }

  static function &factory( $date, $title_format = 'D, M-d' ) {
    // Design Patterns: Factory Singleton with Multiton
    $key = $date->format( 'z' );
    if ( isset( self::$all[$key] ) ) $object = self::$all[$key];
    else {
      $object = new self( $date, $title_format );
      self::$all[$key] = $object;
    }

    return $object;
  }

  function add_actions( &$actions ) {
		$actions[ 'view-periods' ] = '<a href="#">View Periods</a>';
	}

  function classes() {
    $classes = parent::classes();

    foreach ( $this->periods as $period ) {
      $classes .= $period->classes_for_day( $this );
    }

    return $classes;
  }

  function add_period( $period ) {
    array_push( $this->periods, $period );
    return $period;
  }

  function jsonSerialize() {
    return [
      'date'        => $this->date->format( CB_Query::$javascript_date_format ),
      'year'        => $this->year,
      'weekinyear'  => $this->weekinyear,
      'monthinyear' => $this->monthinyear,
      'dayinmonth'  => $this->dayinmonth,
      'dayofweek'   => $this->dayofweek,
      'today'       => $this->today,
      'title'       => $this->title,
      'periods'     => &$this->periods
    ];
  }
}

CB_Query::register_schema_type( 'CB_Day' );

