<?php

$metabox = CB2_Settings::get_settings_group('pages');
$this->render_settings_group_metabox($metabox);

$metabox = CB2_Settings::get_settings_group('booking_options');
$this->render_settings_group_metabox($metabox);

$metabox = CB2_Settings::get_settings_group('permissions');
$this->render_settings_group_metabox($metabox);

?>
