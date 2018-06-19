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
    return new CB_Database_Query( $table, $alias );
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
    $this->add_join( 'wp_posts', $table_alias, array( "$on = $table_alias.ID" ) );
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
    $join = "$table $table_alias on $on";
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
      FROM $this->table
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
    return new CB_Database_Insert( $table );
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

    $wpdb->insert( $this->table, $this->fields, $this->formats );
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
  }

  // ------------------------------------------------- Navigation
  function have_posts() {
    return count( $this->posts );
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
    return ( $post = $this->next_post() );
  }

  // ------------------------------------------------- Output
  function the_json_content( $options = NULL ) {
    print( $this->get_the_json_content( $options ) );
  }

  function get_the_json_content( $options = NULL ) {
    wp_json_encode( $this, $options );
  }

  function the_content( $more_link_text = null, $strip_teaser = false ) {
    print( $this->get_the_content( $more_link_text, $strip_teaser ) );
  }
}

