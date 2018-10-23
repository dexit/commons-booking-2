<?php
class CB_PeriodGroup extends CB_DatabaseTable_PostNavigator implements JsonSerializable {
  public static $database_table = 'cb2_period_groups';
	public static $all = array();
  static $static_post_type = 'periodgroup';
  public static $post_type_args = array(
		'menu_icon' => 'dashicons-admin-settings',
		'label'     => 'Period Groups',
  );
  public $periods  = array();

  static function selector_metabox() {
		$period_group_options = CB_Forms::period_group_options( TRUE );
		$period_groups_count  = count( $period_group_options ) - 1;
		return array(
			'title'      => __( 'Period Group', 'commons-booking-2' ) .
												" <span class='cb2-usage-count-ok'>$period_groups_count</span>",
			'show_names' => FALSE,
			'context'    => 'side',
			'closed'     => TRUE,
			'fields'     => array(
				array(
					'name'    => __( 'Period Group', 'commons-booking-2' ),
					'id'      => 'period_group_ID',
					'type'    => 'radio',
					//'show_option_none' => TRUE,
					'default' => ( isset( $_GET['period_group_ID'] ) ? $_GET['period_group_ID'] : CB2_CREATE_NEW ),
					'options' => $period_group_options,
				),
			),
		);
	}

  function database_table_name() { return self::$database_table; }

  function database_table_schema() {
		return array(
			'name'    => self::$database_table,
			'columns' => array(
				'period_group_id' => array( INT,     (11),   UNSIGNED, NOT_NULL, AUTO_INCREMENT ),
				'name'            => array( VARCHAR, (1024), NULL,     NOT_NULL, FALSE, 'period group' ),
				'description'     => array( VARCHAR, (2048), NULL,     NULL,     FALSE, NULL ),
			),
			'primary key' => array('period_group_id'),
			'many to many' => array(
				'cb2_period_group_period' => 'cb2_periods', // TODO: this will create an extra table
			),
		);
	}

  function post_type() {return self::$static_post_type;}

  static function &factory_from_properties( &$properties, &$instance_container = NULL ) {
		$object = self::factory(
			$properties['ID'],
			$properties['post_title'],
			CB_PostNavigator::get_or_create_new( $properties, 'period_IDs', $instance_container )
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
		CB_Query::assign_all_parameters( $this, func_get_args(), __class__ );
		parent::__construct( $this->periods );
		if ( $ID ) self::$all[$ID] = $this;
  }

  function add_period( $period ) {
		array_push( $this->periods, $period );
  }

  function add_actions( &$actions, $post ) {
		unset( $actions['edit'] );
		unset( $actions['view'] );
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
				$page      = 'cb-post-new';
				$post_type = 'period';
				$add_link  = "admin.php?page=$page&period_group_ID=$this->ID&post_type=$post_type";
				print( "<a class='cb2-todo' href='$add_link'>add new period</a>" );
				print( ' | <a class="cb2-todo" href="admin.php?page=cb2-periods">attach existing period</a>' );
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
					the_inner_loop( $wp_query, 'admin', 'summary' );
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
				$detach_url  = "?page=cb-post-edit&post=$period->ID&post_type=periodgroup&action=detach";
				if ( $period->usage_once() ) {
					$detach_text = 'delete';
					$detach_url  = "?page=cb-post-edit&post=$period->ID&post_type=period&action=delete";
				}
				$detach_link = " | <a class='cb2-todo' href='$detach_url'>$detach_text</a></li>";
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

CB_Query::register_schema_type( 'CB_PeriodGroup' );
