<?php
require_once( 'CB2_Period.php' );

abstract class CB2_PeriodItem extends CB2_PostNavigator implements JsonSerializable {
	public  static $database_table   = 'cb2_perioditem_settings';
	public  static $description      = 'CB2_PeriodItem uses <b>triggers</b> to update the primary postmeta table for performance.';
  public  static $all              = array();

  // Setting $postmeta_table to FALSE
  // will cause the intergration to JOIN to wp_postmeta
  // for these post_types
  // triggers would be necessary to update wp_postmeta
  // the entries in wp_postmeta would conflict with normal wp_posts of the same ID
  // not necessarily causing issues
  //
  // This system WAS implemented originally for performance
  // but has been replaced with a proactive trigger to cache wp_cb2_view_perioditems
  // public  static $postmeta_table   = FALSE;

	public static $all_post_types = array(
		'perioditem-global',
		'perioditem-location',
		'perioditem-timeframe',
		'perioditem-user',
	);
  private static $null_recurrence_index = 0;
  private $priority_overlap_periods     = array();
  private $top_priority_overlap_period  = NULL;

	static function database_table_name() { return self::$database_table; }

  static function database_table_schemas( $prefix ) {
		// The repeating periods calculations
		// are one of the biggest performance hits
		// so they are cached in the database using triggers
		$perioditem_cache_table   = "{$prefix}cb2_cache_perioditems";
		$refresh_cache        = "delete from $perioditem_cache_table; insert into $perioditem_cache_table(period_id, recurrence_index, datetime_period_item_start, datetime_period_item_end, blocked) select * from {$prefix}cb2_view_perioditems;";

		return array( array(
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
		) );
  }

  static function database_views( $prefix ) {
		// cb2_view_sequence_date is designed to return 4000 rows
		// which is equivalent to 10 years on daily repeat
		$perioditem_cache_table = "{$prefix}cb2_cache_perioditems";

		return array(
			'cb2_view_sequence_num'        => "select 0 AS `num` union all select 1 AS `1` union all select 2 AS `2` union all select 3 AS `3` union all select 4 AS `4` union all select 5 AS `5` union all select 6 AS `6` union all select 7 AS `7` union all select 8 AS `8` union all select 9 AS `9`",
			'cb2_view_sequence_date'       => "select ((`t4`.`num` * 1000) + ((`t3`.`num` * 100) + ((`t2`.`num` * 10) + `t1`.`num`))) AS `num` from (((`{$prefix}cb2_view_sequence_num` `t1` join `{$prefix}cb2_view_sequence_num` `t2`) join `{$prefix}cb2_view_sequence_num` `t3`) join `{$prefix}cb2_view_sequence_num` `t4`) where (`t4`.`num` <= 3)",
			'cb2_view_perioditems'         => "select `pr`.`period_id` AS `period_id`,0 AS `recurrence_index`,`pr`.`datetime_part_period_start` AS `datetime_period_item_start`,`pr`.`datetime_part_period_end` AS `datetime_period_item_end`,ifnull(`pis`.`blocked`,0) AS `blocked` from (`{$prefix}cb2_periods` `pr` left join `{$prefix}cb2_perioditem_settings` `pis` on(((`pis`.`period_id` = `pr`.`period_id`) and (`pis`.`recurrence_index` = 0)))) where (isnull(`pr`.`recurrence_type`) and (`pr`.`datetime_from` <= `pr`.`datetime_part_period_start`) and (isnull(`pr`.`datetime_to`) or (`pr`.`datetime_to` >= `pr`.`datetime_part_period_end`))) union all select `pr`.`period_id` AS `period_id`,`sq`.`num` AS `recurrence_index`,(`pr`.`datetime_part_period_start` + interval `sq`.`num` year) AS `datetime_period_item_start`,(`pr`.`datetime_part_period_end` + interval `sq`.`num` year) AS `datetime_period_item_end`,ifnull(`pis`.`blocked`,0) AS `blocked` from ((`{$prefix}cb2_view_sequence_date` `sq` join `{$prefix}cb2_periods` `pr`) left join `{$prefix}cb2_perioditem_settings` `pis` on(((`pis`.`period_id` = `pr`.`period_id`) and (`pis`.`recurrence_index` = `sq`.`num`)))) where ((`pr`.`recurrence_type` = 'Y') and ((year(`pr`.`datetime_part_period_end`) + `sq`.`num`) < 9999) and (`pr`.`datetime_from` <= (`pr`.`datetime_part_period_start` + interval `sq`.`num` year)) and (isnull(`pr`.`datetime_to`) or (`pr`.`datetime_to` >= (`pr`.`datetime_part_period_end` + interval `sq`.`num` year)))) union all select `pr`.`period_id` AS `period_id`,`sq`.`num` AS `recurrence_index`,(`pr`.`datetime_part_period_start` + interval `sq`.`num` month) AS `datetime_period_item_start`,(`pr`.`datetime_part_period_end` + interval `sq`.`num` month) AS `datetime_period_item_end`,ifnull(`pis`.`blocked`,0) AS `blocked` from ((`{$prefix}cb2_view_sequence_date` `sq` join `{$prefix}cb2_periods` `pr`) left join `{$prefix}cb2_perioditem_settings` `pis` on(((`pis`.`period_id` = `pr`.`period_id`) and (`pis`.`recurrence_index` = `sq`.`num`)))) where ((`pr`.`recurrence_type` = 'M') and ((year(`pr`.`datetime_part_period_end`) + (`sq`.`num` / 12)) < 9999) and ((`pr`.`recurrence_sequence` = 0) or (`pr`.`recurrence_sequence` & (pow(2,month((`pr`.`datetime_part_period_start` + interval `sq`.`num` month))) - 1))) and (`pr`.`datetime_from` <= (`pr`.`datetime_part_period_start` + interval `sq`.`num` month)) and (isnull(`pr`.`datetime_to`) or (`pr`.`datetime_to` >= (`pr`.`datetime_part_period_end` + interval `sq`.`num` month)))) union all select `pr`.`period_id` AS `period_id`,`sq`.`num` AS `recurrence_index`,(`pr`.`datetime_part_period_start` + interval `sq`.`num` week) AS `datetime_period_item_start`,(`pr`.`datetime_part_period_end` + interval `sq`.`num` week) AS `datetime_period_item_end`,ifnull(`pis`.`blocked`,0) AS `blocked` from ((`{$prefix}cb2_view_sequence_date` `sq` join `{$prefix}cb2_periods` `pr`) left join `{$prefix}cb2_perioditem_settings` `pis` on(((`pis`.`period_id` = `pr`.`period_id`) and (`pis`.`recurrence_index` = `sq`.`num`)))) where ((`pr`.`recurrence_type` = 'W') and ((year(`pr`.`datetime_part_period_end`) + (`sq`.`num` / 52)) < 9999) and (`pr`.`datetime_from` <= (`pr`.`datetime_part_period_start` + interval `sq`.`num` week)) and (isnull(`pr`.`datetime_to`) or (`pr`.`datetime_to` >= (`pr`.`datetime_part_period_end` + interval `sq`.`num` week)))) union all select `pr`.`period_id` AS `period_id`,`sq`.`num` AS `recurrence_index`,(addtime(cast(cast(`pr`.`datetime_from` as date) as datetime),cast(`pr`.`datetime_part_period_start` as time)) + interval `sq`.`num` day) AS `datetime_period_item_start`,(addtime(cast(cast(`pr`.`datetime_from` as date) as datetime),cast(`pr`.`datetime_part_period_end` as time)) + interval `sq`.`num` day) AS `datetime_period_item_end`,ifnull(`pis`.`blocked`,0) AS `blocked` from (((`{$prefix}cb2_view_sequence_date` `sq` join `{$prefix}cb2_periods` `pr`) left join `{$prefix}cb2_perioditem_settings` `pis` on(((`pis`.`period_id` = `pr`.`period_id`) and (`pis`.`recurrence_index` = `sq`.`num`)))) left join `{$prefix}options` `o` on((`o`.`option_name` = 'start_of_week'))) where ((`pr`.`recurrence_type` = 'D') and ((year(`pr`.`datetime_part_period_end`) + (`sq`.`num` / 356)) < 9999) and ((`pr`.`recurrence_sequence` = 0) or (`pr`.`recurrence_sequence` & pow(2,((((dayofweek((`pr`.`datetime_from` + interval `sq`.`num` day)) - 1) - cast(ifnull(`o`.`option_value`,'0') as signed)) + 7) % 7)))) and (`pr`.`datetime_from` <= (`pr`.`datetime_part_period_start` + interval `sq`.`num` day)) and (isnull(`pr`.`datetime_to`) or (`pr`.`datetime_to` >= (`pr`.`datetime_part_period_end` + interval `sq`.`num` day))))",
			'cb2_view_perioditem_posts'    => "select (((`p`.`period_id` * `pt_pi`.`ID_multiplier`) + `po`.`recurrence_index`) + `pt_pi`.`ID_base`) AS `ID`,1 AS `post_author`,`po`.`datetime_period_item_start` AS `post_date`,`po`.`datetime_period_item_start` AS `post_date_gmt`,'' AS `post_content`,`pst`.`name` AS `post_title`,'' AS `post_excerpt`,if(`ip`.`enabled`,'publish','trash') AS `post_status`,'closed' AS `comment_status`,'closed' AS `ping_status`,'' AS `post_password`,(((`p`.`period_id` * `pt_pi`.`ID_multiplier`) + `po`.`recurrence_index`) + `pt_pi`.`ID_base`) AS `post_name`,'' AS `to_ping`,'' AS `pinged`,`po`.`datetime_period_item_end` AS `post_modified`,`po`.`datetime_period_item_end` AS `post_modified_gmt`,'' AS `post_content_filtered`,((`po`.`period_id` * `pt_p`.`ID_multiplier`) + `pt_p`.`ID_base`) AS `post_parent`,'' AS `guid`,0 AS `menu_order`,`pt_pi`.`post_type` AS `post_type`,'' AS `post_mime_type`,0 AS `comment_count`,`ip`.`global_period_group_id` AS `timeframe_id`,`pt_e`.`post_type_id` AS `post_type_id`,((`pgp`.`period_group_id` * `pt_pg`.`ID_multiplier`) + `pt_pg`.`ID_base`) AS `period_group_ID`,((`po`.`period_id` * `pt_p`.`ID_multiplier`) + `pt_p`.`ID_base`) AS `period_ID`,((`ip`.`global_period_group_id` * `pt_e`.`ID_multiplier`) + `pt_e`.`ID_base`) AS `period_entity_ID`,((`pst`.`period_status_type_id` * `pt_pst`.`ID_multiplier`) + `pt_pst`.`ID_base`) AS `period_status_type_ID`,`po`.`period_id` AS `period_native_id`,`po`.`recurrence_index` AS `recurrence_index`,0 AS `location_ID`,0 AS `item_ID`,0 AS `user_ID`,`pst`.`period_status_type_id` AS `period_status_type_native_id`,`pst`.`name` AS `period_status_type_name`,`po`.`datetime_period_item_start` AS `datetime_period_item_start`,`po`.`datetime_period_item_end` AS `datetime_period_item_end`,cast(`po`.`blocked` as unsigned) AS `blocked`,cast(`ip`.`enabled` as unsigned) AS `enabled` from (((((((((`{$prefix}cb2_cache_perioditems` `po` join `{$prefix}cb2_periods` `p` on((`po`.`period_id` = `p`.`period_id`))) join `{$prefix}cb2_period_group_period` `pgp` on((`pgp`.`period_id` = `p`.`period_id`))) join `{$prefix}cb2_global_period_groups` `ip` on((`ip`.`period_group_id` = `pgp`.`period_group_id`))) join `{$prefix}cb2_period_status_types` `pst` on((`ip`.`period_status_type_id` = `pst`.`period_status_type_id`))) join `{$prefix}cb2_post_types` `pt_pi`) join `{$prefix}cb2_post_types` `pt_p`) join `{$prefix}cb2_post_types` `pt_pg`) join `{$prefix}cb2_post_types` `pt_e`) join `{$prefix}cb2_post_types` `pt_pst`) where ((4 = `pt_pi`.`post_type_id`) and (1 = `pt_p`.`post_type_id`) and (2 = `pt_pg`.`post_type_id`) and (12 = `pt_e`.`post_type_id`) and (8 = `pt_pst`.`post_type_id`) and (isnull(`ip`.`entity_datetime_from`) or (`po`.`datetime_period_item_start` >= `ip`.`entity_datetime_from`)) and (isnull(`ip`.`entity_datetime_to`) or (`po`.`datetime_period_item_end` <= `ip`.`entity_datetime_to`))) union all select (((`p`.`period_id` * `pt_pi`.`ID_multiplier`) + `po`.`recurrence_index`) + `pt_pi`.`ID_base`) AS `ID`,1 AS `post_author`,`po`.`datetime_period_item_start` AS `post_date`,`po`.`datetime_period_item_start` AS `post_date_gmt`,'' AS `post_content`,`pst`.`name` AS `post_title`,'' AS `post_excerpt`,if(`ip`.`enabled`,'publish','trash') AS `post_status`,'closed' AS `comment_status`,'closed' AS `ping_status`,'' AS `post_password`,(((`p`.`period_id` * `pt_pi`.`ID_multiplier`) + `po`.`recurrence_index`) + `pt_pi`.`ID_base`) AS `post_name`,'' AS `to_ping`,'' AS `pinged`,`po`.`datetime_period_item_end` AS `post_modified`,`po`.`datetime_period_item_end` AS `post_modified_gmt`,'' AS `post_content_filtered`,((`po`.`period_id` * `pt_p`.`ID_multiplier`) + `pt_p`.`ID_base`) AS `post_parent`,'' AS `guid`,0 AS `menu_order`,`pt_pi`.`post_type` AS `post_type`,'' AS `post_mime_type`,0 AS `comment_count`,`ip`.`location_period_group_id` AS `timeframe_id`,`pt_e`.`post_type_id` AS `post_type_id`,((`pgp`.`period_group_id` * `pt_pg`.`ID_multiplier`) + `pt_pg`.`ID_base`) AS `period_group_ID`,((`po`.`period_id` * `pt_p`.`ID_multiplier`) + `pt_p`.`ID_base`) AS `period_ID`,((`ip`.`location_period_group_id` * `pt_e`.`ID_multiplier`) + `pt_e`.`ID_base`) AS `period_entity_ID`,((`pst`.`period_status_type_id` * `pt_pst`.`ID_multiplier`) + `pt_pst`.`ID_base`) AS `period_status_type_ID`,`po`.`period_id` AS `period_native_id`,`po`.`recurrence_index` AS `recurrence_index`,`ip`.`location_ID` AS `location_ID`,0 AS `item_ID`,0 AS `user_ID`,`pst`.`period_status_type_id` AS `period_status_type_native_id`,`pst`.`name` AS `period_status_type_name`,`po`.`datetime_period_item_start` AS `datetime_period_item_start`,`po`.`datetime_period_item_end` AS `datetime_period_item_end`,cast(`po`.`blocked` as unsigned) AS `blocked`,cast(`ip`.`enabled` as unsigned) AS `enabled` from ((((((((((`{$prefix}cb2_cache_perioditems` `po` join `{$prefix}cb2_periods` `p` on((`po`.`period_id` = `p`.`period_id`))) join `{$prefix}cb2_period_group_period` `pgp` on((`pgp`.`period_id` = `p`.`period_id`))) join `{$prefix}cb2_location_period_groups` `ip` on((`ip`.`period_group_id` = `pgp`.`period_group_id`))) join `{$prefix}posts` `loc` on((`ip`.`location_ID` = `loc`.`ID`))) join `{$prefix}cb2_period_status_types` `pst` on((`ip`.`period_status_type_id` = `pst`.`period_status_type_id`))) join `{$prefix}cb2_post_types` `pt_pi`) join `{$prefix}cb2_post_types` `pt_p`) join `{$prefix}cb2_post_types` `pt_pg`) join `{$prefix}cb2_post_types` `pt_e`) join `{$prefix}cb2_post_types` `pt_pst`) where ((5 = `pt_pi`.`post_type_id`) and (1 = `pt_p`.`post_type_id`) and (2 = `pt_pg`.`post_type_id`) and (13 = `pt_e`.`post_type_id`) and (8 = `pt_pst`.`post_type_id`) and (isnull(`ip`.`entity_datetime_from`) or (`po`.`datetime_period_item_start` >= `ip`.`entity_datetime_from`)) and (isnull(`ip`.`entity_datetime_to`) or (`po`.`datetime_period_item_end` <= `ip`.`entity_datetime_to`))) union all select (((`p`.`period_id` * `pt_pi`.`ID_multiplier`) + `po`.`recurrence_index`) + `pt_pi`.`ID_base`) AS `ID`,1 AS `post_author`,`po`.`datetime_period_item_start` AS `post_date`,`po`.`datetime_period_item_start` AS `post_date_gmt`,'' AS `post_content`,`pst`.`name` AS `post_title`,'' AS `post_excerpt`,if(`ip`.`enabled`,'publish','trash') AS `post_status`,'closed' AS `comment_status`,'closed' AS `ping_status`,'' AS `post_password`,(((`p`.`period_id` * `pt_pi`.`ID_multiplier`) + `po`.`recurrence_index`) + `pt_pi`.`ID_base`) AS `post_name`,'' AS `to_ping`,'' AS `pinged`,`po`.`datetime_period_item_end` AS `post_modified`,`po`.`datetime_period_item_end` AS `post_modified_gmt`,'' AS `post_content_filtered`,((`po`.`period_id` * `pt_p`.`ID_multiplier`) + `pt_p`.`ID_base`) AS `post_parent`,'' AS `guid`,0 AS `menu_order`,`pt_pi`.`post_type` AS `post_type`,'' AS `post_mime_type`,0 AS `comment_count`,`ip`.`timeframe_period_group_id` AS `timeframe_id`,`pt_e`.`post_type_id` AS `post_type_id`,((`pgp`.`period_group_id` * `pt_pg`.`ID_multiplier`) + `pt_pg`.`ID_base`) AS `period_group_ID`,((`po`.`period_id` * `pt_p`.`ID_multiplier`) + `pt_p`.`ID_base`) AS `period_ID`,((`ip`.`timeframe_period_group_id` * `pt_e`.`ID_multiplier`) + `pt_e`.`ID_base`) AS `period_entity_ID`,((`pst`.`period_status_type_id` * `pt_pst`.`ID_multiplier`) + `pt_pst`.`ID_base`) AS `period_status_type_ID`,`po`.`period_id` AS `period_native_id`,`po`.`recurrence_index` AS `recurrence_index`,`ip`.`location_ID` AS `location_ID`,`ip`.`item_ID` AS `item_ID`,0 AS `user_ID`,`pst`.`period_status_type_id` AS `period_status_type_native_id`,`pst`.`name` AS `period_status_type_name`,`po`.`datetime_period_item_start` AS `datetime_period_item_start`,`po`.`datetime_period_item_end` AS `datetime_period_item_end`,cast(`po`.`blocked` as unsigned) AS `blocked`,cast(`ip`.`enabled` as unsigned) AS `enabled` from (((((((((((`{$prefix}cb2_cache_perioditems` `po` join `{$prefix}cb2_periods` `p` on((`po`.`period_id` = `p`.`period_id`))) join `{$prefix}cb2_period_group_period` `pgp` on((`pgp`.`period_id` = `p`.`period_id`))) join `{$prefix}cb2_timeframe_period_groups` `ip` on((`ip`.`period_group_id` = `pgp`.`period_group_id`))) join `{$prefix}posts` `loc` on((`ip`.`location_ID` = `loc`.`ID`))) join `{$prefix}posts` `itm` on((`ip`.`item_ID` = `itm`.`ID`))) join `{$prefix}cb2_period_status_types` `pst` on((`ip`.`period_status_type_id` = `pst`.`period_status_type_id`))) join `{$prefix}cb2_post_types` `pt_pi`) join `{$prefix}cb2_post_types` `pt_p`) join `{$prefix}cb2_post_types` `pt_pg`) join `{$prefix}cb2_post_types` `pt_e`) join `{$prefix}cb2_post_types` `pt_pst`) where ((6 = `pt_pi`.`post_type_id`) and (1 = `pt_p`.`post_type_id`) and (2 = `pt_pg`.`post_type_id`) and (14 = `pt_e`.`post_type_id`) and (8 = `pt_pst`.`post_type_id`) and (isnull(`ip`.`entity_datetime_from`) or (`po`.`datetime_period_item_start` >= `ip`.`entity_datetime_from`)) and (isnull(`ip`.`entity_datetime_to`) or (`po`.`datetime_period_item_end` <= `ip`.`entity_datetime_to`))) union all select (((`p`.`period_id` * `pt_pi`.`ID_multiplier`) + `po`.`recurrence_index`) + `pt_pi`.`ID_base`) AS `ID`,1 AS `post_author`,`po`.`datetime_period_item_start` AS `post_date`,`po`.`datetime_period_item_start` AS `post_date_gmt`,'' AS `post_content`,`pst`.`name` AS `post_title`,'' AS `post_excerpt`,if(`ip`.`enabled`,'publish','trash') AS `post_status`,'closed' AS `comment_status`,'closed' AS `ping_status`,'' AS `post_password`,(((`p`.`period_id` * `pt_pi`.`ID_multiplier`) + `po`.`recurrence_index`) + `pt_pi`.`ID_base`) AS `post_name`,'' AS `to_ping`,'' AS `pinged`,`po`.`datetime_period_item_end` AS `post_modified`,`po`.`datetime_period_item_end` AS `post_modified_gmt`,'' AS `post_content_filtered`,((`po`.`period_id` * `pt_p`.`ID_multiplier`) + `pt_p`.`ID_base`) AS `post_parent`,'' AS `guid`,0 AS `menu_order`,`pt_pi`.`post_type` AS `post_type`,'' AS `post_mime_type`,0 AS `comment_count`,`ip`.`timeframe_user_period_group_id` AS `timeframe_id`,`pt_e`.`post_type_id` AS `post_type_id`,((`pgp`.`period_group_id` * `pt_pg`.`ID_multiplier`) + `pt_pg`.`ID_base`) AS `period_group_ID`,((`po`.`period_id` * `pt_p`.`ID_multiplier`) + `pt_p`.`ID_base`) AS `period_ID`,((`ip`.`timeframe_user_period_group_id` * `pt_e`.`ID_multiplier`) + `pt_e`.`ID_base`) AS `period_entity_ID`,((`pst`.`period_status_type_id` * `pt_pst`.`ID_multiplier`) + `pt_pst`.`ID_base`) AS `period_status_type_ID`,`po`.`period_id` AS `period_native_id`,`po`.`recurrence_index` AS `recurrence_index`,`ip`.`location_ID` AS `location_ID`,`ip`.`item_ID` AS `item_ID`,`ip`.`user_ID` AS `user_ID`,`pst`.`period_status_type_id` AS `period_status_type_native_id`,`pst`.`name` AS `period_status_type_name`,`po`.`datetime_period_item_start` AS `datetime_period_item_start`,`po`.`datetime_period_item_end` AS `datetime_period_item_end`,cast(`po`.`blocked` as unsigned) AS `blocked`,cast(`ip`.`enabled` as unsigned) AS `enabled` from ((((((((((((`{$prefix}cb2_cache_perioditems` `po` join `{$prefix}cb2_periods` `p` on((`po`.`period_id` = `p`.`period_id`))) join `{$prefix}cb2_period_group_period` `pgp` on((`pgp`.`period_id` = `p`.`period_id`))) join `{$prefix}cb2_timeframe_user_period_groups` `ip` on((`ip`.`period_group_id` = `pgp`.`period_group_id`))) join `{$prefix}posts` `loc` on((`ip`.`location_ID` = `loc`.`ID`))) join `{$prefix}posts` `itm` on((`ip`.`item_ID` = `itm`.`ID`))) join `{$prefix}users` `usr` on((`ip`.`user_ID` = `usr`.`ID`))) join `{$prefix}cb2_period_status_types` `pst` on((`ip`.`period_status_type_id` = `pst`.`period_status_type_id`))) join `{$prefix}cb2_post_types` `pt_pi`) join `{$prefix}cb2_post_types` `pt_p`) join `{$prefix}cb2_post_types` `pt_pg`) join `{$prefix}cb2_post_types` `pt_e`) join `{$prefix}cb2_post_types` `pt_pst`) where ((7 = `pt_pi`.`post_type_id`) and (1 = `pt_p`.`post_type_id`) and (2 = `pt_pg`.`post_type_id`) and (15 = `pt_e`.`post_type_id`) and (8 = `pt_pst`.`post_type_id`) and (isnull(`ip`.`entity_datetime_from`) or (`po`.`datetime_period_item_start` >= `ip`.`entity_datetime_from`)) and (isnull(`ip`.`entity_datetime_to`) or (`po`.`datetime_period_item_end` <= `ip`.`entity_datetime_to`)))",
			'cb2_view_perioditemmeta'      => "select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * `pt`.`ID_multiplier`) + `cal`.`recurrence_index`) * 20) + `pt`.`ID_base`) + 1) AS `meta_id`,`cal`.`ID` AS `post_id`,`cal`.`ID` AS `perioditem_id`,'location_ID' AS `meta_key`,`cal`.`location_ID` AS `meta_value` from (`{$prefix}cb2_view_perioditem_posts` `cal` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type_id` = `cal`.`post_type_id`))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * `pt`.`ID_multiplier`) + `cal`.`recurrence_index`) * 20) + `pt`.`ID_base`) + 2) AS `meta_id`,`cal`.`ID` AS `post_id`,`cal`.`ID` AS `perioditem_id`,'item_ID' AS `meta_key`,`cal`.`item_ID` AS `meta_value` from (`{$prefix}cb2_view_perioditem_posts` `cal` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type_id` = `cal`.`post_type_id`))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * `pt`.`ID_multiplier`) + `cal`.`recurrence_index`) * 20) + `pt`.`ID_base`) + 3) AS `meta_id`,`cal`.`ID` AS `post_id`,`cal`.`ID` AS `perioditem_id`,'user_ID' AS `meta_key`,`cal`.`user_ID` AS `meta_value` from (`{$prefix}cb2_view_perioditem_posts` `cal` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type_id` = `cal`.`post_type_id`))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * `pt`.`ID_multiplier`) + `cal`.`recurrence_index`) * 20) + `pt`.`ID_base`) + 4) AS `meta_id`,`cal`.`ID` AS `post_id`,`cal`.`ID` AS `perioditem_id`,'period_group_ID' AS `meta_key`,`cal`.`period_group_ID` AS `meta_value` from (`{$prefix}cb2_view_perioditem_posts` `cal` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type_id` = `cal`.`post_type_id`))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * `pt`.`ID_multiplier`) + `cal`.`recurrence_index`) * 20) + `pt`.`ID_base`) + 5) AS `meta_id`,`cal`.`ID` AS `post_id`,`cal`.`ID` AS `perioditem_id`,'period_ID' AS `meta_key`,`cal`.`period_ID` AS `meta_value` from (`{$prefix}cb2_view_perioditem_posts` `cal` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type_id` = `cal`.`post_type_id`))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * `pt`.`ID_multiplier`) + `cal`.`recurrence_index`) * 20) + `pt`.`ID_base`) + 6) AS `meta_id`,`cal`.`ID` AS `post_id`,`cal`.`ID` AS `perioditem_id`,'period_status_type_ID' AS `meta_key`,`cal`.`period_status_type_ID` AS `meta_value` from (`{$prefix}cb2_view_perioditem_posts` `cal` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type_id` = `cal`.`post_type_id`))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * `pt`.`ID_multiplier`) + `cal`.`recurrence_index`) * 20) + `pt`.`ID_base`) + 7) AS `meta_id`,`cal`.`ID` AS `post_id`,`cal`.`ID` AS `perioditem_id`,'recurrence_index' AS `meta_key`,`cal`.`recurrence_index` AS `meta_value` from (`{$prefix}cb2_view_perioditem_posts` `cal` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type_id` = `cal`.`post_type_id`))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * `pt`.`ID_multiplier`) + `cal`.`recurrence_index`) * 20) + `pt`.`ID_base`) + 9) AS `meta_id`,`cal`.`ID` AS `post_id`,`cal`.`ID` AS `perioditem_id`,'period_status_type_name' AS `meta_key`,`cal`.`period_status_type_name` AS `meta_value` from (`{$prefix}cb2_view_perioditem_posts` `cal` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type_id` = `cal`.`post_type_id`))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * `pt`.`ID_multiplier`) + `cal`.`recurrence_index`) * 20) + `pt`.`ID_base`) + 10) AS `meta_id`,`cal`.`ID` AS `post_id`,`cal`.`ID` AS `perioditem_id`,'period_entity_ID' AS `meta_key`,`cal`.`period_entity_ID` AS `meta_value` from (`{$prefix}cb2_view_perioditem_posts` `cal` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type_id` = `cal`.`post_type_id`))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * `pt`.`ID_multiplier`) + `cal`.`recurrence_index`) * 20) + `pt`.`ID_base`) + 11) AS `meta_id`,`cal`.`ID` AS `post_id`,`cal`.`ID` AS `perioditem_id`,'blocked' AS `meta_key`,`cal`.`blocked` AS `meta_value` from (`{$prefix}cb2_view_perioditem_posts` `cal` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type_id` = `cal`.`post_type_id`))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * `pt`.`ID_multiplier`) + `cal`.`recurrence_index`) * 20) + `pt`.`ID_base`) + 12) AS `meta_id`,`cal`.`ID` AS `post_id`,`cal`.`ID` AS `perioditem_id`,'enabled' AS `meta_key`,`cal`.`enabled` AS `meta_value` from (`{$prefix}cb2_view_perioditem_posts` `cal` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type_id` = `cal`.`post_type_id`)))",
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
						'html'    => 'If blocked, this instance will not appear in the calendars.',
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

		return $metaboxes;
	}

	protected function __construct(
		$ID,
		$period_entity,
    $period,
    $recurrence_index,
    $datetime_period_item_start,
    $datetime_period_item_end,
    $blocked
  ) {
		CB2_Query::assign_all_parameters( $this, func_get_args(), __class__ );

		// Some sanity checks
		if ( $this->datetime_period_item_start->after( $this->datetime_period_item_end ) )
			throw new Exception( 'datetime_period_item_start > datetime_period_item_end' );

		// Add the period to all the days it appears in
		// CB2_Day::factory() will lazy create singleton CB2_Day's
		$this->days = array();
		if ( $this->datetime_period_item_start ) {
			$date = clone $this->datetime_period_item_start;
			do {
				$day = CB2_Day::factory( clone $date );
				$day->add_perioditem( $this );
				array_push( $this->days, $day );
				$date->add( 1 );
			} while ( $date->before( $this->datetime_period_item_end ) );

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

    $key = "$ID";
    if ( $key ) self::$all[$key] = $this;
  }

  function is( CB2_PostNavigator $perioditem ) {
		return is_a( $perioditem, get_class( $this ) )
			&& property_exists( $perioditem, 'period_entity' )
			&& $perioditem->period_entity->is( $this->period_entity )
			&& $perioditem->recurrence_index == $this->recurrence_index;
  }

  function remove() {
		foreach ( $this->days as $day )
			$day->remove_post( $this );

		if ( $this->ID ) unset( self::$all[$this->ID] );
  }

  function is_blocked() {
		return $this->blocked;
  }

  function block( $block = TRUE ) {
		global $wpdb;

		if ( $block ) {
			$full_table = "{$wpdb->prefix}cb2_perioditem_settings";
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
			"UPDATE {$wpdb->prefix}cb2_perioditem_settings
				SET blocked = b'0'
				WHERE period_id = %d and recurrence_index = %d",
			array(
				$this->period->id(),
				$this->recurrence_index,
			)
		) );
  }

  function overlaps( $period ) {
		return $this->overlaps_time( $period );
  }

  function overlaps_time( $period ) {
		return ( $this->datetime_period_item_start->moreThanOrEqual( $period->datetime_period_item_start )
			    && $this->datetime_period_item_start->lessThanOrEqual( $period->datetime_period_item_end ) )
			||   ( $this->datetime_period_item_end->moreThanOrEqual( $period->datetime_period_item_start )
			    && $this->datetime_period_item_end->lessThanOrEqual( $period->datetime_period_item_end ) );
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
			"cb2-tab-instance"   => 'Instance',
			"cb2-tab-definition" => 'Definition',
			"cb2-tab-security"   => 'Security',
		);
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

  function templates_considered( $context = 'list', $type = NULL, &$templates = NULL ) {
		// Priority order
		$templates = $this->period_entity->templates_considered( $context, $type, $templates );
		return parent::templates_considered( $context, $type, $templates );
	}

  function seconds_in_day( $datetime ) {
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

    $seconds_start = $this->seconds_in_day( $this->datetime_period_item_start );
    $seconds_end   = $this->seconds_in_day( $this->datetime_period_item_end );
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
    $classes  = '';
    if ( $this->period_entity ) $classes .= $this->period_entity->period_status_type->classes();
    $classes .= ' cb2-period-group-type-' . $this->post_type();
    $classes .= ( $this->is_top_priority() ? ' cb2-perioditem-no-overlap' : ' cb2-perioditem-has-overlap' );
    if ( $this->blocked ) $classes .= ' cb2-blocked';
    return $classes;
  }

  function is_top_priority() {
  	return ! $this->top_priority_overlap_period && ! is_null( $this->priority() );
  }

  function styles() {
    $styles = '';

    $day_percent_position = $this->day_percent_position();
    $styles .= "top:$day_percent_position[start_percent]%;";
    $styles .= "height:$day_percent_position[diff_percent]%;";

    $styles .= $this->period_entity->period_status_type->styles();

    return $styles;
  }

  function row_actions( &$actions, $post ) {
		$period_ID = $this->period->ID;
		$actions[ 'edit-definition' ] = "<a href='admin.php?page=cb2-post-edit&post=$period_ID&post_type=period&action=edit'>Edit definition</a>";
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
		$time_period = $this->datetime_period_item_start->format( $format )
			. $separator
			. $this->datetime_period_item_end->format( $format );
		if ( $human_readable ) {
			if ( $this->period->fullday ) $time_period = 'all day';
		}
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

  function get_the_title( $HTML = FALSE ) {
		$title  = '';
		if ( $HTML ) $title .= "<span class='cb2-time-period'>";
		$title .= $this->get_the_time_period() . ' ';
		if ( $HTML ) $title .= "</span>";
		$title .= $this->period_entity->get_the_title( $HTML, $this );
		return $title;
  }

  function jsonSerialize() {
    return array(
      'period_ID' => $this->period->ID,
      'recurrence_index' => $this->recurrence_index,
      'name' => $this->post_title,
      'datetime_period_item_start' => $this->datetime_period_item_start->format( CB2_Query::$json_date_format ),
      'datetime_period_item_end' => $this->datetime_period_item_start->format( CB2_Query::$json_date_format ),
      'datetime_from' => $this->period->datetime_from->format( CB2_Query::$json_date_format ),
      'datetime_to' => ( $this->period->datetime_to ? $this->datetime_to->format( CB2_Query::$json_date_format ) : '' ),
      'period_status_type' => $this->period_entity->period_status_type,
      'recurrence_type' => $this->period->recurrence_type,
      'recurrence_frequency' => $this->period->recurrence_frequency,
      'recurrence_sequence' => $this->period->recurrence_sequence,
      'day_percent_position' => $this->day_percent_position(),
      'classes' => $this->classes(),
      'styles' => $this->styles(),
      'indicators' => $this->indicators(),
      'fullday' => $this->period->fullday,
    );
  }
}


// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB2_PeriodItem_Global extends CB2_PeriodItem {
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
		$datetime_period_item_end,
    $blocked
  ) {
    parent::__construct(
			$ID,
			$period_entity,
			$period,
			$recurrence_index,
			$datetime_period_item_start,
			$datetime_period_item_end,
			$blocked
    );
  }

  static function &factory_from_properties( &$properties, &$instance_container = NULL, $force_properties = FALSE ) {
		$object = self::factory(
			$properties['ID'],
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_entity_ID', $instance_container, 'CB2_PeriodEntity_Global' ),
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_ID',        $instance_container ),
			$properties['recurrence_index'],
			$properties['datetime_period_item_start'],
			$properties['datetime_period_item_end'],
			$properties['blocked']
		);

		self::copy_all_wp_post_properties( $properties, $object );

		return $object;
  }

  static function &factory(
		$ID,
		$period_entity,
    $period,     // CB2_Period
    $recurrence_index,
    $datetime_period_item_start,
    $datetime_period_item_end,
    $blocked
  ) {
    // Design Patterns: Factory Singleton with Multiton
    $key = "$ID";
		if ( $key && $key != CB2_CREATE_NEW && isset( self::$all[$key] ) ) {
			$object = self::$all[$key];
    } else {
			$reflection = new ReflectionClass( __class__ );
			$object     = $reflection->newInstanceArgs( func_get_args() );
    }

    return $object;
  }

 	static function metaboxes() {
		return CB2_PeriodItem::metaboxes();
	}
}


// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB2_PeriodItem_Location extends CB2_PeriodItem {
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
		$datetime_period_item_end,
    $blocked
	) {
    parent::__construct(
			$ID,
			$period_entity,
			$period,
			$recurrence_index,
			$datetime_period_item_start,
			$datetime_period_item_end,
			$blocked
    );
    $this->period_entity->location->add_perioditem( $this );
    array_push( $this->posts, $this->period_entity->location );
  }

  static function &factory_from_properties( &$properties, &$instance_container = NULL, $force_properties = FALSE ) {
		$object = self::factory(
			$properties['ID'],
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_entity_ID', $instance_container, 'CB2_PeriodEntity_Location' ),
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_ID',        $instance_container ),
			$properties['recurrence_index'],
			$properties['datetime_period_item_start'],
			$properties['datetime_period_item_end'],
			$properties['blocked']
		);

		self::copy_all_wp_post_properties( $properties, $object );

		return $object;
  }

  static function &factory(
		$ID,
		$period_entity,
    $period,     // CB2_Period
    $recurrence_index,
    $datetime_period_item_start,
    $datetime_period_item_end,
    $blocked
  ) {
    // Design Patterns: Factory Singleton with Multiton
    $key = "$ID";
		if ( $key && $key != CB2_CREATE_NEW && isset( self::$all[$key] ) ) {
			$object = self::$all[$key];
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

  function jsonSerialize() {
    $array = parent::jsonSerialize();
    //$array[ 'location' ] = &$this->location;
    return $array;
  }

 	static function metaboxes() {
		return CB2_PeriodItem::metaboxes();
	}
}


// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB2_PeriodItem_Timeframe extends CB2_PeriodItem {
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

	static public $static_post_type = 'perioditem-timeframe';

  function post_type() {return self::$static_post_type;}

  function __construct(
		$ID,
		$period_entity,
    $period,
		$recurrence_index,
		$datetime_period_item_start,
		$datetime_period_item_end,
    $blocked
  ) {
    parent::__construct(
			$ID,
			$period_entity,
			$period,
			$recurrence_index,
			$datetime_period_item_start,
			$datetime_period_item_end,
			$blocked
    );
    array_push( $this->posts, $this->period_entity->location );
    array_push( $this->posts, $this->period_entity->item );
    $this->period_entity->item->add_perioditem( $this );
  }

  static function &factory_from_properties( &$properties, &$instance_container = NULL, $force_properties = FALSE ) {
		$object = self::factory(
			$properties['ID'],
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_entity_ID', $instance_container, 'CB2_PeriodEntity_Timeframe' ),
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_ID',        $instance_container ),
			$properties['recurrence_index'],
			$properties['datetime_period_item_start'],
			$properties['datetime_period_item_end'],
			$properties['blocked']
		);

		self::copy_all_wp_post_properties( $properties, $object );

		return $object;
  }

  static function &factory(
		$ID,
		$period_entity, // CB2_PeriodEntity
    $period,        // CB2_Period
    $recurrence_index,
    $datetime_period_item_start,
    $datetime_period_item_end,
    $blocked
  ) {
    // Design Patterns: Factory Singleton with Multiton
    // $ID = period_ID * x + recurrence_index
    // TODO: if 2 different period_entities share the same period, then it will not __construct() twice
    $key = "$ID";
		if ( $key && $key != CB2_CREATE_NEW && isset( self::$all[$key] ) ) {
			$object = self::$all[$key];
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
		return CB2_PeriodItem::metaboxes();
	}

	function get_api_data($version){
		return array(
      'status' => get_the_title($this),
      'start' => $this->datetime_period_item_start->format( CB2_Query::$json_date_format ),
			'end' => $this->datetime_period_item_start->format( CB2_Query::$json_date_format ),
			'location' => $this->period_entity->location->ID
		);
	}
}


// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB2_PeriodItem_Location_User extends CB2_PeriodItem {
  static $database_table = 'cb2_location_user_period_groups';
  public  static $post_type_args = array(
		'menu_icon' => 'dashicons-admin-page',
		'label'     => 'Item Timeframes',
  );

	static public $static_post_type = 'perioditem-staff';

  function post_type() {return self::$static_post_type;}

  function __construct(
		$ID,
		$period_entity,
    $period,
		$recurrence_index,
		$datetime_period_item_start,
		$datetime_period_item_end,
    $blocked
  ) {
    parent::__construct(
			$ID,
			$period_entity,
			$period,
			$recurrence_index,
			$datetime_period_item_start,
			$datetime_period_item_end,
			$blocked
    );
    array_push( $this->posts, $this->period_entity->location );
    array_push( $this->posts, $this->period_entity->user );
    $this->period_entity->item->add_perioditem( $this );
  }

  static function &factory_from_properties( &$properties, &$instance_container = NULL, $force_properties = FALSE ) {
		$object = self::factory(
			$properties['ID'],
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_entity_ID', $instance_container, 'CB2_PeriodEntity_Timeframe' ),
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_ID',        $instance_container ),
			$properties['recurrence_index'],
			$properties['datetime_period_item_start'],
			$properties['datetime_period_item_end'],
			$properties['blocked']
		);

		self::copy_all_wp_post_properties( $properties, $object );

		return $object;
  }

  static function &factory(
		$ID,
		$period_entity, // CB2_PeriodEntity
    $period,        // CB2_Period
    $recurrence_index,
    $datetime_period_item_start,
    $datetime_period_item_end,
    $blocked
  ) {
    // Design Patterns: Factory Singleton with Multiton
    // $ID = period_ID * x + recurrence_index
    // TODO: if 2 different period_entities share the same period, then it will not __construct() twice
    $key = "$ID";
		if ( $key && $key != CB2_CREATE_NEW && isset( self::$all[$key] ) ) {
			$object = self::$all[$key];
    } else {
			$reflection = new ReflectionClass( __class__ );
			$object     = $reflection->newInstanceArgs( func_get_args() );
    }

    return $object;
  }

  function jsonSerialize() {
    $array = parent::jsonSerialize();
    //$array[ 'location' ] = &$this->period_entity->location;
    //$array[ 'item' ]     = &$this->item;
    return $array;
  }

 	static function metaboxes() {
		return CB2_PeriodItem::metaboxes();
	}

	function get_api_data($version){
		return array(
      'status' => get_the_title($this),
      'start' => $this->datetime_period_item_start->format( CB2_Query::$json_date_format ),
			'end' => $this->datetime_period_item_start->format( CB2_Query::$json_date_format ),
			'location' => $this->period_entity->location->ID
		);
	}
}


// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB2_PeriodItem_Timeframe_User extends CB2_PeriodItem {
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
		$datetime_period_item_end,
    $blocked
  ) {
    parent::__construct(
			$ID,
			$period_entity,
			$period,
			$recurrence_index,
			$datetime_period_item_start,
			$datetime_period_item_end,
			$blocked
		);
    array_push( $this->posts, $this->period_entity->location );
    array_push( $this->posts, $this->period_entity->item );
    array_push( $this->posts, $this->period_entity->user );
    $this->period_entity->user->add_perioditem( $this );
  }

  static function &factory_from_properties( &$properties, &$instance_container = NULL, $force_properties = FALSE ) {
		$object = self::factory(
			$properties['ID'],
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_entity_ID', $instance_container, 'CB2_PeriodEntity_Timeframe_User' ),
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_ID',        $instance_container ),
			$properties['recurrence_index'],
			$properties['datetime_period_item_start'],
			$properties['datetime_period_item_end'],
			$properties['blocked']
		);

		self::copy_all_wp_post_properties( $properties, $object );

		return $object;
  }

  static function &factory(
		$ID,
		$period_entity, // CB2_PeriodEntity
    $period,        // CB2_Period
    $recurrence_index,
    $datetime_period_item_start,
    $datetime_period_item_end,
    $blocked
  ) {
    // Design Patterns: Factory Singleton with Multiton
    $key = "$ID";
		if ( $key && $key != CB2_CREATE_NEW && isset( self::$all[$key] ) ) {
			$object = self::$all[$key];
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

  function jsonSerialize() {
    $array = parent::jsonSerialize();
    //$array[ 'location' ] = &$this->location;
    //$array[ 'item' ]     = &$this->item;
    //$array[ 'user' ]     = &$this->user;
    return $array;
  }

 	static function metaboxes() {
		return CB2_PeriodItem::metaboxes();
	}
}
