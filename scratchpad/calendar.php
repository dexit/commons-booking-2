<?php
  /** Loads the WordPress Environment and Template */
  require( $_SERVER['DOCUMENT_ROOT'] . '/wp-blog-header.php' );
  require( $_SERVER['DOCUMENT_ROOT'] . '/wp-content/plugins/commons-booking/includes/CB_Template.php' );
  require( $_SERVER['DOCUMENT_ROOT'] . '/wp-content/plugins/commons-booking/public/includes/CB_Query.php' );
?>
<html>
  <head>
    <title>Manual Calendar page</title>
    <link rel="stylesheet" type="text/css" href="calendar.css" />
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

    $post_recurrence_type      = ( isset( $_POST['recurrence_type'] ) && $_POST['recurrence_type'] ? $_POST['recurrence_type'] : NULL );
    $post_recurrence_frequency = ( isset( $_POST['recurrence_frequency'] ) && $_POST['recurrence_frequency'] ? $_POST['recurrence_frequency'] : NULL );
    $post_recurrence_sequence  = ( isset( $_POST['recurrence_sequence'] ) && $_POST['recurrence_sequence'] ? $_POST['recurrence_sequence'] : NULL );

    print( '<h2>SQL <a href="#" onclick="this.parentNode.nextSibling.style=0">show</a></h2><div style="display:none;">' );
    if ( count( $_POST ) && isset( $_POST['datetime_part_period_start'] ) ) {
      // TODO: allow string inputs and $arg inputs? like WP_Query?
      $period = CB_Period_Instance::factory_period(
        $post_location_ID,
        $post_item_ID,
        $post_user_ID,
        1,
        0,
        $_POST['period_group_name'],
        new DateTime( $_POST['datetime_part_period_start'] ),
        new DateTime( $_POST['datetime_part_period_end'] ),
        new DateTime( $_POST['datetime_from'] ),
        NULL, //new DateTime( $_POST['datetime_to'] ),
        CB_PeriodStatusType::factory(
          $post_period_status_type_id
        ),
        $post_recurrence_type,      // maybe NULL
        $post_recurrence_frequency, // maybe NULL
        CB_Database::bitarray_to_int( $post_recurrence_sequence ),  // Array, maybe NULL
        (object) $_POST
      );
      $period->save();
    }

    // --------------------------------------- Query Parameters
    $startdate_string = ( isset( $_GET['startdate'] ) ? $_GET['startdate'] : '2018-06-01 00:00:00' );
    $enddate_string   = ( isset( $_GET['enddate']   ) ? $_GET['enddate']   : '2018-07-01 00:00:00' );
    $location_ID = ( isset( $_GET['location_ID'] ) ? $_GET['location_ID'] : NULL );
    $item_ID     = ( isset( $_GET['item_ID'] )     ? $_GET['item_ID']     : NULL );
    $user_ID          = ( isset( $_GET['user_ID'] )          ? $_GET['user_ID']          : NULL );
    $period_group_id  = ( isset( $_GET['period_group_id'] ) ? $_GET['period_group_id'] : NULL );
    $post_status = ( isset( $_GET['post_status'] ) ? $_GET['post_status'] : NULL );
    $post_type   = ( isset( $_GET['post_type'] ) ? $_GET['post_type'] : CB_Week::$static_post_type );

    $output_type      = ( isset( $_GET['output_type'] ) ? $_GET['output_type'] : 'HTML' );

    if ( WP_DEBUG && isset( $_POST['reset_data'] ) ) {
      CB_Query::reset_data( $_POST['reset_data'] );
    }

    // --------------------------------------- Query
    $args = array(
      'date_query'    => array(
        'after'  => $startdate_string,
        'before' => $enddate_string
      ),
      'location_ID'   => $location_ID,
      'item_ID'       => $item_ID,
      'author'        => $user_ID,
      'post_status'   => $post_status,
      'post_type'     => $post_type
    );
    $calendar_query = new CB_Query( $args );
    print( '</div>' );


    // ---------------------------------------- Create Form
    print( '<h2>create new period <a href="#" onclick="this.parentNode.nextSibling.style=0">show</a></h2><form style="display:none;" method="POST">' );
    print( '<i>type</i><br/>' );
    print( "Name: <input name='period_group_name'/><br/>" );
    print( 'Location: <select name="location_ID">' . CB_Query::select_options( CB_Query::location_options(), $post_location_ID ) . '</select>' );
    print( 'Item: <select name="item_ID">'     . CB_Query::select_options( CB_Query::item_options(), $post_item_ID ) . '</select>' );
    print( 'User: <select name="user_ID">'          . CB_Query::select_options( CB_Query::user_options(), $post_user_ID ) . '</select>' );
    ?><p class="cb2-help">
      No selection (G Global) = national holidays, general opening times</br>
      Location (L) = opening / closing times, Location specific holidays, discounts, events</br>
      Location - Item (I Timeframe) = availability or repairs</br>
      Location - Item - User (U) = booking, repair by a specific staff.</br>
      Item Type (T) = discounts (not implemented yet)
    </p><?php

    print( '<hr/><i>period definition</i><br/>' );
    print( 'Status: <select name="period_status_type_id">' . CB_Query::select_options( CB_Query::period_status_type_options(), $post_period_status_type_id, FALSE ) . '</select>' );
    print( "Time Part Start: <input name='datetime_part_period_start' value='2018-06-02 09:00:00'/>" );
    print( "Time Part End: <input name='datetime_part_period_end' value='2018-06-02 13:00:00'/>" );
    print( '<br/>' );

    print( 'Recurrence: <select name="recurrence_type">' . CB_Query::select_options( array( 'D' => 'daily', 'W' => 'weekly', 'M' => 'monthly', 'Y' => 'yearly' ), $post_recurrence_type ) . '</select>' );
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
    print( 'Location: <select name="location_ID">' . CB_Query::select_options( CB_Query::location_options(), $location_ID ) . '</select>' );
    print( 'Item: <select name="item_ID">'     . CB_Query::select_options( CB_Query::item_options(), $item_ID ) . '</select>' );
    print( 'User: <select name="user_ID">'          . CB_Query::select_options( CB_Query::user_options(), $user_ID ) . '</select>' );
    print( '<br/>' );
    print( 'Period Status: <select name="post_status">' . CB_Query::select_options( CB_Query::period_status_type_options(), $post_status, TRUE, TRUE ) . '</select>' );
    print( '<br/>' );
    print( 'Output type:<select name="output_type">' . CB_Query::select_options( array( 'HTML' => 'HTML', 'JSON' => 'JSON' ), $output_type ) . '</select>' );
    print( 'Post Type:<select name="post_type">' . CB_Query::select_options( CB_Query::queried_object_types(), $post_type ) . '</select>' );
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
    if ( $output_type == 'HTML' && ( $post_type == 'location' || $post_type == 'item' || $post_type == 'user'  || $post_type == 'form' ) )
      print( '<div class="cb2-help">Calendar rendering of locations / items / users / forms maybe better in JSON output type</div>' );

    switch ( $output_type ) {
      case 'JSON':
        print( '<pre>' );
        print( wp_json_encode( $calendar_query, JSON_PRETTY_PRINT ) );
        print( '</pre>' );
        break;
      case 'HTML':
				global $post;
				$post = &$calendar_query;
				cb_get_template_part( 'commons-booking', $post->template() );
        break;
    }
    ?>
    <hr/>
    <img src="model.png"/>
  </body>
</html>
