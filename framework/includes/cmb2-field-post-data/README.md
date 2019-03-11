# CMB2 Field Data

## Installation

#### Composer
`composer require anewholm/cmb2-field-post-data`

#### Manual
1. Download the plugin from wherever you heard about it
2. Place the plugin folder in your `/wp-content/plugins/` directory
3. Activate the plugin in the plugins dashboard

# Usage
```php
array(
  'id' => $prefix . 'location_metabox_post_data',
  'title' => __( 'Data', $plugin_slug ),
  'fields' => array(
    array(
      'name' => __( 'Post date', $plugin_slug ),
      'id' => $plugin_slug . '_location_post_data',
      'type' => 'post_data',
      'field' => 'post_date',
    ),
  ),
),
```
