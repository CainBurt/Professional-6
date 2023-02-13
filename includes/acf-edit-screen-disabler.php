<?php

if( function_exists('acf_add_local_field_group') ) {
    acf_add_local_field_group(array (
        'key' => 'group_595e2b377bb9f',
        'title' => 'Menu Options',
        'fields' => array (
            array (
                'key' => 'field_595e2b4027c09',
                'label' => 'Enable ACF Edit Screen in the backend',
                'name' => 'enable_acf_edit',
                'type' => 'true_false',
                'instructions' => 'IMPORTANT. ACF Fields should be edited via a local version of the theme and the acf-json file sync, editing the fields on the site here will clash with changes in the theme and break the syncing. Enable this at your own risk.',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array (
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'message' => 'READ ABOVE BEFORE TICKING',
                'default_value' => 0,
                'ui' => 0,
                'ui_on_text' => '',
                'ui_off_text' => '',
            ),
            array (
                'key' => 'field_595e2d4027c09',
                'label' => 'Let non-admins see this page',
                'name' => 'show_debug_menu',
                'type' => 'true_false',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array (
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => 0,
                'ui' => 0,
                'ui_on_text' => '',
                'ui_off_text' => '',
            ),
            array (
                'key' => 'field_595e2c4027c09',
                'label' => 'Show comments menu in admin screen',
                'name' => 'enable_comments_menu',
                'type' => 'true_false',
                'instructions' => 'This does not turn on / off comments, only hide / show the menu in the backend.',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array (
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => 0,
                'ui' => 0,
                'ui_on_text' => '',
                'ui_off_text' => '',
            )
        ),
        'location' => array (
            array (
                array (
                    'param' => 'options_page',
                    'operator' => '==',
                    'value' => 'acf-options-debug-options',
                ),
            )
        ),
        'menu_order' => -1,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => 1,
        'description' => '',
    ));
};
