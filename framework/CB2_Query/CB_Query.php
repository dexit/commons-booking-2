<?php
// -------------------------------------------- WP_DEBUG setup
error_reporting( 0 );
if ( WP_DEBUG ) include( 'krumo/class.krumo.php' );
error_reporting( E_ALL );
if ( ! function_exists( 'krumo' ) ) {
	function krumo( ...$params ) {
		if ( WP_DEBUG ) var_dump( $params );
	}
}
if ( ! function_exists( 'xdebug_print_function_stack' ) ) {
	function xdebug_print_function_stack() {
		if ( WP_DEBUG ) var_dump( debug_backtrace() );
	}
}
define( 'CB2_DEBUG_SAVE', WP_DEBUG && ! defined( 'DOING_AJAX' ) && FALSE );

// Native post create process
define( 'CB2_CREATE_NEW', -1 );
define( 'CB2_UPDATE', TRUE );
define( 'GET_METADATA_ASSIGN', '_get_metadata_assign' );
define( 'CB2_ALLOW_CREATE_NEW', TRUE ); // Allows CB2_CREATE_NEW to be passed as a numeric ID
define( 'CB2_ADMIN_COLUMN_POSTS_PER_PAGE', 4 );

// ----------------------------------------------------------------------------------------
// ----------------------------------------------------------------------------------------
// ----------------------------------------------------------------------------------------
class CB_Query {
	private static $schema_types         = array();
	private static $database_table_types = array();

  public  static $javascript_date_format = 'Y-m-d H:i:s';
  public  static $date_format = 'Y-m-d';
  public  static $datetime_format = 'Y-m-d H:i:s';
  public  static $meta_NULL = 'NULL';
	public static $days = array( 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun' );

  // -------------------------------------------------------------------- Reflection
  // Class lookups
  static function register_schema_type( $Class ) {
		$ReflectionClass = new ReflectionClass( $Class ); // PHP 5

		// Post types
		if ( property_exists( $Class, 'static_post_type' ) ) {
			if ( strlen( $Class::$static_post_type ) > 20 )
				throw new Exception( 'post_type [' . $Class::$static_post_type . '] is longer than the WordPress maximum of 20 characters' );
			self::$schema_types[ $Class::$static_post_type ] = $Class;
		}

		// Database table types
		if ( $ReflectionClass->isSubclassOf( 'CB_DatabaseTable_PostNavigator' ) ) {
			self::$database_table_types[ $Class ] = $Class;
		}
  }

  static function &schema_types() {
    return self::$schema_types;
  }

  static function post_types() {
    return array_keys( self::$schema_types );
  }

  static function schema_type_class( $post_type ) {
		$post_types = self::schema_types();
		return isset( $post_types[$post_type] ) ? $post_types[$post_type] : NULL;
  }

  static function schema_type_all_objects( $post_type, $values_only = TRUE ) {
		$all = NULL;
		if ( $Class = CB_Query::schema_type_class( $post_type ) ) {
			if ( property_exists( $Class, 'all' ) ) {
				if ( $values_only ) $all = array_values( $Class::$all );
				else $all = $Class::$all;
			}
		}
		return $all;
  }

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
		if ( $post_type == CB_User::$static_post_type ) {
			// throw new Exception( 'Use CB_Query::get_user() for CB_User.' );
			return CB_Query::get_user( $post_id );
		}

		$Class = self::schema_type_class( $post_type );
		if ( ! $Class )
			throw new Exception( "[$post_type] not managed" );

		// Redirect
		$old_wpdb_posts = $wpdb->posts;
		if ( $posts_table = CB_Database::posts_table( $Class ) ) {
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

		// WP_Post::get_instance() will check the cache
		// TODO: Can we intelligently wp_cache_delete() instead?
		wp_cache_delete( $post_id, 'posts' );

		// TODO: get_post() will populate ALL fields from the post table: take advantage of this
		$post = get_post( $post_id, $output, $filter );
		if ( is_null( $post ) )
			throw new Exception( "[$Class/$post_type] not found in [$wpdb->prefix] [$wpdb->posts] for [$post_id]" );
		if ( $post->post_type != $post_type )
			throw new Exception( "[$Class/$post_id] fetched a [$post->post_type] post_type from [$posts_table], not a [$post_type]" );

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
		return CB_User::factory( $wp_user->ID, $wp_user->user_login );
	}

	static function ensure_correct_classes( &$posts, $instance_container = NULL, $prevent_auto_draft_publish_transition = FALSE ) {
		// TODO: Several embedded queries: move static <Time class>::$all arrays on to the $instance_container
		// static CB_User::$all are ok, but CB_Time varies according to the query
		// only a problem when using compare => view_mode
		// Currently, if several DIFFERENT time queries happen in the page load
		// the CB_Week::$all will have all of the times in
		// However, this: will cause an error if no new CB_Week are generated:
		//   CB_Week::$all = array();

		// In place change the records
		$post_classes = self::schema_types();
		foreach ( $posts as &$post )
			CB_Query::ensure_correct_class( $post, $instance_container = NULL, $prevent_auto_draft_publish_transition, $post_classes );

		return $posts;
  }

	static function ensure_correct_class( &$post, $instance_container = NULL, $prevent_auto_draft_publish_transition = FALSE, $post_classes = NULL ) {
    // factory()s will also create the extra CB_Time_Classes based OO data structures
		// TODO: The WP_Post may have all its metadata loaded already
		// as the wordpress system adds all fields to the WP_Post dynamically
    global $auto_draft_publish_transition;

		if ( ! $post )
			throw new Exception( 'ensure_correct_class() requires a valid WP_Post object' );
		if ( ! property_exists( $post, 'ID' ) )
			throw new Exception( 'ensure_correct_class() requires a WP_Post->ID property' );
		if ( ! property_exists( $post, 'post_type' ) )
			throw new Exception( 'ensure_correct_class() requires a WP_Post->ID property' );

		if ( ! $post_classes ) $post_classes = self::schema_types();
		if ( isset( $post_classes[$post->post_type] ) ) {
			$Class = $post_classes[$post->post_type];
			// Do not re-create it if it already is!
			if ( ! is_a( $post, $Class ) ) {
				if ( method_exists( $Class, 'factory_from_properties' ) ) {
					if ( $post->ID > 0 ) CB_Query::get_metadata_assign( $post );
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
					// pure pseudo classes like CB_Week are not accessed with get_post()
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

	static function get_post_types() {
		global $wpdb;
		$post_types = wp_cache_get( 'cb2-post-types' );
		if ( ! $post_types ) {
			$post_types = $wpdb->get_results( "SELECT post_type, ID_multiplier, ID_base FROM {$wpdb->prefix}cb2_post_types ORDER BY ID_base DESC", OBJECT_K );
			wp_cache_set( 'cb2-post-types', $post_types );
		}
		return $post_types;
	}

	static function pass_through_query_string( $path, $additional_parameters = array(), $remove_parameters = array() ) {
		$get = array_merge( $_GET, $additional_parameters );
		foreach ( $remove_parameters as $name ) unset( $get[$name] );

		if ( count( $get ) ) {
			$existing_query_string = array();
			if ( strchr( $path, '?' ) ) {
				$existing_query_string_pairs = explode( '&', explode( '?', $path, 2 )[1] );
				foreach ( $existing_query_string_pairs as $value )
					$existing_query_string[ CB_Query::substring_before( $value, '=' ) ] = 1;
			}
			foreach ( $get as $name => $value ) {
				if ( ! isset( $existing_query_string[ $name ] ) ) {
					$path .= ( strchr( $path, '?' ) ? '&' : '?' );
					$path .= urlencode( $name ) . '=' . urlencode( $value );
				}
			}
		}

		return $path;
	}

	static function redirect_wpdb_for_post_type( $post_type, $meta_redirect = TRUE ) {
		global $wpdb;
		$redirected = FALSE;

		if ( $Class = CB_Query::schema_type_class( $post_type ) ) {
			// TODO: Reset the posts to the normal table necessary?
			// maybe it will interfere with other plugins?
			$wpdb->posts    = "{$wpdb->prefix}posts";

			if ( ! property_exists( $Class, 'posts_table' ) || $Class::$posts_table !== FALSE ) {
				// perioditem-global => perioditem
				$post_type_stub = CB_Query::substring_before( $post_type );
				if ( property_exists( $Class, 'posts_table' ) && is_string( $Class::$posts_table ) )
					$post_type_stub = $Class::$posts_table;
				// cb2_view_periodoccurence_posts
				$posts_table    = "{$wpdb->prefix}cb2_view_{$post_type_stub}_posts";
				$wpdb->posts    = $posts_table;
				$redirected     = TRUE;
				//if ( WP_DEBUG ) print( "<span class='cb2-WP_DEBUG-small'>[$Class::$post_type] =&gt; [$posts_table]</span>" );
			}

			if ( $meta_redirect ) {
				if ( ! property_exists( $Class, 'postmeta_table' ) || $Class::$postmeta_table !== FALSE ) {
					// perioditem-global => perioditem
					$post_type_stub = CB_Query::substring_before( $post_type );
					if ( property_exists( $Class, 'postmeta_table' ) && is_string( $Class::$postmeta_table ) )
						$post_type_stub = $Class::$postmeta_table;
					// cb2_view_periodoccurencemeta
					$postmeta_table = "{$wpdb->prefix}cb2_view_{$post_type_stub}meta";
					$wpdb->postmeta = $postmeta_table;
					if ( WP_DEBUG && FALSE )
						print( "<span class='cb2-WP_DEBUG-small'>[$Class::$post_type] =&gt; [$postmeta_table]</span>" );
				}
			} else if ( WP_DEBUG ) print( "<span class='cb2-WP_DEBUG-small'>[$Class::$post_type] no meta redirect</span>" );
		}

		return $redirected;
	}

	static function get_metadata_assign( &$post ) {
		// Switch base tables to our views
		// Load all associated metadata and assign to the post object
		global $auto_draft_publish_transition, $wpdb;

		if ( ! is_object( $post ) )
			throw new Exception( 'get_metadata_assign() post object required' );
		if ( ! property_exists( $post, 'ID' )        || ! $post->ID )
			throw new Exception( 'get_metadata_assign: $post->ID required' );
		if ( ! property_exists( $post, 'post_type' ) || ! $post->post_type )
			throw new Exception( 'get_metadata_assign: $post->post_type required' );

		// WordPress standard settings for the wp_postmeta
		// $wpdb->postmeta = wp_postmeta
		$meta_type       = 'post';
		$meta_table_stub = "{$meta_type}meta";
		$postmeta_table  = $meta_table_stub;

		$ID        = $post->ID;
		$post_type = $post->post_type;

		if ( ! property_exists( $post, 'GET_METADATA_ASSIGN' ) || ! $post->{GET_METADATA_ASSIGN} ) {
			if ( $Class = self::schema_type_class( $post_type ) ) {
				// If $auto_draft_publish_transition is happening
				// Then the primary $wpdb->postmeta table will be set to wp_postmeta
				// This happens when the post is still in the normal WP tables
				// not been moved yet to the native structures and views
				if ( ! $auto_draft_publish_transition ) {
					// postmeta_table() will return FALSE
					// if the Class does not have its own postmeta
					if ( $has_postmeta_table = CB_Database::postmeta_table( $Class, $meta_type, $meta_table_stub ) )
						$postmeta_table = $has_postmeta_table;
				}
				$wpdb->$meta_table_stub = "$wpdb->prefix$postmeta_table";
				if ( WP_DEBUG && FALSE )
					print( "<div class='cb2-WP_DEBUG-small'>using to \$wpdb->$meta_table_stub = {$wpdb->$meta_table_stub} for [$post_type/$meta_type/$auto_draft_publish_transition]</div>" );

				// get_metadata( $meta_type, ... )
				//   meta.php has _get_meta_table( $meta_type );
				//   $table_name = $meta_type . 'meta';
				// And remove pseudo meta like _edit_lock
				$metadata = get_metadata( $meta_type, $ID );
				foreach ( $metadata as $meta_key => &$meta_value_array ) {
					$is_system_metadata = ( substr( $meta_key, 0, 1 ) == '_' );
					if ( ! $is_system_metadata )
						$post->$meta_key = CB_Query::to_object( $meta_key, $meta_value_array[0] );
				}
				// Register that all metadata is present
				$post->{GET_METADATA_ASSIGN} = TRUE;

				if ( WP_DEBUG ) {
					// Check that some meta data is returned
					foreach ( $metadata as $meta_key => $meta_value_array ) {
						$is_system_metadata = ( substr( $meta_key, 0, 1 ) == '_' );
						if ( $is_system_metadata ) unset( $metadata[$meta_key] );
					}
					if ( ! count( $metadata ) && $meta_type != 'post' ) {
						krumo( $wpdb );
						throw new Exception( "[$post_type/$meta_type/$postmeta_table] [$ID/$auto_draft_publish_transition] returned no metadata" );
					}
					if ( CB2_DEBUG_SAVE && FALSE )
						krumo( $ID, $post_type, $post->post_status, $meta_type, $postmeta_table, $metadata );
				}
			} else throw new Exception( "Cannot get_metadata_assign() to [$post_type] not governed by CB2" );
		}

		//return $post; // Passed by reference, so no need to check result
  }

  // -------------------------------------------------------------------- Class, Function and parameter utilities
  // meta-data => objects
  static function ensure_bitarray_integer( $name, $object ) {
		$object = self::ensure_bitarray( $name, $object );
		return CB_Database::bitarray_to_int( $object );
  }

  static function ensure_assoc_bitarray( $name, $object ) {
		$object = self::ensure_bitarray( $name, $object );
		$assoc_array = array();
		foreach ( $object as $loc => $on ) {
			if ( $on ) array_push( $assoc_array, (string) pow( 2, $loc ) );
		}
		return $assoc_array;
  }

  static function ensure_bitarray( $name, $object ) {
		// This can also be an associative array:
		// Array(
		//   (string) '0' => 4,
		//   (string) '1' => 16
		// ) => 20

		if ( is_null( $object ) ) {
			$object = array();
		} else if ( is_array( $object ) ) {
			if ( self::array_has_associative( $object ) ) {
				$object = array_sum( $object );
				$object = CB_Database::int_to_bitarray( $object );
			}
		} else if ( self::check_for_serialisation( $object, 'a' ) ) {
			$object = unserialize( $object );
			if ( self::array_has_associative( $object ) ) {
				$object = array_sum( $object );
				$object = CB_Database::int_to_bitarray( $object );
			}
		} else if ( is_numeric( $object ) ) {
			$object = CB_Database::int_to_bitarray( $object );
		} else {
			krumo( $object );
			throw new Exception( "Cannot understand bit array value for [$name]" );
		}
		return $object;
  }

	static function ensure_datetime( $name, $object ) {
		// Maintains NULLs
		// empty = NULL
		$datetime     = NULL;
		$now          = new DateTime();
		$epoch_min    = 20000000; // Thursday, 20 August 1970

		if      ( is_null( $object ) )          $datetime = NULL;
		else if ( is_string( $object ) && empty( $object ) )
																						$datetime = NULL;
		else if ( $object === FALSE )           $datetime = NULL; // Happens when object->property fails
		else if ( $object instanceof DateTime ) $datetime = &$object;
		else if ( is_numeric( $object ) && $object > $epoch_min )
																						$datetime = $now->setTimestamp( (int) $object );
		else if ( is_string( $object ) )        $datetime = new DateTime( $object );
		else if ( is_array( $object ) && isset( $object['date'] ) ) {
			$string = trim( implode( ' ', $object ) );
			if ( $string ) $datetime = new DateTime( $string );
		} else {
			krumo( $object );
			throw new Exception( "[$name] has unhandled datetime type" );
		}

		return $datetime;
	}

	static function ensure_time( $name, $object ) {
		// TODO: ensure_time()
		return $object;
	}

	static function ensure_int( $name, $object, $allow_create_new = FALSE ) {
		$int = NULL;
		if ( ! is_null( $object ) ) {
			if      ( $allow_create_new && $object == CB2_CREATE_NEW ) {
				// Special value indicating that the ID object should be created
				$int = CB2_CREATE_NEW;
			}
			else if ( is_numeric( $object ) ) $int = (int) $object;
			else if ( is_string( $object ) && empty( $object ) ) $int = 0;
			else throw new Exception( "[$name] is not numeric [$object]" );
		}
		return $int;
	}

	static function ensure_boolean( $name, $object ) {
		return (bool) $object;
	}

	static function ensure_ints( $name, $object, $allow_create_new = FALSE ) {
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

	static function assign_all_parameters( $object, $parameter_values, $class_name = NULL, $method = '__construct' ) {
		// Take all function parameters
		// And make them properties of the class
		// Typical usage:
		//   CB_Query::assign_all_parameters( $this, func_get_args(), __class__ );
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
				if      ( $value instanceof DateTime ) print( $value->format( CB_Database::$database_datetime_format ) );
				else if ( is_object( $value ) ) krumo( $value );
				else if ( is_array(  $value ) ) krumo( $value );
				else print( $value );
				$new_value = self::to_object( $name, $value );
				if ( $new_value !== $value ) {
					print( ' =&gt; ' );
					if      ( $new_value instanceof DateTime ) print( 'new DateTime(' . $new_value->format( CB_Database::$database_datetime_format ) . ')' );
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

	static function to_object( $name, $value ) {
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
		// Used by:
		//   copy_all_wp_post_properties(): post->*         => object
		//   assign_all_parameters():       get_func_args() => object
		//   get_metadata_assign():         meta-data       => object
		//
		// It is (optionally) the job of the object to:
		// convert *_ID(s) => objects using
		//   get_or_create_new(ids)
		//
		// TODO: document the naming conventions of course
		// TODO: move all this in to the CB_DatabaseTable_PostNavigator Class
		// to use the database schema knowledge instead :)
		if ( ! is_null( $value ) ) {
			if      ( substr( $name, 0, 9 ) == 'datetime_' ) $value = self::ensure_datetime( $name, $value );
			else if ( $name == 'date' )                      $value = self::ensure_datetime( $name, $value );
			else if ( $name == 'enabled' )                   $value = self::ensure_boolean(  $name, $value );
			else if ( substr( $name, 0, 5 ) == 'time_' )     $value = self::ensure_time( $name, $value );
			else if ( substr( $name, -9 ) == '_sequence' )   $value = self::ensure_int(  $name, $value );
			else if ( substr( $name, -3 ) == '_id' )         $value = self::ensure_int(  $name, $value, CB2_ALLOW_CREATE_NEW  );
			else if ( substr( $name, -4 ) == '_ids' )        $value = self::ensure_ints( $name, $value, CB2_ALLOW_CREATE_NEW );
			else if ( substr( $name, -3 ) == '_ID' )         $value = self::ensure_int(  $name, $value, CB2_ALLOW_CREATE_NEW );
			else if ( substr( $name, -4 ) == '_IDs' )        $value = self::ensure_ints( $name, $value, CB2_ALLOW_CREATE_NEW );
			else if ( substr( $name, -6 ) == '_index' )      $value = self::ensure_int(  $name, $value );
			else if ( $name == 'ID' )                        $value = self::ensure_int(  $name, $value, CB2_ALLOW_CREATE_NEW );
			else if ( $name == 'id' )                        $value = self::ensure_int(  $name, $value, CB2_ALLOW_CREATE_NEW );

			if ( self::check_for_serialisation( $value ) )
				throw new Exception( "[$value] looks like serialised. This happens because we get_metadata() with SINGLE when WordPress serialises arrays in the meta_value field" );
		}

		return $value;
	}

  // ---------------------------------------------- General utilities
  static function substring_before( $string, $delimiter = '-' ) {
		return ( strpos( $string, $delimiter ) === FALSE ? $string : substr( $string, 0, strpos( $string, $delimiter ) ) );
	}

  static function substring_after( $string, $delimiter = '-' ) {
		return ( strrpos( $string, $delimiter ) === FALSE ? $string : substr( $string, strrpos( $string, $delimiter ) + 1 ) );
	}

	static function check_for_serialisation( $object, $type = 'a-z' ) {
		return ( is_string( $object ) && preg_match( "/^[$type]:[0-9]+:/", $object ) );
	}

	static function array_has_associative( $array ) {
		return array_keys($arr) !== range(0, count($arr) - 1);
	}
}
