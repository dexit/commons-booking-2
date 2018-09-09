<?php
class CB_PeriodGroup extends CB_PostNavigator implements JsonSerializable {
  public static $database_table = 'cb2_period_groups';
	public static $all = array();
  static $static_post_type = 'periodgroup';
  public static $post_type_args = array(
		'menu_icon' => 'dashicons-admin-settings',
		'label'     => 'Period Groups',
  );
  public $periods  = array();

  static function selector_metabox() {
		$period_group_options = CB_Forms::period_group_options( CB2_CREATE_NEW );
		$period_groups_count  = count( $period_group_options ) - 1;
		return array(
			'title'      => __( 'Existing Period Group', 'commons-booking-2' ) .
												" <span class='cb2-usage-count'>$period_groups_count</span>",
			'show_names' => FALSE,
			'context'    => 'side',
			'fields'     => array(
				array(
					'name'    => __( 'PeriodGroup', 'commons-booking-2' ),
					'id'      => 'period_group_ID',
					'type'    => 'select',
					//'show_option_none' => TRUE,
					'default' => ( isset( $_GET['period_group_ID'] ) ? $_GET['period_group_ID'] : NULL ),
					'options' => $period_group_options,
				),
			),
		);
	}

  function post_type() {return self::$static_post_type;}

  static function &factory_from_wp_post( $post ) {
		if ( $post->ID ) CB_Query::get_metadata_assign( $post ); // Retrieves ALL meta values

		// Retrieve all Periods
		// 1-many
		// This may not be present during post creation
		$periods = array();
		if ( property_exists( $post, 'period_IDs' ) && $post->period_IDs ) {
			$period_IDs = CB_Query::ensure_ints( 'period_IDs', $post->period_IDs, TRUE );
			foreach ( $period_IDs as $period_id ) {
				$period = CB_Query::get_post_with_type( CB_Period::$static_post_type, $period_id );
				array_push( $periods, $period );
			}
		}

		$object = self::factory(
			$post->ID,
			$post->post_title,
			$periods
		);

		CB_Query::copy_all_wp_post_properties( $post, $object );

		return $object;
	}

  static function &factory(
		$ID,
		$name,
		$periods = array()
  ) {
    // Design Patterns: Factory Singleton with Multiton
		if ( ! is_null( $ID ) && isset( self::$all[$ID] ) ) {
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
		if ( ! is_null( $ID ) ) self::$all[$ID] = $this;
  }

  function add_period( $period ) {
		array_push( $this->periods, $period );
  }

  function add_actions( &$actions, $post ) {
		unset( $actions['edit'] );
		unset( $actions['view'] );
	}

  function manage_columns( $columns ) {
		$columns['periods'] = 'Periods';
		$this->move_column_to_end( $columns, 'date' );
		return $columns;
	}

	function custom_columns( $column ) {
		$html = '';
		switch ( $column ) {
			case 'periods':
				$html .= $this->summary_periods();
				$html .= '<a href="admin.php?page=cb-post-new&post_type=period">add new period</a> | <a href="admin.php?page=cb2-periods">attach existing period</a>';
				break;
		}
		return $html;
	}

	function summary_periods() {
		$html = ( '<ul class="cb2-period-list">' );
		$count = count( $this->periods );
		foreach ( $this->periods as $period ) {
			$edit_link   = "<a href='?page=cb-post-edit&post=$period->ID&post_type=period&action=edit'>edit</a>";

			$detach_link = '';
			if ( $count > 1 ) {
				$detach_text = 'detach';
				$detach_url  = "?page=cb-post-edit&post=$period->ID&post_type=periodgroup&action=detach";
				if ( $period->usage_once() ) {
					$detach_text = 'delete';
					$detach_url  = "?page=cb-post-edit&post=$period->ID&post_type=period&action=delete";
				}
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
			$html .= " <span class='cb2-usage-count' title='Several Periods'>$period_count</span>";
		$html .= " <a href='post.php?post=$this->ID&action=edit'>edit</a>";
		return $html;
	}

  function classes() {
		return '';
  }

  function post_post_update() {
		global $wpdb;

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
