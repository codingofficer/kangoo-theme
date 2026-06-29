<?php
if (!defined('ABSPATH')) {
    exit;
}

function kangoo_register_control_panel_options_page() {
    if (!function_exists('acf_add_options_page')) {
        return;
    }

    acf_add_options_page(array(
        'page_title'  => __('Kangoo Control Panel', 'kangoo'),
        'menu_title'  => __('Control Panel', 'kangoo'),
        'menu_slug'   => 'control-panel',
        'capability'  => 'edit_posts',
        'redirect'    => false,
        'position'    => 58,
        'icon_url'    => 'dashicons-admin-generic',
        'autoload'    => true,
        'update_button' => __('Save Settings', 'kangoo'),
        'updated_message' => __('Kangoo settings updated.', 'kangoo'),
    ));
}
add_action('acf/init', 'kangoo_register_control_panel_options_page', 1);

function kangoo_register_control_panel_subpage($page_title, $menu_title, $menu_slug) {
    if (!function_exists('acf_add_options_sub_page')) {
        return;
    }

    if (function_exists('acf_get_options_page') && acf_get_options_page($menu_slug)) {
        return;
    }

    acf_add_options_sub_page(array(
        'page_title'  => $page_title,
        'menu_title'  => $menu_title,
        'menu_slug'   => $menu_slug,
        'parent_slug' => 'control-panel',
        'capability'  => 'edit_posts',
        'redirect'    => false,
        'post_id'     => 'options',
        'autoload'    => true,
        'update_button' => __('Save Settings', 'kangoo'),
        'updated_message' => __('Kangoo settings updated.', 'kangoo'),
    ));
}

function kangoo_register_control_panel_subpages() {
    kangoo_register_control_panel_subpage(
        __('Contact Settings', 'kangoo'),
        __('Contact Settings', 'kangoo'),
        'contact-settings'
    );

    kangoo_register_control_panel_subpage(
        __('Footer', 'kangoo'),
        __('Footer', 'kangoo'),
        'footer'
    );

    kangoo_register_control_panel_subpage(
        __('Mega Menu Settings', 'kangoo'),
        __('Mega Menu', 'kangoo'),
        'mega-menu-settings'
    );
}
add_action('acf/init', 'kangoo_register_control_panel_subpages', 2);

function kangoo_prioritize_control_panel_submenu() {
    global $submenu;

    if (empty($submenu['control-panel']) || !is_array($submenu['control-panel'])) {
        return;
    }

    $control_panel_item = array(
        __('Control Panel', 'kangoo'),
        'edit_posts',
        'control-panel',
        __('Kangoo Control Panel', 'kangoo'),
    );

    $filtered_items = array();
    foreach ($submenu['control-panel'] as $item) {
        if (($item[2] ?? '') === 'control-panel') {
            continue;
        }

        $filtered_items[] = $item;
    }

    array_unshift($filtered_items, $control_panel_item);
    $submenu['control-panel'] = $filtered_items;
}
add_action('admin_menu', 'kangoo_prioritize_control_panel_submenu', 9999);

function kangoo_worldpay_gateway_enabled() {
    $enabled = get_option('kangoo_worldpay_gateway_enabled', 0);

    if (function_exists('get_field') && get_option('options_kangoo_worldpay_gateway_enabled', null) !== null) {
        $enabled = get_field('kangoo_worldpay_gateway_enabled', 'option');
    }

    return !empty($enabled);
}

function kangoo_sync_worldpay_gateway_enabled($enabled) {
    $enabled = !empty($enabled);
    update_option('kangoo_worldpay_gateway_enabled', $enabled ? 1 : 0);

    $gateway_options = array(
        'woocommerce_access_worldpay_checkout_settings',
        'woocommerce_access_worldpay_hpp_settings',
    );

    foreach ($gateway_options as $option_name) {
        $settings = get_option($option_name, array());
        if (!is_array($settings)) {
            $settings = array();
        }

        $settings['enabled'] = $enabled ? 'yes' : 'no';
        update_option($option_name, $settings);
    }

    return $enabled ? 1 : 0;
}

function kangoo_register_payment_control_acf_fields() {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group(array(
        'key' => 'group_kangoo_payment_controls',
        'title' => __('Payments', 'kangoo'),
        'fields' => array(
            array(
                'key' => 'field_kangoo_worldpay_gateway_enabled',
                'label' => __('Worldpay Checkout', 'kangoo'),
                'name' => 'kangoo_worldpay_gateway_enabled',
                'type' => 'true_false',
                'instructions' => __('Controls the Worldpay embedded card and hosted checkout gateways. Use Try mode in WooCommerce payment settings until a successful test order is complete.', 'kangoo'),
                'message' => __('Enable Worldpay gateways at checkout', 'kangoo'),
                'ui' => 1,
                'default_value' => 0,
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'options_page',
                    'operator' => '==',
                    'value' => 'control-panel',
                ),
            ),
        ),
        'position' => 'acf_after_title',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'active' => true,
        'show_in_rest' => 0,
    ));
}
add_action('acf/init', 'kangoo_register_payment_control_acf_fields');

function kangoo_sync_worldpay_gateway_enabled_from_acf($value) {
    return kangoo_sync_worldpay_gateway_enabled($value);
}
add_filter('acf/update_value/name=kangoo_worldpay_gateway_enabled', 'kangoo_sync_worldpay_gateway_enabled_from_acf', 10, 1);
