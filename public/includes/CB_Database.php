<?php
// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_Database {
  static $database_date_format = 'Y-m-d';
  static $database_datetime_format = 'Y-m-d H:i:s';
  static $NULL_indicator = '__Null__';

  protected function __construct( $table = NULL, $alias = NULL ) {
    if ( $table ) $this->set_table( $table, $alias );
  }

  function set_table( $name, $table_alias = NULL ) {
    $this->table = ( $table_alias ? "$name $table_alias" : $name );
  }

  function prepare( $arg1 = NULL, $arg2 = NULL, $arg3 = NULL, $arg4 = NULL, $arg5 = NULL, $arg6 = NULL ) {
		return NULL;
  }

  // -------------------------------- Utilities
  static function bitarray_to_int( $bit_array ) {
    $int = NULL;
    if ( is_array( $bit_array ) ) {
      $int = 0;
      foreach ( $bit_array as $loc => $on ) {
        if ( $on ) $int += pow( 2, $loc );
      }
    } else throw new Exception( 'bit_array is not an array' );
    return $int;
  }

  static function bitarray_to_bitstring( $bit_array, $offset = 1 ) {
		// array(0,1,1) => b'011'
    $str = NULL;
    if ( is_array( $bit_array ) ) {
      $str = '000000';
      $strlen = strlen($str);
      foreach ( $bit_array as $loc ) {
        if ( $loc - $offset < $strlen )
          $str[$loc - $offset] = '1';
      }
      $str = "b'$str'";
    } else throw new Exception( 'bit_array is not an array' );
    return $str;
  }

  static function int_to_bitstring( $int ) {
		// 6 => b'011'
		$bitarray  = self::int_to_bitarray( $int );
		$bitstring = "b'" . implode( '', $bitarray ) . "'";
		return $bitstring;
  }

  static function int_to_bitarray( $int ) {
		// 6 => array(0,1,1)
		$binary   = decbin( $int );
		$bitarray = str_split( strrev($binary ) );
		return $bitarray;
  }

  // ------------------------------------------------------- Reflection
  static function has_table( $table ) {
		return in_array( $table, self::tables() );
  }

  static function has_procedure( $procedure ) {
		return in_array( $procedure, self::procedures() );
  }

  static function has_function( $function ) {
		return in_array( $function, self::functions() );
  }

  static function has_column( $table, $column ) {
		return in_array( $column, self::columns( $table ) );
  }

  static function tables() {
		// TODO: not portable. build this from the installation knowledge
		global $wpdb;
		$tables = $wpdb->get_col( "show tables", 0 );
		foreach ( $tables as &$table ) $table = preg_replace( "/^$wpdb->prefix/", '', $table );
		return $tables;
  }

	static function columns( $table, $full_details = FALSE ) {
		// TODO: not portable. build this from the installation knowledge
		global $wpdb;
		// TODO: cacheing!!!!
		$desc_sql = "DESC $wpdb->prefix$table";
		return $full_details ? $wpdb->get_results( $desc_sql, OBJECT_K ) : $wpdb->get_col( $desc_sql, 0 );
  }

  static function procedures() {
		// TODO: not portable. build this from the installation knowledge
		global $wpdb;
		return $wpdb->get_col( 'show procedure status', 1 );
  }

  static function functions() {
		// TODO: not portable. build this from the installation knowledge
		global $wpdb;
		return $wpdb->get_col( 'show function status', 0 );
  }

	static function sanitize_data_for_table( $table, $data, &$formats = array(), $update = FALSE ) {
		// https://developer.wordpress.org/reference/classes/wpdb/insert/
		// A format is one of '%d', '%f', '%s' (integer, float, string).
		// If omitted, all values in $data will be treated as strings...
		$new_data   = array();
		$columns    = self::columns( $table, TRUE );
		if ( CB2_DEBUG_SAVE ) krumo( $columns );

		foreach ( $columns as $column_name => $column_definition ) {
			// Look through the data for a value for this column
			$data_value_array = NULL;

			// Direct value with same name
			if ( isset( $data[$column_name] ) ) {
				$data_value_array = $data[$column_name];
			}

			// Standard mappings
			if ( is_null( $data_value_array ) ) {
				switch ( $column_name ) {
					case 'name':
						if ( isset( $data['post_title'] ) )   $data_value_array = $data['post_title'];
						break;
					case 'description':
						if ( isset( $data['post_content'] ) ) $data_value_array = $data['post_content'];
						break;
				}
			}

			// object ID fields
			if ( is_null( $data_value_array ) ) {
				$object_column_name = preg_replace( '/_IDs?$|_ids?$/', '', $column_name);
				if ( isset( $data[$object_column_name]) ) $data_value_array = $data[$object_column_name];
			}

			// Normalise input from meta-data arrays to objects
			if ( ! is_array( $data_value_array ) )
				$data_value_array = array( $data_value_array );

			// Allow the Database to set DEFAULT values by ignoring empty info
			// A special string of Null will force a Null in the field
			$is_single_empty = (
					 ( count( $data_value_array ) == 0 )
				|| ( count( $data_value_array ) == 1 && is_null( $data_value_array[0] ) )
				|| ( count( $data_value_array ) == 1 && is_string( $data_value_array[0] ) && empty( $data_value_array[0] ) )
			);
			if ( $is_single_empty ) {
				// Is this mySQL specific?
				$field_required = ( $column_definition->Null == 'NO'
					&& is_null( $column_definition->Default )
					&& $column_definition->Extra != 'auto_increment'
				);
				if ( $field_required && ! $update ) {
					global $extra_processing_properties;
					krumo( $data, $extra_processing_properties );
					throw new Exception( "[$table::$column_name] is required" );
				}
			} else {
				$data_value = self::convert_for_field( $column_definition, $data_value_array, $format );

				$new_data[ $column_name ] = $data_value;
				$formats[  $column_name ] = $format;
			}
		}

		return $new_data;
	}

	static function convert_for_field( $column_definition, $data_value_array, &$format ) {
		// Data conversion
		$format           = '%s';
		$data_value       = NULL;
		$column_name      = $column_definition->Field;
		$column_data_type = strtolower( CB_Query::substring_before( $column_definition->Type, '(' ) );

		switch ( $column_data_type ) {
			// TODO: types not portable? build this from the installation knowledge
			case 'bit':
				// int works on input:
				// 6 will successfully set bits 2 (2) and 3 (4)
				// b'01010' bit syntax is tricky because WordPress does not provide a format for it
				if ( count( $data_value_array ) > 1 )
					throw new Exception( 'Multiple number array detected: bit arrays are set with a single unsigned' );
				foreach ( $data_value_array as &$value ) {
					if ( is_string( $value ) && $value == 'on' ) $value = 1;
					if ( is_bool( $value ) ) $value = (int) $value;
					if ( ! is_numeric( $value ) ) {
						krumo( $value );
						throw new Exception( 'Non-numeric value for bit field' );
					}
				}
				$data_value = CB_Query::ensure_int( $column_name, $data_value_array[0] );
				$format     = "%d";
				break;
			case 'tinyint':
			case 'int':
			case 'bigint':
				foreach ( $data_value_array as &$value ) {
					if ( is_object( $value ) ) {
						if ( method_exists( $value, '__toIntFor' ) ) {
							// PHP only supports __toString() magic method
							$value = $value->__toIntFor( $column_data_type, $column_name );
						} else throw new Exception( '[' . get_class( $value ) . '] must implement __toInt()' );
						if ( is_numeric( $value ) ) $value = (int) $value;
					}
					if ( ! is_numeric( $value ) )
						throw new Exception( 'Non-numeric value for int field' );
				}
				// In the normal case of just 1 value in the array
				// we will simply sum just that value
				$data_value = array_sum( $data_value_array );
				$format     = "%d";
				break;
			case 'datetime':
			case 'timestamp';
				// TODO: Multiple value dates ignored at the moment
				if ( count( $data_value_array ) > 1 )
					throw new Exception( 'Multiple datetime input is not understood currently' );
				foreach ( $data_value_array as &$value ) {
					if ( is_object( $value ) && method_exists( $value, '__toDateTimeFor' ) ) {
						// PHP only supports __toString() magic method
						$value = $value->__toDateTimeFor( $column_data_type, $column_name );
					} else {
						$value = CB_Query::ensure_datetime( $column_name, $value );
						$value = $value->format( CB_Database::$database_datetime_format );
					}
					if ( new DateTime( $value ) === FALSE )
						throw new Exception( "Failed to parse [$value] for datetime field" );
				}
				$data_value = $data_value_array[0];
				break;
			case 'char':
			case 'varchar':
			case 'longtext':
			default:
				// In the common single value array
				// we will simply concatenate the first value
				foreach ( $data_value_array as &$value ) {
					if ( is_object( $value ) && method_exists( $value, '__toStringFor' ) ) {
						// We want to send the column name for processing as well
						// so we do not use the built in magic __toString()
						// In the case of *_IDs columns,
						// the object should return its (string) __toInt() instead
						$value = $value->__toStringFor( $column_data_type, $column_name );
					}
					if ( is_numeric( $value ) ) $value = (string) $value;

					if ( ! is_string( $value ) ) throw new Exception( 'Non-string value for string field' );
				}
				$data_value = implode( ',', $data_value_array );
		}

		// Special cases
		if ( is_string( $data_value ) && $data_value == CB_Database::$NULL_indicator ) $data_value = NULL;

		return $data_value;
	}

	// -------------------------------------------------------------------- Classes
  static function database_table( $Class ) {
		$class_database_table = NULL;

		if ( property_exists( $Class, 'database_table' ) ) $class_database_table = $Class::$database_table;
		else if ( property_exists( $Class, 'static_post_type' ) ) {
			$post_type_stub       = CB_Query::substring_before( $Class::$static_post_type );
			$class_database_table = "cb2_{$post_type_stub}_posts";
		}
		if ( $class_database_table && ! CB_Database::has_table( $class_database_table ) )
			throw new Exception( "[$wpdb->prefix$class_database_table] does not exist" );

		return $class_database_table;
  }

  static function posts_table( $Class ) {
		$posts_table = FALSE;

		if ( ! property_exists( $Class, 'posts_table' ) || $Class::$posts_table !== FALSE ) {
			$post_type_stub = CB_Query::substring_before( $Class::$static_post_type );
			$posts_table    = "cb2_view_{$post_type_stub}_posts";
			if ( property_exists( $Class, 'posts_table' ) && is_string( $Class::$posts_table ) )
				$posts_table = $Class::$posts_table;
		}
		return $posts_table;
	}

  static function postmeta_table( $Class, &$meta_type = NULL, &$meta_table_stub = NULL, $ID = NULL ) {
		$postmeta_table = FALSE;

		if ( ! property_exists( $Class, 'postmeta_table' ) || $Class::$postmeta_table !== FALSE ) {
			if ( property_exists( $Class, 'static_post_type' ) ) {
				$meta_type       = CB_Query::substring_before( $Class::$static_post_type );
				$meta_table_stub = "{$meta_type}meta";

				if ( $ID && CB_Query::is_wp_post_ID( $ID ) ) {
					// NOTE: if the sent $ID is a wp_posts id
					// Then the postmeta_table will be set to wp_postmeta
					// This happens when the post is still in the normal WP tables
					// not been moved yet to the native structures and views
					$meta_type       = 'post';
					$meta_table_stub = 'postmeta';
					$postmeta_table  = 'postmeta';
				} else if ( property_exists( $Class, 'postmeta_table' ) && is_string( $Class::$postmeta_table ) ) {
					$postmeta_table  = $Class::$postmeta_table;
				} else {
					$postmeta_table  = "cb2_view_{$meta_table_stub}";
				}
			}
		}

		return $postmeta_table;
  }

  static function id_field( $Class ) {
		$id_field             = NULL;

		if ( $class_database_table = self::database_table( $Class ) ) {
			if ( property_exists( $Class, 'database_id_field' ) ) $id_field = $Class::$database_id_field;
			else {
				$id_field  = str_replace( 'cb2_', '', $class_database_table );
				$id_field  = substr( $id_field, 0, -1 );
				$id_field .= '_id';
			}
			if ( ! CB_Database::has_column( $class_database_table, $id_field ) )
				throw new Exception( "[$wpdb->prefix$class_database_table] does not have column [$id_field] during native table update attempt" );
		}

		return $id_field;
	}
}

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_Database_UpdateInsert extends CB_Database {
  static $database_command = 'UPDATE';

  protected function __construct( $table ) {
    parent::__construct( $table );
	}

  static function factory( $table ) {
    return new self( $table );
  }

  //TODO: complete CB_Database_UpdateInsert
}

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_Database_Delete extends CB_Database {
  static $database_command = 'DELETE';

  protected function __construct( $table ) {
    parent::__construct( $table );
    $this->conditions = array();
	}

  static function factory( $table ) {
    return new self( $table );
  }

  function add_condition( $field, $value, $allow_nulls = FALSE, $comparison = '=', $prepare = TRUE, $NOT = FALSE ) {
    global $wpdb;
    $comparison_sql = "$comparison ";
    $comparison_requires_brakets = ( $comparison == 'IN' || $comparison == 'LIKE' );
    if ( $comparison_requires_brakets ) $comparison_sql .= '(';
    $comparison_sql .= ( $prepare ? '%s' : $value );
    if ( $comparison_requires_brakets ) $comparison_sql .= ')';
		$comparison_sql = "$field $comparison_sql";
    if ( $NOT ) $comparison_sql = "NOT $comparison_sql";

    if ( $prepare ) $comparison_sql = $wpdb->prepare( $comparison_sql, $value );
    if ( $allow_nulls ) $comparison_sql = "(isnull($field) or $comparison_sql)";

    array_push( $this->conditions, $comparison_sql );

    return $this;
  }

  function prepare( $arg1 = NULL, $arg2 = NULL, $arg3 = NULL, $arg4 = NULL, $arg5 = NULL, $arg6 = NULL ) {
    global $wpdb;

    $this->condition_sql = implode( "\n          and ", $this->conditions );

    $this->sql = $wpdb->prepare(
      self::$database_command . "
      FROM $wpdb->prefix$this->table
      where
        $this->condition_sql",
      $arg1, $arg2 //TODO: add other args
    );

    return $this->sql;
  }

  function run( $arg1 = NULL, $arg2 = NULL, $arg3 = NULL, $arg4 = NULL, $arg5 = NULL, $arg6 = NULL ) {
    global $wpdb;
    if ( WP_DEBUG ) print( "<div class='cb2-debug cb2-sql'><pre>deleting from $this->table</pre></div>" );
    $sql = $this->prepare( $arg1, $arg2, $arg3, $arg4, $arg5, $arg6 );
    return $wpdb->query( $sql );
  }
}

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_Database_Truncate extends CB_Database_Delete {
  protected function __construct( $table ) {
    parent::__construct( $table );
	}

  static function factory_truncate( $table, $id_field ) {
		// We require an id field to avoid SQL_SAFE MODE
		// factory_truncate() named as not compatible with parent::factory() signature
    $truncate = new self( $table );
    $truncate->add_condition( $id_field, 0, FALSE, '>=' );
    return $truncate;
  }
}

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_Database_Query extends CB_Database {
  static $database_command = 'SELECT';
  static $post_fields = array(
    'ID' => 'ID',
    'post_title' => 'post_title',
  );

  protected function __construct( $table, $alias = NULL ) {
    parent::__construct( $table, $alias );

    // Data extension
    $this->order_direction = NULL;
    $this->and_count     = FALSE;
		$this->page_start    = NULL;
		$this->page_size     = NULL;
    $this->fields        = array( '*' );
    $this->joins         = array();
    $this->conditions    = array();
    $this->orderby_array = array();

    $this->field_sql     = NULL;
    $this->and_count_sql = NULL;
    $this->join_sql      = NULL;
    $this->condition_sql = NULL;
		$this->order_sql     = NULL;
		$this->limit_sql     = NULL;
  }

  static function factory( $table, $alias = NULL ) {
    return new self( $table, $alias );
  }

  function add_orderby( $name, $table_alias = NULL ) {
		if ( is_array( $name ) ) {
			foreach ( $name as $field_name ) {
				$this->add_orderby( $field_name, $table_alias );
			}
		} else {
			$full_name = ( $table_alias ? "$table_alias.$name" : $name );
			array_push( $this->orderby_array, $full_name );
		}
    return $this;
  }

  function add_order_direction( $dir ) {
    $this->order_direction = $dir;
    return $this;
  }


  function add_all_fields( $table_alias = NULL ) {
    return $this->add_field( '*', $table_alias );
  }

  function add_field( $name, $table_alias = NULL, $alias = NULL ) {
    if ( count( $this->fields ) == 1 && $this->fields[0] == '*' ) $this->fields = array();
    $full_name = ( $table_alias ? "$table_alias.$name" : $name );
    if ( $alias ) $full_name .= " as `$alias`";
    array_push( $this->fields, $full_name );
    return $this;
  }

  function add_constant_field( $alias, $value ) {
		$this->add_field( "'$value'", NULL, $alias );
  }

  function add_flag_field( $name, $bit_index, $table_alias = NULL, $alias = NULL ) {
    return $this->add_field( "$name & " . pow( 2, $bit_index ), $table_alias, $alias );
  }

  function add_posts_join( $table_alias, $on, $add_post_fields = TRUE ) {
    $this->add_join( 'posts', $table_alias, array( "$on = $table_alias.ID" ) );
    if ( $add_post_fields ) $this->add_post_fields( $table_alias );
    return $this;
  }

  function add_post_fields( $table_alias ) {
    foreach ( self::$post_fields as $field_name => $field_alias ) {
      $this->add_field( $field_name, $table_alias, "{$table_alias}_$field_alias" );
    }
    return $this;
  }

  function add_join( $table, $table_alias, $ons ) {
    global $wpdb;
    $on   = implode( $ons, ' and ' );
    $join = "$wpdb->prefix$table $table_alias on $on";
    array_push( $this->joins, $join );
    return $this;
  }

  function limit( $page_start, $page_size = 20 ) {
		$this->page_start = $page_start;
		$this->page_size  = $page_size;
  }

  function add_condition( $name, $value, $table_alias = NULL, $allow_nulls = FALSE, $comparison = '=', $prepare = TRUE, $not = FALSE ) {
    global $wpdb;
    $full_name = ( $table_alias ? "$table_alias.$name" : $name );
    $condition = ( $prepare ? $wpdb->prepare( "$full_name $comparison %s", $value ) : "$full_name $comparison $value" );
    if ( $allow_nulls ) $condition = "(isnull($full_name) or $condition)";
    if ( $not )         $condition = "not $condition";
    array_push( $this->conditions, $condition );
    return $this;
  }

  function prepare( $arg1 = NULL, $arg2 = NULL, $arg3 = NULL, $arg4 = NULL, $arg5 = NULL, $arg6 = NULL ) {
    global $wpdb;
    // SQL construction and execution
    $this->field_sql = implode( $this->fields, ', ' );

    $this->join_sql      = implode( "\n          left outer join ", $this->joins );
    if ( $this->join_sql ) $this->join_sql = "left outer join $this->join_sql ";

    $this->condition_sql = implode( "\n          and ", $this->conditions );

    if ( $this->and_count ) $this->and_count_sql = 'SQL_CALC_FOUND_ROWS';

    // Order
    if ( count( $this->orderby_array ) ) {
      $this->order_sql = implode( ', ', $this->orderby_array );
      if ( $this->order_direction ) $this->order_sql .= " $this->order_direction";
      $this->order_sql = "order by $this->order_sql";
    }

    // Limits
	  if ( ! is_null( $this->page_start ) && ! is_null( $this->page_size ) )
			$this->limit_sql = "LIMIT $this->page_start, $this->page_size";

    $this->sql = $wpdb->prepare(
      self::$database_command . " $this->and_count_sql $this->field_sql
      FROM $wpdb->prefix$this->table
        $this->join_sql
      where
        $this->condition_sql
        $this->order_sql
        $this->limit_sql",
      $arg1, $arg2 //TODO: add other args
    );

    // if ( WP_DEBUG ) print( "<div class='cb2-debug cb2-sql'><pre>$this->sql</pre></div>" );

    return $this->sql;
  }

  function run( $arg1 = NULL, $arg2 = NULL, $arg3 = NULL, $arg4 = NULL, $arg5 = NULL, $arg6 = NULL ) {
    return $this->get_results( $this->prepare( $arg1, $arg2, $arg3, $arg4, $arg5, $arg6 ) );
  }

  function get_results( $sql ) {
    global $wpdb;
    return $wpdb->get_results( $sql );
  }
}

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_Database_Insert extends CB_Database {
  static $database_command = 'INSERT';

  protected function __construct( $table ) {
    parent::__construct( $table );

    // Data extension
    $this->fields  = array();
    $this->formats = array();
  }

  static function factory( $table ) {
    return new self( $table );
  }

  function add_field( $name, $value, $format = NULL ) {
    if ( ! is_null( $value ) )  {
      $this->fields[$name]  = $value;
      $this->formats[$name] = ( is_null( $format ) ? '%s' : $format );
    }
  }

  function prepare( $arg1 = NULL, $arg2 = NULL, $arg3 = NULL, $arg4 = NULL, $arg5 = NULL, $arg6 = NULL ) {
    if ( WP_DEBUG ) {
      print( "<div class='cb2-debug cb2-sql'><h2>WP_DEBUG insert SQL: $this->table</h2><pre>" );
      print_r( $this->fields );
      print_r( $this->formats );
      print( '</pre></div>' );
    }
    return NULL;
	}

  function run() {
    global $wpdb;

    $wpdb->insert( "$wpdb->prefix$this->table", $this->fields, $this->formats );
    $insert_id = $wpdb->insert_id;

    if ( WP_DEBUG ) {
      print( "<div class='cb2-debug cb2-result'>" );
      print( " = $insert_id" );
      print( '</div>' );
    }

    return $insert_id;
  }
}
