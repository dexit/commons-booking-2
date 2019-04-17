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


function cb2_wp_redirect( $location, $status, $javascript = FALSE ) {
	$is_cb2_page = ( isset( $_GET['page'] ) && preg_match( '/^cb2-.*/', $_GET['page'] ) );

	if ( substr( $location, 0, 1 ) == '?' )
		throw new Exception( "cb2_wp_redirect($location) seems invalid" );

	if ( CB2_DEBUG_SAVE ) {
		print( '<hr/><h2>CB2_DEBUG_SAVE wp_redirect() caught</h2>' );
		krumo( $_POST );
		print( "<b>wp_redirect</b>( <a href='$location'>$location</a>, <b>$status</b> )" );
		$location = FALSE; // Prevent actual redirect
	} else if ( WP_DEBUG || $javascript || $is_cb2_page ) {
		// We will have had debug information already
		// and a header after output error
		// so we need to JS redirect instead
		$esc_location = str_replace( "'", "\\\'", $location );
		if ( WP_DEBUG )
			print( "<div class='cb2-WP_DEBUG'>Using JavaScript redirect to [<b>$location</b>] because WP_DEBUG has already output debug info...</div>" );
		print( "<script>document.location='$esc_location';</script>" );
		print( "<style>body{opacity:0.2;}<style>" );
	}

	return $location;
}
add_filter( 'wp_redirect', 'cb2_wp_redirect', 10, 2 );

function cb2_admin_pages() {
	// %token% replacement happens on ALL parameters.
	// If any tokens are replaced then %(x)% => x texts are included.
	// All %token_ID% ending in _ID are converted to posts and the post_title inserted instead of the ID
	global $menu_interface;

	require_once( 'menu_array.php' );

	return $menu_interface;
}

function cb2_metaboxes() {
	$metaboxes          = array();
	$hidden_class       = 'hidden';
	$metabox_wizard_ids = ( isset( $_GET['metabox_wizard_ids'] ) ? $_GET['metabox_wizard_ids'] : '' );
	$metabox_wizard_ids = ( $metabox_wizard_ids ? explode( ',', $metabox_wizard_ids ) : array() );
	$requests_only      = (bool) count( $metabox_wizard_ids );

	foreach ( CB2_PostNavigator::post_type_classes() as $post_type => $Class ) {
		if ( CB2_Query::has_own_method( $Class, 'metaboxes' ) ) {
			$metaboxes[$Class] = array();
			foreach ( $Class::metaboxes() as $i => $metabox ) {
				$title = ( isset( $metabox['title'] ) ? $metabox['title'] : "metabox_$i" );
				if ( ! isset( $metabox['id'] ) ) {
					$id            = preg_replace( '/ <.*/', '', strtolower( $title ) );
					$id            = preg_replace( '/[^a-z0-9]/', '_', $id );
					$metabox['id'] = "{$Class}_{$id}";
				}
				if ( ! isset( $metabox['object_types'] ) ) $metabox['object_types'] = array( $post_type );
				if ( ! isset( $metabox['priority'] ) )     $metabox['priority']     = 'low'; // Under the standard boxes
				$metabox[$Class] = $Class;

				$id               = $metabox['id'];
				$object_types     = $metabox['object_types'];
				$metabox_classes  = ( isset( $metabox['classes'] )    ? ( is_array( $metabox['classes'] ) ? $metabox[ 'classes' ] : explode( ',', $metabox[ 'classes' ] ) ) : array() );
				$on_request       = ( isset( $metabox['on_request'] ) ? $metabox['on_request'] : FALSE );
				$requested        = in_array( $id, $metabox_wizard_ids );
				$include_metabox  = ( ! $requests_only && ! $on_request ) || ( $requests_only && $requested );

				if ( $include_metabox ) {
					// Meta-box level visibility by query-string
					$query_name  = "{$id}_show";
					$show_value  = ( isset( $_GET[$query_name] ) ? $_GET[$query_name] : '' );
					$query_hide  = ( $show_value === FALSE || $show_value == 'no' || $show_value == '0' || $show_value == 'hide' );
					$direct_hide = ( isset( $metabox['visible'] )    && $metabox['visible'] === FALSE );
					$cb_hide     = ( isset( $metabox['visible_cb'] ) && is_callable( $metabox['visible_cb'] ) && $metabox['visible_cb']( $metabox ) === FALSE );
					if ( $query_hide || $direct_hide || $cb_hide )
						array_push( $metabox_classes, $hidden_class );

					foreach ( $metabox['fields'] as &$field ) {
						$field_id = $field['id'];
						$name     = ( isset( $field['name'] ) ? $field['name'] : '<no field name>' );
						$type     = $field['type'];

						// Live hiding and showing of fields by query-string
						$query_name = "{$field_id}_show";
						$show_value = ( isset( $_GET[$query_name] ) ? $_GET[$query_name] : '' );
						$query_hide = ( $show_value === FALSE || $show_value == 'no' || $show_value == '0' || $show_value == 'hide' );
						$direct_hide = ( isset( $field['visible'] )    && $field['visible'] === FALSE );
						$cb_hide     = ( isset( $field['visible_cb'] ) && is_callable( $field['visible_cb'] ) && $field['visible_cb']( $metabox ) === FALSE );
						if ( $query_hide || $direct_hide || $cb_hide ) {
							$optional_text     = __( 'optional' );
							$field['classes']  = ( isset( $field['classes'] ) ? $field['classes'] . " $hidden_class" : $hidden_class );
							$field['name']     = ( isset( $field['name'] ) ? $field['name'] . " ($optional_text)" : '' );
						}

						if ( WP_DEBUG ) {
							// Extended type checks
							switch ( $type ) {
								case 'text_date':
								case 'text_datetime_timestamp':
									if ( $field['date_format'] != CB2_Database::$database_date_format )
										throw new Exception( "[$name] metabox field needs the CB2_Database::\$database_date_format" );
									break;
							}

							// Check callbacks are valid
							foreach ( $field as $field_name => $field_value ) {
								if ( substr( $field_name, -3 ) == '_cb' && ! is_callable( $field_value ) )
									throw new Exception( "metabox [$id/$field_name] is not callable" );
							}
						}
					}

					new_cmb2_box( $metabox );
					$metaboxes[$Class][$id] = $metabox;

					// Unfortunate LI wrapper post_class control
					foreach ( $metabox_classes as $metabox_class ) {
						foreach ( $object_types as $object_type ) {
							$hook = 'cb2_postbox_classes_' . str_replace( '-', '_', preg_replace( '/^[^-]*-/', '', $metabox_class ) );
							if ( function_exists( $hook ) ) {
								add_filter( "postbox_classes_{$object_type}_{$id}", $hook );
							} else {
								krumo( $metabox );
								throw new Exception( "$hook does not exist when linking classes" );
							}
						}
					}
				} // on_request
			}
		}
	}

	return $metaboxes;
}
add_action( 'cmb2_admin_init', 'cb2_metaboxes', 1 );

function cb2_post_row_actions( $actions, $post ) {
	global $wpdb, $post;

	$post_type  = $post->post_type;
	$post_title = htmlspecialchars( $post->post_title );
	if ( $Class = CB2_PostNavigator::post_type_Class( $post_type ) ) {
		// TODO: is this get_metadata_assign necessary? ensure_correct_class() also does it
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
	$page          = $_GET['page'];
	$all_text      = __( 'All' );
	$trash_text    = __( 'Trash' );
	$calendar_text = '<span class="cb2-todo">' . __( 'Calendar' ) . '</span>';
	$map_text      = '<span class="cb2-todo">' . __( 'Map' ) . '</span>';
	$views = array(
		'all'      => "<a href='admin.php?page=$page&post_status=publish'>$all_text</span></a>",
		'trash'    => "<a href='admin.php?page=$page&post_status=trash'>$trash_text</span></a>",
		'calendar' => "<a href='admin.php?page=$page&view=calendar'>$calendar_text</span></a>",
		'map'      => "<a href='admin.php?page=$page&view=map'>$map_text</span></a>",
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
			$all_columns     = array_merge( $columns, $cb2_post->manage_columns( $columns ) );
			$enabled_columns = $cb2_post->enabled_columns();
			if ( count( $enabled_columns ) ) {
				// Maintain the order of columns in $enabled_columns
				$columns = array();
				foreach ( $enabled_columns as $name )
					$columns[ $name ] = $all_columns[ $name ];
			} else {
				// Use all the declared and passed columns
				// with the date last
				$columns = $all_columns;
				CB2_Query::array_first_to_last( $columns, 'date' );
			}
		}
	}
	return $columns;
}
// Action added by WP_Query_integration

function cb2_admin_init_menus() {
	global $wpdb;
	static $i = 1;
	if ( $i == 2 ) throw new Exception();
	$i++;

	$cb2_menu_icon = 'data:image/svg+xml;base64,' . base64_encode('<?xml version="1.0" encoding="UTF-8" standalone="no"?><!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd"><svg width="100%" height="100%" viewBox="0 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/"><path fill="black" d="M12.94,5.68l0,-5.158l6.132,1.352l0,5.641c0.856,-0.207 1.787,-0.31 2.792,-0.31c3.233,0 5.731,1.017 7.493,3.05c1.762,2.034 2.643,4.661 2.643,7.88l0,0.458c0,3.232 -0.884,5.862 -2.653,7.89c-1.769,2.027 -4.283,3.04 -7.542,3.04c-1.566,0 -2.965,-0.268 -4.196,-0.806c1.449,-1.329 2.491,-2.998 3.015,-4.546c0.335,0.123 0.729,0.185 1.181,0.185c1.311,0 2.222,-0.51 2.732,-1.53c0.51,-1.021 0.765,-2.432 0.765,-4.233l0,-0.458c0,-1.749 -0.255,-3.146 -0.765,-4.193c-0.51,-1.047 -1.401,-1.57 -2.673,-1.57c-0.527,0 -0.978,0.107 -1.351,0.321c-1.051,-3.59 -4.047,-6.125 -7.573,-7.013Zm6.06,15.774c0.05,0.153 0.042,0.325 0.042,0.338c-0.001,2.138 -0.918,4.209 -2.516,5.584c-0.172,0.148 -0.346,0.288 -0.523,0.42c-0.209,-0.153 -0.411,-0.316 -0.608,-0.489c-1.676,-1.477 -2.487,-3.388 -2.434,-5.733l0.039,-0.12l6,0Zm-6.06,-13.799c3.351,1.058 5.949,3.88 6.092,7.332c0.011,0.254 0.11,0.416 -0.032,0.843l-6,0l-0.036,-0.108l-0.024,0l0,-8.067Z" /><path fill="black" d="M21.805,24.356c-0.901,0 -1.57,-0.245 -2.008,-0.735c-0.437,-0.491 -0.656,-1.213 -0.656,-2.167l-6.141,0l-0.039,0.12c-0.053,2.345 0.758,4.256 2.434,5.733c1.676,1.478 3.813,2.216 6.41,2.216c3.259,0 5.773,-1.013 7.542,-3.04c1.769,-2.028 2.653,-4.658 2.653,-7.89l0,-0.458c0,-3.219 -6.698,-1.749 -6.698,0l0,0.458c0,1.801 -0.255,3.212 -0.765,4.233c-0.51,1.02 -1.421,1.53 -2.732,1.53Z" /><path fill="black" d="M14.244,28.78c-1.195,0.495 -2.545,0.743 -4.049,0.743c-3.259,0 -5.773,-1.013 -7.542,-3.04c-1.769,-2.028 -2.653,-4.658 -2.653,-7.89l0,-0.458c0,-3.219 0.881,-5.846 2.643,-7.88c1.762,-2.033 4.26,-3.05 7.493,-3.05c0.917,0 1.773,0.086 2.566,0.258c1.566,0.34 2.891,1.016 3.972,2.027c1.63,1.524 2.418,3.597 2.365,6.221l-0.039,0.119l-6.141,0c0,-1.02 -0.226,-1.852 -0.676,-2.494c-0.451,-0.643 -1.133,-0.964 -2.047,-0.964c-1.272,0 -2.163,0.523 -2.673,1.57c-0.51,1.047 -0.765,2.444 -0.765,4.193l0,0.458c0,1.801 0.255,3.212 0.765,4.233c0.51,1.02 1.421,1.53 2.732,1.53c0.32,0 0.61,-0.031 0.871,-0.093c0.517,1.648 1.73,3.281 3.178,4.517Zm-1.244,-7.326l6,0l0.039,0.12c0.053,2.345 -0.758,4.256 -2.434,5.733c-0.134,0.118 -0.27,0.231 -0.409,0.339c-1.85,-1.327 -3.122,-3.233 -3.227,-5.424c-0.011,-0.228 -0.105,-0.357 0.031,-0.768Z" /></svg>');

	$capability_default   = 'read';
	$bookings_sql         = "select count(*) from {$wpdb->prefix}cb2_view_periodinst_posts `po` where ((`po`.`datetime_period_inst_start` > now()) and (`po`.`post_type_id` = 15) and (`po`.`period_status_type_native_id` = 2) and (`po`.`enabled` = 1) and (`po`.`blocked` = 0)) GROUP BY `po`.`timeframe_id` , `po`.`period_native_id`";
	$bookings_count       = ( CB2_Database::query_ok( $bookings_sql ) ? $wpdb->get_var( $bookings_sql ) : '!' );
	// notifications_string cancelled because too long with "CommonsBooking" title
	$notifications_string = ''; //( $bookings_count ? " ($bookings_count)" : '' );
	add_menu_page( 'CB2', "CommonsBooking$notifications_string", $capability_default, CB2_MENU_SLUG, 'cb2_dashboard_page', $cb2_menu_icon );
	add_submenu_page( CB2_MENU_SLUG, 'Dashboard', 'Dashboard', $capability_default, CB2_MENU_SLUG, 'cb2_dashboard_page' );
	if ( WP_DEBUG )
		add_options_page( 'CommonsBooking', 'CommonsBooking WP_DEBUG', 'manage_options', 'cb2-options', 'cb2_options_page' );

	foreach ( cb2_admin_pages() as $menu_slug => $details ) {
		$parent_slug  = ( isset( $details['parent_slug'] )  ? $details['parent_slug']  : CB2_MENU_SLUG );
		$page_title   = ( isset( $details['page_title'] )   ? preg_replace( '/\%.+\%/', '', $details['page_title'] ) : '' );
		$menu_title   = ( isset( $details['menu_title'] )   ? $details['menu_title']   : $page_title );
		$advanced     = ( isset( $details['advanced'] )     ? $details['advanced']     : FALSE );
		$function     = ( isset( $details['function'] )     ? $details['function']     : 'cb2_settings_list_page' );
		$first        = ( isset( $details['first'] )        ? $details['first']        : FALSE );
		$menu_visible = ( isset( $details['menu_visible'] ) ? $details['menu_visible'] : ! $advanced );
		$capability   = ( isset( $details['capability'] )   ? $details['capability']   : $capability_default );

		// Menu adornments
		if ( $menu_visible && isset( $details['count'] ) ) {
			$count_sql   = $details['count'];
			if ( $count_sql == $bookings_sql ) $count = $bookings_count;
			else {
				$can_count   = CB2_Database::query_ok( $count_sql );
				$count       = ( $can_count ? $wpdb->get_var( $count_sql ) : '!' );
			}
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

// ---------------------------------------------------------- Tabs Infrastructure
/*
 * WordPress 4.9 edit-form-advanced.php structure with do_action()s:
 * non-page post_type only. Check for 'page' == $post_type in code for options

// initial meta-box registration calls (no structure)
do_action( 'dbx_post_advanced', $post );
do_action( 'add_meta_boxes', $post_type, $post );
do_action( "add_meta_boxes_{$post_type}", $post );

do_action( 'do_meta_boxes', $post_type, 'normal', $post );
do_action( 'do_meta_boxes', $post_type, 'advanced', $post );
do_action( 'do_meta_boxes', $post_type, 'side', $post );

<div class="wrap">
	<h1 class="wp-heading-inline">...</h1>
	<form name="post" action="..." method="post" id="post"...>
		do_action( 'post_edit_form_tag', $post );
		do_action( 'edit_form_top', $post ); ?>
		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-...">
				<div id="post-body-content">
					<div id="titlediv">
						<div id="titlewrap">...</div>
							do_action( 'edit_form_before_permalink', $post );
						<div class="inside"></div>
					</div><!-- /titlediv -->
					do_action( 'edit_form_after_title', $post );
					<div id="postdivrich" ...>
						wp_editor(...)
					</div>
					do_action( 'edit_form_after_editor', $post );
				</div><!-- /post-body-content -->
				<div id="postbox-container-1" class="postbox-container">
					do_action( 'submitpost_box', $post );
					do_meta_boxes($post_type, 'side', $post);
				</div>
				<div id="postbox-container-2" class="postbox-container">
					do_meta_boxes(null, 'normal', $post);
					do_action( 'edit_form_advanced', $post );
					do_meta_boxes(null, 'advanced', $post);
				</div>
				do_action( 'dbx_post_sidebar', $post );
			</div><!-- /post-body -->
		</div><!-- /poststuff -->
	</form>
</div><!-- /wrap -->
*/
function cb2_edit_form_after_title_tab_switcher( $post ) {
	CB2::the_tabs( NULL, NULL, TRUE );
}
add_action( 'edit_form_after_title', 'cb2_edit_form_after_title_tab_switcher' );

function cb2_postbox_classes_object_summary_bar( $classes ) {
	array_push( $classes, 'cb2-object-summary-bar' );
	return $classes;
}
//add_filter() is carried out in metabox registration

function cb2_postbox_classes_hidden( $classes ) {
	array_push( $classes, 'hidden' );
	return $classes;
}
//add_filter() is carried out in metabox registration

function cb2_edit_form_advanced_tab_extra( $post ) {
	CB2_Query::ensure_correct_class( $post );
	$post_type = $post->post_type;

	if ( method_exists( $post, 'tabs' ) ) {
		if ( $tabs = $post->tabs( TRUE ) ) {
			foreach ( $tabs as $id => $title ) {
				switch ( $id ) {
					case 'postbox-container-1':
					case 'postbox-container-2':
						// Already done and filled with metaboxes side and normal
						break;
					case 'advanced':
						// Carry out the advanced container at the end as
						// it will be filled with metaboxes advanced by edit-form-advanced.php
						// after this hook
						break;
					default:
						print( '</div>' );
						print( "<div id='$id' class='postbox-container'>" );
						do_meta_boxes( $post_type, $id, $post );
				}
			}

			if ( isset( $tabs['advanced'] ) ) {
				print( '</div>' );
				print( "<div id='advanced' class='postbox-container'>" );
				// edit-form-advanced.php will now place advanced metaboxes after this action
			}
		}
	}
}
add_action( 'edit_form_advanced', 'cb2_edit_form_advanced_tab_extra' );

// ---------------------------------------------------------- Pages
function cb2_dashboard_page() {
	require_once( 'dashboard.php' );
}

function cb2_options_page() {
	require_once( 'options_page.php' );
}

function cb2_calendar() {
	require_once( 'calendar.php' );
}

function cb2_reflection() {
	require_once( 'reflection.php' );
}

function cb2_roles() {
	require_once( 'roles.php' );
}

function cb2_gui_setup() {
	require_once( 'GUI.php' );
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
			$post_new_file_custom = CB2_Query::pass_through_query_string( $post_new_file_custom );

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

			// Globally redirect so that get_post() calls work
			// in map_meta_cap() and get_permalink() calls
			// as columns may wp_cache_flush()
			CB2_Query::redirect_wpdb_for_post_type( $post_type );
			if ( CB2_DEBUG_SAVE )
				print( "<div class='cb2-WP_DEBUG-small'>permanent page level wp_posts redirect for [<b>$post_type</b>] to handle get_post()</div>" );

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

	// TODO: inject the metabox visibility CSS properly
	$title_show_value = ( isset( $_GET['title_show'] ) ? $_GET['title_show'] : '' );
	$title_hide       = ( $title_show_value == 'no' || $title_show_value == '0' || $title_show_value == 'hide' );
	if ( $title_hide ) print( '<style>#titlediv {display:none;}</style>' );

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
	$post_new_file_custom = CB2_Query::pass_through_query_string( $post_new_file_custom, array(), array( 'action', 'post' ) );

	// edit-form-advanced.php altered to accept $post_submit_custom
	// post_type will be passed through
	$remove_parameters  = array( 'post', 'action', 'auto_draft_publish_transition' );
	$post_submit_custom = ( isset( $_GET[ 'post_submit_custom' ] ) ? $_GET[ 'post_submit_custom' ] : 'admin.php?page=cb2-post-edit' );
	$post_submit_custom = CB2_Query::pass_through_query_string( $post_submit_custom, array(), $remove_parameters );

	// TODO: inject the metabox visibility CSS properly
	$title_show_value = ( isset( $_GET['title_show'] ) ? $_GET['title_show'] : '' );
	$title_hide       = ( $title_show_value == 'no' || $title_show_value == '0' || $title_show_value == 'hide' );
	if ( $title_hide ) print( '<style>#titlediv {display:none;}</style>' );

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

	// This also happens in /wp-admin/post.php below
	//   get_post() with filter = 'edit'
	//$post_id = $_GET['post'];
	//$post    = get_post( $post_id, OBJECT, 'edit' );
	//CB2_Query::ensure_correct_class( $post );
	//krumo($post); exit();

	$screen = WP_Screen::get( $typenow );
	set_current_screen( $screen );
	require_once( dirname( __FILE__ ) . '/wp-admin/post.php' );
}

function cb2_load_template() {
	global $post;

	if ( isset( $_REQUEST['cb2_load_template'] ) && ! $_POST ) {
		// ------------------------------------------- Inputs
		$ID                = ( isset( $_REQUEST['ID'] )                ? $_REQUEST['ID']        : NULL );
		$post_type         = ( isset( $_REQUEST['post_type'] )         ? $_REQUEST['post_type'] : NULL );
		$context           = ( isset( $_REQUEST['context'] )           ? $_REQUEST['context']   : 'popup' );
		$template_type     = ( isset( $_REQUEST['template_type'] )     ? $_REQUEST['template_type']     : 'edit' );
		$context_post_ID   = ( isset( $_REQUEST['context_post_ID'] )   ? $_REQUEST['context_post_ID']   : NULL );
		$context_post_type = ( isset( $_REQUEST['context_post_type'] ) ? $_REQUEST['context_post_type'] : NULL );
		$template_args     = $_REQUEST;

		// ------------------------------------------- Main post
		// works without a post
		// works with pseudo posts like CB2_Day
		$post = NULL;
		if ( $Class = CB2_PostNavigator::post_type_Class( $post_type ) ) {
			if ( CB2_Database::posts_table( $Class ) )
				$post = CB2_Query::get_post_with_type( $post_type, $ID );
			else if ( method_exists( $Class, 'factory_from_properties' ) )
				$post = $Class::factory_from_properties( $_REQUEST ); // e.g. CB2_Day(date)
			else
				throw new Exception( "Cannot instantiate / get [$Class]" );
		} else if ( WP_DEBUG ) print( "<div class='cb2-WP_DEBUG-small'>load template without post</div>" );
		$templates = CB2::templates( $context, $template_type, FALSE, $templates_considered );

		// ------------------------------------------- extra template_args
		// The outer post displaying a calendar with days in it
		if ( $context_post_ID )
			$template_args[ 'context_post' ] = CB2_Query::get_post_with_type( $context_post_type, $context_post_ID );

		// ------------------------------------------- DEBUG
		if ( WP_DEBUG ) {
			print( "<!-- $ID/$post_type/$context-$template_type -->\n" );
			print( "<!-- Templates considered (in priority order): \n  " . implode( ", \n  ", $templates_considered ) . "\n -->\n" );
			print( "<!--\n" );
			foreach( $_POST as $name => $value ) {
				if      ( is_string( $value ) ) print( "  $name => $value\n" );
				else if ( is_array(  $value ) ) print( "  $name => Array(...)\n" );
				else print( "  $name => ...\n" );
			}
			print( "\n-->\n" );
		}

		// ------------------------------------------- Popup
		print( "<div class='cb2-$context cb2-$context-$template_type cb2-$context-$template_type-$post_type'>" );
		cb2_get_template_part( CB2_TEXTDOMAIN, $templates, '', $template_args );
		print( "<script>setTimeout(function(){
					jQuery('#TB_window').trigger('cb2-popup-appeared');
				}, 0);
			</script>" );
		print( '</div>' );
		exit();
	}
}
add_action( 'admin_init', 'cb2_load_template', 1 );

function cb2_template_save() {
	global $post;
	$html = '';

	// ------------------------------------------- Inputs
	$ID                = ( isset( $_REQUEST['ID'] )                ? $_REQUEST['ID']              : NULL );
	$post_type         = ( isset( $_REQUEST['post_type'] )         ? $_REQUEST['post_type']       : NULL );
	$context           = ( isset( $_REQUEST['context'] )           ? $_REQUEST['context']         : 'save' );
	$template_type     = ( isset( $_REQUEST['template_type'] )     ? $_REQUEST['template_type']   : 'edit' );
	$context_post_ID   = ( isset( $_REQUEST['context_post_ID'] )   ? $_REQUEST['context_post_ID'] : NULL );
	$context_post_type = ( isset( $_REQUEST['context_post_type'] ) ? $_REQUEST['context_post_type'] : NULL );
	$template_args     = $_REQUEST;

	// ------------------------------------------- Main post
	// works without a post
	// works with pseudo posts like CB2_Day
	$post = NULL;
	if ( $Class = CB2_PostNavigator::post_type_Class( $post_type ) ) {
		if ( CB2_Database::posts_table( $Class ) ) {
			$post = CB2_Query::get_post_with_type( $post_type, $ID );
		} else if ( method_exists($Class, 'factory_from_properties' ) ) {
			$post = $Class::factory_from_properties( $_REQUEST ); // e.g. CB2_Day(date)
		} else {
			http_response_code( 500 );
			throw new Exception( "Cannot instantiate / get [$Class]" );
		}
	}
	$templates = CB2::templates( $context, $template_type, FALSE, $templates_considered );

	// ------------------------------------------- extra template_args
	// The outer post displaying a calendar with days in it
	if ( $context_post_ID )
		$template_args[ 'context_post' ] = CB2_Query::get_post_with_type( $context_post_type, $context_post_ID );

	// ------------------------------------------- DEBUG
	if ( WP_DEBUG ) {
		$html .= ( "<!-- $ID/$post_type/$context-$template_type -->\n" );
		$html .= ( "<!-- Templates considered (in priority order): \n  " . implode( ", \n  ", $templates_considered ) . "\n -->\n" );
		$html .= ( "<!--\n" );
		foreach( $_POST as $name => $value ) {
			if      ( is_string( $value ) ) $html .= ( "  $name => $value\n" );
			else if ( is_array(  $value ) ) $html .= ( "  $name => Array(...)\n" );
			else $html .= ( "  $name => ...\n" );
		}
		$html .= ( "\n-->\n" );
	}

	try {
		$html .= cb2_get_template_part( CB2_TEXTDOMAIN, $templates, '', $template_args, TRUE );
	} catch ( Exception $ex ) {
		http_response_code( 500 );
		$message = htmlspecialchars( $ex->getMessage() );
		$html .= ( "<result message='$message'>Server Error</result>" );
	}
	print( $html );
	exit();
}
add_action( 'wp_ajax_cb2_template_save', 'cb2_template_save' );
