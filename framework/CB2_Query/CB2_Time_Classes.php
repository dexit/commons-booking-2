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
    $this->year = (int) $day->date->format( 'Y' ); // e.g. 2017
    $ID         = $this->year;
    $this->days = array();
    $this->first_day_num = 365;
    $this->add_day( $day );

    parent::__construct( $ID, $this->days );
  }

  static function factory( $day ) {
    // Design Patterns: Factory Singleton with Multiton
    $ID     = (int) $day->date->format( 'Y' );
		$object = CB2_PostNavigator::createInstance( __class__, func_get_args(), $ID );
    $object->add_day( $day );
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
		// This will repeat across years, but that is not important
    $this->monthinyear   = (int) $day->date->format( 'n' );  // 1-12
    $ID                  = $this->monthinyear;
    $this->days          = array();
    $this->monthname     = $day->date->format( 'F' );        // January - December;
    $this->first_day_num = 31;
    $this->add_day( $day );
		$this->first        = ($this->monthinyear == 1);

    // WP_Post values
    $this->post_title    = __( $this->monthname );

    parent::__construct( $ID, $this->days );
  }

  static function factory( $day ) {
    // Design Patterns: Factory Singleton with Multiton
    $ID = (int) $day->date->format( 'Y-n' );
		$object = CB2_PostNavigator::createInstance( __class__, func_get_args(), $ID );
		$object->add_day( $day );
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

	protected static function generate_ID( CB2_Day $day ) {
    $year       = (int) $day->date->format( 'Y' );
    $weekinyear = self::weekinyear( $day );
    $ID         = $year * 53 + $weekinyear;

    // Year Boundaries will create 2 weeks over the boundary...
    // a partial 53 and a partial 1
    // if we already have days in the previous end-of-year week then continue with that one
    if ( $weekinyear == 1 ) {
			$last_year    = $year - 1;
			$last_year_ID = $last_year * 53 + 53;
			if ( isset( self::$all[$last_year_ID] ) )
				$ID = $last_year_ID;
		}

    return $ID;
  }

  protected function __construct( CB2_Day $day ) {
		// This will repeat across years, but that is not important
    $ID                  = self::generate_ID( $day );
    $this->weekinyear    = self::weekinyear( $day );
    $this->days          = array();
    $this->first_day_num = 7;
    $this->first         = ( $this->weekinyear == 1 );

    // WP_Post values
    $this->post_title    = __( 'Week' ) . " $this->weekinyear";

    parent::__construct( $ID, $this->days );
  }

  static function factory( CB2_Day $day ) {
		$week = CB2_PostNavigator::createInstance( __class__, func_get_args(), self::generate_ID( $day ) );
		$week->add_day( $day );
		return $week;
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
		for ( $i = 0; $i < count( $days ); $i++ ) {
			if ( $days[$i] ) array_push( $mask, pow( 2, $i ) );
		}
		return array_sum( $mask );
	}

  function pre_days() {
    return $this->first_day_num;
  }

  function add_day( CB2_Day $day ) {
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

  protected static function generate_ID( CB2_DateTime $date ) {
		return $date->format( 'Y' ) * 365 + $date->format( 'z' );
  }

  protected function __construct( CB2_DateTime $date = NULL, String $title_format = NULL ) {
		if ( is_null( $date ) ) $date = new CB2_DateTime();
		else $date = $date->clone();
		$date->clearTime();
    $this->periodinsts  = array();


    // http://php.net/manual/en/function.date.php
    $ID                 = self::generate_ID( $date );
    $this->dayofyear    = (int) $date->format( 'z' );        // 0-364
    $this->date         = $date;
    $this->dayinmonth   = (int) $date->format( 'j' );        // 1-31 day in month
    $this->dayofweek    = self::dayofweek_adjusted( $date ); // 0-6 WordPress start_of_week start day
    $this->today        = $date->is( (new CB2_DateTime())->clearTime() );
    $this->is_current   = $this->today;
    $this->title        = $date->format( $title_format ? $title_format : get_option( 'date_format' ) );
    $this->first        = ( $this->dayinmonth == 1 );

    $this->week         = CB2_Week::factory(  $this );
    $this->month        = CB2_Month::factory( $this );
    $this->year         = CB2_Year::factory(  $this );

    // WP_Post values
    $this->post_title   = $date->format( $title_format ? $title_format : self::no_year_date_format() );
    $this->post_date    = $date->format( CB2_Query::$date_format );

    parent::__construct( $ID, $this->periodinsts );
  }

  static function factory_from_properties( Array $properties ) {
		if ( ! isset( $properties[ 'date' ] ) )
			throw new Exception( "date required when CB2_Day::factory_from_properties()" );

		$title_format = ( isset( $properties[ 'title_format' ] ) ? $properties[ 'title_format' ] : NULL );
		$date         = new CB2_DateTime( $properties[ 'date' ] );
		return self::factory( $date, $title_format );
  }

  static function factory( CB2_DateTime $date, String $title_format = NULL ) {
		return CB2_PostNavigator::createInstance( __class__, func_get_args(), self::generate_ID( $date ) );
  }

  static function day_exists( CB2_DateTime $date ) {
    return isset( self::$all[ self::generate_ID( $date ) ] );
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

  static function dayofweek_adjusted( CB2_DateTime $date ) {
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

  function add_periodinst( CB2_PeriodInst $periodinst ) {
    array_push( $this->periodinsts, $periodinst );
    return $periodinst;
  }

  function tabs( Bool $edit_form_advanced = FALSE ) {
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
