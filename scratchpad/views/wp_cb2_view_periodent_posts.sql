CREATE
    ALGORITHM = UNDEFINED
    DEFINER = `commonsbooking_2`@`%`
    SQL SECURITY DEFINER
VIEW `wp_cb2_view_periodent_posts` AS
    SELECT
        ((`p`.`timeframe_id` * `pt_e`.`ID_multiplier`) + `pt_e`.`ID_base`) AS `ID`,
        1 AS `post_author`,
        '2018-01-01' AS `post_date`,
        '2018-01-01' AS `post_date_gmt`,
        '' AS `post_content`,
        `p`.`name` AS `post_title`,
        '' AS `post_excerpt`,
        IF(`p`.`enabled`, 'publish', 'trash') AS `post_status`,
        'closed' AS `comment_status`,
        'closed' AS `ping_status`,
        '' AS `post_password`,
        (`p`.`timeframe_id` + `pt_e`.`ID_base`) AS `post_name`,
        '' AS `to_ping`,
        '' AS `pinged`,
        '2018-01-01' AS `post_modified`,
        '2018-01-01' AS `post_modified_gmt`,
        '' AS `post_content_filtered`,
        0 AS `post_parent`,
        '' AS `guid`,
        0 AS `menu_order`,
        CONCAT('periodent-', `p`.`period_group_type`) AS `post_type`,
        '' AS `post_mime_type`,
        0 AS `comment_count`,
        `p`.`timeframe_id` AS `timeframe_id`,
        IFNULL(`p`.`location_ID`, 0) AS `location_ID`,
        IFNULL(`p`.`item_ID`, 0) AS `item_ID`,
        IFNULL(`p`.`user_ID`, 0) AS `user_ID`,
        `p`.`title` AS `title`,
        (`p`.`period_group_id` + `pt_pg`.`ID_base`) AS `period_group_ID`,
        (`p`.`period_status_type_id` + `pt_pst`.`ID_base`) AS `period_status_type_ID`,
        `p`.`period_status_type_id` AS `period_status_type_native_id`,
        `pst`.`name` AS `period_status_type_name`,
        (SELECT
                GROUP_CONCAT((`commonsbooking_2`.`wp_cb2_period_group_period`.`period_id` + `pt2`.`ID_base`)
                        SEPARATOR ',')
            FROM
                (`commonsbooking_2`.`wp_cb2_period_group_period`
                JOIN `commonsbooking_2`.`wp_cb2_post_types` `pt2` ON ((`pt2`.`post_type` = 'period')))
            WHERE
                (`commonsbooking_2`.`wp_cb2_period_group_period`.`period_group_id` = `p`.`period_group_id`)
            GROUP BY `commonsbooking_2`.`wp_cb2_period_group_period`.`period_group_id`) AS `period_IDs`,
        CAST(`p`.`enabled` AS UNSIGNED) AS `enabled`,
        ((`p_first`.`period_id` * `pt_p_first`.`ID_multiplier`) + `pt_p_first`.`ID_base`) AS `period_ID`,
        `p_first`.`datetime_part_period_start` AS `datetime_part_period_start`,
        `p_first`.`datetime_part_period_end` AS `datetime_part_period_end`,
        `p_first`.`recurrence_type` AS `recurrence_type`,
        `p_first`.`recurrence_frequency` AS `recurrence_frequency`,
        `p_first`.`datetime_from` AS `datetime_from`,
        `p_first`.`datetime_to` AS `datetime_to`,
        `p_first`.`recurrence_sequence` AS `recurrence_sequence`
    FROM
        ((((((((SELECT
            `ip`.`global_period_group_id` AS `timeframe_id`,
                `pg`.`name` AS `name`,
                CONVERT( `pg`.`name` USING UTF8) AS `title`,
                NULL AS `location_ID`,
                NULL AS `item_ID`,
                NULL AS `user_ID`,
                'global' AS `period_group_type`,
                1 AS `period_group_priority`,
                `ip`.`period_group_id` AS `period_group_id`,
                `ip`.`period_status_type_id` AS `period_status_type_id`,
                `ip`.`enabled` AS `enabled`
        FROM
            (`commonsbooking_2`.`wp_cb2_global_period_groups` `ip`
        JOIN `commonsbooking_2`.`wp_cb2_period_groups` `pg` ON ((`ip`.`period_group_id` = `pg`.`period_group_id`)))) UNION ALL SELECT
            `ip`.`location_period_group_id` AS `timeframe_ID`,
                `pg`.`name` AS `name`,
                CONVERT( CONCAT(`pg`.`name`, CONVERT( CONVERT( IF(LENGTH(`pg`.`name`), ' - ', '') USING UTF8) USING UTF8MB4), `loc`.`post_title`) USING UTF8) AS `title`,
                `ip`.`location_ID` AS `location_ID`,
                NULL AS `item_ID`,
                NULL AS `user_ID`,
                'location' AS `period_group_type`,
                2 AS `period_group_priority`,
                `ip`.`period_group_id` AS `period_group_id`,
                `ip`.`period_status_type_id` AS `period_status_type_id`,
                `ip`.`enabled` AS `enabled`
        FROM
            ((`commonsbooking_2`.`wp_cb2_location_period_groups` `ip`
        JOIN `commonsbooking_2`.`wp_cb2_period_groups` `pg` ON ((`ip`.`period_group_id` = `pg`.`period_group_id`)))
        JOIN `commonsbooking_2`.`wp_posts` `loc` ON ((`ip`.`location_ID` = `loc`.`ID`))) UNION ALL SELECT
            `ip`.`timeframe_period_group_id` AS `timeframe_ID`,
                `pg`.`name` AS `name`,
                CONVERT( CONCAT(`pg`.`name`, CONVERT( CONVERT( IF(LENGTH(`pg`.`name`), ' - ', '') USING UTF8) USING UTF8MB4), `loc`.`post_title`, ' - ', `itm`.`post_title`) USING UTF8) AS `title`,
                `ip`.`location_ID` AS `location_ID`,
                `ip`.`item_ID` AS `item_ID`,
                NULL AS `user_ID`,
                'timeframe' AS `period_group_type`,
                3 AS `period_group_priority`,
                `ip`.`period_group_id` AS `period_group_id`,
                `ip`.`period_status_type_id` AS `period_status_type_id`,
                `ip`.`enabled` AS `enabled`
        FROM
            (((`commonsbooking_2`.`wp_cb2_timeframe_period_groups` `ip`
        JOIN `commonsbooking_2`.`wp_cb2_period_groups` `pg` ON ((`ip`.`period_group_id` = `pg`.`period_group_id`)))
        JOIN `commonsbooking_2`.`wp_posts` `loc` ON ((`ip`.`location_ID` = `loc`.`ID`)))
        JOIN `commonsbooking_2`.`wp_posts` `itm` ON ((`ip`.`item_ID` = `itm`.`ID`))) UNION ALL SELECT
            `ip`.`timeframe_user_period_group_id` AS `timeframe_ID`,
                `pg`.`name` AS `name`,
                CONVERT( CONCAT(`pg`.`name`, CONVERT( CONVERT( IF(LENGTH(`pg`.`name`), ' - ', '') USING UTF8) USING UTF8MB4), `loc`.`post_title`, ' - ', `itm`.`post_title`, ' - ', `usr`.`user_login`) USING UTF8) AS `title`,
                `ip`.`location_ID` AS `location_ID`,
                `ip`.`item_ID` AS `item_ID`,
                `ip`.`user_ID` AS `user_ID`,
                'user' AS `period_group_type`,
                4 AS `period_group_priority`,
                `ip`.`period_group_id` AS `period_group_id`,
                `ip`.`period_status_type_id` AS `period_status_type_id`,
                `ip`.`enabled` AS `enabled`
        FROM
            ((((`commonsbooking_2`.`wp_cb2_timeframe_user_period_groups` `ip`
        JOIN `commonsbooking_2`.`wp_cb2_period_groups` `pg` ON ((`ip`.`period_group_id` = `pg`.`period_group_id`)))
        JOIN `commonsbooking_2`.`wp_posts` `loc` ON ((`ip`.`location_ID` = `loc`.`ID`)))
        JOIN `commonsbooking_2`.`wp_posts` `itm` ON ((`ip`.`item_ID` = `itm`.`ID`)))
        JOIN `commonsbooking_2`.`wp_users` `usr` ON ((`ip`.`user_ID` = `usr`.`ID`)))) `p`
        JOIN `commonsbooking_2`.`wp_cb2_period_status_types` `pst` ON ((`p`.`period_status_type_id` = `pst`.`period_status_type_id`)))
        JOIN `commonsbooking_2`.`wp_cb2_post_types` `pt_e` ON ((`pt_e`.`post_type` = CONVERT( CONVERT( CONCAT('periodent-', `p`.`period_group_type`) USING UTF8) USING UTF8MB4))))
        JOIN `commonsbooking_2`.`wp_cb2_post_types` `pt_pg` ON ((`pt_pg`.`post_type_id` = 2)))
        JOIN `commonsbooking_2`.`wp_cb2_post_types` `pt_pst` ON ((`pt_pst`.`post_type_id` = 8)))
        JOIN `commonsbooking_2`.`wp_cb2_post_types` `pt_p_first` ON ((`pt_p_first`.`post_type_id` = 1)))
        JOIN `commonsbooking_2`.`wp_cb2_periods` `p_first` ON ((`p_first`.`period_id` = (SELECT
                `ps2`.`period_id`
            FROM
                `commonsbooking_2`.`wp_cb2_period_group_period` `ps2`
            WHERE
                (`ps2`.`period_group_id` = `p`.`period_group_id`)
            ORDER BY `ps2`.`period_id`
            LIMIT 1))))
