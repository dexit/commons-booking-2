<?php
// WP_DEBUG setup
if ( WP_DEBUG ) include( 'krumo/class.krumo.php' );
if ( ! function_exists( 'krumo' ) ) {
	function krumo( ...$params ) {if ( WP_DEBUG ) var_dump( $params );}
}
define( 'CB2_DEBUG_SAVE', WP_DEBUG && FALSE );

// System PERIOD_STATUS_TYPEs
// a database trigger prevents deletion of these
define( 'CB2_PERIOD_STATUS_TYPE_AVAILABLE', 1 );
define( 'CB2_PERIOD_STATUS_TYPE_BOOKED',    2 );
define( 'CB2_PERIOD_STATUS_TYPE_CLOSED',    3 ); // For overriding CB2_PERIOD_STATUS_TYPE_OPEN
define( 'CB2_PERIOD_STATUS_TYPE_OPEN',      4 );
define( 'CB2_PERIOD_STATUS_TYPE_REPAIR',    5 );
define( 'CB2_PERIOD_STATUS_TYPE_HOLIDAY',   6 );

// Native post create process
// TODO: Remove dependency on -- create new -- text
define( 'CB2_CREATE_NEW',       '-- create new --' );
define( 'CB2_ALLOW_CREATE_NEW', TRUE ); // Allows CB2_CREATE_NEW to be passed as a numeric ID
define( 'CB2_PUBLISH',          'publish' );
define( 'CB2_AUTODRAFT',        'auto-draft' );
define( 'CB2_POST_PROPERTIES',  array(
	'ID' => FALSE,
	'post_author' => TRUE,     // TRUE == Relevant to native records
	'post_date' => TRUE,
	'post_date_gmt' => FALSE,
	'post_content' => TRUE,
	'post_title' => TRUE,
	'post_excerpt' => TRUE,
	'post_status' => FALSE,
	'comment_status' => FALSE,
	'ping_status' => FALSE,
	'post_password' => FALSE,
	'post_name' => TRUE,
	'to_ping' => FALSE,
	'pinged' => FALSE,
	'post_modified' => TRUE,
	'post_modified_gmt' => FALSE,
	'post_content_filtered' => TRUE,
	'post_parent' => FALSE,
	'guid' => FALSE,
	'menu_order' => FALSE,
	'post_type' => TRUE,
	'post_mime_type' => FALSE,
	'comment_count' => FALSE,
	'filter' => FALSE,
) );

require_once( 'CB_Database.php' );
require_once( 'CB_PostNavigator.php' );
require_once( 'CB_PeriodItem.php' );
require_once( 'CB_Entities.php' );
require_once( 'CB_PeriodEntity.php' );
require_once( 'the_template_functions.php' );
require_once( 'CB_Time_Classes.php' );
require_once( 'WP_Query_integration.php' );
require_once( 'CB_Forms.php' );

class CB_Query {
	private static $schema_types = array();

  public  static $javascript_date_format = 'Y-m-d H:i:s';
  public  static $date_format = 'Y-m-d';
  public  static $datetime_format = 'Y-m-d H:i:s';
  public  static $meta_NULL = 'NULL';
  public  static $without_meta = array(
		'key'     => 'item_ID',
		'value'   => 'NOT_USED',
		'compare' => 'NOT EXISTS',
	);

  // -------------------------------------------------------------------- Reflection
  // post_type to Class lookups
  static function register_schema_type( $Class ) {
		if ( ! property_exists( $Class, 'static_post_type' ) )
			throw new Exception( "[$Class] requires a static static_post_type" );
		if ( strlen( $Class::$static_post_type ) > 20 )
			throw new Exception( 'post_type [' . $Class::$static_post_type . '] is longer than the WordPress maximum of 20 characters' );

		self::$schema_types[ $Class::$static_post_type ] = $Class;
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
  // With CB Object understanding
	static function get_post_with_type( $post_type, $post_id, $output = OBJECT, $filter = 'raw' ) {
		// get_post() with table switch
		// This will use standard WP cacheing
		global $wpdb;

		if ( ! is_string( $post_type ) )
			throw new Exception( "get_post_with_type() \$post_type not a string" );
		if ( ! is_numeric( $post_id ) )
			throw new Exception( "get_post_with_type()[$post_type] \$post_id not numeric" );
		if ( $post_type == 'user' )
			throw new Exception( 'Use CB_Query::get_user() for CB_User.' );

		$Class = self::schema_type_class( $post_type );
		if ( ! $Class )
			throw new Exception( "[$post_type] not managed by CB2" );

		// Redirect
		$old_wpdb_posts = NULL;
		$wpdb->posts = "{$wpdb->prefix}posts";
		if ( $posts_table = CB_Database::posts_table( $Class ) ) {
			$old_wpdb_posts = $wpdb->posts;
			$wpdb->posts    = "$wpdb->prefix$posts_table";
		}
		if ( CB2_DEBUG_SAVE && TRUE && $wpdb->posts == "{$wpdb->prefix}posts" )
			print( "Notice: [$Class] get_post_with_type() using wp_posts table" );

		// WP_Post::get_instance() will check the cache
		// TODO: Can we intelligently wp_cache_delete() instead?
		wp_cache_delete( $post_id, 'posts' );

		$post = get_post( $post_id, $output, $filter );
		if ( is_null( $post ) )
			throw new Exception( "[$Class/$post_type] not found in [$wpdb->prefix] [$wpdb->posts] for [$post_id]" );
		if ( $post->post_type != $post_type )
			throw new Exception( "[$Class/$post_id] fetched a [$post->post_type] post_type from [$posts_table], not a [$post_type]" );

		// Reset
		if ( $old_wpdb_posts ) $wpdb->posts = $old_wpdb_posts;
		$post = self::ensure_correct_class( $post );

		return $post;
	}

	static function get_user( $ID ) {
		if ( ! $ID )      throw new Exception( "User [$ID] blank" );
		$wp_user = get_user_by( 'ID', $ID );
		if ( ! $wp_user ) throw new Exception( "User [$ID] not found" );
		return CB_User::factory( $wp_user->ID, $wp_user->user_login );
	}

	static function ensure_correct_classes( &$records ) {
		// TODO: move static <Time class>::$all arrays on to the CB_Query
		// so that several embedded queries can co-exist
		// Currently, several queries happen in the page load (counts and things)
		// which gives wrong results unless cleared each time:
		CB_Week::$all = array();

		// In place change the records
		$post_classes = self::schema_types();
		foreach ( $records as &$record )
			$record = CB_Query::ensure_correct_class( $record, $post_classes );

		return $records;
  }

	static function ensure_correct_class( &$record, $post_classes = NULL ) {
    // Creation will aslo create the extra time based data structure
		if ( ! $post_classes ) $post_classes = self::schema_types();

		if ( property_exists( $record, 'post_type' ) && isset( $post_classes[$record->post_type] ) ) {
			$Class = $post_classes[$record->post_type];
			// Do not re-create it if it already is!
			if ( ! is_a( $record, $Class ) ) {
				if ( method_exists( $Class, 'factory_from_wp_post' ) ) {
					$record = $Class::factory_from_wp_post( $record );
					if ( is_null( $record ) )
						throw new Exception( "Failed to create [$Class] class from post" );
				}
			}
		}

		return $record;
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



	static function post_type_from_ID( $ID ) {
		$post_types = self::get_post_types();
		$post_type  = NULL;

		foreach ( $post_types as $post_type_check => $details ) {
			$post_type_ID_base = $details->ID_base;
			if ( $post_type_ID_base <= $ID ) {
				$post_type = $post_type_check;
				if ( WP_DEBUG && FALSE ) print( " <i>$ID =&gt; $post_type</i> " );
				break;
			}
		}
		return $post_type;
	}

	static function is_custom_post_type_ID( $ID ) {
		return self::post_type_from_ID( $ID );
	}

	static function is_wp_post_ID( $ID ) {
		return ! self::is_custom_post_type_ID( $ID );
	}

	static function is_wp_auto_draft( $post ) {
		return $post && property_exists( $post, 'ID' )
			&& in_array( $post->post_status, array( 'draft',  CB2_AUTODRAFT ) );
	}

	static function publishing_post( $post ) {
		// Indicates that we need to move the post in to our native structures
		//   post_status = publish
		//   ID          = wp_posts id (NOT the eventual created native_ID)
		// all metadata is saved before the save_post action
		return property_exists( $post, 'ID' )
			&& ! CB_Query::is_wp_auto_draft( $post )
			&& CB_Query::is_wp_post_ID( $post->ID );
	}

	static function ID_from_id_post_type( $id, $post_type ) {
		// NULL return indicates that this post_type is not governed by CB2
		$ID         = NULL;
		$post_types = self::get_post_types();

		if ( ! is_numeric( $id ) ) throw new Exception( "Numeric ID required for id_from_ID_with_post_type($id/$post_type)" );
		if ( ! $post_type )        throw new Exception( "Post type required for id_from_ID_with_post_type($id)" );

		if ( isset( $post_types[$post_type] ) ) {
			$details = $post_types[$post_type];
			if ( $id >= $details->ID_base ) throw new Exception( "[$post_type/$id] is already more than its ID_base [$details->ID_base]" );
			$ID      = $id * $details->ID_multiplier + $details->ID_base;
		}

		return $ID;
	}

	static function id_from_ID_with_post_type( $ID, $post_type ) {
		// NULL return indicates that this post_type is not governed by CB2
		$id         = NULL;
		$post_types = self::get_post_types();

		if ( ! is_numeric( $ID ) ) throw new Exception( "Numeric ID required for id_from_ID_with_post_type($ID/$post_type)" );
		if ( ! $post_type )        throw new Exception( "Post type required for id_from_ID_with_post_type($ID)" );

		if ( isset( $post_types[$post_type] ) ) {
			$details = $post_types[$post_type];
			if ( $details->ID_base > $ID ) throw new Exception( "Negative id from ID [$ID/$post_type] with [$details->ID_base/$details->ID_multiplier]" );
			$id      = ( $ID - $details->ID_base ) / $details->ID_multiplier;
		}

		return $id;
	}

	static function class_from_SELECT( $query ) {
		// Checks to see if the SQL is attempting to access
		// one of our post_types or post IDs
		// TODO: proper char by char decompilation is needed here in CB_Database
		$Class       = FALSE;
		$type        = 'SELECT';
		$query       = trim( $query );
		$query_type  = strtoupper( preg_replace( '/\\s.*/im', '', $query ) );
		$post_type   = NULL;

		if ( $query_type == $type ) {
			// = large ID
			preg_match( '/ID`?\s+=\s+(\d+)/im', $query, $ids );
			if ( is_array( $ids ) && count( $ids ) )
				$post_type = self::post_type_from_ID( $ids[1] );

			// IN( large ids )
			preg_match( '/ID`?\s+IN\s*\(\s*(\d+)/im', $query, $ids );
			if ( is_array( $ids ) && count( $ids ) )
				$post_type = self::post_type_from_ID( $ids[1] );

			// .post_type = '...'
			preg_match( '/\.post_type`?\s*=\s*\'([a-z0-9_]+)\'/im', $query, $post_types );
			if ( is_array( $post_types ) && count( $post_types ) )
				$post_type = $post_types[1];

			if ( $post_type ) $Class = self::schema_type_class( $post_type );
		}

		return $Class;
	}

	static function template_loader_context() {
		// Copied and altered from template-loader.php
		$context = FALSE;
		if     ( is_embed()           ) $context = 'embed';
		elseif ( is_404()             ) $context = '404';
		elseif ( is_search()          ) $context = 'search';
		elseif ( is_front_page()      ) $context = 'front_page';
		elseif ( is_home()            ) $context = 'home';
		elseif ( is_post_type_archive()  ) $context = 'post_type_archive';
		elseif ( is_tax()             ) $context = 'taxonomy';
		elseif ( is_attachment()      ) $context = 'attachment';
		elseif ( is_single()          ) $context = 'single';
		elseif ( is_page()            ) $context = 'page';
		elseif ( is_singular()        ) $context = 'singular';
		elseif ( is_category()        ) $context = 'category';
		elseif ( is_tag()             ) $context = 'tag';
		elseif ( is_author()          ) $context = 'author';
		elseif ( is_date()            ) $context = 'date';
		elseif ( is_archive()         ) $context = 'archive';

		// Extra: set $wp_query->is_list = TRUE to activate this
		if ( is_list() ) $context = 'list';

		return $context;
	}

	static function get_metadata_assign( &$post ) {
		// Switch base tables to our views
		// Load all associated metadata and assign to the post object
		global $wpdb;
		$meta_type = 'post';

		if ( is_object( $post ) ) {
			if ( ! $post->ID )        throw new Exception( 'get_metadata_assign: $post->ID required' );
			if ( ! $post->post_type ) throw new Exception( 'get_metadata_assign: $post->post_type required' );

			$ID        = $post->ID;
			$post_type = $post->post_type;
			if ( ! property_exists( $post, '_get_metadata_assign' ) ) {
				if ( $Class = self::schema_type_class( $post_type ) ) {
					// NOTE: if the sent $ID is a wp_posts id
					// Then the postmeta_table will be set to wp_postmeta
					// This happens when the post is still in the normal WP tables
					// not been moved yet to the native structures and views
					if ( $postmeta_table = CB_Database::postmeta_table( $Class, $meta_type, $meta_table_stub, $ID ) )
						$wpdb->$meta_table_stub = "$wpdb->prefix$postmeta_table";

					// get_metadata( $meta_type, ... )
					//   meta.php has _get_meta_table( $meta_type );
					//   $table_name = $meta_type . 'meta';
					// And remove pseudo meta like _edit_lock
					$metadata = get_metadata( $meta_type, $ID );
					foreach ( $metadata as $name => $value )
						if ( substr( $name, 0, 1 ) == '_' ) unset( $metadata[$name] );

					// Check that some meta data is returned
					if ( ! CB_Query::is_wp_auto_draft( $post ) ) {
						if ( ! is_array( $metadata ) || ! count( $metadata ) )
							throw new Exception( "[$post_type/$meta_type] [$ID] returned no metadata" );
					}
					if ( CB2_DEBUG_SAVE && FALSE )
						krumo( $ID, $post_type, $post->post_status, $meta_type, $postmeta_table, $metadata );

					// Populate object
					foreach ( $metadata as $this_meta_key => $meta_value )
						$post->$this_meta_key = $meta_value[0];
					$post->_get_metadata_assign = TRUE;
				} else throw new Exception( "Cannot get_metadata_assign() to [$post_type] not governed by CB2" );
			}
		} else throw new Exception( 'get_metadata_assign() post required' );

		//return $post; // Passed by reference, so no need to check result
  }

  // -------------------------------------------------------------------- Class, Function and parameter utilities
  static function ensure_bitarray( $name, $object ) {
		if ( is_array( $object ) )
			$object = CB_Database::bitarray_to_int( $object );
		return $object;
  }

  static function substring_before( $string, $delimiter = '-' ) {
		return ( strpos( $string, $delimiter ) === FALSE ? $string : substr( $string, 0, strpos( $string, $delimiter ) ) );
	}

  static function substring_after( $string, $delimiter = '-' ) {
		return ( strrpos( $string, $delimiter ) === FALSE ? $string : substr( $string, strrpos( $string, $delimiter ) + 1 ) );
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
		else {
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
			else throw new Exception( "[$name] is not numeric [$object]" );
		}
		return $int;
	}

	static function ensure_ints( $name, $object, $allow_create_new = FALSE ) {
		$array = array();
		if ( ! is_null( $object ) ) {
			if ( is_string( $object ) && preg_match( '/^a:\\d+:\\{/', $object ) ) {
				$array_object = unserialize( $object );
				if ( $array_object !== FALSE ) $object = $array_object;
			}

			if ( is_array( $object ) ) {
				$array = $object;
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
			else if ( is_string( $object ) && empty( $object ) ) {
				$array = array();
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
			try {
				$value = self::cast_parameter( $name, $value );
			} catch ( Exception $ex ) {
				krumo( $parameters, $parameter_values );
				throw $ex;
			}

			$object->$name = $value;

			if ( WP_DEBUG && FALSE ) {
				print( "<i>$class_name->$name</i>: <b>" );
				if      ( $value instanceof DateTime ) print( $value->format( 'c' ) );
				else if ( is_object( $value ) ) krumo( $value );
				else if ( is_array(  $value ) ) krumo( $value );
				else print( $value );
				print( '</b><br/>' );
			}
		}
	}

	static function copy_all_wp_post_properties( $post, $object, $overwrite = TRUE ) {
		// Important to overwrite
		// because these objects are CACHED
		if ( is_null( $post ) )       throw new Exception( 'copy_all_wp_post_properties( $post null )' );
		if ( is_array( $post ) )      throw new Exception( 'copy_all_wp_post_properties( $post is an array )' );
		if ( ! is_object( $post ) )   throw new Exception( 'copy_all_wp_post_properties( $post not an object )' );
		if ( ! is_object( $object ) ) throw new Exception( 'copy_all_wp_post_properties( $object not an object )' );

		if ( WP_DEBUG ) {
			foreach ( CB2_POST_PROPERTIES as $name => $native_relevant )
				if ( ! property_exists( $post, $name ) )
					throw new Exception( "WP_Post->[$name] does not exist on source post" );
		}

		foreach ( $post as $name => $from_value ) {
			$wp_is_post_property = isset( CB2_POST_PROPERTIES[$name] );
			if ( $wp_is_post_property ) {
				try {
					$new_value = self::cast_parameter( $name, $from_value );
				} catch ( Exception $ex ) {
					krumo( $post );
					throw $ex;
				}

				if ( $overwrite || ! property_exists( $object, $name ) ) {
					if ( WP_DEBUG && FALSE ) {
						if ( property_exists( $object, $name ) ) {
							$old_value = $object->$name;
							if ( ! is_null( $old_value ) && $old_value != $new_value )
								print( "copy_all_wp_post_properties( [$old_value] => [$new_value] )" );
						}
					}
					$object->$name = $new_value;
				}
			}
		}
	}

	static function cast_parameter( $name, $value ) {
		if ( ! is_null( $value ) ) {
			if      ( substr( $name, 0, 9 ) == 'datetime_' ) $value = self::ensure_datetime( $name, $value );
			else if ( $name == 'date' )                      $value = self::ensure_datetime( $name, $value );
			else if ( substr( $name, 0, 5 ) == 'time_' )     $value = self::ensure_time( $name, $value );
			else if ( substr( $name, -9 ) == '_sequence' )   $value = self::ensure_bitarray( $name, $value );
			else if ( substr( $name, -3 ) == '_id' )         $value = self::ensure_int(  $name, $value );
			else if ( substr( $name, -4 ) == '_ids' )        $value = self::ensure_ints( $name, $value, CB2_ALLOW_CREATE_NEW );
			else if ( substr( $name, -3 ) == '_ID' )         $value = self::ensure_int(  $name, $value, CB2_ALLOW_CREATE_NEW );
			else if ( substr( $name, -4 ) == '_IDs' )        $value = self::ensure_ints( $name, $value, CB2_ALLOW_CREATE_NEW );
			else if ( substr( $name, -6 ) == '_index' )      $value = self::ensure_int(  $name, $value );
			else if ( $name == 'ID' )                        $value = self::ensure_int(  $name, $value, CB2_ALLOW_CREATE_NEW );
			else if ( $name == 'id' )                        $value = self::ensure_int(  $name, $value, CB2_ALLOW_CREATE_NEW );
		}

		return $value;
	}
}
