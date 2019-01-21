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
 * which will cause the global $post iterator
 * with have_posts(), the_post() and global $post
 * to iterate through <Class name>s instead of WP_Posts
 * This can also be achieved manually with
 *      CB2_Query::get_post_with_type( $post_ID )
 *   or CB2_Query::ensure_correct_classes( $posts )
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
global $auto_draft_publish_transition;
$auto_draft_publish_transition = isset( $_GET['auto_draft_publish_transition'] );

// --------------------------------------------- Misc
add_filter( 'query',            'cb2_query_debug' );
add_filter( 'query',            'cb2_wpdb_mend_broken_date_selector' );
add_filter( 'posts_where',      'cb2_posts_where_allow_NULL_meta_query' );
add_filter( 'pre_get_posts',    'cb2_pre_get_posts_query_string_extensions');
add_filter( 'query_vars',       'cb2_query_vars' );

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
// Create $native_ID, and $ID
define( 'CB2_DS_PRIORITY',   100 );
add_action( 'save_post', 'cb2_save_post_debug', CB2_DS_PRIORITY, 3 );
define( 'CB2_MTN_PRIORITY',  110 );
add_action( 'save_post', 'cb2_save_post_move_to_native', CB2_MTN_PRIORITY, 3 );

// Prevent updates of wp_posts
add_filter( 'wp_insert_post_empty_content', 'cb2_wp_insert_post_empty_content', 1, 2 );
//add_filter( 'edit_post', 'cb2_edit_post', 1, 2 );
add_action( 'cmb2_save_post_fields',        'cb2_cmb2_save_post_fields_debug', 10, 4 );

// Direct metadata updates
// CMB2 MetaBoxes DO NOT use meta-data!
// instead, they hook in to the save_post and write the meta-data manually
// so there will be no meta-data available at pre_post_update stage
// they also DO NOT use AJAX writing of values to meta-data
add_action( 'add_post_meta',        'cb2_add_post_meta',           10, 3 );
add_action( 'update_post_meta',     'cb2_update_post_meta',        10, 4 );

// --------------------------------------------- Deleting posts
add_filter( 'pre_trash_post',       'cb2_pre_trash_post',   10, 2 );
add_filter( 'pre_untrash_post',     'cb2_pre_untrash_post', 10, 2 );
add_filter( 'pre_delete_post',      'cb2_pre_delete_post',  10, 3 );

// --------------------------------------------- WP_Query Database redirect to views for custom posts
// $wpdb->posts => wp_cb2_view_posts
add_filter( 'pre_get_posts', 'cb2_pre_get_posts_redirect_wpdb' );
add_filter( 'pre_get_posts', 'cb2_pre_get_posts_prevent_update_post_meta_cache' );
add_filter( 'posts_results', 'cb2_post_results_unredirect_wpdb', 10, 2 );
add_filter( 'posts_results', 'cb2_posts_results_add_automatic',  10, 2 );

// --------------------------------------------- WP Loop control
// Here we change the Wp_Query posts to the correct list
add_filter( 'loop_start',    'cb2_loop_start' );

// --------------------------------------------- Custom post types and templates
add_action( 'init', 'cb2_init_register_post_types' );
add_action( 'admin_enqueue_scripts', 'cb2_admin_enqueue_scripts' );

function cb2_wpdb_query_select_debug( $sql ) {
	print( "<div>$sql</div>" );
	return $sql;
}
// if ( WP_DEBUG ) add_filter( 'query', 'cb2_wpdb_query_select_debug' );

// ------------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------
function cb2_init_do_action() {
	// Used by front and backend for
	//   bookings
	//   block / unblock
	// etc.
	$_INPUT         = array_merge( $_GET, $_POST );
	$action_post_ID = NULL;
	if ( isset( $_INPUT['do_action'] ) ) {
		$do_action = explode( '::', $_INPUT['do_action'] );
		if ( count( $do_action ) == 2 ) {
			$Class  = $do_action[0];
			$action = $do_action[1];
			$method = "do_action_$action";
			$args   = $_INPUT;
			array_shift( $args ); // Remove the page

			$user = CB2_User::factory_current();
			if ( ! $user )
				throw new Exception( "user required during [$action]" );

			foreach ( $args as $name => $value ) {
				$interpret_func = 'post_' . preg_replace( '/[^a-zA-Z0-9_]/', '_', $name ) . '_interpret';
				$args[$name]    = ( method_exists( 'CB2_PostNavigator', $interpret_func ) ? CB2_PostNavigator::$interpret_func( $value ) : $value );
			}

			if ( isset( $_INPUT['do_action_post_ID'] ) ) {
				// ------------------------------------------ method handler
				$post_type   = $Class::$static_post_type;
				$post_ID     = (int) $_INPUT['do_action_post_ID'];
				$action_post = CB2_Query::get_post_with_type( $post_type, $post_ID );
				if ( $action_post )  {
					if ( method_exists( $action_post, $method ) ) {
						if ( WP_DEBUG ) {
							print( "<div class='cb2-WP_DEBUG'>Member {$Class}->do_action_[$action]($post_ID)</div>" );
							krumo( $args );
						}
						$action_post->$method( $user, $args );
					} else throw new Exception( "$method does not exist on $Class" );
				} else throw new Exception( "Cannot find $Class($post_ID)" );
			} else {
				// ------------------------------------------ Static handler
				if ( method_exists( $Class, $method ) ) {
					if ( WP_DEBUG ) {
						print( "<div class='cb2-WP_DEBUG'>Static $Class::do_action_[$action]()</div>" );
						krumo( $args );
					}
					$Class::$method( $user, $args );
				} else throw new Exception( "Static $method does not exist on $Class" );
			}
		} else throw new Exception( "Invalid do_action request. Format is <Class>::<method stub after do_action_>" );
	}

	if ( isset( $_INPUT['redirect'] ) )
		wp_redirect( $_INPUT['redirect'] );

	return TRUE;
}
add_action( 'init', 'cb2_init_do_action' );


// ------------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------
// Trash/Delete integration
function cb2_pre_untrash_post( $untrash, $post ) {
	// $untrash is null on input
	$post_type = $post->post_type;
	if ( $Class = CB2_PostNavigator::post_type_Class( $post_type ) ) {
		$cb2_post = CB2_Query::ensure_correct_class( $post );
		if ( method_exists( $cb2_post, 'untrash' ) ) {
			if ( ! $cb2_post->untrash() )
				throw new Exception( "Failed to untrash $Class" );
			$untrash = $post; // Halt normal trash process
		} // else allow null $untrash to continue with normal trash process
	}

	// Returing null will cause the normal trash process to continue
	// $untrash is null on input
	return $untrash;
}

function cb2_pre_trash_post( $trash, $post ) {
	// $trash is null on input
	$post_type = $post->post_type;
	if ( $Class = CB2_PostNavigator::post_type_Class( $post_type ) ) {
		$cb2_post = CB2_Query::ensure_correct_class( $post );
		if ( method_exists( $cb2_post, 'trash' ) ) {
			if ( ! $cb2_post->trash() )
				throw new Exception( "Failed to trash $Class" );
			$trash = $post; // Halt normal trash process
		} // else allow null $trash to continue with normal trash process
	}

	// Returing null will cause the normal trash process to continue
	// $trash is null on input
	return $trash;
}

function cb2_pre_delete_post( $delete, $post, $force_delete ) {
	// $delete is null on input
	$post_type = $post->post_type;
	if ( $Class = CB2_PostNavigator::post_type_Class( $post_type ) ) {
		$cb2_post = CB2_Query::ensure_correct_class( $post );
		if ( method_exists( $cb2_post, 'delete' ) ) {
			$cb2_post->delete();
			$delete = $post;
		} // else allow null $delete to continue with normal deletion
	}

	// Returing null will cause the normal delete process to continue
	// $delete is null on input
	return $delete;
}

// ------------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------
// Update integration
function cb2_wp_insert_post_empty_content( $maybe_empty, $postarr ) {
	global $wpdb, $auto_draft_publish_transition;
	static $updated = array();

	$consider_empty_post = FALSE;
	$post_id     = ( isset( $postarr['ID'] ) ? $postarr['ID'] : NULL );
	$post_type   = ( isset( $postarr['post_type'] ) ? $postarr['post_type'] : NULL );
	$update      = ( ! empty( $post_id ) );
	$post        = (object) $postarr;

	if ( $update ) {
		if ( ! $auto_draft_publish_transition ) {
			if ( $Class = CB2_PostNavigator::post_type_Class( $post_type ) ) {
				if ( $class_database_table = CB2_Database::database_table( $Class ) ) {
					// wp_update_post() will return 0 because we return consider_empty_post = TRUE
					// causing post.php:377 wp_update_post() to try an update again
					$consider_empty_post = TRUE; // Do not continue the update procedure!
					if ( isset( $updated[$post_id] ) ) {
						if ( CB2_DEBUG_SAVE )
							print( "<div class='cb2-WP_DEBUG-small'>re-attempt by post.php:388 to wp_update_post($post_id) rejected. Happens because we intercept, return 0 and it re-trys.</div>" );
					} else {
						$updated[$post_id] = TRUE;

						// CMB2 saves its fields on save_post hook normally to
						//   wp_postmeta
						// We are early intercepting and preventing
						// which unfortunately means that we deny the rest of the valuable update procedure
						// We cannot redirect to a view because we cannot write to the view
						// We cannot manually save the meta-data in advance
						// because, in this multiple-object update, we do not know which Class/table it needs to go to
						// Instead, we need the data, and to pump in to
						//   factory_from_properties() recursive
						if ( CB2_DEBUG_SAVE ) krumo($_POST);
						$properties = $_POST;

						// Prevent post.php wp_insert_post() from continuing
						// with its update of wp_posts
						if ( CB2_DEBUG_SAVE ) {
							print( "<h1>CB2_DEBUG_SAVE:</h1><div>cb2_wp_insert_post_empty_content() preventing update to {$wpdb->prefix}posts and remaining save process</div>" );
							krumo($postarr);
						}

						// ----------------------------------------------------- save()
						$update                = TRUE;
						$container             = NULL; // Not used yet
						$fire_wordpress_events = $consider_empty_post; // We are preventing normal update procedure events
						$cb2_post              = $Class::factory_from_properties( $properties, $container, $update ); // Recursive
						$cb2_post->save( $update, $fire_wordpress_events );
					}
				}
			}
		}
	}

	return $consider_empty_post;
}

function cb2_cmb2_save_post_fields_debug( $object_id, $cmb_id, $updated, $cmb2 ) {
	if ( CB2_DEBUG_SAVE ) {
		print( "<div class='cb2-WP_DEBUG-small'>CMB2::save_post_fields($object_id)</div>" );
	}
}

function cb2_save_post_debug( $post_id, $post, $update ) {
	global $auto_draft_publish_transition;
	static $done = FALSE;

	if ( CB2_DEBUG_SAVE ) {
		if ( ! $done ) {
			print( '<h1>CB2_DEBUG_SAVE is on in CB2_Query.php</h1>' );
			if ( isset( $_GET['XDEBUG_PROFILE'] ) ) {
				$xdebug_profiler_output = xdebug_get_profiler_filename();
				print( "<div class='cb2-WP_DEBUG'>XDEBUG_PROFILE=1 dump will appear in <b><a target='_blank' href='file://$xdebug_profiler_output'>$xdebug_profiler_output</a></b></div>" );
			}

			print( '<p>Debug info will be shown and redirect will be suppressed, but printed at the bottom</p>' );
			print( '<div class="cb2-WP_DEBUG-small">auto_draft_publish_transition ' . ( $auto_draft_publish_transition ? '<b class="cb2-warning">TRUE</b>' : 'FALSE' ) . '</div>' );
			krumo( $_POST );
		}
		// Bug https://core.trac.wordpress.org/ticket/9968
		// will prevent the next action (cb2_save_post_move_to_native)
		// as it deletes from an array of actions currently being traversed
		// remove_action( 'save_post', 'cb2_save_post_debug', CB2_DS_PRIORITY );
		$done = TRUE;
	}
}

function cb2_save_post_move_to_native( $post_id, $post, $update ) {
	// Triggers AFTER cb2_pre_post_update_action()
	// All metadata is already saved in to wp_postmeta
	// This happens in a loop during the post saving procedure:
	//	 post.php:wp_insert_post( array( ..., 'meta_input' => array(...) )
	//		 foreach ( $postarr['meta_input'] as $field => $value ) {
	//	 	 	 update_post_meta( $post_ID, $field, $value );
	//		 }
	global $auto_draft_publish_transition;
	$native_ID = NULL;

	// Updates are handled directly
	if ( $auto_draft_publish_transition ) {
		$post_type = $post->post_type;
		if ( $Class = CB2_PostNavigator::post_type_Class( $post_type ) ) {
			if ( $class_database_table = CB2_Database::database_table( $Class ) ) {
				if ( ! method_exists( $Class, 'factory_from_properties' ) )
					throw new Exception( "$Class::factory_from_properties not present" );

				// This post is currently a normal post in wp_posts
				// with one of our post_types, but a small ID
				// not an auto-draft request anymore so all its meta-data is saved and ready
				// probably created by the Add New post process
				// because we have not hooked in to the wp_insert_post(auto-draft) process
				if ( CB2_DEBUG_SAVE )
					print( "<h2>cb2_save_post_move_to_native( $class_database_table/$post_type )</h2>" );

				// ----------------------------------------------------- Include meta-data
				// Move all extra metadata in to $properties for later actions to use
				// Because we are defaulting to SINGLE
				// meta_value multiple value arrays are returned serialised
				// currently we do not store any arrays:
				//   ID lists are stored as comma delimited for example
				//   bit arrays are handled as unsigned
				if ( CB2_DEBUG_SAVE )
					print( "<div class='cb2-WP_DEBUG-small'>include wp_post meta-data</div>" );
				CB2_Query::get_metadata_assign( $post );
				krumo($post);
				$properties = (array) $post;

				// ----------------------------------------------------- save() => pre_post_create() recursive
				// Important: $post->ID is the ID from wp_posts
				// 0 ID this causes save() => create()
				// we do not want it to try and update() the wp_posts ID
				$properties['ID']      = CB2_CREATE_NEW;
				$update                = FALSE;
				$fire_wordpress_events = FALSE; // This is a late firing save_post action, save_post_{post_type} has already fired
				$cb2_post              = $Class::factory_from_properties( $properties ); // recursive create
				$native_ID             = $cb2_post->save( $update, $fire_wordpress_events );
				if ( ! $native_ID )
					throw new Exception( 'native_ID blank during immediate redirection' );

				// ----------------------------------------------------- Tidy Up auto-draft
				// We delete the auto-draft at the last moment
				// before redirect
				// TODO: wp_delete_post( $post_id, TRUE );

				// ----------------------------------------------------- Redirect
				// We need to reset the ID for further edit screens
				// to start using the native data post now
				if ( CB2_DEBUG_SAVE ) print( "<div class='cb2-WP_DEBUG-small'>redirect_to_native_post [$native_ID]</div>" );
				$page      = 'cb2-post-edit';
				$action    = 'edit';
				$URL       = admin_url( "admin.php?page=$page&post=$native_ID&post_type=$post_type&action=$action" );
				// If CB2_DEBUG_SAVE the redirect will be printed, not acted
				// NOTE: this exit() will prevent other save_post actions firing on post create...
				wp_redirect( $URL );
				exit();
			}
		}

		// Further requests can come from the native tables now
		$auto_draft_publish_transition = FALSE;
	}

	return $native_ID;
}

function cb2_post_class_check( $classes, $class, $ID ) {
	if ( WP_DEBUG ) {
		if ( ! is_admin() ) { // is_admin_[page]()
			$post_type = NULL;
			foreach ( $classes as $class ) {
				if ( substr( $class, 0, 5 ) == 'type-' ) {
					$post_type = substr( $class, 5 );
					break;
				}
			}

			if ( $post_type ) {
				if ( $Class = CB2_PostNavigator::post_type_Class( $post_type ) ) {
					if ( CB2_Database::postmeta_table( $Class ) )
						CB2_Query::debug_print_backtrace( "Please do not use post_class() in CB2 templates with [$post_type] because it cannot be cached. Use CB2::post_class() instead." );
				}
			}
		}
	}

	return $classes;
}
add_filter( 'post_class', 'cb2_post_class_check', 10, 3 );

function cb2_get_cb2_metadata( $type, $post_id, $meta_key, $single ) {
	global $wpdb;
	if ( WP_DEBUG && ! CB2_Query::wpdb_postmeta_is_redirected() )
		CB2_Query::debug_print_backtrace( "Request for CB2 [$type] meta data [$meta_key] without redirected wpdb." );
	return NULL; // Continue with normal operation
}
add_filter( 'get_perioditem_metadata',       'cb2_get_cb2_metadata', 10, 4 );
add_filter( 'get_periodent_metadata',        'cb2_get_cb2_metadata', 10, 4 );
add_filter( 'get_period_metadata',           'cb2_get_cb2_metadata', 10, 4 );
add_filter( 'get_periodgroup_metadata',      'cb2_get_cb2_metadata', 10, 4 );
add_filter( 'get_periodstatustype_metadata', 'cb2_get_cb2_metadata', 10, 4 );

function cb2_delete_cb2_metadata( $delete, $object_id, $meta_key, $meta_value, $delete_all ) {
	$prevent = NULL;

	if ( $post = get_post( $object_id ) ) {
		if ( $Class = CB2_PostNavigator::post_type_Class( $post->post_type ) ) {
			$prevent = TRUE; // Cancel normal operation
			if ( WP_DEBUG )
				print( "<div class='cb2-WP_DEBUG-small'>Attempt to delete meta [$meta_key] on post [$Class/$object_id] suppressed.</div>" );
		}
	}

	return $prevent;
}
add_filter( 'delete_post_metadata',       'cb2_delete_cb2_metadata', 10, 5 );

function cb2_update_post_metadata( $allow, $object_id, $meta_key, $meta_value, $prev_value ) {
	// 'post' meta_type update request, e.g. _edit_lock
	// if the DB is redirected, it will FAIL to write to the view
	// returning non-NULL value will prevent the update
	// Return value:
	//   TRUE = prevent update
	//   NULL = allow update
	global $wpdb;

	$is_system_meta = ( $meta_key && $meta_key[0] == '_' );
	if ( WP_DEBUG && ! $is_system_meta && CB2_Query::wpdb_postmeta_is_redirected() )
		print( "<div class='cb2-WP_DEBUG-small'>Attempt to update non system meta [$meta_key] on post [$object_id] with redirected wpdb [$wpdb->postmeta] suppressed. Update will be caught by another action.</div>" );
	$prevent_update = ( CB2_Query::wpdb_postmeta_is_redirected() ? TRUE : NULL );
	return $prevent_update;
}
add_filter( 'update_post_metadata',  'cb2_update_post_metadata', 10, 5 );

function cb2_the_posts_cache_meta( $posts, $wp_query ) {
	// Primary WP_Query post meta cache has been turned off
	//   cb2_pre_get_posts_prevent_update_post_meta_cache()
	// because it caches under 'post' only, not post_type
	if ( count( $posts ) ) {
		// We traverse the whole array
		// because there will be a mixture of Item, Location, Automatic and PeriodItems
		$object_ids = array();
		$meta_type  = NULL;
		foreach ( $posts as $post ) {
			$Class = CB2_PostNavigator::post_type_Class( $post->post_type );
			if ( $Class && CB2_Database::postmeta_table( $Class, $this_meta_type, $meta_table_stub ) ) {
				array_push( $object_ids, $post->ID );
				$meta_type = $this_meta_type;
			}
		}

		if ( $meta_type ) {
			if ( CB2_DEBUG_SAVE ) {
				// krumo( $posts );
				$object_count     = count( $object_ids );
				print( "<div class='cb2-WP_DEBUG-small'>cb2_the_posts_cache_meta([$object_count] $meta_type)</div>" );
			}
			if ( ! update_meta_cache( $meta_type, $object_ids ) ) {
				global $wpdb;
				krumo($wpdb);
				throw new Exception( 'Failed to update_meta_cache()' );
			}
		}
	}

	return $posts;
}
add_filter( 'the_posts', 'cb2_the_posts_cache_meta', 10, 2 );

function cb2_add_post_meta( $ID, $meta_key, $meta_value ) {
	// We are never adding a record in this scenario
	// Because our data is not normalised
	return cb2_update_post_meta( NULL, $ID, $meta_key, $meta_value );
}

function cb2_update_post_meta( $meta_id, $ID, $meta_key, $meta_value ) {
	// Calls cb2_get_post_metadata() first to check for existence
	// Only calls here if it does not already exist
	// These also happen in a loop during the post saving procedure:
	//	 post.php:wp_insert_post()
	//		 foreach ( $postarr['meta_input'] as $field => $value ) {
	//	 	 	 update_post_meta( $post_ID, $field, $value );
	//		 }
	global $auto_draft_publish_transition, $wpdb;
	static $first = TRUE;

	$prevent = FALSE;

	if ( $post = get_post( $ID ) ) {
		$post_type = $post->post_type;
		if ( $Class = CB2_PostNavigator::post_type_Class( $post_type ) ) {
			if ( $class_database_table = CB2_Database::database_table( $Class ) ) {
				if ( CB2_DEBUG_SAVE ) {
					$table = ( $auto_draft_publish_transition ? "<b class='cb2-warning'>{$wpdb->prefix}posts</b>" : 'native tables' );
					if ( $first ) print( "<h2 class='cb2-WP_DEBUG'>update_meta_data($ID) => $table</h2>" );
					if ( is_string( $meta_value ) ) print( "<b>$meta_key</b>=$meta_value, </li>" );
					$first = FALSE;
				}
				if ( ! $auto_draft_publish_transition ) {
					if ( $id_field = CB2_Database::id_field( $Class ) ) {
						$cb2_post = CB2_Query::ensure_correct_class( $post );
						if ( empty( $meta_value ) ) $meta_value = NULL;
						$data = array( $meta_key => $meta_value );
						if ( method_exists( $cb2_post, 'sanitize_data_for_table' ) )
							$data = $cb2_post->sanitize_data_for_table( $data, $formats );

						$data = CB2_Database::sanitize_data_for_table( $Class, $data, $formats, TRUE );

						if ( CB2_DEBUG_SAVE ) {
							if ( ! is_string( $meta_value ) && ! is_numeric( $meta_value ) )
								krumo( $meta_value, $data );
							print( "<div class='cb2-WP_DEBUG cb2-high-debug' style='font-weight:bold;color:#600;'>cb2_update_post_meta($Class/$post_type): [$meta_key] =&gt; [$meta_value]</div>" );
							// if ( $meta_key == 'recurrence_sequence' ) exit();
						}

						// Update
						// This field may be for another object being saved
						// so do not worry if it is not present in this table
						if ( count( $data ) ) {
							$id      = $cb2_post->id( 'update_post_meta' );
							$where   = array( $id_field => $id );
							$query = $wpdb->update(
								"$wpdb->prefix$class_database_table",
								$data,
								$where,
								$formats
							);
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

	// Returning TRUE will prevent any updates
	return $prevent;
}

// ------------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------
// Framework integration
function cb2_admin_enqueue_scripts() {
	// TODO: re-enable CB2_Admin_Enqueue for public/assets/css/public.css
	wp_enqueue_style(  CB2_TEXTDOMAIN . '-plugin-styles-public', plugins_url( 'public/assets/css/public.css', CB2_PLUGIN_ABSOLUTE ), array(), CB2_VERSION );
	wp_enqueue_style(  CB2_TEXTDOMAIN . '-plugin-styles-cmb2',   plugins_url( 'admin/includes/lib/cmb2/css/cmb2.min.css', CB2_PLUGIN_ABSOLUTE ), array(), CB2_VERSION );

	add_thickbox();
}

function cb2_add_post_type_actions( $action, $priority = 10, $nargs = 1 ) {
	foreach ( CB2_PostNavigator::post_type_classes() as $post_type => $Class ) {
		$action_post_type = "{$action}_{$post_type}";
		add_action( $action_post_type, "cb2_{$action}", $priority, $nargs );
	}
}

function cb2_init_register_post_types() {
	foreach ( CB2_PostNavigator::post_type_classes() as $post_type => $Class ) {
		if ( method_exists( $Class, 'manage_columns' ) ) {
			// Functions handled in the WP_admin_integration
			add_filter( "manage_{$post_type}_columns",  'cb2_manage_columns' );
			add_action( 'manage_posts_custom_column' ,  'cb2_custom_columns' );
		}

		if ( ! property_exists( $Class, 'register_post_type' ) || $Class::$register_post_type ) {
			$supports = ( property_exists( $Class, 'supports' ) ? $Class::$supports : array(
				'title',
			) );
			$rewrite = ( property_exists( $Class, 'rewrite' ) ? $Class::$rewrite : array ( 'slug' => $Class::$static_post_type));

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
				'rewrite'						 => $rewrite,
				// This is not advised in the WordPress codex
				// TODO: change _edit_link redirect_post() in our post.php instead?
				'_edit_link' => "admin.php?page=cb2-post-edit&post_type=$post_type&post=%d",
			);
			if ( property_exists( $Class, 'post_type_args' ) )
				$args = array_merge( $args, $Class::$post_type_args );
			if ( WP_DEBUG && FALSE ) {
				print( "<div class='cb2-WP_DEBUG'>register_post_type([$post_type])</div>" );
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
function cb2_pre_get_posts_prevent_update_post_meta_cache( &$wp_query ) {
	// Prevent standard attempt to cache with meta_type = post
	// our custom types should be cached under the base post_type meta_type:
	//   perioditem_meta[ID]
	//   NOT post_meta[ID]
	// update_postmeta_cache() has hardcoded 'post' meta_type
	// See
	//   cb2_the_posts_cache_meta()
	// for the manual update of the cache
	$wp_query->query_vars['update_post_meta_cache'] = FALSE;
}

function cb2_pre_get_posts_redirect_wpdb( &$wp_query ) {
	// If the wp_query is for a managed post_type
	// then redirect the wpdb prefix
	if ( isset( $wp_query->query['post_type'] ) ) {
		$post_type = $wp_query->query['post_type'];
		if ( is_array( $post_type ) )
			$post_type = ( count( $post_type ) ? array_values( $post_type )[0] : NULL );
		if ( CB2_Query::redirect_wpdb_for_post_type( $post_type ) ) {
			// Subsequent posts will be annotated
			// that they are from the native tables, not wp_post
			$wp_query->cb2_redirected_post_request = TRUE;

			// WP_Post::get_instance() will check the cache
			// after WP_Query ID retrieval
			// CB2 and WP post IDs might be cached and conflict
			// depending on CB2_ID_SHARING
			if ( CB2_DEBUG_SAVE ) print( "<div class='cb2-WP_DEBUG-small'>wp_cache_flush()</div>" );
			wp_cache_flush();
		}
	}
}

function cb2_post_results_unredirect_wpdb( $posts, $wp_query ) {
	CB2_Query::unredirect_wpdb();
	return $posts;
}

function cb2_posts_results_add_automatic( $posts, $wp_query ) {
	if ( isset( $wp_query->query['date_query'] )
		&& isset( $wp_query->query['date_query']['after'] )
		&& isset( $wp_query->query['date_query']['before'] )
	) {
		$startdate_string = $wp_query->query['date_query']['after'];
		$enddate_string   = $wp_query->query['date_query']['before'];
		if ( $startdate_string && $enddate_string ) {
			$startdate = new CB2_DateTime( $startdate_string );
			$enddate   = new CB2_DateTime( $enddate_string );
			$startdate->setTime( 0, 0 );
			$enddate->setTime( 23, 59 );

			while ( $startdate->before( $enddate ) ) {
				CB2_Day::factory( $startdate );
				$startdate->add( 1 );
			}

			usort( $posts, "cb2_posts_date_order" );

			// Reset pointers
			$wp_query->post_count  = count( $wp_query->posts );
			$wp_query->found_posts = (boolean) $wp_query->post_count;
			$wp_query->post = ( $wp_query->found_posts ? $wp_query->posts[0] : NULL );
		}
	}

	return $posts;
}

function cb2_posts_date_order( $post1, $post2 ) {
	// Alphabetical order == date order
  return strcmp( $post1->post_date, $post2->post_date );
}

function cb2_loop_start( &$wp_query ) {
	CB2_Query::reorganise_posts_structure( $wp_query );
}


// ------------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------
// Framework changes and fixes
function cb2_query_debug( $query ) {
	global $wp;
	if ( CB2_DEBUG_SAVE && FALSE )
		print( "<div class='cb2-WP_DEBUG-small'>$query</div>" );

	//if ( trim( $query ) == 'SELECT * FROM wp_posts WHERE ID = 1100000002 LIMIT 1' )
	//	throw new Exception( 'query ran' );

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
	if ( isset( $_GET[ 'period_entity_ID' ] ) )        $meta_query_items[ 'period_entity_clause' ]      = array( 'key' => 'period_entity_ID',        'value' => $_GET[ 'period_entity_ID' ] );
	if ( isset( $_GET[ 'period_status_type_name' ] ) ) $meta_query_items[ 'period_status_type_clause' ] = array( 'key' => 'period_status_type_name', 'value' => $_GET[ 'period_status_type_name' ] );

	if ( $meta_query_items ) {
		if ( ! isset( $meta_query_items[ 'relation' ] ) ) $meta_query_items[ 'relation' ] = 'AND';
		$meta_query[ 'items' ]          = $meta_query_items;
		set_query_var( 'meta_query', $meta_query );
	}

}

function cb2_query_vars( $qvars ) {
	$qvars[] = 'show_overridden_periods';
	$qvars[] = 'location_ID';
	$qvars[] = 'item_ID';
	$qvars[] = 'period_group_ID';
	$qvars[] = 'period_status_type_ID';
	$qvars[] = 'period_status_type_name';

	return $qvars;
}



