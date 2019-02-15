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

        $this->enqueue_scripts();

        if ( version_compare( CMB2_VERSION, '2.2.2', '>=' ) ) {
            $field_type_object->type = new CMB2_Type_Text( $field_type_object );
        }

        $default_size     = 'P2M';
        $today            = CB2_DateTime::today();
        $next2month       = $today->clone()->add( $default_size )->endTime();

        // Inputs
        $url              = $_SERVER['REQUEST_URI'];
        $options          = $field->options();
				$startdate_string = ( isset( $_GET['startdate'] )   ? $_GET['startdate'] : $today->format(  CB2_Query::$datetime_format ) );
				$enddate_string   = ( isset( $_GET['enddate']   )   ? $_GET['enddate']   : $next2month->format( CB2_Query::$datetime_format ) );
        $view             = ( isset( $_GET['view'] ) ? $_GET['view'] : CB2_Week::$static_post_type );
        $startdate        = new CB2_DateTime( $startdate_string );
        $enddate          = new CB2_DateTime( $enddate_string );

        // Defaults
        $default_query    = array(
					'post_status'    => CB2_Post::$PUBLISH,
					'post_type'      => CB2_PeriodItem::$all_post_types,
					'posts_per_page' => -1,
					'order'          => 'ASC',          // defaults to post_date
					'date_query'     => array(
						'after'   => $startdate_string,
						'before'  => $enddate_string,
						'compare' => $view,
					),
				);

        // Analyse options
        $context       = ( isset( $options[ 'context' ] )       ? $options[ 'context' ]  : 'list' );
        $template      = ( isset( $options[ 'template' ] )      ? $options[ 'template' ] : NULL );
				$template_args = ( isset( $options[ 'template-args' ] ) ? $options[ 'template-args' ] : array() );
        $Class_display_strategy = ( isset( $options[ 'display-strategy' ] )  ? $options[ 'display-strategy' ]  : 'WP_Query' );
        $style         = ( isset( $options[ 'style' ] )         ? $options[ 'style' ]    : NULL );
        $query_options = ( isset( $options[ 'query' ] )         ? $options[ 'query' ]    : array() );
        $query_args    = array_merge( $default_query, $query_options );
				if ( ! isset( $options['meta_query']['blocked_clause'] ) )
					$options['meta_query']['blocked_clause'] = 0; // Prevent default

        // Convert %...% values to values on the post
        if ( $post->post_status != CB2_Post::$AUTODRAFT ) {
					CB2_Query::ensure_correct_class( $post );
					CB2_Query::array_walk_paths( $query_args, $post );
				}

				// Request period items
				if ( $Class_display_strategy == 'WP_Query' ) {
					$wp_query = new WP_Query( $query_args );
				} else {
					$wp_query = $Class_display_strategy::factory_from_query_args( $query_args );
				}
        // Context Menu Actions
				$wp_query->actions = ( isset( $options[ 'actions' ] ) ? $options[ 'actions' ] : array() );

        // View handling
        $view_is_calendar_class = ( $view == CB2_Week::$static_post_type ? 'selected' : 'unselected' );
        $view_is_list_class     = ( $view == '' ? 'selected' : 'unselected' );
        $viewless_url           = preg_replace( '/&view=[^&]*/', '', $url );

        // Template args
				if ( ! is_array( $template_args ) ) $template_args = array( $template_args );
				$template_args[ 'post' ]    = $post;
				$template_args[ 'options' ] = $options;
				$template_args[ 'query' ]   = $wp_query;

        // Render
        $the_calendar_pager = CB2::get_the_calendar_pager( $startdate, $enddate );
				print( "<div class='cb2-javascript-form cb2-calendar'>" );
				if ( $style != 'bare' ) print( "<div class='entry-header'>
						<div class='hide-if-no-js alignright actions bulkactions'>
							<label for='cb2-calendar-bulk-action-selector-top' class='screen-reader-text'>Select bulk action</label>
							<!-- no @name on these form elements because it is a *nested* form
								it is submitted only with JavaScript
								@js-name => @name during submission
							-->
							<select class='hide-if-no-js' id='cb2-calendar-bulk-action-selector-top' js-name='do_action'>
								<option value=''>Bulk Actions</option>
								<option value='CB2_PeriodEntity::block'>Block</option>
								<option value='CB2_PeriodEntity::unblock'>UnBlock</option>
							</select>
							<input type='button' class='hide-if-no-js button action' value='Apply'>
						</div>
						<div class='cb2-view-selector'>View as:
							<a class='cb2-$view_is_calendar_class' href='$viewless_url&view=week'>calendar</a>
							| <a class='cb2-$view_is_list_class' href='$viewless_url&view='>list</a></div>
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