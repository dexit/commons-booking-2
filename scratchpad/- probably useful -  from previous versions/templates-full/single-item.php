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
get_header(); ?>

<div class="wrap">
	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">

		<?php
			/* Start the Loop */
			while ( have_posts() ) : the_post();

				get_template_part( 'template-parts/post/content', get_post_format() );

				// Show the Timeframe Items for this Location
				// for the next month
				/*
				$item_ID     = $post->ID;
				$startdate   = new DateTime();
				$enddate     = (clone $startdate)->add( new DateInterval('P1M') );
				$view_mode   = 'week'; // CB_Weeks

				$query = new WP_Query( array(
					'post_status'    => array(
						'publish',
						// PeriodItem-automatic (CB_PeriodItem_Automatic)
						// one is generated for each day between the dates
						// very useful for iterating through to show a calendar
						// They have a post_status = auto-draft
						'auto-draft'
					),
					// Although these PeriodItem-* are requested always
					// The compare below will decide
					// which generated CB_(Object) set will actually be the posts array
					'post_type'      => CB_PeriodItem::$all_post_types,
					'posts_per_page' => -1,
					'order'          => 'ASC',        // defaults to post_date
					'date_query'     => array(
						'after'   => '2018-07-01', //$startdate->format( 'c' ),
						'before'  => $enddate->format( 'c' ),
						// This sets which CB_(ObjectType) is the resultant primary posts array
						// e.g. CB_Weeks generated from the CB_PeriodItem records
						'compare' => $view_mode,
					),
					'meta_query' => array(
						// Restrict to the current CB_Item
						'item_ID_clause' => array(
							'key'     => 'item_ID',
							'value'   => array( $item_ID, CB_Query::$meta_NULL ),
							'compare' => 'IN',
							'type'    => 'NUMERIC', // This causes the 'NULL' to be changed to NULL
						),
						// This allows PeriodItem-* with no item_ID
						// It uses a NOT EXISTS
						// Items with an item_ID which is not $item_ID will not be returned
						'relation' => 'OR',
						'without_meta_item_ID' => CB_Query::$without_meta,
					)
				) );
				?>

				<div class="cb2-calendar">
					<div class="entry-content">
						<table class="cb2-subposts"><tbody>
							<?php the_inner_loop( $query, 'list', 'available' ); ?>
						</tbody></table>
					</div>
				</div>

				<?php
				*/
				// If comments are open or we have at least one comment, load up the comment template.
				if ( comments_open() || get_comments_number() ) :
					comments_template();
				endif;

				the_post_navigation( array(
					'prev_text' => '<span class="screen-reader-text">' . __( 'Previous Post', 'twentyseventeen' ) . '</span><span aria-hidden="true" class="nav-subtitle">' . __( 'Previous', 'twentyseventeen' ) . '</span> <span class="nav-title"><span class="nav-title-icon-wrapper">' . twentyseventeen_get_svg( array( 'icon' => 'arrow-left' ) ) . '</span>%title</span>',
					'next_text' => '<span class="screen-reader-text">' . __( 'Next Post', 'twentyseventeen' ) . '</span><span aria-hidden="true" class="nav-subtitle">' . __( 'Next', 'twentyseventeen' ) . '</span> <span class="nav-title">%title<span class="nav-title-icon-wrapper">' . twentyseventeen_get_svg( array( 'icon' => 'arrow-right' ) ) . '</span></span>',
				) );

			endwhile; // End of the loop.
			?>

		</main><!-- #main -->
	</div><!-- #primary -->
	<?php get_sidebar(); ?>
</div><!-- .wrap -->

<?php get_footer();
