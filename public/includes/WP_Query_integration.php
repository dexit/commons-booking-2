<?php
// --------------------------------------------- Misc
add_filter( 'query',            'cb2_wpdb_mend_broken_date_selector' );
add_filter( 'posts_where',      'cb2_posts_where_allow_NULL_meta_query' );
add_filter( 'pre_get_posts',    'cb2_pre_get_posts_query_string_extensions');

// --------------------------------------------- SQL rewrite for custom posts
// All SQL redirect to the wp_cb2_post* views for the custom posts
// This is the base added to pseudo-post-types
// in the pseudo wp_posts views
// TODO: make a static plugin setting
// TODO: analyse potential conflicts with other installed post_id fake plugins
//   based on this plugin
define( 'CB2_WP_DEBUG',                       WP_DEBUG && FALSE );
define( 'CB2_WP_DEBUG_CUTOFF',                300 );
//add_filter( 'query',            'cb2_query_show' );
add_filter( 'query',             'cb2_wpdb_query_select' );
add_filter( 'get_post_metadata', 'cb2_get_post_metadata', 10, 4 );

// --------------------------------------------- Adding posts
// We let auto-drafts be added to wp_posts in the normal way
// causing the usual INSERT
// Then we move them to the custom DB
// when they are UPDATEed using update_post_meta

// --------------------------------------------- Updating posts
// UPDATE queries not trapped: save_post used instead
//add_filter( 'query',            'cb2_wpdb_query_update' );
// save_post fires after saving
// and has only the old post data because it failed to save it
// cb2_add_post_type_actions( 'save_post', 10, 3 );
add_action( 'pre_post_update',      'cb2_pre_post_update',      10, 2 );
//add_action( 'post_updated',         'cb2_post_updated',         10, 3 );
add_filter( 'add_post_metadata',    'cb2_add_post_metadata',    10, 5 );
add_filter( 'update_post_metadata', 'cb2_update_post_metadata', 10, 5 );
add_action( 'add_post_meta',        'cb2_add_post_meta',        10, 3 );
add_action( 'update_post_meta',     'cb2_update_post_meta',     10, 4 );
add_action( 'save_post',            'cb2_save_post_delete_auto_draft', 10, 3 );

// --------------------------------------------- Deleting posts
add_action( 'delete_post',          'cb2_delete_post' );
add_action( 'trashed_post',         'cb2_delete_post' );

// --------------------------------------------- WP_Query Database redirect to views for custom posts
// $wpdb->posts => wp_cb2_view_posts
add_filter( 'pre_get_posts', 'cb2_pre_get_posts_redirect_wpdb' );
add_filter( 'post_results',  'cb2_post_results_unredirect_wpdb', 10, 2 );

// --------------------------------------------- WP Loop control
// Here we change the Wp_Query posts to the correct list
add_filter( 'loop_start',       'cb2_loop_start' );

// --------------------------------------------- Custom post types and templates
add_action( 'init',             'cb2_init_register_post_types' );
add_action( 'init',             'cb2_init_temp_debug_enqueue' );
add_filter( 'post_row_actions', 'cb2_post_row_actions', 10, 2 );

// ------------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------
// Update integration
function cb2_delete_post( $ID ) {
	global $wpdb;

	if ( $post_type = CB_Query::post_type_from_ID( $ID ) ) {
		$Class = CB_Query::schema_type_class( $post_type );
		$post_type_stub = CB_Query::substring_before( $post_type );
		if ( $Class && ! property_exists( $Class, 'database_table' ) || $Class::$database_table ) {
			// Database schematics
			$class_database_table = NULL;
			if ( property_exists( $Class, 'database_table' ) ) $class_database_table = $Class::$database_table;
			else {
				$class_database_table = "cb2_{$post_type_stub}_posts";
			}

			$id_field = NULL;
			if ( property_exists( $Class, 'database_id_field' ) ) $id_field = $Class::$database_id_field;
			else {
				$id_field  = str_replace( 'cb2_', '', $class_database_table );
				$id_field  = substr( $id_field, 0, -1 );
				$id_field .= '_id';
			}

			$id = CB_Query::id_from_ID( $ID );
			$result = $wpdb->delete( "$wpdb->prefix$class_database_table", array( $id_field => $id ) );
			if ( $result === FALSE ) {
				print( "<div id='error-page'><p>$wpdb->last_error</p></div>" );
				exit();
			}
		}
	}
}

function cb2_save_post_delete_auto_draft( $ID, $post, $update ) {
	global $wpdb;

	if ( $post && property_exists( $post, 'post_type' ) ) {
		$post_type = $post->post_type;
		if ( $Class = CB_Query::schema_type_class( $post_type ) ) {
			$post_type_stub = CB_Query::substring_before( $post_type );
			if ( $Class && ! property_exists( $Class, 'database_table' ) || $Class::$database_table ) {
				$id = CB_Query::id_from_ID( $ID );
				if ( is_null( $id ) ) {
					$post = get_post( $id );
					if ( $post && $post->post_status == 'auto-draft' ) {
						// Remove the auto-draft
						// TODO: this will cause the initial Add New -> further edits process to fail
						// because it passes through the wrong id
						// $wpdb->delete( "{$wpdb->prefix}posts", array( 'ID' => $ID ) );
					}
				}
			}
		}
	}
}

function cb2_post_updated( $ID, $post_after, $post_before ) {
	return cb2_pre_post_update( $ID, (array) $post_after );
}

function cb2_pre_post_update( $ID, $data ) {
	global $wpdb;

	if ( $data && isset( $data['post_type'] ) ) {
		$post_type = $data[ 'post_type' ];
		if ( $Class = CB_Query::schema_type_class( $post_type ) ) {
			$post_type_stub = CB_Query::substring_before( $post_type );
			if ( $Class && ! property_exists( $Class, 'database_table' ) || $Class::$database_table ) {
				// Database schematics
				$class_database_table = NULL;
				if ( property_exists( $Class, 'database_table' ) ) $class_database_table = $Class::$database_table;
				else {
					$class_database_table = "cb2_{$post_type_stub}_posts";
				}

				$id_field = NULL;
				if ( property_exists( $Class, 'database_id_field' ) ) $id_field = $Class::$database_id_field;
				else {
					$id_field  = str_replace( 'cb2_', '', $class_database_table );
					$id_field  = substr( $id_field, 0, -1 );
					$id_field .= '_id';
				}

				// Fields and values assembly
				$data          = CB_Query::sanitize_data_for_table( $class_database_table, $data );
				$id = CB_Query::id_from_ID( $ID );
				if ( is_null( $id ) ) {
					$post = get_post( $ID );
					if ( $post ) { //&&  ) ) {
						// This post is currently an auto-draft normal post in wp_posts
						// probably created by the Add New post process
						// because we have not hooked in to the insert_post process
						// Move this post into our structure

						// Important to move the meta-data at the same time
						// Because the record might refuse to insert otherwise
						$metadata    = get_metadata( 'post', $ID );
						$metadata    = CB_Query::sanitize_data_for_table( $class_database_table, $metadata );
						$insert_data = array_merge( $data, $metadata );

						// Allow for tables with no actual needed columns beyond the id
						$result = NULL;
						if ( count( $insert_data ) )
							$result = $wpdb->insert( "$wpdb->prefix$class_database_table", $insert_data );
						else
							$result = $wpdb->query( "INSERT into `$wpdb->prefix$class_database_table` values()" );
						if ( $result === FALSE ) {
							print( "<div id='error-page'><p>$wpdb->last_error</p></div>" );
							exit();
						}
						$id = $wpdb->insert_id;
						if ( CB2_WP_DEBUG ) print( "<div class='cb2-debug cb2-high-debug' style='font-weight:bold;color:#600;'>($Class/$post_type) = INSERTED new post($native_fields_string)</div>" );

						// We need to reset the ID for further edit screens
						if ( in_array( $post->post_status, array( 'draft',  'auto-draft' ) ) ) {
							$ID = CB_Query::ID_from_id_post_type( $id, $post_type );
							wp_redirect( "/wp-admin/post.php?post=$ID&action=edit" );
							exit();
						}

					} else throw new Exception( "Trying to update a [$post->post_status] CB2 post [$post_type] with an invalid ID [$ID]" );
				} else {
					// The post has a normal ID
					// It exists and came from one of the views
					// So update it in its source table
					// Note that the normal UPDATE SQL produced by WP will have no effect
					// because the post does not exist in the wp_posts table
					// Meta-data not needed here because it will be updated separately
					$native_fields = array();
					$values        = array();
					foreach ( $data as $field_name => $field_value ) {
						array_push( $native_fields, "`$field_name` = %s" );
						array_push( $values, $field_value );
					}

					if ( count( $native_fields ) ) {
						$native_fields_string = implode( ',', $native_fields );
						array_push( $values, $id );
						$query = $wpdb->prepare(
							"UPDATE `$wpdb->prefix$class_database_table` SET $native_fields_string
								WHERE `$id_field` = %d",
							$values
						);
						$result = $wpdb->query( $query );
						if ( $result === FALSE ) {
							print( "<div id='error-page'><p>$wpdb->last_error</p></div>" );
							exit();
						}
						if ( CB2_WP_DEBUG ) print( "<div class='cb2-debug cb2-high-debug' style='font-weight:bold;color:#600;'>($Class/$post_type) = $query</div>" );
					}
				}
			}
		}
	}
}

function cb2_get_post_metadata( $type, $ID, $meta_key, $single ) {
	global $wpdb;

	$value = NULL;

	// Ignore pseudo metadata, e.g. _edit_lock
	if ( $meta_key && $meta_key[0] != '_' ) {
		$post = get_post( $ID );
		if ( $post && property_exists( $post, 'post_type' ) ) {
			$post_type = $post->post_type;
			if ( $Class = CB_Query::schema_type_class( $post_type ) ) {
				$id             = CB_Query::id_from_ID( $ID );
				$post_type_stub = CB_Query::substring_before( $post_type );

				if ( ! property_exists( $Class, 'postmeta_table' ) || $Class::$postmeta_table !== FALSE ) {
					$postmeta_table = "cb2_view_{$post_type_stub}meta";
					if ( property_exists( $Class, 'postmeta_table' ) && is_string( $Class::$postmeta_table ) )
						$postmeta_table = $Class::$postmeta_table;
					$query = $wpdb->prepare(
						"SELECT `meta_value` FROM `$wpdb->prefix$postmeta_table` WHERE `meta_key` = %s AND `post_id` = %d",
						array( $meta_key, $id )
					);

					// Run
					// and prevent normal by returning a value
					$value = $wpdb->get_col( $query, 0);
					// The caller calculates the single logic
					//   if ( $single ) $value = $value[0];
					// However, it has a bug
					// so we make it choose an empty string if it cannot be found
					if ( $single && is_array( $value ) && count( $value ) == 0 ) $value = array( '' );
				}
			}
		}
	}

	return $value;
}

function cb2_add_post_meta( $ID, $meta_key, $meta_value ) {
	// We are never adding a record in this scenario
	// Because our data is not normalised
	return cb2_update_post_metadata( NULL, $ID, $meta_key, $meta_value );
}

function cb2_update_post_meta( $meta_id, $ID, $meta_key, $meta_value ) {
	// We are never adding a record in this scenario
	// Because our data is not normalised
	return cb2_update_post_metadata( NULL, $ID, $meta_key, $meta_value );
}

function cb2_add_post_metadata( $allowing, $ID, $meta_key, $meta_value, $unique ) {
	// We are never adding a record in this scenario
	// Because our data is not normalised
	return cb2_update_post_metadata( $allowing, $ID, $meta_key, $meta_value );
}

function cb2_update_post_metadata( $allowing, $ID, $meta_key, $meta_value, $prev_value = NULL ) {
	// Calls cb2_get_post_metadata() first to check for existence
	// Only calls here if it does not already exist
	global $wpdb;

	$prevent = FALSE;

	// Ignore pseudo metadata, e.g. _edit_lock
	if ( $meta_key && $meta_key[0] != '_' ) {
		$post    = get_post( $ID );
		if ( $post && property_exists( $post, 'post_type' ) ) {
			$post_type = $post->post_type;
			if ( $Class = CB_Query::schema_type_class( $post_type ) ) {
				if ( ! property_exists( $Class, 'database_table' ) || $Class::$database_table ) {
					$post_type_stub = CB_Query::substring_before( $post_type );

					// Database schematics
					$class_database_table = NULL;
					if ( property_exists( $Class, 'database_table' ) ) $class_database_table = $Class::$database_table;
					else {
						$class_database_table = "cb2_{$post_type_stub}_posts";
					}

					$id_field = NULL;
					if ( property_exists( $Class, 'database_id_field' ) ) $id_field = $Class::$database_id_field;
					else {
						$id_field  = str_replace( 'cb2_', '', $class_database_table );
						$id_field  = substr( $id_field, 0, -1 );
						$id_field .= '_id';
					}

					// Sanitize and run
					$data = CB_Query::sanitize_data_for_table( $class_database_table, array( $meta_key => $meta_value ) );
					if ( CB2_WP_DEBUG && ! count( $data ) )
						print( "<div class='cb2-debug cb2-high-debug' style='font-weight:bold;color:#600;'>($Class/$post_type) = column [$meta_key] update on [$class_database_table] IGNORED because not present</div>" );
					foreach ( $data as $meta_key => $meta_value ) {
						// Custom query
						$query   = NULL;
						$id      = CB_Query::id_from_ID( $ID );
						if ( empty( $meta_value ) )
							$query = $wpdb->prepare(
								"UPDATE `$wpdb->prefix$class_database_table` set `$meta_key` = NULL where `$id_field` = %d",
								array( $id )
							);
						else
							$query = $wpdb->prepare(
								"UPDATE `$wpdb->prefix$class_database_table` set `$meta_key` = %s where `$id_field` = %d",
								array( $meta_value, $id )
							);

						// Run
						if ( CB2_WP_DEBUG ) print( "<div class='cb2-debug cb2-high-debug' style='font-weight:bold;color:#600;'>($Class/$post_type) = $query</div>" );
						$result = $wpdb->query( $query );
						if ( $result === FALSE ) {
							print( "<div id='error-page'><p>$wpdb->last_error</p></div>" );
							exit();
						}
					}
					// We DO NOT prevent normal
					// because it prevents other meta data from being updated
					$prevent = FALSE;
				}
			}
		}
	}

	// Returning TRUE will prevent any updates
	return ( $prevent ? TRUE : NULL );
}

// ------------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------
// Framework integration
function cb2_init_temp_debug_enqueue() {
	// TODO: move to main
//	wp_enqueue_style( CB_TEXTDOMAIN . '-plugin-styles', plugins_url( 'scratchpad/calendar.css', CB_PLUGIN_ABSOLUTE ), array(), CB_VERSION );
}

function cb2_add_post_type_actions( $action, $priority = 10, $nargs = 1 ) {
	foreach ( CB_Query::schema_types() as $post_type => $Class ) {
		$action_post_type = "{$action}_{$post_type}";
		add_action( $action_post_type, "cb2_{$action}", $priority, $nargs );
	}
}

function cb2_init_register_post_types() {
	foreach ( CB_Query::schema_types() as $post_type => $Class ) {
		if ( ! property_exists( $Class, 'register_post_type' ) || $Class::$register_post_type ) {
			$args = array(
				'label'  => ucfirst($post_type) . 's',
				'labels' => array(
				),
				// 'public'             => TRUE,
				'show_in_nav_menus'  => TRUE,
				'show_ui'            => TRUE,
				'show_in_menu'       => FALSE, // Hides in the admin suite
				'publicly_queryable' => TRUE,

				'has_archive'        => TRUE,
				'show_in_rest'       => TRUE,
				'supports' => array(
					'custom-fields',
					'title',
					'editor',
					'author',
					'thumbnail',
				),
			);
			if ( property_exists( $Class, 'post_type_args' ) )
				$args = array_merge( $args, $Class::$post_type_args );
			// if ( WP_DEBUG ) print( "<div class='cb2-debug'>register_post_type([$post_type])</div>" );
			register_post_type( $post_type, $args );
		}
	}
}

function cb2_post_row_actions( $actions, $post ) {
	global $wpdb;

	$post = CB_Query::ensure_correct_class( $post );
	if ( $post instanceof CB_PostNavigator && method_exists( $post, 'add_actions' ) )
		$post->add_actions( $actions );

	if ( basename( $_SERVER['PHP_SELF'] ) == 'admin.php' && isset( $_GET[ 'page' ] ) ) {
		$page          = $_GET[ 'page' ];
		$action_string = $wpdb->get_var( $wpdb->prepare(
			"SELECT actions FROM {$wpdb->prefix}cb2_admin_pages WHERE menu_slug = %s LIMIT 1",
			array( $page )
		) );
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

function cb2_query_show( $sql ) {
	if ( WP_DEBUG ) print( "<div class='cb2-debug'>$sql</div>" );
	return $sql;
}

// ------------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------
// WP_Query integration
/*
function cb2_query_wrangler_date_filter_callback( $args, $filter ) {
	// Query Wrangler does not have date filter at the moment
	// So we set it here using QW callback option
	$args[ 'date_query' ] = array(
		'after'   => '2018-07-01',
		'before'  => '2018-08-01',
		'compare' => 'week',
	);
	// Multiple post_status not available in QW yet
	$args[ 'post_status' ] = array( 'publish', 'auto-draft' );
	// perioditem-automatic will be missed unless we do this
	$args[ 'post_type'   ] = CB_PeriodItem::$all_post_types;
	return $args;
}
*/

function cb2_pre_get_posts_redirect_wpdb( &$wp_query ) {
	global $wpdb;

	// TODO: Reset the posts to the normal table necessary?
	// maybe it will interfere with other plugins?
	$wpdb->posts = "{$wpdb->prefix}posts";

	if ( isset( $wp_query->query['post_type'] ) ) {
		$post_type = $wp_query->query['post_type'];
		if ( is_array( $post_type ) && count( $post_type ) ) $post_type = array_values( $post_type )[0];
		if ( $Class = CB_Query::schema_type_class( $post_type ) ) {
			if ( ! property_exists( $Class, 'posts_table' ) || $Class::$posts_table !== FALSE ) {
				// perioditem-global => perioditem
				$post_type_stub = CB_Query::substring_before( $post_type );
				if ( property_exists( $Class, 'posts_table' ) && is_string( $Class::$posts_table ) )
					$post_type_stub = $Class::$posts_table;
				$wp_query->old_wpdb_posts    = $wpdb->posts;
				// cb2_view_periodoccurence_posts
				$wpdb->posts    = "{$wpdb->prefix}cb2_view_{$post_type_stub}_posts";
			}

			if ( ! property_exists( $Class, 'postmeta_table' ) || $Class::$postmeta_table !== FALSE ) {
				// perioditem-global => perioditem
				$post_type_stub = CB_Query::substring_before( $post_type );
				if ( property_exists( $Class, 'postmeta_table' ) && is_string( $Class::$postmeta_table ) )
					$post_type_stub = $Class::$postmeta_table;
				$wp_query->old_wpdb_postmeta = $wpdb->postmeta;
				// cb2_view_periodoccurencemeta
				$wpdb->postmeta = "{$wpdb->prefix}cb2_view_{$post_type_stub}meta";
			}
		}
	}
}

function cb2_post_results_unredirect_wpdb( $posts, &$wp_query ) {
	global $wpdb;
	if ( property_exists( $wp_query->old_wpdb_posts ) )    $wpdb->posts    = $wp_query->old_wpdb_posts;
	if ( property_exists( $wp_query->old_wpdb_postmeta ) ) $wpdb->postmeta = $wp_query->old_wpdb_postmeta;
}

function cb2_wpdb_query_select( $query ) {
	// Use wp_cb2_view_{$post_type}_posts instead of wp_posts
	// ALL database queries come through this filter
	// including insert and updates
	global $wpdb;

	if ( $Class = CB_Query::class_from_SELECT( $query ) ) {
		// perioditem-global => perioditem
		$post_type_stub = CB_Query::substring_before( $Class::$static_post_type );

		// Move table requests to the views that include our custom post types
		if ( ! property_exists( $Class, 'posts_table' ) || $Class::$posts_table !== FALSE ) {
			$posts_table = "cb2_view_{$post_type_stub}_posts";
			if ( property_exists( $Class, 'posts_table' ) && is_string( $Class::$posts_table ) )
				$posts_table = $Class::$posts_table;
			$query = preg_replace( "/([^a-z]){$wpdb->prefix}posts([^a-z])/im",    "$1$wpdb->prefix$posts_table$2", $query );
		}

		if ( ! property_exists( $Class, 'postmeta_table' ) || $Class::$postmeta_table !== FALSE ) {
			$postmeta_table = "cb2_view_{$post_type_stub}meta";
			if ( property_exists( $Class, 'postmeta_table' ) && is_string( $Class::$postmeta_table ) )
				$postmeta_table = $Class::$postmeta_table;
			$query = preg_replace( "/([^a-z]){$wpdb->prefix}postmeta([^a-z])/im", "$1$wpdb->prefix$postmeta_table$2",   $query );
		}

		if ( CB2_WP_DEBUG ) {
			$query_truncated = ( strlen( $query ) > CB2_WP_DEBUG_CUTOFF ? substr( $query, 0, CB2_WP_DEBUG_CUTOFF ) . '...' : $query );
			print( "<div class='cb2-debug cb2-high-debug' style='font-weight:bold;'>($Class/$post_type_stub) = $query_truncated</div>" );
		}
	}
	else if ( CB2_WP_DEBUG ) {
		$query_truncated = ( strlen( $query ) > CB2_WP_DEBUG_CUTOFF ? substr( $query, 0, CB2_WP_DEBUG_CUTOFF ) . '...' : $query );
		print( "<div class='cb2-debug cb2-low-debug' style='color:#777'>(IGNORED) = $query_truncated</div>" );
	}

	return $query;
}

function cb2_loop_start( &$wp_query ) {
	// Convert the WP_Query CB post_type results from WP_Post in to CB_* objects
	if ( $wp_query instanceof WP_Query
		&& property_exists( $wp_query, 'posts' )
		&& is_array( $wp_query->posts )
	) {
		// Create the CB_PeriodItem objects from the WP_Post results
		// This will also create all the associated CB_* Objects like CB_Week
		// WP_Posts will be left unchanged
		$wp_query->posts = CB_Query::ensure_correct_classes( $wp_query->posts );

		// Check to see which schema has been requested and switch it
		if ( isset( $wp_query->query['date_query']['compare'] ) ) {
			if ( $schema = $wp_query->query['date_query']['compare'] ) {
				$wp_query->posts = CB_Query::schema_type_all_objects( $schema );

				// Update WP_Query settings with our custom posts
				$wp_query->post_count  = count( $wp_query->posts );
				$wp_query->found_posts = (boolean) $wp_query->post_count;
				$wp_query->post        = ( $wp_query->found_posts ? $wp_query->posts[0] : NULL );
			}
		}
	}
}

// ------------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------
// Framework changes and fixes
function cb2_wpdb_mend_broken_date_selector( $query ) {
	// Mend the broken ORDER BY for the date filter in WordPress 4.x
	if ( preg_match( '/DISTINCT YEAR\(post_date\), MONTH\(post_date\)/mi', $query ) )
		$query = preg_replace( '/ORDER BY post_date DESC/mi', 'ORDER BY YEAR(post_date), MONTH(post_date) DESC', $query );
	return $query;
}

function cb2_posts_where_allow_NULL_meta_query( $where ) {
	$where = preg_replace(
		"/CAST\(([a-z0-9_]+)\.([a-z0-9_]+) AS SIGNED\)\s*IN\s*\(([^)]*)'NULL'/mi",
		'CAST(\1.\2 AS SIGNED) IN (\3NULL',
		$where
	);
	$where = preg_replace(
		"/CAST\(([a-z0-9_]+)\.([a-z0-9_]+) AS SIGNED\)\s*=\s*'NULL'/mi",
		'CAST(\1.\2 AS SIGNED) = NULL',
		$where
	);

	return $where;
}

function cb2_pre_get_posts_query_string_extensions() {
	// Allows meta limits on the main WP_Query built from the query string
	global $wp_query;

	// $meta_query = $wp_query->query_vars[ 'meta_query' ];
	// if ( ! $meta_query ) $meta_query = array( 'relation' => 'AND' );

  if ( isset( $_GET[ 'meta_key' ] ) )   set_query_var( 'meta_key',   $_GET[ 'meta_key' ] );
  if ( isset( $_GET[ 'meta_value' ] ) ) set_query_var( 'meta_value', $_GET[ 'meta_value' ] );

  $meta_query_items = array();
	if ( isset( $_GET[ 'location_ID' ] ) )             $meta_query_items[ 'location_clause' ]    = array( 'key' => 'location_ID', 'value' => $_GET[ 'location_ID' ] );
	if ( isset( $_GET[ 'item_ID' ] ) )                 $meta_query_items[ 'item_clause' ]        = array( 'key' => 'item_ID',     'value' => $_GET[ 'item_ID' ] );
	if ( isset( $_GET[ 'period_status_type_id' ] ) )   $meta_query_items[ 'period_status_type_clause' ] = array( 'key' => 'period_status_type_id', 'value' => $_GET[ 'period_status_type_id' ] );
	if ( isset( $_GET[ 'period_status_type_name' ] ) ) $meta_query_items[ 'period_status_type_clause' ] = array( 'key' => 'period_status_type_name', 'value' => $_GET[ 'period_status_type_name' ] );

	if ( $meta_query_items ) {
		// Include the auto-draft which do not have meta
		$meta_query[ 'relation' ]       = 'OR';
		$meta_query[ 'without_meta' ]   = CB_Query::$without_meta;
		$meta_query_items[ 'relation' ] = 'AND';
		$meta_query[ 'items' ]          = $meta_query_items;
		set_query_var( 'meta_query', $meta_query );
	}
}


