<?php
class CB_PostNavigator {
  protected function __construct( &$posts = NULL ) {
    $this->zeros = array();
    if ( is_null( $posts ) ) $this->posts = &$this->zeros;
    else                     $this->posts = &$posts;

    // WP_Post default values
    if ( ! property_exists( $this, 'post_status' ) )   $this->post_status   = CB2_PUBLISH;
    if ( ! property_exists( $this, 'post_password' ) ) $this->post_password = '';
    if ( ! property_exists( $this, 'post_author' ) )   $this->post_author   = 1;
    if ( ! property_exists( $this, 'post_date' ) )     $this->post_date     = date( CB_Query::$datetime_format );
    if ( ! property_exists( $this, 'post_modified' ) ) $this->post_modified = date( CB_Query::$datetime_format );
    if ( ! property_exists( $this, 'post_excerpt' ) )  $this->post_excerpt  = $this->get_the_excerpt();
    if ( ! property_exists( $this, 'post_content' ) )  $this->post_content  = $this->get_the_content();
    if ( ! property_exists( $this, 'post_date_gmt' ) ) $this->post_date_gmt = $this->post_date;
    if ( ! property_exists( $this, 'post_modified_gmt' ) ) $this->post_modified_gmt = $this->post_modified;
		if ( ! property_exists( $this, 'filter' ) )        $this->filter = 'suppress'; // Prevent WP_Query from converting objects to WP_Post

    // This will cause subsequent WP_Post::get_instance() calls to return $this
    // rather than attempting to access the wp_posts table
    if ( property_exists( $this, 'ID' ) ) wp_cache_add( $this->ID, $this, 'posts' );
  }

  public function __toString() {return (string) $this->ID;}

  public function __toStringFor( $column_data_type, $column_name ) {
		return (string) $this->__toIntFor( $column_data_type, $column_name );
	}

	public function __toIntFor( $column_data_type, $column_name ) {
		return ( preg_match( '/_ids?$/', $column_name ) ? $this->id() : $this->ID );
	}

  function get_the_debug( $before = '', $after = '', $depth = 0, $object_ids = array() ) {
		global $CB2_POST_PROPERTIES;
		$classname = get_class( $this );

		if ( isset( $object_ids[$this->ID] ) ) {
			$debug = "<div class='cb2-warning'>recursion on $classname::$this->ID</div>";
		} else if ( $depth > 3 ) {
			$debug = "<div class='cb2-warning'>maxdepth exceeeded on $classname::$this->ID</div>";
		} else {
			$object_ids[$this->ID] = TRUE;

			$debug  = $before;
			$debug .= "<ul class='cb2-debug cb2-depth-$depth'>";
			$debug .= "<li class='cb2-classname'>$classname:</li>";
			foreach ( $this as $name => $value ) {
				if ( $name
					&& ( ! isset( $CB2_POST_PROPERTIES[$name] ) || $CB2_POST_PROPERTIES[$name] )
					&& ( ! in_array( $name, array( 'zeros', 'post_type' ) ) )
				) {
					if      ( $value instanceof DateTime ) $value = $value->format( CB_Query::$datetime_format );
					else if ( is_array( $value ) ) {
						/*
						$debug .= "<ul>";
						foreach ( $value as $value2 ) {
							$debug .= "<li>$value2</li>";
						}
						$debug .= "</ul>";
						*/
						$value = 'Array(' . count( $value ) . ')';
					} else if ( is_object($value) && method_exists( $value, 'get_the_debug' ) )
						$value = $value->get_the_debug( $before, $after, $depth + 1, $object_ids );
					$debug .= "<li><b>$name</b>: $value</li>";
				}
			}
			$debug .= '</ul>';
			$debug .= $after;
		}

    return $debug;
  }

  // ------------------------------------------------- Navigation
  function have_posts() {
    return current( $this->posts );
  }

  function next_post() {
    $post = current( $this->posts );
    next( $this->posts );
    return $post;
  }

  function rewind_posts() {
    reset( $this->posts );
    $this->post = NULL;
  }

  function &the_post() {
    global $post;
    $post = $this->next_post();
    // Some abstract post have equal IDs
    // e.g. CB_Week->ID 1 == CB_Day->ID 1
    // thus, we need to reset which one we are talking about constantly
    // the_title() => get_post() calls
    // will call get_instance(ID) and return our current post by pre-setting the cache
		wp_cache_set( $post->ID, $post, 'posts' );
    $this->setup_postdata( $post );
    return $post;
  }

  // ------------------------------------------------- Properties
  function is_feed()    {return FALSE;}
  function is_page()    {return FALSE;}
  function is_single()  {return FALSE;}
  function get( $name ) {return NULL;}

  function add_actions( &$actions, $post ) {}

  function setup_postdata( $post ) {
		global $id, $authordata, $currentday, $currentmonth, $page, $pages, $multipage, $more, $numpages;

    if ( ! $post ) {
        return;
    }

    $id = (int) $post->ID;

    $authordata = get_userdata($post->post_author);

    $currentday = mysql2date('d.m.y', $post->post_date, false);
    $currentmonth = mysql2date('m', $post->post_date, false);
    $numpages = 1;
    $multipage = 0;
    $page = $this->get( 'page' );
    if ( ! $page )
        $page = 1;

    /*
     * Force full post content when viewing the permalink for the $post,
     * or when on an RSS feed. Otherwise respect the 'more' tag.
     */
    if ( $post->ID === get_queried_object_id() && ( $this->is_page() || $this->is_single() ) ) {
        $more = 1;
    } elseif ( $this->is_feed() ) {
        $more = 1;
    } else {
        $more = 0;
    }

    $content = $post->post_content;
    if ( false !== strpos( $content, '<!--nextpage-->' ) ) {
        $content = str_replace( "\n<!--nextpage-->\n", '<!--nextpage-->', $content );
        $content = str_replace( "\n<!--nextpage-->", '<!--nextpage-->', $content );
        $content = str_replace( "<!--nextpage-->\n", '<!--nextpage-->', $content );

        // Ignore nextpage at the beginning of the content.
        if ( 0 === strpos( $content, '<!--nextpage-->' ) )
            $content = substr( $content, 15 );

        $pages = explode('<!--nextpage-->', $content);
    } else {
        $pages = array( $post->post_content );
    }

    /**
     * Filters the "pages" derived from splitting the post content.
     *
     * "Pages" are determined by splitting the post content based on the presence
     * of `<!-- nextpage -->` tags.
     *
     * @since 4.4.0
     *
     * @param array   $pages Array of "pages" derived from the post content.
     *                       of `<!-- nextpage -->` tags..
     * @param WP_Post $post  Current post object.
     */
    $pages = apply_filters( 'content_pagination', $pages, $post );

    $numpages = count( $pages );

    if ( $numpages > 1 ) {
        if ( $page > 1 ) {
            $more = 1;
        }
        $multipage = 1;
    } else {
        $multipage = 0;
    }

    /**
     * Fires once the post data has been setup.
     *
     * @since 2.8.0
     * @since 4.1.0 Introduced `$this` parameter.
     *
     * @param WP_Post  $post The Post object (passed by reference).
     * @param WP_Query $this The current Query object (passed by reference).
     */
    do_action_ref_array( 'the_post', array( &$post, &$this ) );

    return true;
	}

  function templates( $context = 'list', $type = NULL ) {
		$templates     = array();
		$post_type     = $this->post_type;

		// Gather possible combinations for this post_type
		// with sub-types
		$post_sub_type = $post_type;
		do {
			if ( $context && $type ) array_push( $templates, "$context-$post_sub_type-$type" );
			if ( $context )          array_push( $templates, "$context-$post_sub_type" );
			if ( $type )             array_push( $templates, "$post_sub_type-$type" );
			array_push( $templates, $post_sub_type );

			if ( strpos( $post_sub_type, '-' ) === FALSE ) $post_sub_type = NULL;
			else $post_sub_type = CB_Query::substring_before( $post_sub_type );
		} while ( $post_sub_type );

		// Sanitize
		$templates_valid   = array();
		$cb2_template_path = cb2_template_path();
		foreach ( $templates as $template ) {
			$template_path = "$cb2_template_path/$template.php";
			if ( file_exists( $template_path ) )
				array_push( $templates_valid, $template );
		}

		if ( ! count( $templates_valid) ) {
			$templates_tried = implode( ',', $templates );
			throw new Exception( "No valid templates found for [$post_type] in [$cb2_template_path].\nTried: $templates_tried" );
		}

		return $templates_valid;
	}

  // ------------------------------------------------- Output
	function move_column_to_end( &$columns, $column ) {
		if ( isset( $columns[$column] ) ) {
			$title = $columns[$column];
			unset( $columns[$column] );
			$columns[$column] = $title;
		}
	}

  function the_json_content( $options = NULL ) {
    print( $this->get_the_json_content( $options ) );
  }

  function get_the_json_content( $options = NULL ) {
    wp_json_encode( $this, $options );
  }

  function the_content( $more_link_text = null, $strip_teaser = false ) {
		// Borrowed from wordpress
		// https://developer.wordpress.org/reference/functions/the_content/
    $content = $this->get_the_content( $more_link_text, $strip_teaser );
    $content = apply_filters( 'the_content', $content );
    $content = str_replace( ']]>', ']]&gt;', $content );
    echo $content;
  }

  function get_the_edit_post_link( $text = null, $before = '', $after = '', $id = 0, $class = 'post-edit-link' ) {
		global $post;
		$old_post = $post;
		$post = $this;

		ob_start();
		edit_post_link( $text, $before, $after, $id, $class );
		$html = ob_get_contents();
		ob_end_clean();

		$post = $old_post;
		return $html;
  }

  function get_the_excerpt() {
		return '';
  }

  function get_the_content() {
		return '';
  }

  function classes() {
		return '';
  }

  // ------------------------------------------------- Saving
  function post_meta() {
		// Default to this
		// post_updated will sanitize the list against the database table columns and types
		global $CB2_POST_PROPERTIES;
		$meta = array();

		foreach ( $this as $name => $value ) {
			if ( ! is_null( $value )
				&& ! isset( $CB2_POST_PROPERTIES[$name] )
				&& ! in_array( $name, array( 'zeros', 'posts' ) ) // Inherited
			) {
				// meta value will get serialised if it is not a string
				$meta[ $name ] = CB_Query::to_string( $name, $value );
			}
		}

		return $meta;
	}

	function post_auto_draft_args() {
		return array(
			'post_status' => CB2_AUTODRAFT,
			'post_type'   => $this->post_type(),
		);
	}

  function post_args() {
		// Taken from https://developer.wordpress.org/reference/functions/wp_insert_post/
		$args = array(
			'post_title'  => ( property_exists( $this, 'name' ) ? $this->name : '' ),
			'post_status' => CB2_PUBLISH,
			'post_type'   => $this->post_type(),
			// 'meta_input'  => $this->post_meta(), // We are using CMB2

			/*
			'post_author' => 1,
			'post_content' => '',
			'post_content_filtered' => '',
			'post_excerpt' => '',
			'comment_status' => '',
			'ping_status' => '',
			'post_password' => '',
			'to_ping' =>  '',
			'pinged' => '',
			'post_parent' => 0,
			'menu_order' => 0,
			'guid' => '',
			'import_id' => 0,
			'context' => '',
			*/
		);

		if ( property_exists( $this, 'ID' ) && $this->ID )
			$args[ 'ID' ] = $this->ID;

		return $args;
  }

  function id( $why = '' ) {
		return CB_Query::id_from_ID_with_post_type( $this->ID, $this->post_type() );
  }

	static function ID_from_id_post_type( $id, $post_type ) {
		// NULL return indicates that this post_type is not governed by CB2
		$ID         = NULL;
		$post_types = CB_Query::get_post_types();

		if ( ! is_numeric( $id ) ) throw new Exception( "Numeric ID required for id_from_ID_with_post_type($id/$post_type)" );
		if ( ! $post_type )        throw new Exception( "Post type required for id_from_ID_with_post_type($id)" );

		if ( isset( $post_types[$post_type] ) ) {
			$details = $post_types[$post_type];
			if ( $id >= $details->ID_base ) throw new Exception( "[$post_type/$id] is already more than its ID_base [$details->ID_base]" );
			$ID      = $id * $details->ID_multiplier + $details->ID_base;
		}

		return $ID;
	}

  function post_post_update() {
  }

  function pre_post_create( $instance_container = NULL ) {
		// Automatic creation of all dependent objects
		// indicated by property = CB2_CREATE_NEW
		// using the properties attached to this post
		// this-><object_name>_ID(s) == CB2_CREATE_NEW:
		//   <object_name>     => new CB_<Class>(this)
		//   <object_name>_ID  => object->ID
		//   <object_name>_IDs => array( object->ID )
		// TODO: plural collections: CB2_CREATE_NEW_COLLECTION? e.g. period_IDs
		// TODO: change the CB2_CREATE_NEW to be independent from the text in the select box!!!!
		// Linking of 1-many sub-dependencies, e.g. PeriodGroup => Period,
		// should be done in post_post_update()
		global $CB2_POST_PROPERTIES, $post_save_processing;

		foreach ( $post_save_processing as $name => $value ) {
			$is_ID = ( substr( $name, -3 ) == '_ID' || substr( $name, -4 ) == '_IDs' );

			if ( $is_ID && $value == CB2_CREATE_NEW ) {
				$is_plural    = ( substr( $name, -1 ) == 's' );
				$base_name    = ( $is_plural ? substr( $name, 0, -1 ) : $name ); // period_group_ID
				$plural_name  = $base_name . 's';                     // period_group_IDs
				$object_name  = substr( $base_name, 0, -3 );          // period_group
				$object_names = $object_name . 's';                   // period_groups
				$post_type    = str_replace( '_', '', $object_name ); // periodgroup

				if ( $Class = CB_Query::schema_type_class( $post_type ) ) {
					if ( method_exists( $Class, 'factory_from_wp_post' ) ) {
						if ( CB2_DEBUG_SAVE ) print( "<h2>CB_PostNavigator[" . get_class( $this ) . "]::pre_post_create() dependencies [$name]: $object_name => $Class</h2>" );

						// Construct the new input data for the new CB2_Object
						// leave $new_post->ID so that create is triggered
						// leave $new_post->post_status = CB2_PUBLISH because save() manages that
						$new_post = new WP_Post( $post_save_processing );
						foreach ( $CB2_POST_PROPERTIES as $this_name => $native_relevant ) {
							if ( $native_relevant ) $new_post->$this_name = $this->$this_name;
						}
						$new_post->post_type = $post_type;

						// The required instantiation properties should be all in the post_save_processing
						// the Class::__construct() will complain / adjust if not
						$object   = $Class::factory_from_wp_post( $new_post, $instance_container );
						// save() will trigger pre_post_create() on this dependent object also
						// so CB_PeriodGroup will create CB_Period
						// Prevent any circular auto-creation
						unset( $post_save_processing->$name );
						$object->save();
						if ( ! property_exists( $object, 'ID' ) || ! $object->ID ) {
							krumo( $new_post, $object );
							throw new Exception( "$Class::factory_from_wp_post() failed to populate the ID" );
						}

						// Set values on the post_save_processing
						// for subsequent creation usage. ORDER is important!
						// so dependent objects should both create reactively
						$post_save_processing->$object_name  = $object;
						$post_save_processing->$object_names = array( $object );
						$post_save_processing->$base_name    = $object->ID;
						$post_save_processing->$plural_name  = array( $object->ID );

						// Set values on $this
						// because it still has to save itself
						$this->$object_name  = $object;
						$this->$object_names = array( $object );
						$this->$base_name    = $object->ID;
						$this->$plural_name  = array( $object->ID );
					} else throw new Exception( "Cannot create a new [$Class] from post because no factory_from_wp_post()" );
				} else throw new Exception( "Cannot create a new [$post_type] for [$name] because cannot find the Class handler" );
			}
		}
  }

  function save() {
		// Create dependent objects before moving in to the native tables
		// will also reset the metadata for $post, e.g.
		//   $post->period_group_ID = CB2_CREATE_NEW => 800000034
		//   meta: period_group_ID:   CB2_CREATE_NEW => 800000034
		// pre_post_create() will not work on the correct ID
		// because, by definition, it has not been created
		// so do not call $this->id() as it will fail
		global $post_save_processing;

		$native_ID            = NULL;
		$class_database_table = CB_Database::database_table( get_class( $this ) );
		if ( ! $class_database_table )
			throw new Exception( get_class( $this ) . ' does not support save() because it has no database_table' );
		$this->pre_post_create();

		// Move any new values created by pre_post_create()
		// to the main data array
		// TODO: pass them out from pre_post_create()?
		$potential_table_data = (array) $this;
		foreach ( $post_save_processing as $name => $value )
			$potential_table_data[$name] = $value;
		if ( CB2_DEBUG_SAVE ) krumo( $potential_table_data );
		$field_data = CB_Database::sanitize_data_for_table( $class_database_table, $potential_table_data, $formats );
		if ( CB2_DEBUG_SAVE ) krumo( $field_data, $formats );

		// Change Database data
		if ( $this->ID ) {
			$this->update( $field_data, $formats );
		} else {
			$native_ID = $this->create( $field_data, $formats );
			$this->ID = $native_ID;
		}

		$this->post_post_update();

		return $this->ID;
	}

	function update( $update_data, $formats = NULL ) {
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

	function create( $insert_data, $formats = NULL ) {
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
		$native_ID = self::ID_from_id_post_type( $native_id, $this->post_type() );
		if ( CB2_DEBUG_SAVE ) print( "<div class='cb2-WP_DEBUG-small'>$class_database_table::$native_id =&gt; $native_ID</div>" );

		return $native_ID;
	}
}

