<?php
require_once( 'CB_PeriodStatusType.php' );
require_once( 'CB_PeriodGroup.php' );

class CB_Period extends CB_PostNavigator implements JsonSerializable {
	// TODO: use this generic period class
  public static $database_table = 'cb2_periods';
  public static $all = array();
  static $static_post_type = 'period';
  public static $post_type_args = array(
		'menu_icon' => 'dashicons-admin-settings',
  );

  function post_type() {return self::$static_post_type;}

	static function metaboxes() {
		$format_date = 'Y-m-d';
		return array(
			array(
				'title' => __( 'Period', 'commons-booking-2' ),
				'fields' => array(
					array(
						'name' => __( 'Start Date', 'commons-booking-2' ),
						'id' => 'commons-booking-2' . '_start_date',
						'type' => 'text_date',
						'default' => date( $format_date ),
					),
					array(
						'name' => __( 'End Date', 'commons-booking-2' ),
						'id' => 'commons-booking-2' . '_end_date',
						'type' => 'text_date',
						'default' => (new DateTime())->add( new DateInterval( 'P1D' ) )->format( $format_date ),
					),
				),
			),

			array(
				'title' => __( 'Recurrence', 'commons-booking-2' ),
				'context' => 'side',
				'show_names' => FALSE,
				'fields' => array(
					array(
						'name' => __( 'Type', 'commons-booking-2' ),
						'id' => 'commons-booking-2' . '_recurrence_type',
						'type' => 'select',
						'show_option_none' => TRUE,
						'options'          => array(
							'D' => __( 'Daily', 'commons-booking-2' ),
							'W' => __( 'Weekly', 'commons-booking-2' ),
							'M' => __( 'Monthly', 'commons-booking-2' ),
							'Y' => __( 'Yearly', 'commons-booking-2' ),
						),
					),
				),
			),

			array(
				'title' => __( 'Sequence', 'commons-booking-2' ),
				'context' => 'side',
				'show_names' => FALSE,
				'fields' => array(
					array(
						'name' => __( 'Sequence', 'commons-booking-2' ),
						'id' => 'commons-booking-2' . '_sequence',
						'type' => 'multicheck',
						'options'          => array(
							'1'  => __( 'Monday', 'commons-booking-2' ),
							'2'  => __( 'Tuesday', 'commons-booking-2' ),
							'4'  => __( 'Wednesday', 'commons-booking-2' ),
							'8'  => __( 'Thursday', 'commons-booking-2' ),
							'16' => __( 'Friday', 'commons-booking-2' ),
							'32' => __( 'Saturday', 'commons-booking-2' ),
							'64' => __( 'Sunday', 'commons-booking-2' ),
						),
					),
				),
			),

			array(
				'title' => __( 'Validity Period (optional)', 'commons-booking-2' ),
				'desc'  => __( 'Only relevant for reccurring periods. For example: every Monday during summer.', 'commons-booking-2' ),
				'fields' => array(
					array(
						'name' => __( 'From Date', 'commons-booking-2' ),
						'id' => 'commons-booking-2' . '_start_date',
						'type' => 'text_date',
						'default' => date( $format_date ),
					),
					array(
						'name' => __( 'To Date (optional)', 'commons-booking-2' ),
						'id' => 'commons-booking-2' . '_end_date',
						'type' => 'text_date',
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
						'id' => 'commons-booking-2' . '_exceptions',
						'type' => 'title',
					),
				),
			),

			CB_PeriodGroup::selector_metabox(),
		);
	}

	static function &factory_from_wp_post( $post ) {
		// The WP_Post may have all its metadata loaded already
		// as the wordpress system adds all fields to the WP_Post dynamically
		if ( $post->ID ) CB_Query::get_metadata_assign( $post );

		$object = self::factory(
			$post->ID,
			$post->period_id,
			$post->post_title,
			$post->datetime_part_period_start,
			$post->datetime_part_period_end,
			$post->datetime_from,
			$post->datetime_to,
			$post->recurrence_type,
			$post->recurrence_frequency,
			$post->recurrence_sequence,
			$post->usage_count
		);

		CB_Query::copy_all_properties( $post, $object );

		return $object;
	}

  static function &factory(
		$ID,
		$period_id,
    $name,
		$datetime_part_period_start,
		$datetime_part_period_end,
		$datetime_from,
		$datetime_to,
		$recurrence_type,
		$recurrence_frequency,
		$recurrence_sequence,
		$usage_count = 1
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
		$period_id,
    $name,
    $datetime_part_period_start, // DateTime
    $datetime_part_period_end,   // DateTime
    $datetime_from,              // DateTime
    $datetime_to,                // DateTime (NULL)
    $recurrence_type,
    $recurrence_frequency,
    $recurrence_sequence,
    $usage_count = 1
  ) {
		CB_Query::assign_all_parameters( $this, func_get_args(), __class__ );
		$this->id = $period_id;

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

  function summary( $format = NULL ) {
    $now      = new DateTime();
    $summary  = '';
    if ( $datetime_to && $datetime_to < $now ) $summary .= 'Invalid ';
    else {
			if ( $datetime_from > $now ) $summary .= 'Valid from ' . $this->summary_date( $this->datetime_from ) . ' ';
			if ( $datetime_to   > $now ) $summary .= 'to ' .         $this->summary_date( $this->datetime_to   ) . ' ';
		}
		$summary .= $this->summary_recurrence_type();
		$summary .= ' ' . $this->summary_date_period();
		$summary .= ( $this->usage_once() ? '' : " <span class='cb2-usage-count' title='Used in several Period Groups'>$this->usage_count</span>" );

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
			case 'D': $recurrence_string = 'daily';   break;
			case 'W': $recurrence_string = 'weekly';  break;
			case 'M': $recurrence_string = 'monthly'; break;
			case 'Y': $recurrence_string = 'yearly';  break;
		}
		return $recurrence_string;
  }

  function summary_date_period( $format_date = 'M-d', $format_time = 'H:m' ) {
		$summary = '';
		$now     = new DateTime();
		if ( $this->datetime_part_period_start->format( 'Y-m-d' ) == $this->datetime_part_period_end->format( 'Y-m-d' ) ) {
			if ( $now->format( 'Y-m-d' ) == $this->datetime_part_period_start->format( 'Y-m-d' ) )
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

  function usage_once() {
		return $this->usage_count == 1;
  }

  function classes() {
		return '';
  }

  function jsonSerialize() {
		return $this;
	}
}

CB_Query::register_schema_type( 'CB_Period' );

