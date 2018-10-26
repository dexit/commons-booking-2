<?php
abstract class CB2_PostNavigator {
  public static $database_table = 'cb2_post_types';

  static function database_table_name() { return self::$database_table; }

  static function database_table_schema( $prefix ) {
		return array(
			'name'    => self::$database_table,
			'columns' => array(
				'post_type_id'  => array( INT,     (11), UNSIGNED, NOT_NULL, AUTO_INCREMENT ),
				'post_type'     => array( VARCHAR, (20), NULL,     NOT_NULL ),
				'ID_base'       => array( BIGINT,  (20), UNSIGNED, NOT_NULL ),
				'ID_multiplier' => array( BIGINT,  (20), UNSIGNED, NOT_NULL, NULL, '1' ),
			),
			'primary key' => array( 'post_type_id' ),
			'unique keys' => array(
				'post_type',
				'post_type_id',
				'ID_base'
			),
		);
	}

	static function database_data() {
		return array(
			array( '1', 'period', '200000000', '1' ),
			array( '2', 'periodgroup', '800000000', '1' ),
			array( '3', 'perioditem-automatic', '300000000', '10000' ),
			array( '4', 'perioditem-global', '400000000', '10000' ),
			array( '5', 'perioditem-location', '500000000', '10000' ),
			array( '6', 'perioditem-timeframe', '600000000', '10000' ),
			array( '7', 'perioditem-user', '700000000', '10000' ),
			array( '8', 'periodstatustype', '100000000', '1' ),
			array( '12', 'periodent-global', '900000000', '1' ),
			array( '13', 'periodent-location', '1000000000', '1' ),
			array( '14', 'periodent-timeframe', '1100000000', '1' ),
			array( '15', 'periodent-user', '1200000000', '1' ),
		);
	}

  protected function __construct( &$posts = NULL ) {
    $this->zeros = array(); // TODO: re-evaluate this: does it collect stuff?
    if ( is_null( $posts ) ) $this->posts = &$this->zeros;
    else                     $this->posts = &$posts;

    // WP_Post default values
    if ( ! property_exists( $this, 'post_status' ) )   $this->post_status   = CB2_Post::$PUBLISH;
    if ( ! property_exists( $this, 'post_password' ) ) $this->post_password = '';
    if ( ! property_exists( $this, 'post_author' ) )   $this->post_author   = 1;
    if ( ! property_exists( $this, 'post_date' ) )     $this->post_date     = date( CB2_Query::$datetime_format );
    if ( ! property_exists( $this, 'post_modified' ) ) $this->post_modified = date( CB2_Query::$datetime_format );
    if ( ! property_exists( $this, 'post_date_gmt' ) ) $this->post_date_gmt = $this->post_date;
    if ( ! property_exists( $this, 'post_modified_gmt' ) ) $this->post_modified_gmt = $this->post_modified;
		if ( ! property_exists( $this, 'filter' ) )        $this->filter = 'suppress'; // Prevent WP_Query from converting objects to WP_Post
		// We do not populate these here,
		// they would be called explicitly
		// populating them can cause loops
		// because get_the_content() can also __contruct() this
		// indirectly through contruct things that has-a
    if ( ! property_exists( $this, 'post_excerpt' ) )  $this->post_excerpt  = NULL; //$this->get_the_excerpt();
    if ( ! property_exists( $this, 'post_content' ) )  $this->post_content  = NULL; //$this->get_the_content();

    // This will cause subsequent WP_Post::get_instance() calls to return $this
    // rather than attempting to access the wp_posts table
    if ( property_exists( $this, 'ID' ) ) wp_cache_add( $this->ID, $this, 'posts' );
  }

  function is( CB2_PostNavigator $post_navigator ) {
		return $this->ID == $post_navigator->ID;
  }

  function remove_post( $post_remove ) {
		$new_posts = array();
		foreach ( $this->posts as $post ) {
			$is_remove_post = ( $post instanceof CB2_PostNavigator && $post->is( $post_remove ) );
			if ( ! $is_remove_post ) array_push( $new_posts, $post );
		}
		$this->posts = $new_posts;
  }

	// -------------------------------------------------------------------- Reflection
  // Class lookups
  static function post_type_Class( $post_type ) {
		$post_types = self::post_type_classes();
		return isset( $post_types[$post_type] ) ? $post_types[$post_type] : NULL;
  }

  static function post_type_all_objects( $post_type, $values_only = TRUE ) {
		$all = NULL;
		if ( $Class = self::post_type_Class( $post_type ) ) {
			if ( property_exists( $Class, 'all' ) ) {
				if ( $values_only ) $all = array_values( $Class::$all );
				else $all = $Class::$all;
			}
		}
		return $all;
  }

  static function post_type_classes() {
		static $schema_types = NULL;

		if ( is_null( $schema_types ) ) {
			$schema_types = array();
			foreach ( get_declared_classes() as $Class ) { // PHP 4
				$ReflectionClass = new ReflectionClass( $Class );
				if ( $ReflectionClass->isSubclassOf( 'CB2_PostNavigator' ) // PHP 5
					&& property_exists( $Class, 'static_post_type' )
				) {
					if ( strlen( $Class::$static_post_type ) > 20 )
						throw new Exception( 'post_type [' . $Class::$static_post_type . '] is longer than the WordPress maximum of 20 characters' );
					$schema_types[ $Class::$static_post_type ] = $Class;
				}
			}
    }

    return $schema_types;
  }

  protected static function class_from_ID_property_name( $ID_property_name, &$property_name = FALSE, &$is_plural = NULL, $Class = FALSE ) {
		// TODO: factory_subclass() for polymorphic pointers like period_entity_ID
		$is_ID         = ( substr( $ID_property_name, -3 ) == '_ID' || substr( $ID_property_name, -4 ) == '_IDs' );
		// If the caller was just asking about the property then only return FALSE
		$no_excpeption = ( $property_name === FALSE );

		if ( $is_ID ) {
			$is_plural     = ( substr( $ID_property_name, -1 ) == 's' );
			$base_name     = ( $is_plural ? substr( $ID_property_name, 0, -1 ) : $ID_property_name ); // period_group_ID
			$property_type = substr( $base_name, 0, -3 );                // period_group
			$property_name = $property_type . ( $is_plural ? 's' : '' ); // period_group(s)

			if ( $Class === FALSE ) {
				$post_type     = str_replace( '_', '', $property_type );     // periodgroup
				$Class         = self::post_type_Class( $post_type );   // CB2_PeriodGroup
			}
			if ( ! $Class && ! $no_excpeption )
				throw new Exception( "[$ID_property_name/$post_type] has no direct Class" );
		} else {
			if ( ! $no_excpeption )
				throw new Exception( "[$ID_property_name] is not an ID property name" );
		}

		// FALSE indicates not an ID thing
		return $Class;
  }

  protected static function get_or_create_new( &$properties, $ID_property_name, &$instance_container = NULL, $Class = FALSE ) {
		// "get":    the database record(s) exist already and we have their ID
		// "create": create a placeholder object that will be saved to the database later, ID = 0
		// If $ID_property_name is plural, an array will be returned
		$TargetClass = self::class_from_ID_property_name( $ID_property_name, $object_property_name, $plural, $Class );

		if ( ! isset( $properties[ $ID_property_name ] ) )
			throw new Exception( "$ID_property_name required" );

		$property_ID_value = $properties[ $ID_property_name ];

		if ( is_object( $property_ID_value ) )
			throw new Exception( "value for [$ID_property_name] is already an object. get_or_create_new() is for IDs" );

		if ( $plural ) {
			// -------------------------------------------------------- Plural
			$object = array();

			// Cumulative: if the object property is already set, then add to it
			if ( isset( $properties[ $object_property_name ] ) ) {
				$object = $properties[ $object_property_name ];
				if ( ! is_array( $object ) ) {
					krumo( $object );
					throw new Exception( "existing $TargetClass::$object_property_name needs to be an array" );
				}
			}

			if ( ! is_array( $property_ID_value ) ) {
				krumo( $property_ID_value );
				throw new Exception( "$ID_property_name needs to be an array" );
			}
			foreach ( $property_ID_value as $ID_value) {
				if ( ! is_numeric( $ID_value ) ) {
					krumo( $ID_value );
					throw new Exception( "$ID_property_name must be numeric" );
				}
				$ID_value  = (int) $ID_value;

				$subobject = ( $ID_value == CB2_CREATE_NEW
					? $TargetClass::factory_from_properties( $properties, $instance_container )
					: CB2_Query::get_post_with_type( $TargetClass::$static_post_type, $ID_value, $instance_container )
				);
				array_push( $object, $subobject );
			}
		} else {
			// -------------------------------------------------------- Singular
			if ( ! is_numeric( $property_ID_value ) ) {
				krumo( $property_ID_value );
				throw new Exception( "$ID_property_name must be numeric" );
			}
			$property_ID_value = (int) $property_ID_value;
			$object            = ( $property_ID_value == CB2_CREATE_NEW
				? $TargetClass::factory_from_properties( $properties, $instance_container )
				: CB2_Query::get_post_with_type( $TargetClass::$static_post_type, $property_ID_value, $instance_container )
			);
		}

		// Maybe plural = (array)
		$properties[ $object_property_name ] = $object;

		// Maybe array
		return $object;
	}

	static function copy_all_wp_post_properties( $properties, $object, $overwrite = TRUE ) {
		// Important to overwrite
		// because these objects are CACHED
		if ( is_null( $properties ) )    throw new Exception( 'copy_all_wp_post_properties( $properties null )' );
		if ( is_object( $properties ) )  throw new Exception( 'copy_all_wp_post_properties( $properties is an object )' );
		if ( ! is_array( $properties ) ) throw new Exception( 'copy_all_wp_post_properties( $properties is not an array )' );

		if ( WP_DEBUG ) {
			foreach ( CB2_Post::$POST_PROPERTIES as $name => $native_relevant )
				if ( ! isset( $properties, $name ) )
					throw new Exception( "WP_Post->[$name] does not exist on source post" );
		}

		foreach ( $properties as $name => $from_value ) {
			$wp_is_post_property = isset( CB2_Post::$POST_PROPERTIES[$name] );
			if ( $wp_is_post_property ) {
				try {
					$new_value = CB2_Query::to_object( $name, $from_value );
				} catch ( Exception $ex ) {
					krumo( $properties );
					throw $ex;
				}

				if ( $overwrite || ! property_exists( $object, $name ) ) {
					if ( WP_DEBUG && FALSE ) {
						if ( property_exists( $object, $name ) ) {
							$old_value = $object->$name;
							if ( ! is_null( $old_value ) && $old_value != $new_value )
								print( "copy_all_wp_post_properties( [$old_value] => [$new_value] )" );
						}
					}
					$object->$name = $new_value;
				}
			}
		}
	}

  public function __toString() {return (string) $this->ID;}

  public function __toStringFor( $column_data_type, $column_name ) {
		return (string) $this->__toIntFor( $column_data_type, $column_name );
	}

	public function __toIntFor( $column_data_type, $column_name ) {
		return ( preg_match( '/_ids?$/', $column_name ) ? $this->id() : $this->ID );
	}

  function get_the_debug( $before = '', $after = '', $depth = 0, $object_ids = array() ) {
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
					&& ( ! isset( CB2_Post::$POST_PROPERTIES[$name] ) || CB2_Post::$POST_PROPERTIES[$name] )
					&& ( ! in_array( $name, array( 'zeros', 'post_type' ) ) )
				) {
					if      ( $value instanceof DateTime ) $value = $value->format( CB2_Query::$datetime_format );
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
    // e.g. CB2_Week->ID 1 == CB2_Day->ID 1
    // thus, we need to reset which one we are talking about constantly
    // the_title() => get_post() calls
    // will call get_instance(ID) and return our current post by pre-setting the cache
		wp_cache_set( $post->ID, $post, 'posts' );
    $this->setup_postdata( $post );
    return $post;
  }

  function clone_with_create_new( $name = NULL, $deep = TRUE, Array $args = NULL ) {
		// Set all IDs to CB2_CREATE_NEW so the whole object is re-created
		// ready for saving
		$copy = clone $this;
		$copy->ID = CB2_CREATE_NEW;
		if ( $name ) $copy->name = $name;
		if ( is_array( $args ) )
			foreach ( $args as $property_name => $value )
				$copy->$property_name = $value;

		if ( $deep ) {
			foreach ( $copy as &$value ) {
				if ( is_array( $value ) ) {
					foreach ( $value as &$value2 ) {
						if ( is_object( $value2 ) && method_exists( $value2, 'clone_with_create_new' ) )
							$value2 = $value2->clone_with_create_new( $name );
					}
				} else if ( is_object( $value ) && method_exists( $value, 'clone_with_create_new' ) ) {
					$value = $value->clone_with_create_new( $name );
				}
			}
		}

		return $copy;
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
			else $post_sub_type = CB2_Query::substring_before( $post_sub_type );
		} while ( $post_sub_type );

		// Sanitize
		$templates_valid   = array();
		$cb2_template_path = CB2::template_path();
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

	function get_the_after_content() {
		return '';
	}

	function process_form( $action, $values, $user = NULL ) {
		$html ='';
		$handler_function = "process_form_{$action}";
		if ( method_exists( $this, $handler_function ) ) {
			// $_POST has happened for this item
			$html = $this->$handler_function( $action, $values, $user );
		} else if ( WP_DEBUG ) throw new Exception( "handler [$handler_function] not found" );
		return $html;
	}

	function get_the_content() {
		static $form_content = NULL; // Ensure this happens only once
		$content = '';

		if ( is_null( $form_content ) ) {
			if ( $this->is_controller() ) {
				$action   = ( isset( $_POST['action'] ) ? $_POST['action'] : NULL );
				$user     = CB2_User::factory_current();
				$values   = array();
				foreach ( $_POST as $name => $value ) {
					$interpret_func = 'post_' . preg_replace( '/[^a-zA-Z0-9_]/', '_', $name ). '_interpret';
					$values[$name]  = ( method_exists( $this, $interpret_func ) ? $this->$interpret_func( $value ) : $value );
				}
				$form_content = $this->process_form( $action, $values, $user );
			}
		}
		$content .= $form_content;

		if ( property_exists( $this, 'post_content' ) ) $content .= $this->post_content;
		$content .= $this->get_the_after_content();
		return $content;
	}

  function classes() {
		return '';
  }

  // ------------------------------------------------- Saving
  function is_controller() {
		$is_controller = FALSE;
		if ( ! property_exists( $this, '_was_controller' ) ) {
			if ( isset( $_POST['controller'] ) && isset( $_POST['ID'] ) ) {
				$controller    = $_POST['controller'];
				$_POST_ID      = $_POST['ID'];
				$Class         = get_class( $this );
				$is_controller = ( $controller == $Class && $_POST_ID == $this->ID );
				$this->_was_controller = TRUE;
			}
		}
		return $is_controller;
  }

	static function get_post_type_setup() {
		global $wpdb;
		static $post_types = NULL;

		if ( is_null( $post_types ) )
			$post_types = $wpdb->get_results( "SELECT post_type, ID_multiplier, ID_base FROM {$wpdb->prefix}cb2_post_types ORDER BY ID_base DESC", OBJECT_K );

		return $post_types;
	}

  function id( $why = '' ) {
		return self::id_from_ID_with_post_type( $this->ID, $this->post_type() );
  }

	static function id_from_ID_with_post_type( $ID, $post_type ) {
		// NULL return indicates that this post_type is not governed by CB2
		$id         = NULL;
		$post_types = self::get_post_type_setup();

		if ( ! is_numeric( $ID ) ) throw new Exception( "Numeric ID required for id_from_ID_with_post_type($ID/$post_type)" );
		if ( ! $post_type )        throw new Exception( "Post type required for id_from_ID_with_post_type($ID)" );

		if ( isset( $post_types[$post_type] ) ) {
			$details = $post_types[$post_type];
			if ( $details->ID_base > $ID ) throw new Exception( "Negative id from ID [$ID/$post_type] with [$details->ID_base/$details->ID_multiplier]" );
			$id      = ( $ID - $details->ID_base ) / $details->ID_multiplier;
		}

		return $id;
	}

	function post_perioditem_user_IDs_interpret( $perioditem_user_IDs ) {
		return $this->get_posts_with_type( $perioditem_user_IDs, 'perioditem-user' );
	}

	function post_perioditem_timeframe_IDs_interpret( $perioditem_timeframe_IDs ) {
		return $this->get_posts_with_type( $perioditem_timeframe_IDs, 'perioditem-timeframe' );
	}

	function get_posts_with_type( $post_IDs, $post_type ) {
		$objects = array();
		foreach ( $post_IDs as $post_ID ) {
			$object = CB2_Query::get_post_with_type( $post_type, $post_ID );
			array_push( $objects, $object );
		}
		return $objects;
	}

	static function ID_from_id_post_type( $id, $post_type ) {
		// NULL return indicates that this post_type is not governed by CB2
		$ID         = NULL;
		$post_types = self::get_post_type_setup();

		if ( ! is_numeric( $id ) ) throw new Exception( "Numeric ID required for id_from_ID_with_post_type($id/$post_type)" );
		if ( ! $post_type )        throw new Exception( "Post type required for id_from_ID_with_post_type($id)" );

		if ( isset( $post_types[$post_type] ) ) {
			$details = $post_types[$post_type];
			if ( $id >= $details->ID_base ) throw new Exception( "[$post_type/$id] is already more than its ID_base [$details->ID_base]" );
			$ID      = $id * $details->ID_multiplier + $details->ID_base;
		}

		return $ID;
	}
}

