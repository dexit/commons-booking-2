<?php
class CB2_DatabaseTable_PostNavigator extends CB2_PostNavigator {
	protected function __construct( &$posts = NULL ) {
		global $wpdb;

		if ( WP_DEBUG ) {
			$Class = get_class( $this );
			$class_database_table = CB2_Database::database_table( $Class );
			if ( $class_database_table ) {
				$full_table = "$wpdb->prefix$class_database_table";
				$id_field   = CB2_Database::id_field( $Class );

				// If we are instantiating with an ID then it must exist already in the Database
				if ( property_exists( $this, 'ID' ) && $this->ID && $this->ID != CB2_CREATE_NEW ) {
					if ( ! is_numeric( $this->ID ) )
						throw new Exception( "$Class has non-numeric ID [$this->ID]" );

					$id    = $this->id();
					$count = $wpdb->get_var( "select count(*) from $full_table where $id_field=$id" );
					if ( ! $count )
						throw new Exception( "$Class::__construct($id_field=[$id]) does not exist in DB!" );
				}
			}
		}

		parent::__construct( $posts );
	}

	// ------------------------------------------------------------------ Trashing
	function untrash() {
		// This sets enabled to FALSE
		// for deletion see delete()
		$Class = get_class( $this );
		if ( ! $this->can_trash() )
			throw new Exception( "This $Class cannot be untrashed" );

		return $this->trash_row_set( 1 );
	}

	function can_trash() {
		return CB2_Database::has_column( get_class( $this ), 'enabled' );
	}

	function trash() {
		// This sets enabled to FALSE
		// for deletion see delete()
		$Class = get_class( $this );
		if ( ! $this->can_trash() )
			throw new Exception( "This $Class cannot be trashed" );

		return $this->trash_row_set( 0 );
	}

	protected function trash_row_set( $value ) {
		global $wpdb;

		$Class        = get_class( $this );
		$class_database_table = CB2_Database::database_table( $Class );
		$id_field     = CB2_Database::id_field( $Class );
		$full_table   = "{$wpdb->prefix}$class_database_table";
		$sql          = $wpdb->prepare( "UPDATE $full_table set enabled = b'$value' where $id_field = %d", $this->id() );
		$result = $wpdb->query( $sql );
		if ( $result === 0 ) {
			if ( CB2_DEBUG_SAVE ) krumo( $result, $full_table, $sql );
			// Various concurrency issues could happen here so let us ignore it
			// krumo( $result, $full_table, $sql );
			// throw new Exception( "Failed to Trash $Class row. Maybe already trashed" );
		} else if ( is_wp_error( $result ) || $result === FALSE ) {
			krumo( $result, $full_table, $sql );
			throw new Exception( "Trash $Class Error: $wpdb->last_error" );
		}

		return TRUE;
	}

	// ------------------------------------------------------------------ Deleting
	function can_delete() {
		return TRUE;
	}

	function delete( $from = NULL, $depth = 0 ) {
		// Trash dependent leaf objects before trashing this
		$Class                = get_class( $this );
		$class_database_table = CB2_Database::database_table( $Class );
		$direct               = ( $depth == 0 );

		if ( ! $class_database_table )
			throw new Exception( "$Class [$this->ID/$depth] does not support delete() because it has no database_table" );

		if ( CB2_DEBUG_SAVE ) {
			$class  = ( $direct ? '' : '-small' );
			$indent = ( $depth ? '<span>' . str_repeat( '&nbsp;', $depth * 5 ) . "</span>$depth ⟿&nbsp;" : '' );
			print( "<div class='cb2-WP_DEBUG$class'>$indent$Class::delete()</div>" );
			if ( $direct ) krumo( $this );
		}

		if ( WP_DEBUG && $depth > 20 ) {
			krumo( $this );
			throw new Exception( 'depth exeeded 20 when saving' );
		}

		// ----------------------------------------- Outer-object
		// Change Database data
		if ( $this->pre_post_delete( $from, $direct ) ) {
			$this->delete_row( $from );

			// ----------------------------------------- Recursive delete()
			// Let us not alter the array we are traversing
			foreach ( $this as $name => $value ) {
				switch ( $name ) {
					case 'zeros':
					case 'posts':
						// TODO: formalise and centralise this posts, zeros calamity
						break;
					default:
						if ( is_array( $value ) ) {
							$new_array = array();
							foreach ( $value as $name2 => $value2 ) {
								if ( $value2 instanceof CB2_DatabaseTable_PostNavigator ) {
									$value2->delete( $this, $depth + 1 );
								}
								array_push( $new_array, $value2 );
							}
							$value = $new_array;
						} else {
							if ( $value instanceof CB2_DatabaseTable_PostNavigator )
								$value->delete( $this, $depth + 1 );
						}
				}
			}
		} else if ( CB2_DEBUG_SAVE ) {
			$reference_count = $this->reference_count();
			print( "<div class='cb2-WP_DEBUG$class'>" );
			print( "$indent$Class::delete() <span class='cb2-warning'>DENIED ($reference_count)</span>" );
			print( "</div>" );
		}

		$this->post_post_delete( $from, $direct );
	}

	protected function reference_count( $not_from = NULL ) {
		return 0;
	}

	protected function has_references( $throw = FALSE, $not_from = NULL ) {
		$reference_count = $this->reference_count( $not_from );
		if ( $reference_count && $throw ) {
			$Class = get_class( $this );
			$ID    = $this->ID;
			throw new Exception( "Cannot directly delete the $Class($ID) because it still has references" );
		}
		return ( $reference_count != 0 );
	}

	protected function post_post_delete( $from = NULL, $direct = TRUE ) {
	}

	protected function pre_post_delete( $from = NULL, $direct = TRUE ) {
		// By default we stop indirectly deleting things that are referenced
		// and throw if it is a direct request
		// e.g.
		//   A PeriodGroup deleting a Period will silently not delete the Period if it is referenced
		//   A direct Period deletion will throw an error if it has references
		$throw          = $direct;
		$has_references = $this->has_references( $throw );

		if ( CB2_DEBUG_SAVE && $has_references ) {
			$Class = get_class( $this );
			$ID    = $this->ID;
			print( "<div class='cb2-WP_DEBUG-small'>$Class($ID) has references</div>" );
		}

		$continue_with_delete = ! $has_references;
		return $continue_with_delete;
	}

	protected function delete_row( $from = NULL ) {
		global $wpdb;

		$Class = get_class( $this );
		$class_database_table = CB2_Database::database_table( $Class );
		if ( ! $class_database_table )
			throw new Exception( "$Class does not support delete() because it has no database_table" );

		$full_table = "$wpdb->prefix$class_database_table";
		$id_field   = CB2_Database::id_field( $Class );
		$id         = $this->id();
		$where      = array( $id_field => $id );
		if ( CB2_DEBUG_SAVE ) {
			print( "<div class='cb2-WP_DEBUG-small'>$Class::delete_row($id_field=$id)</div>" );
		}
		$result   = $wpdb->delete(
			$full_table,
			$where
		);

		if ( $result === 0 ) {
			krumo( $result, $full_table, $where );
			throw new Exception( "Failed to Delete $Class row" );
		} else if ( is_wp_error( $result ) || $result === FALSE ) {
			krumo( $result, $full_table, $where );
			throw new Exception( "Delete $Class Error: $wpdb->last_error" );
		}

		return TRUE;
	}

	// ------------------------------------------------------------------ Saving
  function save( $update = FALSE, $fire_wordpress_events = TRUE, $depth = 0, $debug = NULL ) {
		// Save dependent leaf objects before saving this
		// will also reset the metadata for $post, e.g.
		//   $post->period_group_ID = CB2_CREATE_NEW => 800000034
		//   meta: period_group_ID:   CB2_CREATE_NEW => 800000034
		$native_ID               = NULL;
		$Class                   = get_class( $this );
		$properties              = (array) $this;
		$properties['author_ID'] = get_current_user_id(); // Always send the user if the database table accepts

		// TODO: sort this out: posts and zeros
		if ( isset( $properties['posts'] ) ) unset( $properties['posts'] );
		if ( isset( $properties['zeros'] ) ) unset( $properties['zeros'] );

		$class_database_table = CB2_Database::database_table( $Class );
		if ( ! $class_database_table )
			throw new Exception( "$Class [$this->ID/$depth] does not support save() because it has no database_table" );
		if ( ! $this->is_saveable() )
			throw new Exception( "$Class [$this->ID/$depth] is not saveable" );

		if ( CB2_DEBUG_SAVE ) {
			$top   = ! $depth;
			$class = ( $depth ? '-small' : '' );
			if ( $top ) krumo( $properties );
			print( "<div class='cb2-WP_DEBUG$class'>" );
			if ( $depth ) print( '<span>' . str_repeat( '&nbsp;', $depth * 5 ) . "</span>$depth ⟿&nbsp;" );
			$update_string = ( $update ? 'UPDATE' : 'CREATE_ONLY' ) . ( $fire_wordpress_events ? ' with wordpress events' : '' );
			print( "{$Class}[$this->ID]::save($update_string) $debug" );
			print( "</div>" );
		}

		if ( WP_DEBUG && $depth > 20 ) {
			krumo( $this );
			throw new Exception( 'depth exeeded 20 when saving' );
		}

		// ----------------------------------------- Recursive leaf first sub-object
		// These save() functions will populate their object's ID field
		// Let us not alter the array we are traversing
		foreach ( $this as $name => $value ) {
			if ( is_array( $value ) ) {
				$new_array = array();
				$i         = 1;
				$count     = count( $value );
				foreach ( $value as $name2 => $array_value ) {
					if ( $array_value instanceof CB2_DatabaseTable_PostNavigator && $array_value->is_saveable() ) {
						$array_value->save( $update, $fire_wordpress_events, $depth + 1, "$name => Array($count)[$i]" );
					}
					array_push( $new_array, $array_value );
					$i++;
				}
				$value = $new_array;
			} else {
				if ( $value instanceof CB2_DatabaseTable_PostNavigator && $value->is_saveable() )
					$value->save( $update, $fire_wordpress_events, $depth + 1 );
			}

			// Re-write the object live for outer saving
			$this->$name = $value;
		}

		// ----------------------------------------- Outer-object
		// Change Database data
		if ( $this->ID == CB2_CREATE_NEW ) {
			$field_data = CB2_Database::sanitize_data_for_table( $Class, $properties, $formats );
			if ( CB2_DEBUG_SAVE ) krumo( $field_data, $formats );
			$ID         = $this->create_row( $field_data, $formats, $fire_wordpress_events );
			$this->ID   = $ID;
			if ( CB2_DEBUG_SAVE )
				print( "<div class='cb2-WP_DEBUG-small'>created $Class(ID $ID)</div>" );
		} else {
			// TODO: data name conflicts? e.g. both CB2_PeriodGroup and CB2_PeriodEntity have @name attributes
			// e.g. PeriodEntity save() may also update the name of the fixed PeriodStatusType
			// because fields like name are everywhere
			// PHP object creation needs more intention annotation
			if ( $update ) {
				$field_data = CB2_Database::sanitize_data_for_table( $Class, $properties, $formats );
				if ( CB2_DEBUG_SAVE ) krumo( $field_data, $formats );
				$this->update_row( $field_data, $formats, $fire_wordpress_events );
			} else {
				if ( CB2_DEBUG_SAVE )
					print( "<div class='cb2-WP_DEBUG-small'>no update mode for $Class(ID $this->ID)</div>" );
			}
		}

		$this->post_post_update();

		return $this->ID;
	}

  protected function post_post_update() {
		// TODO: Examine many-to-many knowledge
  }

	protected function custom_events( $update ) {
		// Pure Virtual
	}

	protected function update_row( $update_data, $formats = NULL, $fire_wordpress_events = TRUE ) {
		global $wpdb;

		// TODO: post_updated WordPress event $post_before parameter not supported currently
		$post_before = $this; //( $fire_wordpress_events ? CB2_Query::get_post_with_type( $this->post_type, $this->ID ) : NULL );
		$Class       = get_class( $this );

		$class_database_table = CB2_Database::database_table( $Class );
		if ( ! $class_database_table )
			throw new Exception( "$Class does not support update() because it has no database_table" );

		$full_table = "$wpdb->prefix$class_database_table";
		$id_field   = CB2_Database::id_field( $Class );
		$id         = $this->id();
		$where      = array( $id_field => $id );
		if ( CB2_DEBUG_SAVE )
			print( "<div class='cb2-WP_DEBUG-small'>$Class::update($id_field=$id)</div>" );
		$result   = $wpdb->update(
			$full_table,
			$update_data,
			$where,
			$formats
		);

		if ( $result === 0 ) {
			// Nothing to update
			// krumo( $result, $full_table, $where );
			// throw new Exception( "Failed to Update $Class row" );
			if ( CB2_DEBUG_SAVE ) {
				$count = $wpdb->get_var( "select count(*) from $full_table where $id_field=$id" );
				if ( $count )
					print( "<div class='cb2-WP_DEBUG-small'>$Class row not updated. Probably no changes.</div>" );
				else
					print( "<div class='cb2-WP_DEBUG-small' style='color:red;'>$Class row not updated. $id_field=[$id] does not exist!</div>" );
			}
		} else if ( is_wp_error( $result ) || $result === FALSE ) {
			krumo( $result, $full_table, $where );
			throw new Exception( "Update $Class Error: $wpdb->last_error" );
		} else {
			if ( CB2_DEBUG_SAVE )
				print( "<div class='cb2-WP_DEBUG-small' style='font-weight:bold;'>$Class updated. $id_field=[$id]</div>" );
		}

		if ( $fire_wordpress_events ) {
			$post_ID         = $this->ID;
			$post            = $this;
			$post->post_type = $this->post_type();
			$update          = TRUE;
			$post_after      = $this;

			// CB2 events
			$this->custom_events( TRUE );
			do_action( 'cb2_data_change', TRUE );

			// Copied from post.php
			if ( CB2_DEBUG_SAVE ) print( "<div class='cb2-WP_DEBUG-small'>$Class update fireing WordPress event edit_post</div>" );
			do_action( 'edit_post', $post_ID, $post );
			if ( CB2_DEBUG_SAVE ) print( "<div class='cb2-WP_DEBUG-small'>$Class update fireing WordPress event post_updated</div>" );
			do_action( 'post_updated', $post_ID, $post_after, $post_before);
			if ( CB2_DEBUG_SAVE ) print( "<div class='cb2-WP_DEBUG-small'>$Class update fireing WordPress event save_post_{$post->post_type}</div>" );
			do_action( "save_post_{$post->post_type}", $post_ID, $post, $update );
			if ( CB2_DEBUG_SAVE ) print( "<div class='cb2-WP_DEBUG-small'>$Class update fireing WordPress event save_post</div>" );
			do_action( 'save_post', $post_ID, $post, $update );
			if ( CB2_DEBUG_SAVE ) print( "<div class='cb2-WP_DEBUG-small'>$Class update fireing WordPress event wp_insert_post</div>" );
			do_action( 'wp_insert_post', $post_ID, $post, $update );
		}

		return $this->ID;
	}

	protected function create_row( $insert_data, $formats = NULL, $fire_wordpress_events = TRUE ) {
		global $wpdb;

		$result               = NULL;
		$Class                = get_class( $this );
		$class_database_table = CB2_Database::database_table( $Class );
		if ( ! $class_database_table )
			throw new Exception( "$Class does not support create() because it has no database_table" );

		$full_table = "$wpdb->prefix$class_database_table";

		// Support completely empty inserts (just an auto-increment)
		if ( CB2_DEBUG_SAVE ) print( "<div class='cb2-WP_DEBUG-small'>$Class::create()</div>" );
		if ( count( $insert_data ) )
			$result = $wpdb->insert( $full_table, $insert_data, $formats );
		else
			$result = $wpdb->query( "INSERT into `$full_table` values()" );

		if ( $result === 0 ) {
			krumo( $result, $full_table, $where );
			throw new Exception( "Failed to Create $Class row" );
		} else if ( is_wp_error( $result ) || $result === FALSE ) {
			krumo( $result, $full_table, $where );
			throw new Exception( "Create $Class Error: $wpdb->last_error" );
		}

		$native_id = $wpdb->insert_id;
		$this->ID  = self::ID_from_id_post_type( $native_id, $this->post_type() );
		if ( CB2_DEBUG_SAVE )
			print( "<div class='cb2-WP_DEBUG-small'>$class_database_table::$native_id =&gt; $this->ID</div>" );

		if ( $fire_wordpress_events ) {
			$post_ID         = $this->ID;
			$post            = $this;
			$post->post_type = $this->post_type();
			$update          = FALSE;

			// CB2 events
			$this->custom_events( FALSE );
			do_action( 'cb2_data_change', FALSE );

			// Copied from post.php
			if ( CB2_DEBUG_SAVE ) print( "<div class='cb2-WP_DEBUG-small'>$Class create fireing WordPress event save_post_{$post->post_type}</div>" );
			do_action( "save_post_{$post->post_type}", $post_ID, $post, $update );
			if ( CB2_DEBUG_SAVE ) print( "<div class='cb2-WP_DEBUG-small'>$Class create fireing WordPress event save_post</div>" );
			do_action( 'save_post', $post_ID, $post, $update );
			if ( CB2_DEBUG_SAVE ) print( "<div class='cb2-WP_DEBUG-small'>$Class create fireing WordPress event wp_insert_post</div>" );
			do_action( 'wp_insert_post', $post_ID, $post, $update );
		}

		return $this->ID;
	}
}
