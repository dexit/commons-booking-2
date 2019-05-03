<!-- override this template for archive content -->
<?php
// TODO: Temporary fix until instance_container ready
CB2_PostNavigator::clear_object_caches();
?>
<form style="float:right;" action="<?php CB2::the_permalink(); ?>"><div>
	<input type="submit" value="<?php print( __( 'book' ) ); ?>"/>
</div></form>
[cb2_item_current_location]
