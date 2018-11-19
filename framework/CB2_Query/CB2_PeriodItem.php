<?php
require_once( 'CB2_Period.php' );

abstract class CB2_PeriodItem extends CB2_PostNavigator implements JsonSerializable {
	public  static $database_table   = 'cb2_perioditem_settings';
	public  static $description      = 'CB2_PeriodItem uses <b>triggers</b> to update the primary postmeta table for performance.';
  public  static $all              = array();
  // public  static $postmeta_table   = FALSE;

	public static $all_post_types = array(
		'perioditem-global',
		'perioditem-location',
		'perioditem-timeframe',
		'perioditem-user',

		'perioditem-automatic', // post_status = auto-draft (last because fake)
	);
  private static $null_recurrence_index = 0;
  private $priority_overlap_periods     = array();
  private $top_priority_overlap_period  = NULL;

	static function database_table_name() { return self::$database_table; }

  static function database_table_schemas( $prefix ) {
		$period_cache_table   = "{$prefix}cb2_cache_perioditems";
		$refresh_cache        = "delete from $period_cache_table; insert into $period_cache_table(period_id, recurrence_index, datetime_period_item_start, datetime_period_item_end, blocked) select * from {$prefix}cb2_view_perioditems;";

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
				'author_ID' => array( 'users',    'ID' ),
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
		// Some lines are concatenated only to reduce line length for my kate editor :)
		$period_cache_table   = "{$prefix}cb2_cache_perioditems";

		return array(
			'cb2_view_sequence_num'        => "select 0 AS `num` union all select 1 AS `1` union all select 2 AS `2` union all select 3 AS `3` union all select 4 AS `4` union all select 5 AS `5` union all select 6 AS `6` union all select 7 AS `7` union all select 8 AS `8` union all select 9 AS `9`",
			'cb2_view_sequence_date'       => "select ((`t4`.`num` * 1000) + ((`t3`.`num` * 100) + ((`t2`.`num` * 10) + `t1`.`num`))) AS `num` from (((`wp_cb2_view_sequence_num` `t1` join `wp_cb2_view_sequence_num` `t2`) join `wp_cb2_view_sequence_num` `t3`) join `wp_cb2_view_sequence_num` `t4`) where (`t4`.`num` <= 3)",
			'cb2_view_perioditems'         => "select `pr`.`period_id` AS `period_id`,0 AS `recurrence_index`,`pr`.`datetime_part_period_start` AS `datetime_period_item_start`,`pr`.`datetime_part_period_end` AS `datetime_period_item_end`,ifnull(`pis`.`blocked`,0) AS `blocked` from (`wp_cb2_periods` `pr` left join `wp_cb2_perioditem_settings` `pis` on(((`pis`.`period_id` = `pr`.`period_id`) and (`pis`.`recurrence_index` = 0)))) where (isnull(`pr`.`recurrence_type`) and (`pr`.`datetime_from` <= `pr`.`datetime_part_period_start`) and (isnull(`pr`.`datetime_to`) or (`pr`.`datetime_to` >= `pr`.`datetime_part_period_end`))) union all select `pr`.`period_id` AS `period_id`,`sq`.`num` AS `recurrence_index`,(`pr`.`datetime_part_period_start` + interval `sq`.`num` year) AS `datetime_period_item_start`,(`pr`.`datetime_part_period_end` + interval `sq`.`num` year) AS `datetime_period_item_end`,ifnull(`pis`.`blocked`,0) AS `blocked` from ((`wp_cb2_view_sequence_date` `sq` join `wp_cb2_periods` `pr`) left join `wp_cb2_perioditem_settings` `pis` on(((`pis`.`period_id` = `pr`.`period_id`) and (`pis`.`recurrence_index` = `sq`.`num`)))) where ((`pr`.`recurrence_type` = 'Y') and ((year(`pr`.`datetime_part_period_end`) + `sq`.`num`) < 9999) and (`pr`.`datetime_from` <= (`pr`.`datetime_part_period_start` + interval `sq`.`num` year)) and (isnull(`pr`.`datetime_to`) or (`pr`.`datetime_to` >= (`pr`.`datetime_part_period_end` + interval `sq`.`num` year)))) union all select `pr`.`period_id` AS `period_id`,`sq`.`num` AS `recurrence_index`,(`pr`.`datetime_part_period_start` + interval `sq`.`num` month) AS `datetime_period_item_start`,(`pr`.`datetime_part_period_end` + interval `sq`.`num` month) AS `datetime_period_item_end`,ifnull(`pis`.`blocked`,0) AS `blocked` from ((`wp_cb2_view_sequence_date` `sq` join `wp_cb2_periods` `pr`) left join `wp_cb2_perioditem_settings` `pis` on(((`pis`.`period_id` = `pr`.`period_id`) and (`pis`.`recurrence_index` = `sq`.`num`)))) where ((`pr`.`recurrence_type` = 'M') and ((year(`pr`.`datetime_part_period_end`) + (`sq`.`num` / 12)) < 9999) and ((`pr`.`recurrence_sequence` = 0) or (`pr`.`recurrence_sequence` & (pow(2,month((`pr`.`datetime_part_period_start` + interval `sq`.`num` month))) - 1))) and (`pr`.`datetime_from` <= (`pr`.`datetime_part_period_start` + interval `sq`.`num` month)) and (isnull(`pr`.`datetime_to`) or (`pr`.`datetime_to` >= (`pr`.`datetime_part_period_end` + interval `sq`.`num` month)))) union all select `pr`.`period_id` AS `period_id`,`sq`.`num` AS `recurrence_index`,(`pr`.`datetime_part_period_start` + interval `sq`.`num` week) AS `datetime_period_item_start`,(`pr`.`datetime_part_period_end` + interval `sq`.`num` week) AS `datetime_period_item_end`,ifnull(`pis`.`blocked`,0) AS `blocked` from ((`wp_cb2_view_sequence_date` `sq` join `wp_cb2_periods` `pr`) left join `wp_cb2_perioditem_settings` `pis` on(((`pis`.`period_id` = `pr`.`period_id`) and (`pis`.`recurrence_index` = `sq`.`num`)))) where ((`pr`.`recurrence_type` = 'W') and ((year(`pr`.`datetime_part_period_end`) + (`sq`.`num` / 52)) < 9999) and (`pr`.`datetime_from` <= (`pr`.`datetime_part_period_start` + interval `sq`.`num` week)) and (isnull(`pr`.`datetime_to`) or (`pr`.`datetime_to` >= (`pr`.`datetime_part_period_end` + interval `sq`.`num` week)))) union all select `pr`.`period_id` AS `period_id`,`sq`.`num` AS `recurrence_index`,(`pr`.`datetime_part_period_start` + interval `sq`.`num` day) AS `datetime_period_item_start`,(`pr`.`datetime_part_period_end` + interval `sq`.`num` day) AS `datetime_period_item_end`,ifnull(`pis`.`blocked`,0) AS `blocked` from ((`wp_cb2_view_sequence_date` `sq` join `wp_cb2_periods` `pr`) left join `wp_cb2_perioditem_settings` `pis` on(((`pis`.`period_id` = `pr`.`period_id`) and (`pis`.`recurrence_index` = `sq`.`num`)))) where ((`pr`.`recurrence_type` = 'D') and ((year(`pr`.`datetime_part_period_end`) + (`sq`.`num` / 356)) < 9999) and ((`pr`.`recurrence_sequence` = 0) or (`pr`.`recurrence_sequence` & pow(2,(dayofweek((`pr`.`datetime_part_period_start` + interval `sq`.`num` day)) - 1)))) and (`pr`.`datetime_from` <= (`pr`.`datetime_part_period_start` + interval `sq`.`num` day)) and (isnull(`pr`.`datetime_to`) or (`pr`.`datetime_to` >= (`pr`.`datetime_part_period_end` + interval `sq`.`num` day))))",
			'cb2_view_perioditem_posts'    => "select (((`ip`.`global_period_group_id` * `pt_pi`.`ID_multiplier`) + `po`.`recurrence_index`) + `pt_pi`.`ID_base`) AS `ID`,1 AS `post_author`,`po`.`datetime_period_item_start` AS `post_date`,`po`.`datetime_period_item_start` AS `post_date_gmt`,'' AS `post_content`,`pst`.`name` AS `post_title`,'' AS `post_excerpt`,if(`ip`.`enabled`,'publish','trash') AS `post_status`,'closed' AS `comment_status`,'closed' AS `ping_status`,'' AS `post_password`,(((`ip`.`global_period_group_id` * `pt_pi`.`ID_multiplier`) + `po`.`recurrence_index`) + `pt_pi`.`ID_base`) AS `post_name`,'' AS `to_ping`,'' AS `pinged`,`po`.`datetime_period_item_end` AS `post_modified`,`po`.`datetime_period_item_end` AS `post_modified_gmt`,'' AS `post_content_filtered`,((`po`.`period_id` * `pt_p`.`ID_multiplier`) + `pt_p`.`ID_base`) AS `post_parent`,'' AS `guid`,0 AS `menu_order`,`pt_pi`.`post_type` AS `post_type`,'' AS `post_mime_type`,0 AS `comment_count`,`ip`.`global_period_group_id` AS `timeframe_id`,`pt_e`.`post_type_id` AS `post_type_id`,'global' AS `period_group_type`,((`pgp`.`period_group_id` * `pt_pg`.`ID_multiplier`) + `pt_pg`.`ID_base`) AS `period_group_ID`,((`po`.`period_id` * `pt_p`.`ID_multiplier`) + `pt_p`.`ID_base`) AS `period_ID`,((`ip`.`global_period_group_id` * `pt_e`.`ID_multiplier`) + `pt_e`.`ID_base`) AS `period_entity_ID`,((`pst`.`period_status_type_id` * `pt_pst`.`ID_multiplier`) + `pt_pst`.`ID_base`) AS `period_status_type_ID`,`po`.`period_id` AS `period_native_id`,`po`.`recurrence_index` AS `recurrence_index`,0 AS `location_ID`,0 AS `item_ID`,0 AS `user_ID`,`pst`.`period_status_type_id` AS `period_status_type_native_id`,`pst`.`name` AS `period_status_type_name`,`po`.`datetime_period_item_start` AS `datetime_period_item_start`,`po`.`datetime_period_item_end` AS `datetime_period_item_end`,cast(`po`.`blocked` as unsigned) AS `blocked`,cast(`ip`.`enabled` as unsigned) AS `enabled` from (((((((((`$period_cache_table` `po` join `wp_cb2_periods` `p` on((`po`.`period_id` = `p`.`period_id`))) join `wp_cb2_period_group_period` `pgp` on((`pgp`.`period_id` = `p`.`period_id`))) join `wp_cb2_global_period_groups` `ip` on((`ip`.`period_group_id` = `pgp`.`period_group_id`))) join `wp_cb2_period_status_types` `pst` on((`ip`.`period_status_type_id` = `pst`.`period_status_type_id`))) join `wp_cb2_post_types` `pt_pi`) join `wp_cb2_post_types` `pt_p`) join `wp_cb2_post_types` `pt_pg`) join `wp_cb2_post_types` `pt_e`) join `wp_cb2_post_types` `pt_pst`) where ((4 = `pt_pi`.`post_type_id`) and (1 = `pt_p`.`post_type_id`) and (2 = `pt_pg`.`post_type_id`) and (12 = `pt_e`.`post_type_id`) and (8 = `pt_pst`.`post_type_id`)) union all select (((`ip`.`location_period_group_id` * `pt_pi`.`ID_multiplier`) + `po`.`recurrence_index`) + `pt_pi`.`ID_base`) AS `ID`,1 AS `post_author`,`po`.`datetime_period_item_start` AS `post_date`,`po`.`datetime_period_item_start` AS `post_date_gmt`,'' AS `post_content`,concat(`loc`.`post_title`,' - ',`pst`.`name`) AS `post_title`,'' AS `post_excerpt`,if(`ip`.`enabled`,'publish','trash') AS `post_status`,'closed' AS `comment_status`,'closed' AS `ping_status`,'' AS `post_password`,(((`ip`.`location_period_group_id` * `pt_pi`.`ID_multiplier`) + `po`.`recurrence_index`) + `pt_pi`.`ID_base`) AS `post_name`,'' AS `to_ping`,'' AS `pinged`,`po`.`datetime_period_item_end` AS `post_modified`,`po`.`datetime_period_item_end` AS `post_modified_gmt`,'' AS `post_content_filtered`,((`po`.`period_id` * `pt_p`.`ID_multiplier`) + `pt_p`.`ID_base`) AS `post_parent`,'' AS `guid`,0 AS `menu_order`,`pt_pi`.`post_type` AS `post_type`,'' AS `post_mime_type`,0 AS `comment_count`,`ip`.`location_period_group_id` AS `timeframe_id`,`pt_e`.`post_type_id` AS `post_type_id`,'location' AS `period_group_type`,((`pgp`.`period_group_id` * `pt_pg`.`ID_multiplier`) + `pt_pg`.`ID_base`) AS `period_group_ID`,((`po`.`period_id` * `pt_p`.`ID_multiplier`) + `pt_p`.`ID_base`) AS `period_ID`,((`ip`.`location_period_group_id` * `pt_e`.`ID_multiplier`) + `pt_e`.`ID_base`) AS `period_entity_ID`,((`pst`.`period_status_type_id` * `pt_pst`.`ID_multiplier`) + `pt_pst`.`ID_base`) AS `period_status_type_ID`,`po`.`period_id` AS `period_native_id`,`po`.`recurrence_index` AS `recurrence_index`,`ip`.`location_ID` AS "
																			. "`location_ID`,0 AS `item_ID`,0 AS `user_ID`,`pst`.`period_status_type_id` AS `period_status_type_native_id`,`pst`.`name` AS `period_status_type_name`,`po`.`datetime_period_item_start` AS `datetime_period_item_start`,`po`.`datetime_period_item_end` AS `datetime_period_item_end`,cast(`po`.`blocked` as unsigned) AS `blocked`,cast(`ip`.`enabled` as unsigned) AS `enabled` from ((((((((((`$period_cache_table` `po` join `wp_cb2_periods` `p` on((`po`.`period_id` = `p`.`period_id`))) join `wp_cb2_period_group_period` `pgp` on((`pgp`.`period_id` = `p`.`period_id`))) join `wp_cb2_location_period_groups` `ip` on((`ip`.`period_group_id` = `pgp`.`period_group_id`))) join `wp_posts` `loc` on((`ip`.`location_ID` = `loc`.`ID`))) join `wp_cb2_period_status_types` `pst` on((`ip`.`period_status_type_id` = `pst`.`period_status_type_id`))) join `wp_cb2_post_types` `pt_pi`) join `wp_cb2_post_types` `pt_p`) join `wp_cb2_post_types` `pt_pg`) join `wp_cb2_post_types` `pt_e`) join `wp_cb2_post_types` `pt_pst`) where ((5 = `pt_pi`.`post_type_id`) and (1 = `pt_p`.`post_type_id`) and (2 = `pt_pg`.`post_type_id`) and (13 = `pt_e`.`post_type_id`) and (8 = `pt_pst`.`post_type_id`)) union all select (((`ip`.`timeframe_period_group_id` * `pt_pi`.`ID_multiplier`) + `po`.`recurrence_index`) + `pt_pi`.`ID_base`) AS `ID`,1 AS `post_author`,`po`.`datetime_period_item_start` AS `post_date`,`po`.`datetime_period_item_start` AS `post_date_gmt`,'' AS `post_content`,concat(`loc`.`post_title`,' - ',`itm`.`post_title`,' - ',`pst`.`name`) AS `post_title`,'' AS `post_excerpt`,if(`ip`.`enabled`,'publish','trash') AS `post_status`,'closed' AS `comment_status`,'closed' AS `ping_status`,'' AS `post_password`,(((`ip`.`timeframe_period_group_id` * `pt_pi`.`ID_multiplier`) + `po`.`recurrence_index`) + `pt_pi`.`ID_base`) AS `post_name`,'' AS `to_ping`,'' AS `pinged`,`po`.`datetime_period_item_end` AS `post_modified`,`po`.`datetime_period_item_end` AS `post_modified_gmt`,'' AS `post_content_filtered`,((`po`.`period_id` * `pt_p`.`ID_multiplier`) + `pt_p`.`ID_base`) AS `post_parent`,'' AS `guid`,0 AS `menu_order`,`pt_pi`.`post_type` AS `post_type`,'' AS `post_mime_type`,0 AS `comment_count`,`ip`.`timeframe_period_group_id` AS `timeframe_id`,`pt_e`.`post_type_id` AS `post_type_id`,'timeframe' AS `period_group_type`,((`pgp`.`period_group_id` * `pt_pg`.`ID_multiplier`) + `pt_pg`.`ID_base`) AS `period_group_ID`,((`po`.`period_id` * `pt_p`.`ID_multiplier`) + `pt_p`.`ID_base`) AS `period_ID`,((`ip`.`timeframe_period_group_id` * `pt_e`.`ID_multiplier`) + `pt_e`.`ID_base`) AS `period_entity_ID`,((`pst`.`period_status_type_id` * `pt_pst`.`ID_multiplier`) + `pt_pst`.`ID_base`) AS `period_status_type_ID`,`po`.`period_id` AS `period_native_id`,`po`.`recurrence_index` AS `recurrence_index`,`ip`.`location_ID` AS `location_ID`,`ip`.`item_ID` AS `item_ID`,0 AS `user_ID`,`pst`.`period_status_type_id` AS `period_status_type_native_id`,`pst`.`name` AS `period_status_type_name`,`po`.`datetime_period_item_start` AS `datetime_period_item_start`,`po`.`datetime_period_item_end` AS `datetime_period_item_end`,cast(`po`.`blocked` as unsigned) AS `blocked`,cast(`ip`.`enabled` as unsigned) AS `enabled` from (((((((((((`$period_cache_table` `po` join `wp_cb2_periods` `p` on((`po`.`period_id` = `p`.`period_id`))) join `wp_cb2_period_group_period` `pgp` on((`pgp`.`period_id` = `p`.`period_id`))) join `wp_cb2_timeframe_period_groups` `ip` on((`ip`.`period_group_id` = `pgp`.`period_group_id`))) join `wp_posts` `loc` on((`ip`.`location_ID` = `loc`.`ID`))) join `wp_posts` `itm` on((`ip`.`item_ID` = `itm`.`ID`))) join `wp_cb2_period_status_types` `pst` on((`ip`.`period_status_type_id` = `pst`.`period_status_type_id`))) join `wp_cb2_post_types` `pt_pi`) join `wp_cb2_post_types` `pt_p`) join `wp_cb2_post_types` `pt_pg`) join `wp_cb2_post_types` `pt_e`) join `wp_cb2_post_types` `pt_pst`) where ((6 = `pt_pi`.`post_type_id`) and (1 = `pt_p`.`post_type_id`) and (2 = `pt_pg`.`post_type_id`) and (14 = `pt_e`.`post_type_id`) and (8 = `pt_pst`.`post_type_id`)) union all select (((`ip`.`timeframe_user_period_group_id` * `pt_pi`.`ID_multiplier`) + `po`.`recurrence_index`) + `pt_pi`.`ID_base`) AS `ID`,1 AS `post_author`,`po`.`datetime_period_item_start` "
																			. "AS `post_date`,`po`.`datetime_period_item_start` AS `post_date_gmt`,'' AS `post_content`,concat(`loc`.`post_title`,' - ',`itm`.`post_title`,' - ',`usr`.`user_login`,' - ',`pst`.`name`) AS `post_title`,'' AS `post_excerpt`,if(`ip`.`enabled`,'publish','trash') AS `post_status`,'closed' AS `comment_status`,'closed' AS `ping_status`,'' AS `post_password`,(((`ip`.`timeframe_user_period_group_id` * `pt_pi`.`ID_multiplier`) + `po`.`recurrence_index`) + `pt_pi`.`ID_base`) AS `post_name`,'' AS `to_ping`,'' AS `pinged`,`po`.`datetime_period_item_end` AS `post_modified`,`po`.`datetime_period_item_end` AS `post_modified_gmt`,'' AS `post_content_filtered`,((`po`.`period_id` * `pt_p`.`ID_multiplier`) + `pt_p`.`ID_base`) AS `post_parent`,'' AS `guid`,0 AS `menu_order`,`pt_pi`.`post_type` AS `post_type`,'' AS `post_mime_type`,0 AS `comment_count`,`ip`.`timeframe_user_period_group_id` AS `timeframe_id`,`pt_e`.`post_type_id` AS `post_type_id`,'user' AS `period_group_type`,((`pgp`.`period_group_id` * `pt_pg`.`ID_multiplier`) + `pt_pg`.`ID_base`) AS `period_group_ID`,((`po`.`period_id` * `pt_p`.`ID_multiplier`) + `pt_p`.`ID_base`) AS `period_ID`,((`ip`.`timeframe_user_period_group_id` * `pt_e`.`ID_multiplier`) + `pt_e`.`ID_base`) AS `period_entity_ID`,((`pst`.`period_status_type_id` * `pt_pst`.`ID_multiplier`) + `pt_pst`.`ID_base`) AS `period_status_type_ID`,`po`.`period_id` AS `period_native_id`,`po`.`recurrence_index` AS `recurrence_index`,`ip`.`location_ID` AS `location_ID`,`ip`.`item_ID` AS `item_ID`,`ip`.`user_ID` AS `user_ID`,`pst`.`period_status_type_id` AS `period_status_type_native_id`,`pst`.`name` AS `period_status_type_name`,`po`.`datetime_period_item_start` AS `datetime_period_item_start`,`po`.`datetime_period_item_end` AS `datetime_period_item_end`,cast(`po`.`blocked` as unsigned) AS `blocked`,cast(`ip`.`enabled` as unsigned) AS `enabled` from ((((((((((((`$period_cache_table` `po` join `wp_cb2_periods` `p` on((`po`.`period_id` = `p`.`period_id`))) join `wp_cb2_period_group_period` `pgp` on((`pgp`.`period_id` = `p`.`period_id`))) join `wp_cb2_timeframe_user_period_groups` `ip` on((`ip`.`period_group_id` = `pgp`.`period_group_id`))) join `wp_posts` `loc` on((`ip`.`location_ID` = `loc`.`ID`))) join `wp_posts` `itm` on((`ip`.`item_ID` = `itm`.`ID`))) join `wp_users` `usr` on((`ip`.`user_ID` = `usr`.`ID`))) join `wp_cb2_period_status_types` `pst` on((`ip`.`period_status_type_id` = `pst`.`period_status_type_id`))) join `wp_cb2_post_types` `pt_pi`) join `wp_cb2_post_types` `pt_p`) join `wp_cb2_post_types` `pt_pg`) join `wp_cb2_post_types` `pt_e`) join `wp_cb2_post_types` `pt_pst`) where ((7 = `pt_pi`.`post_type_id`) and (1 = `pt_p`.`post_type_id`) and (2 = `pt_pg`.`post_type_id`) and (15 = `pt_e`.`post_type_id`) and (8 = `pt_pst`.`post_type_id`))",
			'cb2_view_perioditemmeta'      => "select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * `pt`.`ID_multiplier`) + `cal`.`recurrence_index`) * 20) + `pt`.`ID_base`) + 1) AS `meta_id`,`cal`.`ID` AS `post_id`,`cal`.`ID` AS `perioditem_id`,'location_ID' AS `meta_key`,`cal`.`location_ID` AS `meta_value` from (`wp_cb2_view_perioditem_posts` `cal` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = concat('perioditem-',`cal`.`period_group_type`)))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * `pt`.`ID_multiplier`) + `cal`.`recurrence_index`) * 20) + `pt`.`ID_base`) + 2) AS `meta_id`,`cal`.`ID` AS `post_id`,`cal`.`ID` AS `perioditem_id`,'item_ID' AS `meta_key`,`cal`.`item_ID` AS `meta_value` from (`wp_cb2_view_perioditem_posts` `cal` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = concat('perioditem-',`cal`.`period_group_type`)))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * `pt`.`ID_multiplier`) + `cal`.`recurrence_index`) * 20) + `pt`.`ID_base`) + 3) AS `meta_id`,`cal`.`ID` AS `post_id`,`cal`.`ID` AS `perioditem_id`,'user_ID' AS `meta_key`,`cal`.`user_ID` AS `meta_value` from (`wp_cb2_view_perioditem_posts` `cal` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = concat('perioditem-',`cal`.`period_group_type`)))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * `pt`.`ID_multiplier`) + `cal`.`recurrence_index`) * 20) + `pt`.`ID_base`) + 4) AS `meta_id`,`cal`.`ID` AS `post_id`,`cal`.`ID` AS `perioditem_id`,'period_group_ID' AS `meta_key`,`cal`.`period_group_ID` AS `meta_value` from (`wp_cb2_view_perioditem_posts` `cal` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = concat('perioditem-',`cal`.`period_group_type`)))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * `pt`.`ID_multiplier`) + `cal`.`recurrence_index`) * 20) + `pt`.`ID_base`) + 5) AS `meta_id`,`cal`.`ID` AS `post_id`,`cal`.`ID` AS `perioditem_id`,'period_ID' AS `meta_key`,`cal`.`period_ID` AS `meta_value` from (`wp_cb2_view_perioditem_posts` `cal` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = concat('perioditem-',`cal`.`period_group_type`)))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * `pt`.`ID_multiplier`) + `cal`.`recurrence_index`) * 20) + `pt`.`ID_base`) + 6) AS `meta_id`,`cal`.`ID` AS `post_id`,`cal`.`ID` AS `perioditem_id`,'period_status_type_ID' AS `meta_key`,`cal`.`period_status_type_ID` AS `meta_value` from (`wp_cb2_view_perioditem_posts` `cal` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = concat('perioditem-',`cal`.`period_group_type`)))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * `pt`.`ID_multiplier`) + `cal`.`recurrence_index`) * 20) + `pt`.`ID_base`) + 7) AS `meta_id`,`cal`.`ID` AS `post_id`,`cal`.`ID` AS `perioditem_id`,'recurrence_index' AS `meta_key`,`cal`.`recurrence_index` AS `meta_value` from (`wp_cb2_view_perioditem_posts` `cal` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = concat('perioditem-',`cal`.`period_group_type`)))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * `pt`.`ID_multiplier`) + `cal`.`recurrence_index`) * 20) + `pt`.`ID_base`) + 8) AS `meta_id`,`cal`.`ID` AS `post_id`,`cal`.`ID` AS `perioditem_id`,'period_group_type' AS `meta_key`,`cal`.`period_group_type` AS `meta_value` from (`wp_cb2_view_perioditem_posts` `cal` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = concat('perioditem-',`cal`.`period_group_type`)))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * `pt`.`ID_multiplier`) + `cal`.`recurrence_index`) * 20) + `pt`.`ID_base`) + 9) AS `meta_id`,`cal`.`ID` AS `post_id`,`cal`.`ID` AS `perioditem_id`,'period_status_type_name' AS "
																			. "`meta_key`,`cal`.`period_status_type_name` AS `meta_value` from (`wp_cb2_view_perioditem_posts` `cal` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = concat('perioditem-',`cal`.`period_group_type`)))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * `pt`.`ID_multiplier`) + `cal`.`recurrence_index`) * 20) + `pt`.`ID_base`) + 10) AS `meta_id`,`cal`.`ID` AS `post_id`,`cal`.`ID` AS `perioditem_id`,'period_entity_ID' AS `meta_key`,`cal`.`period_entity_ID` AS `meta_value` from (`wp_cb2_view_perioditem_posts` `cal` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = concat('perioditem-',`cal`.`period_group_type`)))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * `pt`.`ID_multiplier`) + `cal`.`recurrence_index`) * 20) + `pt`.`ID_base`) + 11) AS `meta_id`,`cal`.`ID` AS `post_id`,`cal`.`ID` AS `perioditem_id`,'blocked' AS `meta_key`,`cal`.`blocked` AS `meta_value` from (`wp_cb2_view_perioditem_posts` `cal` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = concat('perioditem-',`cal`.`period_group_type`)))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * `pt`.`ID_multiplier`) + `cal`.`recurrence_index`) * 20) + `pt`.`ID_base`) + 12) AS `meta_id`,`cal`.`ID` AS `post_id`,`cal`.`ID` AS `perioditem_id`,'enabled' AS `meta_key`,`cal`.`enabled` AS `meta_value` from (`wp_cb2_view_perioditem_posts` `cal` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = concat('perioditem-',`cal`.`period_group_type`))))",
		);
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
		if ( $this->datetime_period_item_start > $this->datetime_period_item_end )
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

    if ( $ID ) self::$all[$ID] = $this;
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

  function block() {
		global $wpdb;

		$full_table = "{$wpdb->prefix}cb2_perioditem_settings";
		$blocked = $wpdb->get_var( $wpdb->prepare(
			"SELECT blocked FROM $full_table where period_id = %d and recurrence_index = %d",
			$this->period->id(),
			$this->recurrence_index
		) );

		krumo($blocked);

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

  static function factory_subclass(
		$ID,
		$period_entity, // CB2_PeriodEntity
    $period,        // CB2_Period
    $recurrence_index,
    $datetime_period_item_start,
    $datetime_period_item_end,
    $blocked
  ) {
		// provides appropriate sub-class based on final object parameters
		$object = NULL;
		if      ( $user )     $object = CB2_PeriodItem_Timeframe_User::factory(
				$ID,
				$period_entity,
				$period,
				$recurrence_index,
				$datetime_period_item_start,
				$datetime_period_item_end,
				$blocked
			);
		else if ( $item )     $object = CB2_PeriodItem_Timeframe::factory(
				$ID,
				$period_entity,
				$period,
				$recurrence_index,
				$datetime_period_item_start,
				$datetime_period_item_end,
				$blocked
			);
		else if ( $location ) $object = CB2_PeriodItem_Location::factory(
				$ID,
				$period_entity,
				$period,
				$recurrence_index,
				$datetime_period_item_start,
				$datetime_period_item_end,
				$blocked
			);
		else                  $object = CB2_PeriodItem_Global::factory(
				$ID,
				$period_entity,
				$period,
				$recurrence_index,
				$datetime_period_item_start,
				$datetime_period_item_end,
				$blocked
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
    // TODO: better / faster way of doing seconds_in_day()?
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
    if ( $this->blocked ) $classes .= ' cb2-blocked';
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

  function field_value_string_name( $object, $class = '', $date_format = 'H:i' ) {
		$name_value = NULL;
		$name_field_names = 'name';
		if ( method_exists( $this, 'name_field' ) ) $name_field_names = $this->name_field();

		if ( is_array( $name_field_names ) ) {
			$name_value = '';
			foreach ( $name_field_names as $name_field_name ) {
				if ( $name_value ) $name_value .= ' ';
				$name_value .= CB2::get_field( $name_field_name, $class, $date_format );
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
      'datetime_part_period_start' => $this->datetime_part_period_start->format( CB2_Query::$javascript_date_format ),
      'datetime_part_period_end' => $this->datetime_part_period_end->format( CB2_Query::$javascript_date_format ),
      'datetime_from' => $this->datetime_from->format( CB2_Query::$javascript_date_format ),
      'datetime_to' => ( $this->datetime_to ? $this->datetime_to->format( CB2_Query::$javascript_date_format ) : '' ),
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
class CB2_PeriodItem_Automatic extends CB2_PeriodItem {
	// Completely PHP layer generated posts
	// no postmeta or stuff in the DB
	// A fake perioditem WP_Post for each day
	// simply to make rendering the full calendar easier
	static public $static_post_type = 'perioditem-automatic';
  static $database_table = FALSE;
  static $postmeta_table = FALSE;
  static private $fake_ID = 300000000;

  function post_type() {return self::$static_post_type;}

  static function post_from_date( $date ) {
		$startdate  = ( clone $date )->setTime( 0, 0 );
		$enddate    = ( clone $startdate )->setTime( 23, 59 );
		$post_title = $startdate->format( CB2_Query::$datetime_format );
		$post_name  = $startdate->format( 'Y-m-d' );

		return new WP_Post( (object) array(
			'ID' => self::$fake_ID++,         // Complete fake ID
			GET_METADATA_ASSIGN => TRUE,      // Prevent meta-data analysis ($postmeta_table = FALSE does this also)
			'post_status'    => 'auto-draft', // auto-draft to allow WP_Query selection
			'post_type'      => self::$static_post_type, // Does not exist in database
			'post_author'    => 1,
			'post_date'      => $startdate->format( CB2_Query::$datetime_format ),
			'post_date_gmt'  => $startdate->format( CB2_Query::$datetime_format ),
			'post_content'   => '',
			'post_title'     => $post_title,
			'post_excerpt'   => '',
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
			'post_password'  => '',
			'post_name'      => $post_name,
			'to_ping'        => '',
			'pinged'         => '',
			'post_modified'     => $enddate->format( CB2_Query::$datetime_format ),
			'post_modified_gmt' => $enddate->format( CB2_Query::$datetime_format ),
			'post_content_filtered' => '',
			'post_parent' => NULL,
			'guid'        => '',
			'menu_order'  => 0,
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
			'item_ID'     => 0,
			'user_ID'     => 0,
			'period_status_type_ID' => 0,
			'period_status_type_name' => '',
			'datetime_period_item_start' => $startdate->format( CB2_Query::$datetime_format ),
			'datetime_period_item_end'   => $enddate->format( CB2_Query::$datetime_format ),
			'blocked' => FALSE,
		) );
	}

  function __construct(
		$ID,
		$period_entity,
    $period,
		$recurrence_index,
		$datetime_period_item_start,
		$datetime_period_item_end,
    $blocked
  ) {
		$this->post_type = self::$static_post_type;

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

  static function &factory_from_properties( &$properties, &$instance_container = NULL ) {
		$object = self::factory(
			$properties['ID'],
			NULL, // period_entity
			NULL, // period
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
		if ( $ID && isset( self::$all[$ID] ) ) {
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


// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB2_PeriodItem_Global extends CB2_PeriodItem {
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

  static function &factory_from_properties( &$properties, &$instance_container = NULL ) {
		$object = self::factory(
			$properties['ID'],
			CB2_PostNavigator::get_or_create_new( $properties, 'period_entity_ID', $instance_container, 'CB2_PeriodEntity_Global' ),
			CB2_PostNavigator::get_or_create_new( $properties, 'period_ID',        $instance_container ),
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
		if ( $ID && isset( self::$all[$ID] ) ) {
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


// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB2_PeriodItem_Location extends CB2_PeriodItem {
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

  static function &factory_from_properties( &$properties, &$instance_container = NULL ) {
		$object = self::factory(
			$properties['ID'],
			CB2_PostNavigator::get_or_create_new( $properties, 'period_entity_ID', $instance_container, 'CB2_PeriodEntity_Location' ),
			CB2_PostNavigator::get_or_create_new( $properties, 'period_ID',        $instance_container ),
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
		if ( $ID && isset( self::$all[$ID] ) ) {
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


// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB2_PeriodItem_Timeframe extends CB2_PeriodItem {
  static $name_field = array( 'location', 'item' );
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

  static function &factory_from_properties( &$properties, &$instance_container = NULL ) {
		$object = self::factory(
			$properties['ID'],
			CB2_PostNavigator::get_or_create_new( $properties, 'period_entity_ID', $instance_container, 'CB2_PeriodEntity_Timeframe' ),
			CB2_PostNavigator::get_or_create_new( $properties, 'period_ID',        $instance_container ),
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
		if ( $ID && isset( self::$all[$ID] ) ) {
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
		// TODO: complete update_option() cb2_timeframe_options
		throw new Exception( 'NOT_COMPLETE' );
    $update = CB2_Database_UpdateInsert::factory( self::$database_options_table );
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


// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB2_PeriodItem_Timeframe_User extends CB2_PeriodItem {
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

  static function &factory_from_properties( &$properties, &$instance_container = NULL ) {
		$object = self::factory(
			$properties['ID'],
			CB2_PostNavigator::get_or_create_new( $properties, 'period_entity_ID', $instance_container, 'CB2_PeriodEntity_Timeframe_User' ),
			CB2_PostNavigator::get_or_create_new( $properties, 'period_ID',        $instance_container ),
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
		if ( $ID && isset( self::$all[$ID] ) ) {
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
