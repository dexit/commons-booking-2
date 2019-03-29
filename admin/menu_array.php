<?php
global $wpdb;

// TODO: convert this $menu_interface specification to JSON
$menu_interface = array(
	// --------------------------------------------------- Main Menu
	// Dashboard is the main decleration in WP_Admin_Integration.php
	'cb2-calendar' => array(
		'page_title'    => 'Calendar',
		'function'      => 'cb2_calendar',
	),
	'cb2-items'         => array(
		'page_title' => 'Items',
		'wp_query_args' => 'post_type=item',
		'count'         => "select count(*) from {$wpdb->prefix}posts where post_type='item' and post_status='publish'",
	),
	'cb2-locations'     => array(
		'page_title'     => 'Locations',
		'wp_query_args'  => 'post_type=location',
		'count'          => "select count(*) from {$wpdb->prefix}posts where post_type='location' and post_status='publish'",
	),
	'cb2-bookings'    => array(
		'page_title'    => 'Bookings %(for)% %location_ID%',
		'menu_title'    => 'Bookings',
		'wp_query_args' => 'post_type=periodent-user&period_status_type_ID=' . CB2_PeriodStatusType_Booked::bigID(),
		'count'         => "select count(*) from {$wpdb->prefix}cb2_view_periodinst_posts `po` where ((`po`.`datetime_period_inst_start` > now()) and (`po`.`post_type_id` = 15) and (`po`.`period_status_type_native_id` = 2) and (`po`.`enabled` = 1) and (`po`.`blocked` = 0)) GROUP BY `po`.`timeframe_id` , `po`.`period_native_id`",
		'count_class'   => 'ok',
	),

	// --------------------------------------------------- Secondary list
	'cb2-timeframes'    => array(
		'indent'        => 1,
		'menu_visible'  => FALSE,
		'page_title'    => 'Item availibility %(for)% %location_ID%',
		'menu_title'    => 'Item Availibility',
		'wp_query_args' => 'post_type=periodent-timeframe&post_title=Availability %(for)% %item_ID%&period_status_type_ID=' . CB2_PeriodStatusType_PickupReturn::bigID(),
		'count'         => "select count(*) from {$wpdb->prefix}cb2_timeframe_period_groups where enabled = 1 and period_status_type_id = " . CB2_PeriodStatusType_PickupReturn::$id,
	),
	'cb2-opening-hours' => array(
		'indent'        => 1,
		'menu_visible'  => FALSE,
		'page_title'    => 'Opening Hours %(for)% %location_ID%',
		'menu_title'    => 'Opening Hours',
		'wp_query_args' => 'post_type=periodent-location&recurrence_type=D&recurrence_type_show=no&post_title=Opening Hours %(for)% %location_ID%&period_status_type_ID=' . CB2_PeriodStatusType_Open::bigID(),
		'count'         => "select count(*) from {$wpdb->prefix}cb2_location_period_groups where enabled = 1 and period_status_type_id = " . CB2_PeriodStatusType_Open::$id,
	),
	'cb2-repairs'       => array(
		'indent'        => 1,
		'menu_visible'  => FALSE,
		'page_title'    => 'Repairs %(for)% %location_ID% %item_ID%',
		'menu_title'    => 'Repairs',
		'wp_query_args' => 'post_type=periodent-user&period_status_type_ID=' . CB2_PeriodStatusType_Repair::bigID(),
		'count'         => "select count(*) from {$wpdb->prefix}cb2_timeframe_user_period_groups where enabled = 1 and period_status_type_id = " . CB2_PeriodStatusType_Repair::$id,
		'count_class'   => 'warning',
	),
	'cb2-holidays'      => array(
		'page_title'    => 'Holidays %(for)% %location_ID%',
		'menu_visible'  => FALSE,
		'menu_title'    => 'Holidays',
		'wp_query_args' => 'post_type=periodent-global&post_title=Holidays&period_status_type_ID=' . CB2_PeriodStatusType_Holiday::bigID(),
		'description'   => 'Edit the global holidays, and holidays for specific locations.',
		'count'         => "select count(*) from {$wpdb->prefix}cb2_global_period_groups where enabled = 1 and period_status_type_id = " . CB2_PeriodStatusType_Holiday::$id,
	),

	// --------------------------------------------------- Advanced
	'cb2-roles' => array(
		'page_title'    => 'Roles and Capabilities',
		'function'      => 'cb2_roles',
		'first'         => TRUE,
		'advanced'      => TRUE,
		'capability'    => 'manage_options',
	),
	'cb2-reflection' => array(
		'page_title'    => 'Reflection',
		'function'      => 'cb2_reflection',
		'advanced'      => TRUE,
		'capability'    => 'manage_options',
	),
	'cb2-gui-setup' => array(
		'page_title'    => 'GUI setup',
		'function'      => 'cb2_gui_setup',
		'advanced'      => TRUE,
		'capability'    => 'manage_options',
	),

	'cb2-periods' => array(
		'page_title'    => 'Periods',
		'wp_query_args' => 'post_type=period',
		'count'         => "select count(*) from {$wpdb->prefix}cb2_periods",
		'advanced'      => TRUE,
	),
	'cb2-period-globals' => array(
		'indent'        => 1,
		'page_title'    => 'Globals',
		'wp_query_args' => 'post_type=periodent-global',
		'count'         => "select count(*) from {$wpdb->prefix}cb2_global_period_groups",
		'advanced'      => TRUE,
	),
	'cb2-period-locations' => array(
		'indent'        => 1,
		'page_title'    => 'Locations',
		'wp_query_args' => 'post_type=periodent-location',
		'count'         => "select count(*) from {$wpdb->prefix}cb2_location_period_groups",
		'advanced'      => TRUE,
	),
	'cb2-period-timeframes' => array(
		'indent'        => 1,
		'page_title'    => 'Timeframes',
		'wp_query_args' => 'post_type=periodent-timeframe',
		'count'         => "select count(*) from {$wpdb->prefix}cb2_timeframe_period_groups",
		'advanced'      => TRUE,
	),
	'cb2-period-users' => array(
		'indent'        => 1,
		'page_title'    => 'Users',
		'wp_query_args' => 'post_type=periodent-user',
		'count'         => "select count(*) from {$wpdb->prefix}cb2_timeframe_user_period_groups",
		'advanced'      => TRUE,
	),
	'cb2-period-groups' => array(
		'page_title' => 'Period Groups',
		'wp_query_args' => 'post_type=periodgroup',
		'count'         => "select count(*) from {$wpdb->prefix}cb2_period_groups",
		'advanced'      => TRUE,
	),
	'cb2-periodstatustypes' => array(
		'page_title' => 'Period Status Types',
		'wp_query_args' => 'post_type=periodstatustype',
		'count'         => "select count(*) from {$wpdb->prefix}cb2_period_status_types",
		'advanced'      => TRUE,
	),

	// post-new.php (setup) => edit-form-advanced.php (form)
	// The following line directly accesses the plugin post-new.php
	// However, post-new.php is loaded directly WITHOUT calling the hook function
	// so we cannot set titles and things
	// $wp_admin_dir = 'commons-booking-2/wp-admin';
	// add_submenu_page( CB2_MENU_SLUG, 'Add New', NULL, $capability_default, '/commons-booking-2/admin/post-new.php' );
	// Sending through ?post_type seems to prevent the submenu_page from working
	// This hook, in combination with the capability in the add_submenu_page() call
	// allows the page to load
	'cb2-post-new' => array(
		'page_title'    => 'Add New',
		'function'      => 'cb2_settings_post_new',
		'advanced'      => TRUE,
		'capability'    => 'edit_posts',
	),
	'cb2-post-edit' => array(
		'page_title'    => 'Edit Post',
		'function'      => 'cb2_settings_post_edit',
		'advanced'      => TRUE,
		'capability'    => 'edit_posts',
	),
);
