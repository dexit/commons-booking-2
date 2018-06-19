<?php
require('CB_Calendar_StatusTypes.php');

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_Period extends CB_PostNavigator implements JsonSerializable {
  private static $database_table = 'wp_cb2_periods';
  public  static $all = array();
  public  static $standard_fields = array( 'period_group_type', 'time_start', 'name', 'period_status_type_name', 'recurrence_index', 'priority' );
  private $db_insert;

  protected function __construct(
    $period_id,
    $recurrence_index,
    $name,
    $datetime_part_period_start,
    $datetime_part_period_end,
    $datetime_from,
    $datetime_to,
    $period_status_type,
    $recurrence_type,
    $recurrence_frequency,
    $recurrence_sequence,  // Array
    $period_object = NULL
  ) {
    parent::__construct();

    // Period
    $this->period_id  = $period_id;
    $this->recurrence_index = $recurrence_index;
    $this->name       = $name;
    $this->datetime_part_period_start = $datetime_part_period_start;
    $this->datetime_part_period_end   = $datetime_part_period_end;
    $this->datetime_from = $datetime_from;
    $this->datetime_to   = $datetime_to;

    $this->period_status_type   = $period_status_type;
    $this->recurrence_type      = $recurrence_type;
    $this->recurrence_frequency = $recurrence_frequency;
    $this->recurrence_sequence  = $recurrence_sequence;
    $this->period_object        = $period_object;

    // Real World Objects
    // TODO: make this extensible? e.g. array( CB_Location, ... )
    $this->location = NULL;
    $this->item     = NULL;
    $this->user     = NULL;

    // Pseudo fields (in standard_fields)
    $this->period_group_type = $this->type();
    $this->fullday = ( $this->datetime_part_period_start->format( 'H:i:s' ) == '00:00:00'
      && $this->datetime_part_period_end->format( 'H:i:s' ) == '23:59:59' );

    if ( $this->datetime_to ) print( $this->datetime_to->format( 'Y-m-d H:i:s' ) );
  }

  function type() {
    return 'A';
  }

  function pseudo() {
    return FALSE;
  }

  static function type_class( $period_id, $location_ID, $item_ID, $user_ID ) {
    $period_class = NULL;

    $group_type = 'A'; // Day record (meaningless, just to ensure a record each day)
    if ( $period_id != NULL ) {
      $group_type = 'G'; // Global (e.g. holidays)
      if ( $location_ID != NULL ) {
        $group_type = 'L'; // Location (e.g. closing hours, holidays)
        if ( $item_ID != NULL ) {
          $group_type = 'I'; // Item (e.g. item availability, or repairs)
          if ( $user_ID != NULL ) {
            $group_type = 'U'; // User (e.g. a booking)
          }
        }
      }
    }

    switch ( $group_type ) {
      case 'A': $period_class = 'CB_Period_Automatic';          break;
      case 'G': $period_class = 'CB_Period_Global';             break;
      case 'L': $period_class = 'CB_Period_Location';           break;
      case 'I': $period_class = 'CB_Period_Timeframe';      break;
      case 'U': $period_class = 'CB_Period_Timeframe_User'; break;
      default: throw new Exception( 'Unknown Period type' );
    }

    return $period_class;
  }

  static function factory_period(
    $location_ID,
    $item_ID,
    $user_ID,

    $period_id,
    $recurrence_index,
    $name,
    $datetime_part_period_start,
    $datetime_part_period_end,
    $datetime_from,
    $datetime_to,
    $period_status_type = NULL,
    $recurrence_type,
    $recurrence_frequency,
    $recurrence_sequence,
    $period_object = NULL
  ) {
    // Design Patterns: Factory Singleton with Multiton
    $key = implode( '-', array( $location_ID, $item_ID, $user_ID, $period_id, $recurrence_index ) );
    if ( isset( self::$all[$key] ) ) $object = self::$all[$key];
    else {
      $period_class = self::type_class( $period_id, $location_ID, $item_ID, $user_ID );

      $object = new $period_class(
        $location_ID,
        $item_ID,
        $user_ID,
        $period_id,
        $recurrence_index,
        $name,
        $datetime_part_period_start,
        $datetime_part_period_end,
        $datetime_from,
        $datetime_to,
        $period_status_type,
        $recurrence_type,
        $recurrence_frequency,
        $recurrence_sequence,
        $period_object
      );
      self::$all[$key] = $object;
    }

    return $object;
  }

  function day_percent_position( $from = '00:00', $to = '00:00' ) {
    // 0:00  = 0
    // 9:00  = 32400
    // 18:00 = 64800
    // 24:00 = 86400
    static $seconds_in_day = 24 * 60 * 60; // 86400

    $seconds_start = CB_Calendar_Query::seconds_in_day( $this->datetime_part_period_start );
    $seconds_end   = CB_Calendar_Query::seconds_in_day( $this->datetime_part_period_end );
    $seconds_start_percent = (int) ( $seconds_start / $seconds_in_day * 100 );
    $seconds_end_percent   = (int) ( $seconds_end   / $seconds_in_day * 100 );
    $seconds_diff_percent  = $seconds_end_percent - $seconds_start_percent;

    return array(
      'start_percent' => $seconds_start_percent,
      'end_percent'   => $seconds_end_percent,
      'diff_percent'  => $seconds_diff_percent
    );
  }

  function classes() {
    $classes = '';
    if ( $this->period_status_type ) $classes .= $this->period_status_type->classes();
    $classes .= 'cb2-period-group-type-' . $this->type();
    return $classes;
  }

  function styles() {
    $styles = '';

    $day_percent_position = $this->day_percent_position();
    $styles .= "top:$day_percent_position[start_percent]%;";
    $styles .= "height:$day_percent_position[diff_percent]%;";

    if ( $this->period_status_type ) $styles .= $this->period_status_type->styles();

    return $styles;
  }

  function indicators() {
    $indicators = array();
    if ( $this->period_status_type ) $indicators = $this->period_status_type->indicators();
    return $indicators;
  }

  function classes_for_day( $day ) {
    $classes = '';
    return $classes;
  }

  function get_the_content( $more_link_text = null, $strip_teaser = false ) {
    $html = ( '<tr style="' . $this->styles() . '" class="' . $this->classes() . '">' );
    if ( WP_DEBUG ) $html .= ( '<td>' . $this->debug_html() . '</td>' );

    // Period fields
    foreach ( CB_Period::$standard_fields as $field_name ) {
      $value = $this->period_object->$field_name;
      $class = 'cb2-' . str_replace( '_', '-', $field_name );
      $html .= ( "<td class='$class'>" );
      $html .= ( '<span>' . $this->field_value_string( $field_name, $value, $class ) . '</span>' );
      $html .= ( '</td>' );
    }

    // Indicators field
    $html .= ( "<td class='cb2-indicators'><ul>" );
    foreach ( $this->indicators() as $indicator ) {
      $html .= ( "<li class='cb2-indicator-$indicator'>&nbsp;</li>" );
    }
    $html .= ( '</ul></td>' );

    $html .= ( '</tr>' );

    return $html;
  }

  function debug_html() {
    $onclick = "this.firstChild.style = (this.firstChild.style.length ? '' : 'display:block;');";
    $debug  = '<div class="cb2-debug-control" onclick="' . $onclick . '">';
    $debug .= '<table class="cb2-debug cb2-notice cb2-hidden">';
    if ( $this->period_object ) {
      foreach ( $this->period_object as $period_object_name => $period_object_value ) {
        if ( $period_object_value ) $debug .= "<tr><td>$period_object_name</td><td>$period_object_value</td></tr>";
      }
    }
    $debug .= '</table>debug</div>';

    return $debug;
  }

  function field_value_string( $field_name, $value, $class = '', $date_format = 'H:i' ) {
    $custom_render_function_name = "field_value_string_$field_name";

    if ( method_exists( $this, $custom_render_function_name ) ) {
      $value = $this->{$custom_render_function_name}( $value, $class = '', $date_format );
    } else {
      // Data Types
      if ( is_object( $value ) ) {
        switch ( get_class( $value ) ) {
          case 'DateTime':
            $value = $value->format( $date_format );
            break;
        }
      }
    }

    return $value;
  }

  function name_field() {
    return 'name';
  }

  function field_value_string_name( $value, $class = '', $date_format = 'H:i', $values = NULL ) {
    $name_field_names = $this->name_field();
    $name_value       = $this->name;
    if ( is_null( $values ) ) $values = $this->period_object;

    if ( is_array( $name_field_names ) ) {
      $name_value = '';
      foreach ( $name_field_names as $name_field_name ) {
        if ( property_exists( $values, $name_field_name ) ) {
          if ( $values->$name_field_name ) $name_value .= ' ';
          $name_value .= $values->$name_field_name;
        }
      }
    } else {
      if ( property_exists( $values, $name_field_names ) ) $name_value = $values->$name_field_names;
    }


    return $name_value;
  }

  function save() {
    $insert = CB_Database_Insert::factory( self::$database_table );
    $insert->add_field( 'name', $this->name );
    $insert->add_field( 'datetime_part_period_start', $this->datetime_part_period_start );
    $insert->add_field( 'datetime_part_period_end',   $this->datetime_part_period_end );
    $insert->add_field( 'datetime_from',              $this->datetime_from );
    $insert->add_field( 'datetime_to',                $this->datetime_to );
    $insert->add_field( 'period_status_type_id',      $this->period_status_type->period_status_type_id );
    $insert->add_field( 'recurrence_type',            $this->recurrence_type );
    $insert->add_field( 'recurrence_frequency',       $this->recurrence_frequency );
    $insert->add_field( 'recurrence_sequence',        $this->recurrence_sequence, '%d' );
    $period_id = $insert->run();

    $insert = CB_Database_Insert::factory( 'wp_cb2_period_groups' );
    $insert->add_field( 'name', $this->name );
    $period_group_id = $insert->run();

    $insert = CB_Database_Insert::factory( 'wp_cb2_period_group_period' );
    $insert->add_field( 'period_group_id', $period_group_id );
    $insert->add_field( 'period_id',       $period_id );
    $insert->run();

    return $period_group_id;
  }

  function jsonSerialize() {
    return array(
      'period_id' => $this->period_id,
      'recurrence_index' => $this->recurrence_index,
      'name' => $this->name,
      'datetime_part_period_start' => $this->datetime_part_period_start->format( CB_Calendar_Query::$javascript_date_format ),
      'datetime_part_period_end' => $this->datetime_part_period_end->format( CB_Calendar_Query::$javascript_date_format ),
      'datetime_from' => $this->datetime_from->format( CB_Calendar_Query::$javascript_date_format ),
      'datetime_to' => ( $this->datetime_to ? $this->datetime_to->format( CB_Calendar_Query::$javascript_date_format ) : '' ),
      'period_status_type' => $this->period_status_type,
      'recurrence_type' => $this->recurrence_type,
      'recurrence_frequency' => $this->recurrence_frequency,
      'recurrence_sequence' => $this->recurrence_sequence,
      'type' => $this->type(),
      'day_percent_position' => $this->day_percent_position(),
      'classes' => $this->classes(),
      'styles' => $this->styles(),
      'indicators' => $this->indicators(),
      'period_group_type' => $this->period_group_type,
      'fullday' => $this->fullday
    );
  }
}

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_Period_Automatic extends CB_Period {
  function __construct(
    $location_ID,
    $item_ID,
    $user_ID,

    $period_id,
    $recurrence_index,
    $name,
    $datetime_part_period_start,
    $datetime_part_period_end,
    $datetime_from,
    $datetime_to,
    $period_status_type,
    $recurrence_type,
    $recurrence_frequency,
    $recurrence_sequence,
    $period_object = NULL
  ) {
    parent::__construct(
      $period_id,
      $recurrence_index,
      $name,
      $datetime_part_period_start,
      $datetime_part_period_end,
      $datetime_from,
      $datetime_to,
      $period_status_type,
      $recurrence_type,
      $recurrence_frequency,
      $recurrence_sequence,
      $period_object
    );
  }

  function type() {
    return 'A';
  }

  function pseudo() {
    return TRUE;
  }

  function get_the_content( $more_link_text = null, $strip_teaser = false ) {
    return '';
  }
}

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_Period_Global extends CB_Period {
  static $name_field = 'period_group_name';
  static $database_table = 'wp_cb2_global_period_groups';

  function __construct(
    $location_ID,
    $item_ID,
    $user_ID,

    $period_id,
    $recurrence_index,
    $name,
    $datetime_part_period_start,
    $datetime_part_period_end,
    $datetime_from,
    $datetime_to,
    $period_status_type,
    $recurrence_type,
    $recurrence_frequency,
    $recurrence_sequence,
    $period_object = NULL
  ) {
    parent::__construct(
      $period_id,
      $recurrence_index,
      $name,
      $datetime_part_period_start,
      $datetime_part_period_end,
      $datetime_from,
      $datetime_to,
      $period_status_type,
      $recurrence_type,
      $recurrence_frequency,
      $recurrence_sequence,
      $period_object
    );
  }

  function type() {
    return 'G';
  }

  function name_field() {
    return self::$name_field;
  }

  function save() {
    $period_group_id = parent::save();

    $insert = CB_Database_Insert::factory( self::$database_table );
    $insert->add_field( 'period_group_id',  $period_group_id );
    $insert->run();

    return $period_group_id;
  }
}


// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_Period_Location extends CB_Period {
  static $name_field = 'location_post_title';
  static $database_table = 'wp_cb2_location_period_groups';

  function __construct(
    $location_ID,
    $item_ID,
    $user_ID,

    $period_id,
    $recurrence_index,
    $name,
    $datetime_part_period_start,
    $datetime_part_period_end,
    $datetime_from,
    $datetime_to,
    $period_status_type,
    $recurrence_type,
    $recurrence_frequency,
    $recurrence_sequence,
    $period_object = NULL
  ) {
    parent::__construct(
      $period_id,
      $recurrence_index,
      $name,
      $datetime_part_period_start,
      $datetime_part_period_end,
      $datetime_from,
      $datetime_to,
      $period_status_type,
      $recurrence_type,
      $recurrence_frequency,
      $recurrence_sequence,
      $period_object
    );
    $this->location = CB_Location::factory(
      $location_ID,
      ( isset( $this->period_object->location_post_title ) ? $this->period_object->location_post_title : NULL )
    );
    $this->location->add_period( $this );
    array_push( $this->posts, $this->location );
  }

  function type() {
    return 'L';
  }

  function name_field() {
    return self::$name_field;
  }

  function save() {
    $period_group_id = parent::save();

    $insert = CB_Database_Insert::factory( self::$database_table );
    $insert->add_field( 'location_ID', $this->location->ID );
    $insert->add_field( 'period_group_id',  $period_group_id );
    $insert->run();

    return $period_group_id;
  }

  function jsonSerialize() {
    $array = parent::jsonSerialize();
    //$array[ 'location' ] = &$this->location;
    return $array;
  }
}


// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_Period_Timeframe extends CB_Period {
  static $name_field = array( 'location_post_title', 'item_post_title' );
  static $database_table = 'wp_cb2_timeframe_period_groups';

  function __construct(
    $location_ID,
    $item_ID,
    $user_ID,

    $period_id,
    $recurrence_index,
    $name,
    $datetime_part_period_start,
    $datetime_part_period_end,
    $datetime_from,
    $datetime_to,
    $period_status_type,
    $recurrence_type,
    $recurrence_frequency,
    $recurrence_sequence,
    $period_object = NULL
  ) {
    parent::__construct(
      $period_id,
      $recurrence_index,
      $name,
      $datetime_part_period_start,
      $datetime_part_period_end,
      $datetime_from,
      $datetime_to,
      $period_status_type,
      $recurrence_type,
      $recurrence_frequency,
      $recurrence_sequence,
      $period_object
    );
    $this->location = CB_Location::factory(
      $location_ID,
      ( isset( $this->period_object->location_post_title ) ? $this->period_object->location_post_title : NULL )
    );
    array_push( $this->posts, $this->location );

    $this->item = CB_Item::factory(
      $this->location,
      $item_ID,
      ( isset( $this->period_object->item_post_title ) ? $this->period_object->item_post_title : NULL )
    );
    array_push( $this->posts, $this->item );
    $this->item->add_period( $this );
  }

  function type() {
    return 'I';
  }

  function name_field() {
    return self::$name_field;
  }

  function save() {
    $period_group_id = parent::save();

    $insert = CB_Database_Insert::factory( self::$database_table );
    $insert->add_field( 'location_ID', $this->location->ID );
    $insert->add_field( 'item_ID',     $this->item->ID );
    $insert->add_field( 'period_group_id',  $period_group_id );
    $insert->run();

    return $period_group_id;
  }

  function jsonSerialize() {
    $array = parent::jsonSerialize();
    //$array[ 'location' ] = &$this->location;
    //$array[ 'item' ]     = &$this->item;
    return $array;
  }
}


// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_Period_Timeframe_User extends CB_Period {
  static $name_field = array( 'location_post_title', 'item_post_title', 'user_login' );
  static $database_table = 'wp_cb2_location_item_user_period_groups';

  function __construct(
    $location_ID,
    $item_ID,
    $user_ID,

    $period_id,
    $recurrence_index,
    $name,
    $datetime_part_period_start,
    $datetime_part_period_end,
    $datetime_from,
    $datetime_to,
    $period_status_type,
    $recurrence_type,
    $recurrence_frequency,
    $recurrence_sequence,
    $period_object = NULL
  ) {
    parent::__construct(
      $period_id,
      $recurrence_index,
      $name,
      $datetime_part_period_start,
      $datetime_part_period_end,
      $datetime_from,
      $datetime_to,
      $period_status_type,
      $recurrence_type,
      $recurrence_frequency,
      $recurrence_sequence,
      $period_object
    );
    $this->location = CB_Location::factory(
      $location_ID,
      ( isset( $this->period_object->location_post_title ) ? $this->period_object->location_post_title : NULL )
    );
    array_push( $this->posts, $this->location );

    $this->item = CB_Item::factory(
      $this->location,
      $item_ID,
      ( isset( $this->period_object->item_post_title ) ? $this->period_object->item_post_title : NULL )
    );
    array_push( $this->posts, $this->item );

    $this->user = CB_User::factory(
      $this->location,
      $this->item,
      $user_ID,
      ( isset( $this->period_object->user_login ) ? $this->period_object->user_login : NULL )
    );
    array_push( $this->posts, $this->user );
    $this->user->add_period( $this );
  }

  function type() {
    return 'U';
  }

  function name_field() {
    return self::$name_field;
  }

  function save() {
    $period_group_id = parent::save();

    $insert = CB_Database_Insert::factory( self::$database_table );
    $insert->add_field( 'location_ID',      $this->location->ID );
    $insert->add_field( 'item_ID',          $this->item->ID );
    $insert->add_field( 'user_ID',          $this->user->ID );
    $insert->add_field( 'period_group_id',  $period_group_id );
    $insert->run();

    return $period_group_id;
  }

  function jsonSerialize() {
    $array = parent::jsonSerialize();
    //$array[ 'location' ] = &$this->location;
    //$array[ 'item' ]     = &$this->item;
    //$array[ 'user' ]     = &$this->user;
    return $array;
  }
}


