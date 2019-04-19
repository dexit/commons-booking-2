<?php
class CB2 {
	public static function the_Class() {
		print( self::get_the_Class() );
	}

	public static function get_the_Class() {
		global $post;
		return ( $post ? get_class( $post ) : NULL );
	}

	public static function the_form( Array $selections, Array $defaults = array() ) {
		return CB2_Forms::the_form( $selections, $defaults );
	}

	public static function templates( String $context = 'list', String $type = NULL, Bool $throw_if_not_found = TRUE, &$templates_considered = NULL ) {
		global $post;
		$templates            = array();
		$templates_considered = array();

		if ( $post && method_exists( $post, 'templates' ) )
			$templates = $post->templates( $context, $type, $throw_if_not_found, $templates_considered );
		if ( WP_DEBUG )
			print( "<!-- Templates considered (in priority order): \n  " . implode( ", \n  ", $templates_considered ) . "\n -->" );

		return $templates;
	}

	public static function has_inner_posts( $post_type = NULL, $post_navigator = NULL ) {
		global $post;
		$has_posts = FALSE;
		if ( is_null( $post_navigator ) ) $post_navigator = $post;

		if ( property_exists( $post_navigator, 'posts' ) && is_array( $post_navigator->posts ) ) {
			CB2_Query::reorganise_posts_structure( $post_navigator );
			if ( is_null( $post_type ) ) {
				$has_posts = ( count( $post_navigator->posts ) > 0 );
			} else {
				foreach ( $post_navigator->posts as $inner_post ) {
					if ( $inner_post->post_type == $post_type ) {
						$has_posts = TRUE;
						break;
					}
				}
			}
		}

		return $has_posts;
	}

	public static function is_confirmed() {
		global $post;
		$period_entity = ( property_exists( $post, 'period_entity' ) ? $post->period_entity : $post );
		return ( $period_entity && property_exists( $period_entity, 'confirmed_user_id' ) && $period_entity->confirmed_user_id );
	}

	public static function is_approved() {
		global $post;
		$period_entity = ( property_exists( $post, 'period_entity' ) ? $post->period_entity : $post );
		return ( $period_entity && property_exists( $period_entity, 'approved_user_id' ) && $period_entity->approved_user_id );
	}

	public static function is_published() {
		global $post;
		return ( $post && $post->post_status == CB2_Post::$PUBLISH );
	}

	public static function is_not_published() {
		return ! self::is_published();
	}

	public static function the_date( $format = NULL ) {
		print( self::get_the_date( $format ) );
	}

	public static function get_the_date( $format = NULL, $the_post = NULL ) {
		global $post;
		if ( is_null( $the_post ) ) $the_post = $post;

		if ( '' == $format ) {
			$the_date = mysql2date( get_option( 'date_format' ), $the_post->post_date );
		} else {
			$the_date = mysql2date( $format, $the_post->post_date );
		}

		$the_date = apply_filters( 'get_the_date', $the_date, $format, $the_post );

		return new CB2_DateTime( $the_date );
	}

	public static function has_geo() {
		global $post;
		return ( $post && property_exists( $post, 'geo_latitude' ) && property_exists( $post, 'geo_longitude' ) );
	}

	public static function the_geo_latitude() {
		global $post;
		print( $post && property_exists( $post, 'geo_latitude' ) ? $post->geo_latitude : NULL );
	}

	public static function the_geo_address() {
		global $post;
		print( $post && property_exists( $post, 'geo_address' ) ? $post->geo_address : NULL );
	}

	public static function the_geo_longitude() {
		global $post;
		print( $post && property_exists( $post, 'geo_longitude' ) ? $post->geo_longitude : NULL );
	}

	public static function the_inner_loop( $template_args = NULL, $post_navigator = NULL, $context = 'list', $template_type = NULL, $before = '', $after = '', $reorder_function = NULL ) {
		echo self::get_the_inner_loop( $template_args, $post_navigator, $context, $template_type, $before, $after, $reorder_function );
	}

	public static function get_the_inner_loop( $template_args = NULL, $post_navigator = NULL, $context = 'list', $template_type = NULL, $before = '', $after = '', $reorder_function = NULL ) {
		global $post;
		$html       = '';

		if ( $context == 'single' )
			throw new Exception( "the_inner_loop() should never be called with context [$context] because, by its very nature it is for listing stuffs" );

		if ( ! $post_navigator ) $post_navigator = $post;
		if ( $post_navigator instanceof CB2_PostNavigator || $post_navigator instanceof WP_Query ) {
			$outer_post  = $post;
			// CB2_Query::reorganise_posts_structure() to check that posts exist
			// otherwise have_posts() will FALSE
			if ( $post_navigator instanceof WP_Query ) CB2_Query::reorganise_posts_structure( $post_navigator );
			if ( $post_navigator->have_posts() ) {
				// the_post() will trigger loop_start
				//   CB2_Query::reorganise_posts_structure() which will not do anything
				// because the wp_query is marked as re-organised already
				if ( $reorder_function ) uasort( $post_navigator->posts, $reorder_function );
				$i = 0;
				while ( $post_navigator->have_posts() ) : $post_navigator->the_post();
					$even_class = ( $i % 2 ? 'cb2-row-odd' : 'cb2-row-even' );
					$template_args[ 'even_class' ] = $even_class;
					$post_type  = $post->post_type();
					$html      .= $before;
					CB2_Query::redirect_wpdb_for_post_type( $post_type );
					$templates  = self::templates( $context, $template_type );
					$li         = cb2_get_template_part( CB2_TEXTDOMAIN, $templates, '', $template_args, TRUE, array(), $template_type );
					$html      .= $li;
					// Some periodinsts are suppressed but have debug output
					if ( trim( preg_replace( '/<!--.*-->/', '', $li ) ) ) $i++;
					CB2_Query::unredirect_wpdb();
					$html      .= $after;
				endwhile;
			}

			// NOTE: We have manually set the global $wp_query->post
			// when looping on WP_List_Tables because
			// WP_List_Tables does not use a normal post loop :/
			//
			// https://codex.wordpress.org/Class_Reference/WP_Query
			// wp_reset_postdata() => global $wp_query->reset_postdata();
			//   global $post = global $wp_query->post;
			//   $this->setup_postdata( global $wp_query->post );
			wp_reset_postdata();
			$post = $outer_post;
			// We also wp_cache_set() each ID
			// which means that whoever called the loop may now traverse
			// to another cached post which is then wrong
			wp_cache_flush();
			// In templates we are still using the outer_post
			// with get_post(ID) calls so let us keep that
			if ( $post ) wp_cache_set( $post->ID, $post, 'posts' );
			if ( WP_DEBUG && FALSE )
				$html .= ( "<div class='cb2-WP_DEBUG-small'>the_inner_loop() cache managing [$post->ID / $post->post_type]</div>" );
		} else {
			throw new Exception( 'the_inner_loop() only available for CB2_PostNavigator or WP_Query' );
		}

		return $html;
	}

	public static function the_short_name( $the_post = NULL ) {
		print( self::get_the_short_name( $the_post ) );
	}

	public static function get_the_short_name( $the_post = NULL ) {
		global $post;
		if ( is_null( $the_post ) ) $the_post = $post;
		$short_name = NULL;
		if ( is_object( $the_post ) && method_exists( $the_post, 'get_the_short_name' ) ) {
			$short_name = $the_post->get_the_short_name();
		} else {
			if ( strlen( $the_post->post_title ) > 3 )
				$short_name = preg_replace( '/[^A-Z]/', '', $the_post->post_title );
			else
				$short_name = $the_post->post_title;
		}
		if ( ! $short_name ) $short_name = '(' . __( 'no title' ) . ')';

		return $short_name;
	}

	public static function the_styles( String $styles = '', Array $options = array() ) {
		print( self::get_the_styles( $styles, $options ) );
	}

	public static function get_the_styles( String $styles = '', Array $options = array() ) {
		global $post;
		if ( is_object( $post ) && method_exists( $post, 'styles' ) ) {
			$styles = $post->styles( $styles, $options );
		}
		return $styles;
	}

	public static function the_colour( $the_post = NULL ) {
		print( self::get_the_colour( $the_post ) );
	}

	public static function get_the_colour( $the_post = NULL ) {
		global $post;
		if ( is_null( $the_post ) ) $the_post = $post;
		$the_colour = NULL;
		if ( is_object( $the_post ) && method_exists( $the_post, 'get_the_colour' ) ) {
			$the_colour = $the_post->get_the_colour();
		} else {
			$the_colour = '#67b32a';
		}

		return $the_colour;
	}

	public static function the_context_menu( $context = NULL ) {
		global $post;

		$actions = array_merge(
			self::get_the_class_actions(),
			self::get_the_calendar_actions(),
			self::get_the_query_actions()
		);
		if ( count( $actions ) ) {
			$ID = $post->ID;
			$actions_popup = "?inlineId=cb2-contextmenu_$ID&amp;title=context+menu&amp;width=300&amp;height=100&amp;#TB_inline";
			print( "<a class='thickbox cb2-contextmenu-link' name='context menu' href='$actions_popup'></a>" );

			print( "<div id='cb2-contextmenu_$ID' class='cb2-contextmenu' style='display:none;'><ul>" );
			foreach( $actions as $action ) {
				// Convert array specifications
				if ( is_array( $action ) ) {
					if ( ! isset( $action['page'] ) ) $action['page'] = 'cb2-post-new';
					if ( ! isset( $action['base'] ) ) $action['base'] = 'admin.php';
					$action['link_text'] = ( isset( $action['link_text'] ) ? __( $action['link_text'] ) : __( 'Do Stuff' ) );
					$setup_args_string   = CB2_Query::implode( '&', $action, '=', $post );
					$action = "<a href='$action[base]?$setup_args_string'>$action[link_text]</a>";
				}
				print( "<li>$action</li>" );
			}
			print( '</ul></div>' );
		}
	}

	public static function get_the_message( String $tagged_message, Array $classes = array(), $the_post = NULL ) {
		global $post;
		if ( is_null( $the_post ) ) $the_post = $post;

		$permalink = get_the_permalink( $the_post );
		$tags = array(
			'url'  => $permalink,
			'link' => "<a href='$permalink'>$post->post_title</a>",
		);
		if ( method_exists( $the_post, 'message_tags' ) )
			$tags = $the_post->message_tags();

		$tagged_message = CB2_Query::array_walk_paths_string( $tagged_message, 'message', $the_post, '{{', '}}', '-' );
		$tagged_message = apply_filters('cb2_template_tags_parsed', $tagged_message, 10, 3);

		return $tagged_message;
	}

	public static function the_message( String $tagged_message, Array $classes = array(), $the_post = NULL ) {
		array_push( $classes, 'cb2-content' );
		array_push( $classes, 'cb2_template_tag' );
		$class = implode ( ' ' , $classes );
		echo "<div class='$class'>";
		echo self::get_the_message( $tagged_message, $classes, $the_post );
		echo '</div>';
	}

	public static function the_post_type() {
		echo get_post_type();
	}

	public static function the_calendar_footer( $query = NULL, $classes = '', $type = 'li', $before = '<ul class="cb2-calendar-header">', $after = '</ul>' ) {
		echo self::get_the_calendar_footer( $query, $classes, $type, $before, $after );
	}

	public static function get_the_calendar_footer( $query = NULL, $classes = '', $type = 'li', $before = '<ul class="cb2-calendar-header">', $after = '</ul>' ) {
		return self::get_the_calendar_header( $query, $classes, $type, $before, $after );
	}

	public static function the_calendar_pager( CB2_DateTime $startdate = NULL, CB2_DateTime $enddate = NULL, $classes = '' ) {
		echo self::get_the_calendar_pager( $startdate, $enddate, $classes );
	}

	public static function get_the_calendar_pager( CB2_DateTime $startdate = NULL, CB2_DateTime $enddate = NULL, $classes = '' ) {
		// Inputs
		$url              = $_SERVER['REQUEST_URI'];
		$today            = CB2_DateTime::today();
		$next2month       = $today->clone()->add( 'P2M' )->endTime();
		$startdate_string = ( isset( $_GET['startdate'] ) ? $_GET['startdate'] : $today->format(  CB2_Query::$datetime_format ) );
		$enddate_string   = ( isset( $_GET['enddate']   ) ? $_GET['enddate']   : $next2month->format( CB2_Query::$datetime_format ) );

		// Date handling
		if ( is_null( $startdate ) ) $startdate = new CB2_DateTime( $startdate_string );
		if ( is_null( $enddate ) )   $enddate   = new CB2_DateTime( $enddate_string );
		$pagesize       = $startdate->diff( $enddate );
		$timeless_url   = preg_replace( '/[&\?](start|end)date=[^&]*/', '', $url );
		if ( strchr( $timeless_url, '?' ) === FALSE ) $timeless_url .= '?';

		$nextpage_start = $enddate->clone()->add( 1 )->clearTime();
		$nextpage_end   = $nextpage_start->clone()->add( $pagesize );
		$nextpage_start_string = $nextpage_start->format( CB2_Query::$datetime_format );
		$nextpage_end_string   = $nextpage_end->format(   CB2_Query::$datetime_format );

		$prevpage_end   = $startdate->clone()->sub(1)->endTime();
		$prevpage_start = $prevpage_end->clone()->sub( $pagesize );
		$prevpage_start_string = $prevpage_start->format( CB2_Query::$datetime_format );
		$prevpage_end_string   = $prevpage_end->format(   CB2_Query::$datetime_format );

		$current_view_string = date('m/y', strtotime($startdate_string)) . ' – ' . date('m/y', strtotime($enddate_string));


		return "<div class='entry-footer'>
				<div class='cb2-calendar-pager'>
					<span class='pagination-links'>
					<a href='$timeless_url&startdate=$prevpage_start_string&enddate=$prevpage_end_string' class='prev-page button'>&lt;&lt;</a>
					<span class='pagination-current-view'>$current_view_string</span>
					<a href='$timeless_url&startdate=$nextpage_start_string&enddate=$nextpage_end_string' class='next-page button'>&gt;&gt;</a>
					</span>
				</div>
			</div>";
	}

	public static function the_calendar_header( $query = NULL, $classes = '', $type = 'li', $before = '<ul class="cb2-calendar-header">', $after = '</ul>' ) {
		echo self::get_the_calendar_header( $query, $classes, $type, $before, $after );
	}

	public static function get_the_calendar_header( $query = NULL, $classes = '', $type = 'li', $before = '<ul class="cb2-calendar-header">', $after = '</ul>' ) {
		global $wp_query;
		$html = '';
		$schema_type = NULL;

		if ( ! $query ) $query = $wp_query;
		if ( $query && isset( $query->query['date_query']['compare'] ) )
			$schema_type = $query->query['date_query']['compare'];

		switch ( $schema_type ) {
			case CB2_Week::$static_post_type:
				$html         .= ( $before );
				$days_of_week  = CB2_Week::days_of_week();
				for ( $i = 0; $i < count( $days_of_week ); $i++ ) {
					$html   .= ( "<$type>$days_of_week[$i]</$type>" );
				}
				$html .= ( $after );
				break;
			default:
				// Do nothing
		}

		return $html;
	}

	public static function the_period_status_type_name() {
		echo self::get_the_period_status_type_name();
	}

	public static function get_the_period_status_type_name() {
		global $post;
		return ( is_object( $post ) && method_exists( $post, 'period_status_type_name' ) ? $post->period_status_type_name() : '' );
	}

	public static function the_blocked() {
		echo ( self::is_blocked() ? __('BLOCKED') : '' );
	}

	public static function is_blocked() {
		global $post;
		return ( is_object( $post ) && method_exists( $post, 'is_blocked' ) && $post->is_blocked() );
	}

	public static function the_summary() {
		echo self::get_the_summary();
	}

	public static function get_the_summary() {
		global $post;
		return ( is_object( $post ) && method_exists( $post, 'summary' ) ? $post->summary() : '' );
	}

	public static function the_time_period( $format = NULL, $human_readable = TRUE, $separator = '-' ) {
		echo self::get_the_time_period( $format, $human_readable, $separator );
	}

	public static function get_the_time_period( $format = NULL, $human_readable = TRUE, $separator = '-' ) {
		global $post;
		return ( is_object( $post ) && method_exists( $post, 'get_the_time_period' ) ? $post->get_the_time_period( $format, $human_readable, $separator ) : '' );
	}

	public static function is_current() {
		// Indicates if the time post contains the current time
		// e.g. if the CB2_Day is today, or the CB2_Week contains today
		global $post;
		return is_object( $post ) && property_exists( $post, 'is_current' ) && $post->is_current;
	}

	public static function is_top_priority() {
		// Indicates if the periodinst is overridden by another overlapping periodinst
		global $post, $wp_query;

		$top_priority            = TRUE;
		$show_overridden_periods = FALSE;

		if ( is_object( $wp_query ) && property_exists( $wp_query, 'query' ) ) {
			$post_status = ( isset( $wp_query->query['post_status'] ) ? $wp_query->query['post_status'] : array() );
			if ( ! is_array( $post_status ) ) $post_status = array( $post_status );
			$show_overridden_periods = in_array( CB2_Post::$TRASH, $post_status );
		}

		if ( is_object( $post ) && method_exists( $post, 'is_top_priority' ) ) {
			$top_priority = $post->is_top_priority();
		}

		return $top_priority || $show_overridden_periods;
	}

	public static function the_bulk_options() {
		print( "<div class='hide-if-no-js alignright actions bulkactions'>
			<label for='cb2-bulk-action-selector-top' class='screen-reader-text'>Select bulk action</label>
			<!-- no @name on these form elements because it is a *nested* form
				it is submitted only with JavaScript
				@js-name => @name during submission
			-->
			<select class='hide-if-no-js' id='cb2-bulk-action-selector-top' js-name='do_action'>
				<option value=''>Bulk Actions</option>
				<option value='CB2_PeriodEntity::block'>Block</option>
				<option value='CB2_PeriodEntity::unblock'>UnBlock</option>
			</select>
			<input type='button' class='hide-if-no-js button action' value='Apply'>
		</div>" );
	}

	public static function can_select() {
		global $post;
		return $post && ! property_exists( $post, 'no_select' );
	}

	public static function can_include() {
		global $post;
		return $post && ! property_exists( $post, 'no_include' );
	}

	public static function the_class_actions() {
		echo self::get_the_class_actions();
	}

	public static function the_calendar_actions() {
		echo self::get_the_calendar_actions();
	}

	public static function the_query_actions() {
		echo self::get_the_query_actions();
	}

	public static function get_the_class_actions() {
		global $post;
		return ( is_object( $post ) && method_exists( $post, 'get_the_class_actions' ) ? $post->get_the_class_actions() : array() );
	}

	public static function get_the_calendar_actions() {
		global $post;
		return ( is_object( $post ) && method_exists( $post, 'get_the_calendar_actions' )  ? $post->get_the_calendar_actions() : array() );
	}

	public static function get_the_query_actions() {
		global $wp_query;
		return ( is_object( $wp_query ) && property_exists( $wp_query, 'actions' ) ? $wp_query->actions : array() );
	}

	public static function the_debug_popup() {
		// do not place thickbox if not in wp admin interface
		if ( WP_DEBUG && is_admin() ) {
			$ID = get_the_ID();
			print( "<a class='thickbox cb2-bald cb2-debug-control' name='debug' href='?inlineId=debug_popup_$ID&amp;title=debug&amp;width=300&amp;height=500&amp;#TB_inline'></a>" );
			print( "<div id='debug_popup_$ID' style='display:none;'>" );
			self::the_debug();
			print( '</div>' );
		}
	}

	public static function the_debug( $before = '', $afer = '' ) {
		global $post;
		if ( WP_DEBUG && is_object( $post ) && method_exists( $post, 'get_the_debug' ) ) {
			echo $post->get_the_debug( $before, $afer );
		}
	}

	public static function the_edit_post_url( $post = NULL, $context = 'display' ) {
		// Returns URL
		echo self::get_the_edit_post_url( $post, $context );
	}

	public static function get_the_edit_post_url( $this_post = NULL, String $context = 'display' ) {
		// Returns URL
		global $post;
		if ( is_null( $this_post ) ) $this_post = $post;

		$url = NULL;
		if ( is_object( $this_post ) && method_exists( $this_post, 'get_the_edit_post_url' ) ) {
			$url = $this_post->get_the_edit_post_url( $context );
		} else {
			// Copied from link-template.php
			if ( 'revision' === $this_post->post_type )
				$action = '';
			elseif ( 'display' == $context )
				$action = '&amp;action=edit';
			else
				$action = '&action=edit';

			$post_type_object = get_post_type_object( $this_post->post_type );
			if ( ! $post_type_object )
				return;

			if ( ! current_user_can( 'edit_post', $this_post->ID ) )
				return;

			if ( $post_type_object->_edit_link )
				$url = admin_url( sprintf( $post_type_object->_edit_link . $action, $this_post->ID ) );
		}

		return $url;
	}

	public static function edit_post_link( String $text = NULL, String $before = '', String $after = '', Int $id = 0, String $class = 'post-edit-link' ) {
		// Returns HTML!
		echo self::get_the_edit_post_link( $text, $before, $after, $id, $class );
	}

	public static function get_the_edit_post_link( String $text = NULL, String $before = '', String $after = '', Int $id = 0, String $class = 'post-edit-link' ) {
		// Returns HTML!
		// The WordPress function returns a URL
		// but cannot be controlled in terms of its get_post();
		global $post;

		$link = NULL;
		if ( is_object( $post ) && method_exists( $post, 'get_the_edit_post_link' ) ) {
			$link = $post->get_the_edit_post_link( $text, $before, $after, $id, $class );
		} else {
			ob_start();
			edit_post_link( $text, $before, $after, $id, $class );
			$link = ob_get_clean();
		}

		return $link;
	}

	static function the_nexts( Array $nexts = NULL, $selected = NULL, $edit_form_advanced = FALSE ) {
		print( self::get_the_tabs( $nexts, 'nexts', $selected, $edit_form_advanced ) );
	}

	static function the_tabs( Array $tabs = NULL, $selected = NULL, $edit_form_advanced = FALSE ) {
		print( self::get_the_tabs( $tabs, 'tabs', $selected, $edit_form_advanced ) );
	}

	static function get_the_tabs( Array $tabs = NULL, String $class = 'tabs', $selected = NULL, $edit_form_advanced = FALSE ) {
		// TODO: make this configurable based on the 'tab' option in the metaboxes
		// in order of appearance
		// We cannot use jQuery tabs here
		// because the #tab divs are nto children of the ul controller in this file
		global $post;

		CB2_Query::ensure_correct_class( $post );

		if ( is_null( $tabs ) && method_exists( $post, 'tabs' ) ) {
			$tabs = $post->tabs( $edit_form_advanced );
		}

		if ( count( $tabs ) ) {
			print ( "<ul class='cb2-$class'>" );
			$tab_keys = array_keys( $tabs );
			$last_tab = end( $tab_keys );
			foreach ( $tabs as $id => $title ) {
				$class = ( $last_tab == $id ? 'cb2-last' : '' );
				if ( $selected == $id ) $class .= ' cb2-selected';
				switch ( $id ) {
					case 'postdivrich':
						if ( post_type_supports( $post->post_type, 'editor' ) )
							print ( "<li class='$class'><a href='#$id'><span>" . __( $title ) .    '</span></a></li>' );
						break;
					default:
						print ( "<li class='$class'><a href='#$id'><span>" . __( $title ) .    '</span></a></li>' );
				}
			}
			print ( "</ul><!-- end cb2-tabs -->\n\n" );
		}
	}


	public static function post_class( $class = NULL, $post_id = null ) {
		// Copied from post-template.php
		// Separates classes with a single space, collates classes for post DIV
		echo 'class="' . join( ' ', self::get_post_class( $class, $post_id ) ) . '"';
	}

	public static function get_post_class( $class = '', $post_id = null ) {
		// Replaces the normal get_post_class()
		// normal get_post_class() will get wrong data and not be cached
		// because it requests meta-data with 'post' meta-type
		// which will cache with the wrong cache key
		// thus causing new SQL query every request
		// Some Copied and edited from post-template.php
		global $post;

		$post_type = $post->post_type;

		if ( ! is_null( $post_id ) )
			throw new Exception( 'Explicit post_id not supported in CB2::post_class()' );

		// Copied from post-template.php
		$classes = array();

		if ( $class ) {
			if ( ! is_array( $class ) ) {
				$class = preg_split( '#\s+#', $class );
			}
			$classes = array_map( 'esc_attr', $class );
		} else {
			// Ensure that we always coerce class to being an array.
			$class = array();
		}

		if ( ! $post )
			return $classes;

		// New: Object based classes
		if ( is_object( $post ) && method_exists( $post, 'classes' ) ) {
			if ( $post_classes = $post->classes() ) {
				$classes = array_merge( $classes, preg_split( '#\s+#', $post_classes ) );
			}
		}

		$classes[] = 'post-' . $post->ID;
		if ( ! is_admin() )
			$classes[] = $post_type;
		$classes[] = 'status-' . $post->post_status;
		$classes[] = 'type-' . $post_type;
		if ( strpos( $post_type, '-' ) !== FALSE )
			$classes[] = 'type-' . CB2_Query::substring_before( $post_type ); // Additional CB2 class

		// Removed: Post Format
		// Removed: Post requires password.
		// Removed: Post thumbnails.
		// Removed: sticky for Sticky Posts
		// hentry for hAtom compliance
		$classes[] = 'hentry';
		// Removed: All public taxonomies

		$classes = array_map( 'esc_attr', $classes );

		// Filters the list of CSS classes for the current post.
		// Prevent the WP_DEBUG check
		remove_filter( 'post_class', 'cb2_post_class_check', 10, 3 );
		$classes = apply_filters( 'post_class', $classes, $class, $post->ID );
		add_filter( 'post_class', 'cb2_post_class_check', 10, 3 );

		return array_unique( $classes );
	}

	public static function is_list( $post = '' ) {
		global $wp_query;

		if ( ! isset( $wp_query ) ) {
			_doing_it_wrong( __FUNCTION__, __( 'Conditional query tags do not work before the query is run. Before then, they always return false.' ), '3.1.0' );
			return false;
		}

		return $wp_query->is_list;
	}

	// -------------------------------------------------------------------------------------
	public static function the_content( $content ) {
		global $post;

		if ( $post ) {
			$post_type = $post->post_type;
			if ( $Class = CB2_PostNavigator::post_type_Class( $post_type ) ) {
				$post_class = CB2_Query::ensure_correct_class( $post );
				$post       = &$post_class;
				if ( method_exists( $post, 'get_the_content' ) )
					$content = $post->get_the_content();
			}
		}

		return $content;
	}

	public static function the_logs() {
		print( self::get_the_logs() );
	}

	public static function get_the_logs() {
		global $post;
		return ( is_object( $post ) && method_exists( $post, 'get_the_logs' ) ? $post->get_the_logs() : '' );
	}

	public static function the_ajax_edit_screen( $the_post = NULL, $context = 'normal', $form_action = 'editpost' ) {
		global $post;
		$outer_post   = $post;
		$use_the_post = ( ! is_null( $the_post ) && $the_post != $post );
		if ( $use_the_post ) {
			$post = $the_post;
			setup_postdata( $post );
		}

		$ID        = $post->ID;
		$post_type = $post->post_type;
		$action    = CB2_Query::pass_through_query_string( NULL, array(
			'ID'          => $post->ID,
			'action'      => 'save',
			'post_type'   => $post_type,
		) );

		self::the_hidden_form( $post_type, array(), $post, $form_action );
		self::the_meta_boxes( NULL, $context );
		print( '</div>' ); // /the_hidden_form

		if ( $use_the_post ) {
			$post = $outer_post;
			setup_postdata( $post );
		}
	}

	public static function the_custom_meta_box( String $id, String $name, $value = NULL, String $type = 'text', String $placeholder = '' ) {
		$placeholder_text = __( $placeholder );
		$name_text        = __( $name );
		$value_esc        = esc_attr( $value );
		print( "<div class='cmb2-wrap form-table postbox'>
			<div class='cmb-row cmb-type-$type cmb2-id-$id' data-fieldtype='$type'>
				<div class='cmb-th'>
					<label for='cb2-$id'>$name_text</label>
				</div>
				<div class='cmb-td'>
					<input type='$type' class='cmb2-$type' name='$id' value='$value_esc' id='cb2-$id' placeholder='$placeholder_text' />
				</div>
			</div>
		</div>" );
	}

	public static function the_hidden_form( String $post_type = '', Array $classes = array(), $post = NULL, String $template_type = 'editpost', String $post_url = NULL ) {
		$user_ID          = get_current_user_id();
		$post_ID          = ( $post ? $post->ID : CB2_CREATE_NEW );
		$nonce_action     = 'update-post_' . $post_ID;
		$form_action      = 'cb2_template_save';
		$active_post_lock = '';
		$referer          = wp_get_referer();
		$post_author      = ( $post ? $post->post_author : NULL );
		$post_status      = ( $post ? $post->post_status : NULL );
		$form_extra       = ( $post ? "<input type='hidden' id='post_ID' name='post_ID' value='" . esc_attr($post_ID) . "' />" : '' );
		if ( $post )
			array_push( $classes, 'cb2-with-template-post' );
		if ( is_null( $post_url ) ) {
			$post_url = CB2_Query::pass_through_query_string( admin_url( 'admin-ajax.php' ),
				array(
					'context'       => 'save',
					'template_type' => $template_type,
					'ID'            => $post_ID,
					'post_type'     => $post_type,
				),
				array(
					'cb2_load_template', // Used to load the template, would override the save
					'page'
				)
			);
		}

		// Texts
		$cancel_text     = __( 'Cancel' );
		$save_text       = __( 'Save' );
		$advanced_text   = __( 'advanced' );
		$fullscreen_text = __( 'full screen' );

		// Form start
		$classes_string = implode( ' ', $classes );
		print( "<div id='cb2-ajax-edit-form' action='$post_url' class='cb2-ajax-edit-form $classes_string'>" );

		if ( WP_DEBUG )
			print( "<div class='cb2-WP_DEBUG-small'>global post [$post_type/$post_ID]</div>" );

		// TODO: move CB2 ID => post_ID ?
		print( "<input type='hidden' id='ID' name='ID' value='" . esc_attr($post_ID) . "' />" );

		// ------------------------------- Copied from edit-form-advanced.php
		?>
		<?php wp_nonce_field($nonce_action); ?>
		<input type="hidden" id="user-id" name="user_ID" value="<?php echo (int) $user_ID ?>" />
		<input type="hidden" id="hiddenaction" name="action" value="<?php echo esc_attr( $form_action ) ?>" />
		<input type="hidden" id="originalaction" name="originalaction" value="<?php echo esc_attr( $form_action ) ?>" />
		<input type="hidden" id="post_author" name="post_author" value="<?php echo esc_attr( $post_author ); ?>" />
		<input type="hidden" id="post_type" name="post_type" value="<?php echo esc_attr( $post_type ) ?>" />
		<input type="hidden" id="original_post_status" name="original_post_status" value="<?php echo esc_attr( $post_status ) ?>" />
		<input type="hidden" id="referredby" name="referredby" value="<?php echo $referer ? esc_url( $referer ) : ''; ?>" />
		<?php if ( ! empty( $active_post_lock ) ) { ?>
		<input type="hidden" id="active_post_lock" value="<?php echo esc_attr( implode( ':', $active_post_lock ) ); ?>" />
		<?php
		}
		if ( 'draft' != get_post_status( $post ) )
			wp_original_referer_field(true, 'previous');

		echo $form_extra;

		wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
		wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
		// ------------------------------- /end edit-form-advanced.php

		// ----------------------------- buttons
		print( "<button class='cb2-popup-form-save cb2-save-visible-ajax-form'>$save_text</button>" );
		print( "<div class='dashicons-before dashicons-admin-page cb2-advanced'><a id='cb2-fullscreen' href='#'>$fullscreen_text</a></div>" );
		if ( WP_DEBUG )
			print( "<div class='dashicons-before dashicons-admin-tools cb2-advanced'><a href='#'>$advanced_text</a></div>" );
	}

	static public function the_form_bottom( Array $extra_buttons = array() ) {
		$cancel_text   = __( 'Cancel' );
		$save_text     = __( 'Save' );
		print( "<div class='cb2-actions'>
			<a class='cb2-popup-form-cancel' onclick='tb_remove();' href='#'>$cancel_text</a>
			<button class='cb2-popup-form-save cb2-save-visible-ajax-form'>$save_text</button>" );
		foreach ( $extra_buttons as $id => $value ) {
			$class = ( substr( $value, 0, 5 ) == 'TODO:' ? 'cb2-todo' : '' );
			$value = preg_replace( '/^[A-Z]+:\s*/', '', $value );
			print( "<button class='$class cb2-popup-form-$id'>$value</button>" );
		}
		print( '</div>
			</div>' );
	}

	public static function the_meta_boxes( $the_post = NULL, $context = 'normal' ) {
		global $wp_meta_boxes;
		global $post;
		$outer_post   = $post;
		$use_the_post = ( ! is_null( $the_post ) && $the_post != $post );
		if ( $use_the_post ) {
			$post = $the_post;
			setup_postdata( $post );
		}

		// Populate the $wp_meta_boxes array
		$post_type = $post->post_type;
		if ( is_null( $wp_meta_boxes ) ) {
			$screen = WP_Screen::get( $post_type );
			set_current_screen( $screen );
			do_action( 'add_meta_boxes', $post_type,  $post );
			do_action( "add_meta_boxes_$post_type", $post );
			if ( WP_DEBUG && is_null( $wp_meta_boxes ) )
				throw new Exception( "Failed to load meta boxes for [$post_type]" );
		}

		// In case the cache is for another post
		wp_cache_delete( $post->ID, 'post_meta' );

		// If the post is a CB2_CREATE_NEW
		// then the meta-box will get_metadata(), fail, and present the defaults instead
		// cache it under post 1 as the get_metadata() will abs(ID)
		$old_metadata = NULL;
		if ( $post->ID == CB2_CREATE_NEW) {
			if ( WP_DEBUG )
				print( "<div class='cb2-WP_DEBUG-small'>CB2_CREATE_NEW: setting post_meta cache ID 1</div>" );
			$metadata = array();
			foreach ( (array) $post as $name => $value )
				if ( ! is_array( $value ) )
					$metadata[$name] = array( (string) $value );
			$old_metadata = wp_cache_get( 1, 'post_meta' );
			wp_cache_set( 1, $metadata, 'post_meta' );
		}

		// Output the metaboxes
		// do_meta_boxes()
		//   => CMB2_hookup->metabox_callback( $post, $box )
		//   => new CMB2_Field(...)
		//     => CMB2_Field->get_data()
		//     => CMB2_Field->data_args()
		//     => CMB2->current_object_type() returns post only
		// so we need to redirect the DB for:
		//   	=> get_metadata(... 'post')
		if ( $post ) CB2_Query::redirect_wpdb_for_post_type( $post_type );
		do_meta_boxes( WP_Screen::get( $post_type ), $context, $post );
		if ( $post ) CB2_Query::unredirect_wpdb();

		if ( $old_metadata )
			wp_cache_set( 1, $old_metadata, 'post_meta' );

		if ( $use_the_post ) {
			$post = $outer_post;
			setup_postdata( $post );
		}
	}

	public static function the_meta_box( $box_name, $the_post = NULL, $throw_if_not_found = TRUE ) {
		global $wp_meta_boxes;
		global $post;
		$outer_post   = $post;
		$use_the_post = ( ! is_null( $the_post ) && $the_post != $post );
		if ( $use_the_post ) {
			$post = $the_post;
			setup_postdata( $post );
		}

		// In case the cache is for another post
		wp_cache_delete( $post->ID, 'post_meta' );

		// Populate the $wp_meta_boxes array
		$post_type = $post->post_type;
		if ( is_null( $wp_meta_boxes ) ) {
			$screen = WP_Screen::get( $post_type );
			set_current_screen( $screen );
			do_action( 'add_meta_boxes', $post->post_type,  $post );
			do_action( "add_meta_boxes_{$post->post_type}", $post );
			if ( WP_DEBUG && is_null( $wp_meta_boxes ) )
				throw new Exception( "Failed to load meta boxes for [$post_type]" );
		}

		// Find it
		// TODO: can we, should we, speed this up?
		$box  = NULL;
		$page = NULL;
		foreach ( $wp_meta_boxes as $page => $contexts ) {
			foreach ( $contexts as $context => $priorities ) {
				foreach ( $priorities as $priority => $boxes ) {
					if ( isset( $boxes[ $box_name ] ) ) {
						$box = $boxes[ $box_name ];
						break;
					}
				}
				if ( $box ) break;
			}
			if ( $box ) break;
		}

		// Output the metaboxes
		// do_meta_boxes()
		//   => CMB2_hookup->metabox_callback( $post, $box )
		//   => new CMB2_Field(...)
		//     => CMB2_Field->get_data()
		//     => CMB2_Field->data_args()
		//     => CMB2->current_object_type() returns post only
		// so we need to redirect the DB for:
		//   	=> get_metadata(... 'post')
		if ( $box ) {
			if ( $post ) CB2_Query::redirect_wpdb_for_post_type( $post_type );
			$object = $post;
			// Taken from do_meta_boxes():
			echo '<div id="' . $box['id'] . '" class="postbox ' . postbox_classes($box['id'], $page ) . '" ' . '>' . "\n";
			echo "<h2 class='hndle'><span>{$box['title']}</span></h2>\n";
			echo '<div class="inside">' . "\n";
			call_user_func( $box['callback'], $object, $box ); // CMB2_hookup::metabox_callback
			echo "</div>\n";
			echo "</div>\n";
			// /end do_meta_boxes()
			if ( $post ) CB2_Query::unredirect_wpdb();
		} else if ( $throw_if_not_found ) {
			krumo( $wp_meta_boxes );
			throw new Exception( "Box [$box_name] not found" );
		}

		if ( $use_the_post ) {
			$post = $outer_post;
			setup_postdata( $post );
		}
	}

	public static function the_title( $before = '', $after = '', $HTML = TRUE ) {
		print( self::get_the_title( $before, $after, $HTML ) );
	}

	public static function the_link() {
		$url   = get_the_permalink();
		$title = get_the_title();
		print( "<a href='$url'>$title</a>" );
	}

	public static function get_the_title( $before, $after, $HTML = TRUE ) {
		global $post;

		// Unlike the_content() above, this is not a filter call
		$title = ( property_exists( $post, 'post_title' ) ? $post->post_title : 'no title' );

		if ( $post ) {
			$post_type = $post->post_type;
			if ( $Class = CB2_PostNavigator::post_type_Class( $post_type ) ) {
				$post_class = CB2_Query::ensure_correct_class( $post );
				$post       = &$post_class;
				if ( method_exists( $post, 'get_the_title' ) )
					$title = $post->get_the_title( $HTML );
			}
		}
		return $before . $title . $after;
	}

	public static function template_path() {
		return dirname( dirname( dirname( __FILE__ ) ) ) . '/templates';
	}
}

// TODO: move the_content() filter to CB2_Templates utilities files
add_filter( 'the_content', array( 'CB2', 'the_content' ), 1 );


