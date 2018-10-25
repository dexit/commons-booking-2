<?php
// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB2_TimePostNavigator extends CB2_PostNavigator implements JsonSerializable {
  public static $posts_table    = FALSE;
  public static $postmeta_table = FALSE;
  public static $database_table = FALSE;
	public $first = FALSE;

  function classes() {
    $classes = '';
    if ( $this->is_current ) $classes .= ' cb2-current';
    if ( $this->first )      $classes .= ' cb2-first';
    return $classes;
	}

	function jsonSerialize() {
		throw new Exception( 'CB2_TimePostNavigator::jsonSerialize() is a pure abstract method' );
	}
}

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB2_Year extends CB2_TimePostNavigator {
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


// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB2_Month extends CB2_TimePostNavigator {
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
		$this->first        = ($this->monthinyear == 1);

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


// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB2_Week extends CB2_TimePostNavigator {
  static $all = array();
  static $static_post_type = 'week';
  public $is_current = FALSE;

  function post_type() {return self::$static_post_type;}
  public function __toString() {return $this->post_title;}

  protected function __construct( $day ) {
    $this->days = array();

    $this->weekinyear = (int) $day->weekinyear;
    $this->first_day_num = 7;
    $this->first        = ($this->weekinyear == 1);

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
    if ( $day->weekinyear != $this->weekinyear )
			throw new Exception( "day in wrong week [$this->weekinyear]" );
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


// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB2_Day extends CB2_TimePostNavigator {
  static $all = array();
  static $static_post_type = 'day';
  public $is_current = FALSE;

  function post_type() {return self::$static_post_type;}
  public function __toString() {return $this->post_title;}

  protected function __construct( $date, $title_format = 'M d' ) {
    $this->perioditems  = array();

    $this->date         = $date;
    $this->year         = (int) $date->format( 'Y' );
    $this->weekinyear   = (int) $date->format( 'W' ); // 1-52 Monday start day
    $this->monthinyear  = (int) $date->format( 'n' ); // 1-12
    $this->monthname    = $date->format( 'F' );       // January - December
    $this->dayinmonth   = (int) $date->format( 'j' ); // 1-31 day in month
    $this->dayofweek    = (int) $date->format( 'w' ); // 0-6 Sunday start day (see below)
    $this->dayofyear    = (int) $date->format( 'z' ); // 0-365
    $this->today        = ( $date->format( CB2_Query::$date_format ) == (new DateTime())->format( CB2_Query::$date_format ) );
    $this->is_current   = $this->today;
    $this->title        = $date->format( $title_format );
    $this->first        = ($this->dayinmonth == 1);

    // format( 'w' ) is Sunday start day based:
    // http://php.net/manual/en/function.date.php
    if ( $this->dayofweek == 0 ) $this->dayofweek = 7;

    $this->week  = CB2_Week::factory(  $this );
    $this->month = CB2_Month::factory( $this );
    $this->year  = CB2_Year::factory(  $this );

    // WP_Post values
    $this->post_title    = $date->format( $title_format );
    $this->ID            = $this->dayofyear + 1;
    $this->post_type     = self::$static_post_type;
    parent::__construct( $this->perioditems );
  }

  static function &factory( $date, $title_format = 'M d' ) {
    // Design Patterns: Factory Singleton with Multiton
    $key = $date->format( 'z' );
    if ( isset( self::$all[$key] ) ) $object = self::$all[$key];
    else {
      $object = new self( $date, $title_format );
      self::$all[$key] = $object;
    }

    return $object;
  }



  function add_actions( &$actions, $post ) {
		$actions[ 'view-periods' ] = '<a href="#">View Periods</a>';
	}

  function classes() {
    $classes = parent::classes();

    foreach ( $this->perioditems as $perioditem ) {
      $classes .= $perioditem->classes_for_day( $this );
    }

    return $classes;
  }

  function add_perioditem( $perioditem ) {
    array_push( $this->perioditems, $perioditem );
    return $perioditem;
  }

  function jsonSerialize() {
    return [
      'date'        => $this->date->format( CB2_Query::$javascript_date_format ),
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
