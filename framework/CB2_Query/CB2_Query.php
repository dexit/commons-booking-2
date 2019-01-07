<?php
// -------------------------------------------- WP_DEBUG setup
error_reporting( 0 );
if ( WP_DEBUG ) include( 'krumo/class.krumo.php' );
error_reporting( E_ALL );
if ( ! function_exists( 'krumo' ) ) {
	function krumo( ...$params ) {
		if ( WP_DEBUG ) print( 'CB2_Query.php:8 (WP_DEBUG is TRUE) says install krumo for debug output here.' );
	}
}
if ( ! function_exists( 'xdebug_print_function_stack' ) ) {
	function xdebug_print_function_stack() {
		if ( WP_DEBUG ) var_dump( debug_backtrace() );
	}
}
define( 'CB2_DEBUG_SAVE',      WP_DEBUG && ! defined( 'DOING_AJAX' ) && FALSE );

// Native posts
define( 'CB2_ID_SHARING',    TRUE );
define( 'CB2_ID_BASE',       0 );
define( 'CB2_MAX_CB2_POSTS', 10000 );

define( 'CB2_CREATE_NEW',    -1 );
define( 'CB2_UPDATE', TRUE );
define( 'CB2_GET_METADATA_ASSIGN', '_get_metadata_assign' );
define( 'CB2_ALLOW_CREATE_NEW', TRUE ); // Allows CB2_CREATE_NEW to be passed as a numeric ID
define( 'CB2_ADMIN_COLUMN_POSTS_PER_PAGE', 4 );

// ----------------------------------------------------------------------------------------
// ----------------------------------------------------------------------------------------
// ----------------------------------------------------------------------------------------
class CB2_Query {
	// TODO: rename CB2_Query to CB2_Utility
  public static $json_date_format = 'Y-m-d H:i:s';
  public static $date_format      = 'Y-m-d';
  public static $datetime_format  = 'Y-m-d H:i:s';

  // -------------------------------------------------------------------- WordPress integration
  // Complementary to WordPress
  // with CB Object understanding
  // In the case that 2 posts exist:
  //   wp_posts                ID = 1000000001 normal wordpress post
  //   wp_cb2_view_period_post ID = 1000000001 fake wordpress post
  // we set the post_type to indicate which post we are talking about
	static function get_post_with_type( $post_type, $post_id, $output = OBJECT, $filter = 'raw', $instance_container = NULL ) {
		// ALWAYS gets data from native tables
		// get_post() wrapper
		// loads metadata from native tables only
		// This will use standard WP cacheing
		global $wpdb;
		$redirected_post_request = FALSE;

		if ( ! is_string( $post_type ) )
			throw new Exception( "get_post_with_type() \$post_type not a string" );
		if ( ! is_numeric( $post_id ) )
			throw new Exception( "get_post_with_type()[$post_type] \$post_id not numeric" );

		// We divert here to make callig code simpler
		if ( $post_type == CB2_User::$static_post_type ) {
			// throw new Exception( 'Use CB2_Query::get_user() for CB2_User.' );
			return CB2_Query::get_user( $post_id );
		}

		$Class = CB2_PostNavigator::post_type_Class( $post_type );
		if ( ! $Class )
			throw new Exception( "[$post_type] not managed" );

		// Redirect
		$old_wpdb_posts = $wpdb->posts;
		if ( $posts_table = CB2_Database::posts_table( $Class ) ) {
			$wpdb->posts = "$wpdb->prefix$posts_table";
			$redirected_post_request = TRUE;
		} else {
			$wpdb->posts = "{$wpdb->prefix}posts";
		}
		if ( CB2_DEBUG_SAVE && TRUE
			&& ( ! property_exists( $Class, 'posts_table' ) || $Class::$posts_table !== FALSE )
			&& $wpdb->posts == "{$wpdb->prefix}posts"
		) {
			throw new Exception( "[$Class] get_post_with_type() using wp_posts table" );
		}

		// TODO: get_post() will populate ALL fields from the post table: take advantage of this
		$post = get_post( $post_id, $output, $filter );
		if ( is_null( $post ) )
			throw new Exception( "[$Class/$post_type] not found in [$wpdb->posts] for [$post_id]" );

		if ( $post->post_type != $post_type ) {
			// WP_Post::get_instance() will check the cache
			// In some modes, post IDs can conflict
			// the DB is redirected now, so try again
			wp_cache_delete( $post_id, 'posts' );
			$post = get_post( $post_id, $output, $filter );
			if ( is_null( $post ) )
				throw new Exception( "[$Class/$post_type] not found in [$wpdb->posts] for [$post_id]" );
			if ( $post->post_type != $post_type )
				throw new Exception( "[$Class/$post_id] fetched a [$post->post_type] post_type from [$posts_table], not a [$post_type]" );
		}


		// Reset and Annotate
		// This will make a get_metadata_assign() call
		$post->cb2_redirected_post_request = $redirected_post_request;
		$wpdb->posts = $old_wpdb_posts;
		$cb2_post    = self::ensure_correct_class( $post, $instance_container, TRUE ); // TRUE = prevent_auto_draft_publish_transition

		return $cb2_post;
	}

	static function get_user( $ID ) {
		if ( ! $ID )      throw new Exception( "User [$ID] blank" );
		$wp_user = get_user_by( 'ID', $ID );
		if ( ! $wp_user ) throw new Exception( "User [$ID] not found" );
		return CB2_User::factory( $wp_user->ID, $wp_user->user_login );
	}

	static function ensure_correct_classes( &$posts, $instance_container = NULL, $prevent_auto_draft_publish_transition = FALSE ) {
		// TODO: Several embedded WP_Querys would cause a build up of static $all:
		//   move static <Time class>::$all arrays on to the $instance_container (not used yet)
		// static CB2_User::$all are ok, but CB2_Time varies according to the query
		// only a problem when using compare => view_mode
		// Currently, if several DIFFERENT time queries happen in the page load
		// the CB2_Week::$all will have all of the times in
		// However, this: will cause an error if no new CB2_Week are generated:
		//   CB2_Week::$all = array();

		// In place change the records
		$post_classes = CB2_PostNavigator::post_type_classes();
		foreach ( $posts as &$post )
			CB2_Query::ensure_correct_class( $post, $instance_container = NULL, $prevent_auto_draft_publish_transition, $post_classes );

		return $posts;
  }

	static function ensure_correct_class( &$post, $instance_container = NULL, $prevent_auto_draft_publish_transition = FALSE, $post_classes = NULL ) {
    // factory()s will also create the extra CB2_Time_Classes based OO data structures
    global $auto_draft_publish_transition;

		if ( ! $post )
			throw new Exception( 'ensure_correct_class() requires a valid WP_Post object' );
		if ( ! property_exists( $post, 'ID' ) )
			throw new Exception( 'ensure_correct_class() requires a WP_Post->ID property' );
		if ( ! property_exists( $post, 'post_type' ) )
			throw new Exception( 'ensure_correct_class() requires a WP_Post->ID property' );

		if ( ! $post_classes ) $post_classes = CB2_PostNavigator::post_type_classes();
		if ( isset( $post_classes[$post->post_type] ) ) {
			$Class = $post_classes[$post->post_type];
			// Do not re-create it if it already is!
			if ( ! is_a( $post, $Class ) ) {
				if ( method_exists( $Class, 'factory_from_properties' ) ) {
					if ( $post->ID > 0 ) CB2_Query::get_metadata_assign( $post );
					$properties = (array) $post;

					$old_auto_draft_publish_transition = $auto_draft_publish_transition;
					if ( $prevent_auto_draft_publish_transition ) $auto_draft_publish_transition = FALSE;
					$post = $Class::factory_from_properties( $properties, $instance_container );
					$auto_draft_publish_transition = $old_auto_draft_publish_transition;

					if ( is_null( $post ) )
						throw new Exception( "Failed to create [$Class] class from post" );
					if ( ! property_exists( $post, 'ID' ) )
						throw new Exception( "[$Class::factory_from_properties()] return has no ID property (0 would be valid)" );

					// Only cache set this if it is a fake native post
					// pure pseudo classes like CB2_Week are not accessed with get_post()
					// thus caching is not relevant
					$wp_cache = ( property_exists( $post, 'ID' ) && ( ! property_exists( $Class, 'posts_table' ) || $Class::$posts_table ) );
					if ( $wp_cache ) wp_cache_set( $post->ID, $post, 'posts' );

					if ( WP_DEBUG && FALSE )
						print( "<div class='cb2-WP_DEBUG-small'>Created a [$Class] for [$post->ID] wp_cache:[$wp_cache]</div>" );
				}
			}
		}

		return $post;
	}

	static function is_system_metadata( String $meta_name ) {
		return ( $meta_name && $meta_name[0] == '_' );
	}

	static function pass_through_query_string( $path, $additional_parameters = array(), $remove_parameters = array() ) {
		$get = array_merge( $_GET, $additional_parameters );
		foreach ( $remove_parameters as $name ) unset( $get[$name] );

		if ( count( $get ) ) {
			$existing_query_string = array();
			if ( strchr( $path, '?' ) ) {
				$existing_query_string_pairs = explode( '&', explode( '?', $path, 2 )[1] );
				foreach ( $existing_query_string_pairs as $value )
					$existing_query_string[ CB2_Query::substring_before( $value, '=' ) ] = 1;
			}
			foreach ( $get as $name => $value ) {
				if ( ! isset( $existing_query_string[ $name ] ) ) {
					if ( is_array( $value ) ) $value = implode( ',', $value );
					$path .= ( strchr( $path, '?' ) ? '&' : '?' );
					$path .= urlencode( $name ) . '=' . urlencode( $value );
				}
			}
		}

		return $path;
	}

	static function wpdb_postmeta_is_redirected() {
		global $wpdb;
		return ( strstr( $wpdb->postmeta, 'cb2_view_' ) !== FALSE );
	}

	static function wpdb_posts_is_redirected() {
		global $wpdb;
		return ( strstr( $wpdb->posts, 'cb2_view_' ) !== FALSE );
	}

	static function redirect_wpdb_for_post_type( $post_type, $meta_redirect = TRUE, &$meta_type = NULL ) {
		global $wpdb, $auto_draft_publish_transition;
		$redirected = FALSE;
		$meta_type  = 'post';

		// If $auto_draft_publish_transition is happening
		// Then the primary $wpdb->postmeta table will be set to wp_postmeta
		// This happens when the post is still in the normal WP tables
		// not been moved yet to the native structures and views
		if ( ! $auto_draft_publish_transition ) {
			if ( $Class = CB2_PostNavigator::post_type_Class( $post_type ) ) {
				if ( ! property_exists( $Class, 'posts_table' ) || $Class::$posts_table !== FALSE ) {
					// perioditem-global => perioditem
					$post_type_stub = CB2_Query::substring_before( $post_type );
					if ( property_exists( $Class, 'posts_table' ) && is_string( $Class::$posts_table ) )
						$post_type_stub = $Class::$posts_table;
					// cb2_view_periodoccurence_posts
					$posts_table          = "{$wpdb->prefix}cb2_view_{$post_type_stub}_posts";
					if ( property_exists( $wpdb, 'old_wpdb_posts' ) ) array_push( $wpdb->old_wpdb_posts, $wpdb->posts );
					else $wpdb->old_wpdb_posts = array( $wpdb->posts );
					$wpdb->posts          = $posts_table;
					$redirected           = TRUE;
					if ( WP_DEBUG && FALSE ) print( "<span class='cb2-WP_DEBUG-small'>[$Class::$post_type] =&gt; [$posts_table]</span>" );
				}

				if ( $meta_redirect ) {
					if ( ! property_exists( $Class, 'postmeta_table' ) || $Class::$postmeta_table !== FALSE ) {
						// perioditem-global => perioditem
						$meta_type = CB2_Query::substring_before( $post_type );
						if ( property_exists( $Class, 'postmeta_table' ) && is_string( $Class::$postmeta_table ) )
							$meta_type = $Class::$postmeta_table;
						// cb2_view_periodoccurencemeta
						$postmeta_table        = "{$wpdb->prefix}cb2_view_{$meta_type}meta";
						$post_type_meta        = "{$meta_type}meta";
						$wpdb->$post_type_meta = $postmeta_table;
						// Note that requests using a redirected postmeta
						// cannot be cached by the WP_Query system
						// the meta-type will be post and thus conflict
						// and, in fact, we have turned auto-caching off
						// However, we need the redirect for the primary query meta-query JOINS
						if ( property_exists( $wpdb, 'old_wpdb_postmeta' ) ) array_push( $wpdb->old_wpdb_postmeta, $wpdb->postmeta );
						else $wpdb->old_wpdb_postmeta = array( $wpdb->postmeta );
						$wpdb->postmeta          = $postmeta_table;
						if ( WP_DEBUG && FALSE ) print( "<span class='cb2-WP_DEBUG-small'>[$Class::$post_type] =&gt; [$postmeta_table]</span>" );
					}
				} else if ( WP_DEBUG ) print( "<span class='cb2-WP_DEBUG-small'>[$Class::$post_type] no meta redirect</span>" );
			}
		}

		return $redirected;
	}

	static function unredirect_wpdb() {
		global $wpdb;

		if ( property_exists( $wpdb, 'old_wpdb_posts' ) && count( $wpdb->old_wpdb_posts ) )    {
			$wpdb->posts = array_shift( $wpdb->old_wpdb_posts );
		} else $wpdb->posts = "{$wpdb->prefix}posts";

		if ( property_exists( $wpdb, 'old_wpdb_postmeta' ) && count( $wpdb->old_wpdb_postmeta )  )    {
			$wpdb->postmeta = array_shift( $wpdb->old_wpdb_postmeta );
		} else $wpdb->postmeta = "{$wpdb->prefix}postmeta";
	}

	static function get_metadata_assign( &$post ) {
		// Switch base tables to our views
		// Load all associated metadata and assign to the post object
		global $wpdb;

		if ( ! is_object( $post ) )
			throw new Exception( 'get_metadata_assign() post object required' );
		if ( ! property_exists( $post, 'ID' )        || ! $post->ID )
			throw new Exception( 'get_metadata_assign: $post->ID required' );
		if ( ! property_exists( $post, 'post_type' ) || ! $post->post_type )
			throw new Exception( 'get_metadata_assign: $post->post_type required' );

		$ID              = $post->ID;
		$post_type       = $post->post_type;

		if ( ! property_exists( $post, CB2_GET_METADATA_ASSIGN ) || ! $post->{CB2_GET_METADATA_ASSIGN} ) {
			// get_metadata( $meta_type, ... )
			//   meta.php has _get_meta_table( $meta_type );
			//   $table_name = $meta_type . 'meta';
			//   $meta_type  = post_type stub, e.g. perioditem
			// get_metadata() will use standard WP caches
			self::redirect_wpdb_for_post_type( $post_type, TRUE, $meta_type );
			$metadata = get_metadata( $meta_type, $ID ); // e.g. perioditem, 40004004
			self::unredirect_wpdb();

			// Convert to objects
			foreach ( $metadata as $meta_key => &$meta_value_array ) {
				if ( ! self::is_system_metadata( $meta_key ) ) {
					if ( WP_DEBUG ) {
						if ( ! is_array( $meta_value_array ) )
							throw new Exception( "[$post_type/$meta_key] is not an array" );
						/* Not sure why some values are multiple at the moment
						if ( count( $meta_value_array ) > 1 ) {
							krumo( $meta_value_array );
							throw new Exception( "[$post_type/$meta_key] yielded a multi-value array" );
						}
						*/
					}
					$meta_value      = CB2_Query::to_object( $meta_key, $meta_value_array[0] );
					$post->$meta_key = $meta_value;
					//if ( ! is_object( $meta_value ) ) print( "<div class='cb2-WP_DEBUG-small'>$meta_key = $meta_value $meta_value_array[0]</div>" );
				}
			}

			// Register that all metadata is present
			$post->{CB2_GET_METADATA_ASSIGN} = TRUE;

			if ( WP_DEBUG ) {
				// Check that some meta data is returned
				$has_metadata = FALSE;
				foreach ( $metadata as $meta_key => $meta_value_array ) {
					if ( ! self::is_system_metadata( $meta_key ) ) $has_metadata = TRUE;
				}
				if ( ! $has_metadata && $meta_type != 'post' ) {
					krumo( $wpdb );
					throw new Exception( "[$post_type/$meta_type] [$ID] returned no metadata" );
				}

				if ( CB2_DEBUG_SAVE && FALSE )
					krumo( $ID, $post_type, $post->post_status, $meta_type, $metadata );
			}
		}

		//return $post; // Passed by reference, so no need to check result
  }

  // -------------------------------------------------------------------- Class, Function and parameter utilities
  // meta-data => objects
  static function ensure_assoc_bitarray_integer( String $name, $object ) {
		// Array(4,32,64) = 4 + 32 + 64 => 100
		$object = self::ensure_bitarray( $name, $object, TRUE );
		return CB2_Database::bitarray_to_int( $object );
  }

  static function ensure_assoc_bitarray( String $name, $object ) {
		$object = self::ensure_bitarray( $name, $object );
		$assoc_array = array();
		foreach ( $object as $loc => $on ) {
			if ( $on ) array_push( $assoc_array, (string) pow( 2, $loc ) );
		}
		return $assoc_array;
  }

  static function ensure_bitarray( String $name, $object, $assoc = FALSE ) {
		// 6 => array(0,1,1)
		if ( is_null( $object ) ) {
			$object = array();
		} else if ( is_array( $object ) ) {
			if ( self::array_has_associative( $object ) || $assoc ) {
				$object = array_sum( $object );
				$object = CB2_Database::int_to_bitarray( $object );
			}
		} else if ( self::check_for_serialisation( $object, 'a' ) ) {
			$object = unserialize( $object );
			if ( self::array_has_associative( $object ) ) {
				$object = array_sum( $object );
				$object = CB2_Database::int_to_bitarray( $object );
			}
		} else if ( is_numeric( $object ) ) {
			$object = CB2_Database::int_to_bitarray( $object );
		} else {
			krumo( $object );
			throw new Exception( "Cannot understand bit array value for [$name]" );
		}
		return $object;
  }

	static function ensure_datetime( String $name, $object ) {
		// Maintains NULLs
		// empty = NULL
		$datetime     = NULL;
		$now          = new CB2_DateTime();
		$epoch_min    = 20000000; // Thursday, 20 August 1970

		if      ( is_null( $object ) )          $datetime = NULL;
		else if ( is_string( $object ) && empty( $object ) )
																						$datetime = NULL;
		else if ( $object === FALSE )           $datetime = NULL; // Happens when object->property fails
		else if ( $object instanceof DateTime ) $datetime = new CB2_DateTime( $object );
		else if ( $object instanceof CB2_DateTime ) $datetime = $object;
		else if ( is_numeric( $object ) && $object > $epoch_min )
																						$datetime = $now->setTimestamp( (int) $object );
		else if ( is_string( $object ) )        $datetime = new CB2_DateTime( $object );
		else if ( is_array( $object ) && isset( $object['date'] ) ) {
			$string = trim( implode( ' ', $object ) );
			if ( $string ) $datetime = new CB2_DateTime( $string );
		} else {
			krumo( $object );
			throw new Exception( "[$name] has unhandled datetime type" );
		}

		return $datetime;
	}

	static function ensure_time( String $name, $object ) {
		// TODO: ensure_time()
		return $object;
	}

	static function ensure_array( String $name, $object ) {
		if ( ! is_array( $object ) ) {
			if ( self::check_for_serialisation( $object, 'a' ) ) {
				$object = unserialize( $object );
			} else if ( is_string( $object ) ) {
				if ( empty( $object ) )
					$object = array();
				else if ( preg_match( '/^[0-9, ]+$/', $object ) )
					$object = explode( ',', $object );
				else
					throw new Exception( "[$name] array not understood" );
			}
		}

		return $object;
	}

	static function ensure_int( String $name, $object, Bool $allow_create_new = FALSE ) {
		$int = NULL;
		if ( ! is_null( $object ) ) {
			if      ( $allow_create_new && $object == CB2_CREATE_NEW ) {
				// Special value indicating that the ID object should be created
				$int = CB2_CREATE_NEW;
			}
			else if ( is_numeric( $object ) ) $int = (int) $object;
			else if ( is_string( $object ) && empty( $object ) ) $int = 0;
			else if ( is_array( $object ) )   $int = array_sum( $object );
			else {
				krumo($object);
				throw new Exception( "[$name] is not numeric [$object]" );
			}
		}
		return $int;
	}

	static function ensure_boolean( String $name, $object ) {
		$boolean = FALSE;
		if      ( is_null( $object ) )    $boolean = FALSE;
		else if ( is_object( $object ) && method_exists( $object, '__Boolean' ) ) $boolean = $object->__Boolean();
		else if ( is_string( $object ) )  $boolean = ( $object && $object != '0' && strtolower( $object ) != 'false' && strtolower( $object ) != 'no' );
		else if ( is_numeric( $object ) ) $boolean = (int) $object != 0;
		else                              $boolean = (bool) $object;
		return $boolean;
	}

	static function ensure_char( String $name, $object ) {
		if ( $object instanceof DateTime ) $object = $object->format( self::$datetime_format );
		else if ( ! is_string( $object ) ) $object = (string) $object;
		return $object;
	}

	static function ensure_ints( String $name, $object, Bool $allow_create_new = FALSE ) {
		$array = array();
		if ( ! is_null( $object ) ) {
			if ( is_array( $object ) ) {
				$array = $object;
				foreach ( $array as &$value ) {
					$value = self::ensure_int( $name, $value, $allow_create_new );
				}
			}
			else if ( is_string( $object ) && empty( $object ) ) {
				$array = array();
			}
			else if ( self::check_for_serialisation( $object, 'a' ) ) {
				$array = unserialize( $object );
				foreach ( $array as &$value ) {
					$value = self::ensure_int( $name, $value, $allow_create_new );
				}
			}
			else if ( is_string( $object ) && strchr( $object, ',' ) !== FALSE ) {
				$array = explode( ',', $object );
				foreach ( $array as &$value ) {
					$value = self::ensure_int( $name, $value, $allow_create_new );
				}
			}
			else if ( is_numeric( $object ) ) {
				$array = array( self::ensure_int( $name, $object, $allow_create_new ) );
			}
			else {
				krumo( $object );
				throw new Exception( "[$name] is can not be converted to an array" );
			}
		}
		return $array;
	}

	static function assign_all_parameters( $object, Array $parameter_values, String $class_name = NULL, String $method = '__construct' ) {
		// Take all function parameters
		// And make them properties of the class
		// Typical usage:
		//   CB2_Query::assign_all_parameters( $this, func_get_args(), __class__ );
		if ( ! $class_name ) $class_name = get_class( $object );
		$reflection            = new ReflectionMethod( $class_name, $method ); // PHP 5, PHP 7
		$parameters            = $reflection->getParameters();

		// Match raw values to given parameters OR defaults
		// It is a PHP error to pass a wrong parameter count
		// which means that all parameters must either have a value or a default
		// getDefaultValue() will throw an exception if the parameter does not have one
		$parameter_value_count = count( $parameter_values );
		foreach ( $parameters as $i => &$parameter ) {
			$name                 = $parameter->name;
			$has_stated_parameter = $i < $parameter_value_count;
			$parameter->value = ( $has_stated_parameter ? $parameter_values[$i] : $parameter->getDefaultValue() );
		}

		// Assign to object
		foreach ( $parameters as $i => &$parameter ) {
			$name  = $parameter->name;
			$value = $parameter->value;

			if ( WP_DEBUG && FALSE ) {
				print( "<i>assign_all_parameters($class_name)->$name</i>: <b>" );
				if      ( $value instanceof DateTime ) print( $value->format( CB2_Database::$database_datetime_format ) );
				else if ( is_object( $value ) ) krumo( $value );
				else if ( is_array(  $value ) ) krumo( $value );
				else print( $value );
				$new_value = self::to_object( $name, $value );
				if ( $new_value !== $value ) {
					print( ' =&gt; ' );
					if      ( $new_value instanceof DateTime ) print( 'new CB2_DateTime(' . $new_value->format( CB2_Database::$database_datetime_format ) . ')' );
					else if ( is_object( $new_value ) ) krumo( $new_value );
					else if ( is_array(  $new_value ) ) krumo( $new_value );
					else print( $new_value );
				}
				print( '</b><br/>' );
			}

			// May throw Exceptions
			$new_value = self::to_object( $name, $value );
			$object->$name = $new_value;

		}
	}

	static function is_IDs( String $name, String &$object_name = NULL, Bool &$is_plural = NULL ) {
		// TRUE for _ID and _IDs
		$is_plural   = self::is_plural( $name, $base_name );
		$is_ID       = ( substr( $base_name, -3 ) == '_ID' );
		$object_name = ( $is_ID ? substr( $base_name, 0, -3 ) : $base_name );
		return $is_ID;
	}

	static function is_plural( String $name, String &$base_name = NULL ) {
		$is_plural = FALSE;
		$base_name = $name;
		$last_part = CB2_Query::substring_after( $name, '_' );

		switch ( $last_part ) {
			case 'status':
				break;
			default:
				$is_plural = ( substr( $name, -1 ) == 's' );
				$base_name = ( $is_plural ? substr( $name, 0, -1 ) : $name );
		}

		return $is_plural;
	}

	static function cmb2_fields() {
		static $fields = NULL;

		if ( is_null( $fields ) && class_exists( 'CMB2_Boxes' ) ) {
			$fields     = array();
			$cmb2_boxes = CMB2_Boxes::get_all();
			foreach ( $cmb2_boxes as $cmb2_box ) {
				$fields = array_merge( $fields, $cmb2_box->prop( 'fields' ) );
			}
			if ( WP_DEBUG ) ksort( $fields );
		}

		return $fields;
	}

	static function to_object( String $name, $value, Bool $convert_dates = TRUE ) {
		// Assigning attributes of PHP Objects
		//   string => object
		// based on the property name
		//
		// Plurals allow the follwing:
		//   comma delimited integers,              e.g. 3453,34522,223
		//   serialised arrays (must contain a { ), e.g. a:1:{i:0:... , ,}
		// comma delimited strings are not permitted
		// because they cannot be reliably differentiated from serialised arrays
		//
		// Used during:
		//   factory_from_properties()
		// by:
		//   copy_all_wp_post_properties(): post->*         => object (no date conversion)
		//   assign_all_parameters():       get_func_args() => object
		//   get_metadata_assign():         meta-data       => object
		//
		// It is (optionally) the job of the object to:
		// convert *_ID(s) => objects using
		//   get_or_create_new(ids)
		//
		// TODO: move all this in to the CB2_DatabaseTable_PostNavigator Class
		// to use the database schema knowledge instead :)

		// All these are static cached
		$object_types = self::class_fields();
		$columns      = CB2_Database::columns();
		$cmb2_fields  = self::cmb2_fields();

		$debug        = WP_DEBUG && FALSE;
		$is_IDs       = self::is_IDs( $name, $object_name, $is_plural ); // TRUE for _ID and _IDs

		// UnSerialize multiple meta-values and serialized arrays
		if ( $is_plural ) {
			if ( ! is_array( $value ) && $debug )
				print( "<div class='cb2-WP_DEBUG-small'>converting [$name/$object_name] to an array</div>" );
			$value = self::ensure_array( $name, $value );
		}

		if ( isset( $object_types[$object_name] ) ) {
			// -------------------------------------------------- Object type
			// it could be a request to create object(s)
			// TODO: allow columns to handle _ID(s) requests instead?
			//   period_ID  = -1
			//   period_IDs = array(array(-1, details...), array(-1, details...), ...)
			// or the actual object
			//   period     = <Object>
			//   periods    = array(<Object>, <Object>, ...)
			if ( $is_IDs ) {
				// _IDs _ID
				if ( $is_plural ) {
					if ( $debug ) print( "<div class='cb2-WP_DEBUG-small'>[$name/$object_name] is a [$object_name] object ID list</div>" );
					// TODO: to_object(): we support arrays of arrays now as well
					// when specifying object creatio
					//   period_IDs = array(array(-1, details...), array(-1, details...), ...)
					//$value = self::ensure_ints( $name, $value, CB2_ALLOW_CREATE_NEW );
				} else {
					if ( $debug ) print( "<div class='cb2-WP_DEBUG-small'>[$name/$object_name] is a [$object_name] object ID</div>" );
					$value = self::ensure_int( $name, $value, CB2_ALLOW_CREATE_NEW );
				}
			} else if ( WP_DEBUG ) {
				// base classes: period_entity, ...
				// and derived classes: preioditem_location, ...
				// and final classes: period, period_status_type, ...
				$Class = $object_types[$object_name];
				if ( $debug ) print( "<div class='cb2-WP_DEBUG-small'>[$name/$object_name] is a [$Class] object</div>" );
				if ( ! is_null( $value )  ) {
					if ( $is_plural ) {
						if ( count( $value ) && ! ( $value[0] instanceof $Class ) ) {
							krumo( $value );
							throw new Exception( "[$name/$object_name] is not instance of [$Class]" );
						}
					} else {
						if ( ! ( $value instanceof $Class ) ) {
							krumo( $value );
							throw new Exception( "[$name/$object_name] is not instance of [$Class]" );
						}
					}
				}
			}
		} else if ( isset( $columns[$name] ) ) {
			// -------------------------------------------------- Database field
			// ID, period_id, post_status, ...
			$column = $columns[$name];
			if ( $debug ) print( "<div class='cb2-WP_DEBUG-small'>[$name/$object_name] is a column</div>" );
			switch ( $column->Type ) {
				case CB2_INT:
				case CB2_TINYINT:
				case CB2_BIGINT:
					if ( $is_plural ) $value = self::ensure_ints( $name, $value );
					else              $value = self::ensure_int( $name, $value );
					break;
				case CB2_VARCHAR:
				case CB2_CHAR:
				case CB2_LONGTEXT:
				case CB2_TEXT:
					$value = self::ensure_char( $name, $value );
					break;
				case CB2_DATETIME:
					// WP_Post properties store datetime as string, not object
					// copy_all_wp_post_properties() will set $convert_dates = FALSE
					if ( $convert_dates ) $value = self::ensure_datetime( $name, $value );
					break;
				case CB2_TIMESTAMP:
					$value = self::ensure_time( $name, $value );
					break;
				case CB2_BIT:
					if ( $column->Size == 1 ) $value = self::ensure_boolean( $name, $value );
					else $value = self::ensure_int( $name, $value );
					break;
			}
		} else if ( isset( $cmb2_fields[$name] ) ) {
			// -------------------------------------------------- CMB2 pseudo meta data
			// Do nothing: get_metadata_assign() is capturing the value of a metabox
			// Use sanitize_value() to change it
			if ( $debug ) print( "<div class='cb2-WP_DEBUG-small' style='color:red;font-weight:bold;'>[$name/$object_name] is a CMB2 pseudo-field</div>" );
		} else {
			krumo( $cmb2_fields, $object_types, $columns );
			self::debug_print_backtrace( "[$name/$object_name] Field is unknown CB2_Database column, CB2_PostNavigator object type or a CMB2 field" );
			exit();
		}

		if ( self::check_for_serialisation( $value ) )
			throw new Exception( "[$value] looks like serialised. This happens because we get_metadata() with SINGLE when WordPress serialises arrays in the meta_value field" );

		return $value;
	}

  // ---------------------------------------------- General utilities
  static function has_own_method( String $Class, String $method ) {
		$ReflectionClass = new ReflectionClass( $Class );
		return ( $ReflectionClass->hasMethod( $method )
			&& ( $method_object = $ReflectionClass->getMethod( $method ) )
			&& $method_object->class == $Class
		);
  }

  static function class_fields() {
		static $class_fields = NULL;

		if ( is_null( $class_fields ) ) {
			$class_fields = array();
			foreach ( self::Classes() as $Class ) {
				$class_name = preg_replace( '/^[A-Z][A-Za-z0-9]*_/', '', $Class );
				$class_name = preg_replace( '/([^A-Z])([A-Z])/', '\1_\2', $class_name );
				$class_name = strtolower( $class_name );
				$class_fields[$class_name] = $Class;
				if ( property_exists( $Class, 'static_post_type' ) )
					$class_fields[$Class::$static_post_type] = $Class;
			}
		}

		return $class_fields;
  }

  static function Classes() {
		// TODO: place these in to CB2_PostNavigator?
		static $Classes = array();
		if ( ! count( $Classes ) ) $Classes = self::subclasses( 'CB2_PostNavigator' );
		return $Classes;
  }

  static function subclasses( String $BaseClass ) {
    $subclasses = array();
    foreach ( get_declared_classes() as $Class ) { // PHP 4
			$ReflectionClass = new ReflectionClass( $Class );
			if ( $ReflectionClass->isSubclassOf( $BaseClass ) ) // PHP 5
				array_push( $subclasses, $Class );
    }
    return $subclasses;
	}

  static public function array_walk_paths( Array &$array, stdClass $object ) {
		array_walk_recursive( $array, array( 'CB2_Query', 'array_walk_paths_callback' ), $object );
  }

  static public function array_walk_paths_callback( &$value, String $name, stdClass $object ) {
		if ( is_string( $value ) && preg_match( '/%[^%]+%/', $value ) )
			$value = self::object_value_path( $object, $value );
  }

	static public function object_value_path( $object, $spec ) {
		// $spec = %object_property_name->object_property_name->...%
		// e.g. date->time
		$value = $spec;
		if ( is_string( $spec ) && preg_match( '/%[^%]+%/', $spec ) ) {
			$property_path = explode( '->', substr( $spec, 1, -1 ) );
			$properties    = (array) $object;
			foreach ( $property_path as $property_step ) {
				if ( is_array( $properties ) && isset( $properties[$property_step] ) ) {
					$value      = $properties[$property_step];
					$properties = (array) $value;
				} else if ( WP_DEBUG ) {
					krumo( $properties, $property_step );
					throw new Exception( "[$property_step] not found on object" );
				}
			}
			// if ( WP_DEBUG ) print( "$spec = $value " );
		}
		return $value;
	}

  static function substring_before( String $string, String $delimiter = '-' ) {
		return ( strpos( $string, $delimiter ) === FALSE ? $string : substr( $string, 0, strpos( $string, $delimiter ) ) );
	}

  static function substring_after( String $string, String $delimiter = '-' ) {
		return ( strrpos( $string, $delimiter ) === FALSE ? $string : substr( $string, strrpos( $string, $delimiter ) + 1 ) );
	}

	static function check_for_serialisation( $object, String $type = 'a-z' ) {
		return ( is_string( $object ) && preg_match( "/^[$type]:[0-9]+:/", $object ) );
	}

	static function camel_to_underscore( String $name ) {
		return preg_replace( '/([a-z0-9])([A-Z])/', '\1_\2', $name );
	}

	static function implode( $delimiter, $array, $associative_delimiter = '=', $object = NULL ) {
		$string = NULL;
		if ( self::array_has_associative( $array ) ) {
			$string = '';
			foreach ( $array as $key => $value ) {
				if ( $string ) $string .= $delimiter;
				if ( $object ) $value = self::object_value_path( $object, $value );
				$string .= "$key$associative_delimiter$value";
			}
		} else $string = implode( $delimiter, $array );

		return $string;
	}

	static function array_has_associative( Array $array ) {
		return array_keys($array) !== range(0, count($array) - 1);
	}

	static function debug_print_backtrace( String $message = NULL ) {
		// This function is here because QueryMonitor traps all PHP errors
		// and prevents a decent Stack Trace in xdebug
		static $css_bubble   = "font-size:10px; display:inline-block; margin:1px 0 0 2px; padding:0 5px; min-width:7px; height:17px; border-radius:11px; color:#444; font-size:9px; line-height:17px; text-align:center; box-shadow:0px 0px 1px #aaa;background-color:#fff;";
		static $css_debug    = "margin:1px 3px 0 2px; padding: 2px 5px; min-width: 7px; border-radius: 11px; background-color: #500; color: #fff; font-size: 9px; text-align: center;";
		static $css_function = "overflow:scroll;max-height:200px;color:#010;font-size:10px;margin:0px 0px 20px 30px;padding-left:10px;border-left:14px solid #d7d7d7;border-bottom:4px solid #d7d7d7;";
		static $css_button   = 'line-height:12px;height:12px;width:12px;padding:0px;margin:0px;';
		static $expand       = 'var tr = this.parentNode.parentNode.nextSibling;var hidden = tr.getAttribute("style") == "display:none;";tr.setAttribute("style", ( hidden ? "" : "display:none;" ));tr.firstChild.firstChild.scrollTop = 200000;this.innerText = ( hidden ? "-" : "+" );';
		static $backlines    = 100;
		static $frontlines   = 5;
		$trace               = array_reverse( debug_backtrace() );
		$i                   = 0;
		$document_root       = $_SERVER['DOCUMENT_ROOT'];
		$document_root_len   = strlen( $document_root ) + 1;

		if ( WP_DEBUG ) {
			if ( $message ) print( "<div><span style='$css_debug'>WP_DEBUG:</span> <b>$message</b></div>" );

			print( '<table>' );
			//array_pop( $trace ); // debug_print_backtrace() call
			foreach ( $trace as $line ) {
				$function_name  = $line['function'];
				$class          = ( isset( $line['class'] )  ? $line['class']  : NULL );
				$object         = ( isset( $line['object'] ) ? $line['object'] : NULL );
				$type           = ( isset( $line['type'] )   ? $line['type']   : '->' );
				$absolute_path  = ( isset( $line['file'] )   ? $line['file']   : NULL );
				$lineno         = ( isset( $line['line'] )   ? $line['line']   : NULL );
				$file_contents  = ( $absolute_path ? file_get_contents( $absolute_path ) : '' );
				$file_lines     = explode( "\n", $file_contents );
				$function_lines = array_splice( $file_lines,
														( $lineno > $backlines ? $lineno - $backlines : 0 ),
														( $lineno > $backlines ? $backlines : $lineno ) + $frontlines
													);
				$relative_path  = substr( $absolute_path, $document_root_len );
				$dir            = dirname( $relative_path );
				$file           = basename( $relative_path );
				$link           = "file://$absolute_path";

				if ( strlen( $dir ) > 30 ) $dir = '...' . substr( $dir, strlen( $dir ) - 30 );
				$function_name_full = ( $class ? "$class$type$function_name" : $function_name );

				print( "<tr><td style='$css_bubble'>#$i</td>" );
				print( "<td style='white-space:nowrap;'>$dir/</td>" );
				print( "<td style='white-space:nowrap;'><b><a target='_blank' href='$link'>$file</a></b>:$lineno</td>" );
				print( "<td><button style='$css_button' onclick='$expand'>+</button></td>" );
				print( "<td><b>$function_name_full</b>( " );
				$first_arg = TRUE;
				$cutdir    = 'right';
				$cutoff    = 40;

				switch ( $function_name_full ) {
					case 'require_once':
						$cutoff = 80;
						$cutdir = 'left';
						break;
				}

				foreach ( $line['args'] as $arg ) {
					if ( is_null( $arg ) )     $argstring = ( 'NULL' );
					else if ( is_array( $arg ) )  {
						if ( count( $arg ) > 5 ) $argstring = ( 'Array(' . count( $arg ) . ')' );
						else {
							$argstring = ( '[' );
							$first_subarg = TRUE;
							foreach ( $arg as $subarg ) {
								if ( ! $first_subarg ) $argstring .= ( ', ' );
								if      ( is_null( $arg ) )      $argstring .= ( 'NULL' );
								else if ( is_array( $subarg ) )  $argstring .= ( 'Array(' . count( $subarg ) . ')' );
								else if ( is_object( $subarg ) ) $argstring .= ( get_class( $subarg) . ( property_exists( $subarg, 'ID' ) ? "($subarg->ID)" : '' ) );
								else if ( is_string( $subarg ) && empty( $subarg ) ) $argstring .= ( "''" );
								else if ( is_string( $subarg ) ) $argstring .= ( str_replace( $document_root, '', $subarg ) );
								else if ( is_bool( $subarg ) )   $argstring .= ( $subarg ? 'TRUE' : 'FALSE' );
								else $argstring .= ( $subarg );
								$first_subarg = FALSE;
							}
							$argstring .= ( ']' );
						}
					}
					else if ( is_object( $arg ) ) $argstring = ( get_class( $arg) . ( property_exists( $arg, 'ID' ) ? "($arg->ID)" : '' ) );
					else if ( is_string( $arg ) && empty( $arg ) ) $argstring = ( "''" );
					else if ( is_string( $arg ) ) $argstring = ( str_replace( $document_root, '', $arg ) );
					else if ( is_bool( $arg ) )   $argstring = ( $arg ? 'TRUE' : 'FALSE' );
					else $argstring = ( $arg );

					$argstring = htmlspecialchars( $argstring );
					if ( strlen( $argstring ) > $cutoff ) $argstring = ( $cutdir == 'right' ? substr( $argstring, 0, $cutoff ) . '...' :  '...' . substr( $argstring, -$cutoff) );
					if ( ! $first_arg ) print( ', ' );
					print( $argstring );
					$first_arg = FALSE;
				}

				print( ' )</td><td>' );
				print( '</td></tr>');
				print( "<tr style='display:none;'><td colspan='100'><ul style='$css_function'>" );
				foreach ( $function_lines as $function_line ) {
					$function_line = htmlspecialchars( $function_line );
					$function_line = preg_replace( '/function\\s+([^(]+)/', '<b>function $1</b>', $function_line );
					$function_line = str_replace(  ' ', str_repeat( '&nbsp;', 2 ), $function_line );
					$function_line = preg_replace( '/\\t/', str_repeat( '&nbsp;', 8 ), $function_line );
					$function_line = str_replace(  "$function_name(", "<b style='color:blue;'>$function_name</b>(", $function_line);
					if ( $class ) $function_line = str_replace(  "$class(", "<b style='color:blue;'>$class</b>(", $function_line);
					print( "<li style='margin:0px;line-height:14px;'>$function_line</li>" );
				}
				print( "</ul>" );
				if ( $object ) {
					//var_dump( $object );
				}
				print( "</td></tr>" );
				$i++;
			}
			print( '</table>' );
		}
	}
}
