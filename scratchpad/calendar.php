<?php
  /** Loads the WordPress Environment and Template */
  require_once( $_SERVER['DOCUMENT_ROOT'] . '/wp-blog-header.php' );
  require_once( dirname( dirname( __FILE__ ) ) . '/includes/CB_Template.php' );
  require_once( dirname( dirname( __FILE__ ) ) . '/public/includes/CB_Query.php' );
  require_once( dirname( dirname( __FILE__ ) ) . '/public/includes/CB_Forms.php' );

  if (0) {
		try {
			$obj = new stdClass();
			$test = (string) $obj;
		} catch ( Exception $ex) {
			// NEED to setup a custom Exception handler
			// to convert PHP errors in to PHP 5.0 exceptions
			print( $ex );
		}
		print('[' . is_numeric('') . ']');
		print('[' . is_numeric(NULL) . ']');
		print('[' . is_numeric('34') . ']');
		exit();
	}
?>
<html>
  <head>
    <title>Manual Calendar page</title>
    <link rel="stylesheet" type="text/css" href="calendar.css" />
    <link rel="stylesheet" type="text/css" href="../public/assets/css/debug.css" />
    <link rel="icon" href="favicon.ico" type="image/x-icon" />
    <style>
      .cb2-help {
        color:#773333;
        font-family:sans;
        font-size:11px;
      }
      .cb2-submit {
        height:40px;
        font-weight:bold;
      }
      h2 {
        font-size: 16px;
        margin-bottom:0px;
        margin-top:4px;
      }
      h2 a {
        font-size:11px;
        text-decoration:none;
      }
      #cb2-calendar-actions {
        float:right;
      }
    </style>
  </head>

  <body>
    <?php
    // --------------------------------------- Create new item
    $post_location_ID = ( isset( $_POST['location_ID'] ) && $_POST['location_ID'] ? $_POST['location_ID'] : NULL );
    $post_item_ID     = ( isset( $_POST['item_ID'] )     && $_POST['item_ID']     ? $_POST['item_ID']     : NULL );
    $post_user_ID          = ( isset( $_POST['user_ID'] )          && $_POST['user_ID']          ? $_POST['user_ID']          : NULL );
    $post_period_status_type_id = ( isset( $_POST['period_status_type_id'] ) && $_POST['period_status_type_id'] ? $_POST['period_status_type_id'] : NULL );

    $post_name = ( isset( $_POST['period_group_name'] ) ? $_POST['period_group_name'] : '' );
		$post_datetime_part_period_start = ( isset( $_POST['datetime_part_period_start'] ) ? $_POST['datetime_part_period_start'] : '2018-07-02 09:00:00' );
		$post_datetime_part_period_end   = ( isset( $_POST['datetime_part_period_end'] ) ? $_POST['datetime_part_period_end'] : '2018-07-02 13:00:00' );
		$post_datetime_from              = ( isset( $_POST['datetime_from'] ) ? $_POST['datetime_from'] : NULL );
		$post_datetime_to                = ( isset( $_POST['datetime_to'] ) ? $_POST['datetime_to'] : NULL );

		$post_recurrence_type      = ( isset( $_POST['recurrence_type'] ) && $_POST['recurrence_type'] ? $_POST['recurrence_type'] : NULL );
    $post_recurrence_frequency = ( isset( $_POST['recurrence_frequency'] ) && $_POST['recurrence_frequency'] ? $_POST['recurrence_frequency'] : NULL );
    $post_recurrence_sequence  = ( isset( $_POST['recurrence_sequence'] ) && $_POST['recurrence_sequence'] ? $_POST['recurrence_sequence'] : NULL );

    // --------------------------------------- Reflection
    print( '<h2>reflection <a href="#" onclick="this.parentNode.nextSibling.style=0">show</a></h2><div style="display:none;">' );
    print( '<div style="font-weight:bold;">procedures</div>' );
    var_dump( CB_Database::procedures() );
    print( '<div style="font-weight:bold;">tables</div>' );
    var_dump( CB_Database::tables() );
    print( '<div style="font-weight:bold;">registered PHP objects</div>' );
		$post_types = CB_Query::get_post_types();
    foreach ( CB_Query::schema_types() as $Class ) {
			$post_type      = $Class::$static_post_type;
			$post_type_stub = CB_Query::substring_before( $post_type );
			$post_details   = $post_types[$post_type];
			print( "<div style='font-weight:bold;'>$Class($post_type):</div><ul>" );

			foreach ($post_details as $name => $value )
				print( "<li>$name = $value</li>" );

			if ( CB_Database::has_table( "cb2_view_{$post_type_stub}_posts" ) )
				print( "<li>has posts table cb2_view_{$post_type_stub}_posts</li>" );
			if ( CB_Database::has_table( "cb2_view_{$post_type_stub}meta" ) )
				print( "<li>has post meta table cb2_view_{$post_type_stub}meta</li>" );
			if ( property_exists( $Class, 'database_table' ) && $Class::$database_table ) {
				if ( CB_Database::has_table( $Class::$database_table ) )
					print( "<li>database_table [" . $Class::$database_table . "] exists</li>" );
				else
					print( "<li class='cb2-error'>database_table [" . $Class::$database_table . "] NOT exists</li>" );
			}
			if ( CB_Database::has_procedure( "cb2_{$post_type}_update" ) )
				print( "<li>UPDATE procedure exists</li>" );
			if ( property_exists( $Class, 'all' ) )
				print( '<li>$all collection exists</li>' );

			print( '</ul>' );
    }
    print( '</div>' ); // .Reflection

    // --------------------------------------- Create
    print( '<h2>SQL <a href="#" onclick="this.parentNode.nextSibling.style=0">show</a></h2><div style="display:none;">' );
    if ( count( $_POST ) && isset( $_POST['datetime_part_period_start'] ) ) {
      $period = CB_Period::factory(
				NULL, // $ID,
				NULL, // $period_id,
				$post_name,
				$post_datetime_part_period_start,
				$post_datetime_part_period_end,
				$post_datetime_from,
				$post_datetime_to,
				$post_recurrence_type,
				$post_recurrence_frequency,
				$post_recurrence_sequence
			);
			$period->save(); // Will populate ID and id

			$period_group = CB_PeriodGroup::factory(
				NULL, // $ID,
				NULL, // $period_group_id,
				$post_name
			);
			$period_group->add_period( $period );
			$period_group->save();
			var_dump($period_group);
			exit();

			// Assign period group to timeframe
      $perioditem = CB_PeriodEntity::factory_subclass(
				NULL, // $ID
				NULL, // $name
				$period_group,
				CB_PeriodStatusType::factory( NULL, $post_period_status_type_id ),
				NULL, // $recurrence_index
				NULL, // $datetime_period_item_start
				NULL, // $datetime_period_item_end

				NULL, // $timeframe_id
        CB_Location::factory( $post_location_ID ),
        CB_Item::factory(     $post_item_ID ),
        CB_User::factory(     $post_user_ID )
			);
      $perioditem->save();
			if ( WP_DEBUG ) var_dump( $perioditem );
    }

    // --------------------------------------- Query Parameters
    $startdate_string = ( isset( $_GET['startdate'] )   ? $_GET['startdate'] : '2018-09-01 00:00:00' );
    $enddate_string   = ( isset( $_GET['enddate']   )   ? $_GET['enddate']   : '2018-10-01 00:00:00' );
    $location_ID      = ( isset( $_GET['location_ID'] ) ? $_GET['location_ID'] : NULL );
    $item_ID          = ( isset( $_GET['item_ID'] )     ? $_GET['item_ID']     : NULL );
    $user_ID          = ( isset( $_GET['user_ID'] )     ? $_GET['user_ID']          : NULL );
    $period_group_id  = ( isset( $_GET['period_group_id'] ) ? $_GET['period_group_id'] : NULL );
    $period_status_type_id = ( isset( $_GET['period_status_type_id'] ) ? $_GET['period_status_type_id'] : NULL );
    $schema_type      = ( isset( $_GET['schema_type'] )   ? $_GET['schema_type'] : CB_Week::$static_post_type );
    $template_part    = ( isset( $_GET['template_part'] ) ? $_GET['template_part'] : NULL );
    $no_auto_draft    = isset( $_GET['no_auto_draft'] );

    $output_type      = ( isset( $_GET['output_type'] ) ? $_GET['output_type'] : 'HTML' );

    if ( WP_DEBUG && isset( $_POST['reset_data'] ) ) {
      CB_Forms::reset_data( $_POST['reset_data'] );
    }

    // --------------------------------------- Query
    $meta_query       = array();
    $meta_query_items = array();
    $post_status      = array( 'publish' );
    if ( ! $no_auto_draft )          array_push( $post_status, 'auto-draft' );
    if ( $location_ID )           $meta_query_items[ 'location_clause' ]    = array( 'key' => 'location_ID', 'value' => $location_ID );
    if ( $item_ID )               $meta_query_items[ 'item_clause' ]        = array( 'key' => 'item_ID',     'value' => $item_ID );
    if ( $period_status_type_id ) $meta_query_items[ 'period_status_type_clause' ] = array( 'key' => 'period_status_type_id', 'value' => $period_status_type_id );

    if ( $meta_query_items ) {
			// Include the auto-draft which do not have meta
			$meta_query[ 'relation' ]       = 'OR';
			$meta_query[ 'without_meta' ]   = CB_Query::$without_meta;
			$meta_query_items[ 'relation' ] = 'AND';
			$meta_query[ 'items' ]          = $meta_query_items;
		}

    $args = array(
      'author'         => $user_ID,
      'post_status'    => $post_status,   // auto-draft indicates the pseudo Period A created for each day
      'post_type'      => CB_PeriodItem::$all_post_types,
      'posts_per_page' => -1,             // Not supported with CB_Query (always current month response)
      'order'          => 'ASC',          // defaults to post_date
      'show_overridden_periods' => 'yes', // TODO: doesnt work yet: use the query string
      'date_query'     => array(
        'after'   => $startdate_string,   // TODO: Needs to compare enddate > after
        'before'  => $enddate_string,     // TODO: Needs to compare startdate < before
        'compare' => $schema_type,
      ),
      'meta_query' => $meta_query,        // Location, Item, User
    );
    $query = new WP_Query( $args );
    var_dump( $query->query_vars );
		print( "<div class='cb2-debug'>$query->request</div>" );
    print( '</div>' ); // .SQL


    // ---------------------------------------- Create Form
    print( '<h2>create new period <a href="#" onclick="this.parentNode.nextSibling.style=0">show</a></h2><form style="display:none;" method="POST">' );
    print( '<i>type</i><br/>' );
    print( "Name: <input name='period_group_name'/><br/>" );
    print( 'Location: <select name="location_ID">' . CB_Forms::select_options( CB_Forms::location_options(), $post_location_ID ) . '</select>' );
    print( 'Item: <select name="item_ID">'         . CB_Forms::select_options( CB_Forms::item_options(), $post_item_ID ) . '</select>' );
    print( 'User: <select name="user_ID">'         . CB_Forms::select_options( CB_Forms::user_options(), $post_user_ID ) . '</select>' );
    ?><p class="cb2-help">
      No selection (G Global) = national holidays, general opening times</br>
      Location (L) = opening / closing times, Location specific holidays, discounts, events</br>
      Location - Item (I Timeframe) = availability or repairs</br>
      Location - Item - User (U) = booking, repair by a specific staff.</br>
      Item Type (T) = discounts (not implemented yet)
    </p><?php

    print( '<hr/><i>period definition</i><br/>' );
    print( 'Status: <select name="period_status_type_id">' . CB_Forms::select_options( CB_Forms::period_status_type_options(), $post_period_status_type_id, FALSE ) . '</select>' );
    print( "Time Part Start: <input name='datetime_part_period_start' value='$post_datetime_part_period_start'/>" );
    print( "Time Part End: <input name='datetime_part_period_end' value='$post_datetime_part_period_end'/>" );
    print( '<br/>' );

    print( 'Recurrence: <select name="recurrence_type">' . CB_Forms::select_options( array( 'D' => 'daily', 'W' => 'weekly', 'M' => 'monthly', 'Y' => 'yearly' ), $post_recurrence_type ) . '</select>' );
    ?><p class="cb2-help">
      No recurrence = one-off event on specific day. all of Time Parts and can span any time period.</br>
      daily = only the time portion of Time Parts are used</br>
      weekly = the week, day and time of Time parts are used. Time part cannot span across weeks</br>
      monthly = the month, day and time of Time parts are used</br>
      yearly = year Time parts are ignored.
    </p><?php
    print( 'Frequency: <select disabled="1" name="recurrence_frequency"><option></option>' );
    for ( $i = 1; $i < 31; $i++ ) print( "<option>$i</option>" );
    print( '</select><br/>' );
    print( "Sequence: " );
    $days = array( 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' );
    for ( $i = 0; $i < 31; $i++ ) {
      print( '<input' );
      if ( $i < count( $days ) ) print( " id='recurrence_sequence_$days[$i]'" );
      print( " value='$i' type='checkbox' name='recurrence_sequence[]'/>" );
      if ( $i < count( $days ) ) print( "<label for='recurrence_sequence_$days[$i]'>$days[$i]</label>" );
    }
    print( '<br/>' );

    print( "Valid From: <input name='datetime_from' value='2018-01-01 00:00:00'/>" );
    print( "Valid To: <input name='datetime_to'/>" );
    print( '<br/>' );

    print( '<input class="cb2-submit" type="submit" value="Create"/>' );
    print( '</form>' );


    // --------------------------------------- Filter selection Form
    print( '<h2>filter view <a href="#" onclick="this.parentNode.nextSibling.style=0">show</a></h2><form style="display:none;">' );
    print( "<input name='startdate' value='$startdate_string'/>" );
    print( "<input name='enddate' value='$enddate_string'/><br/>" );
    print( 'Location: <select name="location_ID">'  . CB_Forms::select_options( CB_Forms::location_options(), $location_ID ) . '</select>' );
    print( 'Item: <select name="item_ID">'          . CB_Forms::select_options( CB_Forms::item_options(), $item_ID ) . '</select>' );
    print( 'User: <select name="user_ID">'          . CB_Forms::select_options( CB_Forms::user_options(), $user_ID ) . '</select>' );
    print( '<br/>' );
    print( 'Period Status: <select name="period_status_type_id">' . CB_Forms::select_options( CB_Forms::period_status_type_options(), $period_status_type_id, TRUE ) . '</select>' );
    print( "<input id='no_auto_draft' type='checkbox' name='no_auto_draft'/> <label for='no_auto_draft'>Exclude pseudo-periods (A)</label>" );
    print( '<br/>' );
    print( 'Output type:<select name="output_type">'     . CB_Forms::select_options( array( 'HTML' => 'HTML', 'JSON' => 'JSON' ), $output_type ) . '</select>' );
    print( 'Post Type:<select name="schema_type">'       . CB_Forms::select_options( CB_Forms::schema_options(), $schema_type ) . '</select>' );
    print( 'Template Part:<select name="template_part">' . CB_Forms::select_options( array( 'available' => 'available' ), $template_part ) . '</select>' );
    print( '<br/>' );
    print( '<input class="cb2-submit" type="submit" value="Filter"/>' );
    print( '</form>' );


    // --------------------------------------- Action Forms
    print( '<div id="cb2-calendar-actions">' );
    print( '<form action="calendar.php" method="POST">' );
    print( '<input type="hidden" name="reset_data" value="fryace4"/>' );
    print( '<input class="cb2-submit cb2-dangerous" value="clear all calendar data" type="submit"/>' );
    print( '</form>' );
    print( '</div>' );

    // --------------------------------------- HTML calendar output
    // Title
    print( "<hr/>" );
    if ( $output_type == 'HTML' && ( $schema_type == 'location' || $schema_type == 'item' || $schema_type == 'user'  || $schema_type == 'form' ) )
      print( '<div class="cb2-help">Calendar rendering of locations / items / users / forms maybe better in JSON output type</div>' );

    switch ( $output_type ) {
      case 'JSON':
        print( '<pre>' );
        print( wp_json_encode( $query, JSON_PRETTY_PRINT ) );
        print( '</pre>' );
        break;
      case 'HTML':
				// print( 'HTML templorarily disabled because of performance problems' );
				// exit();
				?><div class="cb2-calendar"><header class="entry-header"><h1 class="entry-title">calendar</h1></header>
					<div class="entry-content">
						<table class="cb2-subposts"><tbody>
							<?php the_inner_loop( $query, 'list', $template_part ); ?>
						</tbody></table>
					</div><!-- .entry-content --></div>
			<?php
				wp_reset_postdata();
				break;
			}
			?>
    <hr/>
    <img src="model.png"/>
  </body>
</html>
