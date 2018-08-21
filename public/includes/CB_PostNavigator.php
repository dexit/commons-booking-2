<?php
class CB_PostNavigator {
  protected function __construct( &$posts = NULL ) {
    $this->zero_array = array();
    if ( is_null( $posts ) ) $this->posts = &$this->zero_array;
    else                     $this->posts = &$posts;

    // WP_Post default values
    if ( ! property_exists( $this, 'post_status' ) )   $this->post_status   = 'publish';
    if ( ! property_exists( $this, 'post_password' ) ) $this->post_password = '';
    if ( ! property_exists( $this, 'post_author' ) )   $this->post_author   = 1;
    if ( ! property_exists( $this, 'post_date' ) )     $this->post_date     = date( 'c' );
    if ( ! property_exists( $this, 'post_modified' ) ) $this->post_modified = date( 'c' );
    if ( ! property_exists( $this, 'post_excerpt' ) )  $this->post_excerpt  = $this->get_the_excerpt();
    if ( ! property_exists( $this, 'post_content' ) )  $this->post_content  = $this->get_the_content();
    if ( ! property_exists( $this, 'post_date_gmt' ) ) $this->post_date_gmt = $this->post_date;
    if ( ! property_exists( $this, 'post_modified_gmt' ) ) $this->post_modified_gmt = $this->post_modified;
		if ( ! property_exists( $this, 'filter' ) )        $this->filter = 'suppress'; // Prevent WP_Query from converting objects to WP_Post

    // This will cause subsequent WP_Post::get_instance() calls to return $this
    // rather than attempting to access the wp_posts table
    if ( property_exists( $this, 'ID' ) ) wp_cache_add( $this->ID, $this, 'posts' );
  }

  public function __toString() {return (string) $this->ID;}

  // ------------------------------------------------- Navigation
  function have_posts() {
    return current( $this->posts );
  }

  function next_post() {
    $post = current( $this->posts );
    next( $this->posts );
    return $post;
  }

  function rewind_posts() {
    reset( $this->posts );
    $this->post = NULL;
  }

  function &the_post() {
    global $post;
    $post = $this->next_post();
    $this->setup_postdata( $post );
    return $post;
  }

  // ------------------------------------------------- Properties
  function is_feed()    {return FALSE;}
  function is_page()    {return FALSE;}
  function is_single()  {return FALSE;}
  function get( $name ) {return NULL;}

  function add_actions( &$actions ) {}

  function setup_postdata( $post ) {
		global $id, $authordata, $currentday, $currentmonth, $page, $pages, $multipage, $more, $numpages;

    if ( ! $post ) {
        return;
    }

    $id = (int) $post->ID;

    $authordata = get_userdata($post->post_author);

    $currentday = mysql2date('d.m.y', $post->post_date, false);
    $currentmonth = mysql2date('m', $post->post_date, false);
    $numpages = 1;
    $multipage = 0;
    $page = $this->get( 'page' );
    if ( ! $page )
        $page = 1;

    /*
     * Force full post content when viewing the permalink for the $post,
     * or when on an RSS feed. Otherwise respect the 'more' tag.
     */
    if ( $post->ID === get_queried_object_id() && ( $this->is_page() || $this->is_single() ) ) {
        $more = 1;
    } elseif ( $this->is_feed() ) {
        $more = 1;
    } else {
        $more = 0;
    }

    $content = $post->post_content;
    if ( false !== strpos( $content, '<!--nextpage-->' ) ) {
        $content = str_replace( "\n<!--nextpage-->\n", '<!--nextpage-->', $content );
        $content = str_replace( "\n<!--nextpage-->", '<!--nextpage-->', $content );
        $content = str_replace( "<!--nextpage-->\n", '<!--nextpage-->', $content );

        // Ignore nextpage at the beginning of the content.
        if ( 0 === strpos( $content, '<!--nextpage-->' ) )
            $content = substr( $content, 15 );

        $pages = explode('<!--nextpage-->', $content);
    } else {
        $pages = array( $post->post_content );
    }

    /**
     * Filters the "pages" derived from splitting the post content.
     *
     * "Pages" are determined by splitting the post content based on the presence
     * of `<!-- nextpage -->` tags.
     *
     * @since 4.4.0
     *
     * @param array   $pages Array of "pages" derived from the post content.
     *                       of `<!-- nextpage -->` tags..
     * @param WP_Post $post  Current post object.
     */
    $pages = apply_filters( 'content_pagination', $pages, $post );

    $numpages = count( $pages );

    if ( $numpages > 1 ) {
        if ( $page > 1 ) {
            $more = 1;
        }
        $multipage = 1;
    } else {
        $multipage = 0;
    }

    /**
     * Fires once the post data has been setup.
     *
     * @since 2.8.0
     * @since 4.1.0 Introduced `$this` parameter.
     *
     * @param WP_Post  $post The Post object (passed by reference).
     * @param WP_Query $this The current Query object (passed by reference).
     */
    do_action_ref_array( 'the_post', array( &$post, &$this ) );

    return true;
	}

  function templates( $context = NULL, $type = NULL ) {
		$templates     = array();
		$post_type     = $this->post_type;

		// Gather possible combinations for this post_type
		// with sub-types
		$post_sub_type = $post_type;
		do {
			if ( $context && $type ) array_push( $templates, "$context-$post_sub_type-$type" );
			if ( $context )          array_push( $templates, "$context-$post_sub_type" );
			if ( $type )             array_push( $templates, "$post_sub_type-$type" );
			array_push( $templates, $post_sub_type );

			if ( strpos( $post_sub_type, '-' ) === FALSE ) $post_sub_type = NULL;
			else $post_sub_type = CB_Query::substring_before( $post_sub_type );
		} while ( $post_sub_type );

		// Sanitize
		$templates_valid   = array();
		$cb2_template_path = cb2_template_path();
		foreach ( $templates as $template ) {
			$template_path = "$cb2_template_path/$template.php";
			if ( file_exists( $template_path ) )
				array_push( $templates_valid, $template );
		}

		if ( ! count( $templates_valid) ) {
			$templates_tried = implode( ',', $templates );
			throw new Exception( "No valid templates found for [$post_type] in [$cb2_template_path].\nTried: $templates_tried" );
		}

		return $templates_valid;
	}

  // ------------------------------------------------- Output
  function the_json_content( $options = NULL ) {
    print( $this->get_the_json_content( $options ) );
  }

  function get_the_json_content( $options = NULL ) {
    wp_json_encode( $this, $options );
  }

  function the_content( $more_link_text = null, $strip_teaser = false ) {
		// Borrowed from wordpress
		// https://developer.wordpress.org/reference/functions/the_content/
    $content = $this->get_the_content( $more_link_text, $strip_teaser );
    $content = apply_filters( 'the_content', $content );
    $content = str_replace( ']]>', ']]&gt;', $content );
    echo $content;
  }

  function get_the_excerpt() {
		return '';
  }

  function get_the_content() {
		return '';
  }

  function classes() {
		return '';
  }

  // ------------------------------------------------- Saving
  function post_meta() {
		// Default to this
		// post_updated will sanitize the list against the database table columns and types
		$meta = array();

		foreach ( $this as $name => $value ) {
			if ( ! is_null( $value ) && ! is_array( $value ) )
				$meta[ $name ] = CB_Database::to_string( $name, $value );
		}

		return $meta;
	}

  function post_args() {
		// Taken from https://developer.wordpress.org/reference/functions/wp_insert_post/
		$args = array(
			'post_title'  => ( property_exists( $this, 'name' ) ? $this->name : '' ),
			'post_status' => 'publish',
			'post_type'   => $this->post_type(),
			'meta_input'  => $this->post_meta(),

			/*
			'post_author' => 1,
			'post_content' => '',
			'post_content_filtered' => '',
			'post_excerpt' => '',
			'comment_status' => '',
			'ping_status' => '',
			'post_password' => '',
			'to_ping' =>  '',
			'pinged' => '',
			'post_parent' => 0,
			'menu_order' => 0,
			'guid' => '',
			'import_id' => 0,
			'context' => '',
			*/
		);
		if ( property_exists( $this, 'ID' ) && ! is_null( $this->ID ) )
			$args[ 'ID' ] = $this->ID;

		return $args;
  }

  function save_posts_linkage() {
  }

  function save( $save_posts_links = FALSE ) {
		global $wpdb;

		$error = NULL;
		$args  = $this->post_args();
		if ( WP_DEBUG && FALSE ) var_dump( $args );

		if ( isset( $args [ 'ID' ] ) ) {
			// Direct existing update
			// wp_update_post() triggers the hooks below
			if ( $args [ 'ID' ] == 0 ) throw new Exception( "Attempt to update post 0" );
			$result = wp_update_post( $args );
			if ( is_wp_error( $result ) ) {
				$error = $wpdb->last_error;
			}
		} else {
			// Create new post in wp_posts
			// wp_insert_post() will not trigger any hooks in this intergration
			// wp_update_post() triggers the hooks below
			// (int|WP_Error) The post ID on success. The value 0 or WP_Error on failure.
			$id   = wp_insert_post( $args );
			if ( is_wp_error( $id ) ) {
				$error = $wpdb->last_error;
			} else {
				$args = array( 'ID' => $id );
				// Run the update action to move it to the custom DB structure
				$result = wp_update_post( $args );
				if ( is_wp_error( $result ) ) {
					$error = $wpdb->last_error;
				} else {
					// Everything worked
					// Store the ID and id for further updates
					// Other saving objects can now use the id to write their records:
					//   e.g. ->period->id
					$id       = $wpdb->insert_id;
					$this->ID = CB_Query::ID_from_id_post_type( $id, $this->post_type() );
					$this->id = $id;
				}
			}
		}

		if ( $error ) {
			print( "<div id='error-page'><p>$error</p></div>" );
			exit();
		} else {
			// save_posts_linkage() is for writing other records in the DB
			// e.g. CB_PeriodItem writes a period_group as well as its own record
			if ( $save_posts_links ) $this->save_posts_linkage();
		}

		return $this;
  }
}

