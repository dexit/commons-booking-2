<?php
global $wpdb;

$DB_NAME = DB_NAME;

print( "<h1>Reflection (Database `$DB_NAME`)</h1>" );
$and_posts         = isset( $_GET['and_posts'] ); // Checkbox
$and_posts_checked = ( $and_posts ? 'checked="1"' : '' );
$testdata          = isset( $_GET['testdata'] );  // Checkbox
$testdata_checked  = ( $testdata ? 'checked="1"' : '' );
$disabled          = ( isset( $_GET['reset_data'] ) ? 'disabled="1"' : '' );
print( '<div class="cb2-actions">' );
print( '<a href="?page=cb2-reflection">show install schema</a>' );
$processing = 'var self = this;
	setTimeout(function(){
		self.setAttribute("value", "Processing...");
		self.setAttribute("disabled", "1");
	}, 0);';
if ( WP_DEBUG ) print( " | <form><div>
		<input type='hidden' name='page' value='cb2-reflection'/>
		<input type='hidden' name='section' value='reinstall_sql'>
		<input onclick='$processing' class='cb2-submit' type='submit' value='generate re-install SQL'/>
		<select name='character_set'>
			<option value=''>-- explicit collation --</option>
			<option value='latin1_swedish_ci'>latin1_swedish_ci (MySQL default)</option>
			<option value='utf8mb4_unicode_ci'>utf8mb4_unicode_ci (advised)</option>
			<option>utf8mb4_general_ci</option>
			<option>utf8_unicode_ci</option>
			<option>utf8_general_ci</option>
		</select>
	</div></form>" );
if ( WP_DEBUG ) print( "<br/><form><div>
		<input type='hidden' name='page' value='cb2-reflection'/>
		<input type='hidden' name='section' value='un-install'>
		<input onclick='$processing' class='cb2-submit cb2-dangerous' type='submit' value='un-install'/>
		<input name='password' placeholder='password (fryace4)' value=''>
	</div></form>" );
if ( WP_DEBUG ) print( " | <form><div>
		<input type='hidden' name='page' value='cb2-reflection'/>
		<input type='hidden' name='section' value='install'>
		<input onclick='$processing' class='cb2-submit cb2-dangerous' type='submit' value='install'/>
		<input name='password' placeholder='password (fryace4)' value=''>
	</div></form>" );
if ( WP_DEBUG ) print( "<br/><form><div>
		<input type='hidden' name='page' value='cb2-reflection'/>
		<input type='hidden' name='section' value='reset_data'/>
		<input type='hidden' name='password' value='fryace4'/>
		<input onclick='$processing' $disabled class='cb2-submit cb2-dangerous' type='submit' value='clear all data'/>
		<input id='and_posts' $and_posts_checked type='checkbox' name='and posts'/> <label for='and_posts'>Clear all <b>CB2</b> wp_post data</label>
		<input id='testdata'  $testdata_checked  type='checkbox' name='testdata'/> <label for='testdata'><b>Overwrite</b> wp_posts with test data and geo</label>
	</div></form>" );
print( '</div><hr/>' );

if ( isset( $_GET['section'] ) ) {
	switch ( $_GET['section'] ) {
		case 'reset_data':
			if ( CB2_Forms::reset_data( $_GET['password'], $and_posts, $testdata ) ) {
				print( '<div>Data reset successful' . ( $and_posts ? ', with posts and postmeta': '' ) . '</div>' );
			}
			break;
		case 'un-install':
			if ( $_GET['password'] == 'fryace4' ) {
				CB2_Database::uninstall();
				print( '<div>Finished UnInstallation.</div>' );
			} else throw new Exception( 'Invalid password' );
			break;
		case 'install':
			if ( $_GET['password'] == 'fryace4' ) {
				CB2_Database::install();
				print( '<div>Finished Installation.</div>' );
			} else throw new Exception( 'Invalid password' );
			break;
		case 'reinstall_sql':
			$full_sql = CB2_Database::get_reinstall_SQL_full( $_GET['character_set'] );
			print( "<pre>$full_sql</pre>" );
			break;
		default:
			print( "<div>commad [$_GET[section]] not understood</div>" );
	}
} else {
	$schema_array = CB2_Database::schema_array();

	// ---------------------------------------------------- Database reflection
	$exsiting_tables   = $exsiting_tables = $wpdb->get_col( 'SHOW TABLES;' );
	$view_results      = $wpdb->get_results( 'select table_name, view_definition from INFORMATION_SCHEMA.views', OBJECT_K );
	$triggers_results  = $wpdb->get_results( 'select trigger_name, action_statement from INFORMATION_SCHEMA.triggers', OBJECT_K );
	$existing_views    = array();
	$existing_triggers = array();
	// The compilation procedure adds things in to the definitions
	// that we do not specifiy, like collation
	foreach ( $triggers_results as $name => $definition ) {
		$action_statement = preg_replace( "/`$DB_NAME`\./", '',  $definition->action_statement );
		$action_statement = preg_replace( '/^BEGIN\\n|\\nEND$/',   '',  $action_statement );
		$action_statement = trim( preg_replace( '/\\s+/', ' ', $action_statement ) );
		array_push( $existing_triggers, $action_statement );
	}
	foreach ( $view_results as $name => &$definition ) {
		$view_body = preg_replace( "/`$DB_NAME`\./", '', $definition->view_definition );
		$view_body = trim( preg_replace( '/\\s+/', ' ', $view_body ) );
		$existing_views[$name] = $view_body;
	}

	// ---------------------------------------------------- System setup
	print( "<h2>PHP Objects =&gt; Database Tables</h2>" );
	print( "<p>CB2 PHP Objects define their own database tables and views.
		The database installation procedure simply asks each CB2 object for its database requirements.
		Values are loaded from the database directly into object instances converting the values based on the table definitions.
		For example, a DATETIME column `date_from` in wp_cb2_period will load a PHP DateTime object value on to a CB2_Period instance.
		See CB2_Query::to_object(). Conversion during saving works in a similar way.
		CB2 objects can be created with an ID of -1 and then save()ed to create new rows in the database.
		Below are listed the CB2 objects and their associated tables, views, relationships and triggers.
	</p>");

	print( "<h2>WordPress and Database setup</h2>" );
	print( "<p>A note on collation: string literals, e.g. 'period', can cause collation issues.
		By default they will adopt the <b>server</b> collation, not the database collation.
		Thus any database field data being concatenated with them or compared to them will cause an error.
		To avoid this always pull all data from the database.
		Returning a variety of charsets and collations with string literals and database data is not a problem for the PHP.
		<br/>
		You cannot collate to the default database collation with MySQL.
		If you really need to do this then carry out a string replacement in the PHP layer.
		This would replace @@character_set_database with the actual characterset name, gained through a database call with get_var(select @@character_set_database).
		String literals can be collated efficiently with _@@character_set_database'string' collate @@collation_database
	</p>");
	$db_charset = $wpdb->get_var( "SELECT @@character_set_database" );
	$db_collate = $wpdb->get_var( "SELECT @@collation_database;" );
	print( '<div>WordPress wp-config.php DB_CHARSET: <b>' . ( DB_CHARSET ? DB_CHARSET : '(Blank)' ) . '</b></div>' );
	print( '<div>WordPress wp-config.php DB_COLLATE: <b>' . ( DB_COLLATE ? DB_COLLATE : '(Blank)' ) . '</b></div>' );
	print( '<div>Database [' . DB_NAME . "] DB_CHARSET: <b>$db_charset</b></div>" );
	print( '<div>Database [' . DB_NAME . "] DB_COLLATE: <b>$db_collate</b></div>" );

	// ---------------------------------------------------- WordPress
	print( "<h2>WordPress ({$wpdb->prefix}postmeta)</h2>" );
	$row_count = $wpdb->get_var( "SELECT count(*) from {$wpdb->prefix}postmeta" );
	$class = ( $row_count >= 1000 ? 'cb2-warning' : '' );
	print( "<div class='$class'>row count: $row_count</div>" );

	print( "<h2>WordPress ({$wpdb->prefix}posts)</h2>" );
	$row_count = $wpdb->get_var( "SELECT count(*) from {$wpdb->prefix}posts" );
	$class = ( $row_count >= 1000 ? 'cb2-warning' : '' );
	print( "<div class='$class'>row count: $row_count</div>" );

	// ---------------------------------------------------- CB2
	foreach ( $schema_array as $Class => $object_types ) {
		$post_type         = ( property_exists( $Class, 'static_post_type' ) ? $Class::$static_post_type : '' );
		$table_definitions = ( isset( $object_types['table'] ) ? $object_types['table'] : NULL );
		$views             = ( isset( $object_types['views'] ) ? $object_types['views'] : NULL );
		$stored_procedures = ( isset( $object_types['stored procedures'] ) ? $object_types['stored procedures'] : NULL );

		print( "<h2>$Class</h2>" );
		// ----------------------------------------------- Infrastructure
		if ( property_exists( $Class, 'description' ) ) print( "<div class='cb2-description'>{$Class::$description}</div>" );
		if ( $post_type ) print( "<div>post_type: <b>$post_type</b></div>" );
		if ( isset( $object_types['data'] ) ) print( "<div>has <b>" . count( $object_types['data'] ) . "</b> initial data rows</div>" );

		if ( ! CB2_Database::database_table( $Class ) ) print( '<div>the Class claims no primary database table</div>' );
		if ( $post_type && ! CB2_Database::posts_table( $Class ) )    print( '<div>the Class claims no posts table</div>' );
		if ( $post_type && ! CB2_Database::postmeta_table( $Class ) ) print( '<div>the Class claims no postmeta table</div>' );

		if ( $table_definitions ) {
			foreach ( $table_definitions as $table_definition ) {
				$existing_columns = array();
				$table_name    = $table_definition['name'];
				$table_exists  = CB2_Database::has_table( $table_name );
				$pseudo        = ( isset( $table_definition['pseudo'] )  ? $table_definition['pseudo']  : FALSE );
				$pseudo_class  = ( $pseudo ? 'cb2-pseudo' : 'cb2-real' );
				$pseudo_title  = ( $pseudo ? ' <b style="color:red">(pseudo)</b>' : '' );
				$managed       = ( isset( $table_definition['managed'] ) ? $table_definition['managed'] : TRUE );
				$managed_class = ( $managed ? '' : 'cb2-unmanaged' );
				$managed_title = ( $managed ? '' : ' <b style="color:red">(unmanaged)</b>' );
				$table_exists_class = ( $pseudo || $table_exists ? '' : 'cb2-table-not-exist' );

				// ----------------------------------------------- TABLE
				print( "<table class='cb2-database-table $table_exists_class $managed_class'><thead>" );
				print( "<tr><th colspan='100'><i class='cb2-database-prefix'>$wpdb->prefix</i>$table_name$pseudo_title$managed_title</th></tr>" );
				print( "<tr>" );
				foreach ( CB2_Database::$columns as $column )
					print( "<th>$column</th>" );
				print( "</tr>" );
				print( "</thead><tbody>" );
				if ( ! $pseudo ) {
					if ( CB2_Database::has_table( $table_name ) ) {
						$existing_columns = $wpdb->get_results( "DESC {$wpdb->prefix}$table_name;", OBJECT_K );
						if ( count( $existing_columns ) > count( $table_definition['columns'] ) )
							print( "<div class='cb2-warning'>$table_name has new columns</div>" );
					} else {
						print( "<div class='cb2-warning'>$table_name does not exist</div>" );
					}
				}

				foreach ( $table_definition['columns'] as $name => $column_definition ) {
					print( "<tr>" );

					print( "<td>$name" );
					if ( ! $pseudo && ! isset( $existing_columns[$name] ) )
						print( " <span class='cb2-warning'>not found</span>" );
					print( "</td>" );

					for ( $i = 0; $i < count( CB2_Database::$columns ) - 2; $i++ ) {
						print( "<td>" );
						print( isset( $column_definition[$i] ) ? $column_definition[$i] : '' );
						print( "</td>" );
					}

					print( "<td>" );
					$fk = ( isset( $table_definition['foreign keys'][$name] ) ? $table_definition['foreign keys'][$name] : NULL );
					if ( $fk ) {
						print( "=&gt;&nbsp;$fk[0]" );
					} else if ( substr( $name, -3 ) == '_ID' ) {
						print( "<div class='cb2-warning'>ID column has no foreign key</div>" );
					}
					print( "</td>" );
					print( "</tr>" );
				}
				print( "</tbody></table>" );

				// ----------------------------------------------- stats
				if ( ! $pseudo ) {
					if ( CB2_Database::has_table( $table_name ) ) {
						$row_count  = $wpdb->get_var( "SELECT count(*) from {$wpdb->prefix}$table_name" );
						$class      = ( $row_count >= 1000 ? 'cb2-warning' : '' );
						print( "<div class='$class'>row count: $row_count</div>" );
					}
				}

				// ----------------------------------------------- TRIGGERS
				if ( isset( $table_definition['triggers'] ) ) {
					foreach ( $table_definition['triggers'] as $trigger_type => $triggers ) {
						foreach ( $triggers as $trigger_body ) {
							print( "<div><b>$trigger_type</b> trigger</div>" );
							$trigger_body = CB2_Database::check_fuction_bodies( "$table_name::trigger", $trigger_body );
							$trigger_body = trim( preg_replace( '/\\s+/', ' ', $trigger_body ) );
							if ( ! in_array( $trigger_body, $existing_triggers ) ) {
								krumo($trigger_body, $existing_triggers);
								print( "&nbsp;<span class='cb2-warning'>trigger different, or does not exist</span>" );
							}
						}
					}
				}

				// ----------------------------------------------- M2M
				if ( isset( $table_definition['many to many'] ) ) {
					foreach ( $table_definition['many to many'] as $m2mname => $m2m_defintion ) {
						$foreign_table = $m2m_defintion[1];
						print( "<div>$table_name also has a many-to-many realtionship with <b>$foreign_table</b> called <b>$m2mname</b></div>" );
					}
				}
			}
		}

		// ----------------------------------------------- VIEWS
		if ( count( $views ) ) {
			print( "<div>views: <ul class='cb2-database-views'>" );
			$first = '';
			foreach ( $views as $name => $view_body ) {
				print( "<li>$first$name" );
				$full_name = "$wpdb->prefix$name";
				$view_body = CB2_Database::check_fuction_bodies( "view::$name", $view_body );
				if ( ! isset( $existing_views[$full_name] ) )
					print( " <span class='cb2-warning'>does not exist</span>" );
				else if ( $existing_views[$full_name] != $view_body ) {
					krumo($existing_views[$full_name], $view_body);
					print( " <span class='cb2-warning'>has different body</span>" );
				} else {
					$row_count = $wpdb->get_var( "SELECT count(*) from $full_name" );
					$class     = ( $row_count >= 1000 ? 'cb2-warning' : '' );
					print( "&nbsp;<span class='$class'>($row_count)</span>" );
				}
				print( '</li>' );
				$first = ', ';
			}
			print( "</ul></div>" );
		}

		// ----------------------------------------------- Stored Procedures
		if ( count( $stored_procedures ) ) {
			print( "<div>stored procedures: <ul class='cb2-database-stored-procedures'>" );
			$first = '';
			foreach ( $stored_procedures as $name => $body ) {
				$full_name = "cb2_$name";
				print( "<li>$first$full_name" );
				print( '</li>' );
				$first = ', ';
			}
			print( "</ul></div>" );
		}
	}

	// --------------------------- Model
	$assets_dir = plugins_url( CB2_TEXTDOMAIN . '/admin/assets' );
	print( '<hr/>' );
	print( '<h2>period diagram</h2>');
	print( <<<HTML
		<p>The periods are purely concerned with time and its repetition. For example: every Monday this year.
		Consider it as a new DATETIME type with repetition built in. The Period table has 1 row per definition.
		Periods can be grouped together in the PeriodGroup table and can have simple exceptions.
		The view view_periodinsts takes the definitions in period and creates 1 row per repetition for each period definition.
		This view is cached by triggers on the main period table due to the amount of processing power needed to run the calculations.
		</p>

		<p>Periods support concepts like yearly holidays, daily opening times, regular booking, regular repairs, etc.
		However we did not want to restrict the type of concept so you will not find tables for these concepts,
		instead you will find an exstensible period_status_type. This can be set to Holiday for example.</p>

		<p>The 4 leaf tables: Global, Location, Timeframe and Timeframe_User; relate real world objects, their period_status_type, state and periods.
			Each leaf table has a different selection of objects it can relate to depending on the type of thing wishing to be expressed.<br/>
			For Example: a shop (object), open (period_status_type), every monday morning (period).<br/>
			For Example: a bicycle (object) booked (period_status_type) by Henry (object), in the shop (object), next wednesday (period).
		</p>
		<img src='$assets_dir/period diagram.png'/>
HTML
	);

	print( '<hr/><h2>posts and meta</h2>');
	print( '<p>All tables are exposed as WordPress posts with post_meta.
		The WordPress framework then, seamlessly, can view, list and edit them with all the usual functions an hooks.' );
	print( '</p>');
	print( "<img src='$assets_dir/posts and meta.png'/>" );
}

