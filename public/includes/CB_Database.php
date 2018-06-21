<?php
// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_Database {
  static $database_date_format = 'Y-m-d H:i:s';

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
  static function to_string( $value ) {
    if ( is_object( $value ) ) {
      switch ( get_class( $value ) ) {
        case 'DateTime':
          $value = $value->format( self::$database_date_format );
          break;
      }
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

  function add_condition( $field, $value, $allow_nulls = FALSE, $comparison = '=', $prepare = TRUE ) {
    global $wpdb;
    $condition = ( $prepare ? $wpdb->prepare( "$field $comparison %s", $value ) : "$field $comparison $value" );
    if ( $allow_nulls ) $condition = "(isnull($field) or $condition)";
    array_push( $this->conditions, $condition );
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
    return $wpdb->query( $this->prepare( $arg1, $arg2, $arg3, $arg4, $arg5, $arg6 ) );
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
    $this->fields        = array( '*' );
    $this->joins         = array();
    $this->conditions    = array();
    $this->orderby_array = array();
  }

  static function factory( $table, $alias = NULL ) {
    return new self( $table, $alias );
  }

  function add_orderby( $name, $table_alias = NULL ) {
    $full_name = ( $table_alias ? "$table_alias.$name" : $name );
    array_push( $this->orderby_array, $full_name );
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

  function add_condition( $field, $value, $allow_nulls = FALSE, $comparison = '=', $prepare = TRUE ) {
    global $wpdb;
    $condition = ( $prepare ? $wpdb->prepare( "$field $comparison %s", $value ) : "$field $comparison $value" );
    if ( $allow_nulls ) $condition = "(isnull($field) or $condition)";
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

    if ( count( $this->orderby_array ) ) {
      $this->order_sql = implode( ', ', $this->orderby_array );
      if ( $this->order_direction ) $this->order_sql .= " $this->order_direction";
      $this->order_sql = "order by $this->order_sql";
    }

    $this->sql = $wpdb->prepare(
      self::$database_command . " $this->field_sql
      FROM $wpdb->prefix$this->table
        $this->join_sql
      where
        $this->condition_sql
        $this->order_sql",
      $arg1, $arg2 //TODO: add other args
    );

    return $this->sql;
  }

  function run( $arg1 = NULL, $arg2 = NULL, $arg3 = NULL, $arg4 = NULL, $arg5 = NULL, $arg6 = NULL ) {
    return $this->get_results( $this->prepare( $arg1, $arg2, $arg3, $arg4, $arg5, $arg6 ) );
  }

  function get_results( $sql ) {
    global $wpdb;
    if ( WP_DEBUG ) print( "<div class='cb2-debug cb2-sql'><pre>$sql</pre></div>" );
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
      $this->fields[$name]  = CB_Database::to_string( $value );
      $this->formats[$name] = ( is_null( $format ) ? '%s' : $format );
    }
  }

  function run() {
    global $wpdb;

    if ( WP_DEBUG ) {
      print( "<div class='cb2-debug cb2-sql'><h2>WP_DEBUG insert SQL: $this->table</h2><pre>" );
      print_r( $this->fields );
      print_r( $this->formats );
      print( '</pre></div>' );
    }

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

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_PostNavigator {
  protected function __construct( &$posts = NULL ) {
    $this->zero_array = array();
    if ( is_null( $posts ) ) $this->posts = &$this->zero_array;
    else                     $this->posts = &$posts;

    // WP_Post default values
    $this->post_status   = 'publish';
    $this->post_password = '';
    $this->post_excerpt  = $this->get_the_excerpt();
    $this->post_content  = $this->get_the_content();
    $this->post_author   = 1;
    $this->post_date     = date( 'c' );
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

  function the_post() {
    global $post;
    $post = $this->next_post();
    $this->setup_postdata( $post );
    return $post;
  }

  // ------------------------------------------------- Properties
  function is_feed()    {return FALSE;}
  function is_page()    {return FALSE;}
  function is_single()  {return FALSE;}
  function get( $name ) {return NULL;}

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

  function template( $mode = 'list' ) {
		return array( $this->post_type(), $mode );
  }

  // ------------------------------------------------- Output
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

  function get_the_excerpt() {
		return '';
  }

  function get_the_content() {
		return '';
  }

  function classes() {
		return '';
  }
}

