<?php
define( CB2_WP_ADMIN_DIR, 'commons-booking-2/wp-admin/' );

function cb2_notification_bubble_in_admin_menu() {
  global $menu, $submenu;

  foreach ($menu as &$amenuitem) {
    if ( is_array($amenuitem) ) {
      $menuitem = &$amenuitem[0];
      if ( substr( $menuitem, -1 ) == ')' ) {
        $menuitem = preg_replace( '/\(([0-9]+)\)$/', '<span class="update-plugins count-$1"><span class="update-count">$1</span></span>', $menuitem );
      }
      else if ( substr( $menuitem, -1 ) == ']' ) {
        $menuitem = preg_replace( '/\[([0-9]+)\]$/', '<span class="menu-item-number count-$1">$1</span>', $menuitem );
      }
    }
  }

  foreach ($submenu as $menu_name => &$menuitems) {
    $first = TRUE;
    foreach ($menuitems as &$amenuitem) {
      if ( is_array($amenuitem) ) {
        $menuitem = &$amenuitem[0];
        if ( $first ) {
          $menuitem = preg_replace( '/\(([0-9]+)\)$|\[([0-9]+)\]$/', '', $menuitem );
        } else {
          if ( substr( $menuitem, -1 ) == ')' ) {
            $menuitem = preg_replace( '/\(([0-9]+)\)$/', '<span class="update-plugins count-$1"><span class="update-count">$1</span></span>', $menuitem );
          }
          else if ( substr( $menuitem, -1 ) == ']' ) {
            $menuitem = preg_replace( '/\[([0-9]+)\]$/', '<span class="menu-item-number count-$1">$1</span>', $menuitem );
          }
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

	$pages = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}cb2_admin_pages", OBJECT_K );
	foreach ( $pages as $menu_slug => $details ) {
		$capability = $details->capability;
		if ( ! $capability ) $capability = $capability_default;
		$parent_slug = $details->parent_slug;
		if ( ! $parent_slug ) $parent_slug = 'cb2';

		$title = preg_replace( '/\%.+\%/', '', $details->page_title );
		add_submenu_page( $parent_slug, $title, $details->menu_title, $capability, $menu_slug, 'cb2_settings_list_page' );
	}

	// post-new => edit-form-advanced.php
	// The following line directly accesses the plugin post-new.php
	// However, post-new.php is loaded directly WITHOUT calling the hook function
	// so we cannot set titles and things
	// $wp_admin_dir = 'commons-booking-2/wp-admin';
	// add_submenu_page( 'cb2', 'Add New', NULL, $capability_default, CB2_WP_ADMIN_DIR . 'post-new.php' );
	// Sending through ?post_type seems to prevent the submenu_page from working
	// This hook, in combination with the capability in the add_submenu_page() call
	// allows the page to load
	add_submenu_page( 'cb2', 'Add New', NULL, $capability_default, 'cb-post-new' );
	add_filter( 'admin_page_cb-post-new', 'cb2_settings_post_new', 0, 10 );
}
add_action( 'admin_menu', 'cb2_admin_init_menus' );

// ---------------------------------------------------------- Pages
function cb2_options_page() {
	global $wpdb;

	print( '<h1>Commons Booking 2</h1>' );
	print( '<ul>' );
	$capability_default   = 'manage_options';
	$pages = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}cb2_admin_pages", OBJECT_K );
	foreach ( $pages as $menu_slug => $details ) {
		$capability = $details->capability;
		if ( ! $capability ) $capability = $capability_default;
		$parent_slug = $details->parent_slug;
		if ( ! $parent_slug ) $parent_slug = 'cb2';

		if ( current_user_can( $capability ) )
			print( "<li><a $disabled href='admin.php?page=$menu_slug'>$details->page_title</a></li>" );
		else
		  print( "<li>$details->page_title</li>" );
	}
	print( '</ul>' );
}

function cb2_settings_list_page() {
	global $wpdb;

	if ( isset( $_GET[ 'page' ] ) ) {
		$page    = $_GET[ 'page' ];
		$typenow = NULL;
		$title   = NULL;

		// Bring stored parameters on to the query-string
		$details = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}cb2_admin_pages WHERE menu_slug = %s LIMIT 1",
			array( $page )
		), OBJECT_K );
		if ( count( $details ) ) {
			$details_page         = $details[$page];
			$title                = $details_page->page_title;
			$replacement          = FALSE;
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

			// Replace tokens in the title and post_new
			foreach ( $_GET as $name => $value ) {
				$token = "%$name%";
				if ( substr( $name, -3 ) == '_ID' ) {
					if ( $post = get_post( $value ) )
						$value = $post->post_title;
					if ( strstr( $title, $token ) ) {
						$replacement          = TRUE;
						$title                = str_replace( $token, $value, $title );
					}
					$post_new_file_custom = str_replace( $token, $value, $post_new_file_custom );
				}
			}
			if ( $replacement ) $title = preg_replace( '/%/', '', $title );
			else                $title = preg_replace( '/%.+%/', '', $title );

			// WP_Query arguments
			$wp_query_args = $details_page->wp_query_args;
			$add_new_query = $details_page->wp_query_args;
			foreach ( explode( '&', $wp_query_args ) as $arg_detail_string ) {
				$arg_details   = explode( '=', $arg_detail_string, 2 );
				$name          = $arg_details[0];
				$value         = ( count( $arg_details ) > 1 ? $arg_details[1] : '' );
				$_GET[ $name ] = $value;
				switch ( $name ) {
					case 'post_type':  $typenow = $value; break;
				}
			}

			// This is a COPY of the normal wp-admin file
			$screen = WP_Screen::get( $typenow );
			set_current_screen( $screen );
			require_once( dirname( __FILE__ ) . '/edit.php' );
		} else throw new Exception( 'CB2 admin page cannot find its location in the db' );
	} else throw new Exception( 'CB2 admin page does not understand its location. A querystring ?page= parameter is needed' );

	return TRUE;
}

function cb2_settings_post_new() {
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
		if ( $replacement ) $title = preg_replace( '/%/', '', $title );
		else                $title = preg_replace( '/%.+%/', '', $title );
	}

	$typenow = $_GET[ 'post_type' ];

	// This is a COPY of the normal wp-admin file
	$screen = WP_Screen::get( $typenow );
	set_current_screen( $screen );
	require_once( dirname( __FILE__ ) . '/post-new.php' );
}

