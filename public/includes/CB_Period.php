<?php
require_once( 'CB_PeriodStatusType.php' );
require_once( 'CB_PeriodGroup.php' );

class CB_Period extends CB_PostNavigator implements JsonSerializable {
  public static $database_table = 'cb2_periods';
  public static $all = array();
  static $static_post_type = 'period';
  public static $post_type_args = array(
		'menu_icon' => 'dashicons-admin-settings',
  );

  function post_type() {return self::$static_post_type;}

	static function metaboxes() {
		$format_date = CB_Query::$date_format;
		return array(
			array(
				'title' => __( 'Period', 'commons-booking-2' ),
				'fields' => array(
					array(
						'name' => __( 'Start Date', 'commons-booking-2' ),
						'id' => 'datetime_part_period_start',
						'type' => 'text_datetime_timestamp',
						'date_format' => CB_Database::$database_date_format,
						'default' => date( $format_date ),
					),
					array(
						'name' => __( 'End Date', 'commons-booking-2' ),
						'id' => 'datetime_part_period_end',
						'type' => 'text_datetime_timestamp',
						'date_format' => CB_Database::$database_date_format,
						'default' => (new DateTime())->add( new DateInterval( 'P1D' ) )->format( $format_date ),
					),
				),
			),

			array(
				'title' => __( 'Recurrence (optional)', 'commons-booking-2' ),
				'context' => 'normal',
				'show_names' => FALSE,
				'add_button'    => __( 'Add Another Entry', 'commons-booking-2' ),
				'remove_button' => __( 'Remove Entry', 'commons-booking-2' ),
				'closed'     => true,
				'fields' => array(
					array(
						'name' => __( 'Type', 'commons-booking-2' ),
						'id' => 'recurrence_type',
						'type' => 'radio_inline',
						'show_option_none' => TRUE,
						// TODO: cannot reset Reccurrence to None after setting it
						// because the value is not sent through and then not changed
						'options'          => array(
							'D' => __( 'Daily', 'commons-booking-2' ),
							'W' => __( 'Weekly', 'commons-booking-2' ),
							'M' => __( 'Monthly', 'commons-booking-2' ),
							'Y' => __( 'Yearly', 'commons-booking-2' ),
						),
					),
					array(
						'name' => __( 'Sequence', 'commons-booking-2' ),
						'id' => 'recurrence_sequence',
						'type' => 'multicheck',
						'options'          => array(
							'1'  => __( 'Sunday', 'commons-booking-2' ),
							'2'  => __( 'Monday', 'commons-booking-2' ),
							'4'  => __( 'Tuesday', 'commons-booking-2' ),
							'8'  => __( 'Wednesday', 'commons-booking-2' ),
							'16' => __( 'Thursday', 'commons-booking-2' ),
							'32' => __( 'Friday', 'commons-booking-2' ),
							'64' => __( 'Saturday', 'commons-booking-2' ),
						),
					),
				),
			),

			array(
				'title' => __( 'Validity Period (optional)', 'commons-booking-2' ),
				'desc'  => __( 'Only relevant for reccurring periods. For example: every Monday during summer.', 'commons-booking-2' ),
				'closed'     => true,
				'fields' => array(
					array(
						'name' => __( 'From Date', 'commons-booking-2' ),
						'id' => 'datetime_from',
						'type' => 'text_datetime_timestamp',
						'date_format' => CB_Database::$database_date_format,
						'default' => date( $format_date ),
					),
					array(
						'name' => __( 'To Date (optional)', 'commons-booking-2' ),
						'id' => 'datetime_to',
						'type' => 'text_datetime_timestamp',
						'date_format' => CB_Database::$database_date_format,
					),
				),
			),

			array(
				'title' => __( 'Exceptions (optional)', 'commons-booking-2' ),
				'desc'  => __( 'Only relevant for reccurring periods. For example: every Monday during summer.', 'commons-booking-2' ),
				'context' => 'side',
				'fields' => array(
					array(
						'name' => __( '<a href="#">add new</a>', 'commons-booking-2' ),
						'id' => 'exceptions',
						'type' => 'title',
					),
				),
			),

			CB_PeriodGroup::selector_metabox(),
		);
	}

  static function selector_metabox() {
		$period_options       = CB_Forms::period_options( CB2_CREATE_NEW );
		$periods_count        = count( $period_options ) - 1;
		return array(
			'title'      => __( 'Existing Period', 'commons-booking-2' ) .
												" <span class='cb2-usage-count'>$periods_count</span>",
			'show_names' => FALSE,
			'context'    => 'side',
			'fields'     => array(
				array(
					'name'    => __( 'Period', 'commons-booking-2' ),
					'id'      => 'period_ID',
					'type'    => 'select',
					//'show_option_none' => TRUE,
					'default' => ( isset( $_GET['period_ID'] ) ? $_GET['period_ID'] : NULL ),
					'options' => $period_options,
				),
			),
		);
	}

	static function &factory_from_wp_post( $post ) {
		// The WP_Post may have all its metadata loaded already
		// as the wordpress system adds all fields to the WP_Post dynamically
		if ( $post->ID ) CB_Query::get_metadata_assign( $post );

		// This may not exist in post creation
		// We do not create the CB_PeriodGroup objects here
		// because it could create a infinite circular creation
		// as the CB_PeriodGroups already create their associated CB_Periods
		$period_group_IDs = array();
		if ( property_exists( $post, 'period_group_IDs' ) )
			$period_group_IDs = CB_Query::ensure_ints( 'period_group_IDs', $post->period_group_IDs, TRUE );

		$object = self::factory(
			$post->ID,
			$post->post_title,
			$post->datetime_part_period_start,
			$post->datetime_part_period_end,
			$post->datetime_from,
			$post->datetime_to,
			$post->recurrence_type,
			$post->recurrence_frequency,
			$post->recurrence_sequence,
			$period_group_IDs
		);

		CB_Query::copy_all_wp_post_properties( $post, $object );

		return $object;
	}

  static function &factory(
		$ID,
    $name,
		$datetime_part_period_start,
		$datetime_part_period_end,
		$datetime_from,
		$datetime_to,
		$recurrence_type,
		$recurrence_frequency,
		$recurrence_sequence,
		$period_group_IDs = array()
  ) {
    // Design Patterns: Factory Singleton with Multiton
		if ( ! is_null( $ID ) && isset( self::$all[$ID] ) ) {
			$object = self::$all[$ID];
    } else {
			$reflection = new ReflectionClass( __class__ );
			$object     = $reflection->newInstanceArgs( func_get_args() );
    }

    return $object;
  }

  public function __construct(
		$ID,
    $name,
    $datetime_part_period_start, // DateTime
    $datetime_part_period_end,   // DateTime
    $datetime_from,              // DateTime
    $datetime_to,                // DateTime (NULL)
    $recurrence_type,
    $recurrence_frequency,
    $recurrence_sequence,
    $period_group_IDs = array()
  ) {
		CB_Query::assign_all_parameters( $this, func_get_args(), __class__ );

    $this->fullday = ( $this->datetime_part_period_start && $this->datetime_part_period_end )
			&& ( 	 $this->datetime_part_period_start->format( 'H:i:s' ) == '00:00:00'
					&& $this->datetime_part_period_end->format(   'H:i:s' ) == '23:59:59'
				 );

		// TODO: make configurable
		$this->fullworkday = ( $this->datetime_part_period_start && $this->datetime_part_period_end )
			&& ( 	 $this->datetime_part_period_start->format( 'H:i:s' ) == '00:09:00'
					&& $this->datetime_part_period_end->format(   'H:i:s' ) == '18:00:00'
				 );

    if ( ! is_null( $ID ) ) self::$all[$ID] = $this;
  }

  function not_used() {
		return $this->usage_count() == 0;
  }

  function used() {
		return $this->usage_count() > 0;
  }

  function usage_once() {
		return $this->usage_count() == 1;
  }

  function usage_count() {
		return count( $this->period_group_IDs );
	}

  // ------------------------------------- Output
  function summary( $format = NULL ) {
    $now      = new DateTime();
    $classes  = $this->classes();
    $summary  = "<span class='$classes'>";
    if      ( $this->is_expired() ) $summary .= '<i class="cb2-invalidity">Expired ' . $this->summary_date( $this->datetime_to   )   . '</i>: ';
    else if ( $this->is_future() )  $summary .= '<i class="cb2-invalidity">Future '  . $this->summary_date( $this->datetime_from   ) . '</i>: ';
    else {
			if ( $this->datetime_from > $now ) $summary .= 'Valid from ' . $this->summary_date( $this->datetime_from ) . ' ';
			if ( $this->datetime_to   > $now ) $summary .= 'to ' .         $this->summary_date( $this->datetime_to   ) . ' ';
		}
		$summary .= $this->summary_recurrence_type();
		$summary .= ' ' . $this->summary_date_period();

		if ( ! $this->usage_once() )
			$summary .= " <span class='cb2-usage-count' title='Used in several Period Groups'>" .
				$this->usage_count() .
				"</span>";
    $summary .= '</span>';

		return $summary;
  }

  function summary_date( $date, $format_date = 'M-d', $format_time = 'H:m' ) {
		$summary = '';
		$now     = new DateTime();
		if ( $now->format( 'Y' ) == $date->format( 'Y' ) ) $summary .= $date->format( $format_date );
		else $summary .= $date->format( "Y-$format_date" );
		return $summary;
  }

  function summary_recurrence_type( ) {
		$recurrence_string = '';
		switch ( $this->recurrence_type ) {
			case 'D': $recurrence_string = 'Daily';   break;
			case 'W': $recurrence_string = 'Weekly';  break;
			case 'M': $recurrence_string = 'Monthly'; break;
			case 'Y': $recurrence_string = 'Yearly';  break;
		}
		return $recurrence_string;
  }

  function summary_date_period( $format_date = 'M-d', $format_time = 'H:m' ) {
		$summary = '';
		$now     = new DateTime();
		if ( $this->datetime_part_period_start->format( CB_Query::$date_format ) == $this->datetime_part_period_end->format( CB_Query::$date_format ) ) {
			if ( $now->format( CB_Query::$date_format ) == $this->datetime_part_period_start->format( CB_Query::$date_format ) )
				$summary .= 'Today';
			else
				$summary .= $this->summary_date( $this->datetime_part_period_start );

			if      ( $this->fullday )     $summary .= ' full day';
			else if ( $this->fullworkday ) $summary .= ' full working day';
			else {
				$summary .= ' ';
				$summary .= $this->datetime_part_period_start->format( $format_time );
				$summary .= ' - ';
				$summary .= $this->datetime_part_period_end->format( $format_time );
			}
		} else {
			$format   = "Y-$format_date $format_time";
			$summary .= $this->datetime_part_period_start->format( $format );
			$summary .= ' - ';
			$summary .= $this->datetime_part_period_end->format( $format );
		}
		return $summary;
	}

  function add_actions( &$actions, $post ) {
		if ( $this->used() ) unset( $actions['trash'] );
	}

  function manage_columns( $columns ) {
		$columns['summary'] = 'Summary';
		$columns['periodgroups'] = 'Period Groups';
		$this->move_column_to_end( $columns, 'date' );
		return $columns;
	}

	function custom_columns( $column ) {
		$html = '';
		switch ( $column ) {
			case 'summary':
				$html .= $this->summary();
				break;
			case 'periodgroups':
				$usage_count = $this->usage_count();
				switch ( $usage_count ) {
					case 0:
						$html .= 'No Period Group!';
						break;
					case 1:
						$period_group = CB_Query::get_post_with_type( CB_PeriodGroup::$static_post_type, $this->period_group_IDs[0] );
						$html .= $period_group->summary();
						break;
					default:
						$html .= "<span class='cb2-usage-count' title='Used in several Period Groups'>$usage_count</span> ";
						$html .= '<ul>';
						foreach ( $this->period_group_IDs as $period_group_ID ) {
							$period_group = CB_Query::get_post_with_type( CB_PeriodGroup::$static_post_type, $period_group_ID );
							$html .= '<li>' . $period_group->summary() . '</li>';
						}
						$html .= '</ul>';
						break;
				}
				break;
		}
		return $html;
	}

	function is_expired() {
		$now = new DateTime();
		return ! is_null( $this->datetime_to ) && $this->datetime_to < $now;
	}

	function is_future() {
		$now = new DateTime();
		return $this->datetime_from >= $now;
	}

	function is_valid() {
		return ( ! $this->is_expired() && ! $this->is_future() );
	}

	function classes() {
		$classes = '';
		if ( $this->is_expired() ) $classes .= ' cb2-expired cb2-invalid';
		if ( $this->is_future() )  $classes .= ' cb2-future cb2-invalid';
		return $classes;
  }

  function post_post_update() {
		global $wpdb;

		// Remove previous relations
		$table = "{$wpdb->prefix}cb2_period_group_period";
		$wpdb->delete( $table, array(
			'period_id' => $this->id()
		) );

		// Link the Period to the PeriodGroup(s)
		// id_from_ID_with_post_type() and id()
		// will throw Exceptions if IDs cannot be found
		foreach ( $this->period_group_IDs as $period_group_ID ) {
			$period_group_id = CB_Query::id_from_ID_with_post_type( $period_group_ID, CB_PeriodGroup::$static_post_type );
			$wpdb->insert( $table, array(
				'period_group_id' => $period_group_id,
				'period_id'       => $this->id(),
			) );
		}
  }

  function jsonSerialize() {
		return $this;
	}
}

CB_Query::register_schema_type( 'CB_Period' );

