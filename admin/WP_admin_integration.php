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
	global $menu_interface;

	require_once( 'menu_array.php' );

	return $menu_interface;
}

function cb2_metaboxes() {
	$hidden_class      = 'hidden';
	$metabox_wizard_ids = ( isset( $_GET['metabox_wizard_ids'] ) ? $_GET['metabox_wizard_ids'] : '' );
	$metabox_wizard_ids = ( $metabox_wizard_ids ? explode( ',', $metabox_wizard_ids ) : array() );
	$requests_only      = (bool) count( $metabox_wizard_ids );

	foreach ( CB2_PostNavigator::post_type_classes() as $post_type => $Class ) {
		if ( CB2_Query::has_own_method( $Class, 'metaboxes' ) ) {
			foreach ( $Class::metaboxes() as $i => $metabox ) {
				if ( ! isset( $metabox['id'] ) )           $metabox['id']           = "{$Class}_metabox_{$i}";
				if ( ! isset( $metabox['object_types'] ) ) $metabox['object_types'] = array( $post_type );
				if ( ! isset( $metabox['priority'] ) )     $metabox['priority']     = 'low'; // Under the standard boxes

				$id               = $metabox['id'];
				$debug_only_value = ( isset( $metabox['debug-only'] ) ? $metabox['debug-only'] : FALSE );
				$debug_only       = ( $debug_only_value == TRUE || $debug_only_value == 'yes' || $debug_only_value == '1' );
				$on_request       = ( isset( $metabox['on_request'] ) ? $metabox['on_request'] : FALSE );
				$requested        = in_array( $id, $metabox_wizard_ids );
				$include_metabox  = ( ! $requests_only && ! $on_request ) || ( $requests_only && $requested );

				if ( $include_metabox ) {
					// Meta-box level visibility by query-string
					$query_name = "{$id}_show";
					$show_value = ( isset( $_GET[$query_name] ) ? $_GET[$query_name] : '' );
					$query_hide = ( $show_value === FALSE || $show_value == 'no' || $show_value == '0' || $show_value == 'hide' );
					if ( $query_hide || ( $debug_only && ! WP_DEBUG ) ) {
						// TODO: inject this metabox visibility CSS properly
						print( "<style>#$id {display:none;}</style>" );
						// This below line affects ALL fields, not the container
						//$metabox['classes'] = ( isset( $field['classes'] ) ? $field['classes'] . " $hidden_class" : $hidden_class );
					}

					if ( $debug_only )
						$metabox['title'] = '<span class="cb2-WP_DEBUG">' . ( isset( $metabox['title'] ) ? $metabox['title'] : '' ) . '</span>';

					foreach ( $metabox['fields'] as &$field ) {
						$field_id = $field['id'];
						$name     = ( isset( $field['name'] ) ? $field['name'] : '<no field name>' );
						$type     = $field['type'];

						// Live hiding and showing of fields by query-string
						$query_name = "{$field_id}_show";
						$show_value = ( isset( $_GET[$query_name] ) ? $_GET[$query_name] : '' );
						$query_hide = ( $show_value === FALSE || $show_value == 'no' || $show_value == '0' || $show_value == 'hide' );
						if ( $query_hide ) {
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
				} // on_request
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
	$page          = $_GET['page'];
	$all_text      = __( 'All' );
	$trash_text    = __( 'Trash' );
	$calendar_text = '<span class="cb2-todo">' . __( 'Calendar' ) . '</span>';
	$views = array(
		'all'      => "<a href='admin.php?page=$page&post_status=publish'>$all_text</span></a>",
		'trash'    => "<a href='admin.php?page=$page&post_status=trash'>$trash_text</span></a>",
		'calendar' => "<a href='admin.php?page=$page&view=calendar'>$calendar_text</span></a>",
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
	// notifications_string cancelled because too long with "CommonsBooking" title
	$notifications_string = ''; //( $bookings_count ? " ($bookings_count)" : '' );
	add_menu_page( 'CB2', "CommonsBooking$notifications_string", $capability_default, CB2_MENU_SLUG, 'cb2_dashboard_page', 'dashicons-admin-post' );
	add_submenu_page( CB2_MENU_SLUG, 'Dashboard', 'Dashboard', 'manage_options', CB2_MENU_SLUG, 'cb2_dashboard_page' );
	add_options_page( 'CommonsBooking', 'CommonsBooking', 'manage_options', 'cb2-options', 'cb2_options_page' );

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
function cb2_dashboard_page() {
	// main CB2 options page
	global $wpdb;

	print( '<h1 class="cb2-todo">Commons Booking 2 Dashboard <a href="options-general.php?page=cb2-options">settings</a></h1>' );
}

function cb2_options_page() {
	require_once( 'options_page.php' );
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
	require_once( 'reflection.php' );
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

	$screen = WP_Screen::get( $typenow );
	set_current_screen( $screen );
	require_once( dirname( __FILE__ ) . '/wp-admin/post.php' );
}

