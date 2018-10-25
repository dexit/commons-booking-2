<?php
// TODO: move these to static Class properties in CB2_Database?
define( 'INT',      'INT' );
define( 'TINYINT',  'TINYINT' );
define( 'BIGINT',   'BIGINT' );
define( 'VARCHAR',  'VARCHAR' );
define( 'DATETIME', 'DATETIME' );
define( 'CHAR',     'CHAR' );
define( 'BIT',      'BIT' );

define( 'CURRENT_TIMESTAMP', 'CURRENT_TIMESTAMP' );
define( 'AUTO_INCREMENT',    'AUTO_INCREMENT' );
define( 'NOT_NULL',          'NOT_NULL' );
define( 'UNSIGNED',          'UNSIGNED' );

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB2_Database {
  static $database_date_format     = 'Y-m-d';
  static $database_datetime_format = 'Y-m-d H:i:s';
  static $NULL_indicator           = '__Null__';

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
		global $wpdb;
		static $tables = NULL;

		if ( ! WP_DEBUG ) throw new Exception( __FUNCTION__ . ' is WP_DEBUG only' );

		if ( is_null( $tables ) ) {
			$tables = $wpdb->get_col( "show tables", 0 );
			foreach ( $tables as &$table ) $table = preg_replace( "/^$wpdb->prefix/", '', $table );
		}

		return $tables;
  }

	static function columns( $table ) {
		global $wpdb;
		static $columns = array();

		if ( ! WP_DEBUG ) throw new Exception( __FUNCTION__ . ' is WP_DEBUG only' );

		if ( ! isset( $columns[$table] ) ) {
			$desc_sql        = "DESC $wpdb->prefix$table";
			$columns[$table] = $wpdb->get_results( $desc_sql, OBJECT_K );
		}

		return $columns[$table];
  }

  static function procedures() {
		global $wpdb;

		if ( ! WP_DEBUG ) throw new Exception( __FUNCTION__ . ' is WP_DEBUG only' );

		return $wpdb->get_col( 'show procedure status', 1 );
  }

  static function functions() {
		global $wpdb;

		if ( ! WP_DEBUG ) throw new Exception( __FUNCTION__ . ' is WP_DEBUG only' );

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
					if ( CB2_DEBUG_SAVE ) krumo( $data );
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
		$column_data_type = strtolower( CB2_Query::substring_before( $column_definition->Type, '(' ) );

		switch ( $column_data_type ) {
			// TODO: types not portable? build this from the installation knowledge
			case 'bit':
				// int works on input:
				// 6 will successfully set bits 2 (2) and 3 (4)
				// b'01010' bit syntax is tricky because WordPress does not provide a format for it
				if ( count( $data_value_array ) > 1 )
					throw new Exception( "Multiple number array detected [$column_name]: bit arrays are set with a single unsigned" );
				foreach ( $data_value_array as &$value ) {
					if ( is_string( $value ) && $value == 'on' ) $value = 1;
					if ( is_bool( $value ) ) $value = (int) $value;
					if ( ! is_numeric( $value ) ) {
						krumo( $value );
						throw new Exception( "Non-numeric value for bit field [$column_name]" );
					}
				}
				$data_value = CB2_Query::ensure_int( $column_name, $data_value_array[0] );
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
					}
					if      ( is_numeric( $value ) ) $value = (int) $value;
					else if ( is_bool( $value ) )    $value = (int) $value;

					if ( ! is_numeric( $value ) ) {
						krumo( $value );
						throw new Exception( "Non-numeric value for int field [$column_name]" );
					}
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
					throw new Exception( "Multiple datetime input is not understood currently for [$column_name]" );
				foreach ( $data_value_array as &$value ) {
					if ( is_object( $value ) && method_exists( $value, '__toDateTimeFor' ) ) {
						// PHP only supports __toString() magic method
						$value = $value->__toDateTimeFor( $column_data_type, $column_name );
					} else {
						$value = CB2_Query::ensure_datetime( $column_name, $value );
						$value = $value->format( CB2_Database::$database_datetime_format );
					}
					if ( new DateTime( $value ) === FALSE )
						throw new Exception( "Failed to parse [$value] for datetime field [$column_name]" );
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

					if ( ! is_string( $value ) ) {
						krumo( $value );
						throw new Exception( "Non-string value for string field [$column_name]" );
					}
				}
				$data_value = implode( ',', $data_value_array );
		}

		// Special cases
		if ( is_string( $data_value ) && $data_value == CB2_Database::$NULL_indicator ) $data_value = NULL;

		return $data_value;
	}

	// -------------------------------------------------------------------- Classes
  static function database_table( $Class ) {
		$class_database_table = NULL;

		if ( property_exists( $Class, 'database_table' ) )
			$class_database_table = $Class::$database_table;
		else if ( property_exists( $Class, 'static_post_type' ) ) {
			$post_type_stub       = CB2_Query::substring_before( $Class::$static_post_type );
			$class_database_table = "cb2_{$post_type_stub}_posts";
		}
		if ( WP_DEBUG && $class_database_table && ! CB2_Database::has_table( $class_database_table ) )
			throw new Exception( "[$wpdb->prefix$class_database_table] does not exist" );

		return $class_database_table;
  }

  static function posts_table( $Class ) {
		$posts_table = FALSE;

		if ( ! property_exists( $Class, 'posts_table' ) || $Class::$posts_table !== FALSE ) {
			$post_type_stub = CB2_Query::substring_before( $Class::$static_post_type );
			$posts_table    = "cb2_view_{$post_type_stub}_posts";
			if ( property_exists( $Class, 'posts_table' ) && is_string( $Class::$posts_table ) )
				$posts_table = $Class::$posts_table;
		}
		return $posts_table;
	}

  static function postmeta_table( $Class, &$meta_type = NULL, &$meta_table_stub = NULL ) {
		$postmeta_table = FALSE;

		if ( ! property_exists( $Class, 'postmeta_table' ) || $Class::$postmeta_table !== FALSE ) {
			if ( property_exists( $Class, 'static_post_type' ) ) {
				$meta_type       = CB2_Query::substring_before( $Class::$static_post_type );
				$meta_table_stub = "{$meta_type}meta";

				if ( property_exists( $Class, 'postmeta_table' ) && is_string( $Class::$postmeta_table ) ) {
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
			if ( WP_DEBUG && ! CB2_Database::has_column( $class_database_table, $id_field ) )
				throw new Exception( "[$wpdb->prefix$class_database_table] does not have column [$id_field] during native table update attempt" );
		}

		return $id_field;
	}
}
