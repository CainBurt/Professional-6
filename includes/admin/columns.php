<?php

function markerColumns($columns) {
    $new_columns          = [];
    $new_columns['cb']    = $columns['cb'];
    $new_columns['thumb'] = __('Marker', 'categories-images');

    unset($columns['cb']);

    return array_merge($new_columns, $columns);
}

add_filter('manage_edit-wpsl_store_category_columns', 'markerColumns');

function markerColumn($columns, $column, $id) {
    if ($column == 'thumb') {
        $marker = get_field('category_image_marker', 'wpsl_store_category_' . $id);

        if (isset($marker['url']) && $marker['url']) {
            $columns = '<span><img src="' . $marker['url'] . '" alt="' . __('Thumbnail', 'categories-images') . '" class="wp-post-image" /></span>';
        }
    }


    return $columns;
}

add_filter('manage_wpsl_store_category_custom_column', 'markerColumn', 10, 3);

