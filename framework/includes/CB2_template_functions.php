<?php
class CB2 {
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

	public static function is_published() {
		global $post;
		return ( $post && $post->post_status == CB2_Post::$PUBLISH );
	}

	public static function is_not_published() {
		return ! self::is_published();
	}

	public static function the_inner_loop( $post_navigator = NULL, $context = 'list', $template_type = NULL, $before = '', $after = '', $template_args = NULL ) {
		echo self::get_the_inner_loop( $post_navigator, $context, $template_type, $before, $after, $template_args );
	}

	public static function get_the_inner_loop( $post_navigator = NULL, $context = 'list', $template_type = NULL, $before = '', $after = '', $template_args = NULL ) {
		global $post;
		$html = '';
		$outer_post = $post;

		if ( $context == 'single' )
			throw new Exception( "the_inner_loop() should never be called with context [$context]" );

		if ( ! $post_navigator ) $post_navigator = $post;
		if ( $post_navigator instanceof CB2_PostNavigator || $post_navigator instanceof WP_Query ) {
			if ( $post_navigator instanceof WP_Query ) CB2_Query::reorganise_posts_structure( $post_navigator );
			if ( $post_navigator->have_posts() ) {
				while ( $post_navigator->have_posts() ) : $post_navigator->the_post();
					$html .= $before;
					CB2_Query::redirect_wpdb_for_post_type( $post->post_type() );
					$html .= cb2_get_template_part( CB2_TEXTDOMAIN, $post->templates( $context, $template_type ), '', $template_args, TRUE );
					CB2_Query::unredirect_wpdb();
					$html .= $after;
				endwhile;
				// NOTE: We have manually set the global $wp_query->post
				// when looping on WP_List_Tables because
				// WP_List_Tables does not use a normal post loop§
				//
				// https://codex.wordpress.org/Class_Reference/WP_Query
				// wp_reset_postdata() => global $wp_query->reset_postdata();
				//   global $post = global $wp_query->post;
				//   $this->setup_postdata( global $wp_query->post );
				wp_reset_postdata();
			}
		} else {
			throw new Exception( 'the_inner_loop() only available for CB2_PostNavigator or WP_Query' );
		}
		$post = $outer_post;

		return $html;
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

	public static function the_post_type() {
		echo get_post_type();
	}

	public static function the_calendar_footer( $query = NULL, $classes = '', $type = 'td', $before = '<tfoot><tr>', $after = '</tr></tfoot>' ) {
		echo self::get_the_calendar_footer( $query, $classes, $type, $before, $after );
	}

	public static function get_the_calendar_footer( $query = NULL, $classes = '', $type = 'td', $before = '<tfoot><tr>', $after = '</tr></tfoot>' ) {
		return self::get_the_calendar_header( $query, $classes, $type, $before, $after );
	}

	public static function the_calendar_header( $query = NULL, $classes = '', $type = 'th', $before = '<thead><tr>', $after = '</tr></thead>' ) {
		echo self::get_the_calendar_header( $query, $classes, $type, $before, $after );
	}

	public static function get_the_calendar_header( $query = NULL, $classes = '', $type = 'th', $before = '<thead><tr>', $after = '</tr></thead>' ) {
		global $wp_query;
		$html = '';
		$schema_type = NULL;

		if ( ! $query ) $query = $wp_query;
		if ( $query && isset( $query->query['date_query']['compare'] ) )
			$schema_type = $query->query['date_query']['compare'];

		switch ( $schema_type ) {
			case CB2_Week::$static_post_type:
				// TODO: use wordpress WeekStartsOn
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
		// Indicates if the perioditem is overridden by another overlapping perioditem
		global $post, $wp_query;

		$top_priority = TRUE;
		$show_overridden_periods = (
			isset( $wp_query->query_vars['show_overridden_periods'] ) &&
			$wp_query->query_vars['show_overridden_periods'] != 'no'
		);
		if ( is_object( $post ) && method_exists( $post, 'is_top_priority' ) )
			$top_priority = $post->is_top_priority();

		return $top_priority || $show_overridden_periods;
	}

	public static function can_select() {
		global $post;
		return $post && ! property_exists( $post, 'no_select' );
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

	public static function the_debug( $before = '', $afer = '' ) {
		global $post;
		if ( WP_DEBUG && is_object( $post ) && method_exists( $post, 'get_the_debug' ) ) {
			echo $post->get_the_debug( $before, $afer );
		}
	}

	public static function the_edit_post_link( $text = null, $before = '', $after = '', $id = 0, $class = 'post-edit-link' ) {
		global $post;
		if ( is_object( $post ) && method_exists( $post, 'get_the_edit_post_link' ) ) {
			echo $post->get_the_edit_post_link( $text, $before, $after, $id, $class );
		} else {
			edit_post_link( $text, $before, $after, $id, $class );
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
			$classes[] = $post->post_type;
		$classes[] = 'type-' . $post->post_type;
		$classes[] = 'status-' . $post->post_status;

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

	public static function the_title( $HTML = TRUE ) {
		print( self::get_the_title( $HTML ) );
	}

	public static function get_the_title( $HTML = TRUE ) {
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
		return $title;
	}

	public static function template_path() {
		return dirname( dirname( dirname( __FILE__ ) ) ) . '/templates';
	}
}

// TODO: move the_content() filter to CB2_Templates utilities files
add_filter( 'the_content', array( 'CB2', 'the_content' ), 1 );


