<?php
class CB2_Forms {
  static function location_options( Bool $none = FALSE ) {
    return self::get_options( 'posts', NULL, 'ID', 'post_title', "post_type = 'location' AND post_status = 'publish'", ( $none ? '-- select --' : FALSE ), NULL );
  }

  static function item_options( Bool $none = FALSE ) {
    return self::get_options( 'posts', NULL, 'ID', 'post_title', "post_type = 'item' AND post_status = 'publish'", ( $none ? '-- select --' : FALSE ), NULL );
  }

  static function user_options( Bool $none = FALSE ) {
    return self::get_options( 'users', NULL, 'ID', 'user_login', '1=1', ( $none ? '-- select --' : FALSE ), NULL );
  }

  static function period_status_type_options( $none = FALSE, Int $none_id = CB2_CREATE_NEW ) {
    return self::get_options( 'cb2_period_status_types', CB2_PeriodStatusType::$static_post_type, 'period_status_type_id', 'name', '1=1', $none, $none_id );
  }

  static function period_group_options( $none = FALSE, Int $none_id = CB2_CREATE_NEW ) {
    return self::get_options( 'cb2_period_groups', CB2_PeriodGroup::$static_post_type, 'period_group_id', 'name', '1=1', $none, $none_id );
  }

  static function period_entity_options( $none = FALSE ) {
    return self::get_options( 'cb2_view_periodent_posts' );
  }

  static function period_options( $none = FALSE, Int $none_id = CB2_CREATE_NEW ) {
    return self::get_options( 'cb2_periods', CB2_Period::$static_post_type, 'period_id', 'name', '1=1', $none, $none_id );
  }

  static function get_options( String $table, String $post_type = NULL, String $id_field = 'ID', String $name_field = 'post_title', String $condition = '1=1', $none = FALSE, $none_id = CB2_CREATE_NEW ) {
		global $wpdb;

		$cache_name = "CB2_Forms::get_options($table, $post_type, $id_field, $name_field, $condition, $none)";
		$options    = wp_cache_get( $cache_name );
		if ( ! $options ) {
			$options    = array();
			if ( $none ) {
				$none_string       = ( $none === TRUE ? __( '-- create new --' ) : __( $none ) );
				$options[$none_id] = htmlspecialchars( $none_string );
			}

			$sql = "select $id_field as ID, $name_field as name from $wpdb->prefix$table where $condition";
			if ( $post_type ) {
				$sql = "select $id_field * pt.ID_multiplier + pt.ID_base as ID, $name_field as name
					from $wpdb->prefix$table,
					{$wpdb->prefix}cb2_post_types pt
					where ($condition) and pt.post_type = '$post_type'";
			}
			$db_options = $wpdb->get_results( $sql, OBJECT_K );
			foreach ( $db_options as $id => &$db_option ) {
				$name = $db_option->name;
				if ( WP_DEBUG ) $name .= " ($id)";
				$options[$id] = htmlspecialchars( $name );
			}
			wp_cache_set( $cache_name, $options ); // Cache the CB2_Forms select options
		}
		return $options;
  }

  static function select_options( $records, $current_value = NULL, $add_none = TRUE, $by_name = FALSE ) {
    $html = '';
    if ( $add_none ) {
			if ( $add_none === TRUE ) $add_none = '--none--';
			$html .= "<option value=''>$add_none</option>";
		}
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
		$classes    = CB2_PostNavigator::post_type_classes();
		foreach ( $classes as $post_type => $Class )
			$post_types[ $post_type ] = $post_type;
    return $post_types;
  }

  static function truncate_table( $table_name, $id_field ) {
		global $wpdb;
		return $wpdb->query( "DELETE from $wpdb->prefix$table_name WHERE $id_field >= 0" );
  }

  static function reset_data( $pass, $and_posts = FALSE, $testdata = FALSE ) {
		global $wpdb;

    $cleared = FALSE;
    $post_types_array = array(
			CB2_PeriodGroup::$static_post_type,
			CB2_Period::$static_post_type,
			CB2_PeriodEntity_Global::$static_post_type,
			CB2_PeriodEntity_Location::$static_post_type,
			CB2_PeriodEntity_Timeframe::$static_post_type,
			CB2_PeriodEntity_Timeframe_User::$static_post_type,
		);
		$post_types_string = "'" . implode( "','", $post_types_array ) . "'";

    if ( WP_DEBUG && $pass == 'fryace4' ) {
			// Native leaves
			self::truncate_table( 'cb2_perioditem_settings', 'period_id' );
			self::truncate_table( 'cb2_timeframe_options', 'option_id' );
			self::truncate_table( 'cb2_global_period_groups', 'period_group_id' );
			self::truncate_table( 'cb2_timeframe_period_groups', 'period_group_id' );
			self::truncate_table( 'cb2_timeframe_user_period_groups', 'period_group_id' );
			self::truncate_table( 'cb2_location_period_groups', 'period_group_id' );
			self::truncate_table( 'cb2_period_group_period', 'period_group_id' );
			self::truncate_table( 'cb2_periods', 'period_id' );
			self::truncate_table( 'cb2_period_groups', 'period_group_id' );

			if ( $and_posts ) {
				// Remove all auto-drafts
				$wpdb->query( "delete from {$wpdb->prefix}posts where post_status = 'auto-draft'" );
				// Remove ALL non page/posts
				$wpdb->query( "delete from {$wpdb->prefix}posts where post_type IN($post_types_string)" );
				// Clear up manual DRI
				$wpdb->query( "delete from {$wpdb->prefix}postmeta where NOT exists(select * from {$wpdb->prefix}posts where ID = post_id)" );
			}

			if ( $testdata ) {
				self::truncate_table( 'posts', 'ID' );
				self::truncate_table( 'postmeta', 'post_id' );
				// Items
				$wpdb->query( "insert into {$wpdb->prefix}posts values( '1', '1', '2018-11-08 17:07:36', '2018-11-08 17:07:36', '', 'Cargo Bike Blue', '', 'publish', 'closed', 'closed', '', 'cargo-bike-blue', '', '', '2018-11-08 17:07:36', '2018-11-08 17:07:36', '', '0', 'http://commonsbooking.ddns.net/?post_type=item&#038;p=1', '0', 'item', '', '0');" );
				$wpdb->query( "insert into {$wpdb->prefix}posts values( '2', '1', '2018-11-08 17:07:36', '2018-11-08 17:07:36', '', 'Cargo Bike Red',  '', 'publish', 'closed', 'closed', '', 'cargo-bike-red',  '', '', '2018-11-08 17:07:36', '2018-11-08 17:07:36', '', '0', 'http://commonsbooking.ddns.net/?post_type=item&#038;p=2', '0', 'item', '', '0');" );
				// Locations
				$wpdb->query( "insert into {$wpdb->prefix}posts values( '3', '1', '2018-11-08 17:07:36', '2018-11-08 17:07:36', '', 'Budapest fairest', '', 'publish', 'closed', 'closed', '', 'budapest-fairest', '', '', '2018-11-08 17:07:36', '2018-11-08 17:07:36', '', '0', 'http://commonsbooking.ddns.net/?post_type=item&#038;p=3', '0', 'location', '', '0');" );
				$wpdb->query( "insert into {$wpdb->prefix}posts values( '4', '1', '2018-11-08 17:07:36', '2018-11-08 17:07:36', '', 'Berlin biscuits',  '', 'publish', 'closed', 'closed', '', 'berlin-biscuits',  '', '', '2018-11-08 17:07:36', '2018-11-08 17:07:36', '', '0', 'http://commonsbooking.ddns.net/?post_type=item&#038;p=4', '0', 'location', '', '0');" );
			}

			$cleared = TRUE;
    }

    return $cleared;
  }
}
