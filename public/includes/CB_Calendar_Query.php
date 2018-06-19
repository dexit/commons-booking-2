<?php
require( 'CB_Database.php' );
require( 'CB_Calendar_Periods.php' );
require( 'CB_RealWorldObjects.php' );

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_Calendar_Query extends CB_PostNavigator implements JsonSerializable {
  /* CB_Calendar_Query behaviour is based loosely on WP_Query
   * https://codex.wordpress.org/Class_Reference/WP_Query
   * TODO: integrate in to WP_Query hooks so that objects can be requested through that interface also
   */
  private static $database_table         = 'wp_cb2_view_calendar_period_items';
  private static $database_table_alias   = 'cal';
  public  static $javascript_date_format = 'Y-m-d H:i:s';

  function __construct(
    $startdate = NULL,
    $enddate = NULL,
    $location_ID = NULL,
    $item_ID = NULL,
    $user_ID = NULL,
    $period_status_type_name = NULL,
    $queried_object_id = NULL
  ) {
    parent::__construct();
    $this->init();
    $this->parse_query( $startdate, $enddate, $location_ID, $item_ID, $user_ID, $period_status_type_name, $queried_object_id );
    if ( $this->constructed_with_args ) $this->get_posts();
  }

  function init() {
    $this->query_vars            = NULL;
    $this->constructed_with_args = FALSE;
    $this->queried_object_id     = NULL;
    $this->set_queried_object_id( 'weeks' );

    $this->order   = NULL;
    $this->orderby = NULL;

    $this->year       = NULL;
    $this->month      = NULL;
    $this->month_name = NULL;
  }

  // -------------------------------------------------------------------- Form helpers
  // TODO: move these functions in to a separate class
  static function location_options() {
    return CB_Calendar_Query::get_options( 'wp_cb2_view_locations' );
  }

  static function item_options() {
    return CB_Calendar_Query::get_options( 'wp_cb2_view_items' );
  }

  static function user_options() {
    return CB_Calendar_Query::get_options( 'wp_users', 'ID', 'user_login' );
  }

  static function period_status_type_options() {
    return CB_Calendar_Query::get_options( 'wp_cb2_period_status_types', 'period_status_type_id', 'name' );
  }

  static function get_options( $table, $id_field = 'ID', $name_field = 'post_title' ) {
    //TODO: cache this
    global $wpdb;
    return $wpdb->get_results( "select $id_field as ID, $name_field as name from $table", OBJECT_K );
  }

  static function select_options( $records, $current_value = NULL, $add_none = TRUE, $by_name = FALSE ) {
    $html = '';
    if ( $add_none ) $html .= "<option value=''>--none--</option>";
    foreach ( $records as $value => $name ) {
      if ( is_object( $name ) ) $name  = $name->name;
      if ( $by_name )           $value = $name;
      $selected = ( $current_value == $value ? 'selected="1"' : '' );
      $html .= "<option value='$value' $selected>$name</option>";
    }
    return $html;
  }

  static function reset_data( $pass ) {
    global $wpdb;

    if ( WP_DEBUG && $pass == 'fryace4' ) {
      $wpdb->query( 'delete from wp_cb2_global_period_groups where period_group_id >= 1;' );
      $wpdb->query( 'delete from wp_cb2_location_item_period_groups where period_group_id >= 1;' );
      $wpdb->query( 'delete from wp_cb2_location_item_user_period_groups where period_group_id >= 1;' );
      $wpdb->query( 'delete from wp_cb2_location_period_groups where period_group_id >= 1;' );

      $wpdb->query( 'delete from wp_cb2_period_group_period where period_group_id >= 1;' );
      $wpdb->query( 'delete from wp_cb2_periods where period_id >= 1;' );
      $wpdb->query( 'delete from wp_cb2_period_groups where period_group_id >= 1;' );
    }
  }

  // -------------------------------------------------------------------- Expose CB_Database_Query
  // TODO: add_condition() etc.
  // Maybe we should directly inherit
  // And remove the PostNavigation

  // -------------------------------------------------------------------- Schema
  static function queried_object_types() {
    return array(
      'periods' => 'periods',
      'days'    => 'days',
      'weeks'   => 'weeks',
      'months'  => 'months',
      'locations' => 'locations',
      'items'   => 'items',
      'users'   => 'users',
      'forms'   => 'forms',
    );
  }

  protected function set_queried_object_id( $queried_object_id ) {
    // TODO: re-evaluate use of queried_object_id for storage of schema
    if ( $queried_object_id != $this->queried_object_id ) {
      switch ( $queried_object_id ) {
        case 'periods':   $this->queried_object = &CB_Period::$all;    break;
        case 'days':      $this->queried_object = &CB_Day::$all;       break;
        case 'weeks':     $this->queried_object = &CB_Week::$all;      break;
        case 'months':    $this->queried_object = &CB_Month::$all;     break;
        case 'locations': $this->queried_object = &CB_Location::$all;  break;
        case 'items':     $this->queried_object = &CB_Item::$all;      break;
        case 'users':     $this->queried_object = &CB_User::$all;      break;
        // TODO: Move to CB_Options::factory() with the_content()
        case 'forms': {
          $forms = array(
            'location_options' => $this->location_options(),
            'item_options'     => $this->item_options(),
            'user_options'     => $this->user_options(),
            'period_status_type_options' => $this->period_status_type_options(),
          );
          $this->queried_object = &$forms;
          break;
        }
        default:
          throw new Exception( "set_queried_object_id(): [$queried_object_id] not understood" );
      }

      $this->queried_object_id = $queried_object_id;
      $this->posts             = &$this->queried_object;
    }

    return $this->queried_object;
  }

  function get_queried_object() {
    return $this->queried_object;
  }

  function get_queried_object_id() {
    return $this->queried_object_id;
  }

  // -------------------------------------------------------------------- Query
  function parse_query(
    $startdate = NULL,
    $enddate = NULL,
    $location_ID = NULL,
    $item_ID = NULL,
    $user_ID = NULL,
    $period_status_type_name = NULL,
    $queried_object_id = NULL
  ) {
    if ( is_null( $startdate ) ) {
      $this->startdate = new DateTime(); // Now
    } else {
      if ( is_object( $startdate ) && $startdate instanceof DateTime ) {
        $this->startdate   = new DateTime( $startdate );
        $this->enddate     = ( $enddate ? new DateTime( $enddate ) : NULL );

        // Hardcoded Real World Object system join requirements
        // All can be NULL indicating no selection
        $this->location_ID = $location_ID;
        $this->item_ID     = $item_ID;
        $this->user_ID          = $user_ID;

        // Hardcoded options
        // All can be NULL
        $this->period_status_type_name = $period_status_type_name;
        if ( $this->queried_object_id ) $this->set_queried_object_id( $queried_object_id );
      } else if ( is_array( $startdate ) ) {
        $this->query_vars       = &$startdate;

        // Calendar options
        $this->startdate        = ( isset( $this->query_vars['startdate'] )        ? new DateTime( $this->query_vars['startdate'] ) : new DateTime() );
        $this->enddate          = ( isset( $this->query_vars['enddate'] )          ? new DateTime( $this->query_vars['enddate']   ) : NULL );
        $this->location_ID = ( isset( $this->query_vars['location_ID'] ) ? $this->query_vars['location_ID'] : NULL );
        $this->item_ID     = ( isset( $this->query_vars['item_ID'] )     ? $this->query_vars['item_ID'] : NULL );
        $this->user_ID          = ( isset( $this->query_vars['user_ID'] )          ? $this->query_vars['user_ID'] : NULL );
        $this->period_status_type_name = ( isset( $this->query_vars['period_status_type_name'] ) ? $this->query_vars['period_status_type_name'] : NULL );
        if ( isset( $this->query_vars['queried_object_id'] ) ) $this->set_queried_object_id( $this->query_vars['queried_object_id'] );
        if ( isset( $this->query_vars['schema'] ) ) $this->set_queried_object_id( $this->query_vars['schema'] );

        // Alternatives for similarity to WP_Query
        // https://codex.wordpress.org/Class_Reference/WP_Query
        $post_type = 'location';
        if ( isset( $this->query_vars['post_type'] ) ) $post_type = $this->query_vars['post_type'];
        if ( isset( $this->query_vars['p'] ) ) {
          if ( $post_type == 'item' ) $this->item_ID     = $this->query_vars['p'];
          else                        $this->location_ID = $this->query_vars['p'];
        }
        if ( isset( $this->query_vars['post_status'] ) )   $this->period_status_type_name = $this->query_vars['post_status'];
        if ( isset( $this->query_vars['author'] ) )        $this->user_ID               = $this->query_vars['author'];
        if ( isset( $this->query_vars['order'] ) )         $this->order                 = $this->query_vars['order'];
        if ( isset( $this->query_vars['orderby'] ) )       $this->orderby               = $this->query_vars['orderby'];

        // Dates
        if ( isset( $this->query_vars['date_query'] ) ) {
          $date_query = $this->query_vars['date_query'];
          if ( isset( $date_query['after']  ) ) $this->startdate = new DateTime( $date_query['after']  );
          if ( isset( $date_query['before'] ) ) $this->enddate   = new DateTime( $date_query['before'] );
        } else if ( isset( $this->query_vars['year'] ) ) {
          $today    = getdate();
          $year     = $this->query_vars['year'];
          $monthnum = ( isset( $this->query_vars['monthnum'] ) ? $this->query_vars['monthnum'] : NULL );
          $day      = ( isset( $this->query_vars['day'] )      ? $this->query_vars['day'] : NULL );

          $startdate_string = $year;
          $endate_string    = $year + 1;
          if ( $monthnum ) {
            $startdate_string  = "$year-$monthnum";
            $endate_string     = "$year-" . ($monthnum + 1);
            if ( $day ) {
              $startdate_string .= "-$day";
              $endate_string    .= '-' . ($day + 1);
            }
          }
          $this->startdate = new DateTime( $startdate_string );
          $this->enddate   = new DateTime( $endate_string );
        }
      } else if ( is_string( $startdate ) ) {
        throw new Exception("CB_Calendar_Query single query string parameter not implemented yet");
      } else {
        throw new Exception("CB_Calendar_Query first parameter must be a DateTime or an array of parameters");
      }
      $this->constructed_with_args = TRUE;
    }

    // Calendar details
    if ( $this->startdate && $this->enddate) {
      $this->year       = (int) $this->startdate->format( 'Y' );
      $this->month      = (int) $this->startdate->format( 'm' );
      $this->month_name = $this->startdate->format( 'F' );
    } else throw new Exception( "startdate and enddate required" );
  }

  function &query(
    $startdate = NULL,
    $enddate = NULL,
    $location_ID = NULL,
    $item_ID = NULL,
    $user_ID = NULL,
    $period_status_type_name = NULL
  ) {
    $this->parse_query( $startdate, $enddate, $location_ID, $item_ID, $user_ID, $period_status_type_name );
    if ( $this->constructed_with_args ) $this->get_posts();
  }

  function &get_posts( $sql = NULL ) {
    global $wpdb;

    $records  = array();
    $db_query = CB_Database_Query::factory( self::$database_table, self::$database_table_alias );

    if ( $sql ) {
      $records = $db_query->get_results( $sql );
    } else {
      $db_query->add_all_fields( self::$database_table_alias );

      // Hardcoded Real World Object system join requirements
      // Either the calendar is restricted to a specific Real World Object
      // or it joins to the varying on in the row for its data
      $db_query->add_posts_join( 'location', 'cal.location_ID' );
      if ( $this->location_ID != NULL ) $db_query->add_condition( 'cal.location_ID', $this->location_ID, TRUE );

      $db_query->add_posts_join(  'item', 'cal.item_ID' );
      if ( $this->item_ID != NULL ) $db_query->add_condition( 'cal.item_ID', $this->item_ID, TRUE );

      $db_query->add_join(  'wp_users', 'user', array( 'cal.user_ID = user.ID' ) );
      $db_query->add_field( 'ID', 'user', 'user_ID' );
      $db_query->add_field( 'user_login', 'user' );
      if ( $this->user_ID != NULL ) $db_query->add_condition( 'cal.user_ID', $this->user_ID, TRUE );

      // Hardcoded options
      $db_query->add_join( 'wp_cb2_periods', 'p', array( 'cal.period_id = p.period_id' ) );
      $db_query->add_all_fields( 'p' );
      $db_query->add_join( 'wp_cb2_period_groups', 'pg', array( 'cal.period_group_id = pg.period_group_id' ) );
      $db_query->add_field( 'name', 'pg', 'period_group_name' );
      $db_query->add_join( 'wp_cb2_period_status_types', 'pst', array( 'pst.period_status_type_id = p.period_status_type_id' ) );
      $db_query->add_all_fields( 'pst' );
      $db_query->add_field( 'name', 'pst', 'period_status_type_name' );
      $db_query->add_flag_field( 'flags', 0, 'pst', 'collect' );
      $db_query->add_flag_field( 'flags', 1, 'pst', 'use' );
      $db_query->add_flag_field( 'flags', 2, 'pst', 'return' );
      if ( $this->period_status_type_name != NULL ) {
        $db_query->add_condition( 'pst.name', $this->period_status_type_name, TRUE );
      }

      // de-normalised options linked to timeframes
      $db_query->add_join( 'wp_cb2_view_timeframe_options', 'cto', array( 'cal.timeframe_id = cto.timeframe_id' ) );
      $db_query->add_all_fields( 'cto' );

      $db_query->add_condition( 'cal.date', 'cast(%s as date)', FALSE, '>=', FALSE );
      $db_query->add_condition( 'cal.date', 'cast(%s as date)', FALSE, '<=', FALSE );

      if ( $this->orderby ) {
        $db_query->add_orderby( $this->orderby );
      } else {
        $db_query->add_orderby( 'date', self::$database_table_alias );
        $db_query->add_orderby( 'time_start', self::$database_table_alias );
      }
      if ( $this->order ) $db_query->add_order_direction( $this->order );

      $records = $db_query->run(
        $this->startdate->format( CB_Database_Query::$database_date_format ),
        $this->enddate->format(   CB_Database_Query::$database_date_format )
      );
    }

    // Reorganise the flat records in to a time based data structure
    if ( $records ) {
      $current_date = NULL;
      foreach ( $records as $period_object ) {
        // CB_Period will create and associate
        // all the associated objects, e.g. CB_Day
        $period = CB_Period::factory_period(
          $period_object->location_ID,
          $period_object->item_ID,
          $period_object->user_ID,

          $period_object->period_id,
          $period_object->recurrence_index,
          $period_object->name,
          new DateTime( $period_object->datetime_part_period_start ),
          new DateTime( $period_object->datetime_part_period_end ),
          new DateTime( $period_object->datetime_from ),
          ( $period_object->datetime_to ? new DateTime( $period_object->datetime_to   ) : NULL ),
          CB_PeriodStatusType::factory(
            $period_object->period_status_type_id,
            $period_object->period_status_type_name,
            $period_object->colour,
            $period_object->opacity,
            $period_object->priority,
            ( $period_object->return  != '0' ),
            ( $period_object->collect != '0' ),
            ( $period_object->use     != '0' )
          ),
          NULL,
          NULL,
          NULL,
          $period_object
        );

        $day = CB_Day::factory( new DateTime( $period_object->date ) );
        $day->add_period( $period );
      }
    }

    return $this->days;
  }

  // -------------------------------------------------------------------- Utilities
  static function seconds_in_day( $datetime ) {
    // TODO: better / faster way of doing this?
    $time_string = $datetime->format( 'H:i' );
    $time_object = new DateTime( "1970-01-01 $time_string" );
    return (int) $time_object->format('U');
  }

  // -------------------------------------------------------------------- Output
  function jsonSerialize() {
    $array = [
      'startdate'    => $this->startdate->format( CB_Calendar_Query::$javascript_date_format ),
      'enddate'      => $this->enddate->format( CB_Calendar_Query::$javascript_date_format ),
      'location_ID' => $this->location_ID,
      'item_ID' => $this->item_ID,
      'user_ID'      => $this->user_ID,
      'period_status_type_name' => $this->period_status_type_name,
      'year'         => $this->year,
      'month'        => $this->month,
      'month_name'   => $this->month_name,
      'period_status_types'    => &CB_PeriodStatusType::$all,
      'queried_object_id'      => $this->queried_object_id,
      $this->queried_object_id => &$this->queried_object
    ];

    return $array;
  }

  function get_the_content( $more_link_text = null, $strip_teaser = false ) {
    $html = ( "<table class='cb2-calendar'><tbody>" );
    while ( $post = $this->next_post() ) {
      $html .= ( '<tr>' );
      $html .= $post->get_the_content( $this );
      $html .= ( '</tr>' );
    }
    $html .= ( '</tbody></table>' );

    return $html;
  }
}

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_Month extends CB_PostNavigator implements JsonSerializable {
  static $all = array();

  protected function __construct( $day ) {
    $this->days = array();
    parent::__construct( $this->days );

    $this->monthinyear = (int) $day->monthinyear;
    $this->first_day_num = 31;

    $this->add_day( $day );
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

  function pre_week_days() {
    return $this->first_day_num;
  }

  function add_day( $day ) {
    if ( $day->monthinyear != $this->monthinyear ) throw Up();
    $this->days[ $day->dayinmonth ] = $day;
    if ( $day->dayinmonth < $this->first_day_num ) $this->first_day_num = $day->dayinmonth;
    return $day;
  }

  function get_the_content( $more_link_text = null, $strip_teaser = false ) {
    $html = '';
    while ( $post = $this->next_post() ) {
      $html .= ( "<td>" );
      $html .= $post->get_the_content( $more_link_text, $strip_teaser );
      $html .= ( '</td>' );
    }
    return $html;
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
class CB_Week extends CB_PostNavigator implements JsonSerializable {
  static $all = array();

  protected function __construct( $day ) {
    $this->days = array();
    parent::__construct( $this->days );

    $this->weekinyear = (int) $day->weekinyear;
    $this->first_day_num = 7;

    $this->add_day( $day );
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

  function pre_week_days() {
    return $this->first_day_num;
  }

  function add_day( $day ) {
    if ( $day->weekinyear != $this->weekinyear ) throw new Exception( "day in wrong week [$this->weekinyear]" );
    $this->days[ $day->dayofweek ] = $day;
    if ( $day->dayofweek < $this->first_day_num ) $this->first_day_num = $day->dayofweek;
    return $day;
  }

  function get_the_content( $more_link_text = null, $strip_teaser = false ) {
    $html = '';
    for ($day = 1; $day < $this->pre_week_days(); $day++ ) {
      $html .= ( "<td class='cb2-empty-pre-cell'>&nbsp;</td>" );
    }
    while ( $post = $this->next_post() ) {
      $html .= ( "<td>" );
      $html .= $post->get_the_content( $more_link_text, $strip_teaser );
      $html .= ( '</td>' );
    }

    return $html;
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
class CB_Day extends CB_PostNavigator implements JsonSerializable {
  static $all = array();

  protected function __construct( $date, $title_format = 'D, M-d' ) {
    $this->periods     = array();
    parent::__construct( $this->periods );

    $this->date        = $date;

    $this->year        = (int) $date->format( 'Y' );
    $this->weekinyear  = (int) $date->format( 'W' ); // Monday start day
    $this->monthinyear = (int) $date->format( 'n' ); // 1-12
    $this->dayinmonth  = (int) $date->format( 'j' ); // 0-31 day in month
    $this->dayofweek   = (int) $date->format( 'w' ); // Sunday start day (see below)
    $this->today       = ( $date->format( 'Y-m-d' ) == (new DateTime())->format( 'Y-m-d' ) );
    $this->title       = $this->date->format( $title_format );

    // format( 'w' ) is Sunday start day based:
    // http://php.net/manual/en/function.date.php
    if ( $this->dayofweek == 0 ) $this->dayofweek = 7;

    $this->week  = CB_Week::factory(  $this );
    $this->month = CB_Month::factory( $this );
  }

  static function factory( $date, $title_format = 'D, M-d' ) {
    // Design Patterns: Factory Singleton with Multiton
    $key = $date->format( 'Y-m-d' );
    if ( isset( self::$all[$key] ) ) $object = self::$all[$key];
    else {
      $object = new self( $date, $title_format );
      self::$all[$key] = $object;
    }

    return $object;
  }

  function classes() {
    $classes = '';
    if ( $this->today ) $classes .= 'cb2-today';

    foreach ( $this->periods as $period ) {
      $classes .= $period->classes_for_day( $this );
    }

    return $classes;
  }

  function add_period( $period ) {
    array_push( $this->periods, $period );
    return $period;
  }

  function get_the_content( $more_link_text = null, $strip_teaser = false ) {
    $html = '';
    $classes = $this->classes(); // Asks periods for thoughts also
    $html .= ( "<div class='$classes'>" );
    $html .= ( "<div class='cb2-date-cell-title $classes'>$this->title</div>" );

    // Periods
    $html .= ( '<table class="cb2-periods"><tbody>' );
    while ( $post = $this->next_post() ) {
      $html .= $post->get_the_content( $more_link_text, $strip_teaser );
    }
    $html .= ( '</tbody></table>');
    $html .= ( '</div>' );

    return $html;
  }

  function jsonSerialize() {
    return [
      'date'        => $this->date->format( CB_Calendar_Query::$javascript_date_format ),
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
