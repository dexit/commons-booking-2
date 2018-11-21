<?php
class CB2_PeriodGroup extends CB2_DatabaseTable_PostNavigator implements JsonSerializable {
  public static $database_table = 'cb2_period_groups';
	public static $all = array();
  static $static_post_type = 'periodgroup';
  public static $post_type_args = array(
		'menu_icon' => 'dashicons-admin-settings',
		'label'     => 'Period Groups',
  );
  public $periods  = array();

  static function selector_metabox( $multiple = FALSE ) {
		$period_group_options = CB2_Forms::period_group_options( TRUE );
		$period_groups_count  = count( $period_group_options ) - 1;
		$plural  = ( $multiple ? 's' : '' );
		$title   = "Period Group$plural";
		$name    = "period_group_ID$plural";
		$type    = ( $multiple ? 'multicheck' : 'radio' );
		$default = ( isset( $_GET[$name] ) ? $_GET[$name] : CB2_CREATE_NEW );
		if ( $multiple ) $default = explode( ',', $default );

		return array(
			'title'      => __( $title, 'commons-booking-2' ) .
												" <span class='cb2-usage-count-ok'>$period_groups_count</span>",
			'show_names' => FALSE,
			'context'    => 'side',
			'closed'     => TRUE,
			'fields'     => array(
				array(
					'name'    => __( $title, 'commons-booking-2' ),
					'id'      => $name,
					'type'    => $type,
					'default' => $default,
					'options' => $period_group_options,
				),
			),
		);
	}

  static function database_table_name() { return self::$database_table; }

  static function database_table_schemas( $prefix ) {
 		$safe_updates_off     = CB2_Database::$safe_updates_off;
		$safe_updates_restore = CB2_Database::$safe_updates_restore;
		$period_item_posts    = "{$prefix}cb2_view_perioditem_posts";
		$period_item_meta     = "{$prefix}cb2_view_perioditemmeta";
		$postmeta             = "{$prefix}postmeta";
		$id_field             = CB2_Database::id_field( __class__ );

		return array( array(
			'name'    => self::$database_table,
			'columns' => array(
				// TYPE, (SIZE), CB2_UNSIGNED, NOT NULL, CB2_AUTO_INCREMENT, DEFAULT, COMMENT
				$id_field     => array( CB2_INT,     (11),   CB2_UNSIGNED, CB2_NOT_NULL, CB2_AUTO_INCREMENT ),
				'name'        => array( CB2_VARCHAR, (1024), NULL,     CB2_NOT_NULL, FALSE, 'period group' ),
				'description' => array( CB2_VARCHAR, (2048), NULL,     NULL,     FALSE, NULL ),
				'author_ID'   => array( CB2_BIGINT,  (20),   CB2_UNSIGNED,     CB2_NOT_NULL, FALSE, 1 ),
			),
			'primary key' => array( $id_field ),
			'foreign keys' => array(
				'author_ID' => array( 'users', 'ID' ),
			),
			'many to many' => array(
				'cb2_period_group_period' => array(
					$id_field, 'cb2_periods', 'period_id',
				),
			),
		) );
	}

  static function database_views( $prefix ) {
		return array(
			'cb2_view_periodgroup_posts' => "select (`p`.`period_group_id` + `pt_pg`.`ID_base`) AS `ID`,1 AS `post_author`,'2018-01-01' AS `post_date`,'2018-01-01' AS `post_date_gmt`,`p`.`description` AS `post_content`,`p`.`name` AS `post_title`,'' AS `post_excerpt`,'publish' AS `post_status`,'closed' AS `comment_status`,'closed' AS `ping_status`,'' AS `post_password`,(`p`.`period_group_id` + `pt_pg`.`ID_base`) AS `post_name`,'' AS `to_ping`,'' AS `pinged`,'2018-01-01' AS `post_modified`,'2018-01-01' AS `post_modified_gmt`,'' AS `post_content_filtered`,0 AS `post_parent`,'' AS `guid`,0 AS `menu_order`,'periodgroup' AS `post_type`,'' AS `post_mime_type`,0 AS `comment_count`,`p`.`period_group_id` AS `period_group_id`,(select group_concat((`{$prefix}cb2_period_group_period`.`period_id` + `pt2`.`ID_base`) separator ',') from (`{$prefix}cb2_period_group_period` join `{$prefix}cb2_post_types` `pt2` on((`pt2`.`post_type` = 'period'))) where (`{$prefix}cb2_period_group_period`.`period_group_id` = `p`.`period_group_id`) group by `{$prefix}cb2_period_group_period`.`period_group_id`) AS `period_IDs` from (`{$prefix}cb2_period_groups` `p` join `{$prefix}cb2_post_types` `pt_pg` on((`pt_pg`.`post_type` = 'periodgroup')))",
			'cb2_view_periodgroupmeta'   => "select ((`po`.`period_group_id` * 10) + `pt`.`ID_base`) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodgroup_id`,'period_IDs' AS `meta_key`,`po`.`period_IDs` AS `meta_value` from (`{$prefix}cb2_view_periodgroup_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = 'periodgroup'))) union all select (((`po`.`period_group_id` * 10) + `pt`.`ID_base`) + 1) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodgroup_id`,'period_group_id' AS `meta_key`,`po`.`period_group_id` AS `meta_value` from (`{$prefix}cb2_view_periodgroup_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = 'periodgroup')))",
		);
	}

	function post_type() {return self::$static_post_type;}

	static function metaboxes() {
		$metaboxes = array();
		array_push( $metaboxes, CB2_Period::selector_metabox( TRUE, TRUE ) ); // Multiple, context primary
		return $metaboxes;
	}

  static function &factory_from_properties( &$properties, &$instance_container = NULL ) {
		$object = self::factory(
			$properties['ID'],
			$properties['post_title'],
			CB2_PostNavigator::get_or_create_new( $properties, 'period_IDs', $instance_container )
		);

		self::copy_all_wp_post_properties( $properties, $object );

		return $object;
	}

  static function &factory(
		$ID,
		$name,
		$periods = array()
  ) {
    // Design Patterns: Factory Singleton with Multiton
		if ( $ID && isset( self::$all[$ID] ) ) {
			$object = self::$all[$ID];
    } else {
			$reflection = new ReflectionClass( __class__ );
			$object     = $reflection->newInstanceArgs( func_get_args() );
    }

    return $object;
  }

  public function __construct(
		$ID,
		$name,
		$periods = array()
  ) {
		CB2_Query::assign_all_parameters( $this, func_get_args(), __class__ );
		parent::__construct( $this->periods );
		if ( $ID ) self::$all[$ID] = $this;
  }

  function add_period( $period ) {
		array_push( $this->periods, $period );
  }

  function row_actions( &$actions, $post ) {
		unset( $actions['view'] );
	}

	static function do_action_attach( CB2_User $user, $args ) {
		// Link the Period to the PeriodGroup
		global $wpdb;

		if ( ! $user->can( 'edit_posts' ) )
			throw new Exception( "User does not have sufficient permissions to attach" );

		// Convert inputs
		$period_ID        = $args['period_ID'];
		$period_group_IDs = $args['period_group_IDs'];
		$period_group_IDs = explode( ',', $period_group_IDs );
		$period_group_ids = array();
		foreach ( $period_group_IDs as $period_group_ID ) {
			array_push( $period_group_ids, CB2_PostNavigator::id_from_ID_with_post_type( $period_group_ID, self::$static_post_type ) );
		}
		$period_id = CB2_PostNavigator::id_from_ID_with_post_type( $period_ID, CB2_Period::$static_post_type );

		// Delete existing
		$table = "{$wpdb->prefix}cb2_period_group_period";
		foreach ( $period_group_ids as $period_group_id ) {
			$wpdb->delete( $table, array(
				'period_group_id' => $period_group_id,
				'period_id'       => $period_id,
			) );
		}

		// Add
		foreach ( $period_group_ids as $period_group_id ) {
			$wpdb->insert( $table, array(
				'period_group_id' => $period_group_id,
				'period_id'       => $period_id,
			) );
		}
	}

	static function do_action_detach( CB2_User $user, $args ) {
		// Link the Period to the PeriodGroup
		global $wpdb;

		if ( ! $user->can( 'edit_posts' ) )
			throw new Exception( "User does not have sufficient permissions to detach" );

		// Convert inputs
		$period_ID        = $args['period_ID'];
		$period_group_IDs = $args['period_group_IDs'];
		$period_group_IDs = explode( ',', $period_group_IDs );
		$period_group_ids = array();
		foreach ( $period_group_IDs as $period_group_ID ) {
			array_push( $period_group_ids, CB2_PostNavigator::id_from_ID_with_post_type( $period_group_ID, self::$static_post_type ) );
		}
		$period_id = CB2_PostNavigator::id_from_ID_with_post_type( $period_ID, CB2_Period::$static_post_type );

		// Delete existing
		$table = "{$wpdb->prefix}cb2_period_group_period";
		foreach ( $period_group_ids as $period_group_id ) {
			$wpdb->delete( $table, array(
				'period_group_id' => $period_group_id,
				'period_id'       => $period_id,
			) );
		}
	}

	function manage_columns( $columns ) {
		$columns['periods'] = 'Periods <a href="admin.php?page=cb2-periods">view all</a>';
		$columns['entities'] = 'Entities';
		$this->move_column_to_end( $columns, 'date' );
		return $columns;
	}

	function custom_columns( $column ) {
		switch ( $column ) {
			case 'periods':
				print( $this->summary_periods() );
				$add_new_text = __( 'add new period' );
				$attach_text  = __( 'attach existing period' );
				$page         = 'cb2-post-new';
				$post_type    = 'period';
				$add_link     = "admin.php?page=$page&period_group_IDs=$this->ID&post_type=$post_type";
				$attach_link  = "admin.php?page=cb2-periods&period_group_IDs=$this->ID&row_actions=attach";
				print( "<a href='$add_link'>$add_new_text</a>" );
				print( " | <a href='$attach_link'>$attach_text</a>" );
				break;
			case 'entities':
				$wp_query_page_name = "paged-column-$column";
				$current_page       = ( isset( $_GET[$wp_query_page_name] ) ? $_GET[$wp_query_page_name] : 1 );
				$wp_query           = new WP_Query( array(
					'post_type'   => array( 'periodent-global', 'periodent-location', 'periodent-timeframe', 'periodent-user' ),
					'meta_query'  => array(
						'period_group_ID_clause' => array(
							'key'   => 'period_group_ID',
							'value' => $this->ID,
						),
					),
					'posts_per_page' => CB2_ADMIN_COLUMN_POSTS_PER_PAGE,
					'page'           => $current_page,
				) );

				if ( $wp_query->have_posts() ) {
					print( '<ul class="cb2-admin-column-ul">' );
					CB2::the_inner_loop( $wp_query, 'admin', 'summary' );
					print( '</ul>' );
				} else {
					print( '<div>' . __( 'No Entities' ) . '</div>' );
				}

				print( '<div class="cb2-column-pagination">' . paginate_links( array(
					'base'         => 'admin.php%_%',
					'total'        => $wp_query->max_num_pages,
					'current'      => $current_page,
					'format'       => "?$wp_query_page_name=%#%",
				) ) . '</div>' );
				break;
		}
	}

	function summary_periods() {
		$html = ( '<ul class="cb2-period-list">' );
		$count = count( $this->periods );

		foreach ( $this->periods as $period ) {
			$edit_link   = $period->get_the_edit_post_link( 'edit' );

			$detach_link = '';
			if ( $count > 1 ) {
				$detach_text = 'detach';
				$detach_url  = "?page=cb2-period-groups&period_ID=$period->ID&period_group_IDs=$this->ID&do_action=CB2_PeriodGroup::detach";
				$detach_link = " | <a href='$detach_url'>$detach_text</a></li>";
			}

			$summary     = $period->summary();
			$html       .= "<li>$summary $edit_link $detach_link</li>";
		}
		$html .= '</ul>';

		return $html;
	}

	function summary() {
		$html = ( $this->post_title ? $this->post_title : $this->ID );
		$period_count = count( $this->periods );
		if ( $period_count > 1 )
			$html .= " <span class='cb2-usage-count-ok' title='Several Periods'>$period_count</span>";
		$html .= ' ' . $this->get_the_edit_post_link( 'edit' );
		return $html;
	}

  function classes() {
		return '';
  }

	protected function reference_count( $not_from = NULL ) {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT count(*)
				from {$wpdb->prefix}cb2_view_periodent_posts
				where period_group_ID = %d",
				$this->ID
			)
		);
	}

	function pre_post_delete( $from_periodentity = NULL, $direct = TRUE ) {
		// $from_periodentity has already been deleted in the Database
		global $wpdb;

		$reference_count      = $this->reference_count( $direct );
		$continue_with_delete = ! $reference_count;

		if ( $continue_with_delete ) {
			// We intend to delete it
			// clear all references to sub-objects first
			$rows_affected = $wpdb->delete(
				"{$wpdb->prefix}cb2_period_group_period",
				array( 'period_group_id' => $this->id() )
			);
			if ( CB2_DEBUG_SAVE ) {
				$Class = get_class( $this );
				$ID    = $this->ID;
				print( "<div class='cb2-WP_DEBUG-small'>" );
				print( "$Class::pre_post_delete($ID)d [$rows_affected] many-to-many period rows" );
				print( "</div>" );
			}
		}

		// Continue CB2_PeriodGroup::delete_row() and traverse sub-objects (CB2_Periods)?
		return $continue_with_delete;
	}

  function post_post_update() {
		global $wpdb;

		if ( CB2_DEBUG_SAVE ) {
			$Class = get_class( $this );
			print( "<div class='cb2-WP_DEBUG'>$Class::post_post_update($this->ID) dependencies</div>" );
		}

		// Link the Period to the PeriodGroup
		$table = "{$wpdb->prefix}cb2_period_group_period";
		$wpdb->delete( $table, array(
			'period_group_id' => $this->id()
		) );
		foreach ( $this->periods as $period ) {
			$wpdb->insert( $table, array(
				'period_group_id' => $this->id(),
				'period_id'       => $period->id(),
			) );
		}
  }

  function jsonSerialize() {
		return $this;
	}
}
