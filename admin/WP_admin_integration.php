<?php
/* This Admin integration is a copy of relevant WordPress 4.9.7 wp-admin/files
 * Some minimal changes have been made and marked as // CB2/<author>: <explanation>
 * For example: for greater control over <h1>titles</h1> and button actions like Add New...
 *
 * WP_Query_integration.php hooks in to inserting and updating events
 * to write to the primary native CB2 database table for the post_type.
 * And hooks in to pre-insert events to ensure required related rows are created.
 * For example: when a timeframe is created, its period_group and period must be created first
 * and, after creation, the period(s) must be 1-many linked to the relevant period_group
 */

/*
function cb2_cmb2_group_wrap_attributes( $group_wrap_attributes, $field_group ) {
	// Allowing context closing of metaboxes
	$group_wrap_attributes['class'] .= ' closed';
	return $group_wrap_attributes;
}
add_filter( 'cmb2_group_wrap_attributes', 'cb2_cmb2_group_wrap_attributes', 10, 2 );
*/

// TODO: this was removed from edit-form-advanced.php and needs to be re-implemented
// added by fleg
// function to render the availability options form
// @TODO: Saving, only open on link
// echo ("<h2>AVAILABILITY OPTIONS</h2>");
// echo CB2_Settings::do_availability_options_metaboxes();


function cb2_wp_redirect( $location, $status ) {
	if ( CB2_DEBUG_SAVE ) {
		print( '<hr/><h2>CB2_DEBUG_SAVE wp_redirect() caught</h2>' );
		krumo( $_POST );
		print( "<b>wp_redirect</b>( <a href='$location'>$location</a>, <b>$status</b> )" );
		$location = FALSE; // Prevent actual redirect
	} else if ( WP_DEBUG ) {
		// We will have had debug information already
		// and a header after output error
		// so we need to JS redirect instead
		$esc_location = str_replace( "'", "\\\'", $location );
		print( "Using JavaScript redirect because WP_DEBUG has already output debug info..." );
		print( "<script>document.location='$esc_location';</script>" );
	}
	return $location;
}
add_filter( 'wp_redirect', 'cb2_wp_redirect', 10, 2 );

function cb2_admin_pages() {
	// %token% replacement happens on ALL parameters.
	// If any tokens are replaced then %(x)% => x texts are included.
	// All %token_ID% ending in _ID are converted to posts and the post_title inserted instead of the ID
	global $wpdb;

	$menu_interface = array(
		'cb2-holidays'      => array(
			'page_title'    => 'Holidays %(for)% %location_ID%',
			'menu_title'    => 'Holidays',
			'wp_query_args' => 'post_type=periodent-global&period_status_type_id=6&post_title=Holidays',
			'description'   => 'Edit the global holidays, and holidays for specific locations.',
			'count'         => "select count(*) from {$wpdb->prefix}cb2_global_period_groups where enabled = 1 and period_status_type_id = " . CB2_PeriodStatusType_Holiday::$id,
		),
		'cb2-items'         => array(
			'page_title' => 'Items',
			'wp_query_args' => 'post_type=item',
			'count'         => "select count(*) from {$wpdb->prefix}posts where post_type='item' and post_status='publish'",
		),
		'cb2-repairs'       => array(
			'indent'        => 1,
			'menu_visible'  => FALSE,
			'page_title'    => 'Repairs %(for)% %location_ID% %item_ID%',
			'menu_title'    => 'Repairs',
			'wp_query_args' => 'post_type=periodent-user&period_status_type_id=5',
			'count'         => "select count(*) from {$wpdb->prefix}cb2_timeframe_user_period_groups where enabled = 1 and period_status_type_id = " . CB2_PeriodStatusType_Repair::$id,
			'count_class'   => 'warning',
		),
		'cb2-locations'     => array(
			'page_title'     => 'Locations',
			'wp_query_args'  => 'post_type=location',
			'count'          => "select count(*) from {$wpdb->prefix}posts where post_type='location' and post_status='publish'",
		),
		'cb2-opening-hours' => array(
			'indent'        => 1,
			'menu_visible'  => FALSE,
			'page_title'    => 'Opening Hours %(for)% %location_ID%',
			'menu_title'    => 'Opening Hours',
			'wp_query_args' => 'post_type=periodent-location&recurrence_type=D&recurrence_type_show=no&period_status_type_id=4&post_title=Opening Hours %(for)% %location_ID%',
			'count'         => "select count(*) from {$wpdb->prefix}cb2_location_period_groups where enabled = 1 and period_status_type_id = " . CB2_PeriodStatusType_Open::$id,
		),
		'cb2-timeframes'    => array(
			'indent'        => 1,
			'menu_visible'  => FALSE,
			'page_title'    => 'Item availibility %(for)% %location_ID%',
			'menu_title'    => 'Item Availibility',
			'wp_query_args' => 'post_type=periodent-timeframe&period_status_type_id=1&post_title=Availability %(for)% %item_ID%',
			'count'         => "select count(*) from {$wpdb->prefix}cb2_timeframe_period_groups where enabled = 1 and period_status_type_id = " . CB2_PeriodStatusType_Available::$id,
		),
		'cb2-bookings'    => array(
			'indent'        => 1,
			'menu_visible'  => FALSE,
			'page_title'    => 'Bookings %(for)% %location_ID%',
			'menu_title'    => 'Bookings',
			'wp_query_args' => 'post_type=periodent-user&period_status_type_id=2',
			'count'         => "select count(*) from {$wpdb->prefix}cb2_view_perioditem_posts `po` where ((`po`.`datetime_period_item_start` > now()) and (`po`.`post_type_id` = 15) and (`po`.`period_status_type_native_id` = 2) and (`po`.`enabled` = 1) and (`po`.`blocked` = 0)) GROUP BY `po`.`timeframe_id` , `po`.`period_native_id`",
			'count_class'   => 'ok',
		),
		'cb2-calendar' => array(
			'page_title'    => 'Calendar',
			'function'      => 'cb2_calendar',
		),

		// Advanced
		'cb2-admin' => array(
			'page_title'    => 'Admin',
			'function'      => 'cb2_admin_page',
			'first'         => TRUE,
			'advanced'      => TRUE,
		),
		'cb2-reflection' => array(
			'page_title'    => 'Reflection',
			'function'      => 'cb2_reflection',
			'first'         => TRUE,
			'advanced'      => TRUE,
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
		),
		'cb2-post-edit' => array(
			'page_title'    => 'Edit Post',
			'function'      => 'cb2_settings_post_edit',
			'advanced'      => TRUE,
		),
	);

	return $menu_interface;
}

function cb2_metaboxes() {
	foreach ( CB2_PostNavigator::post_type_classes() as $post_type => $Class ) {
		if ( method_exists( $Class, 'metaboxes' ) ) {
			foreach ( $Class::metaboxes() as $i => $metabox ) {
				$metabox['id']           = "{$Class}_metabox_{$i}";
				$metabox['object_types'] = array( $post_type );
				$metabox['priority']     = 'low'; // Under the standard boxes

				if ( WP_DEBUG ) {
					// Extended checks
					foreach ( $metabox['fields'] as $field ) {
						$name = ( isset( $field['name'] ) ? $field['name'] : '<no field name>' );
						switch ( $field['type'] ) {
							case 'text_date':
							case 'text_datetime_timestamp':
								if ( $field['date_format'] != CB2_Database::$database_date_format )
									throw new Exception( "[$name] metabox field needs the CB2_Database::\$database_date_format" );
								break;
						}
					}
				}

				new_cmb2_box( $metabox );
			}
		}
	}
}
add_action( 'cmb2_admin_init', 'cb2_metaboxes', 1 );

function cb2_post_row_actions( $actions, $post ) {
	global $wpdb;

	$post_type  = $post->post_type;
	$post_title = htmlspecialchars( $post->post_title );
	if ( $Class = CB2_PostNavigator::post_type_Class( $post_type ) ) {
		CB2_Query::get_metadata_assign( $post );
		$cb2_post = CB2_Query::ensure_correct_class( $post );

		// Move all edit actions to our custom screens
		if ( isset( $actions['edit'] ) )
			$actions['edit'] = "<a href='admin.php?page=cb2-post-edit&post=$cb2_post->ID&post_type=$cb2_post->post_type&action=edit' aria-label='Edit &#8220;$post_title&#8221;'>Edit</a>";

		// Remove QuickEdit quick-edit
		if ( isset( $actions['inline hide-if-no-js'] ) ) unset( $actions['inline hide-if-no-js'] );

		if ( ! $cb2_post->can_trash() ) {
			// Change Trash to delete if no enabled field
			unset( $actions['trash'] );
			if ( $cb2_post->can_delete() ) {
				$delete_text       = __( 'Delete Permanently' );
				$page              = $_GET['page'];
				$do_action         = "$Class::delete";
				$delete_link       = "admin.php?page=$page&post=$cb2_post->ID&action=delete";
				$actions['delete'] = "<a class='cb2-todo' style='color:red;' href='$delete_link'>$delete_text</a>";
			}
		}

		if ( $cb2_post instanceof CB2_PostNavigator && method_exists( $cb2_post, 'row_actions' ) ) {
			$row_actions = ( isset( $_GET['row_actions'] ) ? explode( ',', $_GET['row_actions'] ) : array() );
			$cb2_post->row_actions( $actions, $cb2_post, $row_actions );
		}

		if ( basename( $_SERVER['PHP_SELF'] ) == 'admin.php' && isset( $_GET[ 'page' ] ) ) {
			$page        = $_GET[ 'page' ];
			$admin_pages = cb2_admin_pages();
			if ( isset( $admin_pages[$page] ) && isset( $admin_pages[$page]['actions'] ) ) {
				$action_string = $admin_pages[$page]['actions'];
				if ( $action_string ) {
					$new_actions = explode( ',', $action_string );
					foreach ( $new_actions as $new_action ) {
						foreach ( $cb2_post as $name => $value ) {
							if ( strstr( $new_action, "%$name%" ) !== FALSE )
								$new_action = str_replace( "%$name%", $value, $new_action );
						}
						array_push( $actions, $new_action );
					}
				}
			}
		}
	}

	return $actions;
}
add_filter( 'post_row_actions', 'cb2_post_row_actions', 10, 2 );

function cb2_admin_views( $views ) {
	$page       = $_GET['page'];
	$all_text   = __( 'All' );
	$trash_text = __( 'Trash' );
	$views = array(
		'all'   => "<a href='admin.php?page=$page&post_status=publish'>$all_text</span></a>",
		'trash' => "<a href='admin.php?page=$page&post_status=trash'>$trash_text</span></a>",
	);
	return $views;
}

function cb2_admin_views_remove( $views) {
	return array();
}
add_filter( 'views_periodent-global',    'cb2_admin_views' );
add_filter( 'views_periodent-location',  'cb2_admin_views' );
add_filter( 'views_periodent-timeframe', 'cb2_admin_views' );
add_filter( 'views_periodent-user',      'cb2_admin_views' );

add_filter( 'views_item',                'cb2_admin_views' );
add_filter( 'views_location',            'cb2_admin_views' );

add_filter( 'views_period',              'cb2_admin_views_remove' );
add_filter( 'views_periodgroup',         'cb2_admin_views_remove' );
add_filter( 'views_periodstatustype',    'cb2_admin_views_remove' );


function cb2_notification_bubble_in_admin_menu() {
  global $menu, $submenu;

  foreach ($menu as &$amenuitem) {
    if ( is_array($amenuitem) ) {
      $menuitem = &$amenuitem[0];
      $menuitem = preg_replace( '/\(([0-9]+)\)/', '<span class="update-plugins count-$1"><span class="update-count">$1</span></span>', $menuitem );
      $menuitem = preg_replace( '/\[([0-9]+)\]/', '<span class="menu-item-number count-$1">$1</span>', $menuitem );
    }
  }

  foreach ($submenu as $menu_name => &$menuitems) {
    $first = TRUE;
    foreach ($menuitems as &$amenuitem) {
      if ( is_array($amenuitem) ) {
        $menuitem = &$amenuitem[0];
        if ( $first ) {
          $menuitem = preg_replace( '/\\(([0-9]+)\\)|\\[([0-9]+)\\]/', '', $menuitem );
        } else {
          $menuitem = preg_replace( '/\\(([0-9]+)\\)/', '<span class="update-plugins count-$1"><span class="update-count">$1</span></span>', $menuitem );
          $menuitem = preg_replace( '/\\[([0-9]+)\\]/', '<span class="menu-item-number count-$1">$1</span>', $menuitem );
        }
      }
      $first = FALSE;
    }
  }
}
add_action('admin_menu', 'cb2_notification_bubble_in_admin_menu', 110 );

function cb2_custom_columns( $column ) {
	global $post, $wp_query;
	if ( $post ) {
		// We manually set the post here
		// because WP_List_Table DOES NOT use a normal WP_Query loop
		// so global $wp_query does not advance its post, or current_post
		// inner_loop() therefore fails to reset properly when running
		//   wp_reset_postdata()
		$wp_query->post = $post;

		$cb2_post = CB2_Query::ensure_correct_class( $post );
		if ( $cb2_post && method_exists( $cb2_post, 'custom_columns' ) )
			print( $cb2_post->custom_columns( $column ) );
	}
}
// Action added by WP_Query_integration

function cb2_manage_columns( $columns ) {
	global $post;
	if ( $post ) {
		$cb2_post = CB2_Query::ensure_correct_class( $post );
		if ( method_exists( $cb2_post, 'manage_columns' ) ) {
			$columns = $cb2_post->manage_columns( $columns );
		}
	}
	return $columns;
}
// Action added by WP_Query_integration

function cb2_admin_init_menus() {
	global $wpdb;

	$capability_default   = 'manage_options';
	$bookings_count       = $wpdb->get_var( "select count(*) from {$wpdb->prefix}cb2_view_perioditem_posts `po` where ((`po`.`datetime_period_item_start` > now()) and (`po`.`post_type_id` = 15) and (`po`.`period_status_type_native_id` = 2) and (`po`.`enabled` = 1) and (`po`.`blocked` = 0)) GROUP BY `po`.`timeframe_id` , `po`.`period_native_id`" );
	$notifications_string = ( $bookings_count ? " ($bookings_count)" : '' );
	add_menu_page( 'CB2', "CB2$notifications_string", $capability_default, CB2_MENU_SLUG, 'cb2_options_page', 'dashicons-video-alt' );

	foreach ( cb2_admin_pages() as $menu_slug => $details ) {
		$parent_slug  = ( isset( $details['parent_slug'] )  ? $details['parent_slug']  : CB2_MENU_SLUG );
		$page_title   = ( isset( $details['page_title'] )   ? preg_replace( '/\%.+\%/', '', $details['page_title'] ) : '' );
		$menu_title   = ( isset( $details['menu_title'] )   ? $details['menu_title']   : $page_title );
		$capability   = ( isset( $details['capability'] )   ? $details['capability']   : $capability_default );
		$function     = ( isset( $details['function'] )     ? $details['function']     : 'cb2_settings_list_page' );
		$advanced     = ( isset( $details['advanced'] )     ? $details['advanced']     : FALSE );
		$first        = ( isset( $details['first'] )        ? $details['first']        : FALSE );
		$menu_visible = ( isset( $details['menu_visible'] ) ? $details['menu_visible'] : ! $advanced );

		// Menu adornments
		if ( $menu_visible && isset( $details['count'] ) ) {
			$count       = $wpdb->get_var( $details['count'] );
			$count_class = ( isset( $details['count_class'] ) ? "cb2-usage-count-$details[count_class]" : 'cb2-usage-count-info' );
			if ( $count ) $menu_title .= " <span class='$count_class'>$count</span>";
		}
		if ( isset( $details['indent'] ) )
			$menu_title = str_repeat( '&nbsp;&nbsp;', $details['indent'] ) . '•&nbsp;' . $menu_title;
		if ( isset( $details['advanced'] ) )
			$menu_title = "<span class='cb2-advanced-menu-item $first'>$menu_title</span>";
		if ( isset( $details['description'] ) )
			$menu_title = "<span title='$details[description]'>$menu_title</span>";

		// Create
		if ( $menu_visible ) {
			add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
		} else {
			$priority = ( isset( $details['priority'] ) ? $details['priority'] : 10 );
			add_submenu_page( $parent_slug, $page_title, "<div class='cb2-menu-invisible'/>", $capability_default, $menu_slug );
			add_filter( "admin_page_$menu_slug", $function, 0, $priority );
		}
	}
}
add_action( 'admin_menu', 'cb2_admin_init_menus' );

// ---------------------------------------------------------- Pages
function cb2_options_page() {
	// main CB2 options page
	global $wpdb;

	print( '<h1>Commons Booking 2</h1>' );
	print( '<ul>' );
	$capability_default = 'manage_options';

	foreach ( cb2_admin_pages() as $menu_slug => $details ) {
		$parent_slug  = ( isset( $details['parent_slug'] )  ? $details['parent_slug']  : CB2_MENU_SLUG );
		$page_title   = ( isset( $details['page_title'] )   ? preg_replace( '/\%.+\%/', '', $details['page_title'] ) : '' );
		$menu_title   = ( isset( $details['menu_title'] )   ? $details['menu_title']   : $page_title );
		$capability   = ( isset( $details['capability'] )   ? $details['capability']   : $capability_default );
		$function     = ( isset( $details['function'] )     ? $details['function']     : 'cb2_settings_list_page' );
		$advanced     = ( isset( $details['advanced'] )     ? $details['advanced']     : FALSE );
		$first        = ( isset( $details['first'] )        ? $details['first']        : FALSE );
		$menu_visible = ( isset( $details['menu_visible'] ) ? $details['menu_visible'] : ! $advanced );
		$description  = ( isset( $details['description'] )  ? $details['description']  : FALSE );

		// Menu adornments
		$class        = '';
		$indent       = '';
		$count_bubble = '';
		if ( isset( $details['count'] ) ) {
			$count       = $wpdb->get_var( $details['count'] );
			$count_class = ( isset( $details['count_class'] ) ? "cb2-usage-count-$details[count_class]" : 'cb2-usage-count-info' );
			if ( $count ) $count_bubble .= " <span class='$count_class'>$count</span>";
		}
		if ( isset( $details['indent'] ) )   $indent = str_repeat( '&nbsp;&nbsp;', $details['indent'] ) . '•&nbsp;';
		if ( isset( $details['first']) )     $class .= " $details[first]";
		if ( isset( $details['advanced'] ) ) $class .= ' cb2-advanced-menu-item';
		if ( current_user_can( $capability ) ) {
			print( "<li>$indent<a class='$class' href='admin.php?page=$menu_slug'>$menu_title</a> $count_bubble" );
		} else {
		  print( "<li class='$class'>$indent$menu_title $count_bubble" );
		}
		if ( isset( $details['description'] ) ) print( "<p class='cb2-description'>$details[description]</p>" );
		print( "</li>" );
	}
	print( '</ul>' );
}

function cb2_admin_page() {
	// Admin data controls
	print( '<h1>Super Admin Section</h1>' );
	print( '<p>There be dragons</p>' );

	print( '<h2>status</h2>');
	print( '<ul>' );
	print( '<li>error_reporting: ' . error_reporting() . '</li>' );
	print( '</ul>' );

}

function cb2_calendar() {
	require_once( 'calendar.php' );
}

function cb2_reflection() {
	global $wpdb;

	$DB_NAME = DB_NAME;

	print( "<h1>Reflection ($DB_NAME)</h1>" );
	print( "<p>A note on collation: string literals, e.g. 'period', can cause collation issues.
		By default they will adopt the <b>server</b> collation, not the database collation.
		Thus any database field data being concatenated with them or compared to them will cause an error.
		To avoid this always pull all data from the database.
		Returning a variety of charsets and collations with string literals and database data is not a problem for the PHP.
		<br/>
		You cannot collate to the default database collation with MySQL.
		If you really need to do this then carry out a string replacement in the PHP layer.
		This would replace @@character_set_database with the actual characterset name, gained through a database call with get_var(select @@character_set_database).
		String literals can be collated efficiently with _@@character_set_database'string' collate @@collation_database
	</p>");
	$and_posts         = isset( $_GET['and_posts'] ); // Checkbox
	$and_posts_checked = ( $and_posts ? 'checked="1"' : '' );
	$testdata          = isset( $_GET['testdata'] );  // Checkbox
	$testdata_checked  = ( $testdata ? 'checked="1"' : '' );
	$disabled          = ( isset( $_GET['reset_data'] ) ? 'disabled="1"' : '' );
	print( '<div class="cb2-actions">' );
	print( '<a href="?page=cb2-reflection">show install schema</a>' );
	$processing = 'var self = this;
		setTimeout(function(){
			self.setAttribute("value", "Processing...");
			self.setAttribute("disabled", "1");
		}, 0);';
	if ( WP_DEBUG ) print( " | <form><div>
			<input type='hidden' name='page' value='cb2-reflection'/>
			<input type='hidden' name='section' value='reinstall_sql'>
			<input onclick='$processing' class='cb2-submit' type='submit' value='generate re-install SQL'/>
			<select name='character_set'>
				<option value=''>-- explicit collation --</option>
				<option value='latin1_swedish_ci'>latin1_swedish_ci (MySQL default)</option>
				<option value='utf8mb4_unicode_ci'>utf8mb4_unicode_ci (advised)</option>
				<option>utf8mb4_general_ci</option>
				<option>utf8_unicode_ci</option>
				<option>utf8_general_ci</option>
			</select>
		</div></form>" );
	if ( WP_DEBUG ) print( "<br/><form><div>
			<input type='hidden' name='page' value='cb2-reflection'/>
			<input type='hidden' name='section' value='reinstall'>
			<input onclick='$processing' class='cb2-submit cb2-dangerous' type='submit' value='re-install'/>
			<input name='password' placeholder='password (fryace4)' value=''>
		</div></form>" );
	if ( WP_DEBUG ) print( " | <form><div>
			<input type='hidden' name='page' value='cb2-reflection'/>
			<input type='hidden' name='section' value='reset_data'/>
			<input type='hidden' name='password' value='fryace4'/>
			<input onclick='$processing' $disabled class='cb2-submit cb2-dangerous' type='submit' value='clear all data'/>
			<input id='and_posts' $and_posts_checked type='checkbox' name='and posts'/> <label for='and_posts'>Clear all <b>CB2</b> wp_post data</label>
			<input id='testdata'  $testdata_checked  type='checkbox' name='testdata'/> <label for='testdata'><b>Overwrite</b> wp_posts with test data</label>
		</div></form>" );
	print( '</div><hr/>' );

	if ( isset( $_GET['section'] ) ) {
		switch ( $_GET['section'] ) {
			case 'reset_data':
				if ( CB2_Forms::reset_data( $_GET['password'], $and_posts, $testdata ) ) {
					print( '<div>Data reset successful' . ( $and_posts ? ', with posts and postmeta': '' ) . '</div>' );
				}
				break;
			case 'reinstall':
				if ( $_GET['password'] == 'fryace4' ) {
					CB2_Database::uninstall();
					CB2_Database::install();
					print( '<div>Finished.</div>' );
				} else throw new Exception( 'Invalid password' );
				break;
			case 'reinstall_sql':
				// TODO: detect MySQL DB collation and wp-config.php collation
				// and advise if there are issues
				$full_sql = CB2_Database::get_reinstall_SQL_full( $_GET['character_set'] );
				print( "<pre>$full_sql</pre>" );
				break;
		}
	} else {
		$schema_array = CB2_Database::schema_array();

		// ---------------------------------------------------- Database reflection
		$exsiting_tables   = $exsiting_tables = $wpdb->get_col( 'SHOW TABLES;' );
		$view_results      = $wpdb->get_results( 'select table_name, view_definition from INFORMATION_SCHEMA.views', OBJECT_K );
		$triggers_results  = $wpdb->get_results( 'select trigger_name, action_statement from INFORMATION_SCHEMA.triggers', OBJECT_K );
		$existing_views    = array();
		$existing_triggers = array();
		// The compilation procedure adds things in to the definitions
		// that we do not specifiy, like collation
		foreach ( $triggers_results as $name => $definition ) {
			$action_statement = preg_replace( "/`$DB_NAME`\./", '',  $definition->action_statement );
			$action_statement = preg_replace( '/^BEGIN\\n|\\nEND$/',   '',  $action_statement );
			$action_statement = trim( preg_replace( '/\\s+/', ' ', $action_statement ) );
			array_push( $existing_triggers, $action_statement );
		}
		foreach ( $view_results as $name => &$definition ) {
			$view_body = preg_replace( "/`$DB_NAME`\./", '', $definition->view_definition );
			$view_body = trim( preg_replace( '/\\s+/', ' ', $view_body ) );
			$existing_views[$name] = $view_body;
		}

		// ---------------------------------------------------- System setup
		print( "<h2>WordPress and Database setup</h2>" );
		$db_charset = $wpdb->get_var( "SELECT @@character_set_database" );
		$db_collate = $wpdb->get_var( "SELECT @@collation_database;" );
		print( '<div>WordPress wp-config.php DB_CHARSET: <b>' . ( DB_CHARSET ? DB_CHARSET : '(Blank)' ) . '</b></div>' );
		print( '<div>WordPress wp-config.php DB_COLLATE: <b>' . ( DB_COLLATE ? DB_COLLATE : '(Blank)' ) . '</b></div>' );
		print( '<div>Database [' . DB_NAME . "] DB_CHARSET: <b>$db_charset</b></div>" );
		print( '<div>Database [' . DB_NAME . "] DB_COLLATE: <b>$db_collate</b></div>" );

		// ---------------------------------------------------- WordPress
		print( "<h2>WordPress ({$wpdb->prefix}postmeta)</h2>" );
		$row_count = $wpdb->get_var( "SELECT count(*) from {$wpdb->prefix}postmeta" );
		$class = ( $row_count >= 1000 ? 'cb2-warning' : '' );
		print( "<div class='$class'>row count: $row_count</div>" );

		print( "<h2>WordPress ({$wpdb->prefix}posts)</h2>" );
		$row_count = $wpdb->get_var( "SELECT count(*) from {$wpdb->prefix}posts" );
		$class = ( $row_count >= 1000 ? 'cb2-warning' : '' );
		print( "<div class='$class'>row count: $row_count</div>" );

		// ---------------------------------------------------- CB2
		foreach ( $schema_array as $Class => $object_types ) {
			$post_type         = ( property_exists( $Class, 'static_post_type' ) ? $Class::$static_post_type : '' );
			$table_definitions = ( isset( $object_types['table'] ) ? $object_types['table'] : NULL );
			$views             = ( isset( $object_types['views'] ) ? $object_types['views'] : NULL );

			print( "<h2>$Class</h2>" );
			// ----------------------------------------------- Infrastructure
			if ( property_exists( $Class, 'description' ) ) print( "<div class='cb2-description'>{$Class::$description}</div>" );
			if ( $post_type ) print( "<div>post_type: <b>$post_type</b></div>" );
			if ( isset( $object_types['data'] ) ) print( "<div>has <b>" . count( $object_types['data'] ) . "</b> initial data rows</div>" );

			if ( ! CB2_Database::database_table( $Class ) ) print( '<div>the Class claims no primary database table</div>' );
			if ( $post_type && ! CB2_Database::posts_table( $Class ) )    print( '<div>the Class claims no posts table</div>' );
			if ( $post_type && ! CB2_Database::postmeta_table( $Class ) ) print( '<div>the Class claims no postmeta table</div>' );

			if ( $table_definitions ) {
				foreach ( $table_definitions as $table_definition ) {
					$table_name = ( $table_definition ? $table_definition['name'] : NULL );

					// ----------------------------------------------- TABLE
					print( "<table class='cb2-database-table'><thead>" );
					print( "<tr><th colspan='100'><i class='cb2-database-prefix'>$wpdb->prefix</i>$table_name</th></tr>" );
					print( "<tr>" );
					foreach ( CB2_Database::$columns as $column )
						print( "<th>$column</th>" );
					print( "</tr>" );
					print( "</thead><tbody>" );
					$existing_columns = $wpdb->get_results( "DESC {$wpdb->prefix}$table_name;", OBJECT_K );
					if ( count( $existing_columns ) > count( $table_definition['columns'] ) )
						print( "<div class='cb2-warning'>$table_name has new columns</div>");

					foreach ( $table_definition['columns'] as $name => $column_definition ) {
						print( "<tr>" );

						print( "<td>$name" );
						if ( ! isset( $existing_columns[$name] ) )
							print( " <span class='cb2-warning'>not found</span>" );
						print( "</td>" );

						for ( $i = 0; $i < count( CB2_Database::$columns ) - 2; $i++ ) {
							print( "<td>" );
							print( isset( $column_definition[$i] ) ? $column_definition[$i] : '' );
							print( "</td>" );
						}

						print( "<td>" );
						$fk = ( isset( $table_definition['foreign keys'][$name] ) ? $table_definition['foreign keys'][$name] : NULL );
						if ( $fk ) {
							print( "=&gt;&nbsp;$fk[0]" );
						} else if ( substr( $name, -3 ) == '_ID' ) {
							print( "<div class='cb2-warning'>ID column has no foreign key</div>" );
						}
						print( "</td>" );
						print( "</tr>" );
					}
					print( "</tbody></table>" );

					// ----------------------------------------------- stats
					$row_count  = $wpdb->get_var( "SELECT count(*) from {$wpdb->prefix}$table_name" );
					$class      = ( $row_count >= 1000 ? 'cb2-warning' : '' );
					print( "<div class='$class'>row count: $row_count</div>" );
					if ( ! in_array( "$wpdb->prefix$table_name", $exsiting_tables ) )
						print( "<div class='cb2-warning'>[$wpdb->prefix$table_name] not found in the database</div>" );

					// ----------------------------------------------- TRIGGERS
					if ( isset( $table_definition['triggers'] ) ) {
						foreach ( $table_definition['triggers'] as $trigger_type => $triggers ) {
							foreach ( $triggers as $trigger_body ) {
								print( "<div><b>$trigger_type</b> trigger</div>" );
								$trigger_body = CB2_Database::check_fuction_bodies( "$table_name::trigger", $trigger_body );
								$trigger_body = trim( preg_replace( '/\\s+/', ' ', $trigger_body ) );
								if ( ! in_array( $trigger_body, $existing_triggers ) ) {
									krumo($trigger_body, $existing_triggers);
									print( "&nbsp;<span class='cb2-warning'>trigger different, or does not exist</span>" );
								}
							}
						}
					}

					// ----------------------------------------------- M2M
					if ( isset( $table_definition['many to many'] ) ) {
						foreach ( $table_definition['many to many'] as $m2mname => $m2m_defintion ) {
							$foreign_table = $m2m_defintion[1];
							print( "<div>$table_name also has a many-to-many realtionship with <b>$foreign_table</b> called <b>$m2mname</b></div>" );
						}
					}
				}

				// ----------------------------------------------- VIEWS
				if ( count( $views ) ) {
					print( "<div>views: <ul class='cb2-database-views'>" );
					$first = '';
					foreach ( $views as $name => $view_body ) {
						print( "<li>$first$name" );
						$full_name = "$wpdb->prefix$name";
						$view_body = CB2_Database::check_fuction_bodies( "view::$name", $view_body );
						if ( ! isset( $existing_views[$full_name] ) )
							print( " <span class='cb2-warning'>does not exist</span>" );
						else if ( $existing_views[$full_name] != $view_body ) {
							krumo($existing_views[$full_name], $view_body);
							print( " <span class='cb2-warning'>has different body</span>" );
						} else {
							$row_count = $wpdb->get_var( "SELECT count(*) from $full_name" );
							$class     = ( $row_count >= 1000 ? 'cb2-warning' : '' );
							print( "&nbsp;<span class='$class'>($row_count)</span>" );
						}
						print( '</li>' );
						$first = ', ';
					}
					print( "</ul></div>" );
				}
			}
		}

		// --------------------------- Model
		print( '<hr/>' );
		print( '<h2>model</h2>');
		print( '<img src="' . plugins_url( CB2_TEXTDOMAIN . '/admin/assets/model.png' ) . '"/>' );
	}
}

function cb2_settings_list_page() {
	global $wpdb;
	global $is_IE;

	if ( WP_DEBUG ) print( ' <span class="cb2-WP_DEBUG">' . __FUNCTION__ . '()</span>' ); // CB2/Annesley: debug

	if ( isset( $_GET[ 'page' ] ) ) {
		$page      = $_GET[ 'page' ];
		$post_type = NULL;
		$typenow   = NULL;
		$title     = NULL;

		// Bring stored parameters on to the query-string
		if ( isset( cb2_admin_pages()[$page] ) ) {
			// Replace tokens from the query string
			$details_page = cb2_admin_pages()[$page];
			foreach ( $details_page as $page_setting => &$page_value ) {
				$replacement = FALSE;
				foreach ( $_GET as $get_name => $get_value ) {
					$token = "%$get_name%";
					if ( substr( $get_name, -3 ) == '_ID' ) {
						if ( $post = get_post( $get_value ) )
							$get_value = $post->post_title;
						if ( strstr( $page_value, $token ) ) {
							$replacement = TRUE;
							$page_value  = str_replace( $token, $get_value, $page_value );
						}
					}
				}
				// Remove any tokens that were not found
				// If a replacement was successful, then reveal %()% tokens
				// e.g. %(for)% %location_ID% => for Cargonomia
				if ( $replacement ) $page_value = preg_replace( '/%\(([^)]+)\)%/', '$1', $page_value );
				$page_value = preg_replace( '/%[^%]+%/', '', $page_value );
			}

			$title = $details_page['page_title'];

			// Append input query string to post_new
			$post_new_file_custom = ( isset( $details_page['post_new_page'] ) ? $details_page['post_new_page'] : 'admin.php?page=cb2-post-new' );
			if ( count( $_GET ) ) {
				$existing_query_string = array();
				if ( strchr( $post_new_file_custom, '?' ) ) {
					$existing_query_string_pairs = explode( '&', explode( '?', $post_new_file_custom, 2 )[1] );
					foreach ( $existing_query_string_pairs as $value ) $existing_query_string[ CB2_Query::substring_before( $value, '=' ) ] = 1;
				}
				foreach ( $_GET as $name => $value ) {
					if ( ! isset( $existing_query_string[ $name ] ) ) {
						$post_new_file_custom .= ( strchr( $post_new_file_custom, '?' ) ? '&' : '?' );
						$post_new_file_custom .= urlencode( $name ) . '=' . urlencode( $value );
					}
				}
			}

			// Global params used in included file
			// WP_Query arguments
			// write them to the global query_string
			$wp_query_args = $details_page['wp_query_args'];
			$add_new_query = $details_page['wp_query_args'];
			foreach ( explode( '&', $wp_query_args ) as $arg_detail_string ) {
				$arg_details   = explode( '=', $arg_detail_string, 2 );
				$name          = $arg_details[0];
				$value         = ( count( $arg_details ) > 1 ? $arg_details[1] : '' );
				$_GET[ $name ] = $value;
				switch ( $name ) {
					case 'post_type':  $post_type = $value; break;
				}
			}
			$typenow = $post_type;

			// This is a COPY of the normal wp-admin file

			$screen = WP_Screen::get( $typenow );
			set_current_screen( $screen );
			require_once( dirname( __FILE__ ) . '/wp-admin/edit.php' );
		} else throw new Exception( 'CB2 admin page cannot find its location in the db' );
	} else throw new Exception( 'CB2 admin page does not understand its location. A querystring ?page= parameter is needed' );

	return TRUE;
}

function cb2_settings_post_new() {
	global $auto_draft_publish_transition;
	global $is_IE;

	if ( WP_DEBUG ) print( ' <span class="cb2-WP_DEBUG">' . __FUNCTION__ . '()</span>' ); // CB2/Annesley: debug
	$title = 'Add New';
	if ( isset( $_GET[ 'add_new_label' ] ) ) $title = $_GET[ 'add_new_label' ];
	else {
		// e.g. Add New Location Holiday for Cargonomia
		if ( isset( $_GET[ 'post_type' ] ) )               $title .= ' ' . ucfirst( CB2_Query::substring_after( ucfirst( $_GET[ 'post_type' ] ) ) );
		if ( isset( $_GET[ 'period_status_type_name' ] ) ) $title .= ' ' . ucfirst( $_GET[ 'period_status_type_name' ] );

		// Append objects to the title
		$first = TRUE;
		foreach ( $_GET as $name => $value ) {
			if ( substr( $name, -3 ) == '_ID' ) {
				if ( $post = get_post( $value ) ) {
					if ( $first ) $title .= ' for ';
					else          $title .= ',';
					$value  = ucfirst( $post->post_title );
					$title .= " $value";
					$first  = FALSE;
				}
			}
		}
		$title = preg_replace( '/%[^%]+%/', '', $title );
	}

	// edit-form-advanced.php altered to accept $post_submit_custom
	// post_type will be passed through
	$remove_parameters  = array( 'post_ID' );
	$add_parameters     = array( 'auto_draft_publish_transition' => 1 );
	$post_submit_custom = ( isset( $_GET[ 'post_submit_custom' ] ) ? $_GET[ 'post_submit_custom' ] : 'admin.php?page=cb2-post-edit' );
	$post_submit_custom = CB2_Query::pass_through_query_string( $post_submit_custom, $add_parameters, $remove_parameters );

	// Global params used in included file
	$post_type = $_GET[ 'post_type' ];
	$typenow   = $post_type;

	// DO NOT redirect the $wpdb->prefix
	// because post-new.php will
	//   get_default_post_to_edit( $post_type, CREATE_IN_DB );
	// and this will need to write to wp_posts
	// an auto-draft
	$auto_draft_publish_transition = FALSE;
	if ( CB2_DEBUG_SAVE ) {
		$post_submit_custom    .= '&XDEBUG_PROFILE=1';
		print( "<div class='cb2-WP_DEBUG'>XDEBUG_PROFILE=1</div>" );
		print( '<div class="cb2-WP_DEBUG-small">auto_draft_publish_transition ' . ( $auto_draft_publish_transition ? '<b class="cb2-warning">TRUE</b>' : 'FALSE' ) . '</div>' );
	}

	// This is a COPY of the normal wp-admin file

	$screen = WP_Screen::get( $typenow );
	set_current_screen( $screen );
	require_once( dirname( __FILE__ ) . '/wp-admin/post-new.php' );
}

function cb2_settings_post_edit() {
	global $action, $auto_draft_publish_transition; // post.php will wp_reset_vars(action)
	global $is_IE;

	if ( WP_DEBUG ) print( ' <span class="cb2-WP_DEBUG">' . __FUNCTION__ . '()</span>' ); // CB2/Annesley: debug
	$title = 'Edit Post';
	if ( isset( $_GET[ 'add_new_label' ] ) ) $title = $_GET[ 'add_new_label' ];
	else {
		// e.g. Add New Location Holiday for Cargonomia
		if ( isset( $_GET[ 'post_type' ] ) )               $title .= ' ' . ucfirst( CB2_Query::substring_after( ucfirst( $_GET[ 'post_type' ] ) ) );
		if ( isset( $_GET[ 'period_status_type_name' ] ) ) $title .= ' ' . ucfirst( $_GET[ 'period_status_type_name' ] );

		// Append objects to the title
		$first = TRUE;
		foreach ( $_GET as $name => $value ) {
			if ( substr( $name, -3 ) == '_ID' ) {
				if ( $post = get_post( $value ) ) {
					if ( $first ) $title .= ' for ';
					else          $title .= ',';
					$value  = ucfirst( $post->post_title );
					$title .= " $value";
					$first  = FALSE;
				}
			}
		}
		$title = preg_replace( '/%[^%]+%/', '', $title );
	}

	// Append input query string to post_new
	// post.php altered to accept $post_new_file_custom
	$post_new_file_custom = ( isset( $_GET[ 'post_new_file_custom' ] ) ? $_GET[ 'post_new_file_custom' ] : 'admin.php?page=cb2-post-new' );
	$post_new_file_custom = CB2_Query::pass_through_query_string( $post_new_file_custom );

	// edit-form-advanced.php altered to accept $post_submit_custom
	// post_type will be passed through
	$remove_parameters  = array( 'post', 'action', 'auto_draft_publish_transition' );
	$post_submit_custom = ( isset( $_GET[ 'post_submit_custom' ] ) ? $_GET[ 'post_submit_custom' ] : 'admin.php?page=cb2-post-edit' );
	$post_submit_custom = CB2_Query::pass_through_query_string( $post_submit_custom, array(), $remove_parameters );

	// Global params used in included file
	$post_type = $_GET[ 'post_type' ];
	$typenow   = $post_type;

	// conditionally redirect the get_post() in post.php
	if ( ! $auto_draft_publish_transition ) {
		// Full normal redirected main get_post()
		CB2_Query::redirect_wpdb_for_post_type( $post_type );
		if ( CB2_DEBUG_SAVE )
			print( "<div class='cb2-WP_DEBUG-small'>permanent page level wp_posts redirect for $post_type to handle get_post()</div>" );
	}

	// This is a COPY of the normal wp-admin file will:
	//   $post_id = edit_post() from the $_POST
	// creating meta_data as well

	$screen = WP_Screen::get( $typenow );
	set_current_screen( $screen );
	require_once( dirname( __FILE__ ) . '/wp-admin/post.php' );
}

