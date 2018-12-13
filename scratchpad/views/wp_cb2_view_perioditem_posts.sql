CREATE
    ALGORITHM = UNDEFINED
    DEFINER = `commonsbooking_2`@`%`
    SQL SECURITY DEFINER
VIEW `wp_cb2_view_perioditem_posts` AS
    SELECT
        (((`ip`.`global_period_group_id` * `pt_pi`.`ID_multiplier`) + `po`.`recurrence_index`) + `pt_pi`.`ID_base`) AS `ID`,
        1 AS `post_author`,
        `po`.`datetime_period_item_start` AS `post_date`,
        `po`.`datetime_period_item_start` AS `post_date_gmt`,
        '' AS `post_content`,
        CONVERT( `pst`.`name` USING UTF8) AS `post_title`,
        '' AS `post_excerpt`,
        IF(`ip`.`enabled`, 'publish', 'trash') AS `post_status`,
        'closed' AS `comment_status`,
        'closed' AS `ping_status`,
        '' AS `post_password`,
        (((`ip`.`global_period_group_id` * `pt_pi`.`ID_multiplier`) + `po`.`recurrence_index`) + `pt_pi`.`ID_base`) AS `post_name`,
        '' AS `to_ping`,
        '' AS `pinged`,
        `po`.`datetime_period_item_end` AS `post_modified`,
        `po`.`datetime_period_item_end` AS `post_modified_gmt`,
        '' AS `post_content_filtered`,
        ((`po`.`period_id` * `pt_p`.`ID_multiplier`) + `pt_p`.`ID_base`) AS `post_parent`,
        '' AS `guid`,
        0 AS `menu_order`,
        `pt_pi`.`post_type` AS `post_type`,
        '' AS `post_mime_type`,
        0 AS `comment_count`,
        `ip`.`global_period_group_id` AS `timeframe_id`,
        `pt_e`.`post_type_id` AS `post_type_id`,
        'global' AS `period_group_type`,
        ((`pgp`.`period_group_id` * `pt_pg`.`ID_multiplier`) + `pt_pg`.`ID_base`) AS `period_group_ID`,
        ((`po`.`period_id` * `pt_p`.`ID_multiplier`) + `pt_p`.`ID_base`) AS `period_ID`,
        ((`ip`.`global_period_group_id` * `pt_e`.`ID_multiplier`) + `pt_e`.`ID_base`) AS `period_entity_ID`,
        ((`pst`.`period_status_type_id` * `pt_pst`.`ID_multiplier`) + `pt_pst`.`ID_base`) AS `period_status_type_ID`,
        `po`.`period_id` AS `period_native_id`,
        `po`.`recurrence_index` AS `recurrence_index`,
        0 AS `location_ID`,
        0 AS `item_ID`,
        0 AS `user_ID`,
        `pst`.`period_status_type_id` AS `period_status_type_native_id`,
        `pst`.`name` AS `period_status_type_name`,
        `po`.`datetime_period_item_start` AS `datetime_period_item_start`,
        `po`.`datetime_period_item_end` AS `datetime_period_item_end`,
        CAST(`po`.`blocked` AS UNSIGNED) AS `blocked`,
        CAST(`ip`.`enabled` AS UNSIGNED) AS `enabled`
    FROM
        (((((((((`wp_cb2_cache_perioditems` `po`
        JOIN `wp_cb2_periods` `p` ON ((`po`.`period_id` = `p`.`period_id`)))
        JOIN `wp_cb2_period_group_period` `pgp` ON ((`pgp`.`period_id` = `p`.`period_id`)))
        JOIN `wp_cb2_global_period_groups` `ip` ON ((`ip`.`period_group_id` = `pgp`.`period_group_id`)))
        JOIN `wp_cb2_period_status_types` `pst` ON ((`ip`.`period_status_type_id` = `pst`.`period_status_type_id`)))
        JOIN `wp_cb2_post_types` `pt_pi`)
        JOIN `wp_cb2_post_types` `pt_p`)
        JOIN `wp_cb2_post_types` `pt_pg`)
        JOIN `wp_cb2_post_types` `pt_e`)
        JOIN `wp_cb2_post_types` `pt_pst`)
    WHERE
        ((4 = `pt_pi`.`post_type_id`)
            AND (1 = `pt_p`.`post_type_id`)
            AND (2 = `pt_pg`.`post_type_id`)
            AND (12 = `pt_e`.`post_type_id`)
            AND (8 = `pt_pst`.`post_type_id`))
    UNION ALL SELECT
        (((`ip`.`location_period_group_id` * `pt_pi`.`ID_multiplier`) + `po`.`recurrence_index`) + `pt_pi`.`ID_base`) AS `ID`,
        1 AS `post_author`,
        `po`.`datetime_period_item_start` AS `post_date`,
        `po`.`datetime_period_item_start` AS `post_date_gmt`,
        '' AS `post_content`,
        CONVERT( CONCAT(`loc`.`post_title`, ' - ', `pst`.`name`) USING UTF8) AS `post_title`,
        '' AS `post_excerpt`,
        IF(`ip`.`enabled`, 'publish', 'trash') AS `post_status`,
        'closed' AS `comment_status`,
        'closed' AS `ping_status`,
        '' AS `post_password`,
        (((`ip`.`location_period_group_id` * `pt_pi`.`ID_multiplier`) + `po`.`recurrence_index`) + `pt_pi`.`ID_base`) AS `post_name`,
        '' AS `to_ping`,
        '' AS `pinged`,
        `po`.`datetime_period_item_end` AS `post_modified`,
        `po`.`datetime_period_item_end` AS `post_modified_gmt`,
        '' AS `post_content_filtered`,
        ((`po`.`period_id` * `pt_p`.`ID_multiplier`) + `pt_p`.`ID_base`) AS `post_parent`,
        '' AS `guid`,
        0 AS `menu_order`,
        `pt_pi`.`post_type` AS `post_type`,
        '' AS `post_mime_type`,
        0 AS `comment_count`,
        `ip`.`location_period_group_id` AS `timeframe_id`,
        `pt_e`.`post_type_id` AS `post_type_id`,
        'location' AS `period_group_type`,
        ((`pgp`.`period_group_id` * `pt_pg`.`ID_multiplier`) + `pt_pg`.`ID_base`) AS `period_group_ID`,
        ((`po`.`period_id` * `pt_p`.`ID_multiplier`) + `pt_p`.`ID_base`) AS `period_ID`,
        ((`ip`.`location_period_group_id` * `pt_e`.`ID_multiplier`) + `pt_e`.`ID_base`) AS `period_entity_ID`,
        ((`pst`.`period_status_type_id` * `pt_pst`.`ID_multiplier`) + `pt_pst`.`ID_base`) AS `period_status_type_ID`,
        `po`.`period_id` AS `period_native_id`,
        `po`.`recurrence_index` AS `recurrence_index`,
        `ip`.`location_ID` AS `location_ID`,
        0 AS `item_ID`,
        0 AS `user_ID`,
        `pst`.`period_status_type_id` AS `period_status_type_native_id`,
        `pst`.`name` AS `period_status_type_name`,
        `po`.`datetime_period_item_start` AS `datetime_period_item_start`,
        `po`.`datetime_period_item_end` AS `datetime_period_item_end`,
        CAST(`po`.`blocked` AS UNSIGNED) AS `blocked`,
        CAST(`ip`.`enabled` AS UNSIGNED) AS `enabled`
    FROM
        ((((((((((`wp_cb2_cache_perioditems` `po`
        JOIN `wp_cb2_periods` `p` ON ((`po`.`period_id` = `p`.`period_id`)))
        JOIN `wp_cb2_period_group_period` `pgp` ON ((`pgp`.`period_id` = `p`.`period_id`)))
        JOIN `wp_cb2_location_period_groups` `ip` ON ((`ip`.`period_group_id` = `pgp`.`period_group_id`)))
        JOIN `wp_posts` `loc` ON ((`ip`.`location_ID` = `loc`.`ID`)))
        JOIN `wp_cb2_period_status_types` `pst` ON ((`ip`.`period_status_type_id` = `pst`.`period_status_type_id`)))
        JOIN `wp_cb2_post_types` `pt_pi`)
        JOIN `wp_cb2_post_types` `pt_p`)
        JOIN `wp_cb2_post_types` `pt_pg`)
        JOIN `wp_cb2_post_types` `pt_e`)
        JOIN `wp_cb2_post_types` `pt_pst`)
    WHERE
        ((5 = `pt_pi`.`post_type_id`)
            AND (1 = `pt_p`.`post_type_id`)
            AND (2 = `pt_pg`.`post_type_id`)
            AND (13 = `pt_e`.`post_type_id`)
            AND (8 = `pt_pst`.`post_type_id`))
    UNION ALL SELECT
        (((`ip`.`timeframe_period_group_id` * `pt_pi`.`ID_multiplier`) + `po`.`recurrence_index`) + `pt_pi`.`ID_base`) AS `ID`,
        1 AS `post_author`,
        `po`.`datetime_period_item_start` AS `post_date`,
        `po`.`datetime_period_item_start` AS `post_date_gmt`,
        '' AS `post_content`,
        CONVERT( CONCAT(`loc`.`post_title`,
                ' - ',
                `itm`.`post_title`,
                ' - ',
                `pst`.`name`) USING UTF8) AS `post_title`,
        '' AS `post_excerpt`,
        IF(`ip`.`enabled`, 'publish', 'trash') AS `post_status`,
        'closed' AS `comment_status`,
        'closed' AS `ping_status`,
        '' AS `post_password`,
        (((`ip`.`timeframe_period_group_id` * `pt_pi`.`ID_multiplier`) + `po`.`recurrence_index`) + `pt_pi`.`ID_base`) AS `post_name`,
        '' AS `to_ping`,
        '' AS `pinged`,
        `po`.`datetime_period_item_end` AS `post_modified`,
        `po`.`datetime_period_item_end` AS `post_modified_gmt`,
        '' AS `post_content_filtered`,
        ((`po`.`period_id` * `pt_p`.`ID_multiplier`) + `pt_p`.`ID_base`) AS `post_parent`,
        '' AS `guid`,
        0 AS `menu_order`,
        `pt_pi`.`post_type` AS `post_type`,
        '' AS `post_mime_type`,
        0 AS `comment_count`,
        `ip`.`timeframe_period_group_id` AS `timeframe_id`,
        `pt_e`.`post_type_id` AS `post_type_id`,
        'timeframe' AS `period_group_type`,
        ((`pgp`.`period_group_id` * `pt_pg`.`ID_multiplier`) + `pt_pg`.`ID_base`) AS `period_group_ID`,
        ((`po`.`period_id` * `pt_p`.`ID_multiplier`) + `pt_p`.`ID_base`) AS `period_ID`,
        ((`ip`.`timeframe_period_group_id` * `pt_e`.`ID_multiplier`) + `pt_e`.`ID_base`) AS `period_entity_ID`,
        ((`pst`.`period_status_type_id` * `pt_pst`.`ID_multiplier`) + `pt_pst`.`ID_base`) AS `period_status_type_ID`,
        `po`.`period_id` AS `period_native_id`,
        `po`.`recurrence_index` AS `recurrence_index`,
        `ip`.`location_ID` AS `location_ID`,
        `ip`.`item_ID` AS `item_ID`,
        0 AS `user_ID`,
        `pst`.`period_status_type_id` AS `period_status_type_native_id`,
        `pst`.`name` AS `period_status_type_name`,
        `po`.`datetime_period_item_start` AS `datetime_period_item_start`,
        `po`.`datetime_period_item_end` AS `datetime_period_item_end`,
        CAST(`po`.`blocked` AS UNSIGNED) AS `blocked`,
        CAST(`ip`.`enabled` AS UNSIGNED) AS `enabled`
    FROM
        (((((((((((`wp_cb2_cache_perioditems` `po`
        JOIN `wp_cb2_periods` `p` ON ((`po`.`period_id` = `p`.`period_id`)))
        JOIN `wp_cb2_period_group_period` `pgp` ON ((`pgp`.`period_id` = `p`.`period_id`)))
        JOIN `wp_cb2_timeframe_period_groups` `ip` ON ((`ip`.`period_group_id` = `pgp`.`period_group_id`)))
        JOIN `wp_posts` `loc` ON ((`ip`.`location_ID` = `loc`.`ID`)))
        JOIN `wp_posts` `itm` ON ((`ip`.`item_ID` = `itm`.`ID`)))
        JOIN `wp_cb2_period_status_types` `pst` ON ((`ip`.`period_status_type_id` = `pst`.`period_status_type_id`)))
        JOIN `wp_cb2_post_types` `pt_pi`)
        JOIN `wp_cb2_post_types` `pt_p`)
        JOIN `wp_cb2_post_types` `pt_pg`)
        JOIN `wp_cb2_post_types` `pt_e`)
        JOIN `wp_cb2_post_types` `pt_pst`)
    WHERE
        ((6 = `pt_pi`.`post_type_id`)
            AND (1 = `pt_p`.`post_type_id`)
            AND (2 = `pt_pg`.`post_type_id`)
            AND (14 = `pt_e`.`post_type_id`)
            AND (8 = `pt_pst`.`post_type_id`))
    UNION ALL SELECT
        (((`ip`.`timeframe_user_period_group_id` * `pt_pi`.`ID_multiplier`) + `po`.`recurrence_index`) + `pt_pi`.`ID_base`) AS `ID`,
        1 AS `post_author`,
        `po`.`datetime_period_item_start` AS `post_date`,
        `po`.`datetime_period_item_start` AS `post_date_gmt`,
        '' AS `post_content`,
        CONVERT( CONCAT(`loc`.`post_title`,
                ' - ',
                `itm`.`post_title`,
                ' - ',
                `usr`.`user_login`,
                ' - ',
                `pst`.`name`) USING UTF8) AS `post_title`,
        '' AS `post_excerpt`,
        IF(`ip`.`enabled`, 'publish', 'trash') AS `post_status`,
        'closed' AS `comment_status`,
        'closed' AS `ping_status`,
        '' AS `post_password`,
        (((`ip`.`timeframe_user_period_group_id` * `pt_pi`.`ID_multiplier`) + `po`.`recurrence_index`) + `pt_pi`.`ID_base`) AS `post_name`,
        '' AS `to_ping`,
        '' AS `pinged`,
        `po`.`datetime_period_item_end` AS `post_modified`,
        `po`.`datetime_period_item_end` AS `post_modified_gmt`,
        '' AS `post_content_filtered`,
        ((`po`.`period_id` * `pt_p`.`ID_multiplier`) + `pt_p`.`ID_base`) AS `post_parent`,
        '' AS `guid`,
        0 AS `menu_order`,
        `pt_pi`.`post_type` AS `post_type`,
        '' AS `post_mime_type`,
        0 AS `comment_count`,
        `ip`.`timeframe_user_period_group_id` AS `timeframe_id`,
        `pt_e`.`post_type_id` AS `post_type_id`,
        'user' AS `period_group_type`,
        ((`pgp`.`period_group_id` * `pt_pg`.`ID_multiplier`) + `pt_pg`.`ID_base`) AS `period_group_ID`,
        ((`po`.`period_id` * `pt_p`.`ID_multiplier`) + `pt_p`.`ID_base`) AS `period_ID`,
        ((`ip`.`timeframe_user_period_group_id` * `pt_e`.`ID_multiplier`) + `pt_e`.`ID_base`) AS `period_entity_ID`,
        ((`pst`.`period_status_type_id` * `pt_pst`.`ID_multiplier`) + `pt_pst`.`ID_base`) AS `period_status_type_ID`,
        `po`.`period_id` AS `period_native_id`,
        `po`.`recurrence_index` AS `recurrence_index`,
        `ip`.`location_ID` AS `location_ID`,
        `ip`.`item_ID` AS `item_ID`,
        `ip`.`user_ID` AS `user_ID`,
        `pst`.`period_status_type_id` AS `period_status_type_native_id`,
        `pst`.`name` AS `period_status_type_name`,
        `po`.`datetime_period_item_start` AS `datetime_period_item_start`,
        `po`.`datetime_period_item_end` AS `datetime_period_item_end`,
        CAST(`po`.`blocked` AS UNSIGNED) AS `blocked`,
        CAST(`ip`.`enabled` AS UNSIGNED) AS `enabled`
    FROM
        ((((((((((((`wp_cb2_cache_perioditems` `po`
        JOIN `wp_cb2_periods` `p` ON ((`po`.`period_id` = `p`.`period_id`)))
        JOIN `wp_cb2_period_group_period` `pgp` ON ((`pgp`.`period_id` = `p`.`period_id`)))
        JOIN `wp_cb2_timeframe_user_period_groups` `ip` ON ((`ip`.`period_group_id` = `pgp`.`period_group_id`)))
        JOIN `wp_posts` `loc` ON ((`ip`.`location_ID` = `loc`.`ID`)))
        JOIN `wp_posts` `itm` ON ((`ip`.`item_ID` = `itm`.`ID`)))
        JOIN `wp_users` `usr` ON ((`ip`.`user_ID` = `usr`.`ID`)))
        JOIN `wp_cb2_period_status_types` `pst` ON ((`ip`.`period_status_type_id` = `pst`.`period_status_type_id`)))
        JOIN `wp_cb2_post_types` `pt_pi`)
        JOIN `wp_cb2_post_types` `pt_p`)
        JOIN `wp_cb2_post_types` `pt_pg`)
        JOIN `wp_cb2_post_types` `pt_e`)
        JOIN `wp_cb2_post_types` `pt_pst`)
    WHERE
        ((7 = `pt_pi`.`post_type_id`)
            AND (1 = `pt_p`.`post_type_id`)
            AND (2 = `pt_pg`.`post_type_id`)
            AND (15 = `pt_e`.`post_type_id`)
            AND (8 = `pt_pst`.`post_type_id`))
