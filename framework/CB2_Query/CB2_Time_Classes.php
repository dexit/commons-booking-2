<?php
// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB2_TimePostNavigator extends CB2_PostNavigator implements JsonSerializable {
  public static $posts_table    = FALSE;
  public static $postmeta_table = FALSE;
  public static $database_table = FALSE;
  public static $register_post_type = FALSE;
	public $first = FALSE;

	static function max_days() {
		throw new Exception( 'Cannot use max_days() during generation of installation SQL
			because its dependent stored procedure cb2_view_sequence_date is not created yet.' );

		global $wpdb;
		static $max_days = NULL;
		if ( is_null( $max_days ) ) {
			$sql = "SELECT count(*) from {$wpdb->prefix}cb2_view_sequence_date";
			// Allow exceptions to occur if this fails
			$max_days = $wpdb->get_var( "SELECT count(*) from {$wpdb->prefix}cb2_view_sequence_date" );
		}
		return $max_days;
	}

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
  static $static_post_type      = 'yr'; // Cannot use year, because that causes issues
  public $is_current            = FALSE;

  function post_type() {return self::$static_post_type;}
  public function __toString() {return $this->post_title;}

  protected function __construct( $day ) {
    $this->days = array();
    $this->year = (int) $day->date->format( 'Y' ); // e.g. 2017
    $this->first_day_num = 365;
    $this->add_day( $day );

    // WP_Post values
    $this->post_title    = $this->year;
    $this->ID            = $this->year;
    $this->post_type     = self::$static_post_type;
    parent::__construct( $this->days );
  }

  static function factory( $day ) {
    // Design Patterns: Factory Singleton with Multiton
    $key = $day->date->format( 'Y' );
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
    $this->monthinyear   = (int) $day->date->format( 'n' );  // 1-12
    $this->monthname     = $day->date->format( 'F' );        // January - December;
    $this->first_day_num = 31;
    $this->add_day( $day );
		$this->first        = ($this->monthinyear == 1);

    // WP_Post values
    $this->post_title    = __( $this->monthname );
    $this->ID            = $this->monthinyear; // This will repeat across years, but that is not important
    $this->post_type     = self::$static_post_type;
    parent::__construct( $this->days );
  }

  static function factory( $day ) {
    // Design Patterns: Factory Singleton with Multiton
    $key = $day->date->format( 'Y-n' );
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
	private static $days_sunday_start = array( 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' );

  function post_type() {return self::$static_post_type;}
  public function __toString() {return $this->post_title;}

  protected function __construct( $day ) {
    $this->days          = array();
    $this->weekinyear    = self::weekinyear( $day );
    $this->first_day_num = 7;
    $this->first         = ( $this->weekinyear == 1 );

    $this->add_day( $day );

    // WP_Post values
    $this->post_title    = __( 'Week' ) . " $this->weekinyear";
    $this->ID            = $this->weekinyear; // This will repeat across years, but that is not important
    $this->post_type     = self::$static_post_type;

    parent::__construct( $this->days );
  }

  public static function days_of_week() {
		static $days = array();

		if ( ! count( $days ) ) {
			$start_of_week = get_option( 'start_of_week' );
			for ( $i = 0; $i < count( self::$days_sunday_start ); $i++ ) {
				array_push( $days, self::$days_sunday_start[( $i + $start_of_week ) % 7] );
			}
		}

		return $days;
	}

  private static function weekinyear( CB2_Day $day ) {
		// How many start_of_week are there between inclusive 2 and $date?
		// 1  - first (maybe partial) week
		// 53 - last  (maybe partial) week
    return (int) ceil( ( $day->dayofyear - $day->dayofweek ) / 7 ) + 1;
  }

	static function day_mask( Array $days ) {
		// Array( TRUE, FALSE, ... )
		$mask = array();
		if ( count( $days ) < 7 )
			for ( $i = count( $days ); $i < 7; $i++ )
				array_push( $days, FALSE );
		$start_of_week = get_option( 'start_of_week' );
		for ( $i = 1; $i < $start_of_week; $i++ ) array_push( $days, array_shift( $days ) );
		krumo( $days );
		for ( $i = 0; $i < count( $days ); $i++ ) {
			if ( $days[$i] ) array_push( $mask, pow( 2, $i ) );
		}
		krumo( $mask );
		return array_sum( $mask );
	}

  static function factory( $day ) {
    // Design Patterns: Factory Singleton with Multiton
    $year       = $day->date->format( 'Y' );
    $last_year  = $year - 1;
    $weekinyear = self::weekinyear( $day );
    $key        = "$year-$weekinyear";

    // Year Boundaries will create 2 weeks over the boundary...
    // a partial 53 and a partial 1
    // if we already have days in the previous end-of-year week then continue with that one
    if ( $weekinyear == 1 && isset( self::$all["$last_year-53"] ) )
			$key = "$last_year-53";

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

  protected function __construct( CB2_DateTime $date = NULL, String $title_format = NULL ) {
		if ( is_null( $date ) ) $date = new CB2_DateTime();
		else $date = $date->clone();
		$date->clearTime();
    $this->periodinsts  = array();

    // http://php.net/manual/en/function.date.php
    $this->date         = $date;
    $this->dayinmonth   = (int) $date->format( 'j' );        // 1-31 day in month
    $this->dayofweek    = self::dayofweek_adjusted( $date ); // 0-6 WordPress start_of_week start day
    $this->dayofyear    = (int) $date->format( 'z' );        // 0-364
    $this->today        = $date->is( (new CB2_DateTime())->clearTime() );
    $this->is_current   = $this->today;
    $this->title        = $date->format( $title_format ? $title_format : get_option( 'date_format' ) );
    $this->first        = ($this->dayinmonth == 1);

    $this->week         = CB2_Week::factory(  $this );
    $this->month        = CB2_Month::factory( $this );
    $this->year         = CB2_Year::factory(  $this );

    // WP_Post values
    $this->post_title   = $date->format( $title_format ? $title_format : self::no_year_date_format() );
    $this->ID           = $this->dayofyear + 1;
    $this->post_type    = self::$static_post_type;
    $this->post_date    = $date->format( CB2_Query::$date_format );

    parent::__construct( $this->periodinsts );
  }

  static function &factory_from_properties( Array $properties ) {
		if ( ! isset( $properties[ 'date' ] ) )
			throw new Exception( "date required when CB2_Day::factory_from_properties()" );

		$title_format = ( isset( $properties[ 'title_format' ] ) ? $properties[ 'title_format' ] : NULL );
		$date         = new CB2_DateTime( $properties[ 'date' ] );
		return self::factory( $date, $title_format );
  }

  static function day_exists( CB2_DateTime $date ) {
    $key = $date->format( 'Y-z' ); // year-dayofyear: 2019-364
    return isset( self::$all[$key] );
  }

  static function &factory( CB2_DateTime $date, String $title_format = NULL ) {
    // Design Patterns: Factory Singleton with Multiton
    $key = $date->format( 'Y-z' ); // year-dayofyear: 2019-364
    if ( isset( self::$all[$key] ) ) $object = self::$all[$key];
    else {
      $object = new self( $date, $title_format );
      self::$all[$key] = $object;
    }

    return $object;
  }

  static function with_year_date_format( $append_format = NULL ) {
		return get_option( 'date_format' ) . ( $append_format ? " $append_format" : '' );
	}

  static function no_year_date_format( $format = NULL,  $append_format = NULL, $short_month_names = TRUE ) {
		if ( is_null( $format ) ) $format = self::with_year_date_format();
		$format = preg_replace( '/[^a-zA-Z]*Y[^a-zA-Z]*/', '', $format );
		if ( $short_month_names ) $format = preg_replace( '/F/', 'M', $format );
		return $format . ( $append_format ? " $append_format" : '' );
  }

  static function dayofweek_adjusted( $date ) {
		// 0-6 with WordPress start_of_week start day
		// e.g. if Tuesday is the WordPress start of the week, then Tuesday is 0, Monday is 6
    $dayofweek          = (int) $date->format( 'w' ); // 0-6 Sunday start day (see below)
    $start_of_week      = (int) get_option( 'start_of_week', 0 ); // 0-6 Sunday == 0
    return ( $dayofweek - $start_of_week + 7 ) % 7;
  }

  function row_actions( &$actions, $post ) {
		$actions[ 'view-periods' ] = '<a href="#">View Periods</a>';
	}

	function get_the_class_actions() {
		return array(
			'class-example' => 'CB2_Day class action example',
		);
	}

  function classes() {
    $classes = parent::classes();

    foreach ( $this->periodinsts as $periodinst ) {
      $classes .= $periodinst->classes_for_day( $this );
    }

    return $classes;
  }

  function add_periodinst( $periodinst ) {
    array_push( $this->periodinsts, $periodinst );
    return $periodinst;
  }

  function tabs( $edit_form_advanced = FALSE ) {
		return array(
			"cb2-tab-status"  => 'Type of Thing',
			"cb2-tab-objects" => 'Real Stuff',
			"cb2-tab-periodgroup" => 'Period Group',
			"cb2-tab-period"  => 'Time period',
		);
  }

  function jsonSerialize() {
    return [
      'date'        => $this->date->format( CB2_Query::$json_date_format ),
      //'year'        => $this->year->year,
      //'weekinyear'  => $this->week->weekinyear,
      //'monthinyear' => $this->month->monthinyear,
      //'dayinmonth'  => $this->dayinmonth,
      'dayofweek'   => $this->dayofweek,
      'today'       => $this->today,
      'title'       => $this->title,
      'periods'     => &$this->periodinsts
    ];
  }
}
