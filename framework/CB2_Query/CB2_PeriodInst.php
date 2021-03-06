<?php
require_once( 'CB2_Period.php' );

abstract class CB2_PeriodInst extends CB2_PostNavigator implements JsonSerializable {
	public  static $database_table   = 'cb2_periodinst_settings';
	public  static $description      = 'CB2_PeriodInst uses <b>triggers</b> to update the primary postmeta table for performance.';
  public  static $all              = array();
  public  static $cached_postmeta  = TRUE;

  // Setting $postmeta_table to FALSE
  // will cause the intergration to JOIN to wp_postmeta
  // for these post_types
  // triggers would be necessary to update wp_postmeta
  // the entries in wp_postmeta would conflict with normal wp_posts of the same ID
  // not necessarily causing issues
  //
  // This system WAS implemented originally for performance
  // but has been replaced with a proactive trigger to cache wp_cb2_view_periodinsts
  // public  static $postmeta_table   = FALSE;

	public static $all_post_types = array(
		'periodinst-global',
		'periodinst-location',
		'periodinst-timeframe',
		'periodinst-user',
	);
  private static $null_recurrence_index = 0;
  public $priority_overlap_periods     = array();
  public $top_priority_overlap_period  = NULL;

	static function database_table_name() { return self::$database_table; }

  static function database_table_schemas( $prefix ) {
		// The repeating periods calculations
		// are one of the biggest performance hits
		// so they are cached in the database using triggers
		$periodinst_cache_table   = "{$prefix}cb2_cache_periodinsts";
		$refresh_cache        = "delete from $periodinst_cache_table; insert into $periodinst_cache_table(period_id, recurrence_index, datetime_period_inst_start, datetime_period_inst_end, blocked) select * from {$prefix}cb2_view_periodinsts;";

		return array(
			array(
				'name'    => self::$database_table,
				'columns' => array(
					// TYPE, (SIZE), CB2_UNSIGNED, NOT NULL, CB2_AUTO_INCREMENT, DEFAULT, COMMENT
					'period_id'        => array( CB2_INT,    (11), CB2_UNSIGNED, CB2_NOT_NULL ),
					'recurrence_index' => array( CB2_INT,    (11), CB2_UNSIGNED, CB2_NOT_NULL ),
					'blocked'          => array( CB2_BIT,    (1),  NULL,         CB2_NOT_NULL, NULL,  0 ),
					'author_ID'        => array( CB2_BIGINT, (20), CB2_UNSIGNED, CB2_NOT_NULL, FALSE, 1 ),
				),
				'primary key'  => array( 'period_id', 'recurrence_index' ),
				'foreign keys' => array(
					'period_id' => array( 'cb2_periods', 'period_id'),
					'author_ID' => array( 'users',       'ID' ),
				),
				'triggers'     => array(
					'AFTER UPDATE'  => array( $refresh_cache ),
					'AFTER DELETE'  => array( $refresh_cache ),
					'AFTER INSERT'  => array( $refresh_cache ),
				),
			),
			// Note that static $cached_postmeta = TRUE
			array(
				'name' => 'cb2_view_periodinstmeta_cache',
				'columns' => array(
					'timeframe_id'  => array( CB2_BIGINT,  (20), CB2_UNSIGNED, CB2_NOT_NULL ),
					'recurrence_index' => array( CB2_INT,  (11), CB2_UNSIGNED, CB2_NOT_NULL ),
					'meta_id'       => array( CB2_BIGINT,  (20), CB2_UNSIGNED, CB2_NOT_NULL ),
					'post_id'       => array( CB2_BIGINT,  (20), CB2_UNSIGNED, CB2_NOT_NULL ),
					'periodinst_id' => array( CB2_BIGINT,  (20), CB2_UNSIGNED, CB2_NOT_NULL ),
					'meta_key'      => array( CB2_VARCHAR, (255), NULL,        CB2_NOT_NULL ),
					'meta_value'    => array( CB2_LONGTEXT ),
				),
				'primary key' => array( 'post_id', 'meta_key' ),
			),
		);
  }

  static function refresh_cache() {
		global $wpdb;
		$wpdb->query( self::database_update_periodinst_postmeta_cache_SQL( $wpdb->prefix ) );
  }

  static function database_views( $prefix ) {
		// cb2_view_sequence_date is designed to return 4000 rows
		// which is equivalent to 10 years on daily repeat
		$periodinst_cache_table = "{$prefix}cb2_cache_periodinsts";

		return array(
			'cb2_view_sequence_num'        => "select 0 AS `num` union all select 1 AS `1` union all select 2 AS `2` union all select 3 AS `3` union all select 4 AS `4` union all select 5 AS `5` union all select 6 AS `6` union all select 7 AS `7` union all select 8 AS `8` union all select 9 AS `9`",
			'cb2_view_sequence_date'       => "select ((`t4`.`num` * 1000) + ((`t3`.`num` * 100) + ((`t2`.`num` * 10) + `t1`.`num`))) AS `num` from (((`{$prefix}cb2_view_sequence_num` `t1` join `{$prefix}cb2_view_sequence_num` `t2`) join `{$prefix}cb2_view_sequence_num` `t3`) join `{$prefix}cb2_view_sequence_num` `t4`)",
			// TODO: cb2_view_periodinsts needs to calculate W,M,Y recurrences from datetime_start, like D
			'cb2_view_periodinsts'         => "select `pr`.`period_id` AS `period_id`,0 AS `recurrence_index`,`pr`.`datetime_part_period_start` AS `datetime_period_inst_start`,`pr`.`datetime_part_period_end` AS `datetime_period_inst_end`,ifnull(`pis`.`blocked`,0) AS `blocked` from (`{$prefix}cb2_periods` `pr` left join `{$prefix}cb2_periodinst_settings` `pis` on(((`pis`.`period_id` = `pr`.`period_id`) and (`pis`.`recurrence_index` = 0)))) where (isnull(`pr`.`recurrence_type`) and (`pr`.`datetime_from` <= `pr`.`datetime_part_period_start`) and (isnull(`pr`.`datetime_to`) or (`pr`.`datetime_to` >= `pr`.`datetime_part_period_end`))) union all select `pr`.`period_id` AS `period_id`,`sq`.`num` AS `recurrence_index`,(`pr`.`datetime_part_period_start` + interval `sq`.`num` year) AS `datetime_period_inst_start`,(`pr`.`datetime_part_period_end` + interval `sq`.`num` year) AS `datetime_period_inst_end`,ifnull(`pis`.`blocked`,0) AS `blocked` from ((`{$prefix}cb2_view_sequence_date` `sq` join `{$prefix}cb2_periods` `pr`) left join `{$prefix}cb2_periodinst_settings` `pis` on(((`pis`.`period_id` = `pr`.`period_id`) and (`pis`.`recurrence_index` = `sq`.`num`)))) where ((`pr`.`recurrence_type` = 'Y') and ((year(`pr`.`datetime_part_period_end`) + `sq`.`num`) < 9999) and (`pr`.`datetime_from` <= (`pr`.`datetime_part_period_start` + interval `sq`.`num` year)) and (isnull(`pr`.`datetime_to`) or (`pr`.`datetime_to` >= (`pr`.`datetime_part_period_end` + interval `sq`.`num` year)))) union all select `pr`.`period_id` AS `period_id`,`sq`.`num` AS `recurrence_index`,(`pr`.`datetime_part_period_start` + interval `sq`.`num` month) AS `datetime_period_inst_start`,(`pr`.`datetime_part_period_end` + interval `sq`.`num` month) AS `datetime_period_inst_end`,ifnull(`pis`.`blocked`,0) AS `blocked` from ((`{$prefix}cb2_view_sequence_date` `sq` join `{$prefix}cb2_periods` `pr`) left join `{$prefix}cb2_periodinst_settings` `pis` on(((`pis`.`period_id` = `pr`.`period_id`) and (`pis`.`recurrence_index` = `sq`.`num`)))) where ((`pr`.`recurrence_type` = 'M') and ((year(`pr`.`datetime_part_period_end`) + (`sq`.`num` / 12)) < 9999) and ((`pr`.`recurrence_sequence` = 0) or (`pr`.`recurrence_sequence` & (pow(2,month((`pr`.`datetime_part_period_start` + interval `sq`.`num` month))) - 1))) and (`pr`.`datetime_from` <= (`pr`.`datetime_part_period_start` + interval `sq`.`num` month)) and (isnull(`pr`.`datetime_to`) or (`pr`.`datetime_to` >= (`pr`.`datetime_part_period_end` + interval `sq`.`num` month)))) union all select `pr`.`period_id` AS `period_id`,`sq`.`num` AS `recurrence_index`,(`pr`.`datetime_part_period_start` + interval `sq`.`num` week) AS `datetime_period_inst_start`,(`pr`.`datetime_part_period_end` + interval `sq`.`num` week) AS `datetime_period_inst_end`,ifnull(`pis`.`blocked`,0) AS `blocked` from ((`{$prefix}cb2_view_sequence_date` `sq` join `{$prefix}cb2_periods` `pr`) left join `{$prefix}cb2_periodinst_settings` `pis` on(((`pis`.`period_id` = `pr`.`period_id`) and (`pis`.`recurrence_index` = `sq`.`num`)))) where ((`pr`.`recurrence_type` = 'W') and ((year(`pr`.`datetime_part_period_end`) + (`sq`.`num` / 52)) < 9999) and (`pr`.`datetime_from` <= (`pr`.`datetime_part_period_start` + interval `sq`.`num` week)) and (isnull(`pr`.`datetime_to`) or (`pr`.`datetime_to` >= (`pr`.`datetime_part_period_end` + interval `sq`.`num` week)))) union all select `pr`.`period_id` AS `period_id`,`sq`.`num` AS `recurrence_index`,(addtime(cast(cast(`pr`.`datetime_from` as date) as datetime),cast(`pr`.`datetime_part_period_start` as time)) + interval `sq`.`num` day) AS `datetime_period_inst_start`,(addtime(cast(cast(`pr`.`datetime_from` as date) as datetime),cast(`pr`.`datetime_part_period_end` as time)) + interval `sq`.`num` day) AS `datetime_period_inst_end`,ifnull(`pis`.`blocked`,0) AS `blocked` from (((`{$prefix}cb2_view_sequence_date` `sq` join `{$prefix}cb2_periods` `pr`) left join `{$prefix}cb2_periodinst_settings` `pis` on(((`pis`.`period_id` = `pr`.`period_id`) and (`pis`.`recurrence_index` = `sq`.`num`)))) left join `{$prefix}options` `o` on((`o`.`option_name` = 'start_of_week'))) where ((`pr`.`recurrence_type` = 'D') and ((year(`pr`.`datetime_part_period_end`) + (`sq`.`num` / 356)) < 9999) and ((`pr`.`recurrence_sequence` = 0) or (`pr`.`recurrence_sequence` & pow(2,((((dayofweek((`pr`.`datetime_from` + interval `sq`.`num` day)) - 1) - cast(ifnull(`o`.`option_value`,'0') as signed)) + 7) % 7)))) and (isnull(`pr`.`datetime_to`) or (`pr`.`datetime_to` >= (addtime(cast(cast(`pr`.`datetime_from` as date) as datetime),cast(`pr`.`datetime_part_period_start` as time)) + interval `sq`.`num` day))))",
			'cb2_view_periodinst_posts'    => "select ((((`ip`.`global_period_group_id` * `pt_pi`.`ID_multiplier`) + ((`po`.`period_id` - 1) * `pt_pd`.`ID_multiplier`)) + `po`.`recurrence_index`) + `pt_pi`.`ID_base`) AS `ID`,1 AS `post_author`,`po`.`datetime_period_inst_start` AS `post_date`,`po`.`datetime_period_inst_start` AS `post_date_gmt`,'' AS `post_content`,`pst`.`name` AS `post_title`,'' AS `post_excerpt`,if(`ip`.`enabled`,'publish','trash') AS `post_status`,'closed' AS `comment_status`,'closed' AS `ping_status`,'' AS `post_password`,((((`ip`.`global_period_group_id` * `pt_pi`.`ID_multiplier`) + ((`po`.`period_id` - 1) * `pt_pd`.`ID_multiplier`)) + `po`.`recurrence_index`) + `pt_pi`.`ID_base`) AS `post_name`,'' AS `to_ping`,'' AS `pinged`,`po`.`datetime_period_inst_end` AS `post_modified`,`po`.`datetime_period_inst_end` AS `post_modified_gmt`,'' AS `post_content_filtered`,((`po`.`period_id` * `pt_p`.`ID_multiplier`) + `pt_p`.`ID_base`) AS `post_parent`,'' AS `guid`,0 AS `menu_order`,`pt_pi`.`post_type` AS `post_type`,'' AS `post_mime_type`,0 AS `comment_count`,`ip`.`global_period_group_id` AS `timeframe_id`,`pt_e`.`post_type_id` AS `post_type_id`,((`pgp`.`period_group_id` * `pt_pg`.`ID_multiplier`) + `pt_pg`.`ID_base`) AS `period_group_ID`,((`po`.`period_id` * `pt_p`.`ID_multiplier`) + `pt_p`.`ID_base`) AS `period_ID`,((`ip`.`global_period_group_id` * `pt_e`.`ID_multiplier`) + `pt_e`.`ID_base`) AS `period_entity_ID`,((`pst`.`period_status_type_id` * `pt_pst`.`ID_multiplier`) + `pt_pst`.`ID_base`) AS `period_status_type_ID`,`po`.`period_id` AS `period_native_id`,`po`.`recurrence_index` AS `recurrence_index`,0 AS `location_ID`,0 AS `item_ID`,0 AS `user_ID`,`pst`.`period_status_type_id` AS `period_status_type_native_id`,`pst`.`name` AS `period_status_type_name`,`po`.`datetime_period_inst_start` AS `datetime_period_inst_start`,`po`.`datetime_period_inst_end` AS `datetime_period_inst_end`,cast(`po`.`blocked` as unsigned) AS `blocked`,cast(`ip`.`enabled` as unsigned) AS `enabled` from ((((((((((`{$prefix}cb2_cache_periodinsts` `po` join `{$prefix}cb2_periods` `p` on((`po`.`period_id` = `p`.`period_id`))) join `{$prefix}cb2_period_group_period` `pgp` on((`pgp`.`period_id` = `p`.`period_id`))) join `{$prefix}cb2_global_period_groups` `ip` on((`ip`.`period_group_id` = `pgp`.`period_group_id`))) join `{$prefix}cb2_period_status_types` `pst` on((`ip`.`period_status_type_id` = `pst`.`period_status_type_id`))) join `{$prefix}cb2_post_types` `pt_pi`) join `{$prefix}cb2_post_types` `pt_pd`) join `{$prefix}cb2_post_types` `pt_p`) join `{$prefix}cb2_post_types` `pt_pg`) join `{$prefix}cb2_post_types` `pt_e`) join `{$prefix}cb2_post_types` `pt_pst`) where ((4 = `pt_pi`.`post_type_id`) and (1 = `pt_p`.`post_type_id`) and (101 = `pt_pd`.`post_type_id`) and (2 = `pt_pg`.`post_type_id`) and (12 = `pt_e`.`post_type_id`) and (8 = `pt_pst`.`post_type_id`) and (isnull(`ip`.`entity_datetime_from`) or (`po`.`datetime_period_inst_start` >= `ip`.`entity_datetime_from`)) and (isnull(`ip`.`entity_datetime_to`) or (`po`.`datetime_period_inst_end` <= `ip`.`entity_datetime_to`))) union all select ((((`ip`.`location_period_group_id` * `pt_pi`.`ID_multiplier`) + ((`po`.`period_id` - 1) * `pt_pd`.`ID_multiplier`)) + `po`.`recurrence_index`) + `pt_pi`.`ID_base`) AS `ID`,1 AS `post_author`,`po`.`datetime_period_inst_start` AS `post_date`,`po`.`datetime_period_inst_start` AS `post_date_gmt`,'' AS `post_content`,`pst`.`name` AS `post_title`,'' AS `post_excerpt`,if(`ip`.`enabled`,'publish','trash') AS `post_status`,'closed' AS `comment_status`,'closed' AS `ping_status`,'' AS `post_password`,((((`ip`.`location_period_group_id` * `pt_pi`.`ID_multiplier`) + ((`po`.`period_id` - 1) * `pt_pd`.`ID_multiplier`)) + `po`.`recurrence_index`) + `pt_pi`.`ID_base`) AS `post_name`,'' AS `to_ping`,'' AS `pinged`,`po`.`datetime_period_inst_end` AS `post_modified`,`po`.`datetime_period_inst_end` AS `post_modified_gmt`,'' AS `post_content_filtered`,((`po`.`period_id` * `pt_p`.`ID_multiplier`) + `pt_p`.`ID_base`) AS `post_parent`,'' AS `guid`,0 AS `menu_order`,`pt_pi`.`post_type` AS `post_type`,'' AS `post_mime_type`,0 AS `comment_count`,`ip`.`location_period_group_id` AS `timeframe_id`,`pt_e`.`post_type_id` AS `post_type_id`,((`pgp`.`period_group_id` * `pt_pg`.`ID_multiplier`) + `pt_pg`.`ID_base`) AS `period_group_ID`,((`po`.`period_id` * `pt_p`.`ID_multiplier`) + `pt_p`.`ID_base`) AS `period_ID`,((`ip`.`location_period_group_id` * `pt_e`.`ID_multiplier`) + `pt_e`.`ID_base`) AS `period_entity_ID`,((`pst`.`period_status_type_id` * `pt_pst`.`ID_multiplier`) + `pt_pst`.`ID_base`) AS `period_status_type_ID`,`po`.`period_id` AS `period_native_id`,`po`.`recurrence_index` AS `recurrence_index`,`ip`.`location_ID` AS `location_ID`,0 AS `item_ID`,0 AS `user_ID`,`pst`.`period_status_type_id` AS `period_status_type_native_id`,`pst`.`name` AS `period_status_type_name`,`po`.`datetime_period_inst_start` AS `datetime_period_inst_start`,`po`.`datetime_period_inst_end` AS `datetime_period_inst_end`,cast(`po`.`blocked` as unsigned) AS `blocked`,cast(`ip`.`enabled` as unsigned) AS `enabled` from (((((((((((`{$prefix}cb2_cache_periodinsts` `po` join `{$prefix}cb2_periods` `p` on((`po`.`period_id` = `p`.`period_id`))) join `{$prefix}cb2_period_group_period` `pgp` on((`pgp`.`period_id` = `p`.`period_id`))) join `{$prefix}cb2_location_period_groups` `ip` on((`ip`.`period_group_id` = `pgp`.`period_group_id`))) join `{$prefix}posts` `loc` on((`ip`.`location_ID` = `loc`.`ID`))) join `{$prefix}cb2_period_status_types` `pst` on((`ip`.`period_status_type_id` = `pst`.`period_status_type_id`))) join `{$prefix}cb2_post_types` `pt_pi`) join `{$prefix}cb2_post_types` `pt_pd`) join `{$prefix}cb2_post_types` `pt_p`) join `{$prefix}cb2_post_types` `pt_pg`) join `{$prefix}cb2_post_types` `pt_e`) join `{$prefix}cb2_post_types` `pt_pst`) where ((5 = `pt_pi`.`post_type_id`) and (1 = `pt_p`.`post_type_id`) and (101 = `pt_pd`.`post_type_id`) and (2 = `pt_pg`.`post_type_id`) and (13 = `pt_e`.`post_type_id`) and (8 = `pt_pst`.`post_type_id`) and (isnull(`ip`.`entity_datetime_from`) or (`po`.`datetime_period_inst_start` >= `ip`.`entity_datetime_from`)) and (isnull(`ip`.`entity_datetime_to`) or (`po`.`datetime_period_inst_end` <= `ip`.`entity_datetime_to`))) union all select ((((`ip`.`timeframe_period_group_id` * `pt_pi`.`ID_multiplier`) + ((`po`.`period_id` - 1) * `pt_pd`.`ID_multiplier`)) + `po`.`recurrence_index`) + `pt_pi`.`ID_base`) AS `ID`,1 AS `post_author`,`po`.`datetime_period_inst_start` AS `post_date`,`po`.`datetime_period_inst_start` AS `post_date_gmt`,'' AS `post_content`,`pst`.`name` AS `post_title`,'' AS `post_excerpt`,if(`ip`.`enabled`,'publish','trash') AS `post_status`,'closed' AS `comment_status`,'closed' AS `ping_status`,'' AS `post_password`,((((`ip`.`timeframe_period_group_id` * `pt_pi`.`ID_multiplier`) + ((`po`.`period_id` - 1) * `pt_pd`.`ID_multiplier`)) + `po`.`recurrence_index`) + `pt_pi`.`ID_base`) AS `post_name`,'' AS `to_ping`,'' AS `pinged`,`po`.`datetime_period_inst_end` AS `post_modified`,`po`.`datetime_period_inst_end` AS `post_modified_gmt`,'' AS `post_content_filtered`,((`po`.`period_id` * `pt_p`.`ID_multiplier`) + `pt_p`.`ID_base`) AS `post_parent`,'' AS `guid`,0 AS `menu_order`,`pt_pi`.`post_type` AS `post_type`,'' AS `post_mime_type`,0 AS `comment_count`,`ip`.`timeframe_period_group_id` AS `timeframe_id`,`pt_e`.`post_type_id` AS `post_type_id`,((`pgp`.`period_group_id` * `pt_pg`.`ID_multiplier`) + `pt_pg`.`ID_base`) AS `period_group_ID`,((`po`.`period_id` * `pt_p`.`ID_multiplier`) + `pt_p`.`ID_base`) AS `period_ID`,((`ip`.`timeframe_period_group_id` * `pt_e`.`ID_multiplier`) + `pt_e`.`ID_base`) AS `period_entity_ID`,((`pst`.`period_status_type_id` * `pt_pst`.`ID_multiplier`) + `pt_pst`.`ID_base`) AS `period_status_type_ID`,`po`.`period_id` AS `period_native_id`,`po`.`recurrence_index` AS `recurrence_index`,`ip`.`location_ID` AS `location_ID`,`ip`.`item_ID` AS `item_ID`,0 AS `user_ID`,`pst`.`period_status_type_id` AS `period_status_type_native_id`,`pst`.`name` AS `period_status_type_name`,`po`.`datetime_period_inst_start` AS `datetime_period_inst_start`,`po`.`datetime_period_inst_end` AS `datetime_period_inst_end`,cast(`po`.`blocked` as unsigned) AS `blocked`,cast(`ip`.`enabled` as unsigned) AS `enabled` from ((((((((((((`{$prefix}cb2_cache_periodinsts` `po` join `{$prefix}cb2_periods` `p` on((`po`.`period_id` = `p`.`period_id`))) join `{$prefix}cb2_period_group_period` `pgp` on((`pgp`.`period_id` = `p`.`period_id`))) join `{$prefix}cb2_timeframe_period_groups` `ip` on((`ip`.`period_group_id` = `pgp`.`period_group_id`))) join `{$prefix}posts` `loc` on((`ip`.`location_ID` = `loc`.`ID`))) join `{$prefix}posts` `itm` on((`ip`.`item_ID` = `itm`.`ID`))) join `{$prefix}cb2_period_status_types` `pst` on((`ip`.`period_status_type_id` = `pst`.`period_status_type_id`))) join `{$prefix}cb2_post_types` `pt_pi`) join `{$prefix}cb2_post_types` `pt_pd`) join `{$prefix}cb2_post_types` `pt_p`) join `{$prefix}cb2_post_types` `pt_pg`) join `{$prefix}cb2_post_types` `pt_e`) join `{$prefix}cb2_post_types` `pt_pst`) where ((6 = `pt_pi`.`post_type_id`) and (1 = `pt_p`.`post_type_id`) and (101 = `pt_pd`.`post_type_id`) and (2 = `pt_pg`.`post_type_id`) and (14 = `pt_e`.`post_type_id`) and (8 = `pt_pst`.`post_type_id`) and (isnull(`ip`.`entity_datetime_from`) or (`po`.`datetime_period_inst_start` >= `ip`.`entity_datetime_from`)) and (isnull(`ip`.`entity_datetime_to`) or (`po`.`datetime_period_inst_end` <= `ip`.`entity_datetime_to`))) union all select ((((`ip`.`timeframe_user_period_group_id` * `pt_pi`.`ID_multiplier`) + ((`po`.`period_id` - 1) * `pt_pd`.`ID_multiplier`)) + `po`.`recurrence_index`) + `pt_pi`.`ID_base`) AS `ID`,1 AS `post_author`,`po`.`datetime_period_inst_start` AS `post_date`,`po`.`datetime_period_inst_start` AS `post_date_gmt`,'' AS `post_content`,`pst`.`name` AS `post_title`,'' AS `post_excerpt`,if(`ip`.`enabled`,'publish','trash') AS `post_status`,'closed' AS `comment_status`,'closed' AS `ping_status`,'' AS `post_password`,((((`ip`.`timeframe_user_period_group_id` * `pt_pi`.`ID_multiplier`) + ((`po`.`period_id` - 1) * `pt_pd`.`ID_multiplier`)) + `po`.`recurrence_index`) + `pt_pi`.`ID_base`) AS `post_name`,'' AS `to_ping`,'' AS `pinged`,`po`.`datetime_period_inst_end` AS `post_modified`,`po`.`datetime_period_inst_end` AS `post_modified_gmt`,'' AS `post_content_filtered`,((`po`.`period_id` * `pt_p`.`ID_multiplier`) + `pt_p`.`ID_base`) AS `post_parent`,'' AS `guid`,0 AS `menu_order`,`pt_pi`.`post_type` AS `post_type`,'' AS `post_mime_type`,0 AS `comment_count`,`ip`.`timeframe_user_period_group_id` AS `timeframe_id`,`pt_e`.`post_type_id` AS `post_type_id`,((`pgp`.`period_group_id` * `pt_pg`.`ID_multiplier`) + `pt_pg`.`ID_base`) AS `period_group_ID`,((`po`.`period_id` * `pt_p`.`ID_multiplier`) + `pt_p`.`ID_base`) AS `period_ID`,((`ip`.`timeframe_user_period_group_id` * `pt_e`.`ID_multiplier`) + `pt_e`.`ID_base`) AS `period_entity_ID`,((`pst`.`period_status_type_id` * `pt_pst`.`ID_multiplier`) + `pt_pst`.`ID_base`) AS `period_status_type_ID`,`po`.`period_id` AS `period_native_id`,`po`.`recurrence_index` AS `recurrence_index`,`ip`.`location_ID` AS `location_ID`,`ip`.`item_ID` AS `item_ID`,`ip`.`user_ID` AS `user_ID`,`pst`.`period_status_type_id` AS `period_status_type_native_id`,`pst`.`name` AS `period_status_type_name`,`po`.`datetime_period_inst_start` AS `datetime_period_inst_start`,`po`.`datetime_period_inst_end` AS `datetime_period_inst_end`,cast(`po`.`blocked` as unsigned) AS `blocked`,cast(`ip`.`enabled` as unsigned) AS `enabled` from (((((((((((((`{$prefix}cb2_cache_periodinsts` `po` join `{$prefix}cb2_periods` `p` on((`po`.`period_id` = `p`.`period_id`))) join `{$prefix}cb2_period_group_period` `pgp` on((`pgp`.`period_id` = `p`.`period_id`))) join `{$prefix}cb2_timeframe_user_period_groups` `ip` on((`ip`.`period_group_id` = `pgp`.`period_group_id`))) join `{$prefix}posts` `loc` on((`ip`.`location_ID` = `loc`.`ID`))) join `{$prefix}posts` `itm` on((`ip`.`item_ID` = `itm`.`ID`))) join `{$prefix}users` `usr` on((`ip`.`user_ID` = `usr`.`ID`))) join `{$prefix}cb2_period_status_types` `pst` on((`ip`.`period_status_type_id` = `pst`.`period_status_type_id`))) join `{$prefix}cb2_post_types` `pt_pi`) join `{$prefix}cb2_post_types` `pt_pd`) join `{$prefix}cb2_post_types` `pt_p`) join `{$prefix}cb2_post_types` `pt_pg`) join `{$prefix}cb2_post_types` `pt_e`) join `{$prefix}cb2_post_types` `pt_pst`) where ((7 = `pt_pi`.`post_type_id`) and (1 = `pt_p`.`post_type_id`) and (101 = `pt_pd`.`post_type_id`) and (2 = `pt_pg`.`post_type_id`) and (15 = `pt_e`.`post_type_id`) and (8 = `pt_pst`.`post_type_id`) and (isnull(`ip`.`entity_datetime_from`) or (`po`.`datetime_period_inst_start` >= `ip`.`entity_datetime_from`)) and (isnull(`ip`.`entity_datetime_to`) or (`po`.`datetime_period_inst_end` <= `ip`.`entity_datetime_to`)))",
			'cb2_view_periodinstmeta'      => "select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * `pt`.`ID_multiplier`) + `cal`.`recurrence_index`) * 20) + `pt`.`ID_base`) + 1) AS `meta_id`,`cal`.`ID` AS `post_id`,`cal`.`ID` AS `periodinst_id`,'location_ID' AS `meta_key`,`cal`.`location_ID` AS `meta_value` from (`{$prefix}cb2_view_periodinst_posts` `cal` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type_id` = `cal`.`post_type_id`))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * `pt`.`ID_multiplier`) + `cal`.`recurrence_index`) * 20) + `pt`.`ID_base`) + 2) AS `meta_id`,`cal`.`ID` AS `post_id`,`cal`.`ID` AS `periodinst_id`,'item_ID' AS `meta_key`,`cal`.`item_ID` AS `meta_value` from (`{$prefix}cb2_view_periodinst_posts` `cal` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type_id` = `cal`.`post_type_id`))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * `pt`.`ID_multiplier`) + `cal`.`recurrence_index`) * 20) + `pt`.`ID_base`) + 3) AS `meta_id`,`cal`.`ID` AS `post_id`,`cal`.`ID` AS `periodinst_id`,'user_ID' AS `meta_key`,`cal`.`user_ID` AS `meta_value` from (`{$prefix}cb2_view_periodinst_posts` `cal` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type_id` = `cal`.`post_type_id`))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * `pt`.`ID_multiplier`) + `cal`.`recurrence_index`) * 20) + `pt`.`ID_base`) + 4) AS `meta_id`,`cal`.`ID` AS `post_id`,`cal`.`ID` AS `periodinst_id`,'period_group_ID' AS `meta_key`,`cal`.`period_group_ID` AS `meta_value` from (`{$prefix}cb2_view_periodinst_posts` `cal` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type_id` = `cal`.`post_type_id`))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * `pt`.`ID_multiplier`) + `cal`.`recurrence_index`) * 20) + `pt`.`ID_base`) + 5) AS `meta_id`,`cal`.`ID` AS `post_id`,`cal`.`ID` AS `periodinst_id`,'period_ID' AS `meta_key`,`cal`.`period_ID` AS `meta_value` from (`{$prefix}cb2_view_periodinst_posts` `cal` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type_id` = `cal`.`post_type_id`))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * `pt`.`ID_multiplier`) + `cal`.`recurrence_index`) * 20) + `pt`.`ID_base`) + 6) AS `meta_id`,`cal`.`ID` AS `post_id`,`cal`.`ID` AS `periodinst_id`,'period_status_type_ID' AS `meta_key`,`cal`.`period_status_type_ID` AS `meta_value` from (`{$prefix}cb2_view_periodinst_posts` `cal` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type_id` = `cal`.`post_type_id`))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * `pt`.`ID_multiplier`) + `cal`.`recurrence_index`) * 20) + `pt`.`ID_base`) + 7) AS `meta_id`,`cal`.`ID` AS `post_id`,`cal`.`ID` AS `periodinst_id`,'recurrence_index' AS `meta_key`,`cal`.`recurrence_index` AS `meta_value` from (`{$prefix}cb2_view_periodinst_posts` `cal` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type_id` = `cal`.`post_type_id`))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * `pt`.`ID_multiplier`) + `cal`.`recurrence_index`) * 20) + `pt`.`ID_base`) + 9) AS `meta_id`,`cal`.`ID` AS `post_id`,`cal`.`ID` AS `periodinst_id`,'period_status_type_name' AS `meta_key`,`cal`.`period_status_type_name` AS `meta_value` from (`{$prefix}cb2_view_periodinst_posts` `cal` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type_id` = `cal`.`post_type_id`))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * `pt`.`ID_multiplier`) + `cal`.`recurrence_index`) * 20) + `pt`.`ID_base`) + 10) AS `meta_id`,`cal`.`ID` AS `post_id`,`cal`.`ID` AS `periodinst_id`,'period_entity_ID' AS `meta_key`,`cal`.`period_entity_ID` AS `meta_value` from (`{$prefix}cb2_view_periodinst_posts` `cal` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type_id` = `cal`.`post_type_id`))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * `pt`.`ID_multiplier`) + `cal`.`recurrence_index`) * 20) + `pt`.`ID_base`) + 11) AS `meta_id`,`cal`.`ID` AS `post_id`,`cal`.`ID` AS `periodinst_id`,'blocked' AS `meta_key`,`cal`.`blocked` AS `meta_value` from (`{$prefix}cb2_view_periodinst_posts` `cal` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type_id` = `cal`.`post_type_id`))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * `pt`.`ID_multiplier`) + `cal`.`recurrence_index`) * 20) + `pt`.`ID_base`) + 12) AS `meta_id`,`cal`.`ID` AS `post_id`,`cal`.`ID` AS `periodinst_id`,'enabled' AS `meta_key`,`cal`.`enabled` AS `meta_value` from (`{$prefix}cb2_view_periodinst_posts` `cal` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type_id` = `cal`.`post_type_id`))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * `pt`.`ID_multiplier`) + `cal`.`recurrence_index`) * 20) + `pt`.`ID_base`) + 13) AS `meta_id`,`cal`.`ID` AS `post_id`,`cal`.`ID` AS `periodinst_id`,'post_type' AS `meta_key`,`cal`.`post_type` AS `meta_value` from (`{$prefix}cb2_view_periodinst_posts` `cal` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type_id` = `cal`.`post_type_id`))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * `pt`.`ID_multiplier`) + `cal`.`recurrence_index`) * 20) + `pt`.`ID_base`) + 14) AS `meta_id`,`cal`.`ID` AS `post_id`,`cal`.`ID` AS `periodinst_id`,'datetime_period_inst_start' AS `meta_key`,`cal`.`datetime_period_inst_start` AS `meta_value` from (`{$prefix}cb2_view_periodinst_posts` `cal` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type_id` = `cal`.`post_type_id`))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * `pt`.`ID_multiplier`) + `cal`.`recurrence_index`) * 20) + `pt`.`ID_base`) + 15) AS `meta_id`,`cal`.`ID` AS `post_id`,`cal`.`ID` AS `periodinst_id`,'datetime_period_inst_end' AS `meta_key`,`cal`.`datetime_period_inst_end` AS `meta_value` from (`{$prefix}cb2_view_periodinst_posts` `cal` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type_id` = `cal`.`post_type_id`)))",
		);
	}

	static function metaboxes() {
		$metaboxes = array();

		array_push( $metaboxes,
			array(
				// TODO: link this Enabled in to the Publish meta-box status instead
				'title' => __( 'Blocked', 'commons-booking-2' ),
				'context' => 'normal',
				'show_names' => TRUE,
				'fields' => array(
					array(
						'id'      => 'enabled_explanation',
						'type'    => 'paragraph',
						'classes' => 'cb2-cmb2-compact',
						'desc'    => 'If blocked, this instance will not appear in the calendars.',
					),
					array(
						'name'    => __( 'Blocked', 'commons-booking-2' ),
						'id'      => 'blocked',
						'type'    => 'checkbox',
						'classes' => 'cb2-cmb2-compact',
						'default' => 0,
					),
				),
			)
		);
		array_push( $metaboxes,
			array(
				// TODO: link this Enabled in to the Publish meta-box status instead
				'title'      => __( 'Time period', 'commons-booking-2' ),
				'context'    => 'normal',
				'show_names' => TRUE,
				'classes'    => array( 'hidden' ),
				'fields' => array(
					array(
						'id'          => 'datetime_period_inst_start',
						'name'        => __( 'Start', 'commons-booking-2' ),
						'type'        => 'text_datetime_timestamp',
						'date_format' => CB2_Database::$database_date_format,
						'default' => ( isset( $_GET['datetime_period_inst_start'] ) ? $_GET['datetime_period_inst_start'] : NULL ),
					),
					array(
						'id'          => 'datetime_period_inst_end',
						'name'        => __( 'End', 'commons-booking-2' ),
						'type'        => 'text_datetime_timestamp',
						'date_format' => CB2_Database::$database_date_format,
						'default' => ( isset( $_GET['datetime_period_inst_end'] ) ? $_GET['datetime_period_inst_end'] : NULL ),
					),
				),
			)
		);

		return $metaboxes;
	}

	protected function __construct(
		$ID,
		$period_entity,
    $period,
    $recurrence_index,
    $datetime_period_inst_start,
    $datetime_period_inst_end,
    $blocked
  ) {
		CB2_Query::assign_all_parameters( $this, func_get_args(), __class__ );

		// Some sanity checks
		if ( $this->datetime_period_inst_start->after( $this->datetime_period_inst_end ) )
			throw new Exception( 'datetime_period_inst_start > datetime_period_inst_end' );

		// Add the period to all the days it appears in
		// CB2_Day::factory() will lazy create singleton CB2_Day's
		$this->days = array();
		if ( $this->datetime_period_inst_start ) {
			$date = clone $this->datetime_period_inst_start;
			do {
				// Only add ourselves to days that already exist
				// created by an external process like the calendar
				// TODO: need to substitute this for the $instance_container system
				if ( CB2_Day::day_exists( $date ) ) {
					$day = CB2_Day::factory( $date );
					$day->add_periodinst( $this );
					array_push( $this->days, $day );
				}
				$date->add( 1 );
			} while ( $date->before( $this->datetime_period_inst_end ) );
		}

    parent::__construct( $ID ); // $posts are the Location, Item and User
  }

  function is( CB2_PostNavigator $periodinst ) {
		return is_a( $periodinst, get_class( $this ) )
			&& property_exists( $periodinst, 'period_entity' )
			&& $periodinst->period_entity->is( $this->period_entity )
			&& $periodinst->recurrence_index == $this->recurrence_index;
  }

  function validity_period( String $format = 'wordpress' ) {
		return $this->period_entity->validity_period( $format );
  }

  function remove() {
		foreach ( $this->days as $day )
			$day->remove_post( $this );

		if ( $this->ID ) unset( self::$all[$this->ID] );
  }

  function get_the_colour() {
		return $this->period_entity->period_status_type->colour;
  }

  function is_blocked() {
		return $this->blocked;
  }

  function extra_object_do_actions() {
		return array( $this->period_entity );
  }

  function do_action_block() {
		return $this->block();
  }

  function do_action_unblock() {
		return $this->unblock();
  }

  function block( $block = TRUE ) {
		global $wpdb;

		// TODO: need to move this to standard save() so that events fire
		if ( $block ) {
			$full_table = "{$wpdb->prefix}cb2_periodinst_settings";
			$blocked = $wpdb->get_var( $wpdb->prepare(
				"SELECT blocked FROM $full_table where period_id = %d and recurrence_index = %d",
				$this->period->id(),
				$this->recurrence_index
			) );

			if ( is_null( $blocked ) ) $wpdb->insert(
					$full_table,
					array(
						'period_id'        => $this->period->id(),
						'recurrence_index' => $this->recurrence_index,
						'blocked'          => 1,
					)
				);
			else if ( $blocked == 0 ) $wpdb->update(
					$full_table,
					array(
						'blocked'          => 1,
					),
					array(
						'period_id'        => $this->period->id(),
						'recurrence_index' => $this->recurrence_index,
					)
				);
		} else {
			$this->unblock();
		}
  }

  function unblock() {
		global $wpdb;
		// Manual invocation of UPDATE because of BIT field
		$wpdb->query( $wpdb->prepare(
			"UPDATE {$wpdb->prefix}cb2_periodinst_settings
				SET blocked = b'0'
				WHERE period_id = %d and recurrence_index = %d",
			array(
				$this->period->id(),
				$this->recurrence_index,
			)
		) );
  }

  function get_the_edit_post_url( $context = 'display' ) {
		// Redirect the edit post to the entity
		return $this->period_entity->get_the_edit_post_url( $context );
  }

  function priority() {
		return ( property_exists( $this, 'priority' ) ? $this->priority : $this->period_entity->period_status_type->priority );
  }

	function summary() {
		return ucfirst( $this->post_type() ) . "($this->ID)";
	}

	function tabs( $edit_form_advanced = FALSE ) {
		return array(
			"cb2-tab-definition" => 'Definition',
			"cb2-tab-status"   => 'Security',
		);
	}

  function templates_considered( $context = 'list', $type = NULL, &$templates = NULL ) {
		// Priority order
		$templates = $this->period_entity->templates_considered( $context, $type, $templates );
		return parent::templates_considered( $context, $type, $templates );
	}

  function day_percent_positions( String $from_time = NULL, String $to_time = NULL ) {
    $seconds_start_percent = CB2_Day::day_percent_position( $this->datetime_period_inst_start, $from_time, $to_time );
    $seconds_end_percent   = CB2_Day::day_percent_position( $this->datetime_period_inst_end,   $from_time, $to_time );
    return array(
      'start_percent' => $seconds_start_percent,
      'end_percent'   => $seconds_end_percent,
      'diff_percent'  => $seconds_end_percent - $seconds_start_percent
    );
  }

  function classes() {
    $classes = $this->period->classes();
		$classes = array_merge( $classes, $this->period_entity->period_status_type->classes() );
    array_push( $classes, ( $this->is_top_priority() ? 'cb2-periodinst-no-overlap' : 'cb2-periodinst-has-overlap' ) );
    if ( $this->blocked ) array_push( $classes, 'cb2-blocked' );
    return $classes;
  }

  function is_top_priority() {
  	return ! $this->top_priority_overlap_period && ! is_null( $this->priority() );
  }

  function styles( Array $styles = array(), Array $options = array() ) {
    if ( isset( $options['absolute-positioning'] ) && $options['absolute-positioning'] ) {
			$zindex               = 10000 + $this->priority(); // Maybe overridden
			$day_percent_positions = $this->day_percent_positions();
			array_push( $styles, 'position:absolute' );
			array_push( $styles, "z-index:$zindex" );
			array_push( $styles, "top:$day_percent_positions[start_percent]%" );
			array_push( $styles, "height:$day_percent_positions[diff_percent]%" );
		}

		// May call through to the period_status_type as well
		$color = $this->get_the_colour();
		if ( $color ) array_push( $styles, "background-color:$color" );

    $styles = $this->period_entity->styles( $styles, $options );

    return $styles;
  }

  function row_actions( &$actions, $post ) {
		$period_ID = $this->period->ID;
		$actions[ 'edit-definition' ] = "<a href='admin.php?page=cb2-post-edit&post=$period_ID&post_type=period&action=edit'>Edit definition</a>";
		$actions[ 'trash occurence' ] = '<a href="#" class="submitdelete">Trash Occurence</a>';
	}

  function flags() {
    $flags = array();
    if ( $this->period_entity ) $flags = $this->period_entity->period_status_type->flags();
    return $flags;
  }

  function classes_for_day( $day ) {
    return array();
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

	function get_the_time_period( $format = NULL, $human_readable = TRUE, $separator = '-' ) {
		if ( is_null( $format ) ) $format = get_option( 'time_format' );
		$time_period = $this->datetime_period_inst_start->format( $format )
			. $separator
			. $this->datetime_period_inst_end->format( $format );
		if ( $human_readable ) {
			if ( $this->period->fullday ) $time_period = 'all day';
		}
		return $time_period;
	}

  function get_the_content( String $content = '', Bool $is_single = TRUE ) {
    // Flags field
    $content .= "<td class='cb2-flags'><ul>";
    foreach ( $this->flags() as $flag ) {
			$letter = ( substr( $flag, 0, 3 ) == 'no-' ? $flag[3] : $flag[0] );
      $content  .= "<li class='cb2-indicator-$flag'>$letter</li>";
    }
    $content .= '</ul></td>';

    return $content;
  }

  function get_the_title( $HTML = FALSE ) {
		$title  = '';
		if ( $HTML ) $title .= "<span class='cb2-time-period'>";
		$title .= $this->get_the_time_period();
		if ( $HTML ) $title .= "</span>\n";
		$title .= $this->period_entity->get_the_title( $HTML, $this );
		return $title;
  }

  function jsonSerialize() {
    return array(
      'period_ID' => $this->period->ID,
      'recurrence_index' => $this->recurrence_index,
      'name' => $this->post_title,
      'datetime_period_inst_start' => $this->datetime_period_inst_start->format( CB2_Query::$json_date_format ),
      'datetime_period_inst_end' => $this->datetime_period_inst_start->format( CB2_Query::$json_date_format ),
      'datetime_from' => $this->period->datetime_from->format( CB2_Query::$json_date_format ),
      'datetime_to' => ( $this->period->datetime_to ? $this->period->datetime_to->format( CB2_Query::$json_date_format ) : '' ),
      'period_status_type' => $this->period_entity->period_status_type,
      'recurrence_type' => $this->period->recurrence_type,
      'recurrence_frequency' => $this->period->recurrence_frequency,
      'recurrence_sequence' => $this->period->recurrence_sequence,
      'day_percent_positions' => $this->day_percent_positions(),
      'classes' => implode( ' ', $this->classes() ),
      'styles'  => implode( ';', $this->styles() ),
      'flag'    => $this->flags(),
      'fullday' => $this->period->fullday,
    );
  }
}


// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB2_PeriodInst_Global extends CB2_PeriodInst {
  static $database_table = 'cb2_global_period_groups';
  public  static $post_type_args = array(
		'menu_icon' => 'dashicons-admin-page',
		'label'     => 'Global Periods',
  );

	static public $static_post_type = 'periodinst-global';

  function post_type() {return self::$static_post_type;}

  protected function __construct(
		$ID,
		$period_entity,
    $period,
		$recurrence_index,
		$datetime_period_inst_start,
		$datetime_period_inst_end,
    $blocked
  ) {
    parent::__construct(
			$ID,
			$period_entity,
			$period,
			$recurrence_index,
			$datetime_period_inst_start,
			$datetime_period_inst_end,
			$blocked
    );
  }

  static function &factory_from_properties( Array &$properties, &$instance_container = NULL, Bool $force_properties = FALSE, Bool $set_create_new_post_properties = FALSE ) {
		$object = self::factory(
			(int) $properties['ID'],
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_entity_ID', $instance_container, 'CB2_PeriodEntity_Global' ),
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_ID',        $instance_container ),
			$properties['recurrence_index'],
			$properties['datetime_period_inst_start'],
			$properties['datetime_period_inst_end'],
			$properties['blocked'],
			$properties, $force_properties, $set_create_new_post_properties
		);

		return $object;
  }

  static function &factory(
		$ID,
		$period_entity,
    $period,     // CB2_Period
    $recurrence_index,
    $datetime_period_inst_start,
    $datetime_period_inst_end,
    $blocked,
    Array $properties = NULL, Bool $force_properties = FALSE, Bool $set_create_new_post_properties = FALSE
  ) {
		$object = CB2_PostNavigator::createInstance( __class__, func_get_args(), $ID, $properties, $force_properties, $set_create_new_post_properties );
		return $object;
  }

 	static function metaboxes() {
		return CB2_PeriodInst::metaboxes();
	}
}


// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB2_PeriodInst_Location extends CB2_PeriodInst {
  static $database_table = 'cb2_location_period_groups';
	static public $static_post_type = 'periodinst-location';
  static public $post_type_args = array(
		'menu_icon' => 'dashicons-admin-page',
		'label'     => 'Location Periods',
  );

  function post_type() {return self::$static_post_type;}

  protected function __construct(
		$ID,
		$period_entity,
    $period,
		$recurrence_index,
		$datetime_period_inst_start,
		$datetime_period_inst_end,
    $blocked
	) {
    parent::__construct(
			$ID,
			$period_entity,
			$period,
			$recurrence_index,
			$datetime_period_inst_start,
			$datetime_period_inst_end,
			$blocked
    );
    $this->period_entity->location->add_periodinst( $this );
    array_push( $this->posts, $this->period_entity->location );
  }

  static function &factory_from_properties( Array &$properties, &$instance_container = NULL, Bool $force_properties = FALSE, Bool $set_create_new_post_properties = FALSE ) {
		$object = self::factory(
			(int) $properties['ID'],
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_entity_ID', $instance_container, 'CB2_PeriodEntity_Location' ),
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_ID',        $instance_container ),
			$properties['recurrence_index'],
			$properties['datetime_period_inst_start'],
			$properties['datetime_period_inst_end'],
			$properties['blocked'],
			$properties, $force_properties, $set_create_new_post_properties
		);

		return $object;
  }

  static function &factory(
		$ID,
		$period_entity,
    $period,     // CB2_Period
    $recurrence_index,
    $datetime_period_inst_start,
    $datetime_period_inst_end,
    $blocked,
    Array $properties = NULL, Bool $force_properties = FALSE, Bool $set_create_new_post_properties = FALSE
  ) {
		$object = CB2_PostNavigator::createInstance( __class__, func_get_args(), $ID, $properties, $force_properties, $set_create_new_post_properties );
		return $object;
  }

  function jsonSerialize() {
    $array = parent::jsonSerialize();
    //$array[ 'location' ] = &$this->location;
    return $array;
  }

 	static function metaboxes() {
		return CB2_PeriodInst::metaboxes();
	}
}


// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB2_PeriodInst_Timeframe extends CB2_PeriodInst {
  static $database_table = 'cb2_timeframe_period_groups';
  static $database_options_table = 'cb2_timeframe_options';
  public  static $post_type_args = array(
		'menu_icon' => 'dashicons-admin-page',
		'label'     => 'Item Timeframes',
  );

  static function database_table_name() { return self::$database_options_table; }

  static function database_table_schemas( $prefix ) {
		return array( array(
			'name'    => self::$database_options_table,
			'columns' => array(
				'option_id'    => array( CB2_INT,      (20),  CB2_UNSIGNED, CB2_NOT_NULL, CB2_AUTO_INCREMENT ),
				'timeframe_id' => array( CB2_BIGINT,   (20),  CB2_UNSIGNED, CB2_NOT_NULL ),
				'option_name'  => array( CB2_VARCHAR,  (191), NULL,     NULL, NULL, NULL ),
				'option_value' => array( CB2_LONGTEXT, NULL,  NULL,     CB2_NOT_NULL ),
				'author_ID'    => array( CB2_BIGINT,   (20),  CB2_UNSIGNED,     CB2_NOT_NULL, FALSE, 1 ),
			),
			'primary key'  => array( 'option_id' ),
			'keys'         => array( 'timeframe_id' ),
			'foreign keys' => array(
				'timeframe_id' => array( 'cb2_timeframe_period_groups', 'timeframe_period_group_id' ),
				'author_ID'    => array( 'users', 'ID' ),
			),
		) );
	}

	static public $static_post_type = 'periodinst-timeframe';

  function post_type() {return self::$static_post_type;}

  protected function __construct(
		$ID,
		$period_entity,
    $period,
		$recurrence_index,
		$datetime_period_inst_start,
		$datetime_period_inst_end,
    $blocked
  ) {
    parent::__construct(
			$ID,
			$period_entity,
			$period,
			$recurrence_index,
			$datetime_period_inst_start,
			$datetime_period_inst_end,
			$blocked
    );
    array_push( $this->posts, $this->period_entity->location );
    array_push( $this->posts, $this->period_entity->item );
		$this->period_entity->location->add_periodinst( $this );
		$this->period_entity->item->add_periodinst( $this );
  }

  static function &factory_from_properties( Array &$properties, &$instance_container = NULL, Bool $force_properties = FALSE, Bool $set_create_new_post_properties = FALSE ) {
		$object = self::factory(
			(int) $properties['ID'],
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_entity_ID', $instance_container, 'CB2_PeriodEntity_Timeframe' ),
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_ID',        $instance_container ),
			$properties['recurrence_index'],
			$properties['datetime_period_inst_start'],
			$properties['datetime_period_inst_end'],
			$properties['blocked'],
			$properties, $force_properties, $set_create_new_post_properties
		);

		return $object;
  }

  static function &factory(
		$ID,
		$period_entity, // CB2_PeriodEntity
    $period,        // CB2_Period
    $recurrence_index,
    $datetime_period_inst_start,
    $datetime_period_inst_end,
    $blocked,
    Array $properties = NULL, Bool $force_properties = FALSE, Bool $set_create_new_post_properties = FALSE
  ) {
    // Design Patterns: Factory Singleton with Multiton
    // $ID = period_ID * x + recurrence_index
    // TODO: if 2 different period_entities share the same period, then it will not __construct() twice
		$object = CB2_PostNavigator::createInstance( __class__, func_get_args(), $ID, $properties, $force_properties, $set_create_new_post_properties );
		return $object;
  }

  function get_option( $option, $default = FALSE ) {
		$value = $default;
		if ( isset( $this->period_database_record ) && isset( $this->period_database_record->$option ) )
      $value = $this->period_database_record->$option;
		return $value;
  }

  function update_option( $option, $new_value, $autoload = TRUE ) {
		global $wpdb;

		// TODO: rationalise these DB functions in to parent Class
		$found = $wpdb->update( $wpdb->prefix . CB2_Database::database_table( $Class ),
			array( 'option_value' => $new_value ),
			array(
				'timeframe_id' => $this->id(),
				'option_name'  =>  $option,
			)
		);
		if ( ! $found ) $wpdb->insert( $wpdb->prefix . CB2_Database::database_table( $Class ),
			array(
				'timeframe_id' => $this->id(),
				'option_name'  =>  $option,
				'option_value' => $new_value,
			)
		);

    return $this;
  }

  function jsonSerialize() {
    $array = parent::jsonSerialize();
    //$array[ 'location' ] = &$this->period_entity->location;
    //$array[ 'item' ]     = &$this->item;
    return $array;
  }

 	static function metaboxes() {
		return CB2_PeriodInst::metaboxes();
	}

	function get_api_data($version){
		return array(
      'status' => get_the_title($this),
      'start' => $this->datetime_period_inst_start->format( CB2_Query::$json_date_format ),
			'end' => $this->datetime_period_inst_end->format( CB2_Query::$json_date_format ),
			'location_uid' => get_the_guid($this->period_entity->location)
		);
	}
}


// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB2_PeriodInst_Location_User extends CB2_PeriodInst {
  static $database_table = 'cb2_location_user_period_groups';
  public  static $post_type_args = array(
		'menu_icon' => 'dashicons-admin-page',
		'label'     => 'Item Timeframes',
  );

	static public $static_post_type = 'periodinst-staff';

  function post_type() {return self::$static_post_type;}

  protected function __construct(
		$ID,
		$period_entity,
    $period,
		$recurrence_index,
		$datetime_period_inst_start,
		$datetime_period_inst_end,
    $blocked
  ) {
    parent::__construct(
			$ID,
			$period_entity,
			$period,
			$recurrence_index,
			$datetime_period_inst_start,
			$datetime_period_inst_end,
			$blocked
    );
    array_push( $this->posts, $this->period_entity->location );
    array_push( $this->posts, $this->period_entity->user );
		$this->period_entity->location->add_periodinst( $this );
		$this->period_entity->user->add_periodinst( $this );
  }

  static function &factory_from_properties( Array &$properties, &$instance_container = NULL, Bool $force_properties = FALSE, Bool $set_create_new_post_properties = FALSE ) {
		$object = self::factory(
			(int) $properties['ID'],
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_entity_ID', $instance_container, 'CB2_PeriodEntity_Timeframe' ),
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_ID',        $instance_container ),
			$properties['recurrence_index'],
			$properties['datetime_period_inst_start'],
			$properties['datetime_period_inst_end'],
			$properties['blocked'],
			$properties, $force_properties, $set_create_new_post_properties
		);

		return $object;
  }

  static function &factory(
		$ID,
		$period_entity, // CB2_PeriodEntity
    $period,        // CB2_Period
    $recurrence_index,
    $datetime_period_inst_start,
    $datetime_period_inst_end,
    $blocked,
    Array $properties = NULL, Bool $force_properties = FALSE, Bool $set_create_new_post_properties = FALSE
  ) {
    // Design Patterns: Factory Singleton with Multiton
    // $ID = period_ID * x + recurrence_index
    // TODO: if 2 different period_entities share the same period, then it will not __construct() twice
		$object = CB2_PostNavigator::createInstance( __class__, func_get_args(), $ID, $properties, $force_properties, $set_create_new_post_properties );
		return $object;
  }

  function jsonSerialize() {
    $array = parent::jsonSerialize();
    //$array[ 'location' ] = &$this->period_entity->location;
    //$array[ 'item' ]     = &$this->item;
    return $array;
  }

 	static function metaboxes() {
		return CB2_PeriodInst::metaboxes();
	}

	function get_api_data($version){
		return array(
      'status' => get_the_title($this),
      'start' => $this->datetime_period_inst_start->format( CB2_Query::$json_date_format ),
			'end' => $this->datetime_period_inst_start->format( CB2_Query::$json_date_format ),
			'location' => $this->period_entity->location->ID
		);
	}
}


// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB2_PeriodInst_Timeframe_User extends CB2_PeriodInst {
  static $database_table         = 'cb2_timeframe_user_period_groups';
  public  static $post_type_args = array(
		'menu_icon' => 'dashicons-admin-page',
		'label'     => 'User Periods',
  );

	static public $static_post_type = 'periodinst-user';

  function post_type() {return self::$static_post_type;}

  protected function __construct(
		$ID,
		$period_entity,
    $period,
		$recurrence_index,
		$datetime_period_inst_start,
		$datetime_period_inst_end,
    $blocked
  ) {
    parent::__construct(
			$ID,
			$period_entity,
			$period,
			$recurrence_index,
			$datetime_period_inst_start,
			$datetime_period_inst_end,
			$blocked
		);
    array_push( $this->posts, $this->period_entity->location );
    array_push( $this->posts, $this->period_entity->item );
    array_push( $this->posts, $this->period_entity->user );
		$this->period_entity->location->add_periodinst( $this );
		$this->period_entity->location->add_item( $this->period_entity->item );
		$this->period_entity->item->add_periodinst( $this );
    $this->period_entity->user->add_periodinst( $this );
  }

  static function &factory_from_properties( Array &$properties, &$instance_container = NULL, Bool $force_properties = FALSE, Bool $set_create_new_post_properties = FALSE ) {
		$object = self::factory(
			(int) $properties['ID'],
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_entity_ID', $instance_container, 'CB2_PeriodEntity_Timeframe_User' ),
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_ID',        $instance_container ),
			$properties['recurrence_index'],
			$properties['datetime_period_inst_start'],
			$properties['datetime_period_inst_end'],
			$properties['blocked'],
			$properties, $force_properties, $set_create_new_post_properties
		);

		return $object;
  }

  static function &factory(
		$ID,
		$period_entity, // CB2_PeriodEntity
    $period,        // CB2_Period
    $recurrence_index,
    $datetime_period_inst_start,
    $datetime_period_inst_end,
    $blocked,
    Array $properties = NULL, Bool $force_properties = FALSE, Bool $set_create_new_post_properties = FALSE
  ) {
		$object = CB2_PostNavigator::createInstance( __class__, func_get_args(), $ID, $properties, $force_properties, $set_create_new_post_properties );
		return $object;
  }

  function jsonSerialize() {
    $array = parent::jsonSerialize();
    //$array[ 'location' ] = &$this->location;
    //$array[ 'item' ]     = &$this->item;
    //$array[ 'user' ]     = &$this->user;
    return $array;
  }

 	static function metaboxes() {
		return CB2_PeriodInst::metaboxes();
	}
}
