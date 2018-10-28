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
		print( "wp_redirect( <a href='$location'>$location</a>, <b>$status</b> )" );
		print( '<hr/><h2>CB2_DEBUG_SAVE</h2>' );
		krumo( $_POST );
		xdebug_print_function_stack();
		exit();
	}
	if ( WP_DEBUG ) {
		// We will have had debug information already
		// and a header after output error
		// so we need to JS redirect instead
		$esc_location = str_replace( "'", "\\\'", $location );
		print( "Using JavaScript redirect because WP_DEBUG has already output debug info..." );
		print( "<script>document.location='$esc_location';</script>" );
		exit();
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
			'wp_query_args' => 'post_type=periodent-global&period_status_type_ID=100000006&post_title=Holidays',
			'description'   => 'Edit the global holidays, and holidays for specific locations.',
			'count'         => "select count(*) from {$wpdb->prefix}cb2_global_period_groups where period_status_type_id = " . CB2_PeriodStatusType_Holiday::$id,
		),
		'cb2-items'         => array(
			'page_title' => 'Items',
			'wp_query_args' => 'post_type=item',
			'count'         => "select count(*) from {$wpdb->prefix}posts where post_type='item' and post_status='publish'",
		),
		'cb2-repairs'       => array(
			'indent'      => 1,
			'page_title' => 'Repairs %(for)% %location_ID% %item_ID%',
			'menu_title' => 'Repairs',
			'wp_query_args' => 'post_type=periodent-user&period_status_type_ID=100000005',
			'count'         => "select count(*) from {$wpdb->prefix}cb2_timeframe_user_period_groups where period_status_type_id = " . CB2_PeriodStatusType_Repair::$id,
			'count_class'   => 'warning',
		),
		'cb2-locations'     => array(
			'page_title'     => 'Locations',
			'wp_query_args'  => 'post_type=location',
			'count'          => "select count(*) from {$wpdb->prefix}posts where post_type='location' and post_status='publish'",
		),
		'cb2-opening-hours' => array(
			'indent'      => 1,
			'page_title' => 'Opening Hours %(for)% %location_ID%',
			'menu_title' => 'Opening Hours',
			'wp_query_args' => 'post_type=periodent-location&recurrence_type=D&recurrence_type_show=no&period_status_type_ID=100000004&post_title=Opening Hours %(for)% %location_ID%',
			'count'         => "select count(*) from {$wpdb->prefix}cb2_location_period_groups where period_status_type_id = " . CB2_PeriodStatusType_Open::$id,
		),
		'cb2-timeframes'    => array(
			'indent'      => 1,
			'page_title' => 'Item availibility %(for)% %location_ID%',
			'menu_title' => 'Item Availibility',
			'wp_query_args' => 'post_type=periodent-timeframe&period_status_type_ID=100000001',
			'count'         => "select count(*) from {$wpdb->prefix}cb2_timeframe_period_groups where period_status_type_id = " . CB2_PeriodStatusType_Available::$id,
		),
		'cb2-bookings'    => array(
			'indent'      => 1,
			'page_title' => 'Bookings %(for)% %location_ID%',
			'menu_title' => 'Bookings',
			'wp_query_args' => 'post_type=periodent-user&period_status_type_ID=100000002',
			'count'         => "select count(*) from {$wpdb->prefix}cb2_view_future_bookings",
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
			'page_title' => 'Locations',
			'wp_query_args' => 'post_type=periodent-location',
			'count'         => "select count(*) from {$wpdb->prefix}cb2_location_period_groups",
			'advanced'      => TRUE,
		),
		'cb2-period-timeframes' => array(
			'indent'        => 1,
			'page_title' => 'Timeframes',
			'wp_query_args' => 'post_type=periodent-timeframe',
			'count'         => "select count(*) from {$wpdb->prefix}cb2_timeframe_period_groups",
			'advanced'      => TRUE,
		),
		'cb2-period-users' => array(
			'indent'        => 1,
			'page_title' => 'Users',
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
		// Move all edit actions to our custom screens
		if ( isset( $actions['edit'] ) )
			$actions['edit'] = "<a href='admin.php?page=cb2-post-edit&post=$post->ID&post_type=$post->post_type&action=edit' aria-label='Edit &#8220;$post_title&#8221;'>Edit</a>";

		CB2_Query::get_metadata_assign( $post );
		$post = CB2_Query::ensure_correct_class( $post );
		if ( $post instanceof CB2_PostNavigator && method_exists( $post, 'add_actions' ) ) {
			$add_actions = ( isset( $_GET['add_actions'] ) ? explode( ',', $_GET['add_actions'] ) : array() );
			$post->add_actions( $actions, $post, $add_actions );
		}

		if ( basename( $_SERVER['PHP_SELF'] ) == 'admin.php' && isset( $_GET[ 'page' ] ) ) {
			$page        = $_GET[ 'page' ];
			$admin_pages = cb2_admin_pages();
			if ( isset( $admin_pages[$page] ) && isset( $admin_pages[$page]['actions'] ) ) {
				$action_string = $admin_pages[$page]['actions'];
				if ( $action_string ) {
					$new_actions = explode( ',', $action_string );
					foreach ( $new_actions as $new_action ) {
						foreach ( $post as $name => $value ) {
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
	$bookings_count       = $wpdb->get_var( "select count(*) from {$wpdb->prefix}cb2_view_future_bookings" );
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
		if ( isset( $details['count'] ) ) {
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

function cb2_admin_init_do_action() {
	if ( isset( $_GET['do_action'] ) ) {
		$do_action = explode( '::', $_GET['do_action'] );
		if ( count( $do_action ) == 2 ) {
			// SECURITY: limit which methods can be run
			$Class  = $do_action[0];
			$method = 'do_action_' . $do_action[1];
			if ( method_exists( $Class, $method ) ) {
				$args = $_GET;
				array_shift( $args ); // Remove the page
				call_user_func_array( array( $Class, $method ), $args );
			}
		}
	}
}
add_action( 'admin_init', 'cb2_admin_init_do_action' );

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

	print( '<h1>Reflection</h1>' );
	$and_posts         = isset( $_GET['and_posts'] ); // Checkbox
	$and_posts_checked = ( $and_posts ? 'checked="1"' : '' );
	$disabled          = ( isset( $_GET['reset_data'] ) ? 'disabled="1"' : '' );
	print( '<div class="cb2-actions">' );
	print( '<a href="?page=cb2-reflection">show install schema</a>' );
	print( ' | <a href="?page=cb2-reflection&section=install_SQL">dump install SQL</a>' );
	if ( WP_DEBUG ) print( ' | <form><div>
			<input type="hidden" name="page" value="cb2-reflection"/>
			<input type="hidden" name="section" value="reinstall">
			<input class="cb2-submit cb2-dangerous" type="submit" value="re-install"/>
			<input name="password" placeholder="password" value="">
		</div></form>' );
	if ( WP_DEBUG ) print( " | <form><div>
			<input type='hidden' name='page' value='cb2-reflection'/>
			<input type='hidden' name='section' value='reset_data'/>
			<input type='hidden' name='password' value='fryace4'/>
			<input $disabled class='cb2-submit cb2-dangerous' type='submit' value='clear all data'/>
			<input id='and_posts' $and_posts_checked type='checkbox' name='and posts'/> <label for='and_posts'>And all CB2 wp_post data</label>
		</div></form>" );
	print( '</div>' );

	if ( isset( $_GET['section'] ) ) {
		switch ( $_GET['section'] ) {
			case 'reset_data':
				if ( CB2_Forms::reset_data( $_GET['password'], $and_posts ) ) {
					print( '<div>Data reset successful' . ( $and_posts ? ', with posts and postmeta': '' ) . '</div>' );
				}
				break;
			case 'reinstall':
				if ( $_GET['password'] == 'fryace4' ) {
					$sql = CB2_Database::install_SQL();
					$wpdb->query( $sql );
					print( 'Finished.' );
				} else throw new Exception( 'Invlaid password' );
				break;
			case 'install_SQL':
				print( '<pre>' );
				print( htmlentities( CB2_Database::install_SQL() ) );
				print( '</pre>' );
				break;
		}
	} else {
		$install_array = CB2_Database::install_array();

		foreach ( $install_array['tables'] as $Class => $definition ) {
			$post_type = ( property_exists( $Class, 'static_post_type' ) ? $Class::$static_post_type : '' );

			print( "<h2>$Class (<i class='cb2-database-prefix'>$wpdb->prefix</i>$definition[name])</h2>" );
			if ( $post_type ) print( "<div>post_type: <b>$post_type</b></div>" );

			if ( isset( $install_array['views'][$Class] ) ) {
				print( "views: <ul class='cb2-database-views'>" );
				foreach ( $install_array['views'][$Class] as $name => $body ) {
					print( "<li>$name</li>" );
				}
				print( "</ul>" );
			}

			print( "<table class='cb2-database-table'><thead>" );
			print( "<th>name</th><th>type</th><th>size</th><th>unsigned</th><th>not null</th><th>auto increment</th><th>default</th><th>comment</th>" );
			print( "</thead><tbody>" );
			foreach ( $definition['columns'] as $name => $definition ) {
				print( "<tr>" );
				print( "<td>$name</td>" );
				foreach ( $definition as $value ) {
					print( "<td>$value</td>" );
				}
				print( "</tr>" );
			}
			print( "</tbody></table>" );
		}

		// --------------------------- Model
		print( '<hr/>' );
		print( '<h2>model</h2>');
		print( '<img src="' . plugins_url( CB2_TEXTDOMAIN . '/admin/assets/model.png' ) . '"/>' );
	}
}

function cb2_settings_list_page() {
	global $wpdb;
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
	if ( CB2_DEBUG_SAVE )
		print( '<div class="cb2-WP_DEBUG-small">auto_draft_publish_transition ' . ( $auto_draft_publish_transition ? '<b class="cb2-warning">TRUE</b>' : 'FALSE' ) . '</div>' );

	// This is a COPY of the normal wp-admin file
	$screen = WP_Screen::get( $typenow );
	set_current_screen( $screen );
	require_once( dirname( __FILE__ ) . '/wp-admin/post-new.php' );
}

function cb2_settings_post_edit() {
	global $action, $auto_draft_publish_transition; // post.php will wp_reset_vars(action)

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
	// redirect the postmeta may cause the _edit_lock to fail
	// TODO: move to get_post_type() in post.php? possibly with a conditional $redirect = TRUE parameter
	// TODO: temporarily redirect and unredirect in post.php
	if ( ! $auto_draft_publish_transition ) {
		// Full normal redirected main get_post()
		CB2_Query::redirect_wpdb_for_post_type( $post_type, FALSE );
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

