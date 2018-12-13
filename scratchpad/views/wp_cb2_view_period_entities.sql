CREATE
    ALGORITHM = UNDEFINED
    DEFINER = `commonsbooking_2`@`%`
    SQL SECURITY DEFINER
VIEW `wp_cb2_view_period_entities` AS
    SELECT
        `ip`.`global_period_group_id` AS `timeframe_id`,
        `pg`.`name` AS `name`,
        `pg`.`name` AS `title`,
        NULL AS `location_ID`,
        NULL AS `item_ID`,
        NULL AS `user_ID`,
        'global' AS `period_group_type`,
        1 AS `period_group_priority`,
        `ip`.`period_group_id` AS `period_group_id`,
        `ip`.`period_status_type_id` AS `period_status_type_id`,
        `ip`.`enabled` AS `enabled`
    FROM
        (`wp_cb2_global_period_groups` `ip`
        JOIN `wp_cb2_period_groups` `pg` ON ((`ip`.`period_group_id` = `pg`.`period_group_id`)))
    UNION ALL SELECT
        `ip`.`location_period_group_id` AS `timeframe_ID`,
        `pg`.`name` AS `name`,
        CONCAT(`pg`.`name`,
                CONVERT( IF(LENGTH(`pg`.`name`), ' - ', '') USING UTF8MB4),
                `loc`.`post_title`) AS `title`,
        `ip`.`location_ID` AS `location_ID`,
        NULL AS `item_ID`,
        NULL AS `user_ID`,
        'location' AS `period_group_type`,
        2 AS `period_group_priority`,
        `ip`.`period_group_id` AS `period_group_id`,
        `ip`.`period_status_type_id` AS `period_status_type_id`,
        `ip`.`enabled` AS `enabled`
    FROM
        ((`wp_cb2_location_period_groups` `ip`
        JOIN `wp_cb2_period_groups` `pg` ON ((`ip`.`period_group_id` = `pg`.`period_group_id`)))
        JOIN `wp_posts` `loc` ON ((`ip`.`location_ID` = `loc`.`ID`)))
    UNION ALL SELECT
        `ip`.`timeframe_period_group_id` AS `timeframe_ID`,
        `pg`.`name` AS `name`,
        CONCAT(`pg`.`name`,
                CONVERT( IF(LENGTH(`pg`.`name`), ' - ', '') USING UTF8MB4),
                `loc`.`post_title`,
                ' - ',
                `itm`.`post_title`) AS `title`,
        `ip`.`location_ID` AS `location_ID`,
        `ip`.`item_ID` AS `item_ID`,
        NULL AS `user_ID`,
        'timeframe' AS `period_group_type`,
        3 AS `period_group_priority`,
        `ip`.`period_group_id` AS `period_group_id`,
        `ip`.`period_status_type_id` AS `period_status_type_id`,
        `ip`.`enabled` AS `enabled`
    FROM
        (((`wp_cb2_timeframe_period_groups` `ip`
        JOIN `wp_cb2_period_groups` `pg` ON ((`ip`.`period_group_id` = `pg`.`period_group_id`)))
        JOIN `wp_posts` `loc` ON ((`ip`.`location_ID` = `loc`.`ID`)))
        JOIN `wp_posts` `itm` ON ((`ip`.`item_ID` = `itm`.`ID`)))
    UNION ALL SELECT
        `ip`.`timeframe_user_period_group_id` AS `timeframe_ID`,
        `pg`.`name` AS `name`,
        CONCAT(`pg`.`name`,
                CONVERT( IF(LENGTH(`pg`.`name`), ' - ', '') USING UTF8MB4),
                `loc`.`post_title`,
                ' - ',
                `itm`.`post_title`,
                ' - ',
                `usr`.`user_login`) AS `title`,
        `ip`.`location_ID` AS `location_ID`,
        `ip`.`item_ID` AS `item_ID`,
        `ip`.`user_ID` AS `user_ID`,
        'user' AS `period_group_type`,
        4 AS `period_group_priority`,
        `ip`.`period_group_id` AS `period_group_id`,
        `ip`.`period_status_type_id` AS `period_status_type_id`,
        `ip`.`enabled` AS `enabled`
    FROM
        ((((`wp_cb2_timeframe_user_period_groups` `ip`
        JOIN `wp_cb2_period_groups` `pg` ON ((`ip`.`period_group_id` = `pg`.`period_group_id`)))
        JOIN `wp_posts` `loc` ON ((`ip`.`location_ID` = `loc`.`ID`)))
        JOIN `wp_posts` `itm` ON ((`ip`.`item_ID` = `itm`.`ID`)))
        JOIN `wp_users` `usr` ON ((`ip`.`user_ID` = `usr`.`ID`)))
