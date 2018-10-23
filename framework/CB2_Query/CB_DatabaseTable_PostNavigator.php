<?php
abstract class CB_DatabaseTable_PostNavigator extends CB_PostNavigator {
	abstract function database_table_name();
	abstract function database_table_schema();

  private function runSQL() {
  }

  private function install() {
		$table_name = database_table_name();
		$definition = database_table_schema();
		$sql = "CREATE $table_name ";
		foreach ( $definition['columns'] as $column ) {
		}
		return $this->runSQL( $sql );
  }

  private function uninstall() {
		$table_name = database_table_name();
		$sql = "DROP TABLE $table_name";
		return $this->runSQL( $sql );
	}

  function save( $update = FALSE, $depth = 0 ) {
		// TODO: mvoe this in to the CB_DatabaseTable_PostNavigator Class
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

		$class_database_table = CB_Database::database_table( $Class );
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
					if ( $value2 instanceof CB_DatabaseTable_PostNavigator ) {
						$value2->save( $update, $depth + 1 );
					}
					array_push( $new_array, $value2 );
				}
				$value = $new_array;
			} else {
				if ( $value instanceof CB_DatabaseTable_PostNavigator )
					$value->save( $update, $depth + 1 );
			}

			$this->$name = $value;
		}

		// ----------------------------------------- Outer-object creation
		// Change Database data
		if ( $this->ID == CB2_CREATE_NEW ) {
			$field_data = CB_Database::sanitize_data_for_table( $class_database_table, $properties, $formats );
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
				$field_data = CB_Database::sanitize_data_for_table( $class_database_table, $properties, $formats );
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
		$class_database_table = CB_Database::database_table( get_class( $this ) );
		if ( ! $class_database_table )
			throw new Exception( get_class( $this ) . ' does not support update() because it has no database_table' );

		$full_table = "$wpdb->prefix$class_database_table";
		$id_field   = CB_Database::id_field( get_class( $this ) );
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
		$class_database_table = CB_Database::database_table( get_class( $this ) );
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
