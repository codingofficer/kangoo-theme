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
        'capability'  => 'manage_options',
        'redirect'    => false,
        'position'    => 58,
        'icon_url'    => 'dashicons-admin-generic',
        'autoload'    => true,
        'update_button' => __('Save Settings', 'kangoo'),
        'updated_message' => __('Kangoo settings updated.', 'kangoo'),
    ));
}
add_action('acf/init', 'kangoo_register_control_panel_options_page', 1);

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

    $settings = get_option('woocommerce_access_worldpay_checkout_settings', array());
    if (!is_array($settings)) {
        $settings = array();
    }
    $settings['enabled'] = $enabled ? 'yes' : 'no';
    update_option('woocommerce_access_worldpay_checkout_settings', $settings);

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
                'instructions' => __('Controls the embedded Worldpay card gateway visibility at checkout. Use Try mode in WooCommerce payment settings until a successful test order is complete.', 'kangoo'),
                'message' => __('Enable Worldpay at checkout', 'kangoo'),
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

