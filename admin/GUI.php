<?php
print( "<style>
		body .cb2-WP_DEBUG:before {
			display:none;
		}
		h2,
		h3 {
			margin-bottom:0px;
			padding-bottom:0px;
		}
		.cb2-indent {
			padding-left:30px;
		}
		h3 span,
		h3 select,
		h3 label {
			font-size:12px;
			font-weight: normal;
		}
		th {
			text-align:left;
		}
	</style>" );
print( "<h1>GUI setup</h1>" );
print( "<p></p>");

$metabox_classes = cb2_metaboxes();
foreach ( $metabox_classes as $Class => $metaboxes ) {
	print( "<h2>$Class</h2>" );
	print( "<div class='cb2-indent'>" );

	// ----------------------------------------------------- Supports
	if ( property_exists( $Class, 'supports' ) ) {
		print( '<h3>supports</h3>' );
		print( '<table>' );
		foreach ( $Class::$supports as $support ) {
			print( "<tr>
					<td><input id='$Class-$support-support' checked='1' type='checkbox'/></td>
					<td><label for='$Class-$support-support'>$support</label></td>
				</tr>" );
		}
		print( '<tr><td></td><td><a href="#">add support</a></td></tr>' );
		print( '</table>' );
	}

	// ----------------------------------------------------- Metabox
	foreach ( $metaboxes as $metabox ) {
		$title        = $metabox['title'];
		$title        = preg_replace( '/<[^>]*>/', '', $title );
		$debug_only   = isset( $metabox['debug-only'] );
		$debug_string = ( $debug_only ? ' (debug-only)' : '' );
		$context      = ( isset( $metabox['context'] )  ? $metabox['context']  : '-- select --' );
		$priority     = ( isset( $metabox['priority'] ) ? $metabox['priority'] : 'normal' );

		if ( ! $debug_only ) {
			print( "<h3>$title
					<select>
						<option>$context</option>
					</select>
					<input checked='1' type='checkbox'/> <label>visible</label>
					<span>$debug_string</span>
				</h3>" );
			$classes = ( isset( $metabox['classes'] ) ? $metabox['classes'] : array() );
			if ( ! is_array( $classes ) ) $classes = array( $classes );
			$classes_string = implode( ',', $classes );
			print( "<div><b>Classes</b>: <input value='$classes_string'/></div>" );

			if ( isset( $metabox['fields'] ) ) {
				print( "<table>
					<thead>
						<th>visible</th>
						<th>closed</th>
						<th>name</th>
						<th>id</th>
						<th>type</th>
					</thead>" );
				foreach ( $metabox['fields'] as $field ) {
					$name = ( isset( $field['name'] ) ? $field['name'] : '' );
					$name = preg_replace( '/<[^>]*>/', '', $name );
					print( "<tr>
							<td><input checked='1' type='checkbox'/></td>
							<td><input type='checkbox'/></td>
							<td>$name</td>
							<td>$field[id]</td>
							<td>$field[type]</td>
						</tr>" );
				}
				print( "</table>" );
			}
		}
	}
	// TODO: row_actions GUI setup

	print( "</div>" );
	print( '<hr/>' );
}

