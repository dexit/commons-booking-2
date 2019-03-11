# CMB2 Field Link

## Installation

#### Composer
`composer require anewholm/cmb2-field-post-link`

#### Manual
1. Download the plugin from wherever you heard about it
2. Place the plugin folder in your `/wp-content/plugins/` directory
3. Activate the plugin in the plugins dashboard

# Usage
```php
array(
  'id' => $prefix . 'location_metabox_post_link',
  'title' => __( 'Icon', $plugin_slug ),
  'fields' => array(
    array(
      'name' => __( 'Link to Location thing', $plugin_slug ),
      'id' => $plugin_slug . '_location_post_link',
      'type' => 'post_link',
    ),
  ),
),
```
