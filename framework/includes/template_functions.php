<?php
class CB2 {
	public static function the_inner_loop( $post_navigator = NULL, $context = 'list', $template_type = NULL, $before = '', $after = '' ) {
		echo self::get_the_inner_loop( $post_navigator, $context, $template_type, $before, $after );
	}

	public static function get_the_inner_loop( $post_navigator = NULL, $context = 'list', $template_type = NULL, $before = '', $after = '' ) {
		global $post;
		$html = '';
		$outer_post = $post;

		if ( $context == 'single' )
			throw new Exception( 'the_inner_loop() should never be called with context [single]' );

		if ( ! $post_navigator ) $post_navigator = $post;
		if ( $post_navigator instanceof CB2_PostNavigator || $post_navigator instanceof WP_Query ) {
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
					$setup_args_string = '';
					$action['link_text'] = ( isset( $action['link_text'] ) ? __( $action['link_text'] ) : __( 'Do Stuff' ) );
					if ( ! isset( $action['page'] ) ) $action['page'] = 'cb2-post-new';
					if ( ! isset( $action['base'] ) ) $action['base'] = 'admin.php';
					foreach ( $action as $name => $value ) {
						if ( strchr( $value, '%' ) ) {
							// e.g. date->time
							$property_path = explode( '->', substr( $value, 1, -1 ) );
							$properties    = (array) $post;
							foreach ( $property_path as $property_step ) {
								if ( is_array( $properties ) && isset( $properties[$property_step] ) ) {
									$value      = $properties[$property_step];
									$properties = (array) $value;
								} else if ( WP_DEBUG ) {
									krumo( $properties, $property_step );
									throw new Exception( "[$property_step] not found on object" );
								}
							}
						}
						$setup_args_string .= "$name=$value&";
					}
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
				// TODO: wordpress WeekStartsOn
				$html .= ( $before );
				foreach ( CB2_Query::$days as $dayname ) {
					$html .= ( "<$type>$dayname</$type>" );
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

	public static function the_time_period( $format = 'H:i' ) {
		echo self::get_the_time_period( $format );
	}

	public static function get_the_time_period( $format = 'H:i' ) {
		global $post;
		return ( is_object( $post ) && method_exists( $post, 'get_the_time_period' ) ? $post->get_the_time_period( $format ) : '' );
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

	public static function get_field( $field_name, $class = '', $date_format = 'H:i' ) {
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

	public static function the_field( $field_name, $class = '', $date_format = 'H:i' ) {
		echo self::get_field( $field_name, $class, $date_format );
	}

	public static function the_fields( $field_names, $before = '<td>', $after = '</td>', $class = '', $date_format = 'H:i' ) {
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
				self::the_field( $field_name, $class, $date_format );
				echo '</span>', $after;
			}
		}
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

	public static function post_class( $classes, $class, $ID ) {
		$post_type = NULL;
		foreach ( $classes as $class ) {
			if ( substr( $class, 0, 5 ) == 'type-' ) {
				$post_type = substr( $class, 5 );
				break;
			}
		}

		if ( $post_type ) {
			if ( $Class = CB2_PostNavigator::post_type_Class( $post_type ) ) {
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

	public static function template_path() {
		return dirname( dirname( dirname( __FILE__ ) ) ) . '/templates';
	}
}

// TODO: move functions to CB2_Templates utilities files
add_filter( 'post_class',  array( 'CB2', 'post_class'  ), 10, 3 ); /* @TODO: retire, filter is in public/cb.php */
add_filter( 'the_content', array( 'CB2', 'the_content' ), 1 );
