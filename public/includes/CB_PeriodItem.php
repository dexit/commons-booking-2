<?php
require('CB_Period.php');

class CB_PeriodItem extends CB_PostNavigator implements JsonSerializable {
  public  static $all = array();
  public  static $static_post_type = 'perioditem';
  public  static $standard_fields = array(
		'period_group_type',
		'time_start',
		'name',
		'period->period_status_type->name',
		'recurrence_index',
		'period->priority'
	);
	public static $all_post_types = array(
		'perioditem-automatic', // post_status = auto-draft
		'perioditem-global',
		'perioditem-location',
		'perioditem-timeframe',
		'perioditem-user',
	);
  private static $null_recurrence_index = 0;

  protected function __construct(
		$ID,
    $period,
    $recurrence_index,
    $datetime_period_item_start,
    $datetime_period_item_end
  ) {
		CB_Query::assign_all_parameters( $this, func_get_args(), __class__ );

		// Add the period to all the days it appears in
		// CB_Day::factory() will lazy create singleton CB_Day's
		if ( $this->datetime_period_item_start ) {
			$date     = CB_Query::ensure_datetime( $this->datetime_period_item_start );
			$date_end = CB_Query::ensure_datetime( $this->datetime_period_item_end );
			do {
				$day = CB_Day::factory( $date );
				$day->add_period( $this );
				$date->add( new DateInterval( 'P1D' ) );
			} while ( $date < $date_end );
		}

    parent::__construct();
  }

  function seconds_in_day( $datetime ) {
    // TODO: better / faster way of doing this?
    $time_string = $datetime->format( 'H:i' );
    $time_object = new DateTime( "1970-01-01 $time_string" );
    return (int) $time_object->format('U');
  }

  function day_percent_position( $from = '00:00', $to = '00:00' ) {
    // 0:00  = 0
    // 9:00  = 32400
    // 18:00 = 64800
    // 24:00 = 86400
    static $seconds_in_day = 24 * 60 * 60; // 86400

    $seconds_start = $this->seconds_in_day( $this->datetime_part_period_start );
    $seconds_end   = $this->seconds_in_day( $this->datetime_part_period_end );
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
    if ( $this->period ) $classes .= $this->period->period_status_type->classes();
    $classes .= 'cb2-period-group-type-' . $this->type();
    return $classes;
  }

  function styles() {
    $styles = '';

    $day_percent_position = $this->day_percent_position();
    $styles .= "top:$day_percent_position[start_percent]%;";
    $styles .= "height:$day_percent_position[diff_percent]%;";

    $styles .= $this->period->period_status_type->styles();

    return $styles;
  }

  function add_actions( &$actions ) {
		$period_ID = $this->period->ID;
		$actions[ 'edit-definition' ] = "<a href='/wp-admin/post.php?post=$period_ID&action=edit'>Edit definition</a>";
		$actions[ 'trash occurence' ] = '<a href="#" class="submitdelete">Trash Occurence</a>';
	}

  function indicators() {
    $indicators = array();
    if ( $this->period ) $indicators = $this->period->period_status_type->indicators();
    return $indicators;
  }

  function classes_for_day( $day ) {
    $classes = '';
    return $classes;
  }

  function field_value_string_name( $object, $class = '', $date_format = 'H:i' ) {
		$name_value = NULL;
		$name_field_names = 'name';
		if ( method_exists( $this, 'name_field' ) ) $name_field_names = $this->name_field();

		if ( is_array( $name_field_names ) ) {
			$name_value = '';
			foreach ( $name_field_names as $name_field_name ) {
				if ( $name_value ) $name_value .= ' ';
				$name_value .= get_the_field( $name_field_name, $class, $date_format );
			}
		} else if ( property_exists( $object, $name_field_names ) ) {
			$name_value = $object->$name_field_names;
		}

		return $name_value;
	}


  function get_the_content( $more_link_text = null, $strip_teaser = false ) {
    // Indicators field
    $html = "<td class='cb2-indicators'><ul>";
    foreach ( $this->indicators() as $indicator ) {
			$letter = ( substr( $indicator, 0, 3 ) == 'no-' ? $indicator[3] : $indicator[0] );
      $html  .= "<li class='cb2-indicator-$indicator'>$letter</li>";
    }
    $html .= '</ul></td>';

    return $html;
  }

  function get_the_debug( $before = '<td>', $after = '</td>' ) {
    $onclick = "this.firstElementChild.style = (this.firstElementChild.style.length ? '' : 'display:block;');";
    $debug  = $before;
    $debug .= '<div class="cb2-debug-control" onclick="' . $onclick . '">';
    $debug .= '<table class="cb2-debug cb2-notice cb2-hidden">';
		foreach ( $this as $name => $value ) {
			if ( $name ) {
				if      ( $value instanceof DateTime ) $value = $value->format( 'c' );
				else if ( is_array( $value ) ) $value = 'Array(' . count( $value ) . ')';
				else if ( $value instanceof WP_Post ) $value = 'WP_Post(' . $value->post_title . ')';
				$debug .= "<tr><td>$name</td><td>$value</td></tr>";
			}
		}
    $debug .= '</table><span>debug</span></div>';
    $debug .= $after;

    return $debug;
  }

  function name_field() {
    return 'name';
  }

  function jsonSerialize() {
    return array(
      'period_id' => $this->period_id,
      'recurrence_index' => $this->recurrence_index,
      'name' => $this->name,
      'datetime_part_period_start' => $this->datetime_part_period_start->format( CB_Query::$javascript_date_format ),
      'datetime_part_period_end' => $this->datetime_part_period_end->format( CB_Query::$javascript_date_format ),
      'datetime_from' => $this->datetime_from->format( CB_Query::$javascript_date_format ),
      'datetime_to' => ( $this->datetime_to ? $this->datetime_to->format( CB_Query::$javascript_date_format ) : '' ),
      'period_status_type' => $this->period->period_status_type,
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
class CB_PeriodItem_Automatic extends CB_PeriodItem {
	static public $static_post_type = 'perioditem-automatic';
  static $database_table = FALSE;
  public  static $post_type_args = array(
		'public'    => FALSE,
  );

  function post_type() {return self::$static_post_type;}

  function __construct(
		$ID,
    $period,
		$recurrence_index,
		$datetime_period_item_start,
		$datetime_period_item_end
  ) {
		$this->post_type = self::$static_post_type;

    parent::__construct(
			$ID,
			$period,
			$recurrence_index,
			$datetime_period_item_start,
			$datetime_period_item_end
    );
  }

  static function &factory_from_wp_post( $post ) {
		CB_Query::get_metadata_assign( $post ); // Retrieves ALL meta values
		$object = self::factory(
			$post->ID,
			CB_Query::get_post_type( 'period', $post->period_ID ),
			$post->recurrence_index,
			$post->datetime_period_item_start,
			$post->datetime_period_item_end
		);

		CB_Query::copy_all_properties( $post, $object );

		return $object;
  }

  static function &factory(
		$ID,
    $period,     // CB_Period
    $recurrence_index,
    $datetime_period_item_start,
    $datetime_period_item_end
  ) {
    // Design Patterns: Factory Singleton with Multiton
		if ( isset( self::$all[$ID] ) ) {
			$object = self::$all[$ID];
    } else {
			$reflection = new ReflectionClass( __class__ );
			$object     = $reflection->newInstanceArgs( func_get_args() );
      self::$all[$ID] = $object;
    }

    return $object;
  }
}
CB_Query::register_schema_type( 'CB_PeriodItem_Automatic' );

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_PeriodItem_Global extends CB_PeriodItem {
  static $name_field = 'period_group_name';
  static $database_table = 'cb2_global_period_groups';
  public  static $post_type_args = array(
		'menu_icon' => 'dashicons-admin-page',
		'label'     => 'Global Periods',
  );

	static public $static_post_type = 'perioditem-global';

  function post_type() {return self::$static_post_type;}

  function __construct(
		$ID,
    $period,
		$recurrence_index,
		$datetime_period_item_start,
		$datetime_period_item_end,

		$timeframe_id
  ) {
    parent::__construct(
			$ID,
			$period,
			$recurrence_index,
			$datetime_period_item_start,
			$datetime_period_item_end
    );
  }

  static function &factory_from_wp_post( $post ) {
		CB_Query::get_metadata_assign( $post ); // Retrieves ALL meta values

		$object = self::factory(
			$post->ID,
			CB_Query::get_post_type( 'period', $post->period_ID ),
			$post->recurrence_index,
			$post->datetime_period_item_start,
			$post->datetime_period_item_end,

			$post->timeframe_id
		);

		CB_Query::copy_all_properties( $post, $object );

		return $object;
  }

  static function &factory(
		$ID,
    $period,     // CB_Period
    $recurrence_index,
    $datetime_period_item_start,
    $datetime_period_item_end,

    $timeframe_id
  ) {
    // Design Patterns: Factory Singleton with Multiton
		if ( isset( self::$all[$ID] ) ) {
			$object = self::$all[$ID];
    } else {
			$reflection = new ReflectionClass( __class__ );
			$object     = $reflection->newInstanceArgs( func_get_args() );
      self::$all[$ID] = $object;
    }

    return $object;
  }

  function name_field() {
    return self::$name_field;
  }
}
CB_Query::register_schema_type( 'CB_PeriodItem_Global' );

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_PeriodItem_Location extends CB_PeriodItem {
  static $name_field = 'location';
  static $database_table = 'cb2_location_period_groups';
  public  static $post_type_args = array(
		'menu_icon' => 'dashicons-admin-page',
		'label'     => 'Location Periods',
  );

	static public $static_post_type = 'perioditem-location';

  function post_type() {return self::$static_post_type;}

  function __construct(
		$ID,
    $period,
		$recurrence_index,
		$datetime_period_item_start,
		$datetime_period_item_end,

		$timeframe_id,
		$location // CB_Location
	) {
    parent::__construct(
			$ID,
			$period,
			$recurrence_index,
			$date,
			$datetime_period_item_start,
			$datetime_period_item_end
    );
    $this->location = $location;
    $this->location->add_period( $this );
    array_push( $this->posts, $this->location );
  }

  static function &factory_from_wp_post( $post ) {
		CB_Query::get_metadata_assign( $post ); // Retrieves ALL meta values

		$object = self::factory(
			$post->ID,
			CB_Query::get_post_type( 'period', $post->period_ID ),
			$post->recurrence_index,
			$post->datetime_period_item_start,
			$post->datetime_period_item_end,

			$post->timeframe_id,
			CB_Query::get_post_type( 'location',         $post->location_ID )
		);

		CB_Query::copy_all_properties( $post, $object );

		return $object;
  }

  static function &factory(
		$ID,
    $period,     // CB_Period
    $recurrence_index,
    $datetime_period_item_start,
    $datetime_period_item_end,

    $timeframe_id,
    $location    // CB_Location
  ) {
    // Design Patterns: Factory Singleton with Multiton
		if ( isset( self::$all[$ID] ) ) {
			$object = self::$all[$ID];
    } else {
			$reflection = new ReflectionClass( __class__ );
			$object     = $reflection->newInstanceArgs( func_get_args() );
      self::$all[$ID] = $object;
    }

    return $object;
  }

  function name_field() {
    return self::$name_field;
  }

  function jsonSerialize() {
    $array = parent::jsonSerialize();
    //$array[ 'location' ] = &$this->location;
    return $array;
  }
}
CB_Query::register_schema_type( 'CB_PeriodItem_Location' );

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_PeriodItem_Timeframe extends CB_PeriodItem {
  static $name_field = array( 'location', 'item' );
  static $database_table = 'cb2_timeframe_period_groups';
  static $database_options_table = 'cb2_timeframe_options';
  public  static $post_type_args = array(
		'menu_icon' => 'dashicons-admin-page',
		'label'     => 'Item Timeframes',
  );

	static public $static_post_type = 'perioditem-timeframe';

  function post_type() {return self::$static_post_type;}

  function __construct(
		$ID,
    $period,
		$recurrence_index,
		$datetime_period_item_start,
		$datetime_period_item_end,

		$timeframe_id,
		$location, // CB_Location
		$item      // CB_Item
  ) {
    parent::__construct(
			$ID,
			$period,
			$recurrence_index,
			$datetime_period_item_start,
			$datetime_period_item_end
    );
    $this->timeframe_id = $timeframe_id;

    $this->location = $location;
    array_push( $this->posts, $this->location );

    $this->item = $item;
    array_push( $this->posts, $this->item );
    $this->item->add_period( $this );
  }

  static function &factory_from_wp_post( $post ) {
		CB_Query::get_metadata_assign( $post ); // Retrieves ALL meta values

		$object = self::factory(
			$post->ID,
			CB_Query::get_post_type( 'period', $post->period_ID ),
			$post->recurrence_index,
			$post->datetime_period_item_start,
			$post->datetime_period_item_end,

			$post->timeframe_id,
			CB_Query::get_post_type( 'location',         $post->location_ID ),
			CB_Query::get_post_type( 'item',             $post->item_ID )
		);

		CB_Query::copy_all_properties( $post, $object );

		return $object;
  }

  static function &factory(
		$ID,
    $period,     // CB_Period
    $recurrence_index,
    $datetime_period_item_start,
    $datetime_period_item_end,

    $timeframe_id,
    $location,   // CB_Location
    $item        // CB_Item
  ) {
    // Design Patterns: Factory Singleton with Multiton
		if ( isset( self::$all[$ID] ) ) {
			$object = self::$all[$ID];
    } else {
			$reflection = new ReflectionClass( __class__ );
			$object     = $reflection->newInstanceArgs( func_get_args() );
      self::$all[$ID] = $object;
    }

    return $object;
  }

  function name_field() {
    return self::$name_field;
  }

  function get_option( $option, $default = FALSE ) {
		$value = $default;
		if ( isset( $this->period_database_record ) && isset( $this->period_database_record->$option ) )
      $value = $this->period_database_record->$option;
		return $value;
  }

  function update_option( $option, $new_value, $autoload = TRUE ) {
		// TODO: complete update_option()
    $update = CB_Database_UpdateInsert::factory( self::$database_options_table );
    $update->add_field(     'option_name',  $option );
    $update->add_field(     'option_value', $new_value );
    $update->add_condition( 'timeframe_id', $timeframe_id );
    $update->run();

    return $this;
  }

  function jsonSerialize() {
    $array = parent::jsonSerialize();
    //$array[ 'location' ] = &$this->location;
    //$array[ 'item' ]     = &$this->item;
    return $array;
  }
}
CB_Query::register_schema_type( 'CB_PeriodItem_Timeframe' );


// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_PeriodItem_Timeframe_User extends CB_PeriodItem {
  static $name_field = array( 'location', 'item', 'user' );
  static $database_table = 'cb2_timeframe_user_period_groups';
  public  static $post_type_args = array(
		'menu_icon' => 'dashicons-admin-page',
		'label'     => 'User Periods',
  );

	static public $static_post_type = 'perioditem-user';

  function post_type() {return self::$static_post_type;}

  function __construct(
		$ID,
    $period,
		$recurrence_index,
		$datetime_period_item_start,
		$datetime_period_item_end,

		$timeframe_id,
		$location, // CB_Location
		$item,     // CB_Item
		$user      // CB_User
  ) {
    parent::__construct(
			$ID,
			$period,
			$recurrence_index,
			$datetime_period_item_start,
			$datetime_period_item_end
		);
    $this->timeframe_id = $timeframe_id;

    $this->location = $location;
    array_push( $this->posts, $this->location );

    $this->item = $item;
    array_push( $this->posts, $this->item );

    $this->user = $user;
    array_push( $this->posts, $this->user );
    $this->user->add_period( $this );
  }

  static function &factory_from_wp_post( $post ) {
		CB_Query::get_metadata_assign( $post ); // Retrieves ALL meta values

		$object = self::factory(
			$post->ID,
			CB_Query::get_post_type( 'period', $post->period_ID ),
			$post->recurrence_index,
			$post->datetime_period_item_start,
			$post->datetime_period_item_end,

			$post->timeframe_id,
			CB_Query::get_post_type( 'location',         $post->location_ID ),
			CB_Query::get_post_type( 'item',             $post->item_ID ),
			CB_Query::get_user( $post->user_ID )
		);

		CB_Query::copy_all_properties( $post, $object );

		return $object;
  }

  static function &factory(
		$ID,
    $period,     // CB_Period
    $recurrence_index,
    $datetime_period_item_start,
    $datetime_period_item_end,

    $timeframe_id,
    $location,   // CB_Location
    $item,       // CB_Item
    $user        // CB_User
  ) {
    // Design Patterns: Factory Singleton with Multiton
		if ( isset( self::$all[$ID] ) ) {
			$object = self::$all[$ID];
    } else {
			$reflection = new ReflectionClass( __class__ );
			$object     = $reflection->newInstanceArgs( func_get_args() );
      self::$all[$ID] = $object;
    }

    return $object;
  }

  function name_field() {
    return self::$name_field;
  }

  function jsonSerialize() {
    $array = parent::jsonSerialize();
    //$array[ 'location' ] = &$this->location;
    //$array[ 'item' ]     = &$this->item;
    //$array[ 'user' ]     = &$this->user;
    return $array;
  }
}
CB_Query::register_schema_type( 'CB_PeriodItem_Timeframe_User' );