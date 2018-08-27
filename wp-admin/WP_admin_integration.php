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
function cb2_admin_pages() {
	// %token% replacement happens on ALL parameters.
	// If any tokens are replaced then %(x)% => x texts are included.
	// All %token_ID% ending in _ID are converted to posts and the post_title inserted instead of the ID
	global $wpdb;

	$basic_interface = array(
		'cb2-holidays'      => (object) array(
			'parent_slug'   => '',
			'page_title'    => 'Holidays %(for)% %location_ID%',
			'menu_title'    => 'holidays',
			'capability'    => NULL,
			'function'      => NULL,
			'wp_query_args' => 'post_type=periodent-global&period_status_type_ID=100000009&post_title=Holidays',
			'actions'       => NULL,
			'post_new_page' => NULL,
			'description'   => 'Edit the global holidays, and holidays for specific locations.',
		),
		'cb2-items'         => (object) array(
			'parent_slug' => '',
			'page_title' => 'Items',
			'menu_title' => 'items',
			'capability' => NULL,
			'function'   => NULL,
			'wp_query_args' => 'post_type=item',
			'actions'    => '<a href="?page=cb2-repairs&item_ID=%ID%">Manage Repairs</a>,<a href="?page=cb2-timeframes&item_ID=%ID%">Manage Availability</a>',
			'post_new_page' => NULL,
			'count'         => "select count(*) from {$wpdb->prefix}posts where post_type='item' and post_status='publish'",
		),
		'cb2-repairs'       => (object) array(
			'parent_slug' => '',
			'indent'      => 1,
			'page_title' => 'Repairs %(for)% %location_ID% %item_ID%',
			'menu_title' => 'repairs',
			'capability' => NULL,
			'function'   => NULL,
			'wp_query_args' => 'post_type=periodent-user&period_status_type_ID=100000005',
			'actions'    => NULL,
			'post_new_page' => NULL,
		),
		'cb2-locations'     => (object) array(
			'parent_slug' => '',
			'page_title' => 'Locations',
			'menu_title' => 'locations',
			'capability' => NULL,
			'function'   => NULL,
			'wp_query_args' => 'post_type=location',
			'actions'    => '<a href="?page=cb2-opening-hours&location_ID=%ID%">Manage Opening Times</a>, <a href="?page=cb2-timeframes&location_ID=%ID%">Manage Item Availability</a>',
			'post_new_page' => NULL,
			'count'         => "select count(*) from {$wpdb->prefix}posts where post_type='location' and post_status='publish'",
		),
		'cb2-opening-hours' => (object) array(
			'parent_slug' => '',
			'indent'      => 1,
			'page_title' => 'Opening Hours %(for)% %location_ID%',
			'menu_title' => 'opening hours',
			'capability' => NULL,
			'function'   => NULL,
			'wp_query_args' => 'post_type=periodent-location&period_status_type_ID=100000004&post_title=Opening Hours %(for)% %location_ID%',
			'actions'    => NULL,
			'post_new_page' => NULL,
		),
		'cb2-timeframes'    => (object) array(
			'parent_slug' => '',
			'indent'      => 1,
			'page_title' => 'Item timeframes %(for)% %location_ID%',
			'menu_title' => 'item timeframes',
			'capability' => NULL,
			'function'   => NULL,
			'wp_query_args' => 'post_type=periodent-timeframe',
			'actions'    => NULL,
			'post_new_page' => NULL,
		),
	);

	$advanced_interface = array(
		'cb2-periods' => (object) array(
			'parent_slug'   => '',
			'page_title'    => 'Periods',
			'menu_title'    => 'periods',
			'capability'    => NULL,
			'function'      => NULL,
			'wp_query_args' => 'post_type=period',
			'actions'       => NULL,
			'post_new_page' => NULL,
			'count'         => "select count(*) from {$wpdb->prefix}cb2_periods",
		),

		'cb2-period-globals' => (object) array(
			'parent_slug'   => '',
			'indent'        => 1,
			'page_title'    => 'Period Globals',
			'menu_title'    => 'period globals',
			'capability'    => NULL,
			'function'      => NULL,
			'wp_query_args' => 'post_type=periodent-global',
			'actions'       => NULL,
			'post_new_page' => NULL,
			'count'         => "select count(*) from {$wpdb->prefix}cb2_global_period_groups",
		),
		'cb2-period-locations' => (object) array(
			'parent_slug' => '',
			'indent'        => 1,
			'page_title' => 'Period Locations',
			'menu_title' => 'period locations',
			'capability' => NULL,
			'function'   => NULL,
			'wp_query_args' => 'post_type=periodent-location',
			'actions'    => NULL,
			'post_new_page' => NULL,
			'count'         => "select count(*) from {$wpdb->prefix}cb2_location_period_groups",
		),
		'cb2-period-timeframes' => (object) array(
			'parent_slug' => '',
			'indent'        => 1,
			'page_title' => 'Period Timeframes',
			'menu_title' => 'period timeframes',
			'capability' => NULL,
			'function'   => NULL,
			'wp_query_args' => 'post_type=periodent-timeframe',
			'actions'    => NULL,
			'post_new_page' => NULL,
			'count'         => "select count(*) from {$wpdb->prefix}cb2_timeframe_period_groups",
		),
		'cb2-period-users' => (object) array(
			'parent_slug' => '',
			'indent'        => 1,
			'page_title' => 'Period Users',
			'menu_title' => 'period users',
			'capability' => NULL,
			'function'   => NULL,
			'wp_query_args' => 'post_type=periodent-user',
			'actions'    => NULL,
			'post_new_page' => NULL,
			'count'         => "select count(*) from {$wpdb->prefix}cb2_timeframe_user_period_groups",
		),

		'cb2-period-groups' => (object) array(
			'parent_slug' => '',
			'page_title' => 'Period Groups',
			'menu_title' => 'period groups',
			'capability' => NULL,
			'function'   => NULL,
			'wp_query_args' => 'post_type=periodgroup',
			'actions'    => NULL,
			'post_new_page' => NULL,
			'count'         => "select count(*) from {$wpdb->prefix}cb2_period_groups",
		),
		'cb2-periodstatustypes' => (object) array(
			'parent_slug' => '',
			'page_title' => 'Period Types',
			'menu_title' => 'period types',
			'capability' => NULL,
			'function'   => NULL,
			'wp_query_args' => 'post_type=periodstatustype',
			'actions'    => NULL,
			'post_new_page' => NULL,
			'count'         => "select count(*) from {$wpdb->prefix}cb2_period_status_types",
		),
	);

	// Admin advanced markup
	$first = 'cb2-first';
	foreach ( $advanced_interface as &$menu_item ) {
		$menu_item->first      = $first;
		$menu_item->advanced   = TRUE;
		$first = '';
	}

	$menu_interface = $basic_interface;
	if ( WP_DEBUG && TRUE ) $menu_interface = array_merge( $menu_interface, $advanced_interface );

	// Menu adornments
	foreach ( $menu_interface as &$menu_item ) {
		if ( property_exists( $menu_item, 'count' ) ) {
			$count = $wpdb->get_var( $menu_item->count );
			$menu_item->menu_title .= " ($count)";
		}
		if ( property_exists( $menu_item, 'indent' ) )
			$menu_item->menu_title = str_repeat( '&nbsp;&nbsp;', $menu_item->indent ) . $menu_item->menu_title;
		if ( property_exists( $menu_item, 'advanced' ) )
			$menu_item->menu_title = "<span class='cb2-advanced-menu-item $menu_item->first'>$menu_item->menu_title</span>";
		if ( property_exists( $menu_item, 'description' ) )
			$menu_item->menu_title = "<span title='$menu_item->description'>$menu_item->menu_title</span>";
	}

	return $menu_interface;
}

function cb2_post_row_actions( $actions, $post ) {
	global $wpdb;

	// Move all edit actions to our custom screens
	if ( isset( $actions['edit'] ) )
		$actions['edit'] = "<a href='admin.php?page=cb-post-edit&post=$post->ID&post_type=$post->post_type&action=edit' aria-label='Edit &#8220;$post->post_title&#8221;'>Edit</a>";

	$post = CB_Query::ensure_correct_class( $post );
	if ( $post instanceof CB_PostNavigator && method_exists( $post, 'add_actions' ) )
		$post->add_actions( $actions, $post );

	if ( basename( $_SERVER['PHP_SELF'] ) == 'admin.php' && isset( $_GET[ 'page' ] ) ) {
		$page          = $_GET[ 'page' ];
		$action_string = ( isset( cb2_admin_pages()[$page] ) ? cb2_admin_pages()[$page]->actions : NULL );
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

function cb2_admin_init_menus() {
	global $wpdb;

	$capability_default   = 'manage_options';
	$notifications_string = ' (3)';
  add_menu_page( 'CB2', "CB2$notifications_string", $capability_default, 'cb2', 'cb2_options_page', 'dashicons-video-alt' );

	foreach ( cb2_admin_pages() as $menu_slug => $details ) {
		$capability = $details->capability;
		if ( ! $capability ) $capability = $capability_default;
		$parent_slug = $details->parent_slug;
		if ( ! $parent_slug ) $parent_slug = 'cb2';

		$title = preg_replace( '/\%.+\%/', '', $details->page_title );
		add_submenu_page( $parent_slug, $title, $details->menu_title, $capability, $menu_slug, 'cb2_settings_list_page' );
	}

	// post-new.php (setup) => edit-form-advanced.php (form)
	// The following line directly accesses the plugin post-new.php
	// However, post-new.php is loaded directly WITHOUT calling the hook function
	// so we cannot set titles and things
	// $wp_admin_dir = 'commons-booking-2/wp-admin';
	// add_submenu_page( 'cb2', 'Add New', NULL, $capability_default, '/commons-booking-2/admin/post-new.php' );
	// Sending through ?post_type seems to prevent the submenu_page from working
	// This hook, in combination with the capability in the add_submenu_page() call
	// allows the page to load
	add_submenu_page( 'cb2', 'Add New', NULL, $capability_default, 'cb-post-new' );
	add_filter( 'admin_page_cb-post-new', 'cb2_settings_post_new', 0, 10 );
	add_submenu_page( 'cb2', 'Edit Post', NULL, $capability_default, 'cb-post-edit' );
	add_filter( 'admin_page_cb-post-edit', 'cb2_settings_post_edit', 0, 10 );
}
add_action( 'admin_menu', 'cb2_admin_init_menus' );

// ---------------------------------------------------------- Pages
function cb2_options_page() {
	// main CB2 options page
	global $wpdb;

	print( '<h1>Commons Booking 2</h1>' );
	print( '<ul>' );
	$capability_default = 'manage_options';
	foreach ( cb2_admin_pages() as $menu_slug => $menu_item ) {
		$class = '';
		$capability = $menu_item->capability;
		if ( ! $capability ) $capability = $capability_default;
		$parent_slug = $menu_item->parent_slug;
		if ( ! $parent_slug ) $parent_slug = 'cb2';

		$title = preg_replace( '/\%.+\%/', '', $menu_item->page_title );
		if ( $menu_item->advanced ) $class .= ' cb2-advanced-menu-item';
		if ( current_user_can( $capability ) ) {
			print( "<li><a class='$class' href='admin.php?page=$menu_slug'>$title</a>" );
			if ( property_exists( $menu_item, 'description' ) )
				print( "<p class='cb2-description'>$menu_item->description</p>" );
			print( "</li>" );
		} else
		  print( "<li class='$class'>$title</li>" );
	}
	print( '</ul>' );
}

function cb2_settings_list_page() {
	// edit.php: presents the WP_List_Table using _get_list_table(...)
	global $wpdb;

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

			$title                = $details_page->page_title;
			$post_new_file_custom = ( $details_page->post_new_page ? $details_page->post_new_page : 'admin.php?page=cb-post-new' );

			// Append input query string to post_new
			if ( count( $_GET ) ) {
				$existing_query_string = array();
				if ( strchr( $post_new_file_custom, '?' ) ) {
					$existing_query_string_pairs = explode( '&', explode( '?', $post_new_file_custom, 2 )[1] );
					foreach ( $existing_query_string_pairs as $value ) $existing_query_string[ CB_Query::substring_before( $value, '=' ) ] = 1;
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
			$wp_query_args = $details_page->wp_query_args;
			$add_new_query = $details_page->wp_query_args;
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
			require_once( dirname( __FILE__ ) . '/edit.php' );
		} else throw new Exception( 'CB2 admin page cannot find its location in the db' );
	} else throw new Exception( 'CB2 admin page does not understand its location. A querystring ?page= parameter is needed' );

	return TRUE;
}

function cb2_settings_post_new() {
	// post-new.php (setup) => edit-form-advanced.php (form)
	$title = 'Add New';
	if ( isset( $_GET[ 'add_new_label' ] ) ) $title = $_GET[ 'add_new_label' ];
	else {
		// e.g. Add New Location Holiday for Cargonomia
		if ( isset( $_GET[ 'post_type' ] ) )               $title .= ' ' . ucfirst( CB_Query::substring_after( ucfirst( $_GET[ 'post_type' ] ) ) );
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

	// Global params used in included file
	$typenow = $_GET[ 'post_type' ];

	// This is a COPY of the normal wp-admin file
	$screen = WP_Screen::get( $typenow );
	set_current_screen( $screen );
	require_once( dirname( __FILE__ ) . '/post-new.php' );
}

function cb2_settings_post_edit() {
	// post.php
	$title = 'Edit Post';
	if ( isset( $_GET[ 'add_new_label' ] ) ) $title = $_GET[ 'add_new_label' ];
	else {print($post_id);
		// e.g. Add New Location Holiday for Cargonomia
		if ( isset( $_GET[ 'post_type' ] ) )               $title .= ' ' . ucfirst( CB_Query::substring_after( ucfirst( $_GET[ 'post_type' ] ) ) );
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

	// Global params used in included file
	$typenow = $_GET[ 'post_type' ];
	$action  = 'edit';

	// This is a COPY of the normal wp-admin file
	$screen = WP_Screen::get( $typenow );
	set_current_screen( $screen );
	require_once( dirname( __FILE__ ) . '/post.php' );
}

