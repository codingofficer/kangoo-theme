<?php
if (!defined('ABSPATH')) {
    exit;
}

function kangoo_worldpay_checkout_block_hotfix_src($src, $handle) {
    if ('wc-payment-method-access_worldpay_checkout' !== $handle) {
        return $src;
    }

    $relative_path = '/assets/js/worldpay-checkout-block-hotfix.js';
    $file_path = get_theme_file_path($relative_path);

    if (!file_exists($file_path)) {
        return $src;
    }

    return add_query_arg(
        'ver',
        (string) filemtime($file_path),
        set_url_scheme(get_theme_file_uri($relative_path), 'https')
    );
}
add_filter('script_loader_src', 'kangoo_worldpay_checkout_block_hotfix_src', 20, 2);
