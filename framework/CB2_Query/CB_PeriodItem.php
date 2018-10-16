<?php
require_once( 'CB_Period.php' );

class CB_PeriodItem extends CB_PostNavigator implements JsonSerializable {
  public  static $all              = array();
  public  static $static_post_type = 'perioditem';
  public  static $postmeta_table   = FALSE;
  public  static $standard_fields  = array(
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
  private $priority_overlap_periods     = array();
  private $top_priority_overlap_period  = NULL;

  protected function __construct(
		$ID,
		$period_entity,
    $period,
    $recurrence_index,
    $datetime_period_item_start,
    $datetime_period_item_end
  ) {
		CB_Query::assign_all_parameters( $this, func_get_args(), __class__ );

		// Some sanity checks
		if ( $this->datetime_period_item_start > $this->datetime_period_item_end )
			throw new Exception( 'datetime_period_item_start > datetime_period_item_end' );

		// Add the period to all the days it appears in
		// CB_Day::factory() will lazy create singleton CB_Day's
		if ( $this->datetime_period_item_start ) {
			$date = clone $this->datetime_period_item_start;
			do {
				$day = CB_Day::factory( clone $date );
				$day->add_perioditem( $this );
				$date->add( new DateInterval( 'P1D' ) );
			} while ( $date < $this->datetime_period_item_end );

			// Overlapping periods
			// Might partially overlap many different non-overlapping periods
			// TO DO: location-location doesn't overlap, item-item doesn't overlap
			foreach ( self::$all as $existing_perioditem ) {
				if ( $this->overlaps( $existing_perioditem ) ) {
					$existing_perioditem->add_new_overlap( $this );
					$this->add_new_overlap( $existing_perioditem );
				}
			}
		}

    parent::__construct();

    if ( ! is_null( $ID ) && $ID != CB2_CREATE_NEW ) self::$all[$ID] = $this;
  }

  static function factory_subclass(
		$ID,
		$period_entity, // CB_PeriodEntity
    $period,        // CB_Period
    $recurrence_index,
    $datetime_period_item_start,
    $datetime_period_item_end
  ) {
		// provides appropriate sub-class based on final object parameters
		$object = NULL;
		if      ( $user )     $object = CB_PeriodItem_Timeframe_User::factory(
				$ID,
				$period_entity,
				$period,
				$recurrence_index,
				$datetime_period_item_start,
				$datetime_period_item_end
			);
		else if ( $item )     $object = CB_PeriodItem_Timeframe::factory(
				$ID,
				$period_entity,
				$period,
				$recurrence_index,
				$datetime_period_item_start,
				$datetime_period_item_end
			);
		else if ( $location ) $object = CB_PeriodItem_Location::factory(
				$ID,
				$period_entity,
				$period,
				$recurrence_index,
				$datetime_period_item_start,
				$datetime_period_item_end
			);
		else                  $object = CB_PeriodItem_Global::factory(
				$ID,
				$period_entity,
				$period,
				$recurrence_index,
				$datetime_period_item_start,
				$datetime_period_item_end
			);

		return $object;
  }

  function overlaps( $period ) {
		return $this->overlaps_time( $period );
  }

  function overlaps_time( $period ) {
		return ( $this->datetime_period_item_start >= $period->datetime_period_item_start
			    && $this->datetime_period_item_start <= $period->datetime_period_item_end )
			||   ( $this->datetime_period_item_end   >= $period->datetime_period_item_start
			    && $this->datetime_period_item_end   <= $period->datetime_period_item_end );
  }

  function get_the_edit_post_link( $text = null, $before = '', $after = '', $id = 0, $class = 'post-edit-link' ) {
		// Redirect the edit post to the entity
		return parent::get_the_edit_post_link( $text, $before, $after, ( $id ? $id : $this->period_entity->ID ), $class );
  }

  function priority() {
		$priority = $this->period_entity->period_status_type->priority;
		return (int) $priority;
  }

	function summary() {
		return ucfirst( $this->post_type() ) . "($this->ID)";
	}

  function add_new_overlap( $new_perioditem ) {
		// A Linked list of overlapping periods is not logical
		// Just because A overlaps B and B overlaps C
		//   does not mean that A overlaps C
		if ( $new_perioditem->priority() > $this->priority() ) {
			$this->priority_overlap_periods[ $new_perioditem->priority() ] = $new_perioditem;
			if ( is_null( $this->top_priority_overlap_period )
				|| $new_perioditem->priority() > $this->top_priority_overlap_period->priority()
			)
				$this->top_priority_overlap_period = $new_perioditem;
		}
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
    if ( $this->period_entity ) $classes .= $this->period_entity->period_status_type->classes();
    $classes .= ' cb2-period-group-type-' . $this->post_type();
    $classes .= ( $this->top_priority_overlap_period ? ' cb2-perioditem-has-overlap' : ' cb2-perioditem-no-overlap' );
    return $classes;
  }

  function is_top_priority() {
  	return !$this->top_priority_overlap_period;
  }

  function styles() {
    $styles = '';

    $day_percent_position = $this->day_percent_position();
    $styles .= "top:$day_percent_position[start_percent]%;";
    $styles .= "height:$day_percent_position[diff_percent]%;";

    $styles .= $this->period_entity->period_status_type->styles();

    return $styles;
  }

  function add_actions( &$actions, $post ) {
		$period_ID = $this->period->ID;
		$actions[ 'edit-definition' ] = "<a href='/wp-admin/post.php?post=$period_ID&action=edit'>Edit definition</a>";
		$actions[ 'trash occurence' ] = '<a href="#" class="submitdelete">Trash Occurence</a>';
	}

  function indicators() {
    $indicators = array();
    if ( $this->period_entity ) $indicators = $this->period_entity->period_status_type->indicators();
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
				$name_value .= cb2_get_field( $name_field_name, $class, $date_format );
			}
		} else if ( property_exists( $object, $name_field_names ) ) {
			$name_value = $object->$name_field_names;
		}

		return $name_value;
	}

	function period_status_type() {
		return $this->period_entity->period_status_type;
	}

	function period_status_type_id() {
		return ( $this->period_status_type() ? $this->period_status_type()->id() : NULL );
	}

	function period_status_type_name() {
		return ( $this->period_status_type() ? $this->period_status_type()->name : NULL );
	}

	function get_the_time_period( $format = 'H:i' ) {
		// TODO: all the_*() need to be namespaced. In CB_Query? or CB_TemplateFunctions::*()
		$time_period = $this->datetime_period_item_start->format( $format ) . ' - ' . $this->datetime_period_item_end->format( $format );
		if ( $this->period->fullday ) $time_period = 'all day';
		return $time_period;
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
      'period_status_type' => $this->period_entity->period_status_type,
      'recurrence_type' => $this->recurrence_type,
      'recurrence_frequency' => $this->recurrence_frequency,
      'recurrence_sequence' => $this->recurrence_sequence,
      'type' => $this->type(),
      'day_percent_position' => $this->day_percent_position(),
      'classes' => $this->classes(),
      'styles' => $this->styles(),
      'indicators' => $this->indicators(),
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

  function post_type() {return self::$static_post_type;}

  static private $fake_ID = 300000000;
  static function post_from_date( $date ) {
		$startdate = ( clone $date )->setTime( 0, 0 );
		$enddate   = ( clone $startdate )->setTime( 23, 59 );

		return new WP_Post( (object) array(
			'ID' => self::$fake_ID++,
			'post_author'    => 1,
			'post_date'      => $startdate->format( CB_Query::$datetime_format ),
			'post_date_gmt'  => $startdate->format( CB_Query::$datetime_format ),
			'post_content'   => '',
			'post_title'     => 'automatic',
			'post_excerpt'   => '',
			'post_status'    => 'auto-draft',
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
			'post_password'  => '',
			'post_name'      => 'automatic',
			'to_ping' => '',
			'pinged'  => '',
			'post_modified'     => $enddate->format( CB_Query::$datetime_format ),
			'post_modified_gmt' => $enddate->format( CB_Query::$datetime_format ),
			'post_content_filtered' => '',
			'post_parent' => NULL,
			'guid'        => '',
			'menu_order'  => 0,
			'post_type'   => self::$static_post_type,
			'post_mime_type'    => '',
			'comment_count'     => 0,
			'filter'            => 'raw',
			'period_group_ID'   => 0,
			'period_group_type' => 'automatic',
			'period_ID'         => 0,
			'recurrence_index'  => 0,
			'timeframe_id'      => 0,
			'period_entity_ID'  => 0,
			'location_ID' => 0,
			'item_ID' => 0,
			'user_ID' => 0,
			'period_status_type_ID' => 0,
			'period_status_type_name' => '',
			'datetime_period_item_start' => $startdate->format( CB_Query::$datetime_format ),
			'datetime_period_item_end'   => $enddate->format( CB_Query::$datetime_format ),
		) );
	}

  function __construct(
		$ID,
		$period_entity,
    $period,
		$recurrence_index,
		$datetime_period_item_start,
		$datetime_period_item_end
  ) {
		$this->post_type = self::$static_post_type;

    parent::__construct(
			$ID,
			$period_entity,
			$period,
			$recurrence_index,
			$datetime_period_item_start,
			$datetime_period_item_end
    );
  }

  static function &factory_from_wp_post( $post, $instance_container = NULL ) {
		$object = self::factory(
			$post->ID,
			NULL, // period_entity
			NULL, // period
			$post->recurrence_index,
			$post->datetime_period_item_start,
			$post->datetime_period_item_end
		);

		CB_Query::copy_all_wp_post_properties( $post, $object );

		return $object;
  }

  static function &factory(
		$ID,
		$period_entity,
    $period,     // CB_Period
    $recurrence_index,
    $datetime_period_item_start,
    $datetime_period_item_end
  ) {
    // Design Patterns: Factory Singleton with Multiton
		if ( ! is_null( $ID ) && $ID != CB2_CREATE_NEW && isset( self::$all[$ID] ) ) {
			$object = self::$all[$ID];
    } else {
			$reflection = new ReflectionClass( __class__ );
			$object     = $reflection->newInstanceArgs( func_get_args() );
    }

    return $object;
  }

  function period_status_type_name() {
		return NULL;
  }

  function priority() {
		return NULL;
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
		$period_entity,
    $period,
		$recurrence_index,
		$datetime_period_item_start,
		$datetime_period_item_end
  ) {
    parent::__construct(
			$ID,
			$period_entity,
			$period,
			$recurrence_index,
			$datetime_period_item_start,
			$datetime_period_item_end
    );
  }

  static function &factory_from_wp_post( $post, $instance_container = NULL ) {
		if ( $post->ID ) CB_Query::get_metadata_assign( $post ); // Retrieves ALL meta values
		if ( ! $post->period_entity_ID ) throw new Exception( 'CB_PeriodItem_Global requires a period_entity_ID' );
		if ( ! $post->period_ID )        throw new Exception( 'CB_PeriodItem_Global requires a period_ID' );

		$object = self::factory(
			$post->ID,
			CB_Query::get_post_with_type( CB_PeriodEntity_Global::$static_post_type, $post->period_entity_ID ),
			CB_Query::get_post_with_type( CB_Period::$static_post_type,              $post->period_ID ),
			$post->recurrence_index,
			$post->datetime_period_item_start,
			$post->datetime_period_item_end
		);

		CB_Query::copy_all_wp_post_properties( $post, $object );

		return $object;
  }

  static function &factory(
		$ID,
		$period_entity,
    $period,     // CB_Period
    $recurrence_index,
    $datetime_period_item_start,
    $datetime_period_item_end
  ) {
    // Design Patterns: Factory Singleton with Multiton
		if ( ! is_null( $ID ) && $ID != CB2_CREATE_NEW && isset( self::$all[$ID] ) ) {
			$object = self::$all[$ID];
    } else {
			$reflection = new ReflectionClass( __class__ );
			$object     = $reflection->newInstanceArgs( func_get_args() );
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
	static public $static_post_type = 'perioditem-location';
  static public $post_type_args = array(
		'menu_icon' => 'dashicons-admin-page',
		'label'     => 'Location Periods',
  );

  function post_type() {return self::$static_post_type;}

  function __construct(
		$ID,
		$period_entity,
    $period,
		$recurrence_index,
		$datetime_period_item_start,
		$datetime_period_item_end
	) {
    parent::__construct(
			$ID,
			$period_entity,
			$period,
			$recurrence_index,
			$datetime_period_item_start,
			$datetime_period_item_end
    );
    $this->period_entity->location->add_perioditem( $this );
    array_push( $this->posts, $this->period_entity->location );
  }

  static function &factory_from_wp_post( $post, $instance_container = NULL ) {
		if ( $post->ID ) CB_Query::get_metadata_assign( $post ); // Retrieves ALL meta values
		if ( ! $post->period_entity_ID ) throw new Exception( 'CB_PeriodItem_Location requires a period_entity_ID' );
		if ( ! $post->period_ID )        throw new Exception( 'CB_PeriodItem_Location requires a period_ID' );

		$object = self::factory(
			$post->ID,
			CB_Query::get_post_with_type( CB_PeriodEntity_Location::$static_post_type, $post->period_entity_ID ),
			CB_Query::get_post_with_type( CB_Period::$static_post_type,                $post->period_ID ),
			$post->recurrence_index,
			$post->datetime_period_item_start,
			$post->datetime_period_item_end
		);

		CB_Query::copy_all_wp_post_properties( $post, $object );

		return $object;
  }

  static function &factory(
		$ID,
		$period_entity,
    $period,     // CB_Period
    $recurrence_index,
    $datetime_period_item_start,
    $datetime_period_item_end
  ) {
    // Design Patterns: Factory Singleton with Multiton
		if ( ! is_null( $ID ) && $ID != CB2_CREATE_NEW && isset( self::$all[$ID] ) ) {
			$object = self::$all[$ID];
    } else {
			$reflection = new ReflectionClass( __class__ );
			$object     = $reflection->newInstanceArgs( func_get_args() );
    }

    return $object;
  }

	function overlaps( $existing_perioditem ) {
		$parent_overlaps = parent::overlaps( $existing_perioditem );

		$not_different = (
			 ! property_exists( $existing_perioditem, 'location' )
			|| is_null( $this->location )
			|| $this->location->is( $existing_perioditem->period_entity->location )
		);

		return $not_different && $parent_overlaps;
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
		$period_entity,
    $period,
		$recurrence_index,
		$datetime_period_item_start,
		$datetime_period_item_end
  ) {
    parent::__construct(
			$ID,
			$period_entity,
			$period,
			$recurrence_index,
			$datetime_period_item_start,
			$datetime_period_item_end
    );
    array_push( $this->posts, $this->period_entity->location );
    array_push( $this->posts, $this->period_entity->item );
    $this->period_entity->item->add_perioditem( $this );
  }

  static function &factory_from_wp_post( $post, $instance_container = NULL ) {
		if ( $post->ID ) CB_Query::get_metadata_assign( $post ); // Retrieves ALL meta values
		if ( ! $post->period_entity_ID ) throw new Exception( 'CB_PeriodItem_Timeframe requires a period_entity_ID' );
		if ( ! $post->period_ID )        throw new Exception( 'CB_PeriodItem_Timeframe requires a period_ID' );

		$object = self::factory(
			$post->ID,
			CB_Query::get_post_with_type( CB_PeriodEntity_Timeframe::$static_post_type, $post->period_entity_ID ),
			CB_Query::get_post_with_type( CB_Period::$static_post_type,                 $post->period_ID ),
			$post->recurrence_index,
			$post->datetime_period_item_start,
			$post->datetime_period_item_end
		);

		CB_Query::copy_all_wp_post_properties( $post, $object );

		return $object;
  }

  static function &factory(
		$ID,
		$period_entity, // CB_PeriodEntity
    $period,        // CB_Period
    $recurrence_index,
    $datetime_period_item_start,
    $datetime_period_item_end
  ) {
    // Design Patterns: Factory Singleton with Multiton
		if ( ! is_null( $ID ) && $ID != CB2_CREATE_NEW && isset( self::$all[$ID] ) ) {
			$object = self::$all[$ID];
    } else {
			$reflection = new ReflectionClass( __class__ );
			$object     = $reflection->newInstanceArgs( func_get_args() );
    }

    return $object;
  }

	function overlaps( $existing_perioditem ) {
		$parent_overlaps = parent::overlaps( $existing_perioditem );

		$not_different = (
			 ! property_exists( $existing_perioditem, 'item' )
			|| is_null( $this->item )
			|| $this->item->is( $existing_perioditem->period_entity->item )
		);

		return $not_different && $parent_overlaps;
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
    $update->add_condition( 'timeframe_id', $this->id() );
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
  static $name_field             = array( 'location', 'item', 'user' );
  static $database_table         = 'cb2_timeframe_user_period_groups';
  public  static $post_type_args = array(
		'menu_icon' => 'dashicons-admin-page',
		'label'     => 'User Periods',
  );

	static public $static_post_type = 'perioditem-user';

  function post_type() {return self::$static_post_type;}

  function __construct(
		$ID,
		$period_entity,
    $period,
		$recurrence_index,
		$datetime_period_item_start,
		$datetime_period_item_end
  ) {
    parent::__construct(
			$ID,
			$period_entity,
			$period,
			$recurrence_index,
			$datetime_period_item_start,
			$datetime_period_item_end
		);
    array_push( $this->posts, $this->period_entity->location );
    array_push( $this->posts, $this->period_entity->item );
    array_push( $this->posts, $this->period_entity->user );
    $this->period_entity->user->add_perioditem( $this );
  }

  static function &factory_from_wp_post( $post, $instance_container = NULL ) {
		if ( $post->ID ) CB_Query::get_metadata_assign( $post ); // Retrieves ALL meta values
		if ( ! $post->period_entity_ID ) throw new Exception( 'CB_PeriodItem_Timeframe_User requires a period_entity_ID' );
		if ( ! $post->period_ID )        throw new Exception( 'CB_PeriodItem_Timeframe_User requires a period_ID' );

		$object = self::factory(
			$post->ID,
			CB_Query::get_post_with_type( CB_PeriodEntity_Timeframe_User::$static_post_type, $post->period_entity_ID ),
			CB_Query::get_post_with_type( CB_Period::$static_post_type,                      $post->period_ID ),
			$post->recurrence_index,
			$post->datetime_period_item_start,
			$post->datetime_period_item_end
		);

		CB_Query::copy_all_wp_post_properties( $post, $object );

		return $object;
  }

  static function &factory(
		$ID,
		$period_entity, // CB_PeriodEntity
    $period,        // CB_Period
    $recurrence_index,
    $datetime_period_item_start,
    $datetime_period_item_end
  ) {
    // Design Patterns: Factory Singleton with Multiton
		if ( ! is_null( $ID ) && $ID != CB2_CREATE_NEW && isset( self::$all[$ID] ) ) {
			$object = self::$all[$ID];
    } else {
			$reflection = new ReflectionClass( __class__ );
			$object     = $reflection->newInstanceArgs( func_get_args() );
    }

    return $object;
  }

	function overlaps( $existing_perioditem ) {
		$parent_overlaps = parent::overlaps( $existing_perioditem );

		$not_different = (
			 ! property_exists( $existing_perioditem, 'user' )
			|| is_null( $this->user )
			|| $this->user->is( $existing_perioditem->period_entity->user )
		);

		return $not_different && $parent_overlaps;
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
