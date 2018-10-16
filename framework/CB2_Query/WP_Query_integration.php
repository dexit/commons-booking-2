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
global $post_save_processing;
$post_save_processing = new stdClass();
$post_save_processing->auto_draft_publish_transition = isset( $_GET['auto_draft_publish_transition'] );
$post_save_processing->redirect_to_native_ID         = FALSE;

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
//add_filter( 'query',             'cb2_wpdb_query_select' );
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
add_action( 'save_post', 'cb2_save_post_debug',                   CB2_DS_PRIORITY, 3 ); // Just print out debug info
add_action( 'save_post', 'cb2_save_post_move_to_native',          110, 3 ); // Create $native_ID, and $ID
add_action( 'save_post', 'cb2_save_post_redirect_to_native_post', CB2_MTN_PRIORITY, 3 ); // new $ID
// Delete auto-draft is done at point of redirect now
//add_action( 'save_post', 'cb2_save_post_delete_auto_draft',       130, 3 ); // original wp_posts $post and $ID

// Prevent updates of wp_posts
add_filter( 'wp_insert_post_empty_content', 'cb2_wp_insert_post_empty_content', 1, 2 );
//add_filter( 'edit_post', 'cb2_edit_post', 1, 2 );

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
add_action( 'trashed_post',         'cb2_delete_post' );

// --------------------------------------------- WP_Query Database redirect to views for custom posts
// $wpdb->posts => wp_cb2_view_posts
add_filter( 'pre_get_posts', 'cb2_pre_get_posts_redirect_wpdb' );
add_filter( 'posts_results', 'cb2_post_results_unredirect_wpdb', 10, 2 );
add_filter( 'posts_results', 'cb2_posts_results_add_automatic',  10, 2 );

// --------------------------------------------- WP Loop control
// Here we change the Wp_Query posts to the correct list
add_filter( 'loop_start',       'cb2_loop_start' );

// --------------------------------------------- Custom post types and templates
add_action( 'init', 'cb2_init_register_post_types' );
add_action( 'wp_enqueue_scripts',    'cb2_wp_enqueue_scripts' );
add_action( 'admin_enqueue_scripts', 'cb2_admin_enqueue_scripts' );

// ------------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------
// Update/Delete integration
function cb2_wp_insert_post_empty_content( $maybe_empty, $postarr ) {
	global $post_save_processing;

	$consider_empty_post = FALSE;
	$post_id     = ( isset( $postarr['ID'] ) ? $postarr['ID'] : NULL );
	$post_type   = ( isset( $postarr['post_type'] ) ? $postarr['post_type'] : NULL );
	$update      = ( ! empty( $post_id ) );
	$post        = (object) $postarr;

	if ( $update ) {
		if ( ! $post_save_processing->auto_draft_publish_transition ) {
			if ( $Class = CB_Query::schema_type_class( $post_type ) ) {
				if ( $class_database_table = CB_Database::database_table( $Class ) ) {
					// Prevent Class::factory_from_wp_post()
					//  get_metadata_assign() calling for the old meta-data
					// BECAUSE this is not a wp_post
					// TODO: a more elegant way to indicate that the meta-data is already assigned?
					$post->_get_metadata_assign = TRUE;

					/* TODO: post_save_processing for pre_post_update()
					if ( CB2_DEBUG_SAVE ) krumo( $postarr );
					foreach ( $postarr as $name => $value ) {
						if ( ! isset( CB_Post::$POST_PROPERTIES[ $name ] ) ) {
							$is_system_meta = ( substr( $name, 0, 1 ) == '_' );
							if ( ! $is_system_meta ) {
								$post_save_processing->$name = CB_Query::to_object( $name, $value );
							}
						}
					}
					*/
					if ( CB2_DEBUG_SAVE ) krumo( $post_save_processing );

					$cb2_post = CB_Query::ensure_correct_class( $post );
					$cb2_post->save();

					// Prevent post.php wp_insert_post() from continuing
					// with its update of wp_posts
					if ( CB2_DEBUG_SAVE ) {
						print( "<h1>CB2_DEBUG_SAVE:</h1><div>cb2_wp_insert_post_empty_content() preventing update to wp_posts and remaining save process</div>" );
						krumo($postarr);
					}
					$consider_empty_post = TRUE;
				}
			}
		}
	}

	return $consider_empty_post;
}

function cb2_save_post_debug( $post_id, $post, $update ) {
	global $post_save_processing;
	static $done = FALSE;

	if ( CB2_DEBUG_SAVE ) {
		if ( ! $done ) {
			print( '<h1>CB2_DEBUG_SAVE is on in CB_Query.php</h1>' );
			print( '<p>Debug info will be shown and redirect will be suppressed, but printed at the bottom</p>' );
			print( '<div class="cb2-WP_DEBUG-small">post_save_processing->auto_draft_publish_transition ' . ( $post_save_processing->auto_draft_publish_transition ? '<b class="cb2-warning">TRUE</b>' : 'FALSE' ) . '</div>' );
			krumo( $_POST, $post );
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
	global $post_save_processing;
	$native_ID = NULL;

	if ( $post_save_processing->auto_draft_publish_transition ) {
		$post_type = $post->post_type;
		if ( $Class = CB_Query::schema_type_class( $post_type ) ) {
			if ( $class_database_table = CB_Database::database_table( $Class ) ) {
				// This post is currently a normal post in wp_posts
				// with one of our post_types, but a small ID
				// not an auto-draft request anymore so all its meta-data is saved and ready
				// probably created by the Add New post process
				// because we have not hooked in to the wp_insert_post(auto-draft) process
				if ( CB2_DEBUG_SAVE ) print( "<h2>cb2_save_post_move_to_native( $class_database_table/$post_type )</h2>" );

				// Move all extra metadata in to global $post_save_processing
				// for later actions to use
				$metadata = get_metadata( 'post', $post->ID );
				foreach ( $metadata as $name => $value_array ) {
					if ( ! isset( CB_Post::$POST_PROPERTIES[ $name ] ) ) {
						$is_system_meta = ( substr( $name, 0, 1 ) == '_' );

						// Because we are defaulting to SINGLE
						// meta_value multiple value arrays are returned serialised
						// currently we do not store any arrays:
						//   ID lists are stored as comma delimited for example
						//   bit arrays are handled as unsigned
						if ( ! $is_system_meta ) {
							$value = $value_array[0];
							$post_save_processing->$name = CB_Query::to_object( $name, $value );
						}
					}
				}
				if ( CB2_DEBUG_SAVE ) krumo( $metadata, $post_save_processing );

				// Important: $post->ID is the ID from wp_posts
				// NULL ID this causes save() => create()
				// we do not want it to try and update() the wp_posts ID
				// do not set the $post->ID = NULL
				// because it will not be able to get_metadata_assign()
				$cb2_post     = CB_Query::ensure_correct_class( $post );
				$cb2_post->ID = NULL;
				$native_ID    = $cb2_post->save();

				// Pass the newly created native_ID to the postmeta in wp_postmeta
				// NOTE: this is set agaist the wp_posts ID, not the new one
				// and the remaining save_post calls will be passed this native_id
				if ( ! $native_ID ) throw new Exception( 'native_ID blank during immediate redirection' );
				$post_save_processing->redirect_to_native_ID = $native_ID;
			}
		}
	}

	return $native_ID;
}

function cb2_save_post_redirect_to_native_post( $post_id, $post, $update ) {
	// Triggers AFTER cb2_save_post_move_to_native()
	// Page reload!
	global $post_save_processing;

	if ( $post_save_processing->redirect_to_native_ID ) {
		// We delete the auto-draft at the last moment
		// before redirect
		// TODO: wp_delete_post( $post_id, TRUE );

		// We need to reset the ID for further edit screens
		// to start using the native data post now as
		// native post data has been created already by
		// cb2_save_post_move_to_native()
		if ( CB2_DEBUG_SAVE ) print( "<h2>cb2_save_post_redirect_to_native_post( $post_save_processing->redirect_to_native_ID )</h2>" );
		$page      = 'cb-post-edit';
		$post_type = $post->post_type;
		$action    = 'edit';
		$URL       = admin_url( "admin.php?page=$page&post=$post_save_processing->redirect_to_native_ID&post_type=$post_type&action=$action" );

		// If CB2_DEBUG_SAVE the redirect will be printed, not acted
		wp_redirect( $URL );
		exit();
	}
}

function cb2_get_post_metadata( $type, $post_id, $meta_key, $single ) {
	global $wpdb, $post_save_processing;

	$value = NULL; // Will cause the standard system to query metadata

	// Ignore pseudo metadata, e.g. _edit_lock
	if ( $meta_key && $meta_key[0] != '_' && $meta_key != 'native_ID' ) {
		if ( $post = get_post( $post_id ) ) {
			$post_type = $post->post_type;
			if ( $Class = CB_Query::schema_type_class( $post_type ) ) {
				if ( ! $post_save_processing->auto_draft_publish_transition ) {
					if ( $postmeta_table = CB_Database::postmeta_table( $Class, $meta_type, $meta_table_stub ) ) {
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
	// TODO: this is getting called 3 x times because all the hooks are linked here
	// TODO: move this in to CB_PostNavigator
	global $post_save_processing, $wpdb;
	static $first = TRUE;

	$prevent = FALSE;

	if ( $post = get_post( $ID ) ) {
		$post_type = $post->post_type;
		if ( $Class = CB_Query::schema_type_class( $post_type ) ) {
			if ( $class_database_table = CB_Database::database_table( $Class ) ) {
				if ( $meta_key && $meta_key[0] == '_' ) {
					// Ignore pseudo metadata, e.g. _edit_lock
					$prevent = TRUE;
				} else {
					if ( CB2_DEBUG_SAVE ) {
						$table = ( $post_save_processing->auto_draft_publish_transition ? "<b class='cb2-warning'>{$wpdb->prefix}posts</b>" : 'native tables' );
						if ( $first ) print( "<h2 class='cb2-WP_DEBUG'>update_meta_data($ID) => $table</h2>" );
						print( "<b>$meta_key</b>=$meta_value, </li>" );
						$first = FALSE;
					}
					if ( ! $post_save_processing->auto_draft_publish_transition ) {
						if ( $id_field = CB_Database::id_field( $Class ) ) {
							$post = CB_Query::ensure_correct_class( $post );
							if ( empty( $meta_value ) ) $meta_value = NULL;
							$data = array( $meta_key => $meta_value );

							if ( method_exists( $post, 'sanitize_data_for_table' ) )
								$data = $post->sanitize_data_for_table( $data, $formats );
							else
								$data = CB_Database::sanitize_data_for_table( $class_database_table, $data, $formats, TRUE );

							if ( CB2_DEBUG_SAVE ) {
								if ( ! is_string( $meta_value ) && ! is_numeric( $meta_value ) )
									krumo( $meta_value, $data );
								print( "<div class='cb2-debug cb2-high-debug' style='font-weight:bold;color:#600;'>cb2_update_post_metadata($Class/$post_type): [$meta_key] =&gt; [$meta_value]</div>" );
								// if ( $meta_key == 'recurrence_sequence' ) exit();
							}

							// Update
							// This field may be for another object being saved
							// so do not worry if it is not present in this table
							// TODO: This is executing the triggers for every meta-data update
							// in case of a post save this will fire many times
							if ( count( $data ) ) {
								$id      = CB_Query::id_from_ID_with_post_type( $ID, $post_type );
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
	}

	// Returning TRUE will prevent any updates
	return ( $prevent ? TRUE : NULL );
}

// ------------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------
// Framework integration
function cb2_wp_enqueue_scripts() {
	// TODO: re-enable CB_Enqueue
	wp_enqueue_script(  CB2_TEXTDOMAIN . '-plugin-scripts-public', plugins_url( 'public/assets/js/public.js', CB2_PLUGIN_ABSOLUTE ), array(), CB2_VERSION );
	add_thickbox();
}

function cb2_admin_enqueue_scripts() {
	// TODO: re-enable CB_Admin_Enqueue
	wp_enqueue_style(  CB2_TEXTDOMAIN . '-plugin-styles-public', plugins_url( 'public/assets/css/public.css', CB2_PLUGIN_ABSOLUTE ), array(), CB2_VERSION );
	wp_enqueue_style(  CB2_TEXTDOMAIN . '-plugin-styles-cmb2',   plugins_url( 'admin/includes/lib/cmb2/css/cmb2.min.css', CB2_PLUGIN_ABSOLUTE ), array(), CB2_VERSION );

	add_thickbox();
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
				// TODO: change redirect_post() in our post.php instead?
				'_edit_link' => "admin.php?page=cb-post-edit&post_type=$post_type&post=%d",
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
function cb2_pre_get_posts_redirect_wpdb( &$wp_query ) {
	// If the wp_query is for a managed post_type
	// then redirect the wpdb prefix
	if ( isset( $wp_query->query['post_type'] ) ) {
		$post_type = $wp_query->query['post_type'];
		if ( is_array( $post_type ) )
			$post_type = ( count( $post_type ) ? array_values( $post_type )[0] : NULL );
		if ( CB_Query::redirect_wpdb_for_post_type( $post_type ) ) {
			// Subsequent posts will be annotated
			// that they are from the native tables, not wp_post
			$wp_query->cb2_redirected_post_request = TRUE;
		}
	}
}

function cb2_post_results_unredirect_wpdb( $posts, $wp_query ) {
	global $wpdb;
	if ( property_exists( $wp_query, 'old_wpdb_posts' ) )    {
		$wpdb->posts    = $wp_query->old_wpdb_posts;
		unset( $wp_query->old_wpdb_posts );
	}
	if ( property_exists( $wp_query, 'old_wpdb_postmeta' ) ) {
		$wpdb->postmeta = $wp_query->old_wpdb_postmeta;
		unset( $wp_query->old_wpdb_postmeta );
	}
	return $posts;
}

function cb2_posts_results_add_automatic( $posts, $wp_query ) {
	// Add Automatic posts if requested
	$post_type_auto = CB_PeriodItem_Automatic::$static_post_type;

	if ( isset( $wp_query->query['post_type'] )
		&& ( $post_type = $wp_query->query['post_type'] )
		&& (
			( is_array( $post_type ) && in_array( $post_type_auto, $post_type ) )
			|| $post_type == $post_type_auto
		)
	) {
		if ( isset( $wp_query->query['date_query'] )
			&& isset( $wp_query->query['date_query']['after'] )
			&& isset( $wp_query->query['date_query']['before'] )
		) {
			$startdate_string = $wp_query->query['date_query']['after'];
			$enddate_string   = $wp_query->query['date_query']['before'];
			if ( $startdate_string && $enddate_string ) {
				$startdate = new DateTime( $startdate_string );
				$enddate   = new DateTime( $enddate_string );
				$startdate->setTime( 0, 0 );
				$enddate->setTime( 23, 59 );

				while ( $startdate < $enddate ) {
					array_push( $posts,  CB_PeriodItem_Automatic::post_from_date( $startdate ) );
					$startdate->add( new DateInterval( 'P1D' ));
				}

				usort( $posts, "cb2_posts_date_order" );

				// Reset pointers
				$wp_query->post_count  = count( $wp_query->posts );
				$wp_query->found_posts = (boolean) $wp_query->post_count;
				$wp_query->post = ( $wp_query->found_posts ? $wp_query->posts[0] : NULL );
			} else throw new Exception( "Cannot request [$post_type_auto] without date_query after and before" );
		} else throw new Exception( "Cannot request [$post_type_auto] without date_query after and before" );
	}

	return $posts;
}

function cb2_posts_date_order( $post1, $post2 ) {
	// Alphabetical order == date order
  return strcmp( $post1->post_date, $post2->post_date );
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
		CB_Query::ensure_correct_classes( $wp_query->posts, $wp_query );

		// Indicate that the posts are from a redirected request
		if ( property_exists( $wp_query, 'cb2_redirected_post_request' ) && $wp_query->cb2_redirected_post_request ) {
			foreach ( $wp_query->posts as &$post )
				$post->cb2_redirected_post_request = TRUE;
		}

		// Check to see which schema has been requested and switch it
		if ( isset( $wp_query->query['date_query']['compare'] ) ) {
			if ( $schema = $wp_query->query['date_query']['compare'] ) {
				$wp_query->posts = CB_Query::schema_type_all_objects( $schema );
			}
		}

		// Reset pointers
		$wp_query->post_count  = count( $wp_query->posts );
		$wp_query->found_posts = (boolean) $wp_query->post_count;
		$wp_query->post        = ( $wp_query->found_posts ? $wp_query->posts[0] : NULL );
	}
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



