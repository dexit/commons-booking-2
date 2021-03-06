<?php
require_once( 'CB2_PeriodStatusType.php' );
require_once( 'CB2_PeriodGroup.php' );

define( 'CB2_LINK_SPLIT_FROM', 'S' );
define( 'CB2_LINK_BASED_ON',   'B' );

//------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------
class CB2_Period extends CB2_DatabaseTable_PostNavigator implements JsonSerializable {
  public static $database_table = 'cb2_periods';
  public static $all            = array();
  private $linked_periods       = array();
  static $static_post_type      = 'period';
  public static $post_type_args = array(
		'menu_icon' => 'dashicons-admin-settings',
  );
  public static $recurrence_type_daily  = 'D';
  public static $recurrence_type_weekly = 'W';
  public static $recurrence_type_monthy = 'M';
  public static $recurrence_type_yearly = 'Y';

  function post_type() {return self::$static_post_type;}

  static function database_table_name() { return self::$database_table; }

  static function database_table_schemas( $prefix ) {
		$period_inst_posts      = "{$prefix}cb2_view_periodinst_posts";
		$period_inst_meta       = "{$prefix}cb2_view_periodinstmeta";
		$period_group_period    = "{$prefix}cb2_period_group_period";
		$periodinst_cache_table = "{$prefix}cb2_cache_periodinsts";
		$postmeta               = "{$prefix}postmeta";
		$id_field               = CB2_Database::id_field( __class__ );
		$safe_updates_off       = CB2_Database::$safe_updates_off;
		$safe_updates_restore   = CB2_Database::$safe_updates_restore;
		$window_days_before_default = 8;
		$window_days_after_default  = 180;

		// period => periodinst cacheing
		$cache_fields   = 'period_id, recurrence_index, datetime_period_inst_start, datetime_period_inst_end, blocked';
		$refresh_cache  = <<<SQL
			set @window_days_before = ifnull((select option_value from wp_options where option_name = 'cb2_window_days_before'), $window_days_before_default);
			set @window_days_after  = ifnull((select option_value from wp_options where option_name = 'cb2_window_days_after'),  $window_days_after_default);
			set @window_from        = now() - INTERVAL @window_days_before DAY;
			set @window_to          = now() + INTERVAL @window_days_after  DAY;
			delete from $periodinst_cache_table;
			insert into $periodinst_cache_table($cache_fields)
				select $cache_fields from {$prefix}cb2_view_periodinsts
				where datetime_period_inst_start between @window_from and @window_to;
SQL;

		$trigger_check_recurrence_type = "
					if new.recurrence_type not in('Y', 'M', 'W', 'D') then
						signal sqlstate '45000' set message_text = 'recurrence_type must be one of Y,M,W,D';
					end if;

					if not new.datetime_to is null and new.datetime_to < new.datetime_from then
						signal sqlstate '45000' set message_text = 'datetime_to must be after datetime_from';
					end if;

					if new.datetime_part_period_end < new.datetime_part_period_start then
						signal sqlstate '45000' set message_text = 'datetime_part_period_end must be after datetime_part_period_start';
					end if;

					if new.recurrence_type is null then
						if new.datetime_from > new.datetime_part_period_start then
							signal sqlstate '45001' set message_text = 'datetime_from cannot be more than datetime_part_period_start';
						end if;
						if not new.datetime_to is null then
							if new.datetime_to < new.datetime_part_period_start then
								signal sqlstate '45002' set message_text = 'datetime_to cannot be less than datetime_part_period_start';
							elseif new.datetime_to < new.datetime_part_period_end then
								signal sqlstate '45003' set message_text = 'datetime_to cannot be less than datetime_part_period_end';
							end if;
						end if;
					end if;";

		return array(
			array(
				'name'    => self::$database_table,
				'columns' => array(
					// TYPE, (SIZE), CB2_UNSIGNED, NOT NULL, CB2_AUTO_INCREMENT, DEFAULT, COMMENT
					$id_field     => array( CB2_INT,     (11),   CB2_UNSIGNED, CB2_NOT_NULL, CB2_AUTO_INCREMENT ),
					'name'        => array( CB2_VARCHAR, (1024), NULL,     CB2_NOT_NULL, FALSE, 'period' ),
					'description' => array( CB2_VARCHAR, (2048), NULL,     NULL,     FALSE, NULL ),
					'datetime_part_period_start' => array( CB2_DATETIME, NULL, NULL, CB2_NOT_NULL, FALSE, CB2_CURRENT_TIMESTAMP, 'Only part of this datetime may be used, depending on the recurrence_type' ),
					'datetime_part_period_end'   => array( CB2_DATETIME, NULL, NULL, CB2_NOT_NULL, FALSE, CB2_CURRENT_TIMESTAMP, 'Only part of this datetime may be used, depending on the recurrence_type' ),
					'recurrence_type'      => array( CB2_CHAR,     (1),  NULL,     NULL,     FALSE, NULL, 'recurrence_type:\nNULL - no recurrence\nD - daily recurrence (start and end time parts used only)\nW - weekly recurrence (day-of-week and start and end time parts used only)\nM - monthly recurrence (day-of-month and start and end time parts used only)\nY - yearly recurrence (full absolute start and end time parts used)' ),
					'recurrence_frequency' => array( CB2_INT,      (11), CB2_UNSIGNED, CB2_NOT_NULL, FALSE, 1, 'e.g. Every 2 weeks' ),
					'datetime_from'        => array( CB2_DATETIME, NULL, NULL,     CB2_NOT_NULL, FALSE, CB2_CURRENT_TIMESTAMP, 'Absolute date: when the period should start appearing in the calendar' ),
					'datetime_to'          => array( CB2_DATETIME, NULL, NULL,     NULL,     FALSE, NULL, 'Absolute date: when the period should stop appearing in the calendar' ),
					'recurrence_sequence'  => array( CB2_BIT,      (32), NULL,     CB2_NOT_NULL, FALSE, 0 ),
					'author_ID'            => array( CB2_BIGINT,   (20), CB2_UNSIGNED,     CB2_NOT_NULL, FALSE, 1 ),
				),
				'primary key'  => array( $id_field ),
				'foreign keys' => array(
					'author_ID' => array( 'users', 'ID' ),
				),
				'triggers'     => array(
					'BEFORE INSERT' => array( $trigger_check_recurrence_type ),
					'BEFORE UPDATE' => array( $trigger_check_recurrence_type ),
					'AFTER UPDATE'  => array( $refresh_cache ),
					'AFTER DELETE'  => array( $refresh_cache ),
					'AFTER INSERT'  => array( $refresh_cache ),
				),
			),

			array(
				'name'    => 'cb2_period_linked_period',
				'columns' => array(
					'period_id'        => array( CB2_INT,     (11), CB2_UNSIGNED, CB2_NOT_NULL, ),
					'period_linked_id' => array( CB2_INT,     (11), CB2_UNSIGNED, CB2_NOT_NULL, ),
					'reason'           => array( CB2_CHAR,    (1),  NULL, CB2_NOT_NULL, FALSE, 'B' ),
				),
				'primary key'  => array( 'period_id', 'period_linked_id' ),
				'foreign keys' => array(
					'period_id'        => array( 'cb2_periods', 'period_id' ),
					'period_linked_id' => array( 'cb2_periods', 'period_id' ),
				),
			),

			array(
				'name'    => 'cb2_cache_periodinsts',
				'columns' => array(
					'period_id'        => array( CB2_INT,     (11), CB2_UNSIGNED, CB2_NOT_NULL, ),
					'recurrence_index' => array( CB2_INT,     (11), CB2_UNSIGNED, CB2_NOT_NULL, ),
					'datetime_period_inst_start' => array( CB2_DATETIME, NULL, NULL, CB2_NOT_NULL, ),
					'datetime_period_inst_end'   => array( CB2_DATETIME, NULL, NULL, CB2_NOT_NULL, ),
					'blocked'          => array( CB2_TINYINT, (1),  CB2_UNSIGNED, CB2_NOT_NULL, ),
				),
				'primary key' => array( 'period_id', 'recurrence_index', ),
				'keys'        => array(
					'datetime_period_inst_start',
					'datetime_period_inst_end',
				),
			),
		);
  }

  static function database_views( $prefix ) {
		return array(
			'cb2_view_period_posts' => "select (`p`.`period_id` + `pt_p`.`ID_base`) AS `ID`,1 AS `post_author`,`p`.`datetime_from` AS `post_date`,`p`.`datetime_from` AS `post_date_gmt`,`p`.`description` AS `post_content`,`p`.`name` AS `post_title`,'' AS `post_excerpt`,'publish' AS `post_status`,'closed' AS `comment_status`,'closed' AS `ping_status`,'' AS `post_password`,(`p`.`period_id` + `pt_p`.`ID_base`) AS `post_name`,'' AS `to_ping`,'' AS `pinged`,`p`.`datetime_to` AS `post_modified`,`p`.`datetime_to` AS `post_modified_gmt`,'' AS `post_content_filtered`,0 AS `post_parent`,'' AS `guid`,0 AS `menu_order`,`pt_p`.`post_type` AS `post_type`,'' AS `post_mime_type`,0 AS `comment_count`,`p`.`period_id` AS `period_id`,`p`.`datetime_part_period_start` AS `datetime_part_period_start`,`p`.`datetime_part_period_end` AS `datetime_part_period_end`,`p`.`datetime_from` AS `datetime_from`,`p`.`datetime_to` AS `datetime_to`,`p`.`recurrence_type` AS `recurrence_type`,`p`.`recurrence_sequence` AS `recurrence_sequence`,`p`.`recurrence_frequency` AS `recurrence_frequency`,(select group_concat((`pgp`.`period_group_id` + `pt`.`ID_base`) separator ',') from (`{$prefix}cb2_period_group_period` `pgp` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type_id` = 2))) where (`pgp`.`period_id` = `p`.`period_id`)) AS `period_group_IDs` from ((`{$prefix}cb2_periods` `p` join `{$prefix}cb2_post_types` `pt_p` on((`pt_p`.`post_type_id` = 1))) join `{$prefix}cb2_post_types` `pt_pst` on((`pt_pst`.`post_type_id` = 8)))",
			'cb2_view_periodmeta'   => "select ((`p`.`period_id` * 10) + `pt`.`ID_base`) AS `meta_id`,`p`.`ID` AS `post_id`,`p`.`ID` AS `period_id`,'period_id' AS `meta_key`,`p`.`period_id` AS `meta_value` from (`{$prefix}cb2_view_period_posts` `p` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = `p`.`post_type`))) union all select (((`p`.`period_id` * 10) + `pt`.`ID_base`) + 1) AS `meta_id`,`p`.`ID` AS `post_id`,`p`.`ID` AS `period_id`,'datetime_part_period_start' AS `meta_key`,`p`.`datetime_part_period_start` AS `meta_value` from (`{$prefix}cb2_view_period_posts` `p` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = `p`.`post_type`))) union all select (((`p`.`period_id` * 10) + `pt`.`ID_base`) + 2) AS `meta_id`,`p`.`ID` AS `post_id`,`p`.`ID` AS `period_id`,'datetime_part_period_end' AS `meta_key`,`p`.`datetime_part_period_end` AS `meta_value` from (`{$prefix}cb2_view_period_posts` `p` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = `p`.`post_type`))) union all select (((`p`.`period_id` * 10) + `pt`.`ID_base`) + 3) AS `meta_id`,`p`.`ID` AS `post_id`,`p`.`ID` AS `period_id`,'datetime_from' AS `meta_key`,`p`.`datetime_from` AS `meta_value` from (`{$prefix}cb2_view_period_posts` `p` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = `p`.`post_type`))) union all select (((`p`.`period_id` * 10) + `pt`.`ID_base`) + 4) AS `meta_id`,`p`.`ID` AS `post_id`,`p`.`ID` AS `period_id`,'datetime_to' AS `meta_key`,`p`.`datetime_to` AS `meta_value` from (`{$prefix}cb2_view_period_posts` `p` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = `p`.`post_type`))) union all select (((`p`.`period_id` * 10) + `pt`.`ID_base`) + 5) AS `meta_id`,`p`.`ID` AS `post_id`,`p`.`ID` AS `period_id`,'recurrence_type' AS `meta_key`,`p`.`recurrence_type` AS `meta_value` from (`{$prefix}cb2_view_period_posts` `p` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = `p`.`post_type`))) union all select (((`p`.`period_id` * 10) + `pt`.`ID_base`) + 6) AS `meta_id`,`p`.`ID` AS `post_id`,`p`.`ID` AS `period_id`,'recurrence_frequency' AS `meta_key`,`p`.`recurrence_frequency` AS `meta_value` from (`{$prefix}cb2_view_period_posts` `p` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = `p`.`post_type`))) union all select (((`p`.`period_id` * 10) + `pt`.`ID_base`) + 7) AS `meta_id`,`p`.`ID` AS `post_id`,`p`.`ID` AS `period_id`,'recurrence_sequence' AS `meta_key`,cast(`p`.`recurrence_sequence` as unsigned) AS `meta_value` from (`{$prefix}cb2_view_period_posts` `p` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = `p`.`post_type`))) union all select (((`p`.`period_id` * 10) + `pt`.`ID_base`) + 8) AS `meta_id`,`p`.`ID` AS `post_id`,`p`.`ID` AS `period_id`,'period_group_IDs' AS `meta_key`,`p`.`period_group_IDs` AS `meta_value` from (`{$prefix}cb2_view_period_posts` `p` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = `p`.`post_type`))) union all select (((`p`.`period_id` * 10) + `pt`.`ID_base`) + 9) AS `meta_id`,`p`.`ID` AS `post_id`,`p`.`ID` AS `period_id`,'post_type' AS `meta_key`,`p`.`post_type` AS `meta_value` from (`{$prefix}cb2_view_period_posts` `p` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = `p`.`post_type`)))",
		);
	}

	static function metaboxes() {
		$metaboxes         = array();
		$now               = new CB2_DateTime();
		$day_start_format  = CB2_Query::$date_format . ' 00:00:00';
		$morning_format    = CB2_Query::$date_format . ' 08:00:00';
		$evening_format    = CB2_Query::$date_format . ' 18:00:00';

		// Recurrence sequence: Day selector
		$day_options  = array();
		$days_of_week = CB2_Week::days_of_week();
		for ( $i = 0; $i < count( $days_of_week ); $i++ ) {
			$day_options[pow(2, $i)] = __( $days_of_week[$i] );
		}

		// Recurrence Type options
		$recurrence_types = array(
			CB2_Database::$NULL_indicator => __( 'None', 'commons-booking-2' ),
			'D' => __( 'Daily', 'commons-booking-2' ),
			'W' => __( 'Weekly', 'commons-booking-2' ),
			'M' => __( 'Monthly', 'commons-booking-2' ),
			'Y' => __( 'Yearly', 'commons-booking-2' ),
		);

		// ------------------------------------------ Full advanced interface for period definition
		array_push( $metaboxes,
			array(
				'title'      => __( 'Period', 'commons-booking-2' ),
				//'show_on_cb' => array( 'CB2', 'is_published' ),
				'fields'     => array(
					array(
						'id'      => 'period_explanation',
						'type'    => 'paragraph',
						'float'   => 'right',
						'width'   => 300,
						'desc'    => 'To create separate repeating slots see <b>Recurrence</b> below.
							For example: repeats Mon - Fri 8:00 - 18:00 should use Daily <b>Recurrence Type</b>
							and Mon - Fri <b>Sequence</b>.',
					),
					array(
						'name' => __( 'Start', 'commons-booking-2' ),
						'id' => 'datetime_part_period_start',
						'type' => 'text_datetime_timestamp',
						'date_format' => CB2_Database::$database_date_format,
						'default' => ( isset( $_GET['datetime_part_period_start'] ) ? $_GET['datetime_part_period_start'] : $now->format( $morning_format ) ),
					),
					array(
						'name' => __( 'End', 'commons-booking-2' ),
						'id' => 'datetime_part_period_end',
						'type' => 'text_datetime_timestamp',
						'date_format' => CB2_Database::$database_date_format,
						'default' => ( isset( $_GET['datetime_part_period_end'] ) ? $_GET['datetime_part_period_end'] : $now->format( $evening_format ) ),
					),
				),
			),

			array(
				'title' => __( 'Recurrence', 'commons-booking-2' ),
				'context'    => 'normal',
				'show_names' => TRUE,
				'add_button'    => __( 'Add Another Entry', 'commons-booking-2' ),
				'remove_button' => __( 'Remove Entry', 'commons-booking-2' ),
				//'show_on_cb' => array( 'CB2', 'is_published' ),
				//'closed'     => ! isset( $_GET['recurrence_type'] ),
				'fields' => array(
					array(
						'name' => __( 'Type', 'commons-booking-2' ),
						'id' => 'recurrence_type',
						'type' => 'radio_inline',
						'default' => ( isset( $_GET['recurrence_type'] ) ? $_GET['recurrence_type'] : CB2_Database::$NULL_indicator ),
						'options' => $recurrence_types,
					),
					array(
						'name' => __( 'Daily Sequence', 'commons-booking-2' ),
						'id' => 'recurrence_sequence',
						'type' => 'multicheck_inline',
						'escape_cb'       => array( 'CB2_Period', 'recurrence_sequence_escape' ),
						'sanitization_cb' => array( 'CB2_Period', 'recurrence_sequence_sanitization' ),
						// TODO: does not work default recurrence_sequence
						'default' => ( isset( $_GET['recurrence_sequence'] ) ? $_GET['recurrence_sequence'] : 0 ),
						'options' => $day_options,
					),
					/*
					TODO: Monthly Sequence
					array(
						'name' => __( 'Monthly Sequence', 'commons-booking-2' ),
						'id' => 'recurrence_sequence',
						'type' => 'multicheck',
						'escape_cb'       => array( 'CB2_Period', 'recurrence_sequence_escape' ),
						'sanitization_cb' => array( 'CB2_Period', 'recurrence_sequence_sanitization' ),
						// TODO: does not work default recurrence_sequence
						'default' => ( isset( $_GET['recurrence_sequence'] ) ? $_GET['recurrence_sequence'] : 0 ),
						'options' => array(
							'1'  => __( 'January', 'commons-booking-2' ),
							'2'  => __( 'February', 'commons-booking-2' ),
							'4'  => __( 'March', 'commons-booking-2' ),
							'8'  => __( 'April', 'commons-booking-2' ),
							'16' => __( 'May', 'commons-booking-2' ),
							'32' => __( 'June', 'commons-booking-2' ),
							'64' => __( 'July', 'commons-booking-2' ),
							'128' => __( 'August', 'commons-booking-2' ),
							'256' => __( 'September', 'commons-booking-2' ),
							'1024' => __( 'October', 'commons-booking-2' ),
							'2048' => __( 'November', 'commons-booking-2' ),
							'4096' => __( 'December', 'commons-booking-2' ),
						),
					),
					*/
					array(
						'id'      => 'validity_explanation',
						'type'    => 'paragraph',
						'float'   => 'right',
						'width'   => 300,
						'desc'    => 'The recurrence will repeat indefinitely.
							Here you can provide start and end dates for that recurrnce.',
					),
					array(
						'name' => __( 'Recurrence From Date', 'commons-booking-2' ),
						'id' => 'datetime_from',
						'type' => 'text_datetime_timestamp',
						'date_format' => CB2_Database::$database_date_format,
						'default' => ( isset( $_GET['datetime_from'] ) ? $_GET['datetime_from'] : $now->format( $day_start_format ) ),
					),
					array(
						'name' => __( 'Recurrence To Date (optional)', 'commons-booking-2' ),
						'id' => 'datetime_to',
						'type' => 'text_datetime_timestamp',
						'date_format' => CB2_Database::$database_date_format,
						'default' => ( isset( $_GET['datetime_to'] ) ? $_GET['datetime_to'] : NULL ),
					),
				),
			)
		);

		return $metaboxes;
	}

	static function recurrence_sequence_escape( $value, $field_args, $field ) {
		// Input filter from database to CMB2
		// $value is numeric
		// b'1000010' == 66 in the database, right to left, 2 + 64
		// which is the second day and the sixth day
		// needs to be converted to an array, e.g. (
		//   0 => 4,
		//   1 => 32,
		//   2 => 64,
		// )
		$value = CB2_Query::ensure_int( 'recurrence_sequence', $value );
		return CB2_Query::ensure_assoc_bitarray( 'recurrence_sequence', $value );
	}

	static function recurrence_sequence_sanitization( $value, $field_args, $field ) {
		// Output filter for saving to database
		// Array(4,32,64) = 4 + 32 + 64 => 100
		$old = ( is_array( $value ) ? implode( ',', $value ) : '' );
		$new = CB2_Query::ensure_assoc_bitarray_integer( 'recurrence_sequence', $value );
		if ( CB2_DEBUG_SAVE ) {
			$field_name = $field->args['id'];
			print( "<div class='cb2-WP_DEBUG-small'>CMB2 [$field_name] sanitization ([$old] =&gt; $new)</div>" );
		}
		return $new;
	}

  static function selector_metabox( $multiple = FALSE, String $context = 'normal', Array $classes = array() ) {
		$period_options       = CB2_Forms::period_options( TRUE );
		$periods_count        = count( $period_options ) - 1;
		$plural  = ( $multiple ? 's' : '' );
		$title   = "Period$plural";
		$name    = "period_ID$plural";
		$type    = ( $multiple ? 'multicheck' : 'radio' );
		$default = ( isset( $_GET[$name] ) ? $_GET[$name] : CB2_CREATE_NEW );
		if ( $multiple ) $default = explode( ',', $default );

		$context_normal = ( $context == 'normal' );
		$closed  = ! $context_normal;

		return array(
			'title'      => __( $title, 'commons-booking-2' ) .
												" <span class='cb2-usage-count-ok'>$periods_count</span>",
			'show_names' => FALSE,
			'context'    => $context,
			'closed'     => $closed,
			'fields'     => array(
				array(
					'name'    => __( $title, 'commons-booking-2' ),
					'id'      => $name,
					'type'    => $type,
					'default' => $default,
					'options' => $period_options,
				),
				CB2_Query::metabox_nosave_indicator( $name ),
			),
		);
	}

	static function &factory_from_properties( Array &$properties, &$instance_container = NULL, Bool $force_properties = FALSE, Bool $set_create_new_post_properties = FALSE ) {
		// This may not exist in post creation
		// We do not create the CB2_PeriodGroup objects here
		// because it could create a infinite circular creation
		// as the CB2_PeriodGroups already create their associated CB2_Periods
		$period_group_IDs = array();
		if ( isset( $properties['period_group_IDs'] ) )
			$period_group_IDs = CB2_Query::ensure_ints( 'period_group_IDs', $properties['period_group_IDs'], TRUE );

		// Important that the datetime_from does not mask the whole period
		// this only happens if recurrence_type is NULL
		// so, if it is not specified, we set it to the overall datetime_part_period_start
		// The database will throw helpful Exceptions if anything is amiss
		$datetime_part_period_start = CB2_Query::isset( $properties, 'datetime_part_period_start', CB2_DateTime::day_start()->format() );
		$datetime_from = CB2_Query::isset( $properties, 'datetime_from', $datetime_part_period_start );

		$object = self::factory(
			(int) ( isset( $properties['period_ID'] )  ? $properties['period_ID']            : $properties['ID']   ),
			( isset( $properties['post_title'] )
				? $properties['post_title']
				: ( isset( $properties['name'] ) ? $properties['name'] : '' )
			),
			$datetime_part_period_start,
			( isset( $properties['datetime_part_period_end'] )   ? $properties['datetime_part_period_end']   : CB2_DateTime::day_end()->format() ),
			$datetime_from,
			( isset( $properties['datetime_to'] )          ? $properties['datetime_to']          : NULL ),
			( isset( $properties['recurrence_type'] )      ? $properties['recurrence_type']      : NULL ),
			( isset( $properties['recurrence_frequency'] ) ? $properties['recurrence_frequency'] : NULL ),
			( isset( $properties['recurrence_sequence'] )  ? $properties['recurrence_sequence']  : NULL ),
			$period_group_IDs,
			$properties, $force_properties, $set_create_new_post_properties
		);

		return $object;
	}

  static function &factory(
		Int $ID,
    $name,
		$datetime_part_period_start,
		$datetime_part_period_end,
		$datetime_from        = NULL,
		$datetime_to          = NULL,
		$recurrence_type      = NULL,
		$recurrence_frequency = NULL,
		$recurrence_sequence  = NULL,
		$period_group_IDs = array(),
		Array $properties = NULL,
		Bool $force_properties = FALSE, Bool $set_create_new_post_properties = FALSE
  ) {
		$object = CB2_PostNavigator::createInstance( __class__, func_get_args(), $ID, $properties, $force_properties, $set_create_new_post_properties );
		return $object;
  }

  protected function __construct(
		Int $ID,
    $name,
    $datetime_part_period_start,  // DateTime
    $datetime_part_period_end,    // DateTime
    $datetime_from        = NULL, // DateTime
    $datetime_to          = NULL, // DateTime (NULL)
    $recurrence_type      = NULL,
    $recurrence_frequency = NULL,
    $recurrence_sequence  = NULL,
    $period_group_IDs     = array()
  ) {
		CB2_Query::assign_all_parameters( $this, func_get_args(), __class__ );

		// We helpfully allow setting datetime_from to the datetime_part_period_start here
		// even though the Database DOES NOT allow this
		if ( is_null( $datetime_from ) ) $this->datetime_from = $datetime_part_period_start;

    $this->fullday = ( $this->datetime_part_period_start && $this->datetime_part_period_end )
			&& ( 	 $this->datetime_part_period_start->format( 'H:i:s' ) == '00:00:00'
					&& $this->datetime_part_period_end->format(   'H:i:s' ) == '23:59:59'
				 );

		$day_start = CB2_DateTime::day_start();
		$day_end   = CB2_DateTime::day_end();
		$this->fullworkday = ( $this->datetime_part_period_start && $this->datetime_part_period_end )
			&& ( 	 $this->datetime_part_period_start->format( 'H:i:s' ) == $day_start->format( 'H:i:s' )
					&& $this->datetime_part_period_end->format(   'H:i:s' ) == $day_end->format( 'H:i:s' )
				 );

    parent::__construct( $ID );
  }

  function usage_count() {
		return count( $this->period_group_IDs );
	}

  function user_has_cap( String $required_cap, Bool $current_user_can = NULL ) {
		$new_current_user_can = NULL;
		$Class                = get_class( $this );
		$debug_string         = '';

		if ( $current_user_can !== TRUE ) {
			switch ( $required_cap ) {
				case 'edit_others_posts':
					// If this period is linked to 1 entity-period_group
					// and the user has edit_post on any of the posts in that entity-period_group
					// then grant editing rights
					// e.g. Allow an Item owner to edit periods in bookings
					if ( $this->used_once() ) {
						$first_period_group_ID = $this->period_group_IDs[0];
						$first_period_group    = CB2_Query::get_post_with_type( CB2_PeriodGroup::$static_post_type, $first_period_group_ID );
						if ( $first_period_group->used_once() ) {
							$first_period_entity = $first_period_group->period_entities()[0];

							$count = count( $first_period_entity->posts );
							foreach ( $first_period_entity->posts as $linked_post ) {
								if ( $linked_post != $first_period_entity && method_exists( $linked_post, 'current_user_can' ) ) {
									// Recursive user_has_cap is prevented here by the cb2_user_has_cap()
									$new_current_user_can        = $linked_post->current_user_can( 'edit_post', $current_user_can );
									$new_current_user_can_string = ( $new_current_user_can === TRUE ? 'TRUE' : ( $new_current_user_can === FALSE ? 'FALSE' : 'NULL' ) );
									$current_user_can_string     = ( $current_user_can     === TRUE ? 'TRUE' : ( $current_user_can     === FALSE ? 'FALSE' : 'NULL' ) );
									$debug_string .= ("(Σ$count) $linked_post->post_type(ID:$linked_post->ID/author:$linked_post->post_author) =&gt; $new_current_user_can_string, ");

									// Positive grants on any linked objects override any explicit denys
									// i.e. a deny on the Location is overridden by a grant on the Item
									// and vice versa
									if ( $new_current_user_can === TRUE ) {
										$current_user_can = $new_current_user_can;
										$debug_string .= ( 'break' );
										break;
									}
								}
							}
						} else $debug_string .= ( "(Σ) multiple use period_group, no permission" );
					} else $debug_string .= ( "(Σ) multiple use period, no permission" );

					if ( WP_DEBUG )
						print( "<div class='cb2-WP_DEBUG-security' title='$debug_string'>$Class</div>" );
					break;
			}
		}

		return $current_user_can;
  }

  // ------------------------------------- Output
  function summary( $format = NULL ) {
    $now      = new CB2_DateTime();
    $classes  = implode( ' ', $this->classes() );
    $summary  = "<span class='$classes'>";
    if      ( $this->is_expired() ) $summary .= '<span class="cb2-invalidity">Expired ' . $this->summary_date( $this->datetime_to   )   . ': </span>';
    else if ( $this->is_future() )  $summary .= '<span class="cb2-invalidity">Future '  . $this->summary_date( $this->datetime_from   ) . ': </span>';
    else {
			if ( $this->datetime_from->after( $now ) ) $summary .= 'Valid from ' . $this->summary_date( $this->datetime_from ) . ' ';
			if ( $this->datetime_to && $this->datetime_to->after( $now ) ) $summary .= 'to ' .         $this->summary_date( $this->datetime_to   ) . ' ';
		}
		$summary .= $this->summary_recurrence_type();
		$summary .= ' ' . $this->summary_date_period();

		switch ( $this->usage_count() ) {
			case 0:
				$summary .= " <span class='cb2-usage-count-warning' title='Used in several Period Groups'>0</span>";
				break;
			case 1:
				break;
			default:
				$summary .= " <span class='cb2-usage-count-ok' title='Used in several Period Groups'>" .
					$this->usage_count() .
					"</span>";
		}
    if ( WP_DEBUG ) $summary .= " <span class='cb2-WP_DEBUG-small'>$this->post_author</span>";
    $summary .= '</span>';

		return $summary;
  }

  function summary_date( $date, $format_date = NULL, $format_time = NULL ) {
		if ( is_null( $format_date ) ) {
			$now = new CB2_DateTime();
			if ( $now->format( 'Y' ) == $date->format( 'Y' ) )
				$format_date = CB2_Day::no_year_date_format();
			else
				$format_date = CB2_Day::with_year_date_format();
		}
		return $date->format( $format_date );
  }

  function summary_recurrence_type( ) {
		$recurrence_string = '';

		if ( $this->recurrence_sequence ) {
			// The recurrence_sequence implies the recurrence_type
			$periods  = NULL;
			switch ( $this->recurrence_type ) {
				// TODO: summary_recurrence_type() only days supported so far
				case 'D':
					$periods = CB2_Week::days_of_week();
					break;
			}

			// Apply mask
			if ( $periods ) {
				$sequence         = '';
				$not_all_selected = FALSE;
				for ( $i = 0; $i < count( $periods ); $i++ ) {
					if ( $this->recurrence_sequence & pow( 2, $i ) ) {
						if ( $sequence ) $sequence .= ',';
						$sequence .= $periods[$i];
					} else $not_all_selected = TRUE;
				}
				if ( $sequence && $not_all_selected )
					$recurrence_string .= $sequence;
			}
		}

		if ( empty( $recurrence_string ) ) {
			// No recurrence_sequence, so type needed
			$recurrence_type_string = NULL;
			switch ( $this->recurrence_type ) {
				case 'D': $recurrence_type_string = 'Daily';   break;
				case 'W': $recurrence_type_string = 'Weekly';  break;
				case 'M': $recurrence_type_string = 'Monthly'; break;
				case 'Y': $recurrence_type_string = 'Yearly';  break;
			}
			$recurrence_string .= $recurrence_type_string;
		}

		return $recurrence_string;
  }

  function summary_date_period( $format_date = NULL, $format_time = NULL ) {
		$summary = '';
		$now     = new CB2_DateTime();
		if ( is_null( $format_time ) ) $format_time = get_option( 'time_format' );

		if ( $this->datetime_part_period_start->format( CB2_Query::$date_format ) == $this->datetime_part_period_end->format( CB2_Query::$date_format ) ) {
			if ( $this->recurrence_type != 'D') {
				if ( $now->format( CB2_Query::$date_format ) == $this->datetime_part_period_start->format( CB2_Query::$date_format ) )
					$summary .= __( 'Today' );
				else
					$summary .= $this->summary_date( $this->datetime_part_period_start );
			}

			if      ( $this->fullday )     $summary .= ' ' . __( 'full day' );
			else if ( $this->fullworkday ) $summary .= ' ' . __( 'full working day' );
			else {
				$summary .= ' ';
				$summary .= $this->datetime_part_period_start->format( $format_time );
				$summary .= ' - ';
				$summary .= $this->datetime_part_period_end->format( $format_time );
			}
		} else {
			$format   = CB2_Day::with_year_date_format( $format_time );
			$summary .= $this->datetime_part_period_start->format( $format );
			$summary .= ' - ';
			$summary .= $this->datetime_part_period_end->format( $format );
		}
		return $summary;
	}

  function row_actions( &$actions, $post, $row_actions = array() ) {
		if ( $this->used() ) unset( $actions['trash'] );
		unset( $actions['view'] );

		if ( in_array( 'attach', $row_actions ) ) {
			$period_group_IDs  = $_GET['period_group_IDs'];
			$attach_text       = __( 'Attach' );
			$cancel_text       = __( 'Cancel Attach' );
			$page              = 'cb2-period-groups';
			$do_action         = 'CB2_PeriodGroup::attach';
			$attach_link       = "admin.php?page=$page&period_ID=$this->ID&period_group_IDs=$period_group_IDs&do_action=$do_action";
			$actions['attach'] = "<a href='$attach_link'>$attach_text</a>";
			$actions['cancel'] = "<a style='color:red;' href='admin.php?page=cb2-periods'>$cancel_text</a>";

			if ( ! $this->used() ) unset( $actions['trash'] );
			unset( $actions['view'] );
			unset( $actions['edit'] );
		}
	}

  function manage_columns( $columns ) {
		$columns['summary'] = 'Summary';
		$columns['periodgroups'] = 'Period Groups <a href="admin.php?page=cb2-period-groups">view all</a>';
		return $columns;
	}

	function custom_columns( $column ) {
		$html = '';
		switch ( $column ) {
			case 'summary':
				$html .= $this->summary();
				break;
			case 'periodgroups':
				switch ( $this->usage_count() ) {
					case 0:
						$html .= 'No Period Group!';
						break;
					case 1:
						$period_group = CB2_Query::get_post_with_type( CB2_PeriodGroup::$static_post_type, $this->period_group_IDs[0] );
						$html .= $period_group->summary();
						break;
					default:
						$html .= '<ul cb2-content>';
						foreach ( $this->period_group_IDs as $period_group_ID ) {
							$period_group = CB2_Query::get_post_with_type( CB2_PeriodGroup::$static_post_type, $period_group_ID );
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
		$now = new CB2_DateTime();
		return ! is_null( $this->datetime_to ) && $this->datetime_to->before( $now );
	}

	function is_future() {
		$now = new CB2_DateTime();
		return $this->datetime_from >= $now;
	}

	function is_valid() {
		return ( ! $this->is_expired() && ! $this->is_future() );
	}

	function classes() {
		$classes = array();
		if ( $this->recurrence_type ) array_push( $classes, "cb2-recurrence-type-$this->recurrence_type" );
		array_push( $classes, ( $this->recurrence_type ? 'cb2-has-recurrence' : 'cb2-no-recurrence' ) );
		if ( $this->is_expired() ) array_push( $classes, 'cb2-expired cb2-invalid' );
		if ( $this->is_future() )  array_push( $classes, 'cb2-future' );
		return $classes;
  }

  function linkTo( CB2_Period $linked_period, String $reason = 'B' ) {
		// B - based on
		// I - instance of
		if ( ! isset( $this->linked_periods[$reason] ) ) $this->linked_periods[$reason] = array();
		array_push( $this->linked_periods[$reason], $linked_period );
  }

  function post_post_update() {
		global $wpdb;

		if ( CB2_DEBUG_SAVE ) {
			$Class = get_class( $this );
			print( "<div class='cb2-WP_DEBUG'>$Class::post_post_update($this->ID) dependencies</div>" );
		}

		$table = "{$wpdb->prefix}cb2_period_linked_period";
		foreach ( $this->linked_periods as $reason => $periods ) {
			foreach ( $periods as $period ) {
				$wpdb->delete( $table, array(
					'period_id'        => $this->id(),
					'period_linked_id' => $period->id(),
				) );
				$wpdb->insert( $table, array(
					'period_id'        => $this->id(),
					'period_linked_id' => $period->id(),
					'reason'           => $reason,
				) );
			}
		}
  }

  function period_instance( Int $recurrence_index ) {
		$query = new WP_Query( array(
			'post_type'   => CB2_PeriodInst::$all_post_types,
			'meta_query'  => array(
				'period_ID_clause' => array(
					'key'   => 'period_ID',
					'value' => $this->ID,
				),
				'relation' => 'AND',
				'recurrence_index_clause' => array(
					'key'   => 'recurrence_index',
					'value' => $recurrence_index,
				),
			),
			'posts_per_page' => 1,
			'page'           => 0,
		) );
		return $query->post;
  }

	function jsonSerialize() {
		return $this;
	}
}
