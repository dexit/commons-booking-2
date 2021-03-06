<?php
class CB2_Forms {
  static function location_options( Bool $none = FALSE ) {
    return self::get_options( 'posts', NULL, 'ID', 'post_title', "post_type = 'location' AND post_status = 'publish'", ( $none ? '-- select --' : FALSE ), CB2_Database::$NULL_indicator );
  }

  static function item_options( Bool $none = FALSE ) {
    return self::get_options( 'posts', NULL, 'ID', 'post_title', "post_type = 'item' AND post_status = 'publish'", ( $none ? '-- select --' : FALSE ), CB2_Database::$NULL_indicator );
  }

  static function user_options( Bool $none = FALSE ) {
    return self::get_options( 'users', NULL, 'ID', 'user_login', '1=1', ( $none ? '-- select --' : FALSE ), CB2_Database::$NULL_indicator );
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
		global $wpdb, $post;

		$cache_name = "self::get_options($table, $post_type, $id_field, $name_field, $condition, $none)";
		$options    = wp_cache_get( $cache_name );
		if ( ! $options ) {
			$options    = array();
			if ( $none ) {
				$none_string       = ( $none === TRUE ? __( '-- create new --' ) : __( $none ) );
				$options[$none_id] = htmlspecialchars( $none_string );
			}

			$author_field = 1;
			// TODO: move to WP_Query for get_options()
			switch ( CB2_Query::substring_before( str_replace( 'cb2_view_', 'cb2-view', $table ), '_' ) ) {
				case 'users':    $author_field = 'ID'; break;
				case 'posts':    $author_field = 'post_author'; break;
				case 'cb2'  :    $author_field = 'author_ID'; break;
				case 'cb2-view': $author_field = 'post_author'; break;
			}
			$sql          = "select $id_field as ID, $author_field as post_author, $name_field as name
				from $wpdb->prefix$table
				where $condition";
			if ( $post_type ) {
				$sql = "select $id_field * pt.ID_multiplier + pt.ID_base as ID, $author_field as post_author, $name_field as name
					from $wpdb->prefix$table,
					{$wpdb->prefix}cb2_post_types pt
					where ($condition) and pt.post_type = '$post_type'";
			}
			$db_options      = ( CB2_Database::query_ok( $sql ) ? $wpdb->get_results( $sql, OBJECT_K ) : array() );
			$current_user_id = get_current_user_id();
			foreach ( $db_options as $id => &$db_option ) {
				$name        = $db_option->name;
				$post_author = $db_option->post_author;
				//if ( current_user_can( 'edit_post' ) ) {
					if ( empty( $name ) ) $name = __( '(no title)' );
					if ( WP_DEBUG )       $name .= " ($id/$post_author)";
					$options[$id] = htmlspecialchars( $name );
				//}
			}
			wp_cache_set( $cache_name, $options ); // Cache the self select options
		}
		return $options;
  }

  static function select_options( $records, $current_value = NULL, $add_none = TRUE, $by_name = FALSE ) {
    $html = '';
    if ( $add_none ) {
			if ( $add_none === TRUE ) $add_none = 'select';
			$add_none = '-- ' . __( $add_none ) . ' --';
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
			self::truncate_table( 'cb2_periodinst_settings', 'period_id' );
			self::truncate_table( 'cb2_timeframe_options', 'option_id' );
			self::truncate_table( 'cb2_global_period_groups', 'period_group_id' );
			self::truncate_table( 'cb2_timeframe_period_groups', 'period_group_id' );
			self::truncate_table( 'cb2_timeframe_user_period_groups', 'period_group_id' );
			self::truncate_table( 'cb2_location_period_groups', 'period_group_id' );
			self::truncate_table( 'cb2_period_group_period', 'period_group_id' );
			self::truncate_table( 'cb2_period_linked_period', 'period_id' );
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
				// GEO data
				$wpdb->query( "insert into {$wpdb->prefix}postmeta values( '1', '3', 'geo_address', 'a place called Chiapas');" );
				$wpdb->query( "insert into {$wpdb->prefix}postmeta values( '2', '3', 'geo_latitude', '51');" );
				$wpdb->query( "insert into {$wpdb->prefix}postmeta values( '3', '3', 'geo_longitude', '0');" );
				$wpdb->query( "insert into {$wpdb->prefix}postmeta values( '4', '4', 'geo_address', 'Spain 1936');" );
				$wpdb->query( "insert into {$wpdb->prefix}postmeta values( '5', '4', 'geo_latitude', '51.2');" );
				$wpdb->query( "insert into {$wpdb->prefix}postmeta values( '6', '4', 'geo_longitude', '0.2');" );
			}

			$cleared = TRUE;
    }

    return $cleared;
  }

  static function the_form( Array &$selections, Array $defaults = array(), $form_class = 'cb2-calendar-filter' ) {
		$selections       = array_merge( $defaults, $selections );

		// --------------------------------------- Defaults
		if ( ! isset( $selections['interval_to_show'] ) )  $selections['interval_to_show']  = 'P1M';
		if ( ! isset( $selections['interaction_style'] ) ) $selections['interaction_style'] = NULL;
		if ( ! isset( $selections['output_type'] ) )       $selections['output_type']       = 'Calendar';
		if ( ! isset( $selections['context'] ) )           $selections['context']           = NULL;
		if ( ! isset( $selections['template_part'] ) )     $selections['template_part']     = NULL;
		if ( ! isset( $selections['selection_mode'] ) )    $selections['selection_mode']    = NULL;
		if ( ! isset( $selections['schema_type'] ) )       $selections['schema_type']       = NULL;
		$today            = CB2_DateTime::today();
		$plusXmonths      = $today->clone()->add( $selections['interval_to_show'] )->endTime();

		// --------------------------------------- Defaults
		$output_type      = $selections['output_type'];
		$schema_type      = $selections['schema_type'];
		$schema_type_sent = $schema_type;
		if ( ! $schema_type ) {
			// automatic
			switch ( $output_type ) {
				case 'Calendar': $schema_type = CB2_Week::$static_post_type; break;
				case 'Map':      $schema_type = CB2_Location::$static_post_type; break;
				case 'List':     $schema_type = NULL; break;
				case 'API/JSON': $schema_type = CB2_Item::$static_post_type; break;
			}
			$selections['schema_type'] = $schema_type;
		}
		$template_context = $selections['context'];
		$template_context_sent = $template_context;
		if ( ! $template_context ) {
			// automatic
			switch ( $output_type ) {
				case 'Calendar': $template_context = 'list';  break;
				case 'Map':      $template_context = 'hcard'; break;
				case 'List':     $template_context = 'list';  break;
				case 'API/JSON': $template_context = 'list';  break;
			}
			$selections['context'] = $template_context;
		}

		// --------------------------------------- Checks
		if ( $output_type == 'Calendar' && (
				 $schema_type == CB2_Location::$static_post_type
			|| $schema_type == CB2_Item::$static_post_type
			|| $schema_type == CB2_User::$static_post_type
		) )
			print( '<div class="cb2-help">Calendar rendering of locations / items / users / forms maybe better in JSON output type</div>' );
		if ( $output_type == 'Map'      && ( $schema_type != CB2_Location::$static_post_type ) )
			print( "<div class='cb2-warning'>location schema hierarchy advised for Map. [$schema_type] sent</div>" );

		// --------------------------------------- Filter selection Form
		$startdate_string  = CB2_Query::isset( $selections, 'startdate', $today->format( CB2_Query::$datetime_format ) );
		$enddate_string    = CB2_Query::isset( $selections, 'enddate',   $plusXmonths->format( CB2_Query::$datetime_format ) );
		$location_options  = self::select_options( self::location_options(), CB2_Query::isset( $selections, 'location_ID' ) );
		$item_options      = self::select_options( self::item_options(), CB2_Query::isset( $selections, 'item_ID' ) );
		$user_options      = self::select_options( self::user_options(), CB2_Query::isset( $selections, 'user_ID' ) );
		$author_options    = self::select_options( self::user_options(), CB2_Query::isset( $selections, 'author_ID' ) );
		$period_status_type_options = self::select_options( self::period_status_type_options(), CB2_Query::isset( $selections, 'period_status_type_ID' ), TRUE );
		$period_entity_options = self::select_options( self::period_entity_options(), CB2_Query::isset( $selections, 'period_entity_ID' ), TRUE );
		$show_overridden_periods_checked = ( CB2_Query::isset( $selections, 'show_overridden_periods' ) ? 'checked="1"' : '' );
		$show_blocked_periods_checked    = ( CB2_Query::isset( $selections, 'show_blocked_periods' )    ? 'checked="1"' : '' );

		$interaction_style    = self::select_options( array(
			'cb2-one-row-scroll' => 'One Row Scroll',
		), CB2_Query::isset( $selections, 'interaction_style' ) );
		$output_options_array = array(
			'Calendar' => 'Calendar',
			'List'     => 'List',
			'Map'      => 'Map',
		);
		if ( WP_DEBUG ) $output_options_array['API/JSON'] = 'API/JSON (WP_DEBUG)';
		$output_options    = self::select_options(
			$output_options_array,
			CB2_Query::isset( $selections, 'output_type' ), FALSE
		);
		$schema_options    = self::select_options(
			self::schema_options(),
			$schema_type_sent, "automatic ($schema_type)"
		);
		$context_options   = self::select_options( array(
			'list'   => 'list',
			'popup'  => 'popup',
			'hcard'  => 'hcard',
			'single' => 'single',
		), $template_context_sent, "automatic ($template_context)"  );
		$template_options  = self::select_options( array(
			'available'  => 'available',
			'items'      => 'items',
			'indicators' => 'indicators',
			'overlaid'   => 'overlaid',
		), CB2_Query::isset( $selections, 'template_part' ), 'none' );
		$selection_mode_options   = self::select_options( array(
			'range'   => 'range',
		), CB2_Query::isset( $selections, 'selection_mode' ), 'single' );
		$display_strategys = self::select_options(
			CB2_Query::subclasses( 'CB2_PeriodInteractionStrategy' ),
			CB2_Query::isset( $selections, 'display_strategy', 'WP_Query' ),
			'raw', TRUE
		);

		$class_WP_DEBUG    = ( WP_DEBUG ? '' : 'hidden' );
		$advanced_class    = ( isset( $selections['advanced'] ) ? 'block' : 'none' );
		$advanced_checked  = ( isset( $selections['advanced'] ) ? 'checked="1"' : '' );
		$dates_text        = __( 'Time Period' );
		$location_text     = __( 'Location' );
		$item_text         = __( 'Item' );
		$output_type_text  = __( 'Display as' );
		$period_status_type_options_html = self::count_options( self::period_status_type_options() );
		$period_status_type_text = __( 'Status' ) . $period_status_type_options_html;
		$user_text         = __( 'User' );
		$author_text       = __( 'Author' );
		$advanced_text     = __( 'advanced' );
		$period_entity_options_html = self::count_options( self::period_entity_options() );
		$period_entity_text      = __( 'Period Entity' ) . $period_entity_options_html;
		$interaction_style_text  = __( 'Interaction Style' );
		$schema_hierarchy_text   = __( 'Schema Hierarchy' );
		$template_context_text   = __( 'Template Context' );
		$template_part_text      = __( 'Template Part' );
		$display_strategy_text   = __( 'Display Strategy' );
		$selection_mode_text     = __( 'Selection Mode' );

		print( <<<HTML
			<form class='$form_class cb2-form'><div>
				<input name='page' type='hidden' value='cb2-calendar'/>
				<div class="cb2-form-wrapper cmb2-wrap"><div class="cmb2-metabox">
					$dates_text: <input class='cmb2-datepicker' type='text' name='startdate' value='$startdate_string'/> to
					<input class='cmb2-datepicker' type='text' name='enddate' value='$enddate_string'/>
				</div></div>
				<div class="cb2-form-wrapper">
					$location_text: <select name="location_ID">$location_options</select>
				</div>
				<div class="cb2-form-wrapper">
					$item_text: <select name="item_ID">$item_options</select>
				</div>
				<div class="cb2-form-wrapper cb2-todo">
					$user_text: <select name="user_ID">$user_options</select>
				</div>
				<div class="cb2-form-wrapper">
					$period_status_type_text: <select name="period_status_type_ID">$period_status_type_options</select>
				</div>
				<div class="cb2-form-wrapper">
					$output_type_text: <select name="output_type">$output_options</select>
				</div>
				<div style='display:$advanced_class'>
					<div class="cb2-form-wrapper">
						$period_entity_text: <select name="period_entity_ID">$period_entity_options</select>
					</div>
					<div class="cb2-form-wrapper cb2-todo">
						$author_text:<select name="author_ID">$author_options</select>
					</div>
					<div class="cb2-form-wrapper">
						$interaction_style_text: <select name="interaction_style">$interaction_style</select>
					</div>
					<div class="cb2-form-wrapper">
						$schema_hierarchy_text:  <select name="schema_type">$schema_options</select>
					</div>
					<div class="cb2-form-wrapper">
						$template_context_text:  <select name="context">$context_options</select>
					</div>
					<div class="cb2-form-wrapper">
						$template_part_text:     <select name="template_part">$template_options</select>
					</div>
					<div class="cb2-form-wrapper">
						$display_strategy_text:  <select name="display_strategy">$display_strategys</select>
					</div>
					<div class="cb2-form-wrapper">
						$selection_mode_text:    <select name="selection_mode">$selection_mode_options</select>
					</div>
					<div class="cb2-form-wrapper">
						<input id='show_overridden_periods' type='checkbox' $show_overridden_periods_checked name='show_overridden_periods'/> <label for='show_overridden_periods'>show overridden periods</label>
					</div>
					<div class="cb2-form-wrapper">
						<input id='show_blocked_periods'    type='checkbox' $show_blocked_periods_checked    name='show_blocked_periods'/>    <label for='show_blocked_periods'>show blocked periods</label>
					</div>
				</div>
				<div class="cb2-form-wrapper">
					<input class="cb2-submit button" type="submit" value="Filter"/>
					<input id="cb2-advanced" onclick="jQuery(this.form).submit()" $advanced_checked type="checkbox" name="advanced" /> <label for="cb2-advanced">$advanced_text</label>
				</div>
			</div></form>
HTML
		);
	}

	static function count_options( $array, $class = 'ok' ) {
		$count = count( $array );
		return "<span class='cb2-usage-count-$class'>$count</span>";
	}
}
