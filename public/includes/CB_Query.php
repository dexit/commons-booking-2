<?php
require_once( 'CB_Database.php' );
require_once( 'CB_PostNavigator.php' );
require_once( 'CB_PeriodItem.php' );
require_once( 'CB_Entities.php' );
require_once( 'CB_PeriodEntity.php' );
require_once( 'the_template_functions.php' );
require_once( 'CB_Time_Classes.php' );
require_once( 'WP_Query_integration.php' );
require_once( 'CB_Forms.php' );

// System PERIOD_STATUS_TYPEs
define( 'PERIOD_STATUS_TYPE_AVAILABLE', 1 );
define( 'PERIOD_STATUS_TYPE_BOOKED', 2 );
define( 'PERIOD_STATUS_TYPE_CLOSED', 3 );
define( 'PERIOD_STATUS_TYPE_OPEN', 4 );
define( 'PERIOD_STATUS_TYPE_REPAIR', 5 );

class CB_Query {
	private static $schema_types = array();

  public  static $javascript_date_format = 'Y-m-d H:i:s';
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
	static function get_post_type( $post_type, $post_id = NULL, $output = OBJECT, $filter = 'raw' ) {
		// get_post() with table switch
		// This will use standard WP cacheing
		global $wpdb;
		$old_wpdb_posts = NULL;

		if ( $post_type == 'user' ) throw new Exception( 'Use CB_Query::get_user() for users.' );

		$wpdb->posts = "{$wpdb->prefix}posts";

		if ( $Class = self::schema_type_class($post_type) ) {
			if ( $posts_table = CB_Database::posts_table( $Class ) ) {
				$old_wpdb_posts = $wpdb->posts;
				$wpdb->posts    = "$wpdb->prefix$posts_table";
			}
		}

		$post = get_post( $post_id, $output, $filter );

		if ( $Class ) {
			if ( is_null( $post ) )
				throw new Exception( "[$Class/$post_type] not found in [$wpdb->prefix] [$wpdb->posts] for [$post_id]" );
			if ( $old_wpdb_posts )
				$wpdb->posts = $old_wpdb_posts;
			if ( method_exists( $Class, 'factory_from_wp_post' ) )
				$post = $Class::factory_from_wp_post( $post );
		}

		return $post;
	}

	static function get_user( $ID ) {
		return get_user_by( 'ID', $ID );
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
			if ( method_exists( $Class, 'factory_from_wp_post' ) ) {
				$record = $Class::factory_from_wp_post( $record );
				if ( is_null( $record ) ) throw new Exception( "Failed to create [$Class] class from post" );
			}
		}

		return $record;
	}

	static function get_post_types() {
		global $wpdb;
		$post_types = wp_cache_get( 'cb2-post-types' );
		if ( ! $post_types ) {
			$post_types = $wpdb->get_results( "SELECT post_type, ID_multiplier, ID_Base FROM {$wpdb->prefix}cb2_post_types ORDER BY ID_base DESC", OBJECT_K );
			wp_cache_set( 'cb2-post-types', $post_types );
		}
		return $post_types;
	}



	static function post_type_from_ID( $ID ) {
		$post_types = self::get_post_types();
		$post_type  = NULL;

		foreach ( $post_types as $post_type_check => $details ) {
			$post_type_ID_base = $details->ID_Base;
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
			&& in_array( $post->post_status, array( 'draft',  'auto-draft' ) );
	}

	static function ID_from_id_post_type( $id, $post_type ) {
		// NULL return indicates that this post_type is not governed by CB2
		$ID         = NULL;
		$post_types = self::get_post_types();
		if ( isset( $post_types[$post_type] ) ) {
			$details = $post_types[$post_type];
			$ID      = $id * $details->ID_multiplier + $details->ID_Base;
		}

		return $ID;
	}

	static function id_from_ID_with_post_type( $ID, $post_type ) {
		// NULL return indicates that this post_type is not governed by CB2
		$id         = NULL;
		$post_types = self::get_post_types();
		if ( isset( $post_types[$post_type] ) ) {
			$details = $post_types[$post_type];
			$id      = ( $ID - $details->ID_Base ) / $details->ID_multiplier;
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

	static function get_metadata_assign( $post ) {
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
					$metadata = get_metadata( $meta_type, $ID );
					if ( ! is_array( $metadata ) || ! count( $metadata ) )
						throw new Exception( "[$post_type] [$ID] returned no metadata" );

					// Populate object
					foreach ( $metadata as $this_meta_key => $meta_value )
						$post->$this_meta_key = $meta_value[0];
					$post->_get_metadata_assign = TRUE;
				} else throw new Exception( "Cannot get_metadata_assign() to [$post_type] not governed by CB2" );
			}
		} else throw new Exception( 'get_metadata_assign() post required' );
  }

  // -------------------------------------------------------------------- Class, Function and parameter utilities
  static function ensure_bitarray( $object ) {
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

	static function ensure_datetime( $object ) {
		// Maintains NULLs
		$datetime = NULL;

		if      ( $object instanceof DateTime ) $datetime = &$object;
		else if ( is_string( $object ) )        $datetime = new DateTime( $object );
		else if ( is_null( $object ) )          $datetime = NULL;
		else throw new Exception( 'unhandled datetime type' );

		return $datetime;
	}

	static function ensure_time( $object ) {
		// TODO: ensure_time()
		return $object;
	}

	static function assign_all_parameters( $object, $parameter_values, $class_name = NULL, $method = '__construct' ) {
		// Take all function parameters
		// And make them properties of the class
		// Typical usage:
		//   CB_Query::assign_all_parameters( $this, func_get_args(), __class__ );
		if ( ! $class_name ) $class_name = get_class( $object );
		$reflection            = new ReflectionMethod( $class_name, $method ); // PHP 5, PHP 7
		$parameters            = $reflection->getParameters();
		$parameter_value_count = count( $parameter_values );
		foreach ( $parameters as $i => &$parameter ) {
			$name  = $parameter->name;
			$value = ( $i >= $parameter_value_count ? $parameter->getDefaultValue() : $parameter_values[$i] );
			$value = self::cast_parameter( $name, $value );

			$object->$name = $value;
			if ( WP_DEBUG && FALSE ) {
				print( "<i>$class_name->$name</i>: <b>" );
				if      ( $value instanceof DateTime ) print( $value->format( 'c' ) );
				else if ( is_object( $value ) ) var_dump( $value );
				else if ( is_array(  $value ) ) var_dump( $value );
				else print( $value );
				print( '</b><br/>' );
			}
		}
	}

	static function copy_all_properties( $from, $to, $overwrite = TRUE ) {
		foreach ( $from as $name => $value ) {
			if ( $overwrite || ! property_exists( $to, $name ) ) {
				$to->$name = self::cast_parameter( $name, $value );
			}
		}
	}

	static function cast_parameter( $name, $value ) {
		if ( ! is_null( $value ) ) {
			if      ( substr( $name, 0, 9 ) == 'datetime_' ) $value = self::ensure_datetime( $value );
			else if ( $name == 'date' )                      $value = self::ensure_datetime( $value );
			else if ( substr( $name, 0, 5 ) == 'time_' )     $value = $value;
			else if ( substr( $name, -3 ) == '_id' )         $value = (int) $value;
			else if ( substr( $name, -6 ) == '_index' )      $value = (int) $value;
			else if ( substr( $name, -9 ) == '_sequence' )   $value = self::ensure_bitarray( $value );
			else if ( $name == 'ID' )                        $value = (int) $value;
		}

		return $value;
	}

	static function sanitize_data_for_table( $table, $data ) {
		$new_data   = array();
		$columns    = CB_Database::columns( $table, TRUE );

		foreach ( $data as $field_name => $field_value ) {
			// Meta data queries use arrays
			if ( is_array( $field_value ) ) $field_value = $field_value[0];

			if ( ! isset( $columns[$field_name] ) ) {
				// Standard mappings
				switch ( $field_name ) {
					case 'post_title':   $field_name = 'name';        break;
					case 'post_content': $field_name = 'description'; break;
				}

				// ID => id mappings
				if ( substr( $field_name, -3 ) == '_ID' ) {
					$field_stub  = substr( $field_name, 0, -3 );
					$post_type   = str_replace( '_', '', $field_stub );
					$field_name  = "{$field_stub}_id";
					$field_value = CB_Query::id_from_ID_with_post_type( $field_value, $post_type );
				}

				// period_group => (object) period_group->period_group_id
				if ( is_object( $field_value ) && property_exists( $field_value, "{$field_name}_id" ) ) {
					$field_name  = "{$field_name}_id";
					$field_value = $field_value->$field_name;
				}
			}

			// Check table
			if ( isset( $columns[$field_name] ) ) {
				// Data conversion
				$column_definition = $columns[$field_name];
				switch ( self::substring_before( $column_definition->Type, '(' ) ) {
					case 'bit':
						$field_value = CB_Database::int_to_bitstring( $field_value );
						break;
				}
				$new_data[ $field_name ] = $field_value;
			}
		}

		return $new_data;
	}
}
