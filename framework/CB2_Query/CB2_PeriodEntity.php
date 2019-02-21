<?php
abstract class CB2_PeriodEntity extends CB2_DatabaseTable_PostNavigator implements JsonSerializable {
	public static $all = array();

  protected static function database_table_schema_root(
		String $table_name, String $primary_id_column,
		Array $extra_columns      = array(),
		Array $extra_foreign_keys = array(),
		Array $triggers           = array()
	) {
		$base_columns = array(
			// TYPE, (SIZE), CB2_UNSIGNED, NOT NULL, CB2_AUTO_INCREMENT, DEFAULT, COMMENT
			$primary_id_column      => array( CB2_BIGINT,   (20), CB2_UNSIGNED, CB2_NOT_NULL, CB2_AUTO_INCREMENT ),
			'period_group_id'       => array( CB2_INT,      (11), CB2_UNSIGNED, CB2_NOT_NULL ),
			'period_status_type_id' => array( CB2_INT,      (11), CB2_UNSIGNED, CB2_NOT_NULL ),
			'enabled'               => array( CB2_BIT,      (1),  NULL,         CB2_NOT_NULL, NULL,  1 ),
			'author_ID'             => array( CB2_BIGINT,   (20), CB2_UNSIGNED, CB2_NOT_NULL, FALSE, 1 ),
			'entity_datetime_from'  => array( CB2_DATETIME, NULL, NULL, NULL, NULL, NULL, 'override all period settings' ),
			'entity_datetime_to'    => array( CB2_DATETIME, NULL, NULL, NULL, NULL, NULL, 'override all period settings' ),
			'confirmed_user_id'     => array( CB2_BIGINT,   (20), CB2_UNSIGNED ),
			'approved_user_id'      => array( CB2_BIGINT,   (20), CB2_UNSIGNED ),
		);
		$columns = array_merge( $base_columns, $extra_columns );

		$base_foreign_keys = array(
			'period_group_id'       => array( 'cb2_period_groups',       'period_group_id' ),
			'period_status_type_id' => array( 'cb2_period_status_types', 'period_status_type_id' ),
			'author_ID'             => array( 'users', 'ID' ),
			'confirmed_user_id'     => array( 'users', 'ID' ),
			'approved_user_id'      => array( 'users', 'ID' ),
		);
		$foreign_keys = array_merge( $base_foreign_keys, $extra_foreign_keys );

		$primary_key = array_keys( $extra_columns );
		$primary_key = array_merge( $primary_key, array(
			'period_group_id',
			'period_status_type_id',
		) );

		return array(
			'name'         => $table_name,
			'columns'      => $columns,
			'primary key'  => $primary_key,
			'unique keys'  => array( $primary_id_column ),
			'foreign keys' => $foreign_keys,
			'triggers'     => $triggers,
		);
  }

  static function database_views(  $prefix ) {
		return array(
			'cb2_view_periodent_posts'   => "select ((`p`.`timeframe_id` * `pt_e`.`ID_multiplier`) + `pt_e`.`ID_base`) AS `ID`,1 AS `post_author`,'2018-01-01' AS `post_date`,'2018-01-01' AS `post_date_gmt`,'' AS `post_content`,`p`.`name` AS `post_title`,'' AS `post_excerpt`,if(`p`.`enabled`,'publish','trash') AS `post_status`,'closed' AS `comment_status`,'closed' AS `ping_status`,'' AS `post_password`,(`p`.`timeframe_id` + `pt_e`.`ID_base`) AS `post_name`,'' AS `to_ping`,'' AS `pinged`,'2018-01-01' AS `post_modified`,'2018-01-01' AS `post_modified_gmt`,'' AS `post_content_filtered`,0 AS `post_parent`,'' AS `guid`,0 AS `menu_order`,`pt_e`.`post_type` AS `post_type`,'' AS `post_mime_type`,0 AS `comment_count`,`p`.`timeframe_id` AS `timeframe_id`,ifnull(`p`.`location_ID`,0) AS `location_ID`,ifnull(`p`.`item_ID`,0) AS `item_ID`,ifnull(`p`.`cb2_user_ID`,0) AS `cb2_user_ID`,`p`.`entity_datetime_from` AS `entity_datetime_from`,`p`.`entity_datetime_to` AS `entity_datetime_to`,`p`.`confirmed_user_id` AS `confirmed_user_id`,`p`.`approved_user_id` AS `approved_user_id`,(`p`.`period_group_id` + `pt_pg`.`ID_base`) AS `period_group_ID`,(`p`.`period_status_type_id` + `pt_pst`.`ID_base`) AS `period_status_type_ID`,`p`.`period_status_type_id` AS `period_status_type_native_id`,`pst`.`name` AS `period_status_type_name`,(select group_concat((`{$prefix}cb2_period_group_period`.`period_id` + `pt2`.`ID_base`) separator ',') from (`{$prefix}cb2_period_group_period` join `{$prefix}cb2_post_types` `pt2` on((`pt2`.`post_type_id` = 1))) where (`{$prefix}cb2_period_group_period`.`period_group_id` = `p`.`period_group_id`) group by `{$prefix}cb2_period_group_period`.`period_group_id`) AS `period_IDs`,cast(`p`.`enabled` as unsigned) AS `enabled`,((`p_first`.`period_id` * `pt_p_first`.`ID_multiplier`) + `pt_p_first`.`ID_base`) AS `period_ID`,`p_first`.`datetime_part_period_start` AS `datetime_part_period_start`,`p_first`.`datetime_part_period_end` AS `datetime_part_period_end`,`p_first`.`recurrence_type` AS `recurrence_type`,`p_first`.`recurrence_frequency` AS `recurrence_frequency`,`p_first`.`datetime_from` AS `datetime_from`,`p_first`.`datetime_to` AS `datetime_to`,`p_first`.`recurrence_sequence` AS `recurrence_sequence` from ((((((((select `ip`.`global_period_group_id` AS `timeframe_id`,`pg`.`name` AS `name`,NULL AS `location_ID`,NULL AS `item_ID`,NULL AS `cb2_user_ID`,`ip`.`entity_datetime_from` AS `entity_datetime_from`,`ip`.`entity_datetime_to` AS `entity_datetime_to`,`ip`.`confirmed_user_id` AS `confirmed_user_id`,`ip`.`approved_user_id` AS `approved_user_id`,12 AS `post_type_id`,1 AS `period_group_priority`,`ip`.`period_group_id` AS `period_group_id`,`ip`.`period_status_type_id` AS `period_status_type_id`,`ip`.`enabled` AS `enabled` from (`{$prefix}cb2_global_period_groups` `ip` join `{$prefix}cb2_period_groups` `pg` on((`ip`.`period_group_id` = `pg`.`period_group_id`)))) union all select `ip`.`location_period_group_id` AS `timeframe_ID`,`pg`.`name` AS `name`,`ip`.`location_ID` AS `location_ID`,NULL AS `item_ID`,NULL AS `cb2_user_ID`,`ip`.`entity_datetime_from` AS `entity_datetime_from`,`ip`.`entity_datetime_to` AS `entity_datetime_to`,`ip`.`confirmed_user_id` AS `confirmed_user_id`,`ip`.`approved_user_id` AS `approved_user_id`,13 AS `post_type_id`,2 AS `period_group_priority`,`ip`.`period_group_id` AS `period_group_id`,`ip`.`period_status_type_id` AS `period_status_type_id`,`ip`.`enabled` AS `enabled` from ((`{$prefix}cb2_location_period_groups` `ip` join `{$prefix}cb2_period_groups` `pg` on((`ip`.`period_group_id` = `pg`.`period_group_id`))) join `{$prefix}posts` `loc` on((`ip`.`location_ID` = `loc`.`ID`))) union all select `ip`.`timeframe_period_group_id` AS `timeframe_ID`,`pg`.`name` AS `name`,`ip`.`location_ID` AS `location_ID`,`ip`.`item_ID` AS `item_ID`,NULL AS `cb2_user_ID`,`ip`.`entity_datetime_from` AS `entity_datetime_from`,`ip`.`entity_datetime_to` AS `entity_datetime_to`,`ip`.`confirmed_user_id` AS `confirmed_user_id`,`ip`.`approved_user_id` AS `approved_user_id`,14 AS `post_type_id`,3 AS `period_group_priority`,`ip`.`period_group_id` AS `period_group_id`,`ip`.`period_status_type_id` AS `period_status_type_id`,`ip`.`enabled` AS `enabled` from (((`{$prefix}cb2_timeframe_period_groups` `ip` join `{$prefix}cb2_period_groups` `pg` on((`ip`.`period_group_id` = `pg`.`period_group_id`))) join `{$prefix}posts` `loc` on((`ip`.`location_ID` = `loc`.`ID`))) join `{$prefix}posts` `itm` on((`ip`.`item_ID` = `itm`.`ID`))) union all select `ip`.`timeframe_user_period_group_id` AS `timeframe_ID`,`pg`.`name` AS `name`,`ip`.`location_ID` AS `location_ID`,`ip`.`item_ID` AS `item_ID`,`ip`.`user_ID` AS `cb2_user_ID`,`ip`.`entity_datetime_from` AS `entity_datetime_from`,`ip`.`entity_datetime_to` AS `entity_datetime_to`,`ip`.`confirmed_user_id` AS `confirmed_user_id`,`ip`.`approved_user_id` AS `approved_user_id`,15 AS `post_type_id`,4 AS `period_group_priority`,`ip`.`period_group_id` AS `period_group_id`,`ip`.`period_status_type_id` AS `period_status_type_id`,`ip`.`enabled` AS `enabled` from ((((`{$prefix}cb2_timeframe_user_period_groups` `ip` join `{$prefix}cb2_period_groups` `pg` on((`ip`.`period_group_id` = `pg`.`period_group_id`))) join `{$prefix}posts` `loc` on((`ip`.`location_ID` = `loc`.`ID`))) join `{$prefix}posts` `itm` on((`ip`.`item_ID` = `itm`.`ID`))) join `{$prefix}users` `usr` on((`ip`.`user_ID` = `usr`.`ID`)))) `p` join `{$prefix}cb2_period_status_types` `pst` on((`p`.`period_status_type_id` = `pst`.`period_status_type_id`))) join `{$prefix}cb2_post_types` `pt_e` on((`pt_e`.`post_type_id` = `p`.`post_type_id`))) join `{$prefix}cb2_post_types` `pt_pg` on((`pt_pg`.`post_type_id` = 2))) join `{$prefix}cb2_post_types` `pt_pst` on((`pt_pst`.`post_type_id` = 8))) join `{$prefix}cb2_post_types` `pt_p_first` on((`pt_p_first`.`post_type_id` = 1))) left join `{$prefix}cb2_periods` `p_first` on((`p_first`.`period_id` = (select `ps2`.`period_id` from `{$prefix}cb2_period_group_period` `ps2` where (`ps2`.`period_group_id` = `p`.`period_group_id`) order by `ps2`.`period_id` limit 1))))",
			'cb2_view_periodentmeta'     => "select (((`po`.`timeframe_id` * `pt`.`ID_multiplier`) * 25) + `pt`.`ID_base`) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'period_IDs' AS `meta_key`,cast(`po`.`period_IDs` as char charset latin1) AS `meta_value` from (`{$prefix}cb2_view_periodent_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = `po`.`post_type`))) union all select ((((`po`.`timeframe_id` * `pt`.`ID_multiplier`) * 25) + `pt`.`ID_base`) + 1) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'period_group_ID' AS `meta_key`,cast(`po`.`period_group_ID` as char charset latin1) AS `meta_value` from (`{$prefix}cb2_view_periodent_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = `po`.`post_type`))) union all select ((((`po`.`timeframe_id` * `pt`.`ID_multiplier`) * 25) + `pt`.`ID_base`) + 2) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'period_status_type_ID' AS `meta_key`,cast(`po`.`period_status_type_ID` as char charset latin1) AS `meta_value` from (`{$prefix}cb2_view_periodent_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = `po`.`post_type`))) union all select ((((`po`.`timeframe_id` * `pt`.`ID_multiplier`) * 25) + `pt`.`ID_base`) + 3) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'location_ID' AS `meta_key`,cast(`po`.`location_ID` as char charset latin1) AS `meta_value` from (`{$prefix}cb2_view_periodent_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = `po`.`post_type`))) union all select ((((`po`.`timeframe_id` * `pt`.`ID_multiplier`) * 25) + `pt`.`ID_base`) + 4) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'item_ID' AS `meta_key`,cast(`po`.`item_ID` as char charset latin1) AS `meta_value` from (`{$prefix}cb2_view_periodent_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = `po`.`post_type`))) union all select ((((`po`.`timeframe_id` * `pt`.`ID_multiplier`) * 25) + `pt`.`ID_base`) + 5) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'cb2_user_ID' AS `meta_key`,cast(`po`.`cb2_user_ID` as char charset latin1) AS `meta_value` from (`{$prefix}cb2_view_periodent_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = `po`.`post_type`))) union all select ((((`po`.`timeframe_id` * `pt`.`ID_multiplier`) * 25) + `pt`.`ID_base`) + 6) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'enabled' AS `meta_key`,cast(`po`.`enabled` as char charset latin1) AS `meta_value` from (`{$prefix}cb2_view_periodent_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = `po`.`post_type`))) union all select ((((`po`.`timeframe_id` * `pt`.`ID_multiplier`) * 25) + `pt`.`ID_base`) + 7) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'period_status_type_name' AS `meta_key`,cast(`po`.`period_status_type_name` as char charset latin1) AS `meta_value` from (`{$prefix}cb2_view_periodent_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = `po`.`post_type`))) union all select ((((`po`.`timeframe_id` * `pt`.`ID_multiplier`) * 25) + `pt`.`ID_base`) + 8) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'datetime_part_period_start' AS `meta_key`,cast(`po`.`datetime_part_period_start` as char charset latin1) AS `meta_value` from (`{$prefix}cb2_view_periodent_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = `po`.`post_type`))) union all select ((((`po`.`timeframe_id` * `pt`.`ID_multiplier`) * 25) + `pt`.`ID_base`) + 9) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'datetime_part_period_end' AS `meta_key`,cast(`po`.`datetime_part_period_end` as char charset latin1) AS `meta_value` from (`{$prefix}cb2_view_periodent_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = `po`.`post_type`))) union all select ((((`po`.`timeframe_id` * `pt`.`ID_multiplier`) * 25) + `pt`.`ID_base`) + 10) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'recurrence_type' AS `meta_key`,cast(`po`.`recurrence_type` as char charset latin1) AS `meta_value` from (`{$prefix}cb2_view_periodent_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = `po`.`post_type`))) union all select ((((`po`.`timeframe_id` * `pt`.`ID_multiplier`) * 25) + `pt`.`ID_base`) + 11) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'recurrence_frequency' AS `meta_key`,cast(`po`.`recurrence_frequency` as char charset latin1) AS `meta_value` from (`{$prefix}cb2_view_periodent_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = `po`.`post_type`))) union all select ((((`po`.`timeframe_id` * `pt`.`ID_multiplier`) * 25) + `pt`.`ID_base`) + 12) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'datetime_from' AS `meta_key`,cast(`po`.`datetime_from` as char charset latin1) AS `meta_value` from (`{$prefix}cb2_view_periodent_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = `po`.`post_type`))) union all select ((((`po`.`timeframe_id` * `pt`.`ID_multiplier`) * 25) + `pt`.`ID_base`) + 13) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'datetime_to' AS `meta_key`,cast(`po`.`datetime_to` as char charset latin1) AS `meta_value` from (`{$prefix}cb2_view_periodent_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = `po`.`post_type`))) union all select ((((`po`.`timeframe_id` * `pt`.`ID_multiplier`) * 25) + `pt`.`ID_base`) + 14) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'recurrence_sequence' AS `meta_key`,cast(cast(`po`.`recurrence_sequence` as unsigned) as char charset latin1) AS `meta_value` from (`{$prefix}cb2_view_periodent_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = `po`.`post_type`))) union all select ((((`po`.`timeframe_id` * `pt`.`ID_multiplier`) * 25) + `pt`.`ID_base`) + 15) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'period_ID' AS `meta_key`,cast(`po`.`period_ID` as char charset latin1) AS `meta_value` from (`{$prefix}cb2_view_periodent_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = `po`.`post_type`))) union all select ((((`po`.`timeframe_id` * `pt`.`ID_multiplier`) * 25) + `pt`.`ID_base`) + 16) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'period_status_type_id' AS `meta_key`,cast(`po`.`period_status_type_native_id` as char charset latin1) AS `meta_value` from (`{$prefix}cb2_view_periodent_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = `po`.`post_type`))) union all select ((((`po`.`timeframe_id` * `pt`.`ID_multiplier`) * 25) + `pt`.`ID_base`) + 17) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'entity_datetime_from' AS `meta_key`,cast(`po`.`entity_datetime_from` as char charset latin1) AS `meta_value` from (`{$prefix}cb2_view_periodent_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = `po`.`post_type`))) union all select ((((`po`.`timeframe_id` * `pt`.`ID_multiplier`) * 25) + `pt`.`ID_base`) + 18) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'entity_datetime_to' AS `meta_key`,cast(`po`.`entity_datetime_to` as char charset latin1) AS `meta_value` from (`{$prefix}cb2_view_periodent_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = `po`.`post_type`))) union all select ((((`po`.`timeframe_id` * `pt`.`ID_multiplier`) * 25) + `pt`.`ID_base`) + 19) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'confirmed_user_id' AS `meta_key`,cast(`po`.`confirmed_user_id` as char charset latin1) AS `meta_value` from (`{$prefix}cb2_view_periodent_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = `po`.`post_type`))) union all select ((((`po`.`timeframe_id` * `pt`.`ID_multiplier`) * 25) + `pt`.`ID_base`) + 20) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'approved_user_id' AS `meta_key`,cast(`po`.`approved_user_id` as char charset latin1) AS `meta_value` from (`{$prefix}cb2_view_periodent_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = `po`.`post_type`)))",
			// TODO: link cb2_view_timeframe_options in to the cb2_view_periodentmeta
			'cb2_view_timeframe_options' => "select distinct `c2to`.`timeframe_id` AS `timeframe_id`,(select `{$prefix}cb2_timeframe_options`.`option_value` from `{$prefix}cb2_timeframe_options` where ((`{$prefix}cb2_timeframe_options`.`timeframe_id` = `c2to`.`timeframe_id`) and (`{$prefix}cb2_timeframe_options`.`option_name` = 'max-slots')) order by `{$prefix}cb2_timeframe_options`.`option_id` desc limit 1) AS `max-slots`,(select `{$prefix}cb2_timeframe_options`.`option_value` from `{$prefix}cb2_timeframe_options` where ((`{$prefix}cb2_timeframe_options`.`timeframe_id` = `c2to`.`timeframe_id`) and (`{$prefix}cb2_timeframe_options`.`option_name` = 'closed-days-booking')) order by `{$prefix}cb2_timeframe_options`.`option_id` desc limit 1) AS `closed-days-booking`,(select `{$prefix}cb2_timeframe_options`.`option_value` from `{$prefix}cb2_timeframe_options` where ((`{$prefix}cb2_timeframe_options`.`timeframe_id` = `c2to`.`timeframe_id`) and (`{$prefix}cb2_timeframe_options`.`option_name` = 'consequtive-slots')) order by `{$prefix}cb2_timeframe_options`.`option_id` desc limit 1) AS `consequtive-slots`,(select `{$prefix}cb2_timeframe_options`.`option_value` from `{$prefix}cb2_timeframe_options` where ((`{$prefix}cb2_timeframe_options`.`timeframe_id` = `c2to`.`timeframe_id`) and (`{$prefix}cb2_timeframe_options`.`option_name` = 'use-codes')) order by `{$prefix}cb2_timeframe_options`.`option_id` desc limit 1) AS `use-codes`,(select `{$prefix}cb2_timeframe_options`.`option_value` from `{$prefix}cb2_timeframe_options` where ((`{$prefix}cb2_timeframe_options`.`timeframe_id` = `c2to`.`timeframe_id`) and (`{$prefix}cb2_timeframe_options`.`option_name` = 'limit')) order by `{$prefix}cb2_timeframe_options`.`option_id` desc limit 1) AS `limit`,(select `{$prefix}cb2_timeframe_options`.`option_value` from `{$prefix}cb2_timeframe_options` where ((`{$prefix}cb2_timeframe_options`.`timeframe_id` = `c2to`.`timeframe_id`) and (`{$prefix}cb2_timeframe_options`.`option_name` = 'holiday_provider')) order by `{$prefix}cb2_timeframe_options`.`option_id` desc limit 1) AS `holiday-provider` from `{$prefix}cb2_timeframe_options` `c2to`",
		);
	}

	static function metaboxes() {
		$metaboxes         = array();

		// Default period times
		$now               = new CB2_DateTime();
		$day_start_format  = CB2_Query::$date_format . ' 00:00:00';
		$morning_format    = CB2_Query::$date_format . ' 08:00:00';
		$evening_format    = CB2_Query::$date_format . ' 18:00:00';

		array_push( $metaboxes,
			array(
				'title'      => __( 'Calendar view', 'commons-booking-2' ),
				'context'    => 'normal',
				'show_names' => FALSE,
				'show_on_cb' => array( 'CB2', 'is_published' ),
				'classes_cb' => array( 'CB2_PeriodEntity', 'metabox_calendar_classes_cb' ),
				'fields' => array(
					array(
						'name'    => __( 'Timeframe', 'commons-booking-2' ),
						'id'      => 'calendar',
						'type'    => 'calendar',
						'options_cb' => array( 'CB2_PeriodEntity', 'metabox_calendar_options_cb' ),
					),
				),
			)
		);

		// Standard
		array_push( $metaboxes,
			array(
				// TODO: link this Enabled in to the Publish meta-box status instead
				'title' => __( 'Enabled', 'commons-booking-2' ),
				'context' => 'side',
				'show_names' => FALSE,
				'fields' => array(
					array(
						'id'      => 'enabled_explanation',
						'type'    => 'paragraph',
						'classes' => 'cb2-cmb2-compact',
						'html'    => 'If not enabled, the entity will be moved to the <span style="color:red;">Trash</span>.',
					),
					array(
						'name'    => __( 'Enabled', 'commons-booking-2' ),
						'id'      => 'enabled',
						'type'    => 'checkbox',
						'classes' => 'cb2-cmb2-compact',
						'default' => 1,
					),
				),
			)
		);
		array_push( $metaboxes,
			array(
				'title' => __( 'Period validity override (optional)', 'commons-booking-2' ),
				'context' => 'side',
				'show_names' => TRUE,
				'fields' => array(
					array(
						'name' => __( 'From Date', 'commons-booking-2' ),
						'id' => 'entity_datetime_from',
						'type' => 'text_datetime_timestamp',
						'date_format' => CB2_Database::$database_date_format,
						'default' => ( isset( $_GET['entity_datetime_from'] ) ? $_GET['entity_datetime_from'] : NULL ),
					),
					array(
						'name' => __( 'To Date', 'commons-booking-2' ),
						'id' => 'entity_datetime_to',
						'type' => 'text_datetime_timestamp',
						'date_format' => CB2_Database::$database_date_format,
						'default' => ( isset( $_GET['entity_datetime_to'] ) ? $_GET['entity_datetime_to'] : NULL ),
					),
				),
			)
		);

		// Security
		array_push( $metaboxes,
			array(
				'title' => __( 'Security', 'commons-booking-2' ),
				'context' => 'security',
				'fields' => array(
					array(
						'name'    => __( 'Confirmed', 'commons-booking-2' ),
						'id'      => 'confirmed_user_id',
						'type'    => 'checkbox',
						'classes' => 'cb2-cmb2-compact',
					),
					array(
						'name'    => __( 'Authorised', 'commons-booking-2' ),
						'id'      => 'approved_user_id',
						'type'    => 'checkbox',
						'classes' => 'cb2-cmb2-compact',
					),
				),
			)
		);

		// Advanced
		// array_push( $metaboxes, CB2_Period::selector_metabox( TRUE, 'advanced' ) ); // Multiple
		array_push( $metaboxes, CB2_PeriodGroup::selector_metabox( FALSE, 'advanced' ) );
		array_push( $metaboxes, CB2_PeriodStatusType::selector_metabox( 'advanced' ) );

		return $metaboxes;
	}

	static function metabox_calendar_classes_cb( $field ) {
		global $post;

		$classes = array();

		if ( $post ) {
			CB2_Query::ensure_correct_class( $post );
			if ( method_exists( $post, 'metabox_calendar_classes_object_cb' ) ) {
				$classes = array_merge( $classes, $post->metabox_calendar_classes_object_cb( $field ) );
			}
		}

		return $classes;
	}

	static function metabox_calendar_options_cb( $field ) {
		global $post;

		$options = array();

		if ( $post ) {
			CB2_Query::ensure_correct_class( $post );
			if ( method_exists( $post, 'metabox_calendar_options_object_cb' ) ) {
				$options = array_merge( $options, $post->metabox_calendar_options_object_cb( $field ) );
			}
		}

		return $options;
	}

	protected function metabox_calendar_classes_object_cb( $field ) {
		$classes = $this->period_status_type->metabox_calendar_classes_object_cb( $field, $this );
		return $classes;
	}

	protected function metabox_calendar_options_object_cb( $field ) {
		$options = $this->period_status_type->metabox_calendar_options_object_cb( $field, $this );
		return $options;
	}

	public function tabs( $edit_form_advanced = FALSE ) {
		$tabs = NULL;
		if ( $this->post_status == CB2_Post::$PUBLISH ) {
			$tabs = array(
				'postdivrich'         => 'Content',
				'postbox-container-2' => 'Management',
				'postbox-container-1' => 'Options',
				'cb2-tab-security'            => 'Security',
			);
			if ( WP_DEBUG ) $tabs[ 'debug' ] = 'Debug';
		}
		return $tabs;
	}

  protected static function factory_from_to_perioditems(
		CB2_PeriodItem $perioditem_from,
		CB2_PeriodItem $perioditem_to,
		$new_periodentity_Class,
		$new_period_status_type_Class,
		$name = NULL,

		$copy_period_group     = TRUE,
		CB2_Location $location = NULL,
		CB2_Item     $item     = NULL,
		CB2_User     $user     = NULL
  ) {
		$period_entity = $perioditem_from->period_entity;

		// PeriodGroup, Period and refrences
		$period_group  = NULL;
		if ( $copy_period_group ) {
			// We do not want to clone the period_group
			// only the period item *instance*
			$period = new CB2_Period(
				CB2_CREATE_NEW,
				( $name ? $name : $perioditem_from->post_title ),
				$perioditem_from->datetime_period_item_start, // datetime_part_period_start
				$perioditem_to->datetime_period_item_end,     // datetime_part_period_end
				CB2_DateTime::yesterday()                     // datetime_from
			);
			$period_group = new CB2_PeriodGroup(
				CB2_CREATE_NEW,
				( $name ? $name : $perioditem_from->post_title ),
				array( $period ) // periods
			);
		} else {
			// Linking means that:
			// changing the original availability period will change the booking as well!
			$period_group = $period_entity->period_group;
		}

		// new PeriodEntity
		$new_period_entity = $new_periodentity_Class::factory(
			CB2_CREATE_NEW,
			$name,
			$period_group,
			new $new_period_status_type_Class(),
			TRUE,
			NULL, NULL, // entity_datetime_*

			( $location ? $location : $period_entity->location ),
			( $item     ? $item     : $period_entity->item ),
			( $user     ? $user     : $period_entity->user )
		);

		return $new_period_entity;
  }

  protected static function factory_from_perioditem(
		CB2_PeriodItem $perioditem,
		$new_periodentity_Class,
		$new_period_status_type_Class,
		$name = NULL,

		$copy_period_group     = TRUE,
		CB2_Location $location = NULL,
		CB2_Item     $item     = NULL,
		CB2_User     $user     = NULL
	) {
		$period_entity = $perioditem->period_entity;

		// PeriodGroup, Period and refrences
		$period_group  = NULL;
		if ( $copy_period_group ) {
			// We do not want to clone the period_group
			// only the period item *instance*
			$datetime_now = new CB2_DateTime();
			$period = new CB2_Period(
				CB2_CREATE_NEW,
				( $name ? $name : $perioditem->post_title ),
				$perioditem->datetime_period_item_start, // datetime_part_period_start
				$perioditem->datetime_period_item_end,   // datetime_part_period_end
				$datetime_now                            // datetime_from
			);
			$period_group = new CB2_PeriodGroup(
				CB2_CREATE_NEW,
				( $name ? $name : $perioditem->post_title ),
				array( $period ) // periods
			);
		} else {
			// Linking means that:
			// changing the original availability period will change the booking as well!
			$period_group = $period_entity->period_group;
		}

		// new PeriodEntity
		$new_period_entity = $new_periodentity_Class::factory(
			CB2_CREATE_NEW,
			$name,
			$period_group,
			new $new_period_status_type_Class(),
			TRUE,
			NULL, NULL, // entity_datetime_*

			( $location ? $location : $period_entity->location ),
			( $item     ? $item     : $period_entity->item ),
			( $user     ? $user     : $period_entity->user )
		);

		return $new_period_entity;
  }

  static function &factory(
		$ID,
    $name,
		$period_group,       // Can be NULL, indicating <create new>
		$period_status_type,
		$enabled,
    $entity_datetime_from = NULL, // CB2_DateTime
    $entity_datetime_to   = NULL, // CB2_DateTime

		//here to prevent static inheritance warning
		$location = NULL,
		$item     = NULL,
		$user     = NULL
	) {
    // Design Patterns: Factory Singleton with Multiton
		if ( $ID && $ID != CB2_CREATE_NEW && isset( self::$all[$ID] ) ) {
			$object = self::$all[$ID];
    } else {
			$reflection = new ReflectionClass( __class__ );
			$object     = $reflection->newInstanceArgs( func_get_args() );
    }

    return $object;
  }

  static function factory_from_properties( Array &$properties, &$instance_container = NULL, $force_properties = FALSE ) {
		$object = self::factory_subclass(
			$properties['ID'], // Required. Set to CB2_CREATE_NEW if creating
			( isset( $properties['post_title'] )
				? $properties['post_title']
				: ( isset( $properties['name'] ) ? $properties['name']  : '' )
			),
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_group_ID',       $instance_container ),
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_status_type_ID', $instance_container ),
			( isset( $properties['enabled'] ) && $properties['enabled'] ), // Can come from a checkbox
			( isset( $properties['entity_datetime_from'] ) ? $properties['entity_datetime_from'] : NULL ),
			( isset( $properties['entity_datetime_to'] )   ? $properties['entity_datetime_to'] : NULL ),

			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'location_ID', $instance_container, FALSE, CB2_NOT_REQUIRED ),
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'item_ID',     $instance_container, FALSE, CB2_NOT_REQUIRED ),
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'cb2_user_ID', $instance_container, FALSE, CB2_NOT_REQUIRED )
		);

		self::copy_all_wp_post_properties( $properties, $object );

		return $object;
	}

  static function factory_subclass(
		$ID,
		$name,
		$period_group,       // CB2_PeriodGroup
		$period_status_type, // CB2_PeriodStatusType
		$enabled,
    $entity_datetime_from = NULL, // CB2_DateTime
    $entity_datetime_to   = NULL, // CB2_DateTime

    $location = NULL,   // CB2_Location
    $item     = NULL,   // CB2_Item
    $user     = NULL    // CB2_User
  ) {
		// provides appropriate sub-class based on final object parameters
		$object = NULL;
		if      ( $user )     $object = CB2_PeriodEntity_Timeframe_User::factory(
				$ID,
				$name,
				$period_group,
				$period_status_type,
				$enabled,
				$entity_datetime_from,
				$entity_datetime_to,
				$location,
				$item,
				$user
			);
		else if ( $item )     $object = CB2_PeriodEntity_Timeframe::factory(
				$ID,
				$name,
				$period_group,
				$period_status_type,
				$enabled,
				$entity_datetime_from,
				$entity_datetime_to,
				$location,
				$item,
				$user
			);
		else if ( $location ) $object = CB2_PeriodEntity_Location::factory(
				$ID,
				$name,
				$period_group,
				$period_status_type,
				$enabled,
				$entity_datetime_from,
				$entity_datetime_to,
				$location,
				$item,
				$user
			);
		else                  $object = CB2_PeriodEntity_Global::factory(
				$ID,
				$name,
				$period_group,
				$period_status_type,
				$enabled,
				$entity_datetime_from,
				$entity_datetime_to,
				$location,
				$item,
				$user
			);

		return $object;
  }

  public function __construct(
		$ID,
    $name,
		$period_group,                // CB2_PeriodGroup {[CB2_Period, ...]}
    $period_status_type,          // CB2_PeriodStatusType
    $enabled,
    $entity_datetime_from = NULL, // CB2_DateTime
    $entity_datetime_to   = NULL  // CB2_DateTime
  ) {
		parent::__construct();
		CB2_Query::assign_all_parameters( $this, func_get_args(), __class__ );

		$this->period_count = count( $this->period_group->periods );
  }

  function do_action_confirm() {
		$this->confirm( get_current_user_id() );
  }

  function confirm( Int $user_id = 1 ) {
		global $wpdb;
		$Class = get_class( $this );
		$wpdb->update( $wpdb->prefix . CB2_Database::database_table( $Class ),
			array( 'confirmed_user_id' => $user_id ),
			array( CB2_Database::id_field( $Class ) => $this->id() )
		);
		$this->confirmed_user_id = $user_id;
		return $this;
  }

  function approve( Int $user_id = 1 ) {
		global $wpdb;
		$Class = get_class( $this );
		$wpdb->update( $wpdb->prefix . CB2_Database::database_table( $Class ),
			array( 'approved_user_id' => $user_id ),
			array( CB2_Database::id_field( $Class ) => $this->id() )
		);
		$this->approved_user_id = $user_id;
		return $this;
  }

  function save( $update = FALSE, $fire_wordpress_events = TRUE, $depth = 0, $debug = NULL ) {
		$ret = parent::save( $update, $fire_wordpress_events, $depth, $debug );

		// Additional event
		if ( $fire_wordpress_events ) {
			$Class                   = get_class( $this );
			$post_type               = $this->post_type();
			$period_status_type_name = $this->period_status_type->name;
			$action                  = "save_post_{$post_type}_{$period_status_type_name}";
			if ( CB2_DEBUG_SAVE )
				print( "<div class='cb2-WP_DEBUG-small'>{$Class}[$this->ID] fires event [$action]</div>" );
			do_action( $action, $this->ID, $this );
		}

		return $ret;
  }

  function templates_considered( $context = 'list', $type = NULL, &$templates = NULL ) {
		$templates = parent::templates_considered( $context, $type, $templates );

		$period_status_type_name = preg_replace( '/[^a-zA-Z0-9]/', '-', $this->period_status_type->name );
		$period_status_type = ( $type ? "$type-$period_status_type_name" : $period_status_type_name );
		$templates = parent::templates_considered( $context, $period_status_type, $templates );

		return $templates;
  }

	protected function custom_events( $update ) {
		$period_status_type_name = strtolower( $this->period_status_type->name );
		// cb2_save_post_booked
		do_action( "cb2_save_post_$period_status_type_name", $this, $update );
		if ( $update ) do_action( "cb2_update_post_$period_status_type_name", $this );
		else           do_action( "cb2_insert_post_$period_status_type_name", $this );
	}

  static function do_action_generic( CB2_User $user, $args ) {
		$do_action_2 = $args['do_action'];                // <Class>::<action>
		$details     = explode( '::', $do_action_2 );
		$do_action   = $details[1];

		if ( ! $user->can( 'edit_posts' ) )
			throw new Exception( "User does not have sufficient permissions to $do_action" );

		// Compile all the object arrays sent through
		$perioditems = array();
		foreach ( $args as $name => $perioditem_array )
			if ( substr( $name, 0, 11 ) == 'perioditem-'
				&& is_array( $perioditem_array )
				&& count( $perioditem_array )
				&& $perioditem_array[0] instanceof CB2_PeriodItem
			)
				$perioditems = array_merge( $perioditems, $perioditem_array );

		foreach ( $perioditems as $perioditem )
			$perioditem->$do_action();
  }

  static function do_action_block( CB2_User $user, $args ) {
		return self::do_action_generic( $user, $args );
  }

  static function do_action_unblock( CB2_User $user, $args ) {
		return self::do_action_generic( $user, $args );
  }

  function row_actions( &$actions, $post ) {
		if ( isset( $actions['inline hide-if-no-js'] ) )
			$actions['inline hide-if-no-js'] = str_replace( ' class="', ' class="cb2-todo ', $actions['inline hide-if-no-js'] );
	}

	function manage_columns( $columns ) {
		$columns['confirmed'] = 'Confirmed';
		$columns['approved']  = 'Approved';
		if ( ! $this->period_group )
			throw new Exception( '[' . get_class( $this ) . "] [$this->ID] has no period_group" );
		return $this->period_group->manage_columns( $columns );
	}

	function custom_columns( $column ) {
		if ( ! $this->period_group )
			throw new Exception( '[' . get_class( $this ) . "] [$this->ID] has no period_group" );
		switch ( $column ) {
			case 'confirmed':
				if ( property_exists( $this, 'confirmed_user_id' ) && $this->confirmed_user_id )
					print( "<input class='cb2-tick-only' type='checkbox' checked='1' />" );
				break;
			case 'approved':
				if ( property_exists( $this, 'approved_user_id' ) && $this->approved_user_id )
					print( "<input class='cb2-tick-only' type='checkbox' checked='1' />" );
				break;
			default:
				$this->period_group->custom_columns( $column );
		}
	}

  function classes() {
    $classes  = '';
    $classes .= $this->period_status_type->classes();
    $classes .= ' cb2-' . $this->post_type();
    if ( property_exists( $this, 'location' ) && $this->location ) $classes .= ' cb2-has-location';
    if ( property_exists( $this, 'item' )     && $this->item )     $classes .= ' cb2-has-item';
    if ( property_exists( $this, 'user' )     && $this->user )     $classes .= ' cb2-has-user';
    if ( property_exists( $this, 'confirmed_user_id' ) && $this->confirmed_user_id ) $classes .= ' cb2-confirmed';
    if ( property_exists( $this, 'approved_user_id' )  && $this->confirmed_user_id ) $classes .= ' cb2-approved';
    return $classes;
  }

	function summary() {
		$classes = $this->classes();
		$html  = "<div class='$classes'>";
		$html .= '<b>' . $this->post_title . '</b>';
		$html .= $this->summary_actions();
		$html .= '<br/>';
		$html .= $this->period_group->summary_periods();
		$html .= "</div>";
		return $html;
	}

	function get_the_title( $HTML = FALSE, $parent = NULL ) {
		$title = '';
		foreach ( $this->posts as $entity ) {
			$post_type = $entity->post_type;
			if ( $HTML ) $title .= "<span class='cb2-$post_type-name'>";
			$title .= $entity->post_title;
			if ( $HTML ) $title .= "</span>";
			$title .= ' ';
		}
		$period_status_type_name = $this->period_status_type->get_the_title( $HTML, $parent );
		if ( $HTML ) $title .= "<span class='cb2-periodstatustype-name'>";
		$title .= $period_status_type_name;
		if ( $HTML ) $title .= "</span>";

		return $title;
	}

	function summary_actions() {
		$post_type = $this->post_type();
		$page      = 'cb2-post-edit';
		$action    = 'edit';
		$edit_link = "?page=$page&post=$this->ID&post_type=$post_type&action=$action";
		return " <a href='$edit_link'>edit</a>";
	}

	function jsonSerialize() {
    return $this;
  }
}

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB2_PeriodEntity_Global extends CB2_PeriodEntity {
  public static $database_table = 'cb2_global_period_groups';
  static $static_post_type      = 'periodent-global';
  static $Class_PeriodItem      = 'CB2_PeriodItem_Global'; // Associated CB2_PeriodItem

	static function metaboxes() {
		return parent::metaboxes();
	}

  static function database_table_name() { return self::$database_table; }

  static function database_table_schemas( $prefix ) {
		$database_table_name  = self::database_table_name();
		$post_type            = self::$Class_PeriodItem::$static_post_type;
		$id_field             = CB2_Database::id_field( __class__ );

		return array( CB2_PeriodEntity::database_table_schema_root(
			$database_table_name,
			$id_field
		) );
	}

  function post_type() {return self::$static_post_type;}

	static function &factory_from_properties( Array &$properties, &$instance_container = NULL, $force_properties = FALSE ) {
		$object = self::factory(
			( isset( $properties['global_period_group_ID'] ) ? $properties['global_period_group_ID'] : $properties['ID'] ),
			( isset( $properties['post_title'] ) ? $properties['post_title']           : $properties['name'] ),
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_group_ID',       $instance_container ),
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_status_type_ID', $instance_container ),
			( isset( $properties['enabled'] ) && $properties['enabled'] ), // Can come from a checkbox
			( isset( $properties['entity_datetime_from'] ) ? $properties['entity_datetime_from'] : NULL ),
			( isset( $properties['entity_datetime_to'] )   ? $properties['entity_datetime_to'] : NULL )
		);

		self::copy_all_wp_post_properties( $properties, $object );

		return $object;
	}

  static function &factory(
		$ID,
    $name,
		$period_group,
		$period_status_type,
		$enabled,
		$entity_datetime_from = NULL,
		$entity_datetime_to = NULL,

		//here to prevent static inheritance warning
		$location = NULL,
		$item     = NULL,
		$user     = NULL
	) {
    // Design Patterns: Factory Singleton with Multiton
		if ( $ID && $ID != CB2_CREATE_NEW && isset( self::$all[$ID] ) ) {
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
		$period_group,              // CB2_PeriodGroup {[CB2_Period, ...]}
    $period_status_type,        // CB2_PeriodStatusType
    $enabled,
		$entity_datetime_from,
		$entity_datetime_to
  ) {
		parent::__construct(
			$ID,
			$name,
			$period_group,
			$period_status_type,
			$enabled,
			$entity_datetime_from,
			$entity_datetime_to
    );
  }
}

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB2_PeriodEntity_Location extends CB2_PeriodEntity {
  public static $database_table = 'cb2_location_period_groups';
  static $static_post_type      = 'periodent-location';
  static $Class_PeriodItem      = 'CB2_PeriodItem_Location'; // Associated CB2_PeriodItem

	static function metaboxes() {
		$metaboxes         = parent::metaboxes();
		$Class             = get_class();
		array_unshift( $metaboxes, CB2_Location::selector_metabox( 'normal', array( 'cb2-object-summary-bar' ) ) );

		// Default period times
		$now               = new CB2_DateTime();
		$day_start_format  = CB2_Query::$date_format . ' 00:00:00';
		$morning_format    = CB2_Query::$date_format . ' 08:00:00';
		$evening_format    = CB2_Query::$date_format . ' 18:00:00';

		// TODO: make slot types configurable
		// TODO: build these slot time specs in to CB2_DateTime
		$slot_types = array(
			'Custom' => array(),
			'The day is the slot!' => array(
				array( 'day start', 'day end' )
			),
			'2 slots: Morning and Afternoon' => array(
				array( 'day start', 'lunch start' ),
				array( 'lunch end', 'day end' ),
			),
			//'Hourly slots',
		);
		foreach ( $slot_types as $name => $slot_type ) {
			$slot_types[json_encode( $slot_type )] = $name;
			unset($slot_types[$name]);
		}

		// ------------------------------------------ Calendar based metabox showing just one week
		$advanced_url  = CB2_Query::pass_through_query_string( NULL, array(), array(
			'CB2_PeriodEntity_Location_metabox_0_show',
			'metabox_wizard_ids',
			'title_show',
			'recurrence_type_show',
		) );
		$advanced_text = __( 'advanced' );
		$advanced      = "<div class='dashicons-before dashicons-admin-tools cb2-advanced'><a href='$advanced_url'>$advanced_text</a></div>";

		array_push( $metaboxes,
			array(
				'id'         => "{$Class}_metabox_openinghours_wizard",
				'title'      => __( 'Opening Hours Wizard', 'commons-booking-2' ) . " $advanced",
				//'show_on_cb' => array( 'CB2', 'is_not_published' ),
				'on_request' => TRUE, // Prevents the metabox being shown unless explicitly asked
				'show_names' => TRUE,
				'fields'     => array(
					array(
						'name'    => '<span class="cb2-todo">' . __( 'Preset selector', 'commons-booking-2' ) . '</span>',
						'id'      => 'period_openinghours_preset_selector',
						'type'    => 'radio_inline',
						'classes' => 'cb2-cmb2-compact',
						'default' => '[]',
						'options' => $slot_types,
					),
					array(
						'id'      => 'period_group_ID',
						'default' => CB2_CREATE_NEW,
						'type'    => 'hidden',
					),
					array(
						'id'      => 'location_ID',
						'default' => ( isset( $_GET['location_ID'] ) ? $_GET['location_ID'] : NULL ),
						'type'    => ( isset( $_GET['location_ID'] ) ? 'hidden' : 'text' ),
					),
					array(
						'id'      => 'enabled',
						'default' => TRUE,
						'type'    => 'hidden',
					),
					array(
						'id'      => 'period_status_type_ID',
						'default' => CB2_PeriodStatusType_Open::bigID(),
						'type'    => 'hidden',
					),
					array(
						'name'    => '',
						'id'      => 'period_IDs',
						'type'    => 'calendar',
						'sanitization_cb' => array( $Class, 'period_openinghours_sanitize' ),
						'classes' => array( 'cb2-calendar-grey', 'cb2-cmb2-compact' ),
						'options' => array(
							'actions'  => array(
								'make-available' => array(
									'link_text'   => __( 'Open today' ),
									'post_type'   => CB2_PeriodEntity_Location::$static_post_type,
									'period_status_type_ID' => CB2_PeriodStatusType_Open::bigID(),
									'day_post_ID' => '%ID%',
								),
							),
							'style'  => 'bare', // Day TDs only
							'query'  => array(
								'post_status' => 'any',
								'date_query' => array(
									'after'   => CB2_DateTime::next_week_start()->format( CB2_Query::$date_format ),
									'before'  => CB2_DateTime::next_week_end()->format(   CB2_Query::$date_format ),
									'compare' => CB2_Week::$static_post_type,
								),
								'meta_query' => array(
									'location_ID_clause' => array(
										'key'     => 'location_ID',
										'value'   => '%location->ID%',
										'compare' => 'IN',
									),
									'period_status_type_ID_clause' => array(
										'key'     => 'period_status_type_ID',
										'value'   => CB2_PeriodStatusType_Open::$id,
									),
								),
							),
						),
					),
				),
			)
		);

		return $metaboxes;
	}

	static function period_openinghours_sanitize( $value, $field_args, $field ) {
		// Rationalise into time group(s)
		// $value = array( 'Mon:08:00-20:00', ... )
		$periods       = array();
		$now           = CB2_DateTime::today();
		$datetime_from = $now->format( CB2_Query::$date_format );

		if ( CB2_DEBUG_SAVE ) {
			krumo($value);
			$name = $field_args['id'];
			print( "<div class='cb2-WP_DEBUG-small'>CMB2::sanitize [$name]</div>" );
		}

		// Group similar time periods together so they can be declared in one period
		$groups = array();
		foreach ( $value as $interval ) {
			if ( $interval ) {
				preg_match( '/^([A-Z][a-z][a-z]):([0-9][0-9]:[0-9][0-9])-([0-9][0-9]:[0-9][0-9])$/', $interval, $matches );
				if ( count( $matches ) != 4 )
					throw new Exception( 'Opening Hours Time Interval specification invalid' );
				$day         = $matches[1];
				$start_time  = $matches[2];
				$end_time    = $matches[3];
				$time_period = "$start_time-$end_time";
				if ( isset( $groups[$time_period] ) ) array_push( $groups[$time_period], $day );
				else $groups[$time_period] = array( $day );
			}
		}

		// Specifiy the periods
		foreach ( $groups as $time_period => $days ) {
			$name = '';
			$recurrence_sequence = array();
			foreach ( $days as $day ) {
				if ( $name ) $name .= ',';
				$name .= __( $day );

				$dayofweek = CB2_Day::dayofweek_adjusted( new DateTime( $day ) );
				array_push( $recurrence_sequence, pow( 2, $dayofweek ) );
			}
			$name = __( 'Opening hours' ) . ": $name";

			$period_start = substr( $time_period, 0, 5 );
			$period_end   = substr( $time_period, 6, 10 );

			array_push( $periods, array(
				'ID'              => CB2_CREATE_NEW,
				'name'            => $name,
				'datetime_from'   => $datetime_from,
				'recurrence_type' => CB2_Period::$recurrence_type_daily,
				'recurrence_sequence' => $recurrence_sequence,
				'datetime_part_period_start' => $period_start,
				'datetime_part_period_end'   => $period_end,
			) );
		}

		return $periods;
	}

	static function database_table_name() { return self::$database_table; }

  static function database_table_schemas( $prefix ) {
		$database_table_name  = self::database_table_name();
		$post_type            = self::$Class_PeriodItem::$static_post_type; // Associated CB2_PeriodItem
		$id_field             = CB2_Database::id_field( __class__ );

		return array( CB2_PeriodEntity::database_table_schema_root(
			$database_table_name,
			$id_field,
			array(
				'location_ID' => array( CB2_BIGINT, (20), CB2_UNSIGNED, CB2_NOT_NULL ),
			),
			array(
				'location_ID' => array( 'posts', 'ID' ),
			)
		) );
	}

  function post_type() {return self::$static_post_type;}

	static function &factory_from_properties( Array &$properties, &$instance_container = NULL, $force_properties = FALSE ) {
		$object = self::factory(
			( isset( $properties['location_period_group_ID'] ) ? $properties['location_period_group_ID'] : $properties['ID'] ),
			( isset( $properties['post_title'] )
				? $properties['post_title']
				: ( isset( $properties['name'] ) ? $properties['name']  : '' )
			),
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_group_ID',       $instance_container ),
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_status_type_ID', $instance_container ),
			( isset( $properties['enabled'] ) && $properties['enabled'] ), // Can come from a checkbox
			( isset( $properties['entity_datetime_from'] ) ? $properties['entity_datetime_from'] : NULL ),
			( isset( $properties['entity_datetime_to'] )   ? $properties['entity_datetime_to'] : NULL ),

			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'location_ID',           $instance_container )
		);

		self::copy_all_wp_post_properties( $properties, $object );

		return $object;
	}

  static function &factory(
		$ID,
		$name,
		$period_group,
		$period_status_type,
		$enabled,
		$entity_datetime_from = NULL,
		$entity_datetime_to   = NULL,

		$location = NULL,
		$item     = NULL,
		$user     = NULL
  ) {
    // Design Patterns: Factory Singleton with Multiton
		if ( $ID && $ID != CB2_CREATE_NEW && isset( self::$all[$ID] ) ) {
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
		$period_group,              // CB2_PeriodGroup {[CB2_Period, ...]}
    $period_status_type,        // CB2_PeriodStatusType
		$enabled,
		$entity_datetime_from = NULL,
		$entity_datetime_to   = NULL,

    $location                   // CB2_Location
  ) {
		parent::__construct(
			$ID,
			$name,
			$period_group,
			$period_status_type,
			$enabled,
			$entity_datetime_from,
			$entity_datetime_to
    );

		$this->location = $location;
    array_push( $this->posts, $this->location );
  }
}


// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB2_PeriodEntity_Timeframe extends CB2_PeriodEntity {
  public static $database_table = 'cb2_timeframe_period_groups';
  static $static_post_type      = 'periodent-timeframe';
  static $Class_PeriodItem      = 'CB2_PeriodItem_Timeframe'; // Associated CB2_PeriodItem

  static function metaboxes() {
		$metaboxes = parent::metaboxes();
		array_unshift( $metaboxes, CB2_Item::selector_metabox(     'normal', array( 'cb2-object-summary-bar' ) ) );
		array_unshift( $metaboxes, CB2_Location::selector_metabox( 'normal', array( 'cb2-object-summary-bar' ) ) );
		return $metaboxes;
	}

  static function database_table_name() { return self::$database_table; }

  static function database_table_schemas( $prefix ) {
		$database_table_name  = self::database_table_name();
		$post_type            = self::$Class_PeriodItem::$static_post_type; // Associated CB2_PeriodItem
		$id_field             = CB2_Database::id_field( __class__ );

		return array( CB2_PeriodEntity::database_table_schema_root(
			$database_table_name,
			$id_field,
			array(
				'location_ID' => array( CB2_BIGINT, (20), CB2_UNSIGNED, CB2_NOT_NULL ),
				'item_ID'     => array( CB2_BIGINT, (20), CB2_UNSIGNED, CB2_NOT_NULL ),
			),
			array(
				'location_ID' => array( 'posts', 'ID' ),
				'item_ID'     => array( 'posts', 'ID' ),
			)
		) );
  }

  function post_type() {return self::$static_post_type;}

	static function &factory_from_properties( Array &$properties, &$instance_container = NULL, $force_properties = FALSE ) {
		$object = self::factory(
			( isset( $properties['timeframe_period_group_ID'] ) ? $properties['timeframe_period_group_ID'] : $properties['ID'] ),
			( isset( $properties['post_title'] )
				? $properties['post_title']
				: ( isset( $properties['name'] ) ? $properties['name']  : '' )
			),
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_group_ID',       $instance_container ),
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_status_type_ID', $instance_container ),
			( isset( $properties['enabled'] ) && $properties['enabled'] ), // Can come from a checkbox
			( isset( $properties['entity_datetime_from'] ) ? $properties['entity_datetime_from'] : NULL ),
			( isset( $properties['entity_datetime_to'] )   ? $properties['entity_datetime_to'] : NULL ),

			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'location_ID',           $instance_container ),
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'item_ID',               $instance_container )
		);

		self::copy_all_wp_post_properties( $properties, $object );

		return $object;
	}

  static function &factory(
		$ID,
		$name,
		$period_group,
		$period_status_type,
		$enabled,
		$entity_datetime_from = NULL,
		$entity_datetime_to   = NULL,

		$location = NULL,
		$item     = NULL,
		$user     = NULL
  ) {
    // Design Patterns: Factory Singleton with Multiton
		if ( $ID && $ID != CB2_CREATE_NEW && isset( self::$all[$ID] ) ) {
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
		$period_group,              // CB2_PeriodGroup {[CB2_Period, ...]}
    $period_status_type,        // CB2_PeriodStatusType
    $enabled,
		$entity_datetime_from,
		$entity_datetime_to,

    $location,                  // CB2_Location
    $item                       // CB2_Item
  ) {
		parent::__construct(
			$ID,
			$name,
			$period_group,
			$period_status_type,
			$enabled,
			$entity_datetime_from,
			$entity_datetime_to,
			$location,
			$item
    );

		$this->location = $location;
    array_push( $this->posts, $this->location );
		$this->item = $item;
    array_push( $this->posts, $this->item );
  }
}


// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB2_PeriodEntity_Location_User extends CB2_PeriodEntity {
  public static $database_table = 'cb2_location_user_period_groups';
  static $static_post_type      = 'periodent-staff';
  static $Class_PeriodItem      = 'CB2_PeriodItem_Location_User'; // Associated CB2_PeriodItem

  static function metaboxes() {
		$metaboxes = parent::metaboxes();
		array_unshift( $metaboxes, CB2_User::selector_metabox(     'normal', array( 'cb2-object-summary-bar' ) ) );
		array_unshift( $metaboxes, CB2_Location::selector_metabox( 'normal', array( 'cb2-object-summary-bar' ) ) );
		return $metaboxes;
	}

  static function database_table_name() { return self::$database_table; }

  static function database_table_schemas( $prefix ) {
		$database_table_name  = self::database_table_name();
		$post_type            = self::$Class_PeriodItem::$static_post_type; // Associated CB2_PeriodItem
		$id_field             = CB2_Database::id_field( __class__ );

		return array( CB2_PeriodEntity::database_table_schema_root(
			$database_table_name,
			$id_field,
			array(
				'location_ID' => array( CB2_BIGINT, (20), CB2_UNSIGNED, CB2_NOT_NULL ),
				'user_ID'     => array( CB2_BIGINT, (20), CB2_UNSIGNED, CB2_NOT_NULL ),
			),
			array(
				'location_ID' => array( 'posts', 'ID' ),
				'user_ID'     => array( 'users', 'ID' ),
			)
		) );
  }

  function post_type() {return self::$static_post_type;}

	static function &factory_from_properties( Array &$properties, &$instance_container = NULL, $force_properties = FALSE ) {
		$object = self::factory(
			( isset( $properties['location_user_period_group_ID'] ) ? $properties['location_user_period_group_ID'] : $properties['ID'] ),
			( isset( $properties['post_title'] )
				? $properties['post_title']
				: ( isset( $properties['name'] ) ? $properties['name']  : '' )
			),
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_group_ID',       $instance_container ),
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_status_type_ID', $instance_container ),
			( isset( $properties['enabled'] ) && $properties['enabled'] ), // Can come from a checkbox
			( isset( $properties['entity_datetime_from'] ) ? $properties['entity_datetime_from'] : NULL ),
			( isset( $properties['entity_datetime_to'] )   ? $properties['entity_datetime_to'] : NULL ),

			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'location_ID', $instance_container ),
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'cb2_user_ID', $instance_container )
		);

		self::copy_all_wp_post_properties( $properties, $object );

		return $object;
	}

  static function &factory(
		$ID,
		$name,
		$period_group,
		$period_status_type,
		$enabled,
		$entity_datetime_from = NULL,
		$entity_datetime_to   = NULL,

		$location = NULL,
		$item     = NULL,
		$user     = NULL
  ) {
    // Design Patterns: Factory Singleton with Multiton
		if ( $ID && $ID != CB2_CREATE_NEW && isset( self::$all[$ID] ) ) {
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
		$period_group,              // CB2_PeriodGroup {[CB2_Period, ...]}
    $period_status_type,        // CB2_PeriodStatusType
    $enabled,
		$entity_datetime_from,
		$entity_datetime_to,

    $location,                  // CB2_Location
    $item,                      // TODO: NULL...
    $user                       // CB2_User
  ) {
		parent::__construct(
			$ID,
			$name,
			$period_group,
			$period_status_type,
			$enabled,
			$entity_datetime_from,
			$entity_datetime_to,
			$location,
			NULL,
			$user
    );

		$this->location = $location;
    array_push( $this->posts, $this->location );
		$this->user = $user;
    array_push( $this->posts, $this->user );
  }
}


// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB2_PeriodEntity_Timeframe_User extends CB2_PeriodEntity {
  public static $database_table = 'cb2_timeframe_user_period_groups';
  static $static_post_type      = 'periodent-user';
  static $Class_PeriodItem      = 'CB2_PeriodItem_Timeframe_User'; // Associated CB2_PeriodItem

  static function metaboxes() {
		$metaboxes = parent::metaboxes();
		array_unshift( $metaboxes, CB2_User::selector_metabox( 'normal', array( 'cb2-object-summary-bar' ) ) );
		array_unshift( $metaboxes, CB2_Item::selector_metabox( 'normal', array( 'cb2-object-summary-bar' ) ) );
		array_unshift( $metaboxes, CB2_Location::selector_metabox( 'normal', array( 'cb2-object-summary-bar' ) ) );
		return $metaboxes;
	}

  static function database_table_name() { return self::$database_table; }

  static function database_table_schemas( $prefix ) {
		$database_table_name  = self::database_table_name();
		$post_type            = self::$Class_PeriodItem::$static_post_type; // Associated CB2_PeriodItem
		$id_field             = CB2_Database::id_field( __class__ );

		return array( CB2_PeriodEntity::database_table_schema_root(
			$database_table_name,
			$id_field,
			array(
				'location_ID' => array( CB2_BIGINT, (20), CB2_UNSIGNED, CB2_NOT_NULL ),
				'item_ID'     => array( CB2_BIGINT, (20), CB2_UNSIGNED, CB2_NOT_NULL ),
				'user_ID'     => array( CB2_BIGINT, (20), CB2_UNSIGNED, CB2_NOT_NULL ),
			),
			array(
				'location_ID' => array( 'posts', 'ID' ),
				'item_ID'     => array( 'posts', 'ID' ),
				'user_ID'     => array( 'users', 'ID' ),
			)
		) );
  }

  function post_type() {return self::$static_post_type;}

	static function &factory_from_properties( Array &$properties, &$instance_container = NULL, $force_properties = FALSE ) {
		$object = self::factory(
			( isset( $properties['timeframe_user_period_group_ID'] ) ? $properties['timeframe_user_period_group_ID'] : $properties['ID'] ),
			( isset( $properties['post_title'] )
				? $properties['post_title']
				: ( isset( $properties['name'] ) ? $properties['name']  : '' )
			),
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_group_ID',       $instance_container ),
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_status_type_ID', $instance_container ),
			( isset( $properties['enabled'] ) && $properties['enabled'] ), // Can come from a checkbox
			( isset( $properties['entity_datetime_from'] ) ? $properties['entity_datetime_from'] : NULL ),
			( isset( $properties['entity_datetime_to'] )   ? $properties['entity_datetime_to'] : NULL ),

			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'location_ID', $instance_container ),
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'item_ID',     $instance_container ),
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'cb2_user_ID', $instance_container )
		);

		self::copy_all_wp_post_properties( $properties, $object );

		return $object;
	}

  static function &factory(
		$ID,
		$name,
		$period_group,
		$period_status_type,
		$enabled,
		$entity_datetime_from = NULL,
		$entity_datetime_to   = NULL,

		$location = NULL,
		$item     = NULL,
		$user     = NULL
  ) {
    // Design Patterns: Factory Singleton with Multiton
		if ( $ID && $ID != CB2_CREATE_NEW && isset( self::$all[$ID] ) ) {
			$object = self::$all[$ID];
    } else {
			$reflection = new ReflectionClass( __class__ );
			$object     = $reflection->newInstanceArgs( func_get_args() );
    }

    return $object;
  }

  static function factory_booked_from_available_timeframe_item_from_to( CB2_PeriodItem_Timeframe $perioditem_available_from, CB2_PeriodItem_Timeframe $perioditem_available_to, CB2_User $user, $name = 'booking', $copy_period_group = TRUE ) {
		if ( ! $perioditem_available_from->period_entity->period_status_type instanceof CB2_PeriodStatusType_PickupReturn )
			throw new Exception( 'Tried to morph into perioditem-user from non-available status [' . $perioditem_available->period_status_type->name . ']' );
		if ( ! $perioditem_available_to->period_entity->period_status_type instanceof CB2_PeriodStatusType_PickupReturn )
			throw new Exception( 'Tried to morph into perioditem-user from non-available status [' . $perioditem_available->period_status_type->name . ']' );
		if ( ! $user )
			throw new Exception( 'Tried to morph into periodentity-user without user]' );

		return CB2_PeriodEntity::factory_from_to_perioditems(
			$perioditem_available_from,
			$perioditem_available_to,
			'CB2_PeriodEntity_Timeframe_User',
			'CB2_PeriodStatusType_Booked',
			$name,

			$copy_period_group,
			NULL, // Copy location from $perioditem_available_from
			NULL, // Copy location from $perioditem_available_from
			$user
		);
  }

  static function factory_booked_from_available_timeframe_item( CB2_PeriodItem_Timeframe $perioditem_available, CB2_User $user, $name = 'booking', $copy_period_group = TRUE ) {
		if ( ! $perioditem_available->period_entity->period_status_type instanceof CB2_PeriodStatusType_PickupReturn )
			throw new Exception( 'Tried to morph into perioditem-user from non-available status [' . $perioditem_available->period_status_type->name . ']' );
		if ( ! $user )
			throw new Exception( 'Tried to morph into periodentity-user without user]' );

		return CB2_PeriodEntity::factory_from_perioditem(
			$perioditem_available,
			'CB2_PeriodEntity_Timeframe_User',
			'CB2_PeriodStatusType_Booked',
			$name,

			$copy_period_group,
			NULL, // Copy location from $perioditem_available
			NULL, // Copy location from $perioditem_available
			$user
		);
  }

  public function __construct(
		$ID,
		$name,
		$period_group,              // CB2_PeriodGroup {[CB2_Period, ...]}
    $period_status_type,        // CB2_PeriodStatusType
    $enabled,
		$entity_datetime_from,
		$entity_datetime_to,

    $location,                  // CB2_Location
    $item,                      // CB2_Item
    $user                       // CB2_User
  ) {
		parent::__construct(
			$ID,
			$name,
			$period_group,
			$period_status_type,
			$enabled,
			$entity_datetime_from,
			$entity_datetime_to,
			$location,
			$item,
			$user
    );

		$this->location = $location;
    array_push( $this->posts, $this->location );
		$this->item = $item;
    array_push( $this->posts, $this->item );
		$this->user = $user;
    array_push( $this->posts, $this->user );
  }

  public function get_the_after_content() {
		$templates = $this->templates( 'single' );
		cb2_get_template_part( CB2_TEXTDOMAIN, $templates );
  }

	function summary_actions() {
		$actions = parent::summary_actions();
		$view_link = get_permalink( $this->ID );
		$actions  .= " | <a href='$view_link'>view</a>";
		return $actions;
	}
}
