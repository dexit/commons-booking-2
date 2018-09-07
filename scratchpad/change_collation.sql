SET character_set_client = 'utf8mb4';
SET collation_connection = 'utf8mb4_unicode_ci';
ALTER DATABASE commonsbooking_2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
# otherwise zeros in datetime defaults will crash
SET sql_mode = ''; 
# current collation setup
show variables like 'char%';
show variables like 'coll%';

# to view the collation on a column
show full columns from wp_posts;

# getting the view definition without collation issues
# MySQL Clients will often crash when trying to show the view details
select VIEW_DEFINITION from INFORMATION_SCHEMA.VIEWS where TABLE_NAME = 'wp_cb2_view_periodentmeta' limit 1;

# change everything
ALTER SCHEMA xsearchs_commonsbooking DEFAULT CHARACTER SET utf8mb4 DEFAULT COLLATE utf8mb4_unicode_520_ci;
alter table wp_users convert to character set utf8mb4 collate utf8mb4_unicode_520_ci;
alter table wp_usermeta convert to character set utf8mb4 collate utf8mb4_unicode_520_ci;
alter table wp_terms convert to character set utf8mb4 collate utf8mb4_unicode_520_ci;
alter table wp_termmeta convert to character set utf8mb4 collate utf8mb4_unicode_520_ci;
alter table wp_term_taxonomy convert to character set utf8mb4 collate utf8mb4_unicode_520_ci;
alter table wp_term_relationships convert to character set utf8mb4 collate utf8mb4_unicode_520_ci;
alter table wp_posts convert to character set utf8mb4 collate utf8mb4_unicode_520_ci;
alter table wp_postmeta convert to character set utf8mb4 collate utf8mb4_unicode_520_ci;
alter table wp_options convert to character set utf8mb4 collate utf8mb4_unicode_520_ci;
alter table wp_links convert to character set utf8mb4 collate utf8mb4_unicode_520_ci;
alter table wp_comments convert to character set utf8mb4 collate utf8mb4_unicode_520_ci;
alter table wp_commentmeta convert to character set utf8mb4 collate utf8mb4_unicode_520_ci;
alter table wp_cb2_timeframe_user_period_groups convert to character set utf8mb4 collate utf8mb4_unicode_520_ci;
alter table wp_cb2_timeframe_period_groups convert to character set utf8mb4 collate utf8mb4_unicode_520_ci;
alter table wp_cb2_timeframe_options convert to character set utf8mb4 collate utf8mb4_unicode_520_ci;
alter table wp_cb2_post_types convert to character set utf8mb4 collate utf8mb4_unicode_520_ci;
alter table wp_cb2_periods convert to character set utf8mb4 collate utf8mb4_unicode_520_ci;
alter table wp_cb2_period_status_types convert to character set utf8mb4 collate utf8mb4_unicode_520_ci;
alter table wp_cb2_period_groups convert to character set utf8mb4 collate utf8mb4_unicode_520_ci;
alter table wp_cb2_period_group_period convert to character set utf8mb4 collate utf8mb4_unicode_520_ci;
alter table wp_cb2_location_period_groups convert to character set utf8mb4 collate utf8mb4_unicode_520_ci;
alter table wp_cb2_global_period_groups convert to character set utf8mb4 collate utf8mb4_unicode_ci;
