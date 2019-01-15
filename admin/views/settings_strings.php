<?php

$strings_array = CB2_Strings::get();
$fields_array = array();

// reformat array to fit our cmb2 settings fields
foreach ($strings_array as $category => $fields) {
    // add title field
    $fields_array[] = array(
        'name' => $category,
        'id' => $category . '-title',
        'type' => 'title',
    );
    foreach ($fields as $field_name => $field_value) {

        $fields_array[] = array(
            'name' => $field_name,
            'id' => $category . '_' . $field_name,
            'type' => 'textarea_small',
            'default' => $field_value,
        );
    } // end foreach fields

} // end foreach strings_array

$metabox_strings = array(
    'name' => __('Strings', 'commons-booking'),
		'slug' => 'strings',
		'id' => 'strings',
    'fields' => $fields_array,
);

$this->render_settings_group_metabox($metabox_strings);

?>
