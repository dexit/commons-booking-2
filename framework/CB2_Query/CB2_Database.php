<?php
define( 'CB2_INT',       'INT' );
define( 'CB2_TINYINT',   'TINYINT' );
define( 'CB2_BIGINT',    'BIGINT' );
define( 'CB2_VARCHAR',   'VARCHAR' );
define( 'CB2_CHAR',      'CHAR' );
define( 'CB2_TEXT',      'TEXT' );
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

	static $columns = array(
		'name',
		'type',
		'size',
		'unsigned',
		'not null',
		'auto increment',
		'default',
		'comment',
		'foreign key',
	);

  public static function schema_array() {
		global $wpdb;
		$schema_array = array();

		foreach ( get_declared_classes() as $Class ) { // PHP 4
			if ( CB2_Query::has_own_method( $Class, 'database_table_schemas' ) )
				$schema_array[ $Class ][ 'table' ] = $Class::database_table_schemas( $wpdb->prefix );
			if ( CB2_Query::has_own_method( $Class, 'database_views' ) )
				$schema_array[ $Class ][ 'views' ] = $Class::database_views( $wpdb->prefix );
			if ( CB2_Query::has_own_method( $Class, 'database_data' ) )
				$schema_array[ $Class ][ 'data' ]  = $Class::database_data( $wpdb->prefix );
		}
		return $schema_array;
  }

  public static function get_install_SQL_header( $prefix, $character_set = FALSE ) {
		// Setting the character set
		// would cause all string literals in the views
		// to be in that character set
		// If the database is in a different character set, e.g. latin1
		// then CONCAT(field_value, "string literal") will throw a collation error
		//
		// We are removing foreign key checks although we do not need to
		$date          = (new CB2_DateTime())->format( 'c' );
		$host          = $_SERVER['HTTP_HOST'];

		$sqls = array();
		array_push( $sqls, "# ------------------------------ $date" );
		array_push( $sqls, "# host: $host" );
		array_push( $sqls, "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */" );
		array_push( $sqls, "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */" );
		array_push( $sqls, "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */" );
		array_push( $sqls, "/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */" );
		array_push( $sqls, "/*!40103 SET TIME_ZONE='+00:00' */" );
		array_push( $sqls, "/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */" );
		array_push( $sqls, "/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */" );
		array_push( $sqls, "/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */" );
		array_push( $sqls, "/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */" );
		if ( $character_set ) array_push( $sqls, "/*!40101 SET NAMES $character_set */" );

		return $sqls;

	}

	public static function get_install_SQL_footer( $prefix ) {
		$sqls = array();

		array_push( $sqls, "/*!40101 SET SQL_MODE=@OLD_SQL_MODE */" );
		array_push( $sqls, "/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */" );
		array_push( $sqls, "/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */" );
		array_push( $sqls, "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */" );
		array_push( $sqls, "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */" );
		array_push( $sqls, "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */" );
		array_push( $sqls, "/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */" );

		return $sqls;
	}

	public static function get_reinstall_SQL_full( $character_set = FALSE ) {
		// Useful for copy paste installations
		// and for debug
		global $wpdb;

		$sqls = array();
		$sqls = array_merge( $sqls, self::get_install_SQL_header( $wpdb->prefix, $character_set ) );
		$sqls = array_merge( $sqls, self::get_uninstall_sqls() );
		$sqls = array_merge( $sqls, self::get_install_sqls() );
		$sqls = array_merge( $sqls, self::get_install_SQL_footer( $wpdb->prefix ) );

		$sqls = preg_replace( '/ using utf8mb4\)/i', " using $character_set)", $sqls );

		// DELIMITER ;; is rendered DELIMITER ;;;
		// and so are the trigger END;; rendered END;;;
		return implode( ";\n", $sqls );
	}

	public static function install() {
		global $wpdb;

		$install_SQLs = self::get_install_sqls();
		foreach ( $install_SQLs as $sql ) {
			// In this scenario we do not need DELIMITERs
			// because the SQL statement is singular
			$is_delimiter = ( $sql && substr( $sql, 0, 10 ) == 'DELIMITER ' );
			$is_comment   = ( $sql && strstr( '#/', $sql[0] ) !== FALSE );
			if ( ! $is_comment && ! $is_delimiter ) {
				$wpdb->query( $sql );
			}
		}
	}

	public static function get_install_sqls() {
		global $wpdb;

		$install_SQLs = array();
		$schema_array = self::schema_array();

		foreach ( $schema_array as $Class => $objects ) {
			if ( isset( $objects['table'] ) ) {
				foreach ( $objects['table'] as $table ) {
					$pseudo  = ( isset( $table['pseudo'] )  ? $table['pseudo']  : FALSE );
					$managed = ( isset( $table['managed'] ) ? $table['managed'] : TRUE );
					if ( ! $pseudo && $managed ) $install_SQLs = array_merge( $install_SQLs, self::database_table_schema_SQL( $wpdb->prefix, $table ) );
				}
			}
		}

		foreach ( $schema_array as $Class => $objects ) {
			if ( isset( $objects['data'] ) ) {
				// TODO: only the primary table can be populated at the moment
				$table_name = $Class::database_table_name();
				$install_SQLs = array_merge( $install_SQLs, self::database_data_SQL( $wpdb->prefix, $table_name, $objects['data'] ) );
			}
		}

		foreach ( $schema_array as $Class => $objects ) {
			if ( isset( $objects['table'] ) ) {
				foreach ( $objects['table'] as $table ) {
					$pseudo  = ( isset( $table['pseudo'] )  ? $table['pseudo']  : FALSE );
					$managed = ( isset( $table['managed'] ) ? $table['managed'] : TRUE );
					if ( ! $pseudo && $managed ) $install_SQLs = array_merge( $install_SQLs, self::database_table_constraints_SQL( $wpdb->prefix, $table ) );
				}
			}
		}

		foreach ( $schema_array as $Class => $objects ) {
			if ( isset( $objects['views'] ) ) {
				foreach ( $objects['views'] as $name => $body ) {
					$install_SQLs = array_merge( $install_SQLs, self::database_views_SQL( $wpdb->prefix, $name, $body ) );
				}
			}
		}

		return $install_SQLs;
	}

	private static function database_constraint_drops_SQL( $prefix, $definition ) {
		// DROP all the constraints, not just the registered ones
		// because the DB might have changed
		static $i = 1;
		$table_name = $definition['name'];
		$full_table = "$prefix$table_name";
		$pseudo     = ( isset( $definition['pseudo'] )  ? $definition['pseudo']  : FALSE );
		$managed    = ( isset( $definition['managed'] ) ? $definition['managed'] : TRUE );
		if ( $pseudo || ! $managed ) throw new Exception( "$table_name is not managed. Why is constraints drop SQL being requested?" );
		$mysql_constraint_drop = 'DROP FOREIGN KEY';

		$sqls = array();
		if ( isset( $definition['many to many'] ) ) {
			foreach ( $definition['many to many'] as $m2m_table => $m2m_definition ) {
				$full_m2m_table = "$prefix$m2m_table";
				$fk_name_base   = "fk_$full_m2m_table";
				array_push( $sqls, "ALTER TABLE `$full_m2m_table` $mysql_constraint_drop `fk_$i`" );
				$i++;
				array_push( $sqls, "ALTER TABLE `$full_m2m_table` $mysql_constraint_drop `fk_$i`" );
				$i++;
			}
		}

		if ( isset( $definition['foreign keys'] ) ) {
			foreach ( $definition['foreign keys'] as $column => $constraint ) {
				array_push( $sqls, "ALTER TABLE `$full_table` $mysql_constraint_drop `fk_$i`" );
				$i++;
			}
		}

		return $sqls;
	}

	private static function database_table_drops_SQL( $prefix, $definition ) {
		$sqls       = array();
		$table_name = $definition['name'];
		$pseudo     = ( isset( $definition['pseudo'] )  ? $definition['pseudo']  : FALSE );
		$managed    = ( isset( $definition['managed'] ) ? $definition['managed'] : TRUE );
		if ( $pseudo || ! $managed ) throw new Exception( "$table_name is not managed. Why is drop SQL being requested?" );
		array_push( $sqls, "DROP TABLE IF EXISTS `$prefix$table_name`" );
		return $sqls;
	}

  private static function database_table_schema_SQL( $prefix, $definition ) {
		static $i = 1;

		$sqls = array();
		$table_name = $definition['name'];
		$pseudo     = ( isset( $definition['pseudo'] )  ? $definition['pseudo']  : FALSE );
		$managed    = ( isset( $definition['managed'] ) ? $definition['managed'] : TRUE );
		if ( $pseudo || ! $managed ) throw new Exception( "$table_name is not managed. Why is create SQL being requested?" );
		$sql        = "CREATE TABLE `$prefix$table_name` (\n";
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

		if ( isset( $definition['primary key'] ) ) {
			$sql .= "  PRIMARY KEY (";
			foreach ( $definition['primary key'] as $column ) $sql .= "`$column`,";
			$sql = substr( $sql, 0, -1 );
			$sql .= "),\n";
		}

		if ( isset( $definition['unique keys'] ) ) {
			foreach ( $definition['unique keys'] as $name ) {
				$sql .= "  UNIQUE KEY `uk_$i` (`$name`),\n";
				$i++;
			}
		}

		if ( isset( $definition['keys'] ) ) {
			foreach ( $definition['keys'] as $name ) {
				$sql .= "  KEY `idx_$i` (`$name`),\n";
				$i++;
			}
		}

		$sql = substr( $sql, 0, -2 ); // Trailing comma
		$sql .= "\n)";
		array_push( $sqls, $sql );

		if ( isset( $definition['many to many'] ) ) {
			foreach ( $definition['many to many'] as $m2m_table => $details ) {
				$column_name   = $details[0];
				$column        = $definition['columns'][$column_name];
				$target_table  = "$prefix$details[1]";
				$target_column = $details[2];

				$count         = count( $column );
				$type          = $column[0];
				$size          = ( $count > 1 && $column[1] ? "($column[1])" : '' );
				$unsigned      = ( $count > 2 && $column[2] ? 'UNSIGNED' : '' );

				array_push( $sqls, "# many-to-many directive $m2m_table" );
				array_push( $sqls, "DROP TABLE IF EXISTS `$prefix$m2m_table`" );
				$sql  = "CREATE TABLE `$prefix$m2m_table` (\n";
				$sql .= "  `$column_name`   $type$size $unsigned NOT NULL,\n";
				$sql .= "  `$target_column` $type$size $unsigned NOT NULL,\n";
				$sql .= "  PRIMARY KEY( `$column_name`, `$target_column` )\n";
				$sql .= ")";
				array_push( $sqls, $sql );
			}
		}

		return $sqls;
	}

	private static function database_table_constraints_SQL( $prefix, $definition ) {
		static $i = 1;
		static $j = 1;

		$sqls = array();
		$table_name = $definition['name'];
		$pseudo     = ( isset( $definition['pseudo'] )  ? $definition['pseudo']  : FALSE );
		$managed    = ( isset( $definition['managed'] ) ? $definition['managed'] : TRUE );
		if ( $pseudo || ! $managed ) throw new Exception( "$table_name is not managed. Why is constraints SQL being requested?" );
		$full_table = "$prefix$table_name";
		array_push( $sqls, "# Constraints for $table_name" );

		if ( isset( $definition['many to many'] ) ) {
			foreach ( $definition['many to many'] as $m2m_table => $m2m_definition ) {
				$full_m2m_table = "$prefix$m2m_table";
				$column_name    = $m2m_definition[0];
				$column         = $definition['columns'][$column_name];
				$target_table   = "$prefix$m2m_definition[1]";
				$target_column  = $m2m_definition[2];

				$fk_name_base = "fk_$full_m2m_table";
				array_push( $sqls, "ALTER TABLE `$full_m2m_table` ADD CONSTRAINT `fk_$i` FOREIGN KEY (`$column_name`) REFERENCES `$full_table` (`$column_name`) ON DELETE NO ACTION ON UPDATE NO ACTION" );
				$i++;
				array_push( $sqls, "ALTER TABLE `$full_m2m_table` ADD CONSTRAINT `fk_$i` FOREIGN KEY (`$target_column`) REFERENCES `$target_table` (`$target_column`) ON DELETE NO ACTION ON UPDATE NO ACTION" );
				$i++;

				// many-to-many can have triggers also
				if ( isset( $m2m_definition['triggers'] ) && count( $m2m_definition['triggers'] )  ) {
					array_push( $sqls, "DELIMITER ;;" );
					foreach ( $m2m_definition['triggers'] as $type => $triggers ) {
						foreach ( $triggers as $body ) {
							$body = str_replace( "@@TRIGGER_NAME@@", "'$m2m_table.tr_$i'", $body );
							$body = str_replace( "@@TRIGGER_TYPE@@", "'$type'", $body );

							array_push( $sqls, "CREATE TRIGGER `tr_$i` $type ON `$full_m2m_table` FOR EACH ROW\nBEGIN\n$body\nEND;;\n" );
							$i++;
						}
					}
					array_push( $sqls, "DELIMITER ;" );
				}
			}
		}

		if ( isset( $definition['foreign keys'] ) ) {
			foreach ( $definition['foreign keys'] as $column => $constraint ) {
				$foreign_table  = "$prefix$constraint[0]";
				$foreign_column = $constraint[1];
				array_push( $sqls, "ALTER TABLE `$full_table` ADD CONSTRAINT `fk_$i` FOREIGN KEY (`$column`) REFERENCES `$foreign_table` (`$foreign_column`) ON DELETE NO ACTION ON UPDATE NO ACTION" );
				$i++;
			}
		}

		if ( isset( $definition['triggers'] ) && count( $definition['triggers'] ) ) {
			array_push( $sqls, "DELIMITER ;;" );
			foreach ( $definition['triggers'] as $type => $triggers ) {
				foreach ( $triggers as $trigger_body ) {
					$trigger_body = str_replace( "@@TRIGGER_NAME@@", "'$table_name.tr_$j'", $trigger_body );
					$trigger_body = str_replace( "@@TRIGGER_TYPE@@", "'$type'", $trigger_body );

					$trigger_body = self::check_fuction_bodies( "$table_name::trigger", $trigger_body );

					array_push( $sqls, "CREATE TRIGGER `tr_$j` $type ON `$full_table` FOR EACH ROW\nBEGIN\n$trigger_body\nEND;;" );
					$j++;
				}
			}
			array_push( $sqls, "DELIMITER ;" );
		}

		return $sqls;
	}

	private static function database_views_SQL( String $prefix, String $name, String $body ) {
		$sqls = array();
		$body = str_replace( "@@VIEW_NAME@@", "'$name'", $body );
		self::check_fuction_bodies( "view::$name", $body );
		array_push( $sqls, "DROP VIEW IF EXISTS `$prefix$name`" );
		array_push( $sqls, "CREATE VIEW `$prefix$name` AS\n  $body" );

		return $sqls;
	}

	private static function database_data_SQL( $prefix, $name, $data ) {
		$sqls = array();
		array_push( $sqls, "# Data for table $name" );
		foreach ( $data as $row ) {
			$sql = "INSERT INTO `$prefix$name` values(";
			foreach ( $row as $value )
				$sql .= "'$value',";
			$sql  = substr( $sql, 0, -1 );
			$sql .= ")";
			array_push( $sqls, $sql );
		}

		return $sqls;
  }

  static function uninstall() {
		global $wpdb;

		$install_SQLs = self::get_uninstall_sqls();
		foreach ( $install_SQLs as $sql ) {
			$is_comment   = ( $sql && strstr( '#/', $sql[0] ) !== FALSE );
			$is_delimiter = ( $sql && substr( $sql, 0, 10 ) == 'DELIMITER ' );
			if ( ! $is_comment && ! $is_delimiter ) {
				try {
					$wpdb->query( $sql );
				}
				catch ( Exception $ex ) {
					// Nevermind
				}
			}
		}
  }

  static function get_uninstall_sqls() {
		global $wpdb;

		$install_SQLs  = array();
		$schema_array = self::schema_array();

		foreach ( $schema_array as $Class => $objects ) {
			if ( isset( $objects['table'] ) ) {
				foreach ( $objects['table'] as $table ) {
					$pseudo  = ( isset( $table['pseudo'] )  ? $table['pseudo']  : FALSE );
					$managed = ( isset( $table['managed'] ) ? $table['managed'] : TRUE );
					if ( ! $pseudo && $managed ) $install_SQLs = array_merge( $install_SQLs, self::database_constraint_drops_SQL( $wpdb->prefix, $table ) );
				}
			}
		}

		foreach ( $schema_array as $Class => $objects ) {
				if ( isset( $objects['table'] ) ) {
					foreach ( $objects['table'] as $table ) {
						$pseudo  = ( isset( $table['pseudo'] )  ? $table['pseudo']  : FALSE );
						$managed = ( isset( $table['managed'] ) ? $table['managed'] : TRUE );
						if ( ! $pseudo && $managed ) $install_SQLs = array_merge( $install_SQLs, self::database_table_drops_SQL( $wpdb->prefix, $table ) );
					}
				}
		}

		return $install_SQLs;
	}

	public static function column_types() {
		static $columns = array();

		if ( count( $columns ) == 0 ) {
			$schema_array = self::schema_array();
			foreach ( $schema_array as $Class => $objects ) {
					if ( isset( $objects['table'] ) ) {
						foreach ( $objects['table'] as $table ) {
							foreach ( $table['columns'] as $name => $details ) {
								// TYPE, (SIZE), CB2_UNSIGNED, NOT NULL, CB2_AUTO_INCREMENT, DEFAULT, COMMENT
								if ( isset( $columns[$name] ) ) {
									if ( WP_DEBUG && $columns[$name][1] != $details[1] )
										throw new Exception( "Column [$name] has inconsistent types between tables" );
								} else {
									$columns[$name] = $details;
								}
							}
						}
					}
			}
		}

		return $columns;
	}

	public static function check_fuction_bodies( $identifier, $body ) {
		// When copying and pasting view bodies
		global $wpdb;

		// MySQL inserts CONVERT() statements for its local COLLATION
		// this will FAIL for double embedded CONVERTs
		// TODO: on the final system this should not be necessary
		if ( preg_match( '/ using /', $body ) )
			throw new Exception( "Function body [$identifier] has using statement string in it" );
		// MySQL inserts fully qualified Database names
		if ( preg_match( '/commonsbooking_2|wp47|wp501/', $body ) )
			throw new Exception( "Function body [$identifier] has database name string in it" );

		return $body;
	}

	// -------------------------------- Conversion Utilities
  static function bitarray_to_int( $bit_array ) {
    // array(1,0,1)
		// array(on,,on) = 2^0 + 2^2 => 1 + 4 = 5
    $int = NULL;
    if ( is_array( $bit_array ) ) {
      $int = 0;
      foreach ( $bit_array as $loc => $on ) {
        if ( $on ) $int += pow( 2, $loc );
      }
    } else throw new Exception( 'bit_array is not an array' );
    return $int;
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
  static function has_column( $Class, $column_name ) {
		return isset( self::columns( $Class )[$column_name] );
  }

	static function columns( $Class = NULL, $table = NULL ) {
		// Convert to results of the MySQL DESC results
		// DESC table
		// TODO: convert to MySQL DESC results is not necessary
		global $wpdb;
		static $columns_all = array();

		// Cache
		if ( is_null( $Class ) && count( $columns_all ) ) return $columns_all;

		if ( $Class && ! is_callable( array( $Class, 'database_table_schemas' ) ) )
			throw new Exception( "Request for [{$Class}::database_table_schemas()] columns has no table." );

		// Select the columns
		$column_array = array();
		if ( is_null( $Class ) ) {
			foreach ( CB2_Query::Classes() as $ClassX ) {
				if ( CB2_Query::has_own_method( $ClassX, 'database_table_schemas' ) ) {
					$database_table_schemas = $ClassX::database_table_schemas( $wpdb->prefix );
					foreach ( $database_table_schemas as $database_table_schema ) {
						if ( isset( $database_table_schema['columns'] ) )
							$column_array = array_merge( $column_array, $database_table_schema['columns'] );
					}
				}
			}
		} else {
			$database_table_schemas = $Class::database_table_schemas( $wpdb->prefix );
			if ( is_null( $table ) ) {
				// Primary (first) table
				$column_array = $database_table_schemas[0]['columns'];
			} else {
				// Columns from specific table
				foreach ( $database_table_schemas as $database_table_schema ) {
					if ( $database_table_schema['name'] == $table ) {
						$column_array = $database_table_schema['columns'];
						break;
					}
				}
			}
		}

		// Create the MySQL DESC output
		$columns      = array();
		foreach ( $column_array as $column_name => $column_definition ) {
			// TYPE, (SIZE), CB2_UNSIGNED, NOT NULL, CB2_AUTO_INCREMENT, DEFAULT, COMMENT
			$count = count( $column_definition );
			if ( isset( $columns[$column_name] ) ) {
				if ( WP_DEBUG && $columns[$column_name][1] != $column_definition[1] )
					throw new Exception( "Column [$name] has inconsistent types between tables" );
			}
			$columns[$column_name] = (object) array(
				'Field'   => $column_name,
				'Type'    => $column_definition[0],
				'Null'    => ( $count > 3 && $column_definition[3] === CB2_NOT_NULL ? 'NO' : NULL ),
				'Extra'   => ( $count > 4 && $column_definition[4] ? 'auto_increment' : NULL ),
				'Default' => ( $count > 5 && ! is_null( $column_definition[5] ) ? TRUE : NULL ),
				// Non MySQL DESC
				'Size'    => $column_definition[1],
				'Signed'  => $column_definition[2],
				'Comment' => ( $count > 6 && $column_definition[6] ? $column_definition[6] : NULL ),
				// Custom
				'Plural'  => CB2_Query::is_plural( $column_name ),
			);
		}
		if ( WP_DEBUG ) ksort( $columns );

		// Cacheing
		if ( is_null( $Class ) ) $columns_all = $columns;

		return $columns;
  }

  // ------------------------------------ Data saving
	static function sanitize_data_for_table( $Class, $data, &$formats = array(), $update = FALSE ) {
		// https://developer.wordpress.org/reference/classes/wpdb/insert/
		// A format is one of '%d', '%f', '%s' (integer, float, string).
		// If omitted, all values in $data will be treated as strings...
		global $wpdb;

		$new_data     = array();
		$columns      = self::columns( $Class );
		if ( CB2_DEBUG_SAVE ) krumo( $columns );

		foreach ( $columns as $column_name => $column_definition ) {
			// Look through the data for a value for this column
			$data_value_array = NULL;

			// Direct value with same name
			if ( isset( $data[$column_name] ) ) {
				$data_value_array = $data[$column_name];
			}

			// Namespaced value with same name
			// e.g. cb2_user_ID => user_ID
			if ( isset( $data["cb2_$column_name"] ) ) {
				$data_value_array = $data["cb2_$column_name"];
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
				if ( CB2_DEBUG_SAVE ) krumo( $data_value_array );
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
				if ( CB2_DEBUG_SAVE ) krumo( $data_value );
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
				if ( count( $data_value_array ) > 1 ) {
					krumo($data_value_array);
					throw new Exception( "Multiple datetime input is not understood currently for [$column_name]" );
				}
				foreach ( $data_value_array as &$value ) {
					if ( is_object( $value ) && method_exists( $value, '__toDateTimeFor' ) ) {
						// PHP only supports __toString() magic method
						$value = $value->__toDateTimeFor( $column_data_type, $column_name );
					} else {
						$value = CB2_Query::ensure_datetime( $column_name, $value );
						$value = $value->format( CB2_Database::$database_datetime_format );
					}
					// Check the value can be parsed
					new CB2_DateTime( $value, "Failed to parse [$value] for datetime field [$column_name]" );
				}
				$data_value = $data_value_array[0];
				break;
			case CB2_CHAR:
			case CB2_VARCHAR:
			case CB2_LONGTEXT:
			case CB2_TEXT:
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
						if ( $Class::$postmeta_table == 'postmeta' ) $meta_type = 'post';
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

	// ------------------------------------- Database reflection
	// generally only for WP_DEBUG
	// TODO: not portable. build this from the installation knowledge
  static function tables() {
		global $wpdb;
		$tables = $wpdb->get_col( "show tables", 0 );
		foreach ( $tables as &$table )
			$table = preg_replace( "/^$wpdb->prefix/", '', $table );
		return $tables;
  }

  static function has_table( String $table ) {
		return in_array( $table, self::tables() );
  }

  static function query_ok( String $sql ) {
		$ok = TRUE;
		if ( WP_DEBUG && preg_match( '/\sfrom\s+$wpdb->prefix([a-z0-9_]+)/i', $sql, $matches ) ) {
			$table = $matches[1];
			$table = preg_replace( "/^$wpdb->prefix/", '', $table );
			$ok    = self::has_table( $table );
			if ( ! $ok ) throw new Exception( "[$table] does not exist" );
		}
		return $ok;
  }
}
