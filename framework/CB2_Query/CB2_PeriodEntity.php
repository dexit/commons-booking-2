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
			$primary_id_column      => array( CB2_BIGINT, (20), CB2_UNSIGNED, CB2_NOT_NULL, CB2_AUTO_INCREMENT ),
			'period_group_id'       => array( CB2_INT,    (11), CB2_UNSIGNED, CB2_NOT_NULL ),
			'period_status_type_id' => array( CB2_INT,    (11), CB2_UNSIGNED, CB2_NOT_NULL ),
			'enabled'               => array( CB2_BIT,    (1),  NULL,         CB2_NOT_NULL, NULL,  1 ),
			'author_ID'             => array( CB2_BIGINT, (20), CB2_UNSIGNED,         CB2_NOT_NULL, FALSE, 1 ),
		);
		$columns = array_merge( $base_columns, $extra_columns );

		$base_foreign_keys = array(
			'period_group_id'       => array( 'cb2_period_groups',       'period_group_id' ),
			'period_status_type_id' => array( 'cb2_period_status_types', 'period_status_type_id' ),
			'author_ID'             => array( 'users', 'ID' ),
		);
		$foreign_keys = array_merge( $base_foreign_keys, $extra_foreign_keys );

		$primary_key = array_keys( $extra_columns );
		$primary_key = array_merge( $primary_key, array_keys( $base_foreign_keys ) );

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
			'cb2_view_periodent_posts'   => "select ((`p`.`timeframe_id` * `pt_e`.`ID_multiplier`) + `pt_e`.`ID_base`) AS `ID`,1 AS `post_author`,'2018-01-01' AS `post_date`,'2018-01-01' AS `post_date_gmt`,'' AS `post_content`,`p`.`name` AS `post_title`,'' AS `post_excerpt`,if(`p`.`enabled`,'publish','trash') AS `post_status`,'closed' AS `comment_status`,'closed' AS `ping_status`,'' AS `post_password`,(`p`.`timeframe_id` + `pt_e`.`ID_base`) AS `post_name`,'' AS `to_ping`,'' AS `pinged`,'2018-01-01' AS `post_modified`,'2018-01-01' AS `post_modified_gmt`,'' AS `post_content_filtered`,0 AS `post_parent`,'' AS `guid`,0 AS `menu_order`,concat('periodent-',`p`.`period_group_type`) AS `post_type`,'' AS `post_mime_type`,0 AS `comment_count`,`p`.`timeframe_id` AS `timeframe_id`,ifnull(`p`.`location_ID`,0) AS `location_ID`,ifnull(`p`.`item_ID`,0) AS `item_ID`,ifnull(`p`.`user_ID`,0) AS `user_ID`,`p`.`title` AS `title`,(`p`.`period_group_id` + `pt_pg`.`ID_base`) AS `period_group_ID`,(`p`.`period_status_type_id` + `pt_pst`.`ID_base`) AS `period_status_type_ID`,`p`.`period_status_type_id` AS `period_status_type_native_id`,`pst`.`name` AS `period_status_type_name`,(select group_concat((`{$prefix}cb2_period_group_period`.`period_id` + `pt2`.`ID_base`) separator ',') from (`{$prefix}cb2_period_group_period` join `{$prefix}cb2_post_types` `pt2` on((`pt2`.`post_type` = 'period'))) where (`{$prefix}cb2_period_group_period`.`period_group_id` = `p`.`period_group_id`) group by `{$prefix}cb2_period_group_period`.`period_group_id`) AS `period_IDs`,cast(`p`.`enabled` as unsigned) AS `enabled`,((`p_first`.`period_id` * `pt_p_first`.`ID_multiplier`) + `pt_p_first`.`ID_base`) AS `period_ID`,`p_first`.`datetime_part_period_start` AS `datetime_part_period_start`,`p_first`.`datetime_part_period_end` AS `datetime_part_period_end`,`p_first`.`recurrence_type` AS `recurrence_type`,`p_first`.`recurrence_frequency` AS `recurrence_frequency`,`p_first`.`datetime_from` AS `datetime_from`,`p_first`.`datetime_to` AS `datetime_to`,`p_first`.`recurrence_sequence` AS `recurrence_sequence` from ((((((((select `ip`.`global_period_group_id` AS `timeframe_id`,`pg`.`name` AS `name`,convert(`pg`.`name` using utf8) AS `title`,NULL AS `location_ID`,NULL AS `item_ID`,NULL AS `user_ID`,'global' AS `period_group_type`,1 AS `period_group_priority`,`ip`.`period_group_id` AS `period_group_id`,`ip`.`period_status_type_id` AS `period_status_type_id`,`ip`.`enabled` AS `enabled` from (`{$prefix}cb2_global_period_groups` `ip` join `{$prefix}cb2_period_groups` `pg` on((`ip`.`period_group_id` = `pg`.`period_group_id`)))) union all select `ip`.`location_period_group_id` AS `timeframe_ID`,`pg`.`name` AS `name`,convert(concat(`pg`.`name`,convert(if(length(`pg`.`name`),' - ','') using utf8),`loc`.`post_title`) using utf8) AS `title`,`ip`.`location_ID` AS `location_ID`,NULL AS `item_ID`,NULL AS `user_ID`,'location' AS `period_group_type`,2 AS `period_group_priority`,`ip`.`period_group_id` AS `period_group_id`,`ip`.`period_status_type_id` AS `period_status_type_id`,`ip`.`enabled` AS `enabled` from ((`{$prefix}cb2_location_period_groups` `ip` join `{$prefix}cb2_period_groups` `pg` on((`ip`.`period_group_id` = `pg`.`period_group_id`))) join `{$prefix}posts` `loc` on((`ip`.`location_ID` = `loc`.`ID`))) union all select `ip`.`timeframe_period_group_id` AS `timeframe_ID`,`pg`.`name` AS `name`,convert(concat(`pg`.`name`,convert(if(length(`pg`.`name`),' - ','') using utf8),`loc`.`post_title`,' - ',`itm`.`post_title`) using utf8) AS `title`,`ip`.`location_ID` AS `location_ID`,`ip`.`item_ID` AS `item_ID`,NULL AS `user_ID`,'timeframe' AS `period_group_type`,3 AS `period_group_priority`,`ip`.`period_group_id` AS `period_group_id`,`ip`.`period_status_type_id` AS `period_status_type_id`,`ip`.`enabled` AS `enabled` from (((`{$prefix}cb2_timeframe_period_groups` `ip` join `{$prefix}cb2_period_groups` `pg` on((`ip`.`period_group_id` = `pg`.`period_group_id`))) join `{$prefix}posts` `loc` on((`ip`.`location_ID` = `loc`.`ID`))) join `{$prefix}posts` `itm` on((`ip`.`item_ID` = `itm`.`ID`))) union all select `ip`.`timeframe_user_period_group_id` AS `timeframe_ID`,`pg`.`name` AS `name`,convert(concat(`pg`.`name`,convert(if(length(`pg`.`name`),' - ','') using utf8),`loc`.`post_title`,' - ',`itm`.`post_title`,' - ',`usr`.`user_login`) using utf8) AS `title`,`ip`.`location_ID` AS `location_ID`,`ip`.`item_ID` AS `item_ID`,`ip`.`user_ID` AS `user_ID`,'user' AS `period_group_type`,4 AS `period_group_priority`,`ip`.`period_group_id` AS `period_group_id`,`ip`.`period_status_type_id` AS `period_status_type_id`,`ip`.`enabled` AS `enabled` from ((((`{$prefix}cb2_timeframe_user_period_groups` `ip` join `{$prefix}cb2_period_groups` `pg` on((`ip`.`period_group_id` = `pg`.`period_group_id`))) join `{$prefix}posts` `loc` on((`ip`.`location_ID` = `loc`.`ID`))) join `{$prefix}posts` `itm` on((`ip`.`item_ID` = `itm`.`ID`))) join `{$prefix}users` `usr` on((`ip`.`user_ID` = `usr`.`ID`)))) `p` join `{$prefix}cb2_period_status_types` `pst` on((`p`.`period_status_type_id` = `pst`.`period_status_type_id`))) join `{$prefix}cb2_post_types` `pt_e` on((`pt_e`.`post_type` = convert(concat('periodent-',`p`.`period_group_type`) using utf8)))) join `{$prefix}cb2_post_types` `pt_pg` on((`pt_pg`.`post_type_id` = 2))) join `{$prefix}cb2_post_types` `pt_pst` on((`pt_pst`.`post_type_id` = 8))) join `{$prefix}cb2_post_types` `pt_p_first` on((`pt_p_first`.`post_type_id` = 1))) join `{$prefix}cb2_periods` `p_first` on((`p_first`.`period_id` = (select `ps2`.`period_id` from `{$prefix}cb2_period_group_period` `ps2` where (`ps2`.`period_group_id` = `p`.`period_group_id`) order by `ps2`.`period_id` limit 1))))",
			'cb2_view_periodentmeta'     => "select (((`po`.`timeframe_id` * `pt`.`ID_multiplier`) * 20) + `pt`.`ID_base`) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'period_IDs' AS `meta_key`,convert(`po`.`period_IDs` using utf8) AS `meta_value` from (`{$prefix}cb2_view_periodent_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = convert(`po`.`post_type` using utf8)))) union all select ((((`po`.`timeframe_id` * `pt`.`ID_multiplier`) * 20) + `pt`.`ID_base`) + 1) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'period_group_ID' AS `meta_key`,convert(`po`.`period_group_ID` using utf8) AS `meta_value` from (`{$prefix}cb2_view_periodent_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = convert(`po`.`post_type` using utf8)))) union all select ((((`po`.`timeframe_id` * `pt`.`ID_multiplier`) * 20) + `pt`.`ID_base`) + 2) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'period_status_type_ID' AS `meta_key`,convert(`po`.`period_status_type_ID` using utf8) AS `meta_value` from (`{$prefix}cb2_view_periodent_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = convert(`po`.`post_type` using utf8)))) union all select ((((`po`.`timeframe_id` * `pt`.`ID_multiplier`) * 20) + `pt`.`ID_base`) + 3) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'location_ID' AS `meta_key`,convert(`po`.`location_ID` using utf8) AS `meta_value` from (`{$prefix}cb2_view_periodent_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = convert(`po`.`post_type` using utf8)))) union all select ((((`po`.`timeframe_id` * `pt`.`ID_multiplier`) * 20) + `pt`.`ID_base`) + 4) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'item_ID' AS `meta_key`,convert(`po`.`item_ID` using utf8) AS `meta_value` from (`{$prefix}cb2_view_periodent_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = convert(`po`.`post_type` using utf8)))) union all select ((((`po`.`timeframe_id` * `pt`.`ID_multiplier`) * 20) + `pt`.`ID_base`) + 5) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'user_ID' AS `meta_key`,convert(`po`.`user_ID` using utf8) AS `meta_value` from (`{$prefix}cb2_view_periodent_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = convert(`po`.`post_type` using utf8)))) union all select ((((`po`.`timeframe_id` * `pt`.`ID_multiplier`) * 20) + `pt`.`ID_base`) + 6) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'enabled' AS `meta_key`,convert(`po`.`enabled` using utf8) AS `meta_value` from (`{$prefix}cb2_view_periodent_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = convert(`po`.`post_type` using utf8)))) union all select ((((`po`.`timeframe_id` * `pt`.`ID_multiplier`) * 20) + `pt`.`ID_base`) + 7) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'period_status_type_name' AS `meta_key`,convert(`po`.`period_status_type_name` using utf8) AS `meta_value` from (`{$prefix}cb2_view_periodent_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = convert(`po`.`post_type` using utf8)))) union all select ((((`po`.`timeframe_id` * `pt`.`ID_multiplier`) * 20) + `pt`.`ID_base`) + 8) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'datetime_part_period_start' AS `meta_key`,convert(`po`.`datetime_part_period_start` using utf8) AS `meta_value` from (`{$prefix}cb2_view_periodent_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = convert(`po`.`post_type` using utf8)))) union all select ((((`po`.`timeframe_id` * `pt`.`ID_multiplier`) * 20) + `pt`.`ID_base`) + 9) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'datetime_part_period_end' AS `meta_key`,convert(`po`.`datetime_part_period_end` using utf8) AS `meta_value` from (`{$prefix}cb2_view_periodent_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = convert(`po`.`post_type` using utf8)))) union all select ((((`po`.`timeframe_id` * `pt`.`ID_multiplier`) * 20) + `pt`.`ID_base`) + 10) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'recurrence_type' AS `meta_key`,convert(`po`.`recurrence_type` using utf8) AS `meta_value` from (`{$prefix}cb2_view_periodent_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = convert(`po`.`post_type` using utf8)))) union all select ((((`po`.`timeframe_id` * `pt`.`ID_multiplier`) * 20) + `pt`.`ID_base`) + 11) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'recurrence_frequency' AS `meta_key`,convert(`po`.`recurrence_frequency` using utf8) AS `meta_value` from (`{$prefix}cb2_view_periodent_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = convert(`po`.`post_type` using utf8)))) union all select ((((`po`.`timeframe_id` * `pt`.`ID_multiplier`) * 20) + `pt`.`ID_base`) + 12) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'datetime_from' AS `meta_key`,convert(`po`.`datetime_from` using utf8) AS `meta_value` from (`{$prefix}cb2_view_periodent_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = convert(`po`.`post_type` using utf8)))) union all select ((((`po`.`timeframe_id` * `pt`.`ID_multiplier`) * 20) + `pt`.`ID_base`) + 13) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'datetime_to' AS `meta_key`,convert(`po`.`datetime_to` using utf8) AS `meta_value` from (`{$prefix}cb2_view_periodent_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = convert(`po`.`post_type` using utf8)))) union all select ((((`po`.`timeframe_id` * `pt`.`ID_multiplier`) * 20) + `pt`.`ID_base`) + 14) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'recurrence_sequence' AS `meta_key`,convert(cast(`po`.`recurrence_sequence` as unsigned) using utf8) AS `meta_value` from (`{$prefix}cb2_view_periodent_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = convert(`po`.`post_type` using utf8)))) union all select ((((`po`.`timeframe_id` * `pt`.`ID_multiplier`) * 20) + `pt`.`ID_base`) + 15) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'period_ID' AS `meta_key`,convert(`po`.`period_ID` using utf8) AS `meta_value` from (`{$prefix}cb2_view_periodent_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = convert(`po`.`post_type` using utf8)))) union all select ((((`po`.`timeframe_id` * `pt`.`ID_multiplier`) * 20) + `pt`.`ID_base`) + 16) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'period_status_type_id' AS `meta_key`,convert(`po`.`period_status_type_native_id` using utf8) AS `meta_value` from (`{$prefix}cb2_view_periodent_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = convert(`po`.`post_type` using utf8)))) union all select ((((`po`.`timeframe_id` * `pt`.`ID_multiplier`) * 20) + `pt`.`ID_base`) + 17) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'title' AS `meta_key`,convert(`po`.`title` using utf8) AS `meta_value` from (`{$prefix}cb2_view_periodent_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = convert(`po`.`post_type` using utf8))))",
			// TODO: link cb2_view_timeframe_options in to the cb2_view_periodentmeta
			'cb2_view_timeframe_options' => "select distinct `c2to`.`timeframe_id` AS `timeframe_id`,(select `{$prefix}cb2_timeframe_options`.`option_value` from `{$prefix}cb2_timeframe_options` where ((`{$prefix}cb2_timeframe_options`.`timeframe_id` = `c2to`.`timeframe_id`) and (`{$prefix}cb2_timeframe_options`.`option_name` = 'max-slots')) order by `{$prefix}cb2_timeframe_options`.`option_id` desc limit 1) AS `max-slots`,(select `{$prefix}cb2_timeframe_options`.`option_value` from `{$prefix}cb2_timeframe_options` where ((`{$prefix}cb2_timeframe_options`.`timeframe_id` = `c2to`.`timeframe_id`) and (`{$prefix}cb2_timeframe_options`.`option_name` = 'closed-days-booking')) order by `{$prefix}cb2_timeframe_options`.`option_id` desc limit 1) AS `closed-days-booking`,(select `{$prefix}cb2_timeframe_options`.`option_value` from `{$prefix}cb2_timeframe_options` where ((`{$prefix}cb2_timeframe_options`.`timeframe_id` = `c2to`.`timeframe_id`) and (`{$prefix}cb2_timeframe_options`.`option_name` = 'consequtive-slots')) order by `{$prefix}cb2_timeframe_options`.`option_id` desc limit 1) AS `consequtive-slots`,(select `{$prefix}cb2_timeframe_options`.`option_value` from `{$prefix}cb2_timeframe_options` where ((`{$prefix}cb2_timeframe_options`.`timeframe_id` = `c2to`.`timeframe_id`) and (`{$prefix}cb2_timeframe_options`.`option_name` = 'use-codes')) order by `{$prefix}cb2_timeframe_options`.`option_id` desc limit 1) AS `use-codes`,(select `{$prefix}cb2_timeframe_options`.`option_value` from `{$prefix}cb2_timeframe_options` where ((`{$prefix}cb2_timeframe_options`.`timeframe_id` = `c2to`.`timeframe_id`) and (`{$prefix}cb2_timeframe_options`.`option_name` = 'limit')) order by `{$prefix}cb2_timeframe_options`.`option_id` desc limit 1) AS `limit`,(select `{$prefix}cb2_timeframe_options`.`option_value` from `{$prefix}cb2_timeframe_options` where ((`{$prefix}cb2_timeframe_options`.`timeframe_id` = `c2to`.`timeframe_id`) and (`{$prefix}cb2_timeframe_options`.`option_name` = 'holiday_provider')) order by `{$prefix}cb2_timeframe_options`.`option_id` desc limit 1) AS `holiday-provider` from `{$prefix}cb2_timeframe_options` `c2to`",
		);
	}

	static function metaboxes() {
		$metaboxes = CB2_Period::metaboxes( FALSE );
		array_push( $metaboxes,
			array(
				// TODO: link this Enabled in to the Publish meta-box status instead
				'title' => __( 'Enabled', 'commons-booking-2' ),
				'context' => 'side',
				'show_names' => FALSE,
				'closed'     => TRUE,
				'fields' => array(
					array(
						'name' => __( 'Enabled', 'commons-booking-2' ),
						'id' => 'enabled',
						'type' => 'checkbox',
						'default' => 1,
					),
				),
			)
		);
		array_push( $metaboxes,
			array(
				'title'      => __( 'Calendar view', 'commons-booking-2' ),
				'context'    => 'normal',
				'show_names' => FALSE,
				'show_on_cb' => array( 'CB2_Period', 'metabox_show_when_published' ),
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
		array_push( $metaboxes, CB2_PeriodStatusType::selector_metabox() );
		array_push( $metaboxes, CB2_Period::selector_metabox( TRUE ) ); // Multiple

		return $metaboxes;
	}

	static function metabox_calendar_options_cb( $field ) {
		global $post;

		$options = array(
			'template' => 'available',
			'actions' => array(
				'make-available' => array(
					'link_text' => __( 'Make Available' ),
					'post_type' => CB2_PeriodEntity_Timeframe::$static_post_type,
					'period_status_type_ID'      => CB2_PeriodStatusType_Available::bigID(),
					'datetime_part_period_start' => '%date->date%',
					'datetime_part_period_end'   => '%date->date%',
				),
				'set-times' => '<form><div>Example form:<input/></div></form>',
			),
		);
		if ( $post ) $options[ 'query' ] = array(
			'meta_query' => array(
				'period_entity_ID_clause' => array(
					'key'     => 'period_entity_ID',
					'value'   => array( $post->ID, 0 ),
					'compare' => 'IN',
				),
			),
		);

		return $options;
	}

  protected static function factory_from_perioditem(
		CB2_PeriodItem $perioditem,
		$new_periodentity_Class,
		$new_period_status_type_Class,
		$name = NULL,

		$copy_period_group    = TRUE,
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
			// TODO: contiguous bookings in factory_from_perioditem()
			$datetime_now = new DateTime();
			$period = new CB2_Period(
				CB2_CREATE_NEW,
				$perioditem->post_title,
				$perioditem->datetime_period_item_start, // datetime_part_period_start
				$perioditem->datetime_period_item_end,   // datetime_part_period_end
				$datetime_now                            // datetime_from
			);
			$period_group = new CB2_PeriodGroup(
				CB2_CREATE_NEW,
				$perioditem->post_title,
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

		//here to prevent static inheritance warning
		$location = NULL,
		$item     = NULL,
		$user     = NULL
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

  static function factory_subclass(
		$ID,
		$name,
		$period_group,       // CB2_PeriodGroup
		$period_status_type, // CB2_PeriodStatusType
		$enabled,

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
				$location,
				$item,
				$user
			);

		return $object;
  }

  public function __construct(
		$ID,
    $name,
		$period_group,              // CB2_PeriodGroup {[CB2_Period, ...]}
    $period_status_type,        // CB2_PeriodStatusType
    $enabled
  ) {
		parent::__construct();

		$this->ID                 = $ID;
    $this->name               = $name;
		$this->period_group       = $period_group;
		$this->period_status_type = $period_status_type;
		$this->enabled            = $enabled;
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
		if ( ! $this->period_group )
			throw new Exception( '[' . get_class( $this ) . "] [$this->ID] has no period_group" );
		return $this->period_group->manage_columns( $columns );
	}

	function custom_columns( $column ) {
		if ( ! $this->period_group )
			throw new Exception( '[' . get_class( $this ) . "] [$this->ID] has no period_group" );
		return $this->period_group->custom_columns( $column );
	}

  function classes() {
    $classes  = '';
    $classes .= $this->period_status_type->classes();
    $classes .= ' cb2-' . $this->post_type();
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

	static function &factory_from_properties( &$properties, &$instance_container = NULL, $force_properties = FALSE ) {
		$object = self::factory(
			( isset( $properties['global_period_group_ID'] ) ? $properties['global_period_group_ID'] : $properties['ID'] ),
			( isset( $properties['post_title'] ) ? $properties['post_title']           : $properties['name'] ),
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_group_ID',       $instance_container ),
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_status_type_ID', $instance_container ),
			( isset( $properties['enabled'] ) && $properties['enabled'] ) // Can come from a checkbox
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

		//here to prevent static inheritance warning
		$location = NULL,
		$item     = NULL,
		$user     = NULL
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

  public function __construct(
		$ID,
		$name,
		$period_group,              // CB2_PeriodGroup {[CB2_Period, ...]}
    $period_status_type,        // CB2_PeriodStatusType
    $enabled
  ) {
		parent::__construct(
			$ID,
			$name,
			$period_group,
			$period_status_type,
			$enabled
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
		$metaboxes = parent::metaboxes();
		array_unshift( $metaboxes, CB2_Location::selector_metabox() );
		// array_push(    $metaboxes, CB2_Location::summary_metabox() );
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
			),
			array(
				'location_ID' => array( 'posts', 'ID' ),
			)
		) );
	}

  function post_type() {return self::$static_post_type;}

	static function &factory_from_properties( &$properties, &$instance_container = NULL, $force_properties = FALSE ) {
		$object = self::factory(
			( isset( $properties['location_period_group_ID'] ) ? $properties['location_period_group_ID'] : $properties['ID'] ),
			( isset( $properties['post_title'] ) ? $properties['post_title']           : $properties['name'] ),
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_group_ID',       $instance_container ),
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_status_type_ID', $instance_container ),
			( isset( $properties['enabled'] ) && $properties['enabled'] ), // Can come from a checkbox

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
		$location = NULL,
		$item     = NULL,
		$user     = NULL
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

  public function __construct(
		$ID,
		$name,
		$period_group,              // CB2_PeriodGroup {[CB2_Period, ...]}
    $period_status_type,        // CB2_PeriodStatusType
		$enabled,

    $location                   // CB2_Location
  ) {
		parent::__construct(
			$ID,
			$name,
			$period_group,
			$period_status_type,
			$enabled
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
		array_unshift( $metaboxes, CB2_Item::selector_metabox() );
		array_unshift( $metaboxes, CB2_Location::selector_metabox() );
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

	static function &factory_from_properties( &$properties, &$instance_container = NULL, $force_properties = FALSE ) {
		$object = self::factory(
			( isset( $properties['timeframe_period_group_ID'] ) ? $properties['timeframe_period_group_ID'] : $properties['ID'] ),
			( isset( $properties['post_title'] ) ? $properties['post_title']           : $properties['name'] ),
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_group_ID',       $instance_container ),
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_status_type_ID', $instance_container ),
			( isset( $properties['enabled'] ) && $properties['enabled'] ), // Can come from a checkbox

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
		$location = NULL,
		$item     = NULL,
		$user     = NULL
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

  public function __construct(
		$ID,
		$name,
		$period_group,              // CB2_PeriodGroup {[CB2_Period, ...]}
    $period_status_type,        // CB2_PeriodStatusType
    $enabled,

    $location,                  // CB2_Location
    $item                       // CB2_Item
  ) {
		parent::__construct(
			$ID,
			$name,
			$period_group,
			$period_status_type,
			$enabled,
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
class CB2_PeriodEntity_Timeframe_User extends CB2_PeriodEntity {
  public static $database_table = 'cb2_timeframe_user_period_groups';
  static $static_post_type      = 'periodent-user';
  static $Class_PeriodItem      = 'CB2_PeriodItem_Timeframe_User'; // Associated CB2_PeriodItem

  static function metaboxes() {
		$metaboxes = parent::metaboxes();
		array_unshift( $metaboxes, CB2_User::selector_metabox() );
		array_unshift( $metaboxes, CB2_Item::selector_metabox() );
		array_unshift( $metaboxes, CB2_Location::selector_metabox() );
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

	static function &factory_from_properties( &$properties, &$instance_container = NULL, $force_properties = FALSE ) {
		$object = self::factory(
			( isset( $properties['timeframe_user_period_group_ID'] ) ? $properties['timeframe_user_period_group_ID'] : $properties['ID'] ),
			( isset( $properties['post_title'] ) ? $properties['post_title']           : $properties['name'] ),
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_group_ID',       $instance_container ),
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_status_type_ID', $instance_container ),
			( isset( $properties['enabled'] ) && $properties['enabled'] ), // Can come from a checkbox

			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'location_ID',           $instance_container ),
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'item_ID',               $instance_container ),
			CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'user_ID',               $instance_container )
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

		$location = NULL,
		$item     = NULL,
		$user     = NULL
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

  static function factory_booked_from_available_timeframe_item( CB2_PeriodItem_Timeframe $perioditem_available, CB2_User $user, $name = 'booking', $copy_period_group = TRUE ) {
		if ( ! $perioditem_available->period_entity->period_status_type instanceof CB2_PeriodStatusType_Available )
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

	function summary_actions() {
		$actions = parent::summary_actions();
		$view_link = get_permalink( $this->ID );
		$actions  .= " | <a href='$view_link'>view</a>";
		return $actions;
	}
}
