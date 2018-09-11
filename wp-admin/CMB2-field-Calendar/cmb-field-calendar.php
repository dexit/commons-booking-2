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

        $yesterday        = (new DateTime())->sub( new DateInterval( 'P2D' ) );
        $nextmonth        = (clone $yesterday)->add( new DateInterval( 'P1M' ) );

        // Inputs
        $url              = $_SERVER['REQUEST_URI'];
        $options          = $field->args( 'options' );
				$startdate_string = ( isset( $_GET['startdate'] )   ? $_GET['startdate'] : $yesterday->format( CB_Query::$date_format ) );
				$enddate_string   = ( isset( $_GET['enddate']   )   ? $_GET['enddate']   : $nextmonth->format( CB_Query::$date_format ) );
        $view             = ( isset( $_GET['view'] ) ? $_GET['view'] : CB_Week::$static_post_type );

        // Defaults
        $default_query    = array(
					'post_status'    => array( CB2_PUBLISH, CB2_AUTODRAFT ),   // auto-draft indicates the pseudo Period A created for each day
					'post_type'      => CB_PeriodItem::$all_post_types,
					'posts_per_page' => -1,
					'order'          => 'ASC',          // defaults to post_date
					'date_query'     => array(
						'after'   => $startdate_string,
						'before'  => $enddate_string,
						'compare' => $view,
					),
        );

        // Analyse options
        $query_options = ( isset( $options[ 'query' ] )    ? $options[ 'query' ]    : array() );
        $context       = ( isset( $options[ 'context' ] )  ? $options[ 'context' ]  : 'list' );
        $template      = ( isset( $options[ 'template' ] ) ? $options[ 'template' ] : NULL );
        $query_args    = array_merge( $default_query, $query_options );
				if ( isset( $query_args['meta_query'] ) ) {
					// Include the auto-draft which do not have meta
					$meta_query = &$query_args['meta_query'];
					if ( ! isset( $meta_query[ 'relation' ] ) ) $meta_query[ 'relation' ] = 'OR';
					if ( ! isset( $meta_query[ 'without_meta' ] ) ) $meta_query[ 'without_meta' ] = CB_Query::$without_meta;
					if ( ! isset( $meta_query[ 'items' ][ 'relation' ] ) ) $meta_query[ 'items' ][ 'relation' ] = 'AND';
				}

        // Request period items
        $query = new WP_Query( $query_args );

        // Date handling
        $startdate      = new DateTime( $startdate_string );
        $enddate        = new DateTime( $enddate_string );
        $pagesize       = $startdate->diff( $enddate );
        $timeless_url   = preg_replace( '/&(start|end)date=[^&]*/', '', $url );

        $nextpage_start = (clone $enddate);
        $nextpage_end   = (clone $nextpage_start);
        $nextpage_end->add( $pagesize );
        $nextpage_start_string = $nextpage_start->format( CB_Query::$date_format );
        $nextpage_end_string   = $nextpage_end->format( CB_Query::$date_format );

        $prevpage_start = (clone $startdate);
        $prevpage_end   = (clone $prevpage_start);
        $prevpage_start->sub( $pagesize );
        $prevpage_start_string = $prevpage_start->format( CB_Query::$date_format );
        $prevpage_end_string   = $prevpage_end->format( CB_Query::$date_format );

        // View handling
        $view_is_calendar_class = ( $view == CB_Week::$static_post_type ? 'selected' : 'unselected' );
        $view_is_list_class     = ( $view == '' ? 'selected' : 'unselected' );
        $viewless_url = preg_replace( '/&view=[^&]*/', '', $url );

        // Render
				print( "
				<div class='cb2-calendar'>
					<div class='entry-header'>
						<div class='alignright actions bulkactions'>
							<label for='bulk-action-selector-top' class='screen-reader-text'>Select bulk action</label>
							<select name='bulk-action' id='bulk-action-selector-top'>
								<option value='-1'>Bulk Actions</option>
									<option value='block' class='hide-if-no-js'>Block</option>
									<option value='unblock'>UnBlock</option>
									<option value='sequence'>Set Sequence</option>
								</select>
								<input type='submit' id='doaction' class='button action' value='Apply'>
						</div>

						<div class='cb2-view-selector'>View:
							<a class='cb2-$view_is_calendar_class' href='$viewless_url&view=week'>calendar</a>
							| <a class='cb2-$view_is_list_class' href='$viewless_url&view='>list</a></div>
						<div class='cb2-calendar-pager'>
							<a href='$timeless_url&startdate=$prevpage_start_string&enddate=$prevpage_end_string'>&lt;&lt; previous page</a>
							| <a href='$timeless_url&startdate=$nextpage_start_string&enddate=$nextpage_end_string'>next page &gt;&gt;</a>
						</div>
					</div>
					<div class='entry-content clear'>
						<table class='cb2-subposts'><tbody>" );
				$outer_post  = $post;
				while ( $query->have_posts() ) : $query->the_post();
					print( $before );
					cb_get_template_part( CB_TEXTDOMAIN, $post->templates( $context, $template ) );
					print( $after );
				endwhile;
				print( "</table>
					</div>
					<div class='entry-footer'>
						<div class='cb2-calendar-pager'>
							<a href='$timeless_url&startdate=$prevpage_start_string&enddate=$prevpage_end_string'>&lt;&lt; previous page</a>
							| <a href='$timeless_url&startdate=$nextpage_start_string&enddate=$nextpage_end_string'>next page &gt;&gt;</a>
						</div>
					</div>
				</div>" );
				$post     = &$outer_post;

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
