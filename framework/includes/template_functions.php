<?php
/**
 * TODO: for template-tags use file template-tags.php?
 * similar with cb2_get_template_part.php?
 */

// -------------------------------------------------------------------------------------
// -------------------------------------------------------------------------------------
// -------------------------------------------------------------------------------------
function the_inner_loop( $post_navigator = NULL, $context = 'list', $template_type = NULL, $before = '', $after = '' ) {
	echo get_the_inner_loop( $post_navigator, $context, $template_type, $before, $after );
}

function get_the_inner_loop( $post_navigator = NULL, $context = 'list', $template_type = NULL, $before = '', $after = '' ) {
	global $post;
	$html = '';

	if ( $context == 'single' )
		throw new Exception( 'the_inner_loop() should never be called with context [single]' );

	if ( ! $post_navigator ) $post_navigator = $post;
	if ( $post_navigator instanceof CB_PostNavigator || $post_navigator instanceof WP_Query ) {
		if ( $post_navigator->have_posts() ) {
			while ( $post_navigator->have_posts() ) : $post_navigator->the_post();
				$html .= $before;
				$html .= cb2_get_template_part( CB2_TEXTDOMAIN, $post->templates( $context, $template_type ), '', array(), TRUE );
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
		throw new Exception( 'the_inner_loop() only available for CB_PostNavigator or WP_Query' );
	}

	return $html;
}

function the_calendar_footer( $query = NULL, $classes = '', $type = 'td', $before = '<tfoot><tr>', $after = '</tr></tfoot>' ) {
	echo get_the_calendar_footer( $query, $classes, $type, $before, $after );
}

function get_the_calendar_footer( $query = NULL, $classes = '', $type = 'td', $before = '<tfoot><tr>', $after = '</tr></tfoot>' ) {
	return get_the_calendar_header( $query, $classes, $type, $before, $after );
}

function the_calendar_header( $query = NULL, $classes = '', $type = 'th', $before = '<thead><tr>', $after = '</tr></thead>' ) {
	echo get_the_calendar_header( $query, $classes, $type, $before, $after );
}

function get_the_calendar_header( $query = NULL, $classes = '', $type = 'th', $before = '<thead><tr>', $after = '</tr></thead>' ) {
	global $wp_query;
	$html = '';
	$schema_type = NULL;

	if ( ! $query ) $query = $wp_query;
	if ( $query && isset( $query->query['date_query']['compare'] ) )
		$schema_type = $query->query['date_query']['compare'];

	switch ( $schema_type ) {
		case CB_Week::$static_post_type:
			// TODO: wordpress WeekStartsOn
			$html .= ( $before );
			foreach ( CB_Query::$days as $dayname ) {
				$html .= ( "<$type>$dayname</$type>" );
			}
			$html .= ( $after );
			break;
		default:
			// Do nothing
	}

	return $html;
}

function the_period_status_type_name() {
	echo get_the_period_status_type_name();
}

function get_the_period_status_type_name() {
	global $post;
	return ( is_object( $post ) && method_exists( $post, 'period_status_type_name' ) ? $post->period_status_type_name() : '' );
}

function the_summary() {
	print( get_the_summary() );
}

function get_the_summary() {
	global $post;
	return ( is_object( $post ) && method_exists( $post, 'summary' ) ? $post->summary() : '' );
}

function the_time_period( $format = 'H:i' ) {
	print( get_the_time_period( $format ) );
}

function get_the_time_period( $format = 'H:i' ) {
	global $post;
	return ( is_object( $post ) && method_exists( $post, 'get_the_time_period' ) ? $post->get_the_time_period( $format ) : '' );
}

function is_current() {
	// Indicates if the time post contains the current time
	// e.g. if the CB_Day is today, or the CB_Week contains today
	global $post;
	return is_object( $post ) && property_exists( $post, 'is_current' ) && $post->is_current;
}

function is_top_priority() {
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

function cb2_get_field( $field_name, $class = '', $date_format = 'H:i' ) {
	global $post;
	$object  = $post;
	$value   = NULL;
	$missing = FALSE;

	if ( is_object( $object ) ) {
		// Syntax: object->object->field_name
		if ( strstr( $field_name, '->' ) !== FALSE ) {
			$object_hierarchy = explode( '->' , $field_name );
			$field_name = array_pop( $object_hierarchy ); // Last is the fieldname
			foreach ( $object_hierarchy as $object_name ) {
				if ( property_exists( $object, $object_name ) && is_object( $object->$object_name ) )
					$object = $object->$object_name;
				else $missing = TRUE;
			}
		}

		if ( ! $missing ) {
			$custom_render_function_name = "field_value_string_$field_name";
			if ( method_exists( $object, $custom_render_function_name ) ) {
				$value = $object->{$custom_render_function_name}( $object, $class = '', $date_format );
			}

			else if ( property_exists( $object, $field_name ) ) {
				$value = $object->$field_name;
				if ( is_object( $value ) ) {
					if ( method_exists( $value, 'get_field_this' ) ) {
						$value = $value->get_field_this( $class, $date_format );
					} else {
						switch ( get_class( $value ) ) {
							case 'DateTime':
								$value = $value->format( $date_format );
								break;
							case 'WP_Post':
								$permalink = get_the_permalink( $value, TRUE );
								$value     = "<a href='$permalink' title='view $value'>$value</a>";
								break;
							case 'WP_User':
								$value = $value->user_login;
								break;
						}
					}
				}
			}
		}
	}

	return $value;
}

function cb2_the_field( $field_name, $class = '', $date_format = 'H:i' ) {
	echo cb2_get_field( $field_name, $class, $date_format );
}

function cb2_the_fields( $field_names, $before = '<td>', $after = '</td>', $class = '', $date_format = 'H:i' ) {
	global $post;

	if ( is_object( $post ) ) {
		// TODO: allow better placement of class here
		// that respects the possibility of complex tags being passed in
		$before_open = ( substr( $before, -1 ) == '>' ? substr( $before, 0, -1 ) : $before );
		foreach ( $field_names as $field_name ) {
			$class = 'cb2-' . str_replace( '_', '-', str_replace( '->', '-', $field_name ) );
			echo $before_open, ' class="', $class, '">';
			echo "<span class='cb2-field-name'>$field_name";
			echo '<span class="cb2-colon">:</span></span>';
			echo '<span class="cb2-field-value">';
			cb2_the_field( $field_name, $class, $date_format );
			echo '</span>', $after;
		}
	}
}

function the_debug( $before = '', $afer = '' ) {
	global $post;
	if ( WP_DEBUG && is_object( $post ) && method_exists( $post, 'get_the_debug' ) ) {
		echo $post->get_the_debug( $before, $afer );
	}
}

function the_edit_post_link( $text = null, $before = '', $after = '', $id = 0, $class = 'post-edit-link' ) {
	global $post;
	if ( is_object( $post ) && method_exists( $post, 'get_the_edit_post_link' ) ) {
		echo $post->get_the_edit_post_link( $text, $before, $after, $id, $class );
	} else {
		edit_post_link( $text, $before, $after, $id, $class );
	}
}

function cb2_post_class( $classes, $class, $ID ) {
	$post_type = NULL;
	foreach ( $classes as $class ) {
		if ( substr( $class, 0, 5 ) == 'type-' ) {
			$post_type = substr( $class, 5 );
			break;
		}
	}

	if ( $post_type ) {
		if ( $Class = CB_Query::schema_type_class( $post_type ) ) {
			if ( property_exists( $Class, 'all' ) ) {
				$lookup = $Class::$all;
				if ( isset( $lookup[$ID] ) ) {
					if ( $object = $lookup[$ID] ) {
						// Add the objects classes()
						if ( $object_classes = $object->classes() ) {
							array_push( $classes, $object_classes );
						}
					} else throw new Exception( "Object [$ID] NULL in general $Class::\$all(" . count( $lookup ) . ") lookup" );
				} //else throw new Exception( "Object [$ID] not found in general $Class::\$all(" . count( $lookup ) . ") lookup" );
			} else throw new Exception( "$Class::\$all lookup property required" );
		}
	}

	return $classes;
}
add_filter( 'post_class', 'cb2_post_class', 10, 3 ); /* @TODO: retire, filter is in public/cb.php */

function is_list( $post = '' ) {
	global $wp_query;

	if ( ! isset( $wp_query ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Conditional query tags do not work before the query is run. Before then, they always return false.' ), '3.1.0' );
		return false;
	}

	return $wp_query->is_list;
}

// -------------------------------------------------------------------------------------
// -------------------------------------------------------------------------------------
// -------------------------------------------------------------------------------------
// TODO: move functions to CB_Templates utilities files
add_filter( 'the_content', 'cb2_the_content', 1 );
//add_filter( 'the_content', 'cb2_template_include_custom_plugin_templates' );

/*
add_filter( "get_template_part_{$slug}", $slug, $name )
add_action( 'get_template_part_template-parts/post/content', 'cb2_get_template_part', 10, 2 );
function cb2_get_template_part( $slug, $name ) {
	print( "cb2_get_template_part( $slug, $name )" );
}
*/

function cb2_the_content( $content ) {
	global $post;
	if ( $post ) {
		$post_type = $post->post_type;
		if ( $Class = CB_Query::schema_type_class( $post_type ) ) {
			$post_class = CB_Query::ensure_correct_class( $post );
			$post       = &$post_class;
			if ( method_exists( $post, 'get_the_content' ) )
				$content = $post->get_the_content();
		}
	}
	return $content;
}

function cb2_template_path() {
	return dirname( dirname( dirname( __FILE__ ) ) ) . '/templates';
}

function cb2_template_include_custom_plugin_templates( $content ) {
	// Plugin provided default template partials
	// CB_Class->templates() should provide templates in priority order
	// e.g. $template = single-item.php (from theme or wordpress)
	// $post->templates( wp_query ) = array( single-location.php, single.php )
	// TODO: cache template dir listing
	global $post;
	$current_template_path = false;

	if ( $post instanceof CB_PostNavigator ) {
		if ( $current_template_path ) {
			$current_template_stub     = substr( basename( $current_template_path ), 0, -4 );
			// $current_is_theme_template = strstr( $current_template_path, 'content/themes/' );
		}

		// Get class templates and the current template suggestion
		$post_template_suggestions = NULL;
		$post_type                 = $post->post_type;

		$context                   = CB_Query::template_loader_context();

		if (is_single()) {
			$context = "single";
		} else {
			$context = "list";
		}

			echo "context: " . $context;
		$post_template_suggestions = $post->templates( $context );

		// Read the plugin templates directory
		// TODO: lazy cache this and check for contents:
		// ! preg_match( '|Template Name:(.*)$|mi', file_get_contents( $full_path ), $header )
		$plugin_templates   = array();
		$templates_dir_path = cb2_template_path();
		$templates_dir      = dir( $templates_dir_path );
		while ( FALSE !== ( $template_name = $templates_dir->read() ) ) {
			if ( substr( $template_name, -4 ) == '.php' && strchr( $template_name, '-' ) ) {
				$template_stub = substr( $template_name, 0, -4 );
				$plugin_templates[ $template_stub ] = "$templates_dir_path/$template_stub.php";
			}
		}

		// For each priority order suggestion for this class and context
		foreach ( $post_template_suggestions as $template_stub ) {
			// 1) If the current template is already the priority suggestion then use it
			if ( $current_template_path && $template_stub == $current_template_stub ) break;
			// 2) If the plugin has a template for this priority suggesion then use it
			else if ( isset( $plugin_templates[ $template_stub ] ) ) {
				$current_template_path = $plugin_templates[ $template_stub ];
				break;
			}
			// 3) Check for next priority
		}




	}

	if ($current_template_path) {
		ob_start ();
        include $current_template_path;
        $template = ob_get_contents ();
        ob_end_clean();
        $content .= $template;

	}
	return $content;


}

/*
function cb2_form_elements( $form ) {
  // Process all normal shortcodes in CF7 forms
  // CF7 is not used for the booking form management now
  // So this function is no longer necessary
  return do_shortcode( $form );
}
add_filter( 'wpcf7_form_elements', 'cb2_form_elements' );
*/
