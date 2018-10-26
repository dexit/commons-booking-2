<?php
class CB2_DatabaseTable_PostNavigator extends CB2_PostNavigator {
  public static function install_SQL() {
		$sql = '';
		// Tables and data
		foreach ( get_declared_classes() as $Class ) { // PHP 4
			if ( CB2_Query::has_own_method( $Class, 'database_table_schema' ) )
				$sql .= self::install_Class_SQL( $Class );
		}

		// Views
		/*
		foreach ( get_declared_classes() as $Class ) { // PHP 4
			if ( CB2_Query::has_own_method( $Class, 'database_table_schema' ) )
				$sql .= self::install_Class_SQL( $Class );
		}

		// Constraints
		...
		*/

		return $sql;
  }

  private static function install_Class_SQL( $Class ) {
		global $wpdb;

		$table_name = $Class::database_table_name();
		$definition = $Class::database_table_schema();
		$sql  = "# ------------------------------------ $Class::$table_name\n";
		$sql .= "CREATE `$wpdb->prefix$table_name` (\n";

		foreach ( $definition['columns'] as $name => $column ) {
			$count    = count( $column );
			$type     = $column[0];

			// Optional
			$size     = ( $count > 1 && $column[1] ? "($column[1])" : '' );
			$unsigned = ( $count > 2 && $column[2] ? 'UNSIGNED' : '' );
			$not_null = ( $count > 3 && $column[3] ? 'NOT NULL' : '' );
			$auto     = ( $count > 4 && $column[4] ? 'AUTO_INCREMENT' : '' );
			$default  = ( $count > 5 && $column[5] ? $column[5] : NULL );
			$comment  = ( $count > 6 && $column[6] ? "COMMENT '$column[6]'" : '' );
			switch ( $default ) {
				case NULL:
					break;
				case 'CURRENT_TIMESTAMP':
				case 'NULL':
					$default = "DEFAULT $default";
					break;
				default:
					$default = "DEFAULT '$default'";
			}

			$syntax = "`$name` $type$size $unsigned $not_null $auto $default $comment";
			$syntax = trim( preg_replace( '/ {2,}/', ' ', $syntax ) );
			$sql   .= "$syntax,\n";
		}

		if ( $definition['primary key'] ) {
			$sql .= "PRIMARY KEY (";
			foreach ( $definition['primary key'] as $column ) $sql .= "`$column`,";
			$sql = substr( $sql, 0, -1 );
			$sql .= "),\n";
		}

		if ( $definition['unique keys'] ) {
			foreach ( $definition['unique keys'] as $name )
				$sql .= "UNIQUE KEY `{$name}_UNIQUE` (`$name`),\n";
		}

		if ( $definition['foreign key constraints'] ) {
			$i = 1;
			foreach ( $definition['foreign key constraints'] as $column => $constraint ) {
				$foreign_table  = "$wpdb->prefix$constraint[0]";
				$foreign_column = $constraint[1];
				$fk_name = "fk_$wpdb->prefix{$table_name}_$i";
				$sql .= "CONSTRAINT `$fk_name` FOREIGN KEY (`$column`) REFERENCES `$foreign_table` (`$foreign_column`) ON DELETE NO ACTION ON UPDATE NO ACTION,\n";
				$i++;
			}
		}

		$sql = substr( $sql, 0, -2 ) . "\n";
		$sql .= ");\n";

		if ( $definition['many to many'] ) {
			foreach ( $definition['many to many'] as $m2m_table => $details ) {
				$column_name   = $details[0];
				$column        = $definition['columns'][$column_name];
				$target_table  = "$wpdb->prefix$details[1]";
				$target_column = $details[2];
				$type          = "$column[0]($column[1])";

				$sql .= "CREATE `$wpdb->prefix$m2m_table` (\n";
				$sql .= "`$column_name` $type NOT NULL,\n";
				$sql .= "`$target_column` $type NOT NULL\n";

				$fk_name_base = "fk_$wpdb->prefix{$m2m_table}";
				$sql .= "CONSTRAINT `{$fk_name_base}_1` FOREIGN KEY (`$column_name`) REFERENCES `$wpdb->prefix$table_name` (`$column_name`) ON DELETE NO ACTION ON UPDATE NO ACTION,\n";
				$sql .= "CONSTRAINT `{$fk_name_base}_2` FOREIGN KEY (`$target_column`) REFERENCES `$target_table` (`$target_column`) ON DELETE NO ACTION ON UPDATE NO ACTION,\n";
				$sql .= ");\n";
			}
		}

		// TODO: dependency order
		if ( method_exists( $Class, 'database_views' ) ) {
			$views = $Class::database_views();
			foreach ( $views as $name => $view )
				$sql .= "CREATE VIEW `$wpdb->prefix$name` AS\n$view;\n";
		}

		if ( method_exists( $Class, 'database_table_install_data' ) ) {
			$data = $Class::database_table_install_data();
			foreach ( $data as $row ) {
				$sql .= "INSERT INTO `$wpdb->prefix$table_name` values(";
				foreach ( $row as $value )
					$sql .= "'$value',";
				$sql  = substr( $sql, 0, -1 );
				$sql .= ");\n";
			}
		}

		$sql .= "\n\n";
		return $sql;
  }

  private static function uninstall_SQL( $Class ) {
		global $wpdb;

		$table_name = $Class::database_table_name();
		return "DROP TABLE `$wpdb->prefix$table_name`;";
	}

  function save( $update = FALSE, $depth = 0 ) {
		// TODO: mvoe this in to the CB2_DatabaseTable_PostNavigator Class
		// Save dependent leaf objects before saving this
		// will also reset the metadata for $post, e.g.
		//   $post->period_group_ID = CB2_CREATE_NEW => 800000034
		//   meta: period_group_ID:   CB2_CREATE_NEW => 800000034
		$native_ID            = NULL;
		$Class                = get_class( $this );
		$properties           = (array) $this;

		// TODO: sort this out: posts and zeros
		if ( isset( $properties['posts'] ) ) unset( $properties['posts'] );
		if ( isset( $properties['zeros'] ) ) unset( $properties['zeros'] );

		$class_database_table = CB2_Database::database_table( $Class );
		if ( ! $class_database_table )
			throw new Exception( "$Class [$this->ID/$depth] does not support save() because it has no database_table" );

		if ( CB2_DEBUG_SAVE ) {
			$top   = ! $depth;
			$class = ( $top ? '' : '-small' );
			if ( $top ) krumo( $properties );
			print( "<div class='cb2-WP_DEBUG$class'>" );
			if ( $depth ) print( '<span>' . str_repeat( '&nbsp;', $depth * 5 ) . "</span>$depth ⟿&nbsp;" );
			$update_string = ( $update ? 'UPDATE' : 'CREATE_ONLY' );
			print( "$Class::save($update_string)" );
			print( "</div>" );
		}

		if ( WP_DEBUG && $depth > 20 ) {
			krumo( $this );
			throw new Exception( 'depth exeeded 20 when saving' );
		}

		// ----------------------------------------- Recursive leaf first sub-object creation
		// These save() functions will populate their object's ID field
		// Let us not alter the array we are traversing
		foreach ( $this as $name => $value ) {
			if ( is_array( $value ) ) {
				$new_array = array();
				foreach ( $value as $name2 => $value2 ) {
					if ( $value2 instanceof CB2_DatabaseTable_PostNavigator ) {
						$value2->save( $update, $depth + 1 );
					}
					array_push( $new_array, $value2 );
				}
				$value = $new_array;
			} else {
				if ( $value instanceof CB2_DatabaseTable_PostNavigator )
					$value->save( $update, $depth + 1 );
			}

			$this->$name = $value;
		}

		// ----------------------------------------- Outer-object creation
		// Change Database data
		if ( $this->ID == CB2_CREATE_NEW ) {
			$field_data = CB2_Database::sanitize_data_for_table( $class_database_table, $properties, $formats );
			if ( CB2_DEBUG_SAVE ) krumo( $field_data, $formats );
			$ID         = $this->create( $field_data, $formats );
			$this->ID   = $ID;
			if ( CB2_DEBUG_SAVE )
				print( "<div class='cb2-WP_DEBUG-small'>created $Class(ID $ID)</div>" );
		} else {
			// TODO: data name conflicts?
			// e.g. PeriodEntity save() may also update the name of the fixed PeriodStatusType
			// because fields like name are everywhere
			// PHP object creation needs more intention annotation
			if ( $update ) {
				$field_data = CB2_Database::sanitize_data_for_table( $class_database_table, $properties, $formats );
				if ( CB2_DEBUG_SAVE ) krumo( $field_data, $formats );
				$this->update( $field_data, $formats );
				if ( CB2_DEBUG_SAVE )
					print( "<div class='cb2-WP_DEBUG-small'>updated $Class(ID $this->ID)</div>" );
			} else {
				if ( CB2_DEBUG_SAVE )
					print( "<div class='cb2-WP_DEBUG-small'>no update mode for $Class(ID $this->ID)</div>" );
			}
		}

		$this->post_post_update();

		return $this->ID;
	}

  protected function post_post_update() {
		// Examine many-to-many knowledge
  }

	protected function update( $update_data, $formats = NULL ) {
		global $wpdb;
		$class_database_table = CB2_Database::database_table( get_class( $this ) );
		if ( ! $class_database_table )
			throw new Exception( get_class( $this ) . ' does not support update() because it has no database_table' );

		$full_table = "$wpdb->prefix$class_database_table";
		$id_field   = CB2_Database::id_field( get_class( $this ) );
		$id         = $this->id();
		$where      = array( $id_field => $id );
		if ( CB2_DEBUG_SAVE ) print( '<div class="cb2-WP_DEBUG-small">' . get_class( $this ) . "::update($id_field=$id)</div>" );
		$result   = $wpdb->update(
			$full_table,
			$update_data,
			$where,
			$formats
		);
		if ( $result === 0 )
			if ( CB2_DEBUG_SAVE ) print( '<div class="cb2-WP_DEBUG-small cb2-warning">no rows updated</div>' );
		else if ( is_wp_error( $result ) || $result === FALSE ) {
			krumo( $result, $full_table, $update_data, $where, $formats );
			throw new Exception( "Update Error: $wpdb->last_error" );
		}

		return $this->ID;
	}

	protected function create( $insert_data, $formats = NULL ) {
		global $wpdb;
		$result = NULL;
		$class_database_table = CB2_Database::database_table( get_class( $this ) );
		if ( ! $class_database_table )
			throw new Exception( get_class( $this ) . ' does not support create() because it has no database_table' );

		$full_table = "$wpdb->prefix$class_database_table";

		// Support completely empty inserts (just an auto-increment)
		if ( CB2_DEBUG_SAVE ) print( '<div class="cb2-WP_DEBUG-small">' . get_class( $this ) . '::create()</div>' );
		if ( count( $insert_data ) )
			$result = $wpdb->insert( $full_table, $insert_data, $formats );
		else
			$result = $wpdb->query( "INSERT into `$full_table` values()" );
		if ( is_wp_error( $result ) || $result === FALSE || $result === 0 ) {
			krumo( $result, $insert_data, $formats );
			throw new Exception( "Update Error: $wpdb->last_error" );
		}

		$native_id = $wpdb->insert_id;
		$ID        = self::ID_from_id_post_type( $native_id, $this->post_type() );
		if ( CB2_DEBUG_SAVE )
			print( "<div class='cb2-WP_DEBUG-small'>$class_database_table::$native_id =&gt; $ID</div>" );

		return $ID;
	}
}
