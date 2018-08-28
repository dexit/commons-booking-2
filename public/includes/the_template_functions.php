<?php
// -------------------------------------------------------------------------------------
// -------------------------------------------------------------------------------------
// -------------------------------------------------------------------------------------
function the_inner_loop( $post_navigator = NULL, $context = 'list', $template_type = NULL, $before = '', $after = '' ) {
	global $post;

	if ( $context == 'single' )
		throw new Exception( 'the_inner_loop() should never be called with context [single]' );

	if ( ! $post_navigator ) $post_navigator = $post;
	if ( $post_navigator instanceof CB_PostNavigator || $post_navigator instanceof WP_Query ) {
		$outer_post  = $post;
		while ( $post_navigator->have_posts() ) : $post_navigator->the_post();
			print( $before );
			cb_get_template_part( CB_TEXTDOMAIN, $post->templates( $context, $template_type ) );
			print( $after );
		endwhile;
		$post     = &$outer_post;
	} else {
		throw new Exception( 'the_inner_loop() only available for CB_PostNavigator' );
	}
}

function is_current() {
	// Indicates if the time post contains the current time
	// e.g. if the CB_Day is today, or the CB_Week contains today
	global $post;
	return is_object( $post ) && property_exists( $post, 'is_current' ) && $post->is_current;
}

function get_the_field( $field_name, $class = '', $date_format = 'H:i' ) {
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

function the_field( $field_name, $class = '', $date_format = 'H:i' ) {
	echo get_the_field( $field_name, $class, $date_format );
}

function the_fields( $field_names, $before = '<td>', $after = '</td>', $class = '', $date_format = 'H:i' ) {
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
			the_field( $field_name, $class, $date_format );
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
add_filter( 'post_class', 'cb2_post_class', 10, 3 );

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
add_filter( 'the_content', 'cb2_template_include_ensure_correct_class', 1 );
//add_filter( 'the_content', 'cb2_template_include_custom_plugin_templates' );

/*
add_filter( "get_template_part_{$slug}", $slug, $name )
add_action( 'get_template_part_template-parts/post/content', 'cb2_get_template_part', 10, 2 );
function cb2_get_template_part( $slug, $name ) {
	var_dump(array($slug, $name));
}
*/

function cb2_template_include_ensure_correct_class( $template ) {
	global $post;
	if ( $post ) {
		$post_class = CB_Query::ensure_correct_class( $post );
		$post       = &$post_class;
	}
	return $template;
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
        ob_end_clean ();
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
