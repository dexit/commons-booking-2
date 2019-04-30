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

  static function selector_metabox( String $context = 'normal', Bool $multiple = FALSE, Bool $closed = TRUE, Array $classes = array() ) {
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
			'context'    => $context,
			'classes'    => $classes,
			'closed'     => $closed,
			'fields'     => array(
				array(
					'name'    => __( $title, 'commons-booking-2' ),
					'id'      => $name,
					'type'    => $type,
					'default' => $default,
					'options' => $period_group_options,
				),
				CB2_Query::metabox_nosave_indicator( $name ),
			),
		);
	}

	function split_period_at_instance( CB2_PeriodInst $period_inst, Array $new_period_properties = NULL, &$instance_container = NULL ) {
		return $this->split_period_from_date( $period_inst->period, $period_inst->datetime_period_inst_start, $new_period_properties, $instance_container );
	}

	function split_period_from_date( CB2_Period $period, CB2_Datetime $when, Array $new_period_properties = NULL, &$instance_container = NULL ) {
		$copy_text         = __( 'copy' );
		$limits_error_text = __( 'Cannot split period outside period limits' );
		$new_name          = "$period->name $copy_text";

		// $new_period_properties allows us to create the new period from the save $_POST directly
		$new_period = NULL;
		if ( $new_period_properties ) {
			$new_period_properties['ID'] = CB2_CREATE_NEW; // properties are not to be trusted!
			if ( ! isset( $new_period_properties['post_title'] ) )
				$new_period_properties['post_title'] = $new_name;
			$new_period = CB2_Period::factory_from_properties( $new_period_properties, $instance_container );
		} else {
			$new_period = $period->clone_with_create_new( $new_name ); // CB2_CREATE_NEW
		}

		// Split
		if ( $period->recurrence_type ) {
			// Repeating period instances, curtail them with datetime_to
			// TODO: this should probably be a refusal, rather than an Exception. Maybe catch it?
			if ( $when->lessThanOrEqual( $period->datetime_from )
				|| ( $period->datetime_to && $when->moreThanOrEqual( $period->datetime_to ) )
			) throw new Exception( $limits_error_text );

			$new_period->datetime_from = $when;
			$period->datetime_to       = $when->justBefore();
		} else {
			// TODO: Single event instance splitting will not work at the moment
			// because it will use the start date of the single instance,
			// not the clicked CB2_Day part of that multi-day instance

			// Single event instance, probably covering multiple days
			// can still be split in 1 day, as long as the when is between start and end
			if ( $when->lessThanOrEqual( $period->datetime_part_period_start )
				|| $when->moreThanOrEqual( $period->datetime_part_period_end )
			) throw new Exception( $limits_error_text );

			$new_period->datetime_part_period_start = $when;
			$period->datetime_part_period_end       = $when->justBefore();
		}

		// Add and annotate
		$this->add_period( $new_period );
		$new_period->linkTo( $period, CB2_LINK_SPLIT_FROM );

		return $new_period;
	}

  static function selector_wizard_metabox( String $context = 'normal', Bool $closed = FALSE, Array $classes = array() ) {
		// $period_group_options = CB2_Forms::period_group_options( TRUE );
		// $period_groups_count  = count( $period_group_options ) - 1;

		$p = '<p class="cb2-description">';
		$none_selected_text  = __( 'not selected' );
		$none_selected__span = "<span id='cb2-location-indicator'/>($none_selected_text)</span>";
		return array(
			'title'      => __( 'Booking Slots', 'commons-booking-2' ),
			'show_names' => FALSE,
			'context'    => $context,
			'classes'    => $classes,
			'closed'     => $closed,
			'show_on_cb' => array( 'CB2', 'is_not_published' ),
			'fields'     => array(
				array(
					'name'    => __( 'Booking Slots', 'commons-booking-2' ),
					'id'      => 'period_group_ID',
					'type'    => 'radio',
					'sanitization_cb' => array( get_class(), 'selector_wizard_metabox_sanitization' ),
					'default' => 'OPH',
					'options' => array(
						'OPH' => __( 'Opening Hours' ) . $p . __( 'Use the Opening Hours of the Location selected' ) . " $none_selected__span</p>",
						CB2_CREATE_NEW => __( 'Custom' )        . $p . __( 'Start with a blank calendar and create your own slots' ) . '</p>',
						'TMP' => __( '<span class="cb2-todo">Template</span>' )      . $p . __( 'Copy slots from other availabilities' ) . '</p>',
					),
				),
			),
		);
	}

	static function selector_wizard_metabox_sanitization( $value, $field_args, $field ) {
		switch ( $value ) {
			case 'OPH': {
				// Adopt selected Opening Hours
				if ( isset( $_REQUEST['location_ID'] ) ) {
					$location = CB2_Query::get_post_with_type( CB2_Location::$static_post_type, $_REQUEST[ 'location_ID' ] );
					$value    = $location->last_opening_hours_period_group_set();
					if ( ! $value ) {
						// TODO: selector_wizard_metabox_sanitization requires opening hours for location
						print( __( 'location has no Opening Hours' ) );
						$value = CB2_CREATE_NEW;
						exit();
					}
				} else {
					// TODO: selector_wizard_metabox_sanitization requires location error message
					print( __( 'location required to adopt Opening Hours' ) );
					exit();
				}
				break;
			}
			case 'TMP': {
				throw new Exception( 'Templates not implemented yet' );
				break;
			}
		}
		return $value;
	}

  static function database_table_name() { return self::$database_table; }

  static function database_table_schemas( $prefix ) {
 		$safe_updates_off     = CB2_Database::$safe_updates_off;
		$safe_updates_restore = CB2_Database::$safe_updates_restore;
		$period_inst_posts    = "{$prefix}cb2_view_periodinst_posts";
		$period_inst_meta     = "{$prefix}cb2_view_periodinstmeta";
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
			'cb2_view_periodgroup_posts' => "select (`p`.`period_group_id` + `pt_pg`.`ID_base`) AS `ID`,1 AS `post_author`,'2018-01-01' AS `post_date`,'2018-01-01' AS `post_date_gmt`,`p`.`description` AS `post_content`,`p`.`name` AS `post_title`,'' AS `post_excerpt`,'publish' AS `post_status`,'closed' AS `comment_status`,'closed' AS `ping_status`,'' AS `post_password`,(`p`.`period_group_id` + `pt_pg`.`ID_base`) AS `post_name`,'' AS `to_ping`,'' AS `pinged`,'2018-01-01' AS `post_modified`,'2018-01-01' AS `post_modified_gmt`,'' AS `post_content_filtered`,0 AS `post_parent`,'' AS `guid`,0 AS `menu_order`,`pt_pg`.`post_type` AS `post_type`,'' AS `post_mime_type`,0 AS `comment_count`,`p`.`period_group_id` AS `period_group_id`,ifnull((select group_concat((`{$prefix}cb2_period_group_period`.`period_id` + `pt2`.`ID_base`) separator ',') from (`{$prefix}cb2_period_group_period` join `{$prefix}cb2_post_types` `pt2` on((`pt2`.`post_type_id` = 1))) where (`{$prefix}cb2_period_group_period`.`period_group_id` = `p`.`period_group_id`) group by `{$prefix}cb2_period_group_period`.`period_group_id`),'') AS `period_IDs`,(select group_concat(`pgp`.`ID` separator ',') from `{$prefix}cb2_view_periodent_posts` `pgp` where (`pgp`.`period_group_ID` = `p`.`period_group_id`)) AS `period_entity_IDs` from (`{$prefix}cb2_period_groups` `p` join `{$prefix}cb2_post_types` `pt_pg` on((`pt_pg`.`post_type_id` = 2)))",
			'cb2_view_periodgroupmeta'   => "select ((`po`.`period_group_id` * 10) + `pt`.`ID_base`) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodgroup_id`,'period_IDs' AS `meta_key`,`po`.`period_IDs` AS `meta_value` from (`{$prefix}cb2_view_periodgroup_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = 'periodgroup'))) union all select (((`po`.`period_group_id` * 10) + `pt`.`ID_base`) + 1) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodgroup_id`,'period_group_id' AS `meta_key`,`po`.`period_group_id` AS `meta_value` from (`{$prefix}cb2_view_periodgroup_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = 'periodgroup'))) union all select (((`po`.`period_group_id` * 10) + `pt`.`ID_base`) + 2) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodgroup_id`,'post_type' AS `meta_key`,`po`.`post_type` AS `meta_value` from (`{$prefix}cb2_view_periodgroup_posts` `po` join `{$prefix}cb2_post_types` `pt` on((`pt`.`post_type` = 'periodgroup')))",
		);
	}

	function post_type() {return self::$static_post_type;}

	static function metaboxes() {
		$metaboxes = array();
		array_push( $metaboxes, CB2_Period::selector_metabox( TRUE ) ); // Multiple
		return $metaboxes;
	}

  static function &factory_from_properties( Array &$properties, &$instance_container = NULL, Bool $force_properties = FALSE, Bool $set_create_new_post_properties = FALSE ) {
		// This may not exist in post creation
		// We do not create the CB2_PeriodGroup objects here
		// because it could create a infinite circular creation
		// as the CB2_PeriodGroups already create their associated CB2_Periods
		$period_entity_IDs = array();
		if ( isset( $properties['period_entity_IDs'] ) )
			$period_entity_IDs = CB2_Query::ensure_ints( 'period_entity_IDs', $properties['period_entity_IDs'], TRUE );

		$object = self::factory(
			(int) ( isset( $properties['period_group_ID'] ) ? $properties['period_group_ID'] : $properties['ID'] ),
			( isset( $properties['post_title'] )
				? $properties['post_title']
				: ( isset( $properties['name'] ) ? $properties['name'] : '' )
			),
			( isset( $properties['period_IDs'] ) // Empty groups are allowed
				? CB2_PostNavigator::get_or_create_new( $properties, $force_properties, 'period_IDs', $instance_container )
				: array()
			),
			$period_entity_IDs,
			$properties, $force_properties, $set_create_new_post_properties
		);

		return $object;
	}

  static function &factory(
		Int $ID           = CB2_CREATE_NEW,
		String $name      = 'period group',
		Array $periods    = array(),
		Array $period_entity_IDs = array(),
		Array $properties = NULL, Bool $force_properties = FALSE, Bool $set_create_new_post_properties = FALSE
  ) {
		$object = CB2_PostNavigator::createInstance( __class__, func_get_args(), $ID, $properties, $force_properties, $set_create_new_post_properties );
		return $object;
  }

  protected function __construct(
		Int $ID           = CB2_CREATE_NEW,
		String $name      = 'period group',
		Array $periods    = array(),
		Array $period_entity_IDs = array()
  ) {
		CB2_Query::assign_all_parameters( $this, func_get_args(), __class__ );
		parent::__construct( $ID, $this->periods );
  }

  function add_period( CB2_Period &$period ) {
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
		$columns['periods']  = 'Periods <a href="admin.php?page=cb2-periods">view all</a>';
		$columns['entities'] = 'Entities';
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
					'post_type'      => array( 'periodent-global', 'periodent-location', 'periodent-timeframe', 'periodent-user' ),
					'post__in'       => $this->period_entity_IDs,
					'posts_per_page' => CB2_ADMIN_COLUMN_POSTS_PER_PAGE,
					'page'           => $current_page,
				) );

				if ( $wp_query->have_posts() ) {
					print( '<ul class="cb2-admin-column-ul cb2-content">' );
					CB2::the_inner_loop( NULL, $wp_query, 'admin', 'summary' );
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

	function period_entities( Int $current_page = 0, Bool $force_refresh = FALSE ) {
		static $period_entities = NULL;

		if ( is_null( $period_entities ) || $force_refresh ) {
			$wp_query           = new WP_Query( array(
				'post_type'      => array( 'periodent-global', 'periodent-location', 'periodent-timeframe', 'periodent-user' ),
				'post__in'       => $this->period_entity_IDs,
				'posts_per_page' => CB2_ADMIN_COLUMN_POSTS_PER_PAGE,
				'page'           => $current_page,
			) );
			$period_entities = $wp_query->posts;
			CB2_Query::ensure_correct_classes( $period_entities );
		}

		return $period_entities;
	}

	function summary_periods() {
		$count = count( $this->periods );

		if ( $count ) {
			$html = ( '<ul class="cb2-period-list">' );
			foreach ( $this->periods as $period ) {
				$edit_link   = $period->get_the_edit_post_link( 'edit' );

				$detach_link = '';
				if ( $count > 1 && $edit_link ) {
					$detach_text = 'detach';
					$detach_url  = "?page=cb2-period-groups&period_ID=$period->ID&period_group_IDs=$this->ID&do_action=CB2_PeriodGroup::detach";
					$detach_link = " | <a href='$detach_url'>$detach_text</a></li>";
				}

				$summary     = $period->summary();
				$html       .= "<li>$summary $edit_link $detach_link</li>";
			}
			$html .= '</ul>';
		} else {
			$html = '<div>' . __( 'No Periods' ) . '</div>';
		}

		return $html;
	}

	function summary() {
		$html = ( $this->post_title ? $this->post_title : $this->ID );
		$period_count = count( $this->periods );
		if ( $period_count > 1 )
			$html .= " <span class='cb2-usage-count-ok' title='Several Periods'>$period_count</span>";
		$html .= ' ' . $this->get_the_edit_post_link( 'edit' );

		switch ( $this->usage_count() ) {
			case 0:
				$html .= " <span class='cb2-usage-count-warning' title='Used in several Period Groups'>0</span>";
				break;
			case 1:
				break;
			default:
				$html .= " <span class='cb2-usage-count-ok' title='Used in several Period Groups'>" .
					$this->usage_count() .
					"</span>";
		}
    if ( WP_DEBUG ) $html .= " <span class='cb2-WP_DEBUG-small'>$this->post_author</span>";

		return $html;
	}

	protected function usage_count() {
		return count( $this->period_entity_IDs );
	}

	function pre_post_delete( $from_periodentity = NULL, $direct = TRUE ) {
		// $from_periodentity has already been deleted in the Database
		global $wpdb;

		$usage_count          = $this->usage_count( $direct );
		$continue_with_delete = ! $usage_count;

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
