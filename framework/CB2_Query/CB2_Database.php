<?php
define( 'CB2_INT',       'INT' );
define( 'CB2_TINYINT',   'TINYINT' );
define( 'CB2_BIGINT',    'BIGINT' );
define( 'CB2_VARCHAR',   'VARCHAR' );
define( 'CB2_CHAR',      'CHAR' );
define( 'CB2_LONGTEXT',  'LONGTEXT' );
define( 'CB2_DATETIME',  'DATETIME' );
define( 'CB2_TIMESTAMP', 'TIMESTAMP' );
define( 'CB2_BIT',       'BIT' );

define( 'CB2_CURRENT_TIMESTAMP', 'CURRENT_TIMESTAMP' );
define( 'CB2_AUTO_INCREMENT',    'AUTO_INCREMENT' );
define( 'CB2_NOT_NULL',          'NOT_NULL' );
define( 'CB2_NULL',              'NULL' );
define( 'CB2_UNSIGNED',          'UNSIGNED' );

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB2_Database {
  static $database_date_format     = 'Y-m-d';
  static $database_datetime_format = 'Y-m-d H:i:s';
  static $NULL_indicator           = '__Null__';

	// -------------------------------- Database Installation
  static $safe_updates_off     = "
					set @safe_updates = @@sql_safe_updates;
					set @@sql_safe_updates = 0;";
	static $safe_updates_restore = "
					SET @@sql_safe_updates = @safe_updates;";

  public static function install_array() {
		global $wpdb;
		$install_array = array();

		foreach ( get_declared_classes() as $Class ) { // PHP 4
			if ( CB2_Query::has_own_method( $Class, 'database_table_schema' ) )
				$install_array[ $Class ][ 'table' ] = $Class::database_table_schema( $wpdb->prefix );
			if ( CB2_Query::has_own_method( $Class, 'database_views' ) )
				$install_array[ $Class ][ 'views' ] = $Class::database_views( $wpdb->prefix );
			if ( CB2_Query::has_own_method( $Class, 'database_data' ) )
				$install_array[ $Class ][ 'data' ]  = $Class::database_data( $wpdb->prefix );
		}
		return $install_array;
  }

  public static function install_SQL() {
		$date = (new DateTime())->format( 'c' );
		$host = $_SERVER['HTTP_HOST'];
		$sql  = "# ------------------------------ $date\n";
		$sql .= "# host: $host\n";
		$sql .= "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n";
		$sql .= "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n";
		$sql .= "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n";
		$sql .= "/*!40101 SET NAMES utf8 */;\n";
		$sql .= "/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;\n";
		$sql .= "/*!40103 SET TIME_ZONE='+00:00' */;\n";
		$sql .= "/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;\n";
		$sql .= "/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;\n";
		$sql .= "/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;\n";
		$sql .= "/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;\n";
		$sql .= "\n\n";

		// Tables
		$sql .= "# ------------------------------ TABLES\n";
		foreach ( get_declared_classes() as $Class ) { // PHP 4
			if ( CB2_Query::has_own_method( $Class, 'database_table_schema' ) )
				$sql .= self::database_table_schema_SQL( $Class );
		}
		$sql .= "\n\n";

		// Data
		$sql .= "# ------------------------------ DATA\n";
		foreach ( get_declared_classes() as $Class ) { // PHP 4
			if ( CB2_Query::has_own_method( $Class, 'database_data' ) )
				$sql .= self::database_data_SQL( $Class );
		}
		$sql .= "\n\n";

		// Constraints
		$sql .= "# ------------------------------ CONSTRAINTS\n";
		foreach ( get_declared_classes() as $Class ) { // PHP 4
			if ( CB2_Query::has_own_method( $Class, 'database_table_schema' ) )
				$sql .= self::database_table_constraints_SQL( $Class );
		}
		$sql .= "\n\n";

		// Views
		$sql .= "# ------------------------------ VIEWS\n";
		foreach ( get_declared_classes() as $Class ) { // PHP 4
			if ( CB2_Query::has_own_method( $Class, 'database_views' ) )
				$sql .= self::database_views_SQL( $Class );
		}
		$sql .= "\n\n";

		$sql .= "/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;\n";
		$sql .= "/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;\n";
		$sql .= "/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;\n";
		$sql .= "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n";
		$sql .= "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n";
		$sql .= "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n";
		$sql .= "/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;\n";

		return $sql;
  }

  private static function database_table_schema_SQL( $Class ) {
		global $wpdb;
		static $i = 1;

		$table_name = $Class::database_table_name();
		$definition = $Class::database_table_schema( $wpdb->prefix );

		$sql  = "DROP TABLE IF EXISTS `$wpdb->prefix$table_name`;\n";
		$sql .= "CREATE TABLE `$wpdb->prefix$table_name` (\n";
		foreach ( $definition['columns'] as $name => $column ) {
			// TYPE, (SIZE), CB2_UNSIGNED, NOT NULL, CB2_AUTO_INCREMENT, DEFAULT, COMMENT
			$count    = count( $column );
			$type     = $column[0];

			// Optional
			$size     = ( $count > 1 && $column[1] ? "($column[1])" : '' );
			$unsigned = ( $count > 2 && $column[2] ? 'UNSIGNED' : '' );
			$not_null = ( $count > 3 && $column[3] ? 'NOT NULL' : '' );
			$auto     = ( $count > 4 && $column[4] ? 'AUTO_INCREMENT' : '' );
			$default  = ( $count > 5 ? $column[5] : NULL );
			$comment  = ( $count > 6 && $column[6] ? "COMMENT '$column[6]'" : '' );

			// TODO: use the standard converter for the DEFAULT
			if ( is_null( $default ) ) {
				// No default
			} else if ( $default === CB2_CURRENT_TIMESTAMP || $default === CB2_NULL ) {
				// Stated constants
				$default = "DEFAULT $default";
			} else if ( $type == CB2_BIT ) {
				$default = 'DEFAULT ' . CB2_Database::int_to_bitstring( $default );
			} else if ( is_string( $default ) ) {
				$default = "DEFAULT '" . str_replace( "'", "\\\'", $default ) . "'";
			} else {
				$default = "DEFAULT $default";
			}

			$syntax = "`$name` $type$size $unsigned $not_null $auto $default $comment";
			$syntax = trim( preg_replace( '/ {2,}/', ' ', $syntax ) );
			$sql   .= "  $syntax,\n";
		}

		if ( $definition['primary key'] ) {
			$sql .= "  PRIMARY KEY (";
			foreach ( $definition['primary key'] as $column ) $sql .= "`$column`,";
			$sql = substr( $sql, 0, -1 );
			$sql .= "),\n";
		}

		if ( $definition['unique keys'] ) {
			foreach ( $definition['unique keys'] as $name ) {
				$sql .= "  UNIQUE KEY `uk_$i` (`$name`),\n";
				$i++;
			}
		}

		if ( $definition['keys'] ) {
			foreach ( $definition['keys'] as $name ) {
				$sql .= "  KEY `idx_$i` (`$name`),\n";
				$i++;
			}
		}

		$sql = substr( $sql, 0, -2 ); // Trailing comma
		$sql .= "\n);\n";

		if ( $definition['many to many'] ) {
			foreach ( $definition['many to many'] as $m2m_table => $details ) {
				$column_name   = $details[0];
				$column        = $definition['columns'][$column_name];
				$target_table  = "$wpdb->prefix$details[1]";
				$target_column = $details[2];

				$count         = count( $column );
				$type          = $column[0];
				$size          = ( $count > 1 && $column[1] ? "($column[1])" : '' );
				$unsigned      = ( $count > 2 && $column[2] ? 'UNSIGNED' : '' );

				$sql .= "# many-to-many directive $m2m_table:\n";
				$sql .= "DROP TABLE IF EXISTS `$wpdb->prefix$m2m_table`;\n";
				$sql .= "CREATE TABLE `$wpdb->prefix$m2m_table` (\n";
				$sql .= "  `$column_name`   $type$size $unsigned NOT NULL,\n";
				$sql .= "  `$target_column` $type$size $unsigned NOT NULL,\n";
				$sql .= "  PRIMARY KEY( `$column_name`, `$target_column` )\n";
				$sql .= ");\n";
			}
		}

		$sql .= "\n";
		return $sql;
	}

	private static function database_table_constraints_SQL( $Class ) {
		global $wpdb;
		static $i = 1;

		$table_name = $Class::database_table_name();
		$definition = $Class::database_table_schema( $wpdb->prefix );
		$sql = "# Constraints for $table_name\n";

		if ( $definition['many to many'] ) {
			foreach ( $definition['many to many'] as $m2m_table => $details ) {
				$column_name   = $details[0];
				$column        = $definition['columns'][$column_name];
				$target_table  = "$wpdb->prefix$details[1]";
				$target_column = $details[2];

				$fk_name_base = "fk_$wpdb->prefix{$m2m_table}";
				$sql .= "ALTER TABLE `$wpdb->prefix$m2m_table` ADD CONSTRAINT `fk_$i` FOREIGN KEY (`$column_name`) REFERENCES `$wpdb->prefix$table_name` (`$column_name`) ON DELETE NO ACTION ON UPDATE NO ACTION;\n";
				$i++;
				$sql .= "ALTER TABLE `$wpdb->prefix$m2m_table` ADD CONSTRAINT `fk_$i` FOREIGN KEY (`$target_column`) REFERENCES `$target_table` (`$target_column`) ON DELETE NO ACTION ON UPDATE NO ACTION;\n";
				$i++;

				// many-to-many can have triggers also
				if ( $details['triggers'] ) {
					$sql .= "DELIMITER ;;\n";
					foreach ( $details['triggers'] as $type => $triggers ) {
						foreach ( $triggers as $body ) {
							if ( FALSE ) $body .= "\n					insert into {$prefix}cb2_debug( `event`, rows_affected ) values( @@TRIGGER_NAME@@, ROW_COUNT() );\n";
							$body = str_replace( "@@TRIGGER_NAME@@", "'tr_$i'", $body );

							$sql .= "CREATE TRIGGER `tr_$i` $type ON `$wpdb->prefix$m2m_table` FOR EACH ROW\n";
							$sql .= "BEGIN\n$body\nEND;;\n";
							$i++;
						}
					}
					$sql .= "DELIMITER ;\n";
				}
			}
		}

		if ( $definition['foreign keys'] ) {
			foreach ( $definition['foreign keys'] as $column => $constraint ) {
				$foreign_table  = "$wpdb->prefix$constraint[0]";
				$foreign_column = $constraint[1];
				$sql .= "ALTER TABLE `$wpdb->prefix$table_name` ADD CONSTRAINT `fk_$i` FOREIGN KEY (`$column`) REFERENCES `$foreign_table` (`$foreign_column`) ON DELETE NO ACTION ON UPDATE NO ACTION;\n";
				$i++;
			}
		}

		if ( $definition['triggers'] ) {
			$sql .= "DELIMITER ;;\n";
			foreach ( $definition['triggers'] as $type => $triggers ) {
				foreach ( $triggers as $body ) {
					if ( FALSE ) $body .= "\n					insert into {$prefix}cb2_debug( `event`, rows_affected ) values( @@TRIGGER_NAME@@, ROW_COUNT() );\n";
					$body = str_replace( "@@TRIGGER_NAME@@", "'tr_$i'", $body );

					$sql .= "CREATE TRIGGER `tr_$i` $type ON `$wpdb->prefix$table_name` FOR EACH ROW\n";
					$sql .= "BEGIN\n$body\nEND;;\n";
					$i++;
				}
			}
			$sql .= "DELIMITER ;\n";
		}

		return $sql;
	}

	private static function database_views_SQL( $Class ) {
		global $wpdb;

		$sql = "# Views for $Class\n";
		foreach ( $Class::database_views() as $name => $view ) {
			$sql .= "DROP VIEW IF EXISTS `$wpdb->prefix$name`;\n";
			$sql .= "CREATE VIEW `$wpdb->prefix$name` AS\n  $view;\n";
		}
		$sql .= "\n";

		return $sql;
	}

	private static function database_data_SQL( $Class ) {
		global $wpdb;

		$table_name = $Class::database_table_name();

		$sql = "# Data for $table_name\n";
		foreach ( $Class::database_data() as $row ) {
			$sql .= "INSERT INTO `$wpdb->prefix$table_name` values(";
			foreach ( $row as $value )
				$sql .= "'$value',";
			$sql  = substr( $sql, 0, -1 );
			$sql .= ");\n";
		}
		$sql .= "\n";

		return $sql;
  }

  private static function uninstall_SQL( $Class ) {
		global $wpdb;
		// TODO: uninstall_SQL()
		$table_name = $Class::database_table_name();
		return "DROP TABLE `$wpdb->prefix$table_name`;";
	}

	// -------------------------------- Conversion Utilities
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
	static function columns( $Class ) {
		global $wpdb;

		// Convert to results of the MySQL DESC results
		// DESC table
		// TODO: convert to MySQL DESC results is not necessary
		$column_array = $Class::database_table_schema( $wpdb->prefix )['columns'];
		$columns      = array();
		foreach ( $column_array as $column_name => $column_definition ) {
			// TYPE, (SIZE), CB2_UNSIGNED, NOT NULL, CB2_AUTO_INCREMENT, DEFAULT, COMMENT
			$count = count( $column_definition );
			$columns[$column_name] = (object) array(
				'Field'   => $column_name,
				'Type'    => $column_definition[0],
				'Null'    => ( $count > 3 && $column_definition[3] === CB2_NOT_NULL ? 'NO' : NULL ),
				'Extra'   => ( $count > 4 && $column_definition[4] ? 'auto_increment' : NULL ),
				'Default' => ( $count > 5 && ! is_null( $column_definition[5] ) ? TRUE : NULL ),
			);
		}

		return $columns;
  }

  // ------------------------------------ Data saving
	static function sanitize_data_for_table( $Class, $data, &$formats = array(), $update = FALSE ) {
		// https://developer.wordpress.org/reference/classes/wpdb/insert/
		// A format is one of '%d', '%f', '%s' (integer, float, string).
		// If omitted, all values in $data will be treated as strings...
		global $wpdb;

		$new_data     = array();
		$columns      = CB2_Database::columns( $Class );
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
					throw new Exception( "[$Class(table)::$column_name] is required" );
				}
			} else {
				$data_value = self::convert_for_field( $column_definition->Field, $column_definition->Type, $data_value_array, $format );

				$new_data[ $column_name ] = $data_value;
				$formats[  $column_name ] = $format;
			}
		}

		return $new_data;
	}

	static function convert_for_field( $column_name, $column_data_type, $data_value_array, &$format ) {
		// Data conversion
		$format           = '%s';
		$data_value       = NULL;

		switch ( $column_data_type ) {
			case CB2_BIT:
				// PostGRES and MySQL support the BIT type:
				// https://www.postgresql.org/docs/7.1/static/datatype-bit.html
				// with b'01010' syntax
				//
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
			case CB2_TINYINT:
			case CB2_INT:
			case CB2_BIGINT:
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
			case CB2_DATETIME:
			case CB2_TIMESTAMP;
				// PostGRES and MySQL support the TIMESTAMP type
				// https://www.postgresql.org/docs/8.4/static/datatype-datetime.html
				// TODO: PostGRES does not support DATETIME type
				// TODO: PostGRES does support INTERVAL type, but it needs to be checked
				//
				// Multiple value dates ignored
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
			case CB2_CHAR:
			case CB2_VARCHAR:
			case CB2_LONGTEXT:
			default:
				// PostGRES supports CHAR and VARCHAR
				// TODO: PostGRES calls LONGTEXT TEXT
				// https://www.postgresql.org/docs/7.1/static/datatype-character.html
				//
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
		if ( is_string( $data_value ) && $data_value == CB2_Database::$NULL_indicator )
			$data_value = NULL;

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

		return $class_database_table;
  }

  static function posts_table( $Class ) {
		$posts_table = FALSE;

		if ( property_exists( $Class, 'static_post_type' ) ) {
			if ( ! property_exists( $Class, 'posts_table' ) || $Class::$posts_table !== FALSE ) {
				$post_type_stub = CB2_Query::substring_before( $Class::$static_post_type );
				$posts_table    = "cb2_view_{$post_type_stub}_posts";
				if ( property_exists( $Class, 'posts_table' ) && is_string( $Class::$posts_table ) )
					$posts_table = $Class::$posts_table;
			}
		}
		return $posts_table;
	}

  static function postmeta_table( $Class, &$meta_type = NULL, &$meta_table_stub = NULL ) {
		$postmeta_table = FALSE;

		if ( property_exists( $Class, 'static_post_type' ) ) {
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
		}

		return $id_field;
	}
}