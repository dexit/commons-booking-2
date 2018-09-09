<?php
// --------------------------------------------- Database model integration
/*
 * ---- Database schema
 * Normal tables have 2 associated views:
 *   wp_cb2_<table name> e.g. wp_cb2_periods
 *     => wp_cb2_view_<table name>_posts: equivalent of wp_posts
 *     => wp_cb2_view_<table name>meta:   equivalent of wp_postmeta
 * <table name> MUST == associated <post_type>
 * during queries for custom registered <post_type>,
 * the global $wpdb->prefix is changed to wp_cb2_view_<post_type>
 * thus redirecting the generated SQL query temporarily to the custom views.
 *
 * ---- Class Handlers
 * A special registered Class MUST handle the resultant posts
 * registering itself using CB_Query::register_schema_type( <Class name> )
 * which will cause the global $post iterator
 * with have_posts(), the_post() and global $post
 * to iterate through <Class name>s instead of WP_Posts
 * This can also be achieved manually with
 *      CB_Query::get_post_with_type( $post_ID )
 *   or CB_Query::ensure_correct_classes( $posts )
 *
 * ---- Generated Fake Posts
 * Generated custom posts have post ID ranges to avoid conflict with normal WordPress posts
 * These are controlled by wp_cb2_post_types
 * Each post_type has a start ID, for example: period post_type starts at 1000000000
 * Every Class Handler has an id and an ID:
 *   id: the native identifier in the native table, e.g. the period_id 1
 *   ID: the generated fake post identifier, e.g. $post->ID 1000000001
 *
 * ---- Performace tuning with triggers
 * Some of the views can be slow when returing normalised meta-data
 * for millions of generated PeriodItems, which are then filtered.
 * So PeriodItems DO NOT use a wp_cb2_view_perioditemmeta view directly.
 * Instead triggers on the native tables sync the metadata in to wp_postmeta
 * so that normal WordPress metadata queries work.
 * The triggers use wp_cb2_view_perioditemmeta to sync the metadata during post saves()
 * of periods or entities.
 */
global $extra_processing_properties;
$extra_processing_properties = new stdClass();
$extra_processing_properties->dependency_depth = 0;

// --------------------------------------------- Misc
add_filter( 'query',            'cb2_query_debug' );
add_filter( 'query',            'cb2_wpdb_mend_broken_date_selector' );
add_filter( 'posts_where',      'cb2_posts_where_allow_NULL_meta_query' );
add_filter( 'pre_get_posts',    'cb2_pre_get_posts_query_string_extensions');
add_filter( 'query_vars',       'cb2_query_vars' );

// --------------------------------------------- SQL rewrite for custom posts
// All SQL redirect to the wp_cb2_post* views for the custom posts
// This is the base added to pseudo-post-types
// in the pseudo wp_posts views
// TODO: make a static plugin setting
// TODO: analyse potential conflicts with other installed post_id fake plugins
//   based on this plugin
add_filter( 'query',             'cb2_wpdb_query_select' );
add_filter( 'get_post_metadata', 'cb2_get_post_metadata', 10, 4 );

// --------------------------------------------- Adding / Updating posts
// We let auto-drafts be added to wp_posts in the normal way
// causing the usual INSERT
// Then we move them to the custom DB
// when they are UPDATEed using update_post() => save_post hook
//
// UPDATE queries not trapped: save_post used instead
//   add_filter( 'query', 'cb2_wpdb_query_update' );
// save_post fires after saving wp_posts AND wp_postmeta
//
// CMB2 MetaBoxes DO NOT use meta-data!
// instead, they hook in to the save_post and write the meta-data manually
// so there will be no meta-data available at pre_post_update stage
define( 'CB2_DS_PRIORITY',  100 );
define( 'CB2_MTN_PRIORITY', 140 );
if ( CB2_DEBUG_SAVE ) add_action( 'save_post', 'cb2_save_post_debug', CB2_DS_PRIORITY, 3 ); // Just print out debug info
add_action( 'save_post', 'cb2_save_post_move_to_native',              110, 3 ); // Create $native_ID, and $ID
add_action( 'save_post', 'cb2_save_post_post_actions',                120, 3 ); // $CB_object->post_post_update()
add_action( 'save_post', 'cb2_save_post_delete_auto_draft',           130, 3 ); // original wp_posts $post and $ID
add_action( 'save_post', 'cb2_save_post_redirect_to_native_post', CB2_MTN_PRIORITY, 3 ); // new $ID

// Direct metadata updates
// CMB2 MetaBoxes DO NOT use meta-data!
// instead, they hook in to the save_post and write the meta-data manually
// so there will be no meta-data available at pre_post_update stage
// they also DO NOT use AJAX writing of values to meta-data
add_filter( 'add_post_metadata',    'cb2_add_post_metadata',       10, 5 );
add_filter( 'update_post_metadata', 'cb2_update_post_metadata',    10, 5 );
add_action( 'add_post_meta',        'cb2_add_post_meta',           10, 3 );
add_action( 'update_post_meta',     'cb2_update_post_meta',        10, 4 );

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

// ------------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------
// Update/Delete integration
function cb2_delete_post( $ID ) {
	global $wpdb;

	if ( $post_type = CB_Query::post_type_from_ID( $ID ) ) {
		if ( $Class = CB_Query::schema_type_class( $post_type ) ) {
			if ( $class_database_table = CB_Database::database_table( $Class ) ) {
				if ( $id_field = CB_Database::id_field( $Class ) ) {
					if ( $id = CB_Query::id_from_ID_with_post_type( $ID, $post_type ) ) {
						$result = $wpdb->delete( "$wpdb->prefix$class_database_table", array( $id_field => $id ) );
						if ( $result === FALSE ) {
							print( "<div id='error-page'><p>$wpdb->last_error</p></div>" );
							exit();
						} // Still a WP_Post?
					}
				} else throw new Exception( "Cannot delete [$Class] because no id field" );
			}
		}
	}
}

function cb2_save_post_debug( $post_id, $post, $update ) {
	static $done = FALSE;
	if ( ! $done ) {
		print( '<h1>CB2_DEBUG_SAVE is on in CB_Query.php</h1>' );
		print( '<p>Debug info will be shown and redirect will be suppressed, but printed at the bottom</p>' );
		krumo( $_POST );
	}
	// Bug https://core.trac.wordpress.org/ticket/9968
	// will prevent the next action (cb2_save_post_move_to_native)
	// as it deletes from an array of actions currently being traversed
	// remove_action( 'save_post', 'cb2_save_post_debug', CB2_DS_PRIORITY );
	$done = TRUE;
}

function cb2_save_post_move_to_native( $post_id, $post, $update ) {
	// Triggers AFTER cb2_pre_post_update_action()
	// All metadata is already saved in to wp_postmeta
	// This happens in a loop during the post saving procedure:
	//	 post.php:wp_insert_post( array( ..., 'meta_input' => array(...) )
	//		 foreach ( $postarr['meta_input'] as $field => $value ) {
	//	 	 	 update_post_meta( $post_ID, $field, $value );
	//		 }
	global $wpdb, $extra_processing_properties;

	$post_type = $post->post_type;
	if ( $Class = CB_Query::schema_type_class( $post_type ) ) {
		if ( CB_Query::publishing_post( $post ) ) {
			if ( $class_database_table = CB_Database::database_table( $Class ) ) {
				// This post is currently a normal post in wp_posts
				// with one of our post_types, but a small ID
				// not an auto-draft request anymore so all its meta-data is saved and ready
				// probably created by the Add New post process
				// because we have not hooked in to the wp_insert_post(auto-draft) process
				if ( CB2_DEBUG_SAVE ) print( "<h2>cb2_save_post_move_to_native( $Class/$post_type )</h2>" );
				$cb2_post = CB_Query::ensure_correct_class( $post );

				// Move all extra metadata in to global object
				// for later actions to use
				$metadata = get_metadata( 'post', $post_id );
				foreach ( $metadata as $name => $value ) {
					if ( ! isset( CB2_POST_PROPERTIES[ $name ] ) )
						if ( substr( $name, 0, 1 ) != '_' ) $extra_processing_properties->$name = $value[0];
				}
				if ( CB2_DEBUG_SAVE ) krumo( $extra_processing_properties );

				// Create dependent objects before moving in to the native tables
				// will also reset the metadata for $post, e.g.
				//   $post->period_group_ID = CB2_CREATE_NEW => 800000034
				//   meta: period_group_ID:   CB2_CREATE_NEW => 800000034
				// pre_post_create() will not work on the correct ID
				// because, by definition, it has not been created
				// so do not call $this->id() as it will fail
				$extra_processing_properties->dependency_depth++;
				if ( method_exists( $cb2_post, 'pre_post_create' ) ) $cb2_post->pre_post_create();
				$extra_processing_properties->dependency_depth--;

				// Move this post into our structure
				// Allow for main Class tables with no actual needed columns beyond the id
				$result   = NULL;
				$insert_data = CB_Database::sanitize_data_for_table( $class_database_table, (array) $cb2_post );
				if ( CB2_DEBUG_SAVE ) krumo( $cb2_post, $insert_data );
				if ( count( $insert_data ) )
					$result = $wpdb->insert( "$wpdb->prefix$class_database_table", $insert_data );
				else
					$result = $wpdb->query( "INSERT into `$wpdb->prefix$class_database_table` values()" );
				if ( $result === FALSE ) {
					print( "<div id='error-page'><p>$wpdb->last_error</p></div>" );
					exit();
				}
				$native_id = $wpdb->insert_id;
				$native_ID = CB_Query::ID_from_id_post_type( $native_id, $post_type );

				// Pass the newly created native_ID to the postmeta in wp_postmeta
				// NOTE: this is set agaist the wp_posts ID, not the new one
				// and the remaining save_post calls will be passed this native_id
				$extra_processing_properties->native_ID = $native_ID;
			}
		}
	}
}

function cb2_save_post_post_actions( $post_id, $post, $update ) {
	// Triggers AFTER cb2_save_post_move_to_native()
	// $post_id can still be the wp_post ID
	global $extra_processing_properties;

	$post_type = $post->post_type;
	if ( $Class = CB_Query::schema_type_class( $post_type ) ) {
		if ( ! CB_Query::is_wp_auto_draft( $post ) ) {
			if ( $class_database_table = CB_Database::database_table( $Class ) ) {
				// We NEED the correct ID
				// so that update actions work on the correct database row
				if ( property_exists( $extra_processing_properties, 'native_ID' ) )
					$post->ID = $extra_processing_properties->native_ID;
				foreach ( $extra_processing_properties as $name => $value )
					$post->$name = $value;
				$cb_post = CB_Query::ensure_correct_class( $post );

				if ( method_exists( $cb_post, 'post_post_update' ) ) {
					if ( CB2_DEBUG_SAVE ) {
						print( "<h2>cb2_save_post_post_actions( $Class/$post_type/$post->ID/$post_id )</h2>" );
						krumo( $cb_post, $post, $extra_processing_properties );
					}
					$cb_post->post_post_update();
				}
			}
		}
	}
}

function cb2_save_post_redirect_to_native_post( $post_id, $post, $update ) {
	// Triggers AFTER cb2_save_post_move_to_native()
	// Page reload!
	global $wpdb, $extra_processing_properties;

	$post_type = $post->post_type;
	if ( $Class = CB_Query::schema_type_class( $post_type ) ) {
		if ( $class_database_table = CB_Database::database_table( $Class ) ) {
			// We NEED the correct ID
			// so that update actions work on the correct database row
			if ( ! $extra_processing_properties->dependency_depth ) {
				if ( property_exists( $extra_processing_properties, 'native_ID' ) ) {
					$post->ID = $extra_processing_properties->native_ID;
					// We need to reset the ID for further edit screens
					// to start using the native data post now as
					// native post data has been created already by
					// cb2_save_post_move_to_native()
					$page      = 'cb-post-edit';
					$action    = 'edit';
					$URL       = admin_url( "admin.php?page=$page&post=$post->ID&post_type=$post_type&action=$action" );

					if ( CB2_DEBUG_SAVE ) print( '<div>CB2_DEBUG_SAVE mode, no auto-redirect</div>' );
					else wp_redirect( $URL );
					print( "<div>redirecting to <a href='$URL'>$URL</a>...</div>" );
					exit();
				}
			}
		}
	}
}

function cb2_save_post_delete_auto_draft( $ID, $post, $update ) {
	global $wpdb, $extra_processing_properties;

	if ( $post && property_exists( $post, 'post_type' ) ) {
		$post_type = $post->post_type;
		if ( $Class = CB_Query::schema_type_class( $post_type ) ) {
			if ( $native_ID = get_post_meta( $ID, 'native_ID', TRUE ) ) {
				// Remove the auto-draft
				// TODO: this will cause the initial Add New -> further edits process to fail
				// because it passes through the wrong id
				// $wpdb->delete( "{$wpdb->prefix}posts", array( 'ID' => $ID ) );
			}
		}
	}
}

function cb2_get_post_metadata( $type, $post_id, $meta_key, $single ) {
	global $wpdb;

	$value = NULL; // Will cause the standard system to query metadata

	// Ignore pseudo metadata, e.g. _edit_lock
	if ( $meta_key && $meta_key[0] != '_' && $meta_key != 'native_ID' ) {
		if ( $post = get_post( $post_id ) ) {
			$post_type = $post->post_type;
			if ( $Class = CB_Query::schema_type_class( $post_type ) ) {
				if ( $postmeta_table = CB_Database::postmeta_table( $Class, $meta_type, $meta_table_stub, $post_id ) ) {
					if ( $native_ID = get_post_meta( $post_id, 'native_ID', TRUE ) )
						$post->ID = $native_ID;

					$query = $wpdb->prepare(
						"SELECT `meta_value` FROM `$wpdb->prefix$postmeta_table` WHERE `meta_key` = %s AND `post_id` = %d",
						array( $meta_key, $post->ID )
					);

					$value = $wpdb->get_var( $query );
					// The caller calculates the single logic
					//   if ( $single ) $value = $value[0];
					// However, it has a bug
					// so we make it choose an empty string if it cannot be found
					if ( $single && is_array( $value ) && count( $value ) == 0 )
						$value = array( '' );
				}
			}
		}
	}

	// Prevent normal by returning a value
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
	// These also happen in a loop during the post saving procedure:
	//	 post.php:wp_insert_post()
	//		 foreach ( $postarr['meta_input'] as $field => $value ) {
	//	 	 	 update_post_meta( $post_ID, $field, $value );
	//		 }
	global $wpdb;

	$prevent = FALSE;

	// Ignore pseudo metadata, e.g. _edit_lock
	if ( $meta_key && $meta_key[0] != '_' ) {
		if ( $post = get_post( $ID ) ) {
			$post_type = $post->post_type;
			if ( ! CB_Query::is_wp_auto_draft( $post ) && CB_Query::is_custom_post_type_ID( $ID ) ) {
				if ( $Class = CB_Query::schema_type_class( $post_type ) ) {
					if ( $class_database_table = CB_Database::database_table( $Class ) ) {
						if ( $id_field = CB_Database::id_field( $Class ) ) {
							$post = CB_Query::ensure_correct_class( $post );
							$data = array( $meta_key => $meta_value );

							if ( method_exists( $post, 'sanitize_data_for_table' ) )
								$data = $post->sanitize_data_for_table( $data );
							$data = CB_Database::sanitize_data_for_table( $class_database_table, $data );
							if ( WP_DEBUG && FALSE && ! count( $data ) )
								print( "<div class='cb2-debug cb2-high-debug' style='font-weight:bold;color:#600;'>($Class/$post_type) = column [$meta_key] update on [$class_database_table] IGNORED because not present</div>" );

							foreach ( $data as $meta_key => $meta_value ) {
								// Custom query
								$query   = NULL;
								$id      = CB_Query::id_from_ID_with_post_type( $ID, $post_type );
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
								if ( WP_DEBUG && FALSE ) print( "<div class='cb2-debug cb2-high-debug' style='font-weight:bold;color:#600;'>($Class/$post_type) = $query</div>" );
								$result = $wpdb->query( $query );
								if ( $result === FALSE ) {
									print( "<div id='error-page'><p>$wpdb->last_error</p></div>" );
									exit();
								}
							}
						} else throw new Exception( "Cannot update meta for [$Class] because no id field or database table" );
						// We DO NOT prevent normal
						// because it prevents other meta data from being updated
						$prevent = FALSE;
					}
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
	// TODO: re-enable CB_Enqueue
	wp_enqueue_style( CB_TEXTDOMAIN . '-plugin-styles-admin',  plugins_url( 'admin/assets/css/admin.css', CB_PLUGIN_ABSOLUTE ), array(), CB_VERSION );
	wp_enqueue_style( CB_TEXTDOMAIN . '-plugin-styles-public', plugins_url( 'public/assets/css/public.css', CB_PLUGIN_ABSOLUTE ), array(), CB_VERSION );
	wp_enqueue_script( CB_TEXTDOMAIN . '-plugin-script', plugins_url( 'admin/assets/js/admin.js', CB_PLUGIN_ABSOLUTE ), array( 'jquery' ), CB_VERSION );
}

function cb2_add_post_type_actions( $action, $priority = 10, $nargs = 1 ) {
	foreach ( CB_Query::schema_types() as $post_type => $Class ) {
		$action_post_type = "{$action}_{$post_type}";
		add_action( $action_post_type, "cb2_{$action}", $priority, $nargs );
	}
}

function cb2_init_register_post_types() {
	foreach ( CB_Query::schema_types() as $post_type => $Class ) {
		if ( method_exists( $Class, 'manage_columns' ) ) {
			// Functions handled in the WP_admin_integration
			add_filter( "manage_{$post_type}_columns",  'cb2_manage_columns' );
			add_action( 'manage_posts_custom_column' ,  'cb2_custom_columns' );
		}

		if ( ! property_exists( $Class, 'register_post_type' ) || $Class::$register_post_type ) {
			$supports = ( property_exists( $Class, 'supports' ) ? $Class::$supports : array(
				'title',
			) );

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
				'supports'           => $supports,
			);
			if ( property_exists( $Class, 'post_type_args' ) )
				$args = array_merge( $args, $Class::$post_type_args );
			if ( WP_DEBUG && FALSE ) {
				print( "<div class='cb2-debug'>register_post_type([$post_type])</div>" );
				krumo($args);
			}
			register_post_type( $post_type, $args );
		}
	}
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
	$args[ 'post_status' ] = array( 'publish', CB2_AUTODRAFT );
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

		if ( WP_DEBUG && FALSE ) {
			$query_truncated = ( strlen( $query ) > 300 ? substr( $query, 0, 300 ) . '...' : $query );
			print( "<div class='cb2-debug cb2-high-debug' style='font-weight:bold;'>($Class/$post_type_stub) = $query_truncated</div>" );
		}
	}
	else if ( WP_DEBUG && FALSE ) {
		$query_truncated = ( strlen( $query ) > 300 ? substr( $query, 0, 300 ) . '...' : $query );
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
function cb2_query_debug( $query ) {
	global $wp;
	if ( CB2_DEBUG_SAVE && strstr( $_SERVER['REQUEST_URI'], 'post.php' ) )
		print( "<div style='font-size:9px;color:#77a'>$query</div>" );
	return $query;
}

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
  if ( isset( $_GET[ 'show_overridden_periods' ] ) ) set_query_var( 'show_overridden_periods', $_GET[ 'show_overridden_periods' ] );

  $meta_query_items = array();
	if ( isset( $_GET[ 'location_ID' ] ) )             $meta_query_items[ 'location_clause' ]    = array( 'key' => 'location_ID', 'value' => $_GET[ 'location_ID' ] );
	if ( isset( $_GET[ 'item_ID' ] ) )                 $meta_query_items[ 'item_clause' ]        = array( 'key' => 'item_ID',     'value' => $_GET[ 'item_ID' ] );
	if ( isset( $_GET[ 'user_ID' ] ) )                 $meta_query_items[ 'user_clause' ]        = array( 'key' => 'user_ID',     'value' => $_GET[ 'user_ID' ] );
	if ( isset( $_GET[ 'period_status_type_ID' ] ) )   $meta_query_items[ 'period_status_type_clause' ] = array( 'key' => 'period_status_type_ID',   'value' => $_GET[ 'period_status_type_ID' ] );
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

function cb2_query_vars( $qvars ) {
	$qvars[] = 'show_overridden_periods';
	$qvars[] = 'location_ID';
	$qvars[] = 'item_ID';
	$qvars[] = 'period_status_type_ID';
	$qvars[] = 'period_status_type_name';

	return $qvars;
}



