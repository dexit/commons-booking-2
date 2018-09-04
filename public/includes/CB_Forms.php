<?php
class CB_Forms {
  static function location_options( $none = FALSE ) {
    return self::get_options( 'posts', NULL, 'ID', 'post_title', "post_type = 'location' AND post_status = 'publish'", $none );
  }

  static function item_options( $none = FALSE ) {
    return self::get_options( 'posts', NULL, 'ID', 'post_title', "post_type = 'item' AND post_status = 'publish'", $none );
  }

  static function user_options( $none = FALSE ) {
    return self::get_options( 'users', NULL, 'ID', 'user_login', '1=1', $none );
  }

  static function period_status_type_options( $none = FALSE ) {
    return self::get_options( 'cb2_period_status_types', CB_PeriodStatusType::$static_post_type, 'period_status_type_id', 'name', '1=1', $none );
  }

  static function period_group_options( $none = FALSE ) {
    return self::get_options( 'cb2_period_groups', CB_PeriodGroup::$static_post_type, 'period_group_id', 'name', '1=1', $none );
  }

  static function period_options( $none = FALSE ) {
    return self::get_options( 'cb2_periods', CB_Period::$static_post_type, 'period_id', 'name', '1=1', $none );
  }

  static function get_options( $table, $post_type, $id_field = 'ID', $name_field = 'post_title', $condition = '1=1', $none = FALSE ) {
		global $wpdb;

		$cache_name = "CB_Forms::get_options($table, $post_type, $id_field, $name_field, $condition, $none)";
		$options    = wp_cache_get( $cache_name );
		if ( ! $options ) {
			$options    = array();
			if ( $none ) {
				$none_string = ( $none === TRUE ? 'None' : $none);
				$options[$none_string] = htmlspecialchars( $none_string );
			}

			$sql = "select $id_field as ID, $name_field as name from $wpdb->prefix$table where $condition";
			if ( $post_type ) {
				$sql = "select $id_field * pt.ID_multiplier + pt.ID_base as ID, $name_field as name
					from $wpdb->prefix$table,
					{$wpdb->prefix}cb2_post_types pt
					where ($condition) and pt.post_type = '$post_type'";
			}
			$db_options = $wpdb->get_results( $sql, OBJECT_K );
			foreach ( $db_options as $id => &$db_option ) $options[$id] = htmlspecialchars( $db_option->name );
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

  static function reset_data( $pass, $and_posts = FALSE ) {
    global $wpdb;
    $cleared      = FALSE;
    $post_types   = "'page', 'post', 'location', 'item'";
		$post_IDs_SQL = "select ID from {$wpdb->prefix}posts where not post_type IN($post_types)";
		$no_NULLs     = FALSE;
		$no_prepare   = FALSE; // SQL clause in parameter
		$NOT          = TRUE;
		$exists       = 'exists(select * from wp_posts where ID = post_id)';

    if ( WP_DEBUG && $pass == 'fryace4' ) {
			if ( $and_posts ) {
				// Remove all non-page/post metadata
				CB_Database_Delete::factory( 'postmeta' )->add_condition( 'post_id', $post_IDs_SQL, $no_NULLs, 'IN', $no_prepare )->run();
				// Remove all _edit_locks etc.
				// TODO: disabled because WordPress escapes the %% as GUIDs
				// CB_Database_Delete::factory( 'postmeta' )->add_condition( 'meta_key', '\_%%', $no_NULLs, 'LIKE' )->run();
				// Remove all auto-drafts
				CB_Database_Delete::factory( 'posts' )->add_condition( 'post_status', CB2_AUTODRAFT )->run();
				// Remove ALL non page/posts
				CB_Database_Delete::factory( 'posts' )->add_condition( 'post_type', $post_types, $no_NULLs, 'IN', $no_prepare, $NOT )->run();
				// Clear up manual DRI
				CB_Database_Delete::factory( 'postmeta' )->add_condition( $exists, NULL, $no_NULLs, NULL, $no_prepare, $NOT )->run();
			}

			// Clear native
			CB_Database_Truncate::factory_truncate( 'cb2_timeframe_options', 'option_id' )->run();
			CB_Database_Truncate::factory_truncate( 'cb2_global_period_groups', 'period_group_id' )->run();
			CB_Database_Truncate::factory_truncate( 'cb2_timeframe_period_groups', 'period_group_id' )->run();
			CB_Database_Truncate::factory_truncate( 'cb2_timeframe_user_period_groups', 'period_group_id' )->run();
			CB_Database_Truncate::factory_truncate( 'cb2_location_period_groups', 'period_group_id' )->run();
			CB_Database_Truncate::factory_truncate( 'cb2_period_group_period', 'period_group_id' )->run();
			CB_Database_Truncate::factory_truncate( 'cb2_periods', 'period_id' )->run();
			CB_Database_Truncate::factory_truncate( 'cb2_period_groups', 'period_group_id' )->run();
			$cleared = TRUE;
    }

    return $cleared;
  }
}
