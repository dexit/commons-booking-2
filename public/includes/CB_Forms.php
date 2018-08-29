<?php
class CB_Forms {
  static function location_options() {
    return self::get_options( 'posts', 'ID', 'post_title', "post_type = 'location' AND post_status = 'publish'" );
  }

  static function item_options() {
    return self::get_options( 'posts', 'ID', 'post_title', "post_type = 'item' AND post_status = 'publish'" );
  }

  static function user_options() {
    return self::get_options( 'users', 'ID', 'user_login' );
  }

  static function period_status_type_options() {
    return self::get_options( 'cb2_period_status_types', 'period_status_type_id', 'name' );
  }

  static function period_group_options() {
    return self::get_options( 'cb2_period_groups', 'period_group_id', 'name' );
  }

  static function get_options( $table, $id_field = 'ID', $name_field = 'post_title', $condition = '1=1' ) {
		global $wpdb;

		$cache_name = "$table, $id_field, $name_field, $condition";
		$options    = wp_cache_get( $cache_name );
		if ( ! $options ) {
			$options = $wpdb->get_results( "select $id_field as ID, $name_field as name from $wpdb->prefix$table where $condition", OBJECT_K );
			foreach ( $options as &$option ) $option = $option->name;
			wp_cache_set( $cache_name, $options );
		}
		return $options;
  }

  static function select_options( $records, $current_value = NULL, $add_none = TRUE, $by_name = FALSE ) {
    $html = '';
    if ( $add_none ) $html .= "<option value=''>--none--</option>";
    foreach ( $records as $value => $name ) {
      if ( is_object( $name ) ) $name  = $name->name;
      if ( $by_name )           $value = $name;
      $selected = ( $current_value == $value ? 'selected="1"' : '' );
      $html .= "<option value='$value' $selected>$name</option>";
    }
    return $html;
  }

  static function schema_options() {
		$post_types = array();
		$classes    = CB_Query::schema_types();
		foreach ( $classes as $post_type => $Class )
			$post_types[ $post_type ] = $post_type;
    return $post_types;
  }

  static function reset_data( $pass ) {
    global $wpdb;

    if ( WP_DEBUG && $pass == 'fryace4' ) {
			CB_Database_Truncate::factory_truncate( 'cb2_timeframe_options', 'option_id' )->run();
			CB_Database_Truncate::factory_truncate( 'cb2_global_period_groups', 'period_group_id' )->run();
			CB_Database_Truncate::factory_truncate( 'cb2_timeframe_period_groups', 'period_group_id' )->run();
			CB_Database_Truncate::factory_truncate( 'cb2_timeframe_user_period_groups', 'period_group_id' )->run();
			CB_Database_Truncate::factory_truncate( 'cb2_location_period_groups', 'period_group_id' )->run();
			CB_Database_Truncate::factory_truncate( 'cb2_period_group_period', 'period_group_id' )->run();
			CB_Database_Truncate::factory_truncate( 'cb2_periods', 'period_id' )->run();
			CB_Database_Truncate::factory_truncate( 'cb2_period_groups', 'period_group_id' )->run();
    }
  }
}
