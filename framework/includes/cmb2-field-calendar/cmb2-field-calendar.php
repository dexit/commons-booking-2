<?php

/*
Plugin Name: CMB2 Field Type: Calendar
Plugin URI: https://wordpress.org/plugins/commons-booking
GitHub Plugin URI: https://wordpress.org/plugins/commons-booking
Description: Calendar field type for CMB2.
Version: 0.1.3
Author: Annesley Newholm
License: MIT
*/

define('CMB2_CALENDAR_FIELD_NAME', 'calendar');

class CMB2_Field_Calendar {

    /**
     * @var string Version
     */
    const VERSION = '0.1.0';

    /**
     * CMB2_Field_Calendar constructor.
     */
    public function __construct() {
        add_filter( 'cmb2_render_calendar',   [ $this, 'render_calendar' ], 10, 5 );
        add_filter( 'cmb2_sanitize_calendar', [ $this, 'sanitize_calendar' ], 10, 4 );
    }

    /**
     * Render the field
     *
     * @param $field
     * @param $field_escaped_value
     * @param $object_id
     * @param $object_type
     * @param $field_type_object
     */
    public function render_calendar(
        CMB2_Field $field,
        $field_escaped_value,
        $object_id,
        $object_type,
        CMB2_Types $field_type_object
    ) {
				global $post;

				CB2_Query::ensure_correct_class( $post );
        $this->enqueue_scripts();
				if ( version_compare( CMB2_VERSION, '2.2.2', '>=' ) ) {
					$field_type_object->type = new CMB2_Type_Text( $field_type_object );
        }

        // Inputs
        $url              = $_SERVER['REQUEST_URI'];
        $options          = $field->options();

        $default_size     = 'P2M';
        $today            = CB2_DateTime::today();
        $next2month       = $today->clone()->add( $default_size )->endTime();
				$startdate_string = ( isset( $_REQUEST['startdate'] )   ? $_REQUEST['startdate'] : $today->format(  CB2_Query::$datetime_format ) );
				$enddate_string   = ( isset( $_REQUEST['enddate']   )   ? $_REQUEST['enddate']   : $next2month->format( CB2_Query::$datetime_format ) );
        $schema_type      = ( isset( $_REQUEST['schema_type'] ) ? $_REQUEST['schema_type'] : CB2_Week::$static_post_type );
        $startdate        = new CB2_DateTime( $startdate_string );
        $enddate          = new CB2_DateTime( $enddate_string );

        // Defaults
        $default_query    = array(
					'post_status'    => CB2_Post::$PUBLISH,
					'post_type'      => CB2_PeriodInst::$all_post_types,
					'posts_per_page' => -1,
					'order'          => 'ASC',          // defaults to post_date
					'date_query'     => array(
						array(
							// post_modified_gmt is the end date of the period instance
							'column' => 'post_modified_gmt',
							'after'  => $startdate_string,
						),
						array(
							// post_gmt is the start date of the period instance
							'column' => 'post_date_gmt',
							'before' => $enddate_string,
						),
						'compare' => $schema_type,
					),
				);

        // Analyse options
        $style          = ( isset( $options[ 'style' ] )         ? $options[ 'style' ]    : NULL );
        $query_options  = ( isset( $options[ 'query' ] )         ? $options[ 'query' ]    : array() );
        $query_args     = array_merge( $default_query, $query_options );
				if ( ! isset( $query_args['meta_query']['blocked_clause'] ) )
					$query_args['meta_query']['blocked_clause'] = 0; // Prevent default

        // Convert %...% values to values on the post
        // TODO: move this in to the DisplayStrategy
        if ( $post->post_status != CB2_Post::$AUTODRAFT ) {
					CB2_Query::array_walk_paths( $query_args, $post );
				}

				// Request periodinsts
        $context        = ( isset( $options[ 'context' ] )       ? $options[ 'context' ]  : 'list' );
        $template       = ( isset( $options[ 'template' ] )      ? $options[ 'template' ] : NULL );
				$template_args  = ( isset( $options[ 'template-args' ] ) ? $options[ 'template-args' ] : array() );
        $Class_display_strategy = ( isset( $options[ 'display-strategy' ] )  ? $options[ 'display-strategy' ]  : 'WP_Query' );
				if ( $Class_display_strategy == 'WP_Query' ) {
					$wp_query = new WP_Query( $query_args );
				} else {
					$wp_query = $Class_display_strategy::factory_from_query_args( $query_args );
				}

				// Context Menu Actions
				$wp_query->actions = ( isset( $options[ 'actions' ] ) ? $options[ 'actions' ] : array() );

        // View handling
        $view_is_calendar_class = ( $schema_type == CB2_Week::$static_post_type ? 'selected' : 'unselected' );
        $view_is_list_class     = ( $schema_type == '' ? 'selected' : 'unselected' );
        $viewless_url           = preg_replace( '/&schema_type=[^&]*/', '', $url );

        // Template args
				if ( ! is_array( $template_args ) ) $template_args = array( $template_args );
				$template_args[ 'post' ]    = $post;
				$template_args[ 'options' ] = $options;
				$template_args[ 'query' ]   = $wp_query;

				// TODO: convert classes() functions to returning arrays
        $classes          = ( isset( $options['classes'] )
					? ( is_array( $options['classes'] ) ? $options['classes'] : array( $options['classes'] ) )
					: array()
				);
				if ( method_exists( $post, 'classes' ) )
					array_push( $classes, $post->classes() );

        // Render
        $the_calendar_pager = CB2::get_the_calendar_pager( $startdate, $enddate );
        $classes_string     = implode( ' ', $classes );
				print( "<div class='cb2-javascript-form cb2-calendar $classes_string'>" );
				if ( $style != 'bare' ) print( "<div class='entry-header'>
						<div class='cb2-view-selector'>View as:
							<a class='cb2-$view_is_calendar_class' href='$viewless_url&schema_type=week'>calendar</a>
							| <a class='cb2-$view_is_list_class' href='$viewless_url&schema_type='>list</a></div>
						$the_calendar_pager
					</div>"
				);

				print( "<div class='cb2-calendar'>
						<div class='entry-content clear'>" );
							if ( $style != 'bare' ) CB2::the_calendar_header( $wp_query );
							print( '<ul class="cb2-subposts">' );
							CB2::the_inner_loop( $template_args, $wp_query, $context, $template );
							print( '</ul>' );
							if ( $style != 'bare' ) CB2::the_calendar_footer( $wp_query );
						print( "</div>
					</div>"
				);

				if ( $style != 'bare' ) print( $the_calendar_pager );
				print( "</div>" );

        // Debug
        if ( WP_DEBUG ) {
					$post_types = array();
					$post_count = count( $wp_query->posts );
					foreach ( $wp_query->posts as $postx )
						$post_types[$postx->post_type] = $postx->post_type;
					print( "<div class='cb2-WP_DEBUG' style='border:1px solid #000;padding:3px;font-size:10px;background-color:#fff;margin:1em 0em;'>" );
					print( "<b>$post_count</b> posts returned" );
					print( ' containing only <b>[' . implode( ', ', $post_types ) . "]</b> post_types" );
					print( ' <a class="cb2-calendar-krumo-show">more...</a><div class="cb2-calendar-krumo" style="display:none;">' );
					// loop_start not happened yet. ->posts will be wrong
					krumo( $wp_query );
					print( '</div></div>' );
				}

        $field_type_object->_desc( true, true );
    }

    /**
     * Sanitize values
     */
    public function sanitize_calendar( $override_value, $value, $object_id, $field_args ) {
        return $value;
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_script( 'cmb2-calendar-main', plugins_url( 'assets/js/main.js',    __FILE__ ), NULL, self::VERSION );
        wp_enqueue_style(  'cmb2-calendar-main', plugins_url( 'assets/css/style.css', __FILE__ ), NULL, self::VERSION );
    }
}

new CMB2_Field_Calendar();
