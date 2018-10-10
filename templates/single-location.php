<?php echo "template : single-location.php <br>"; ?>
<?php
/**
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package WordPress
 * @subpackage Twenty_Seventeen
 * @since 1.0
 * @version 1.0
 */


				// Show the Timeframe Items for this Location for the next month
				$location_ID = $post->ID;
				$startdate   = new DateTime();
				$enddate     = (clone $startdate)->add( new DateInterval('P1M') );
				$view_mode   = 'item';

				$query       = new WP_Query( array(
					'post_status'    => CB2_PUBLISH,
					'post_type'      => CB_PeriodItem::$all_post_types,
					'posts_per_page' => -1,
					'order'          => 'ASC',        // defaults to post_date
					'date_query'     => array(
						'after'   => '2018-07-01', //$startdate->format( CB_Query::$datetime_format ),
						'before'  => $enddate->format( CB_Query::$datetime_format ),
						'compare' => $view_mode,
					),
					'meta_query' => array(
						'relation' => 'AND',
						'location_ID_clause' => array(
							'key'   => 'location_ID',
							'value' => $location_ID,
						),
						// This allows PeriodItem-* with no item_ID
						// It uses a NOT EXISTS
						// Items with an item_ID which is not $item_ID will not be returned
						'relation' => 'OR',
						'without_meta_item_ID' => CB_Query::$without_meta,
					)
				) );
				the_inner_loop( $query, 'list' );

