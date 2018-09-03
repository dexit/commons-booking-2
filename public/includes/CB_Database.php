<?php
// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_Database {
  static $database_date_format = 'Y-m-d';
  static $database_datetime_format = 'Y-m-d H:i:s';

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
  static function to_string( &$name, $value ) {
		// TODO: Refactor this in to the objects
    if ( is_array( $value ) ) {
			// periods = array(period, period) => period_IDs = 200000238, 200000239
			$string_value = '';
			$sub_name = $name;
			if ( substr( $sub_name, -1 ) == 's' ) $sub_name = substr( $sub_name, 0, -1 );
			foreach ( $value as $sub_value ) {
				if ( $string_value ) $string_value .= ',';
				$string_value .= self::to_string( $sub_name, $sub_value );
			}
			if ( substr( $sub_name, -1 ) != 's' ) $sub_name .= 's';
			$name  = $sub_name;
			$value = $string_value;
    } else if ( is_object( $value ) && $value instanceof CB_PostNavigator ) {
			// period = period object => period_ID = 200000238
			if ( property_exists( $value, 'ID' ) ) {
				$name .= '_ID';
				$value = $value->ID;
				if ( ! is_numeric( $value ) ) throw new Exception( "[$name] value [$value] is not numeric" );
			} else throw new Exception( "This CB_Post / CB_PostNavigator object should have an ID for [$name] property" );
		} else if ( is_object( $value ) && $value instanceof DateTime ) {
			// DateTime => 2018-06-10 12:34:23
			$date_string = $value->format( self::$database_datetime_format );
			if ( $value < new DateTime( '1970-01-01' ) ) throw new Exception( "Dodgy date [$date_string]" );
			$value = $date_string;
		} else {
			$value = (string) $value;
    }
    return $value;
  }

  static function bitarray_to_int( $bit_array ) {
    $int = NULL;
    if ( is_array( $bit_array ) ) {
      $int = 0;
      foreach ( $bit_array as $loc ) {
        $int += pow( 2, $loc );
      }
    }
    return $int;
  }

  static function bitarray_to_bitstring( $bit_array, $offset = 1 ) {
    $str = NULL;
    if ( is_array( $bit_array ) ) {
      $str = '000000';
      $strlen = strlen($str);
      foreach ( $bit_array as $loc ) {
        if ( $loc - $offset < $strlen )
          $str[$loc - $offset] = '1';
      }
      $str = "b'$str'";
    }
    return $str;
  }

  static function int_to_bitstring( $int ) {
		$binary    = decbin( $int );
		$bitstring = "b'$binary'";
		return $bitstring;
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
		global $wpdb;
		$tables = $wpdb->get_col( "show tables", 0 );
		foreach ( $tables as &$table ) $table = preg_replace( "/^$wpdb->prefix/", '', $table );
		return $tables;
  }

	static function columns( $table, $full_details = FALSE ) {
		global $wpdb;
		// TODO: cacheing!!!!
		$desc_sql = "DESC $wpdb->prefix$table";
		return $full_details ? $wpdb->get_results( $desc_sql, OBJECT_K ) : $wpdb->get_col( $desc_sql, 0 );
  }

  static function procedures() {
		global $wpdb;
		return $wpdb->get_col( 'show procedure status', 1 );
  }

  static function functions() {
		global $wpdb;
		return $wpdb->get_col( 'show function status', 0 );
  }

	static function sanitize_data_for_table( $table, $data ) {
		$new_data   = array();
		$columns    = self::columns( $table, TRUE );

		foreach ( $data as $field_name => $field_value ) {
			if ( is_null( $field_value ) || empty( $field_value ) ) {
				// Allow Database default values to take precedence
			} else {
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
					switch ( CB_Query::substring_before( $column_definition->Type, '(' ) ) {
						case 'bit':
							$field_value = self::int_to_bitstring( $field_value );
							break;
					}
					$field_value = CB_Query::cast_parameter( $field_name, $field_value );
					$field_value = self::to_string( $field_name, $field_value );

					$new_data[ $field_name ] = $field_value;
				}
			}
		}

		return $new_data;
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
      $this->fields[$name]  = CB_Database::to_string( $name, $value );
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
