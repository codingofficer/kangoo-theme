<?php
if (!defined('ABSPATH')) {
    exit;
}

function kangoo_mark_dynamic_commerce_pages_uncacheable() {
    $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
    $path = trim((string) wp_parse_url($request_uri, PHP_URL_PATH), '/');

    if (in_array($path, array('cart', 'checkout', 'my-account'), true) || strpos($path, 'my-account/') === 0) {
        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }

        if (!defined('DONOTCACHEOBJECT')) {
            define('DONOTCACHEOBJECT', true);
        }
    }
}
kangoo_mark_dynamic_commerce_pages_uncacheable();

function kangoo_plain_wc_price($amount) {
    if (!function_exists('wc_price')) {
        return html_entity_decode('&pound;', ENT_QUOTES, 'UTF-8') . number_format((float) $amount, 2);
    }

    return html_entity_decode(wp_strip_all_tags(wc_price((float) $amount)), ENT_QUOTES, 'UTF-8');
}

function kangoo_get_app_apk_release() {
    if (function_exists('kangoo_app_api_latest_apk_release')) {
        $release = kangoo_app_api_latest_apk_release();

        if (!empty($release['apk_url'])) {
            return $release;
        }
    }

    $release = array(
        'apk_url' => home_url('/app/android/kangoo-pouches-v0.6.1.apk'),
        'version' => '0.6.1',
        'file' => 'kangoo-pouches-v0.6.1.apk',
    );
    $dir = trailingslashit(ABSPATH) . 'app/android';

    if (!is_dir($dir) || !is_readable($dir)) {
        return $release;
    }

    $files = glob(trailingslashit($dir) . 'kangoo-pouches-v*.apk');

    if (!$files) {
        return $release;
    }

    usort($files, function($a, $b) {
        $version_a = kangoo_get_app_version_from_apk_filename($a);
        $version_b = kangoo_get_app_version_from_apk_filename($b);
        $compare = version_compare($version_b ?: '0', $version_a ?: '0');

        if ($compare !== 0) {
            return $compare;
        }

        return filemtime($b) <=> filemtime($a);
    });

    $file = basename($files[0]);
    $release['file'] = $file;
    $release['version'] = kangoo_get_app_version_from_apk_filename($file) ?: $release['version'];
    $release['apk_url'] = home_url('/app/android/' . rawurlencode($file));

    return $release;
}

function kangoo_get_app_version_from_apk_filename($file) {
    if (preg_match('/kangoo-pouches-v([0-9]+(?:\.[0-9]+){1,3})\.apk$/', basename((string) $file), $matches)) {
        return $matches[1];
    }

    return '';
}

function kangoo_get_url_coupon_code() {
    if (empty($_GET['coupon'])) {
        return '';
    }

    $coupon_code = sanitize_text_field(wp_unslash($_GET['coupon']));
    $coupon_code = function_exists('wc_format_coupon_code') ? wc_format_coupon_code($coupon_code) : strtolower($coupon_code);

    return $coupon_code;
}

function kangoo_get_removed_coupon_code() {
    if (empty($_GET['remove_coupon'])) {
        return '';
    }

    $coupon_code = sanitize_text_field(wp_unslash($_GET['remove_coupon']));

    return function_exists('wc_format_coupon_code') ? wc_format_coupon_code($coupon_code) : strtolower($coupon_code);
}

function kangoo_url_coupon_lifetime() {
    return 30 * MINUTE_IN_SECONDS;
}

function kangoo_url_coupon_timestamp_expired($timestamp) {
    $timestamp = absint($timestamp);

    return !$timestamp || (time() - $timestamp) > kangoo_url_coupon_lifetime();
}

function kangoo_track_url_coupon($coupon_code) {
    if (!function_exists('WC') || !WC()->session || !$coupon_code) {
        return;
    }

    WC()->session->set('kangoo_url_coupon_applied', wc_format_coupon_code($coupon_code));
    WC()->session->set('kangoo_url_coupon_applied_at', time());
}

function kangoo_remember_url_coupon_attempt($coupon_code) {
    if (!function_exists('WC') || !WC()->session || !$coupon_code) {
        return;
    }

    WC()->session->set('kangoo_url_coupon_last_attempted', wc_format_coupon_code($coupon_code));
    WC()->session->set('kangoo_url_coupon_last_attempted_at', time());
}

function kangoo_clear_url_coupon_tracking() {
    if (!function_exists('WC') || !WC()->session) {
        return;
    }

    WC()->session->__unset('kangoo_pending_url_coupon');
    WC()->session->__unset('kangoo_pending_url_coupon_at');
    WC()->session->__unset('kangoo_url_coupon_applied');
    WC()->session->__unset('kangoo_url_coupon_applied_at');
    WC()->session->__unset('kangoo_url_coupon_last_attempted');
    WC()->session->__unset('kangoo_url_coupon_last_attempted_at');
}

function kangoo_clear_coupon_tracking_for_removed_coupon($coupon_code = '') {
    $coupon_code = $coupon_code ? $coupon_code : kangoo_get_removed_coupon_code();
    $coupon_code = $coupon_code && function_exists('wc_format_coupon_code') ? wc_format_coupon_code($coupon_code) : strtolower((string) $coupon_code);

    if (!$coupon_code || !function_exists('WC')) {
        return;
    }

    if (function_exists('wc_load_cart') && !WC()->session) {
        wc_load_cart();
    }

    if (WC()->session) {
        $tracked_coupon_keys = array(
            'kangoo_pending_url_coupon'       => 'kangoo_pending_url_coupon_at',
            'kangoo_url_coupon_applied'       => 'kangoo_url_coupon_applied_at',
            'kangoo_url_coupon_last_attempted' => 'kangoo_url_coupon_last_attempted_at',
        );

        foreach ($tracked_coupon_keys as $coupon_key => $timestamp_key) {
            $tracked_coupon = (string) WC()->session->get($coupon_key);
            $tracked_coupon = $tracked_coupon && function_exists('wc_format_coupon_code') ? wc_format_coupon_code($tracked_coupon) : strtolower($tracked_coupon);

            if ($tracked_coupon === $coupon_code) {
                WC()->session->__unset($coupon_key);
                WC()->session->__unset($timestamp_key);
            }
        }
    }

    if (function_exists('kangoo_referrals_get_active_code') && function_exists('kangoo_referrals_clear_active_code') && $coupon_code === kangoo_referrals_get_active_code()) {
        kangoo_referrals_clear_active_code();
    }
}
add_action('wp_loaded', 'kangoo_clear_coupon_tracking_for_removed_coupon', 1);
add_action('woocommerce_removed_coupon', 'kangoo_clear_coupon_tracking_for_removed_coupon', 1);

function kangoo_remove_url_coupon_notices($coupon_code) {
    if (!$coupon_code || !function_exists('wc_get_notices') || !function_exists('wc_set_notices')) {
        return;
    }

    $coupon_code = strtolower(wc_format_coupon_code($coupon_code));
    $notices = wc_get_notices();

    foreach ($notices as $notice_type => $messages) {
        foreach ($messages as $index => $notice) {
            $message = is_array($notice) && isset($notice['notice']) ? (string) $notice['notice'] : (string) $notice;
            $plain_message = strtolower(wp_strip_all_tags(html_entity_decode($message, ENT_QUOTES, get_bloginfo('charset'))));

            if (strpos($plain_message, $coupon_code) !== false && strpos($plain_message, 'coupon') !== false) {
                unset($notices[$notice_type][$index]);
            }
        }

        if (empty($notices[$notice_type])) {
            unset($notices[$notice_type]);
        }
    }

    wc_set_notices($notices);
}

function kangoo_apply_coupon_without_customer_notices($coupon_code) {
    if (!function_exists('WC') || !WC()->cart) {
        return false;
    }

    $notices_before = function_exists('wc_get_notices') ? wc_get_notices() : array();
    $applied = WC()->cart->apply_coupon($coupon_code);

    if (!$applied) {
        if (function_exists('wc_set_notices')) {
            wc_set_notices($notices_before);
        } elseif (function_exists('wc_clear_notices')) {
            wc_clear_notices();
        }

        kangoo_remove_url_coupon_notices($coupon_code);
    }

    return $applied;
}

function kangoo_expire_stale_url_coupon() {
    if ((is_admin() && !wp_doing_ajax()) || !function_exists('WC')) {
        return;
    }

    if (function_exists('wc_load_cart') && (!WC()->cart || !WC()->session)) {
        wc_load_cart();
    }

    if (!WC()->cart || !WC()->session) {
        return;
    }

    $current_url_coupon = kangoo_get_url_coupon_code();
    $pending_coupon = (string) WC()->session->get('kangoo_pending_url_coupon');
    $pending_at = absint(WC()->session->get('kangoo_pending_url_coupon_at'));
    $applied_coupon = (string) WC()->session->get('kangoo_url_coupon_applied');
    $applied_at = absint(WC()->session->get('kangoo_url_coupon_applied_at'));
    $last_attempted_coupon = (string) WC()->session->get('kangoo_url_coupon_last_attempted');
    $last_attempted_at = absint(WC()->session->get('kangoo_url_coupon_last_attempted_at'));

    if ($pending_coupon && !$current_url_coupon && kangoo_url_coupon_timestamp_expired($pending_at)) {
        WC()->session->__unset('kangoo_pending_url_coupon');
        WC()->session->__unset('kangoo_pending_url_coupon_at');
    }

    if ($last_attempted_coupon && !$current_url_coupon && kangoo_url_coupon_timestamp_expired($last_attempted_at)) {
        WC()->session->__unset('kangoo_url_coupon_last_attempted');
        WC()->session->__unset('kangoo_url_coupon_last_attempted_at');
    }

    if (!$applied_coupon || $current_url_coupon === $applied_coupon || !kangoo_url_coupon_timestamp_expired($applied_at)) {
        return;
    }

    if (WC()->cart->has_discount($applied_coupon)) {
        WC()->cart->remove_coupon($applied_coupon);

        if (!doing_action('woocommerce_before_calculate_totals')) {
            WC()->cart->calculate_totals();
        }
    }

    kangoo_clear_url_coupon_tracking();
}
add_action('wp_loaded', 'kangoo_expire_stale_url_coupon', 18);
add_action('woocommerce_cart_loaded_from_session', 'kangoo_expire_stale_url_coupon', 1);
add_action('woocommerce_before_calculate_totals', 'kangoo_expire_stale_url_coupon', 1);

function kangoo_clean_url_coupon_notices() {
    if ((is_admin() && !wp_doing_ajax()) || !function_exists('WC')) {
        return;
    }

    if (function_exists('wc_load_cart') && (!WC()->cart || !WC()->session)) {
        wc_load_cart();
    }

    if (!WC()->session) {
        return;
    }

    $coupon_code = kangoo_get_url_coupon_code();

    if (!$coupon_code) {
        $coupon_code = (string) WC()->session->get('kangoo_pending_url_coupon');
    }

    if (!$coupon_code) {
        $coupon_code = (string) WC()->session->get('kangoo_url_coupon_last_attempted');
    }

    if ($coupon_code) {
        kangoo_remove_url_coupon_notices($coupon_code);
    }
}
add_action('wp_loaded', 'kangoo_clean_url_coupon_notices', 99);

function kangoo_apply_url_coupon() {
    if ((is_admin() && !wp_doing_ajax()) || !function_exists('WC')) {
        return;
    }

    if (kangoo_get_removed_coupon_code()) {
        return;
    }

    $coupon_code = kangoo_get_url_coupon_code();

    if (!$coupon_code) {
        return;
    }

    if (function_exists('kangoo_rewards_coupon_code') && $coupon_code === kangoo_rewards_coupon_code()) {
        return;
    }

    if (function_exists('wc_load_cart') && (!WC()->cart || !WC()->session)) {
        wc_load_cart();
    }

    if (!WC()->cart || !WC()->session) {
        if (WC()->session) {
            kangoo_remember_url_coupon_attempt($coupon_code);
            WC()->session->set('kangoo_pending_url_coupon', $coupon_code);
            WC()->session->set('kangoo_pending_url_coupon_at', time());
        }

        return;
    }

    WC()->session->set_customer_session_cookie(true);
    kangoo_remember_url_coupon_attempt($coupon_code);
    WC()->session->set('kangoo_pending_url_coupon', $coupon_code);
    WC()->session->set('kangoo_pending_url_coupon_at', time());

    if (WC()->cart->has_discount($coupon_code)) {
        WC()->session->__unset('kangoo_pending_url_coupon');
        WC()->session->__unset('kangoo_pending_url_coupon_at');
        kangoo_track_url_coupon($coupon_code);
        return;
    }

    $coupon = new WC_Coupon($coupon_code);
    $is_referral_coupon = function_exists('kangoo_referrals_is_coupon_code') && kangoo_referrals_is_coupon_code($coupon_code);

    if (!$coupon->get_id() && !$is_referral_coupon) {
        WC()->session->__unset('kangoo_pending_url_coupon');
        WC()->session->__unset('kangoo_pending_url_coupon_at');
        kangoo_remove_url_coupon_notices($coupon_code);
        return;
    }

    $applied = kangoo_apply_coupon_without_customer_notices($coupon_code);

    if ($applied) {
        WC()->session->__unset('kangoo_pending_url_coupon');
        WC()->session->__unset('kangoo_pending_url_coupon_at');
        kangoo_track_url_coupon($coupon_code);
        WC()->cart->calculate_totals();
    }
}
add_action('wp_loaded', 'kangoo_apply_url_coupon', 20);

function kangoo_apply_pending_url_coupon() {
    if (!function_exists('WC') || !WC()->cart || !WC()->session) {
        return;
    }

    if (kangoo_get_removed_coupon_code()) {
        return;
    }

    $coupon_code = (string) WC()->session->get('kangoo_pending_url_coupon');
    $pending_at = absint(WC()->session->get('kangoo_pending_url_coupon_at'));

    if ($coupon_code && kangoo_url_coupon_timestamp_expired($pending_at)) {
        WC()->session->__unset('kangoo_pending_url_coupon');
        WC()->session->__unset('kangoo_pending_url_coupon_at');
        kangoo_remove_url_coupon_notices($coupon_code);
        return;
    }

    if (!$coupon_code || WC()->cart->has_discount($coupon_code)) {
        if ($coupon_code && WC()->cart->has_discount($coupon_code)) {
            WC()->session->__unset('kangoo_pending_url_coupon');
            WC()->session->__unset('kangoo_pending_url_coupon_at');
            kangoo_track_url_coupon($coupon_code);
        }

        return;
    }

    if (function_exists('kangoo_rewards_coupon_code') && $coupon_code === kangoo_rewards_coupon_code()) {
        WC()->session->__unset('kangoo_pending_url_coupon');
        WC()->session->__unset('kangoo_pending_url_coupon_at');
        kangoo_remove_url_coupon_notices($coupon_code);
        return;
    }

    $coupon = new WC_Coupon($coupon_code);
    $is_referral_coupon = function_exists('kangoo_referrals_is_coupon_code') && kangoo_referrals_is_coupon_code($coupon_code);

    if (!$coupon->get_id() && !$is_referral_coupon) {
        WC()->session->__unset('kangoo_pending_url_coupon');
        WC()->session->__unset('kangoo_pending_url_coupon_at');
        kangoo_remove_url_coupon_notices($coupon_code);
        return;
    }

    $applied = kangoo_apply_coupon_without_customer_notices($coupon_code);

    if ($applied) {
        WC()->session->__unset('kangoo_pending_url_coupon');
        WC()->session->__unset('kangoo_pending_url_coupon_at');
        kangoo_track_url_coupon($coupon_code);
        return;
    }

    WC()->session->__unset('kangoo_pending_url_coupon');
    WC()->session->__unset('kangoo_pending_url_coupon_at');
    kangoo_remove_url_coupon_notices($coupon_code);
}
add_action('woocommerce_cart_loaded_from_session', 'kangoo_apply_pending_url_coupon', 20);
add_action('woocommerce_before_calculate_totals', 'kangoo_apply_pending_url_coupon', 5);

function kangoo_ajax_apply_url_coupon() {
    check_ajax_referer('kangoo_url_coupon', 'nonce');

    if (!function_exists('WC')) {
        wp_send_json_error(array(
            'message' => __('WooCommerce is not available.', 'kangoo'),
        ), 400);
    }

    $coupon_code = isset($_POST['coupon']) ? sanitize_text_field(wp_unslash($_POST['coupon'])) : '';
    $coupon_code = function_exists('wc_format_coupon_code') ? wc_format_coupon_code($coupon_code) : strtolower($coupon_code);

    if (!$coupon_code || (function_exists('kangoo_rewards_coupon_code') && $coupon_code === kangoo_rewards_coupon_code())) {
        wp_send_json_error(array(
            'message' => __('Invalid coupon code.', 'kangoo'),
        ), 400);
    }

    if (function_exists('wc_load_cart') && (!WC()->cart || !WC()->session)) {
        wc_load_cart();
    }

    if (!WC()->session) {
        wp_send_json_error(array(
            'message' => __('Unable to start cart session.', 'kangoo'),
        ), 400);
    }

    $coupon = new WC_Coupon($coupon_code);
    $is_referral_coupon = function_exists('kangoo_referrals_is_coupon_code') && kangoo_referrals_is_coupon_code($coupon_code);

    if (!$coupon->get_id() && !$is_referral_coupon) {
        wp_send_json_error(array(
            'message' => __('That coupon code could not be found.', 'kangoo'),
        ), 404);
    }

    WC()->session->set_customer_session_cookie(true);
    kangoo_remember_url_coupon_attempt($coupon_code);
    WC()->session->set('kangoo_pending_url_coupon', $coupon_code);
    WC()->session->set('kangoo_pending_url_coupon_at', time());

    if (WC()->cart && !WC()->cart->is_empty() && !WC()->cart->has_discount($coupon_code)) {
        kangoo_apply_pending_url_coupon();
    }

    if (WC()->cart && WC()->cart->has_discount($coupon_code)) {
        kangoo_track_url_coupon($coupon_code);
    }

    wp_send_json_success(array(
        'coupon'  => $coupon_code,
        'applied' => WC()->cart ? WC()->cart->has_discount($coupon_code) : false,
    ));
}
add_action('wp_ajax_kangoo_apply_url_coupon', 'kangoo_ajax_apply_url_coupon');
add_action('wp_ajax_nopriv_kangoo_apply_url_coupon', 'kangoo_ajax_apply_url_coupon');

function kangoo_theme_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('woocommerce');
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ));
    add_theme_support('custom-logo', array(
        'height'      => 60,
        'width'       => 200,
        'flex-height' => true,
        'flex-width'  => true,
    ));

    register_nav_menus(array(
        'primary' => __('Primary Menu', 'kangoo'),
        'footer'  => __('Footer Menu', 'kangoo'),
    ));
}
add_action('after_setup_theme', 'kangoo_theme_setup');

function kangoo_append_app_link_to_menus($items, $args) {
    $items = (string) $items;

    if (!is_object($args) || empty($args->theme_location)) {
        return $items;
    }

    if (!in_array($args->theme_location, array('primary', 'footer'), true)) {
        return $items;
    }

    if (stripos($items, '/kangoo-app') !== false || stripos($items, 'Kangoo App') !== false) {
        return $items;
    }

    $items .= sprintf(
        '<li class="menu-item menu-item-type-custom menu-item-kangoo-app"><a href="%1$s">%2$s</a></li>',
        esc_url(home_url('/kangoo-app/')),
        esc_html__('Kangoo App', 'kangoo')
    );

    return $items;
}
add_filter('wp_nav_menu_items', 'kangoo_append_app_link_to_menus', 10, 2);

function kangoo_theme_favicon_url($url, $size, $blog_id) {
    return get_template_directory_uri() . '/assets/images/kangoo-icon-white.png';
}
add_filter('get_site_icon_url', 'kangoo_theme_favicon_url', 10, 3);

function kangoo_theme_favicon_meta() {
    if (function_exists('has_site_icon') && has_site_icon()) {
        return;
    }

    $icon_url = get_template_directory_uri() . '/assets/images/kangoo-icon-white.png';
    echo '<link rel="icon" href="' . esc_url($icon_url) . '" sizes="512x512" type="image/png">' . "\n";
    echo '<link rel="apple-touch-icon" href="' . esc_url($icon_url) . '">' . "\n";
}
add_action('wp_head', 'kangoo_theme_favicon_meta', 1);
add_action('admin_head', 'kangoo_theme_favicon_meta', 1);

function kangoo_get_acf_image_id($image) {
    if (is_array($image)) {
        if (!empty($image['ID'])) {
            return (int) $image['ID'];
        }

        if (!empty($image['id'])) {
            return (int) $image['id'];
        }
    }

    return is_numeric($image) ? (int) $image : 0;
}

function kangoo_get_acf_image_alt($image, $attachment_id = 0) {
    if (is_array($image) && isset($image['alt'])) {
        return (string) $image['alt'];
    }

    if ($attachment_id) {
        return (string) get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
    }

    return '';
}

function kangoo_get_webp_url_for_image_url($url) {
    $url = trim((string) $url);

    if ($url === '' || !preg_match('/\.(?:png|jpe?g)(?:\?.*)?$/i', $url)) {
        return '';
    }

    $uploads = wp_get_upload_dir();

    if (empty($uploads['baseurl']) || empty($uploads['basedir'])) {
        return '';
    }

    $image_path = (string) wp_parse_url($url, PHP_URL_PATH);
    $base_path = (string) wp_parse_url($uploads['baseurl'], PHP_URL_PATH);

    if ($image_path === '' || $base_path === '' || strpos($image_path, $base_path) !== 0) {
        return '';
    }

    $relative_path = rawurldecode(ltrim(substr($image_path, strlen($base_path)), '/'));
    $webp_relative_path = preg_replace('/\.(?:png|jpe?g)$/i', '.webp', $relative_path);
    $webp_path = wp_normalize_path(trailingslashit($uploads['basedir']) . $webp_relative_path);

    if (!file_exists($webp_path)) {
        return '';
    }

    return trailingslashit($uploads['baseurl']) . str_replace('%2F', '/', rawurlencode($webp_relative_path));
}

function kangoo_get_attachment_webp_srcset($attachment_id, $size = 'large') {
    $srcset = wp_get_attachment_image_srcset($attachment_id, $size);

    if (!$srcset) {
        $src = wp_get_attachment_image_url($attachment_id, $size);
        $webp_src = kangoo_get_webp_url_for_image_url($src);

        return $webp_src ? esc_url($webp_src) : '';
    }

    $webp_candidates = array();

    foreach (explode(',', $srcset) as $candidate) {
        $candidate = preg_replace('/\s+/', ' ', trim($candidate));

        if ($candidate === '') {
            continue;
        }

        $parts = explode(' ', $candidate);
        $candidate_url = array_shift($parts);
        $webp_url = kangoo_get_webp_url_for_image_url($candidate_url);

        if ($webp_url === '') {
            continue;
        }

        $descriptor = trim(implode(' ', $parts));
        $webp_candidates[] = trim(esc_url($webp_url) . ' ' . $descriptor);
    }

    return implode(', ', $webp_candidates);
}

function kangoo_wrap_attachment_image_with_webp($markup, $attachment_id, $size = 'large', $sizes = '') {
    if (!$markup || !$attachment_id) {
        return $markup;
    }

    $webp_srcset = kangoo_get_attachment_webp_srcset($attachment_id, $size);

    if ($webp_srcset === '') {
        return $markup;
    }

    if ($sizes === '') {
        $sizes = wp_get_attachment_image_sizes($attachment_id, $size);
    }

    $source = '<source type="image/webp" srcset="' . esc_attr($webp_srcset) . '"';

    if ($sizes) {
        $source .= ' sizes="' . esc_attr($sizes) . '"';
    }

    $source .= '>';

    return '<picture>' . $source . $markup . '</picture>';
}

function kangoo_render_acf_image($image, $size = 'large', $attr = array()) {
    $attachment_id = kangoo_get_acf_image_id($image);
    $skip_srcset = !empty($attr['kangoo_no_srcset']);
    unset($attr['kangoo_no_srcset']);

    $attr = wp_parse_args($attr, array(
        'decoding' => 'async',
    ));

    if (!array_key_exists('alt', $attr)) {
        $attr['alt'] = kangoo_get_acf_image_alt($image, $attachment_id);
    }

    if ($attachment_id && $skip_srcset) {
        $image_src = wp_get_attachment_image_src($attachment_id, $size);

        if ($image_src) {
            if (empty($attr['width'])) {
                $attr['width'] = (int) $image_src[1];
            }

            if (empty($attr['height'])) {
                $attr['height'] = (int) $image_src[2];
            }

            $attributes = '';
            foreach ($attr as $name => $value) {
                if ($value === false || $value === null || $value === '') {
                    continue;
                }

                $attributes .= ' ' . esc_attr($name) . '="' . esc_attr($value) . '"';
            }

            return '<img src="' . esc_url($image_src[0]) . '"' . $attributes . '>';
        }
    }

    if ($attachment_id) {
        $markup = wp_get_attachment_image($attachment_id, $size, false, $attr);

        if ($markup) {
            return kangoo_wrap_attachment_image_with_webp($markup, $attachment_id, $size, isset($attr['sizes']) ? $attr['sizes'] : '');
        }
    }

    $src = function_exists('kangoo_get_image_url_from_acf_value') ? kangoo_get_image_url_from_acf_value($image, $size) : '';

    if ($src === '') {
        return '';
    }

    if (is_array($image)) {
        if (empty($attr['width']) && !empty($image['width'])) {
            $attr['width'] = (int) $image['width'];
        }

        if (empty($attr['height']) && !empty($image['height'])) {
            $attr['height'] = (int) $image['height'];
        }
    }

    $attributes = '';
    foreach ($attr as $name => $value) {
        if ($value === false || $value === null || $value === '') {
            continue;
        }

        $attributes .= ' ' . esc_attr($name) . '="' . esc_attr($value) . '"';
    }

    return '<img src="' . esc_url($src) . '"' . $attributes . '>';
}

function kangoo_get_home_hero_image() {
    if (!is_front_page() || !function_exists('get_field')) {
        return null;
    }

    $sections = get_field('homepage_sections');

    if (!is_array($sections)) {
        return null;
    }

    foreach ($sections as $section) {
        if (!is_array($section) || ($section['acf_fc_layout'] ?? '') !== 'hero') {
            continue;
        }

        if (array_key_exists('show_section', $section) && ($section['show_section'] === false || $section['show_section'] === '0' || $section['show_section'] === 0)) {
            return null;
        }

        return $section['hero_image'] ?? null;
    }

    return null;
}

function kangoo_preload_home_hero_image() {
    $image = kangoo_get_home_hero_image();

    if (!$image) {
        return;
    }

    $attachment_id = kangoo_get_acf_image_id($image);
    $href = $attachment_id ? wp_get_attachment_image_url($attachment_id, 'large') : kangoo_get_image_url_from_acf_value($image, 'large');

    if (!$href) {
        return;
    }

    $attributes = array(
        'rel' => 'preload',
        'as' => 'image',
        'href' => $href,
    );

    if ($attachment_id) {
        $srcset = wp_get_attachment_image_srcset($attachment_id, 'large');
        $webp_href = kangoo_get_webp_url_for_image_url($href);
        $webp_srcset = kangoo_get_attachment_webp_srcset($attachment_id, 'large');

        if ($webp_href) {
            $attributes['href'] = $webp_href;
            $attributes['type'] = 'image/webp';
        }

        if ($webp_srcset || $srcset) {
            $attributes['imagesrcset'] = $webp_srcset ? $webp_srcset : $srcset;
            $attributes['imagesizes'] = '(max-width: 768px) 86vw, 42vw';
        }
    }

    $markup = '<link';
    foreach ($attributes as $name => $value) {
        $markup .= ' ' . esc_attr($name) . '="' . esc_attr($value) . '"';
    }

    echo $markup . '>' . "\n";
}
add_action('wp_head', 'kangoo_preload_home_hero_image', 2);

function kangoo_is_product_archive_view() {
    return is_front_page()
        || (function_exists('is_shop') && is_shop())
        || (function_exists('is_product_category') && is_product_category())
        || (function_exists('is_product_tag') && is_product_tag())
        || (function_exists('is_product_taxonomy') && is_product_taxonomy());
}

function kangoo_is_commerce_view() {
    return (function_exists('is_woocommerce') && is_woocommerce())
        || (function_exists('is_cart') && is_cart())
        || (function_exists('is_checkout') && is_checkout())
        || (function_exists('is_account_page') && is_account_page());
}

function kangoo_is_blog_view() {
    return is_home()
        || is_singular('post')
        || is_category()
        || is_tag()
        || is_date()
        || is_author();
}

function kangoo_is_info_page_view() {
    if (!is_page() || is_front_page() || kangoo_is_commerce_view()) {
        return false;
    }

    $tool_templates = array(
        'page-templates/template-pouch-finder.php',
        'page-templates/template-pouch-comparison.php',
        'page-templates/template-build-a-box.php',
        'page-templates/template-strength-ladder.php',
        'page-templates/template-flavour-explorer.php',
        'page-templates/template-kangoo-app.php',
    );

    foreach ($tool_templates as $template) {
        if (is_page_template($template)) {
            return false;
        }
    }

    return true;
}

function kangoo_should_async_style($handle) {
    if (!is_front_page()) {
        return false;
    }

    return in_array(
        $handle,
        array(
            'kangoo-woocommerce',
            'kangoo-account-drawer',
            'kangoo-helper-mascot',
            'kangoo-mascot-helper',
        ),
        true
    );
}

function kangoo_async_style_loader_tag($html, $handle, $href, $media) {
    if (!kangoo_should_async_style($handle)) {
        return $html;
    }

    $media = $media ?: 'all';

    return sprintf(
        '<link rel="preload" id="%1$s-css" href="%2$s" as="style" media="%3$s" onload="this.onload=null;this.rel=\'stylesheet\'">' . "\n" . '<noscript>%4$s</noscript>',
        esc_attr($handle),
        esc_url($href),
        esc_attr($media),
        $html
    );
}
add_filter('style_loader_tag', 'kangoo_async_style_loader_tag', 10, 4);

function kangoo_should_prioritize_product_card_image() {
    static $home_product_card_image_prioritized = false;

    if (!is_front_page() || $home_product_card_image_prioritized) {
        return false;
    }

    $home_product_card_image_prioritized = true;

    return true;
}

function kangoo_get_product_card_thumbnail($product, $attr = array()) {
    if (!$product instanceof WC_Product) {
        return '';
    }

    $defaults = array(
        'alt'      => $product->get_name(),
        'class'    => 'attachment-woocommerce_thumbnail size-woocommerce_thumbnail',
        'decoding' => 'async',
    );

    $attr = wp_parse_args($attr, $defaults);
    $image_id = $product->get_image_id();

    if ($image_id) {
        $markup = wp_get_attachment_image($image_id, 'woocommerce_thumbnail', false, $attr);

        return kangoo_wrap_attachment_image_with_webp(
            $markup,
            $image_id,
            'woocommerce_thumbnail',
            isset($attr['sizes']) ? $attr['sizes'] : '(max-width: 640px) 44vw, 300px'
        );
    }

    return function_exists('wc_placeholder_img') ? wc_placeholder_img('woocommerce_thumbnail', $attr) : '';
}

function kangoo_enqueue_assets() {
    $theme_version = wp_get_theme()->get('Version');
    $css_uri = get_template_directory_uri() . '/assets/css/';
    $js_uri  = get_template_directory_uri() . '/assets/js/';
    $is_front_page = is_front_page();
    $is_product_page = function_exists('is_product') && is_product();
    $is_product_archive = kangoo_is_product_archive_view();
    $is_commerce_view = kangoo_is_commerce_view();
    $critical_style_handle = 'kangoo-header-footer';

    wp_enqueue_style(
        'kangoo-google-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap',
        array(),
        null
    );

    wp_enqueue_style('kangoo-base', $css_uri . 'base.css', array('kangoo-google-fonts'), $theme_version);
    wp_enqueue_style('kangoo-components', $css_uri . 'components.css', array('kangoo-base'), $theme_version);
    wp_enqueue_style('kangoo-header-footer', $css_uri . 'header-footer.css', array('kangoo-components'), $theme_version);

    if ($is_front_page) {
        wp_enqueue_style('kangoo-home', $css_uri . 'home.css', array($critical_style_handle), $theme_version);
        $critical_style_handle = 'kangoo-home';
    }

    if ($is_product_archive || $is_product_page || $is_commerce_view) {
        wp_enqueue_style('kangoo-shop', $css_uri . 'shop.css', array($critical_style_handle), $theme_version);
        $critical_style_handle = 'kangoo-shop';
    }

    if ($is_front_page) {
        wp_enqueue_style('kangoo-home-commerce', $css_uri . 'home-commerce.css', array($critical_style_handle), $theme_version);
        $critical_style_handle = 'kangoo-home-commerce';
    }

    if ($is_product_page) {
        wp_enqueue_style('kangoo-product', $css_uri . 'product.css', array($critical_style_handle), $theme_version);
        $critical_style_handle = 'kangoo-product';
    }

    if ($is_front_page || $is_commerce_view) {
        wp_enqueue_style('kangoo-woocommerce', $css_uri . 'woocommerce.css', array($critical_style_handle), $theme_version);

        if (!$is_front_page) {
            $critical_style_handle = 'kangoo-woocommerce';
        }
    }

    wp_enqueue_style('kangoo-account-drawer', $css_uri . 'account-drawer.css', array($critical_style_handle), $theme_version);

    if (function_exists('kangoo_is_event_theme_active') && kangoo_is_event_theme_active()) {
        wp_enqueue_style('kangoo-event-themes', $css_uri . 'event-themes.css', array($critical_style_handle), $theme_version);
        $critical_style_handle = 'kangoo-event-themes';
    }

    if (kangoo_is_info_page_view()) {
        wp_enqueue_style('kangoo-info-page', $css_uri . 'info-page.css', array('kangoo-header-footer'), $theme_version);
        $critical_style_handle = 'kangoo-info-page';
    }

    if (kangoo_is_blog_view()) {
        wp_enqueue_style('kangoo-blog', $css_uri . 'blog.css', array('kangoo-header-footer'), $theme_version);
        $critical_style_handle = 'kangoo-blog';
    }

    if (is_page_template('page-templates/template-pouch-finder.php')) {
        wp_enqueue_style('kangoo-pouch-finder', $css_uri . 'pouch-finder.css', array('kangoo-header-footer'), $theme_version);
        $critical_style_handle = 'kangoo-pouch-finder';
    }

    if (is_page_template('page-templates/template-pouch-comparison.php')) {
        wp_enqueue_style('kangoo-pouch-comparison', $css_uri . 'pouch-comparison.css', array('kangoo-header-footer'), $theme_version);
        $critical_style_handle = 'kangoo-pouch-comparison';
    }

    if (is_page_template('page-templates/template-build-a-box.php')) {
        wp_enqueue_style('kangoo-build-a-box', $css_uri . 'build-a-box.css', array('kangoo-header-footer'), $theme_version);
        $critical_style_handle = 'kangoo-build-a-box';
    }

    if (is_page_template('page-templates/template-strength-ladder.php')) {
        wp_enqueue_style('kangoo-strength-ladder', $css_uri . 'strength-ladder.css', array('kangoo-header-footer'), $theme_version);
        $critical_style_handle = 'kangoo-strength-ladder';
    }

    if (is_page_template('page-templates/template-flavour-explorer.php')) {
        wp_enqueue_style('kangoo-flavour-explorer', $css_uri . 'flavour-explorer.css', array('kangoo-header-footer'), $theme_version);
        $critical_style_handle = 'kangoo-flavour-explorer';
    }

    if (is_page_template('page-templates/template-kangoo-app.php')) {
        wp_enqueue_style('kangoo-app-page', $css_uri . 'kangoo-app.css', array('kangoo-header-footer'), $theme_version);
        $critical_style_handle = 'kangoo-app-page';
    }

    if (function_exists('kangoo_is_light_theme_active') && kangoo_is_light_theme_active()) {
        wp_enqueue_style('kangoo-theme-light', $css_uri . 'theme-light.css', array($critical_style_handle), $theme_version);
        $critical_style_handle = 'kangoo-theme-light';
    }

    if (is_page('referral-program')) {
        wp_enqueue_style(
            'kangoo-referral-program',
            $css_uri . 'referral-program.css',
            array($critical_style_handle),
            $theme_version
        );
    }

    wp_enqueue_script(
        'kangoo-main',
        $js_uri . 'main.js',
        array(),
        $theme_version,
        true
    );

    wp_add_inline_script(
        'kangoo-main',
        "document.addEventListener('DOMContentLoaded',function(){document.querySelectorAll('a[aria-label=\"Open the pouch finder\"]').forEach(function(link){if((link.textContent||'').trim().indexOf('Find my pouch')!==-1){link.setAttribute('aria-label','Find my pouch');}});});",
        'after'
    );

    wp_localize_script('kangoo-main', 'kangooRewards', array(
        'ajax_url'        => admin_url('admin-ajax.php'),
        'ajax_nonce'      => wp_create_nonce('kangoo_rewards_ajax'),
        'url_coupon_nonce' => wp_create_nonce('kangoo_url_coupon'),
        'store_api_url'   => esc_url_raw(rest_url('wc/store/v1/cart')),
        'store_api_nonce' => wp_create_nonce('wc_store_api'),
        'coupon_code'     => kangoo_rewards_coupon_code(),
        'active_points'   => function_exists('kangoo_rewards_get_session_points') ? kangoo_rewards_get_session_points() : 0,
        'active_discount' => function_exists('kangoo_rewards_get_session_points') ? kangoo_rewards_points_to_money(kangoo_rewards_get_session_points()) : 0,
        'active_discount_html' => function_exists('kangoo_rewards_get_session_points') ? kangoo_plain_wc_price(kangoo_rewards_points_to_money(kangoo_rewards_get_session_points())) : '',
        'free_shipping_threshold' => kangoo_get_active_free_shipping_threshold(),
        'standard_free_shipping_threshold' => kangoo_free_shipping_threshold(),
        'first_order_free_shipping_threshold' => kangoo_first_order_free_shipping_threshold(),
        'first_order_shipping_coupon_code' => kangoo_first_order_shipping_coupon_code(),
        'first_order_free_shipping_active' => kangoo_is_first_order_free_shipping_offer_active(),
        'checkout_email_is_existing_customer' => kangoo_saved_checkout_email_is_existing_customer(),
        'checkout_email'  => function_exists('kangoo_get_saved_checkout_email') ? kangoo_get_saved_checkout_email() : '',
        'checkout_dob'    => function_exists('kangoo_get_saved_checkout_dob') ? kangoo_get_saved_checkout_dob() : '',
    ));

    wp_localize_script('kangoo-main', 'kangooSearch', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('kangoo_ajax_search'),
    ));

    wp_enqueue_script(
        'kangoo-ajax-cart',
        $js_uri . 'ajax-cart.js',
        array('jquery'),
        $theme_version,
        true
    );

    wp_enqueue_script(
        'kangoo-account-drawer',
        $js_uri . 'account-drawer.js',
        array('jquery'),
        $theme_version,
        true
    );

    if (is_page_template('page-templates/template-pouch-finder.php')) {
        wp_enqueue_script(
            'kangoo-pouch-finder',
            $js_uri . 'pouch-finder.js',
            array(),
            $theme_version,
            true
        );
    }

    if (is_page_template('page-templates/template-pouch-comparison.php')) {
        wp_enqueue_script(
            'kangoo-pouch-comparison',
            $js_uri . 'pouch-comparison.js',
            array(),
            $theme_version,
            true
        );
    }

    if (is_page_template('page-templates/template-build-a-box.php')) {
        wp_enqueue_script(
            'kangoo-build-a-box',
            $js_uri . 'build-a-box.js',
            array('jquery'),
            $theme_version,
            true
        );
    }

    wp_localize_script('kangoo-ajax-cart', 'kangooAjaxCart', array(
        'ajax_url'          => admin_url('admin-ajax.php'),
        'nonce'             => wp_create_nonce('kangoo_update_mini_cart_qty'),
        'add_to_cart_nonce' => wp_create_nonce('kangoo_ajax_add_to_cart'),
        'remove_nonce'      => wp_create_nonce('kangoo_remove_mini_cart_item'),
        'clear_nonce'       => wp_create_nonce('kangoo_clear_cart'),
        'cart_url'          => function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/'),
    ));

    wp_localize_script('kangoo-account-drawer', 'kangooAccount', array(
        'ajax_url'        => admin_url('admin-ajax.php'),
        'login_nonce'     => wp_create_nonce('kangoo_account_login'),
        'register_nonce'  => wp_create_nonce('kangoo_account_register'),
        'logout_nonce'    => wp_create_nonce('kangoo_account_logout'),
        'status_nonce'    => wp_create_nonce('kangoo_account_status'),
        'is_logged_in'    => is_user_logged_in(),
        'account_url'     => function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/'),
        'login_title'     => __('Do you have an account with us?', 'kangoo'),
        'login_success'   => __('Signed in successfully.', 'kangoo'),
        'register_success'=> __('Account created successfully.', 'kangoo'),
    ));

    if (function_exists('is_product') && is_product()) {
        wp_enqueue_script('wc-add-to-cart-variation');
        wp_enqueue_script('wc-cart-fragments');
        wp_enqueue_script(
            'kangoo-product-reviews',
            $js_uri . 'product-reviews.js',
            array(),
            $theme_version,
            true
        );
        wp_localize_script('kangoo-product-reviews', 'kangooProductReviews', array(
            'rest_url' => esc_url_raw(rest_url('kangoo-app/v1')),
        ));
    }
}
add_action('wp_enqueue_scripts', 'kangoo_enqueue_assets');

function kangoo_checkout_county_field_required($fields) {
    foreach (array('billing', 'shipping') as $section) {
        $key = $section . '_state';

        if (isset($fields[$section][$key])) {
            $fields[$section][$key]['required'] = true;
            $fields[$section][$key]['label'] = __('County', 'kangoo');
            $fields[$section][$key]['placeholder'] = __('County', 'kangoo');
        }
    }

    return $fields;
}
add_filter('woocommerce_checkout_fields', 'kangoo_checkout_county_field_required', 20);

function kangoo_checkout_age_verification_html() {
    if (!WC()->cart || WC()->cart->is_empty()) {
        return;
    }

    $posted_dob = isset($_POST['kangoo_age_dob']) ? wc_clean(wp_unslash($_POST['kangoo_age_dob'])) : '';
    $saved_dob = function_exists('kangoo_get_saved_checkout_dob') ? kangoo_get_saved_checkout_dob() : '';
    $dob = $posted_dob ? $posted_dob : $saved_dob;

    if ($dob && kangoo_calculate_age_from_date($dob) >= 18) {
        ?>
        <input type="hidden" name="kangoo_age_dob" id="kangoo_age_dob" value="<?php echo esc_attr($dob); ?>">
        <input type="hidden" name="kangoo_age_confirm" id="kangoo_age_confirm" value="1">
        <?php
        return;
    }
    ?>
    <div class="kangoo-checkout-age" id="kangoo-checkout-age">
        <div class="kangoo-checkout-age__header">
            <span class="kangoo-checkout-age__badge"><?php esc_html_e('18+', 'kangoo'); ?></span>
            <div>
                <h3><?php esc_html_e('Age verification', 'kangoo'); ?></h3>
                <p><?php esc_html_e('Nicotine products can only be purchased by customers aged 18 or over. Please confirm your date of birth and be ready to provide valid photo ID if requested.', 'kangoo'); ?></p>
            </div>
        </div>

        <p class="form-row form-row-wide kangoo-checkout-age__field">
            <label for="kangoo_age_dob"><?php esc_html_e('Date of birth', 'kangoo'); ?> <abbr class="required" title="<?php esc_attr_e('required', 'kangoo'); ?>">*</abbr></label>
            <input
                type="date"
                class="input-text"
                name="kangoo_age_dob"
                id="kangoo_age_dob"
                value="<?php echo esc_attr($dob); ?>"
                max="<?php echo esc_attr(gmdate('Y-m-d', strtotime('-18 years'))); ?>"
                required
            >
        </p>

        <p class="form-row form-row-wide kangoo-checkout-age__confirm">
            <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
                <input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="kangoo_age_confirm" id="kangoo_age_confirm" value="1" <?php checked(!empty($_POST['kangoo_age_confirm'])); ?>>
                <span><?php esc_html_e('I confirm I am 18 or over and the details I have provided are accurate. I understand I may be asked to provide valid photo ID before my order is completed or delivered.', 'kangoo'); ?></span>
            </label>
        </p>
    </div>
    <?php
}
add_action('woocommerce_review_order_before_submit', 'kangoo_checkout_age_verification_html', 8);

function kangoo_calculate_age_from_date($date) {
    $date = trim((string) $date);
    $normalized_date = $date;

    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $date, $matches)) {
        $normalized_date = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $normalized_date)) {
        return null;
    }

    try {
        $dob = new DateTimeImmutable($normalized_date);
        $today = new DateTimeImmutable('today');
    } catch (Exception $exception) {
        return null;
    }

    if ($dob > $today) {
        return null;
    }

    return (int) $dob->diff($today)->y;
}

function kangoo_normalize_dob_parts($day, $month, $year) {
    $day = preg_replace('/\D+/', '', (string) $day);
    $month = preg_replace('/\D+/', '', (string) $month);
    $year = preg_replace('/\D+/', '', (string) $year);

    if ($day === '' || $month === '' || $year === '') {
        return '';
    }

    $day = str_pad($day, 2, '0', STR_PAD_LEFT);
    $month = str_pad($month, 2, '0', STR_PAD_LEFT);

    if (strlen($year) === 2) {
        $year = ((int) $year > (int) gmdate('y')) ? '19' . $year : '20' . $year;
    }

    if (strlen($year) !== 4 || !checkdate((int) $month, (int) $day, (int) $year)) {
        return '';
    }

    return $year . '-' . $month . '-' . $day;
}

function kangoo_checkout_number_options($start, $end, $pad = 0, $descending = false) {
    $numbers = range((int) $start, (int) $end);

    if ($descending) {
        $numbers = array_reverse($numbers);
    }

    $options = array();

    foreach ($numbers as $number) {
        $value = $pad > 0 ? str_pad((string) $number, $pad, '0', STR_PAD_LEFT) : (string) $number;

        $options[] = array(
            'value' => $value,
            'label' => $value,
        );
    }

    return $options;
}

function kangoo_validate_checkout_age_verification() {
    $dob = isset($_POST['kangoo_age_dob']) ? wc_clean(wp_unslash($_POST['kangoo_age_dob'])) : '';
    $saved_dob = function_exists('kangoo_get_saved_checkout_dob') ? kangoo_get_saved_checkout_dob() : '';
    $dob = $dob ? $dob : $saved_dob;
    $age = kangoo_calculate_age_from_date($dob);

    if ($dob === '' || $age === null) {
        wc_add_notice(__('Please enter a valid date of birth for age verification.', 'kangoo'), 'error');
        return;
    }

    if ($age < 18) {
        wc_add_notice(__('You must be 18 or over to place an order.', 'kangoo'), 'error');
    }

    if (empty($_POST['kangoo_age_confirm']) && !$saved_dob) {
        wc_add_notice(__('Please confirm that you are 18 or over and understand photo ID may be required.', 'kangoo'), 'error');
    }
}
add_action('woocommerce_checkout_process', 'kangoo_validate_checkout_age_verification');

function kangoo_get_posted_checkout_age_verification() {
    $dob = isset($_POST['kangoo_age_dob']) ? wc_clean(wp_unslash($_POST['kangoo_age_dob'])) : '';
    $saved_dob = function_exists('kangoo_get_saved_checkout_dob') ? kangoo_get_saved_checkout_dob() : '';
    $dob = $dob ? $dob : $saved_dob;
    $age = kangoo_calculate_age_from_date($dob);

    return array(
        'dob'       => $dob,
        'age'       => $age,
        'confirmed' => (!empty($_POST['kangoo_age_confirm']) || $saved_dob) ? 'yes' : 'no',
    );
}

function kangoo_save_checkout_age_verification($order, $data = array()) {
    if (!$order instanceof WC_Order) {
        return;
    }

    $age_verification = kangoo_get_posted_checkout_age_verification();

    if ($age_verification['dob'] !== '' && $age_verification['age'] !== null) {
        $order->update_meta_data('_kangoo_age_verified_dob', $age_verification['dob']);
        $order->update_meta_data('_kangoo_age_verified_age_at_order', $age_verification['age']);
        $order->update_meta_data('_kangoo_age_verification_confirmed', $age_verification['confirmed']);

        if (is_user_logged_in()) {
            update_user_meta(get_current_user_id(), 'kangoo_date_of_birth', $age_verification['dob']);
        }
    }
}
add_action('woocommerce_checkout_create_order', 'kangoo_save_checkout_age_verification', 20, 2);

function kangoo_admin_order_age_verification_meta($order) {
    $dob = $order instanceof WC_Order ? $order->get_meta('_kangoo_age_verified_dob') : '';
    $age = $order instanceof WC_Order ? $order->get_meta('_kangoo_age_verified_age_at_order') : '';
    $confirmed = $order instanceof WC_Order ? $order->get_meta('_kangoo_age_verification_confirmed') : '';

    if (!$dob && $order instanceof WC_Order) {
        $dob = $order->get_meta('_wc_other/kangoo/age-dob');
        $age = kangoo_calculate_age_from_date((string) $dob);
    }

    if (!$dob && $order instanceof WC_Order) {
        $dob = kangoo_normalize_dob_parts(
            $order->get_meta('_wc_other/kangoo/age-day'),
            $order->get_meta('_wc_other/kangoo/age-month'),
            $order->get_meta('_wc_other/kangoo/age-year')
        );
        $age = $dob ? kangoo_calculate_age_from_date((string) $dob) : '';
    }

    if (!$confirmed && $order instanceof WC_Order) {
        $block_confirmed = $order->get_meta('_wc_other/kangoo/age-confirm');
        $confirmed = in_array($block_confirmed, array('1', 1, true, 'yes'), true) ? 'yes' : '';
    }

    if (!$dob && !$confirmed) {
        return;
    }
    ?>
    <div class="kangoo-admin-age-verification">
        <h3><?php esc_html_e('Age verification', 'kangoo'); ?></h3>
        <?php if ($dob) : ?>
            <p><strong><?php esc_html_e('Date of birth:', 'kangoo'); ?></strong> <?php echo esc_html($dob); ?></p>
        <?php endif; ?>
        <?php if ($age !== '') : ?>
            <p><strong><?php esc_html_e('Age at order:', 'kangoo'); ?></strong> <?php echo esc_html($age); ?></p>
        <?php endif; ?>
        <p><strong><?php esc_html_e('18+ / photo ID confirmation:', 'kangoo'); ?></strong> <?php echo esc_html($confirmed === 'yes' ? __('Confirmed', 'kangoo') : __('Not confirmed', 'kangoo')); ?></p>
    </div>
    <?php
}
add_action('woocommerce_admin_order_data_after_billing_address', 'kangoo_admin_order_age_verification_meta');

function kangoo_register_checkout_block_age_verification_fields() {
    if (!function_exists('woocommerce_register_additional_checkout_field')) {
        return;
    }

    woocommerce_register_additional_checkout_field(array(
        'id'            => 'kangoo/age-day',
        'label'         => __('DD', 'kangoo'),
        'optionalLabel' => __('DD', 'kangoo'),
        'location'      => 'contact',
        'type'          => 'text',
        'required'      => false,
        'attributes'    => array(
            'autocomplete' => 'bday-day',
            'pattern'      => '\d{1,2}',
            'title'        => __('Enter the day you were born.', 'kangoo'),
            'maxLength'    => 2,
        ),
        'sanitize_callback' => static function ($field_value) {
            return preg_replace('/\D+/', '', sanitize_text_field((string) $field_value));
        },
    ));

    woocommerce_register_additional_checkout_field(array(
        'id'            => 'kangoo/age-month',
        'label'         => __('MM', 'kangoo'),
        'optionalLabel' => __('MM', 'kangoo'),
        'location'      => 'contact',
        'type'          => 'text',
        'required'      => false,
        'attributes'    => array(
            'autocomplete' => 'bday-month',
            'pattern'      => '\d{1,2}',
            'title'        => __('Enter the month you were born.', 'kangoo'),
            'maxLength'    => 2,
        ),
        'sanitize_callback' => static function ($field_value) {
            return preg_replace('/\D+/', '', sanitize_text_field((string) $field_value));
        },
    ));

    woocommerce_register_additional_checkout_field(array(
        'id'            => 'kangoo/age-year',
        'label'         => __('YYYY', 'kangoo'),
        'optionalLabel' => __('YYYY', 'kangoo'),
        'location'      => 'contact',
        'type'          => 'text',
        'required'      => false,
        'attributes'    => array(
            'autocomplete' => 'bday-year',
            'pattern'      => '\d{2,4}',
            'title'        => __('Enter the year you were born.', 'kangoo'),
            'maxLength'    => 4,
        ),
        'sanitize_callback' => static function ($field_value) {
            return preg_replace('/\D+/', '', sanitize_text_field((string) $field_value));
        },
    ));
}
add_action('woocommerce_init', 'kangoo_register_checkout_block_age_verification_fields');

function kangoo_validate_checkout_block_age_fields($errors, $fields, $group) {
    $day = isset($fields['kangoo/age-day']) ? $fields['kangoo/age-day'] : '';
    $month = isset($fields['kangoo/age-month']) ? $fields['kangoo/age-month'] : '';
    $year = isset($fields['kangoo/age-year']) ? $fields['kangoo/age-year'] : '';
    $dob = kangoo_normalize_dob_parts($day, $month, $year);
    $dob = $dob ? $dob : (function_exists('kangoo_get_saved_checkout_dob') ? kangoo_get_saved_checkout_dob() : '');
    $age = $dob ? kangoo_calculate_age_from_date($dob) : null;

    if (!$dob || $age === null) {
        $errors->add('kangoo_invalid_age_dob', __('Please enter a valid date of birth.', 'kangoo'));
        return;
    }

    if ($age < 18) {
        $errors->add('kangoo_underage_checkout', __('You must be 18 or over to place an order.', 'kangoo'));
    }
}
add_action('woocommerce_blocks_validate_location_other_fields', 'kangoo_validate_checkout_block_age_fields', 10, 3);
add_action('woocommerce_blocks_validate_location_contact_fields', 'kangoo_validate_checkout_block_age_fields', 10, 3);

function kangoo_email_logo_url() {
    return function_exists('kangoo_email_theme_logo_url')
        ? kangoo_email_theme_logo_url()
        : 'https://kangoopouches.co.uk/wp-content/uploads/2026/05/kangoo-logo-black.png';
}

function kangoo_email_header_image($image) {
    return kangoo_email_logo_url();
}
add_filter('woocommerce_email_header_image', 'kangoo_email_header_image');
add_filter('option_woocommerce_email_header_image', 'kangoo_email_header_image');

function kangoo_email_from_name($from_name) {
    return 'Kangoo Pouches';
}
add_filter('woocommerce_email_from_name', 'kangoo_email_from_name');

function kangoo_email_colours($value, $option) {
    $colours = array(
        'woocommerce_email_base_color'       => '#ff7a00',
        'woocommerce_email_background_color' => '#f5f7fb',
        'woocommerce_email_body_background_color' => '#ffffff',
        'woocommerce_email_text_color'       => '#111827',
    );

    return isset($colours[$option]) ? $colours[$option] : $value;
}
add_filter('pre_option_woocommerce_email_base_color', function ($value) {
    return kangoo_email_colours($value, 'woocommerce_email_base_color');
});
add_filter('pre_option_woocommerce_email_background_color', function ($value) {
    return kangoo_email_colours($value, 'woocommerce_email_background_color');
});
add_filter('pre_option_woocommerce_email_body_background_color', function ($value) {
    return kangoo_email_colours($value, 'woocommerce_email_body_background_color');
});
add_filter('pre_option_woocommerce_email_text_color', function ($value) {
    return kangoo_email_colours($value, 'woocommerce_email_text_color');
});

function kangoo_email_footer_text($footer_text) {
    return 'Kangoo Pouches<br>Fast UK delivery, discreet packaging, and helpful support.<br><a href="https://kangoopouches.co.uk">kangoopouches.co.uk</a>';
}
add_filter('woocommerce_email_footer_text', 'kangoo_email_footer_text');

function kangoo_email_order_items_args($args) {
    $args['show_image'] = true;
    $args['image_size'] = array(64, 64);

    return $args;
}
add_filter('woocommerce_email_order_items_args', 'kangoo_email_order_items_args');

function kangoo_woocommerce_email_styles($css, $email = null) {
    $kangoo_css = '
        body,
        #wrapper {
            background-color: #f5f7fb !important;
        }

        #wrapper {
            padding: 28px 12px !important;
        }

        #template_container {
            max-width: 640px !important;
            width: 100% !important;
            border: 1px solid #e5e7eb !important;
            border-radius: 18px !important;
            overflow: hidden !important;
            box-shadow: none !important;
        }

        #template_header {
            background-color: #ffffff !important;
            border-bottom: 4px solid #ff7a00 !important;
        }

        #template_header_image {
            padding: 28px 32px 24px !important;
            text-align: center !important;
        }

        #template_header_image img {
            max-width: 240px !important;
            width: 100% !important;
            height: auto !important;
        }

        #template_header h1 {
            color: #111827 !important;
            font-family: Arial, Helvetica, sans-serif !important;
            font-size: 28px !important;
            line-height: 1.2 !important;
            font-weight: 800 !important;
            display: none !important;
            max-height: 0 !important;
            overflow: hidden !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        #body_content {
            width: 100% !important;
        }

        #template_body,
        #body_content,
        #body_content_inner {
            background-color: #ffffff !important;
        }

        #body_content_inner {
            color: #111827 !important;
            font-family: Arial, Helvetica, sans-serif !important;
            font-size: 15px !important;
            line-height: 1.65 !important;
        }

        #body_content_inner p {
            color: #374151 !important;
            margin: 0 0 16px !important;
        }

        #body_content_inner table {
            width: 100% !important;
        }

        #body_content_inner h2,
        #body_content_inner h3 {
            color: #111827 !important;
            font-weight: 800 !important;
        }

        #body_content_inner a {
            color: #2563eb !important;
            font-weight: 700 !important;
        }

        .td,
        .address {
            border-color: #e5e7eb !important;
            color: #111827 !important;
        }

        table.order_details,
        table.td {
            border-color: #e5e7eb !important;
            border-radius: 12px !important;
            overflow: hidden !important;
        }

        table.order_details th,
        table.order_details td,
        table.td th,
        table.td td {
            color: #111827 !important;
            border-color: #e5e7eb !important;
            padding: 12px !important;
        }

        table.order_details th,
        table.td th {
            background-color: #f9fafb !important;
            font-weight: 800 !important;
        }

        table.order_details td,
        table.td td {
            vertical-align: top !important;
        }

        table.order_details img,
        table.td img {
            border-radius: 10px !important;
            border: 1px solid #e5e7eb !important;
            background: #ffffff !important;
            max-width: 64px !important;
            height: auto !important;
            display: inline-block !important;
        }

        .address {
            line-height: 1.55 !important;
        }

        #addresses td {
            color: #111827 !important;
            vertical-align: top !important;
        }

        .button,
        a.button {
            background-color: #4da3ff !important;
            border-radius: 999px !important;
            color: #ffffff !important;
            font-weight: 800 !important;
            text-decoration: none !important;
        }

        #template_footer {
            background-color: #f5f7fb !important;
        }

        #template_footer #credit {
            color: #6b7280 !important;
            font-size: 12px !important;
            line-height: 1.6 !important;
        }

        #template_footer #credit a {
            color: #2563eb !important;
        }

        @media only screen and (max-width: 640px) {
            #wrapper {
                padding: 0 !important;
            }

            #template_container {
                border-left: 0 !important;
                border-right: 0 !important;
                border-radius: 0 !important;
            }

            #template_header_image {
                padding: 22px 20px 8px !important;
                text-align: center !important;
            }

            #template_header_image img {
                max-width: 220px !important;
            }

            #template_header h1 {
                font-size: 24px !important;
                padding: 0 !important;
                text-align: left !important;
            }

            #body_content_inner {
                font-size: 14px !important;
                padding: 22px 20px !important;
            }

            table.order_details th,
            table.order_details td,
            table.td th,
            table.td td {
                padding: 10px 8px !important;
                font-size: 13px !important;
            }

            table.order_details img,
            table.td img {
                max-width: 52px !important;
            }

            #addresses td {
                display: block !important;
                width: 100% !important;
                padding-right: 0 !important;
                padding-left: 0 !important;
            }
        }
    ';

    return $css . $kangoo_css;
}
add_filter('woocommerce_email_styles', 'kangoo_woocommerce_email_styles', 20, 2);

function kangoo_no_cache_cart_pages() {
    if (
        (function_exists('is_cart') && is_cart())
        || (function_exists('is_checkout') && is_checkout())
        || (function_exists('is_account_page') && is_account_page())
    ) {
        nocache_headers();

        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }
    }
}
add_action('template_redirect', 'kangoo_no_cache_cart_pages', 0);

/* =========================================================================
BLOG
========================================================================= */

function kangoo_register_blog_post_type() {
    $labels = array(
        'name'                  => __('Blog', 'kangoo'),
        'singular_name'         => __('Blog Article', 'kangoo'),
        'menu_name'             => __('Blog', 'kangoo'),
        'name_admin_bar'        => __('Blog Article', 'kangoo'),
        'add_new'               => __('Add New', 'kangoo'),
        'add_new_item'          => __('Add New Article', 'kangoo'),
        'new_item'              => __('New Article', 'kangoo'),
        'edit_item'             => __('Edit Article', 'kangoo'),
        'view_item'             => __('View Article', 'kangoo'),
        'all_items'             => __('All Articles', 'kangoo'),
        'search_items'          => __('Search Articles', 'kangoo'),
        'not_found'             => __('No blog articles found.', 'kangoo'),
        'not_found_in_trash'    => __('No blog articles found in Trash.', 'kangoo'),
        'featured_image'        => __('Article image', 'kangoo'),
        'set_featured_image'    => __('Set article image', 'kangoo'),
        'remove_featured_image' => __('Remove article image', 'kangoo'),
    );

    register_post_type('kangoo_blog', array(
        'labels'              => $labels,
        'public'              => true,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_rest'        => true,
        'menu_icon'           => 'dashicons-welcome-write-blog',
        'has_archive'         => 'blog',
        'rewrite'             => array(
            'slug'       => 'blog',
            'with_front' => false,
        ),
        'supports'            => array('title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions'),
        'taxonomies'          => array('blog_topic'),
        'hierarchical'        => false,
        'query_var'           => true,
        'capability_type'     => 'post',
        'map_meta_cap'        => true,
        'menu_position'       => 5,
    ));
}
add_action('init', 'kangoo_register_blog_post_type');

function kangoo_register_blog_taxonomy() {
    register_taxonomy('blog_topic', array('kangoo_blog'), array(
        'labels' => array(
            'name'              => __('Blog Topics', 'kangoo'),
            'singular_name'     => __('Blog Topic', 'kangoo'),
            'search_items'      => __('Search Blog Topics', 'kangoo'),
            'all_items'         => __('All Blog Topics', 'kangoo'),
            'edit_item'         => __('Edit Blog Topic', 'kangoo'),
            'update_item'       => __('Update Blog Topic', 'kangoo'),
            'add_new_item'      => __('Add New Blog Topic', 'kangoo'),
            'new_item_name'     => __('New Blog Topic Name', 'kangoo'),
            'menu_name'         => __('Topics', 'kangoo'),
        ),
        'hierarchical'      => true,
        'public'            => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'query_var'         => true,
        'rewrite'           => array(
            'slug'       => 'blog-topic',
            'with_front' => false,
        ),
    ));
}
add_action('init', 'kangoo_register_blog_taxonomy');

function kangoo_flush_rewrite_rules_on_theme_switch() {
    kangoo_register_blog_post_type();
    kangoo_register_blog_taxonomy();
    kangoo_rewards_add_account_endpoint();
    kangoo_referrals_add_account_endpoint();
    kangoo_add_attribute_landing_rewrites();
    flush_rewrite_rules();
    update_option('kangoo_blog_rewrite_version', '1');
    update_option('kangoo_rewards_rewrite_version', '1');
    update_option('kangoo_referrals_rewrite_version', '1');
    update_option('kangoo_attribute_rewrite_version', '1');
}
add_action('after_switch_theme', 'kangoo_flush_rewrite_rules_on_theme_switch');

function kangoo_maybe_flush_blog_rewrite_rules() {
    if (
        get_option('kangoo_blog_rewrite_version') === '1'
        && get_option('kangoo_rewards_rewrite_version') === '1'
        && get_option('kangoo_referrals_rewrite_version') === '1'
        && get_option('kangoo_attribute_rewrite_version') === '1'
    ) {
        return;
    }

    kangoo_register_blog_post_type();
    kangoo_register_blog_taxonomy();
    kangoo_rewards_add_account_endpoint();
    kangoo_referrals_add_account_endpoint();
    kangoo_add_attribute_landing_rewrites();
    flush_rewrite_rules();
    update_option('kangoo_blog_rewrite_version', '1');
    update_option('kangoo_rewards_rewrite_version', '1');
    update_option('kangoo_referrals_rewrite_version', '1');
    update_option('kangoo_attribute_rewrite_version', '1');
}
add_action('admin_init', 'kangoo_maybe_flush_blog_rewrite_rules');

function kangoo_register_blog_acf_fields() {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group(array(
        'key' => 'group_kangoo_blog_article',
        'title' => __('Blog Article SEO', 'kangoo'),
        'fields' => array(
            array(
                'key' => 'field_kangoo_blog_eyebrow',
                'label' => __('Eyebrow Label', 'kangoo'),
                'name' => 'blog_eyebrow',
                'type' => 'text',
                'instructions' => __('Short category-style label shown above the article title.', 'kangoo'),
                'maxlength' => 60,
            ),
            array(
                'key' => 'field_kangoo_blog_standfirst',
                'label' => __('Standfirst', 'kangoo'),
                'name' => 'blog_standfirst',
                'type' => 'textarea',
                'instructions' => __('A concise introduction used on the article page and cards. Keep it useful for search snippets.', 'kangoo'),
                'rows' => 3,
                'maxlength' => 220,
            ),
            array(
                'key' => 'field_kangoo_blog_read_time',
                'label' => __('Read Time', 'kangoo'),
                'name' => 'blog_read_time',
                'type' => 'number',
                'instructions' => __('Estimated reading time in minutes.', 'kangoo'),
                'min' => 1,
                'max' => 60,
                'append' => __('min', 'kangoo'),
            ),
            array(
                'key' => 'field_kangoo_blog_seo_title',
                'label' => __('SEO Title', 'kangoo'),
                'name' => 'blog_seo_title',
                'type' => 'text',
                'instructions' => __('Optional title for search engines and social previews. Aim for 50-60 characters.', 'kangoo'),
                'maxlength' => 70,
            ),
            array(
                'key' => 'field_kangoo_blog_meta_description',
                'label' => __('Meta Description', 'kangoo'),
                'name' => 'blog_meta_description',
                'type' => 'textarea',
                'instructions' => __('Optional meta description. Aim for 140-160 characters.', 'kangoo'),
                'rows' => 3,
                'maxlength' => 180,
            ),
            array(
                'key' => 'field_kangoo_blog_featured_product',
                'label' => __('Featured Product', 'kangoo'),
                'name' => 'blog_featured_product',
                'type' => 'post_object',
                'instructions' => __('Optional WooCommerce product to promote inside the article.', 'kangoo'),
                'post_type' => array('product'),
                'return_format' => 'object',
                'ui' => 1,
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'kangoo_blog',
                ),
            ),
        ),
        'position' => 'acf_after_title',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'active' => true,
        'show_in_rest' => 1,
    ));
}
add_action('acf/init', 'kangoo_register_blog_acf_fields');

if (is_admin()) {
    $kangoo_blog_guide_seeder = get_template_directory() . '/inc/blog-guide-seeder.php';

    if (file_exists($kangoo_blog_guide_seeder)) {
        require_once $kangoo_blog_guide_seeder;
    }
}

function kangoo_register_pack_pricing_acf_fields() {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group(array(
        'key' => 'group_kangoo_pack_pricing',
        'title' => __('Pack Pricing', 'kangoo'),
        'fields' => array(
            array(
                'key' => 'field_kangoo_pack_pricing_enabled',
                'label' => __('Enable Pack Pricing', 'kangoo'),
                'name' => 'pack_pricing_enabled',
                'type' => 'true_false',
                'ui' => 1,
                'default_value' => 0,
            ),
            array(
                'key' => 'field_kangoo_pack_pricing_tiers',
                'label' => __('Pack Price Tiers', 'kangoo'),
                'name' => 'pack_pricing_tiers',
                'type' => 'repeater',
                'instructions' => __('Add quantity-based pack prices. Pack price is the total price for that quantity, not the unit price.', 'kangoo'),
                'layout' => 'table',
                'button_label' => __('Add Pack Tier', 'kangoo'),
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_kangoo_pack_pricing_enabled',
                            'operator' => '==',
                            'value' => '1',
                        ),
                    ),
                ),
                'sub_fields' => array(
                    array(
                        'key' => 'field_kangoo_pack_tier_quantity',
                        'label' => __('Quantity', 'kangoo'),
                        'name' => 'quantity',
                        'type' => 'number',
                        'min' => 1,
                        'step' => 1,
                        'required' => 1,
                    ),
                    array(
                        'key' => 'field_kangoo_pack_tier_price',
                        'label' => __('Pack Price', 'kangoo'),
                        'name' => 'pack_price',
                        'type' => 'number',
                        'min' => 0,
                        'step' => '0.01',
                        'prepend' => '£',
                        'required' => 1,
                    ),
                    array(
                        'key' => 'field_kangoo_pack_tier_badge',
                        'label' => __('Badge', 'kangoo'),
                        'name' => 'badge',
                        'type' => 'text',
                        'placeholder' => __('Best value', 'kangoo'),
                    ),
                    array(
                        'key' => 'field_kangoo_pack_tier_default',
                        'label' => __('Default', 'kangoo'),
                        'name' => 'default_selected',
                        'type' => 'true_false',
                        'ui' => 1,
                    ),
                ),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'product',
                ),
            ),
        ),
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'active' => true,
        'show_in_rest' => 1,
    ));
}
add_action('acf/init', 'kangoo_register_pack_pricing_acf_fields');

function kangoo_normalize_product_badge($badge) {
    $badge = strtolower(trim((string) $badge));
    $badge = str_replace(array('-', ' '), '_', $badge);

    if ($badge === 'auto') {
        return 'automatic';
    }

    if ($badge === 'best') {
        return 'best_seller';
    }

    if ($badge === 'limited') {
        return 'limited_edition';
    }

    return $badge;
}

function kangoo_add_automatic_product_badge_choice($field) {
    if (!is_array($field) || !isset($field['type']) || $field['type'] !== 'select') {
        return $field;
    }

    $choices = isset($field['choices']) && is_array($field['choices']) ? $field['choices'] : array();
    $choice_values = array_map('kangoo_normalize_product_badge', array_keys($choices));

    if (!in_array('best_seller', $choice_values, true) || !in_array('new', $choice_values, true)) {
        return $field;
    }

    if (!in_array('automatic', $choice_values, true)) {
        $automatic_choice = array('automatic' => __('Automatic', 'kangoo'));
        $field['choices'] = $automatic_choice + $choices;
    }

    if (empty($field['default_value']) || kangoo_normalize_product_badge($field['default_value']) === 'none') {
        $field['default_value'] = isset($choices['auto']) ? 'auto' : 'automatic';
    }

    return $field;
}
add_filter('acf/load_field/name=badge', 'kangoo_add_automatic_product_badge_choice');

function kangoo_product_is_new($product_id, $days = 7) {
    $product_id = absint($product_id);

    if (!$product_id) {
        return false;
    }

    $published = get_post_time('U', true, $product_id);

    if (!$published) {
        return false;
    }

    return $published >= strtotime('-' . absint($days) . ' days', current_time('timestamp', true));
}

function kangoo_product_is_auto_best_seller($product_id) {
    static $best_seller_ids = null;

    $product_id = absint($product_id);

    if (!$product_id) {
        return false;
    }

    $sales = (int) get_post_meta($product_id, 'total_sales', true);

    if ($sales < 3) {
        return false;
    }

    if ($best_seller_ids === null) {
        $best_seller_ids = get_posts(array(
            'post_type'              => 'product',
            'post_status'            => 'publish',
            'posts_per_page'         => 3,
            'fields'                 => 'ids',
            'meta_key'               => 'total_sales',
            'orderby'                => 'meta_value_num',
            'order'                  => 'DESC',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'meta_query'             => array(
                array(
                    'key'     => 'total_sales',
                    'value'   => 3,
                    'compare' => '>=',
                    'type'    => 'NUMERIC',
                ),
            ),
        ));
    }

    return in_array($product_id, array_map('absint', $best_seller_ids), true);
}

function kangoo_get_product_badge($product_id = null) {
    $product_id = $product_id ? absint($product_id) : get_the_ID();

    if (!$product_id) {
        return null;
    }

    $manual_badge = function_exists('get_field') ? kangoo_normalize_product_badge(get_field('badge', $product_id)) : '';

    if ($manual_badge === 'automatic') {
        $manual_badge = '';
    }

    if ($manual_badge === 'none') {
        return null;
    }

    $forced_badges = array(
        'limited_edition' => __('Limited Edition', 'kangoo'),
        'best_seller'     => __('Best Seller', 'kangoo'),
        'new'             => __('New', 'kangoo'),
        'sale'            => __('Sale', 'kangoo'),
    );

    if ($manual_badge && isset($forced_badges[$manual_badge])) {
        if ($manual_badge === 'new' && !kangoo_product_is_new($product_id)) {
            return null;
        }

        return array(
            'key'   => $manual_badge,
            'label' => $forced_badges[$manual_badge],
        );
    }

    if (kangoo_product_is_auto_best_seller($product_id)) {
        return array(
            'key'   => 'best_seller',
            'label' => __('Best Seller', 'kangoo'),
        );
    }

    if (kangoo_product_is_new($product_id)) {
        return array(
            'key'   => 'new',
            'label' => __('New', 'kangoo'),
        );
    }

    return null;
}

function kangoo_get_product_price_html($product) {
    if (!class_exists('WC_Product') || !$product instanceof WC_Product) {
        return '';
    }

    if (!$product->is_type('simple')) {
        return $product->get_price_html();
    }

    $regular_price = (float) $product->get_regular_price();
    $current_price = (float) $product->get_price();

    if ($product->is_on_sale() && $regular_price > $current_price && $current_price > 0) {
        return sprintf(
            '<del aria-hidden="true">%1$s</del> <ins>%2$s</ins>',
            wp_kses_post(wc_price($regular_price)),
            wp_kses_post(wc_price($current_price))
        );
    }

    return $product->get_price_html();
}

function kangoo_get_product_badge_key($product_id = null) {
    $badge = kangoo_get_product_badge($product_id);

    return $badge && isset($badge['key']) ? $badge['key'] : '';
}

function kangoo_blog_get_field($name, $post_id = null, $default = '') {
    $post_id = $post_id ? $post_id : get_the_ID();

    if (function_exists('get_field')) {
        $value = get_field($name, $post_id);

        if ($value !== null && $value !== '') {
            return $value;
        }
    }

    return $default;
}

function kangoo_blog_estimated_read_time($post_id = null) {
    $post_id = $post_id ? $post_id : get_the_ID();
    $manual = (int) kangoo_blog_get_field('blog_read_time', $post_id, 0);

    if ($manual > 0) {
        return $manual;
    }

    $content = get_post_field('post_content', $post_id);
    $words = str_word_count(wp_strip_all_tags(strip_shortcodes($content)));

    return max(1, (int) ceil($words / 220));
}

function kangoo_blog_meta_description($post_id = null) {
    $post_id = $post_id ? $post_id : get_the_ID();
    $description = kangoo_blog_get_field('blog_meta_description', $post_id);

    if (!$description) {
        $description = kangoo_blog_get_field('blog_standfirst', $post_id);
    }

    if (!$description) {
        $description = get_the_excerpt($post_id);
    }

    return wp_strip_all_tags($description);
}

function kangoo_blog_fallback_image_url() {
    $theme_fallback_path = get_template_directory() . '/assets/images/kangoo-pouches-blog-fallback-image.png';

    if (file_exists($theme_fallback_path)) {
        return get_template_directory_uri() . '/assets/images/kangoo-pouches-blog-fallback-image.png';
    }

    return 'https://kangoopouches.co.uk/wp-content/uploads/2026/05/kangoo-logo-black.png';
}

function kangoo_blog_featured_image_url($post_id = null, $size = 'large') {
    $post_id = $post_id ? $post_id : get_the_ID();
    $image = get_the_post_thumbnail_url($post_id, $size);

    return $image ? $image : kangoo_blog_fallback_image_url();
}

function kangoo_blog_featured_image_html($post_id = null, $size = 'large') {
    $post_id = $post_id ? $post_id : get_the_ID();

    if (has_post_thumbnail($post_id)) {
        return get_the_post_thumbnail($post_id, $size);
    }

    return sprintf(
        '<img src="%s" alt="%s" loading="lazy" decoding="async">',
        esc_url(kangoo_blog_fallback_image_url()),
        esc_attr(get_the_title($post_id))
    );
}

function kangoo_blog_document_title_parts($parts) {
    if (!is_singular('kangoo_blog')) {
        return $parts;
    }

    $seo_title = kangoo_blog_get_field('blog_seo_title');

    if ($seo_title) {
        $parts['title'] = wp_strip_all_tags($seo_title);
    }

    return $parts;
}
add_filter('document_title_parts', 'kangoo_blog_document_title_parts');

function kangoo_blog_head_meta() {
    if (is_post_type_archive('kangoo_blog')) {
        $description = __('Read Kangoo guides, product comparisons and practical buying advice for choosing with confidence.', 'kangoo');

        echo "\n" . '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
        echo '<meta property="og:type" content="website">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr__('Kangoo Blog', 'kangoo') . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url(get_post_type_archive_link('kangoo_blog')) . '">' . "\n";
        return;
    }

    if (is_tax('blog_topic')) {
        $term = get_queried_object();
        $description = $term && !empty($term->description)
            ? wp_strip_all_tags($term->description)
            : __('Helpful Kangoo articles, comparisons and buying advice.', 'kangoo');

        echo "\n" . '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
        echo '<meta property="og:type" content="website">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr(single_term_title('', false)) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url(get_term_link($term)) . '">' . "\n";
        return;
    }

    if (!is_singular('kangoo_blog')) {
        return;
    }

    $post_id = get_the_ID();
    $description = kangoo_blog_meta_description($post_id);
    $image = kangoo_blog_featured_image_url($post_id, 'large');
    $title = single_post_title('', false);
    $seo_title = kangoo_blog_get_field('blog_seo_title', $post_id);

    if ($seo_title) {
        $title = wp_strip_all_tags($seo_title);
    }

    if ($description) {
        echo "\n" . '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
    }

    echo '<meta property="og:type" content="article">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url(get_permalink($post_id)) . '">' . "\n";

    echo '<meta property="og:image" content="' . esc_url($image) . '">' . "\n";
}
add_action('wp_head', 'kangoo_blog_head_meta', 4);

function kangoo_blog_schema() {
    if (is_post_type_archive('kangoo_blog')) {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => __('Kangoo Blog', 'kangoo'),
            'description' => __('Guides, product comparisons and practical buying advice from Kangoo.', 'kangoo'),
            'url' => get_post_type_archive_link('kangoo_blog'),
        );

        echo "\n" . '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
        return;
    }

    if (is_tax('blog_topic')) {
        $term = get_queried_object();
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => single_term_title('', false),
            'description' => $term && !empty($term->description) ? wp_strip_all_tags($term->description) : __('Helpful Kangoo articles and buying advice.', 'kangoo'),
            'url' => $term ? get_term_link($term) : '',
        );

        echo "\n" . '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
        return;
    }

    if (!is_singular('kangoo_blog')) {
        return;
    }

    $post_id = get_the_ID();
    $image = kangoo_blog_featured_image_url($post_id, 'large');
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => get_the_title($post_id),
        'description' => kangoo_blog_meta_description($post_id),
        'datePublished' => get_the_date(DATE_W3C, $post_id),
        'dateModified' => get_the_modified_date(DATE_W3C, $post_id),
        'mainEntityOfPage' => get_permalink($post_id),
        'author' => array(
            '@type' => 'Person',
            'name' => get_the_author_meta('display_name', (int) get_post_field('post_author', $post_id)),
        ),
        'publisher' => array(
            '@type' => 'Organization',
            'name' => get_bloginfo('name'),
        ),
    );

    $schema['image'] = $image;

    echo "\n" . '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}
add_action('wp_head', 'kangoo_blog_schema', 20);

function kangoo_print_json_ld($schema) {
    if (empty($schema) || !is_array($schema)) {
        return;
    }

    echo "\n" . '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}

function kangoo_schema_url($url) {
    if (is_wp_error($url) || empty($url)) {
        return '';
    }

    return esc_url_raw($url);
}

function kangoo_site_logo_url() {
    $custom_logo_id = get_theme_mod('custom_logo');
    $logo_url = $custom_logo_id ? wp_get_attachment_image_url($custom_logo_id, 'full') : '';

    return $logo_url ? $logo_url : 'https://kangoopouches.co.uk/wp-content/uploads/2026/05/kangoo-logo-black.png';
}

function kangoo_site_identity_schema() {
    $home_url = home_url('/');
    $organization_id = trailingslashit($home_url) . '#organization';
    $website_id = trailingslashit($home_url) . '#website';

    kangoo_print_json_ld(array(
        '@context' => 'https://schema.org',
        '@graph' => array(
            array(
                '@type' => 'Organization',
                '@id' => $organization_id,
                'name' => get_bloginfo('name'),
                'url' => $home_url,
                'logo' => array(
                    '@type' => 'ImageObject',
                    'url' => kangoo_site_logo_url(),
                ),
            ),
            array(
                '@type' => 'WebSite',
                '@id' => $website_id,
                'url' => $home_url,
                'name' => get_bloginfo('name'),
                'publisher' => array(
                    '@id' => $organization_id,
                ),
                'potentialAction' => array(
                    '@type' => 'SearchAction',
                    'target' => trailingslashit($home_url) . '?s={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ),
            ),
        ),
    ));
}
add_action('wp_head', 'kangoo_site_identity_schema', 18);

function kangoo_schema_breadcrumb_item($position, $name, $url) {
    return array(
        '@type' => 'ListItem',
        'position' => (int) $position,
        'name' => wp_strip_all_tags($name),
        'item' => kangoo_schema_url($url),
    );
}

function kangoo_breadcrumb_schema() {
    if (is_front_page()) {
        return;
    }

    $items = array(
        kangoo_schema_breadcrumb_item(1, __('Home', 'kangoo'), home_url('/')),
    );

    if (function_exists('is_product') && is_product()) {
        global $product;

        if (!$product instanceof WC_Product) {
            $product = wc_get_product(get_the_ID());
        }

        if ($product instanceof WC_Product) {
            $terms = wp_get_post_terms($product->get_id(), 'product_cat');
            $term = !empty($terms) && !is_wp_error($terms) ? $terms[0] : null;

            if ($term instanceof WP_Term) {
                $ancestors = array_reverse(get_ancestors($term->term_id, 'product_cat'));

                foreach ($ancestors as $ancestor_id) {
                    $ancestor = get_term($ancestor_id, 'product_cat');

                    if ($ancestor instanceof WP_Term && !is_wp_error($ancestor)) {
                        $items[] = kangoo_schema_breadcrumb_item(count($items) + 1, $ancestor->name, get_term_link($ancestor));
                    }
                }

                $items[] = kangoo_schema_breadcrumb_item(count($items) + 1, $term->name, get_term_link($term));
            }

            $items[] = kangoo_schema_breadcrumb_item(count($items) + 1, get_the_title($product->get_id()), get_permalink($product->get_id()));
        }
    } elseif (is_product_category() || is_product_taxonomy() || is_category() || is_tax()) {
        $term = get_queried_object();

        if ($term instanceof WP_Term) {
            $ancestors = array_reverse(get_ancestors($term->term_id, $term->taxonomy));

            foreach ($ancestors as $ancestor_id) {
                $ancestor = get_term($ancestor_id, $term->taxonomy);

                if ($ancestor instanceof WP_Term && !is_wp_error($ancestor)) {
                    $items[] = kangoo_schema_breadcrumb_item(count($items) + 1, $ancestor->name, get_term_link($ancestor));
                }
            }

            $items[] = kangoo_schema_breadcrumb_item(count($items) + 1, single_term_title('', false), get_term_link($term));
        }
    } elseif (is_singular()) {
        $items[] = kangoo_schema_breadcrumb_item(count($items) + 1, get_the_title(), get_permalink());
    } elseif (is_post_type_archive('kangoo_blog')) {
        $items[] = kangoo_schema_breadcrumb_item(count($items) + 1, __('Blog', 'kangoo'), get_post_type_archive_link('kangoo_blog'));
    } elseif (function_exists('is_shop') && is_shop()) {
        $items[] = kangoo_schema_breadcrumb_item(count($items) + 1, __('Shop', 'kangoo'), get_permalink(wc_get_page_id('shop')));
    }

    if (count($items) < 2) {
        return;
    }

    kangoo_print_json_ld(array(
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $items,
    ));
}
add_action('wp_head', 'kangoo_breadcrumb_schema', 19);

function kangoo_product_schema() {
    if (!function_exists('is_product') || !is_product() || !function_exists('wc_get_product')) {
        return;
    }

    $product = wc_get_product(get_the_ID());

    if (!$product instanceof WC_Product) {
        return;
    }

    $product_id = $product->get_id();
    $description = $product->get_short_description() ? $product->get_short_description() : get_the_excerpt($product_id);
    $image_ids = array_filter(array_merge(array($product->get_image_id()), $product->get_gallery_image_ids()));
    $images = array();

    foreach ($image_ids as $image_id) {
        $image_url = wp_get_attachment_image_url($image_id, 'full');

        if ($image_url) {
            $images[] = $image_url;
        }
    }

    if (empty($images)) {
        $images[] = wc_placeholder_img_src('full');
    }

    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        '@id' => get_permalink($product_id) . '#product',
        'name' => get_the_title($product_id),
        'description' => wp_strip_all_tags($description),
        'url' => get_permalink($product_id),
        'image' => array_values(array_unique($images)),
        'brand' => array(
            '@type' => 'Brand',
            'name' => $product->get_attribute('pa_brand') ?: get_bloginfo('name'),
        ),
        'offers' => array(
            '@type' => 'Offer',
            'url' => get_permalink($product_id),
            'priceCurrency' => get_woocommerce_currency(),
            'price' => wc_format_decimal(wc_get_price_to_display($product), wc_get_price_decimals()),
            'availability' => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'itemCondition' => 'https://schema.org/NewCondition',
            'seller' => array(
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
            ),
        ),
    );

    if ($product->get_sku()) {
        $schema['sku'] = $product->get_sku();
    }

    $kangoo_review_summary = function_exists('kangoo_reviews_get_summary') ? kangoo_reviews_get_summary($product_id) : array();

    if (!empty($kangoo_review_summary['count']) && !empty($kangoo_review_summary['average'])) {
        $schema['aggregateRating'] = array(
            '@type' => 'AggregateRating',
            'ratingValue' => (string) $kangoo_review_summary['average'],
            'reviewCount' => (int) $kangoo_review_summary['count'],
        );

        if (function_exists('kangoo_reviews_get_product_reviews')) {
            $kangoo_schema_reviews = array();
            $kangoo_reviews = kangoo_reviews_get_product_reviews($product_id, 8);

            foreach ($kangoo_reviews as $kangoo_review) {
                $kangoo_review_body = trim((string) ($kangoo_review['review_body'] ?? ''));
                $kangoo_review_rating = isset($kangoo_review['rating']) ? (float) $kangoo_review['rating'] : 0;

                if ($kangoo_review_body === '' || $kangoo_review_rating <= 0) {
                    continue;
                }

                $kangoo_schema_review = array(
                    '@type' => 'Review',
                    'reviewBody' => $kangoo_review_body,
                    'reviewRating' => array(
                        '@type' => 'Rating',
                        'ratingValue' => (string) $kangoo_review_rating,
                        'bestRating' => '5',
                        'worstRating' => '1',
                    ),
                    'author' => array(
                        '@type' => 'Person',
                        'name' => !empty($kangoo_review['reviewer_name']) ? (string) $kangoo_review['reviewer_name'] : __('Kangoo customer', 'kangoo'),
                    ),
                    'publisher' => array(
                        '@type' => 'Organization',
                        'name' => get_bloginfo('name'),
                    ),
                );

                if (!empty($kangoo_review['date'])) {
                    $kangoo_schema_review['datePublished'] = (string) $kangoo_review['date'];
                }

                $kangoo_schema_reviews[] = $kangoo_schema_review;
            }

            if (!empty($kangoo_schema_reviews)) {
                $schema['review'] = $kangoo_schema_reviews;
            }
        }
    }

    kangoo_print_json_ld($schema);
}
add_action('wp_head', 'kangoo_product_schema', 21);

function kangoo_product_archive_itemlist_schema() {
    if ((!function_exists('is_shop') || !is_shop()) && (!function_exists('is_product_category') || !is_product_category()) && (!function_exists('is_product_taxonomy') || !is_product_taxonomy())) {
        return;
    }

    global $wp_query;

    if (empty($wp_query->posts)) {
        return;
    }

    $items = array();

    foreach ($wp_query->posts as $index => $post) {
        if ($post instanceof WP_Post && $post->post_type === 'product') {
            $items[] = array(
                '@type' => 'ListItem',
                'position' => $index + 1,
                'url' => get_permalink($post),
                'name' => get_the_title($post),
            );
        }
    }

    if (empty($items)) {
        return;
    }

    kangoo_print_json_ld(array(
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => wp_get_document_title(),
        'itemListElement' => $items,
    ));
}
add_action('wp_head', 'kangoo_product_archive_itemlist_schema', 22);

function kangoo_ajax_search() {
    check_ajax_referer('kangoo_ajax_search', 'nonce');

    $query = isset($_GET['query']) ? sanitize_text_field(wp_unslash($_GET['query'])) : '';
    $query = trim($query);

    if (strlen($query) < 2) {
        wp_send_json_success(array(
            'products' => array(),
            'guides' => array(),
        ));
    }

    $products = array();

    if (function_exists('wc_get_products')) {
        $product_query = new WP_Query(array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => 6,
            's' => $query,
            'no_found_rows' => true,
        ));

        if ($product_query->have_posts()) {
            while ($product_query->have_posts()) {
                $product_query->the_post();
                $product = wc_get_product(get_the_ID());

                if (!$product instanceof WC_Product || !$product->is_visible()) {
                    continue;
                }

                $image_id = $product->get_image_id();
                $image = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : wc_placeholder_img_src('thumbnail');
                $strength = function_exists('get_field') ? (string) get_field('strength_mg', $product->get_id()) : '';

                if ($strength !== '' && stripos($strength, 'mg') === false) {
                    $strength .= 'mg';
                }

                $products[] = array(
                    'title' => get_the_title(),
                    'url' => get_permalink(),
                    'image' => $image,
                    'price_html' => function_exists('kangoo_get_product_price_html') ? kangoo_get_product_price_html($product) : $product->get_price_html(),
                    'strength' => strtoupper($strength),
                    'stock' => $product->is_in_stock() ? '' : __('Sold Out', 'kangoo'),
                );
            }

            wp_reset_postdata();
        }
    }

    $guides = array();
    $guide_query = new WP_Query(array(
        'post_type' => 'kangoo_blog',
        'post_status' => 'publish',
        'posts_per_page' => 3,
        's' => $query,
        'no_found_rows' => true,
    ));

    if ($guide_query->have_posts()) {
        while ($guide_query->have_posts()) {
            $guide_query->the_post();

            $guides[] = array(
                'title' => html_entity_decode(get_the_title(), ENT_QUOTES, get_bloginfo('charset')),
                'url' => get_permalink(),
                'excerpt' => html_entity_decode(wp_trim_words(wp_strip_all_tags(get_the_excerpt()), 14), ENT_QUOTES, get_bloginfo('charset')),
            );
        }

        wp_reset_postdata();
    }

    wp_send_json_success(array(
        'products' => $products,
        'guides' => $guides,
    ));
}
add_action('wp_ajax_kangoo_ajax_search', 'kangoo_ajax_search');
add_action('wp_ajax_nopriv_kangoo_ajax_search', 'kangoo_ajax_search');

/* =========================================================================
PACK PRICING
========================================================================= */

function kangoo_get_pack_pricing_product_id($product_id) {
    $product_id = absint($product_id);

    if (!$product_id || !function_exists('wc_get_product')) {
        return $product_id;
    }

    $product = wc_get_product($product_id);

    if ($product && $product->is_type('variation')) {
        return (int) $product->get_parent_id();
    }

    return $product_id;
}

function kangoo_get_pack_pricing_tiers($product_id = null) {
    $product_id = kangoo_get_pack_pricing_product_id($product_id ? $product_id : get_the_ID());

    if (!$product_id || !function_exists('get_field')) {
        return array();
    }

    if (function_exists('kangoo_is_99p_product') && kangoo_is_99p_product($product_id)) {
        return array();
    }

    if (!get_field('pack_pricing_enabled', $product_id)) {
        return array();
    }

    $rows = get_field('pack_pricing_tiers', $product_id);

    if (empty($rows) || !is_array($rows)) {
        return array();
    }

    $tiers = array();
    $has_default = false;

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $quantity = isset($row['quantity']) ? absint($row['quantity']) : 0;
        $pack_price = isset($row['pack_price']) ? (float) $row['pack_price'] : 0;

        if ($quantity < 1 || $pack_price <= 0) {
            continue;
        }

        $is_default = !empty($row['default_selected']);

        if ($is_default) {
            $has_default = true;
        }

        $tiers[] = array(
            'quantity' => $quantity,
            'pack_price' => round($pack_price, 2),
            'unit_price' => round($pack_price / $quantity, 4),
            'badge' => isset($row['badge']) ? sanitize_text_field($row['badge']) : '',
            'default_selected' => $is_default,
        );
    }

    usort($tiers, static function ($a, $b) {
        return $a['quantity'] <=> $b['quantity'];
    });

    if (!$has_default && !empty($tiers)) {
        $tiers[0]['default_selected'] = true;
    }

    return $tiers;
}

function kangoo_get_pack_pricing_tier_for_quantity($product_id, $quantity) {
    $quantity = max(1, absint($quantity));
    $tiers = kangoo_get_pack_pricing_tiers($product_id);
    $selected = null;

    foreach ($tiers as $tier) {
        if ($quantity >= (int) $tier['quantity']) {
            $selected = $tier;
        }
    }

    return $selected;
}

function kangoo_get_product_stock_limit($product) {
    if (!$product instanceof WC_Product || !$product->managing_stock()) {
        return null;
    }

    $quantity = $product->get_stock_quantity();

    return $quantity === null ? null : max(0, (int) $quantity);
}

function kangoo_low_stock_public_threshold() {
    return 3;
}

function kangoo_should_show_low_stock_quantity($product) {
    if (!$product instanceof WC_Product) {
        return false;
    }

    $product_id = $product->get_id();

    if (function_exists('kangoo_is_99p_product') && kangoo_is_99p_product($product_id)) {
        return false;
    }

    $stock_limit = kangoo_get_product_stock_limit($product);

    return $stock_limit !== null && $stock_limit > 0 && $stock_limit < kangoo_low_stock_public_threshold();
}

function kangoo_get_low_stock_message($product) {
    if (!kangoo_should_show_low_stock_quantity($product)) {
        return '';
    }

    $stock_limit = kangoo_get_product_stock_limit($product);

    return sprintf(
        _n('Low stock: only %d left', 'Low stock: only %d left', $stock_limit, 'kangoo'),
        $stock_limit
    );
}

function kangoo_render_pack_pricing_selector() {
    global $product;

    if (!$product instanceof WC_Product) {
        return;
    }

    if (function_exists('kangoo_is_99p_product') && kangoo_is_99p_product($product->get_id())) {
        return;
    }

    $tiers = kangoo_get_pack_pricing_tiers($product->get_id());

    if (empty($tiers)) {
        return;
    }

    $stock_limit = kangoo_get_product_stock_limit($product);
    $available_tiers = array();

    foreach ($tiers as $tier) {
        $quantity = isset($tier['quantity']) ? (int) $tier['quantity'] : 0;

        if ($quantity < 1) {
            continue;
        }

        if ($stock_limit !== null && $quantity > $stock_limit) {
            continue;
        }

        $available_tiers[] = $tier;
    }

    if (empty($available_tiers)) {
        return;
    }

    $active_quantity = (int) $available_tiers[0]['quantity'];

    foreach ($available_tiers as $tier) {
        if (!empty($tier['default_selected'])) {
            $active_quantity = (int) $tier['quantity'];
            break;
        }
    }

    $active_set = false;
    ?>
    <div class="pack-pricing" data-pack-pricing>
        <span class="pack-pricing__label"><?php esc_html_e('Choose pack size', 'kangoo'); ?></span>
        <div class="pack-pricing__options">
            <?php foreach ($available_tiers as $tier) : ?>
                <?php
                $quantity = (int) $tier['quantity'];
                $pack_price = (float) $tier['pack_price'];
                $unit_price = (float) $tier['unit_price'];
                $is_active = !$active_set && $quantity === $active_quantity;

                if ($is_active) {
                    $active_set = true;
                }
                ?>
                <button
                    type="button"
                    class="pack-pricing__option<?php echo $is_active ? ' is-active' : ''; ?>"
                    data-pack-qty="<?php echo esc_attr($quantity); ?>"
                    data-pack-price="<?php echo esc_attr($pack_price); ?>"
                    data-unit-price="<?php echo esc_attr($unit_price); ?>"
                    aria-pressed="<?php echo $is_active ? 'true' : 'false'; ?>"
                >
                    <span class="pack-pricing__name">
                        <?php
                        printf(
                            esc_html(_n('%d-pack', '%d-pack', $quantity, 'kangoo')),
                            $quantity
                        );
                        ?>
                    </span>
                    <span class="pack-pricing__price"><?php echo wp_kses_post(wc_price($pack_price)); ?></span>
                    <span class="pack-pricing__unit"><?php echo wp_kses_post(wc_price($unit_price)); ?><?php esc_html_e('/unit', 'kangoo'); ?></span>
                    <?php if (!empty($tier['badge'])) : ?>
                        <span class="pack-pricing__badge"><?php echo esc_html($tier['badge']); ?></span>
                    <?php endif; ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}
add_action('woocommerce_before_add_to_cart_quantity', 'kangoo_render_pack_pricing_selector', 5);

function kangoo_apply_pack_pricing_to_cart($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    if (!$cart || empty($cart->cart_contents)) {
        return;
    }

    foreach ($cart->cart_contents as $cart_item) {
        if (empty($cart_item['data']) || !($cart_item['data'] instanceof WC_Product)) {
            continue;
        }

        $product_id = !empty($cart_item['variation_id']) ? (int) $cart_item['variation_id'] : (int) $cart_item['product_id'];
        $quantity = isset($cart_item['quantity']) ? (int) $cart_item['quantity'] : 1;
        $tier = kangoo_get_pack_pricing_tier_for_quantity($product_id, $quantity);

        if (!$tier) {
            continue;
        }

        $cart_item['data']->set_price((float) $tier['unit_price']);
    }
}
add_action('woocommerce_before_calculate_totals', 'kangoo_apply_pack_pricing_to_cart', 20);

function kangoo_99p_price() {
    return 0.99;
}

function kangoo_99p_term_slugs() {
    return array('99p', '99p-pouches', '99p-collection');
}

function kangoo_is_99p_product($product_id) {
    $product_id = kangoo_get_pack_pricing_product_id($product_id);

    if (!$product_id) {
        return false;
    }

    $slugs = kangoo_99p_term_slugs();

    return has_term($slugs, 'product_cat', $product_id) || has_term($slugs, 'product_tag', $product_id);
}

/* =========================================================================
SEO PRODUCT MODULE HELPERS
========================================================================= */

function kangoo_get_page_url_by_template($template, $fallback = '') {
    static $template_urls = array();

    $template = (string) $template;

    if ($template === '') {
        return $fallback ? home_url($fallback) : home_url('/');
    }

    if (!isset($template_urls[$template])) {
        $page_ids = get_posts(array(
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_key'       => '_wp_page_template',
            'meta_value'     => $template,
        ));

        $template_urls[$template] = !empty($page_ids) ? get_permalink($page_ids[0]) : '';
    }

    if ($template_urls[$template]) {
        return $template_urls[$template];
    }

    return $fallback ? home_url($fallback) : home_url('/');
}

function kangoo_get_term_url_by_slug($taxonomy, $slug, $fallback = '') {
    static $term_urls = array();

    if (!taxonomy_exists($taxonomy)) {
        return $fallback ? home_url($fallback) : home_url('/');
    }

    $cache_key = $taxonomy . ':' . $slug;

    if (isset($term_urls[$cache_key])) {
        return $term_urls[$cache_key] ?: ($fallback ? home_url($fallback) : home_url('/'));
    }

    $term = get_term_by('slug', $slug, $taxonomy);

    if (!$term || is_wp_error($term)) {
        $term_urls[$cache_key] = '';

        return $fallback ? home_url($fallback) : home_url('/');
    }

    $url = get_term_link($term);
    $term_urls[$cache_key] = !is_wp_error($url) ? $url : '';

    return $term_urls[$cache_key] ?: ($fallback ? home_url($fallback) : home_url('/'));
}

function kangoo_get_image_url_from_acf_value($image, $size = 'medium') {
    if (is_array($image)) {
        if (!empty($image['sizes'][$size])) {
            return (string) $image['sizes'][$size];
        }

        if (!empty($image['url'])) {
            return (string) $image['url'];
        }

        if (!empty($image['ID'])) {
            return (string) wp_get_attachment_image_url((int) $image['ID'], $size);
        }

        if (!empty($image['id'])) {
            return (string) wp_get_attachment_image_url((int) $image['id'], $size);
        }
    }

    if (is_numeric($image)) {
        return (string) wp_get_attachment_image_url((int) $image, $size);
    }

    return is_string($image) ? trim($image) : '';
}

function kangoo_resolve_product_category_term($value) {
    if (!taxonomy_exists('product_cat')) {
        return null;
    }

    if (is_wp_error($value)) {
        return null;
    }

    if ($value instanceof WP_Term && $value->taxonomy === 'product_cat') {
        return $value;
    }

    if (is_array($value)) {
        if (isset($value['term_id'])) {
            return kangoo_resolve_product_category_term((int) $value['term_id']);
        }

        if (isset($value['id'])) {
            return kangoo_resolve_product_category_term((int) $value['id']);
        }

        if (isset($value['slug'])) {
            return kangoo_resolve_product_category_term((string) $value['slug']);
        }

        if (isset($value['value'])) {
            return kangoo_resolve_product_category_term($value['value']);
        }

        if (isset($value['label'])) {
            return kangoo_resolve_product_category_term($value['label']);
        }

        $first = reset($value);
        return $first ? kangoo_resolve_product_category_term($first) : null;
    }

    if (is_numeric($value)) {
        $term = get_term((int) $value, 'product_cat');
        return $term instanceof WP_Term && !is_wp_error($term) ? $term : null;
    }

    $value = trim((string) $value);

    if ($value === '') {
        return null;
    }

    $term = get_term_by('slug', sanitize_title($value), 'product_cat');

    if ($term instanceof WP_Term && !is_wp_error($term)) {
        return $term;
    }

    $term = get_term_by('name', $value, 'product_cat');

    return $term instanceof WP_Term && !is_wp_error($term) ? $term : null;
}

function kangoo_get_product_category_image_url($term, $size = 'medium') {
    $term = kangoo_resolve_product_category_term($term);

    if (!$term) {
        return '';
    }

    $thumbnail_id = (int) get_term_meta($term->term_id, 'thumbnail_id', true);

    if ($thumbnail_id) {
        $image_url = wp_get_attachment_image_url($thumbnail_id, $size);

        if ($image_url) {
            return (string) $image_url;
        }
    }

    if (function_exists('get_field')) {
        foreach (array('category_image', 'brand_image', 'image') as $field_name) {
            $image_url = kangoo_get_image_url_from_acf_value(get_field($field_name, 'product_cat_' . $term->term_id), $size);

            if ($image_url !== '') {
                return $image_url;
            }
        }
    }

    foreach (array('category_image', 'brand_image', 'image') as $meta_key) {
        $image_url = kangoo_get_image_url_from_acf_value(get_term_meta($term->term_id, $meta_key, true), $size);

        if ($image_url !== '') {
            return $image_url;
        }
    }

    return '';
}

function kangoo_resolve_brand_category_card($category_value, $manual_label = '', $manual_image = null, $manual_link = array(), $extra = array()) {
    $term = kangoo_resolve_product_category_term($category_value);
    $label = trim((string) $manual_label);
    $url = function_exists('kangoo_acf_link_url') ? kangoo_acf_link_url($manual_link) : '';
    $target = function_exists('kangoo_acf_link_target') ? kangoo_acf_link_target($manual_link) : '_self';
    $image_url = kangoo_get_image_url_from_acf_value($manual_image, 'medium');

    if ($term) {
        $term_link = get_term_link($term);

        if (!is_wp_error($term_link)) {
            $label = $term->name;
            $url = (string) $term_link;
            $target = '_self';
            $term_image_url = kangoo_get_product_category_image_url($term, 'medium');

            if ($term_image_url !== '') {
                $image_url = $term_image_url;
            }
        }
    }

    $card = array(
        'label'      => $label,
        'url'        => $url,
        'target'     => $target ?: '_self',
        'link'       => array(
            'title'  => $label,
            'url'    => $url,
            'target' => $target ?: '_self',
        ),
        'image_url'  => $image_url,
        'image'      => $image_url !== '' ? array('url' => $image_url) : array(),
        'featured'   => !empty($extra['featured']),
        'badge_text' => isset($extra['badge_text']) ? (string) $extra['badge_text'] : '',
    );

    return $card;
}

function kangoo_get_product_brand_label($product) {
    if (!class_exists('WC_Product') || !$product instanceof WC_Product) {
        return '';
    }

    $brand = trim((string) $product->get_attribute('pa_brand'));

    if ($brand !== '') {
        return $brand;
    }

    $product_id = $product->get_id();
    $known_brands = array(
        'zyn'           => 'ZYN',
        'velo'          => 'VELO',
        'killa'         => 'KILLA',
        'pablo'         => 'PABLO',
        'fumi'          => 'FUMi',
        'nordic-spirit' => 'Nordic Spirit',
        'xqs'           => 'XQS',
    );

    foreach ($known_brands as $slug => $label) {
        if (has_term($slug, 'product_cat', $product_id) || has_term($slug, 'product_tag', $product_id)) {
            return $label;
        }
    }

    $name = strtolower($product->get_name());

    foreach ($known_brands as $slug => $label) {
        if (strpos($name, strtolower(str_replace('-', ' ', $slug))) !== false || strpos($name, strtolower($label)) !== false) {
            return $label;
        }
    }

    return '';
}

function kangoo_get_product_strength_details($product) {
    if (!class_exists('WC_Product') || !$product instanceof WC_Product) {
        return array(
            'label' => '',
            'mg'    => 0,
            'band'  => '',
        );
    }

    $product_id = $product->get_id();
    $strength = function_exists('get_field') ? (string) get_field('strength_mg', $product_id) : '';

    if ($strength === '') {
        $strength = (string) $product->get_attribute('pa_strength');
    }

    if ($strength === '' && preg_match('/(\d+(?:\.\d+)?)\s*mg/i', $product->get_name(), $matches)) {
        $strength = $matches[1] . 'mg';
    }

    $mg = $strength !== '' ? (float) preg_replace('/[^0-9.]/', '', $strength) : 0;
    $label = strtoupper(trim($strength));

    if ($label !== '' && stripos($label, 'MG') === false && preg_match('/\d/', $label)) {
        $label .= 'MG';
    }

    $words = strtolower($strength . ' ' . $product->get_name());
    $band = '';

    if ($mg >= 15 || strpos($words, 'extra') !== false) {
        $band = 'extra';
    } elseif ($mg >= 10 || strpos($words, 'strong') !== false) {
        $band = 'strong';
    } elseif ($mg >= 5 || strpos($words, 'medium') !== false || strpos($words, 'balanced') !== false) {
        $band = 'balanced';
    } elseif ($mg > 0 || strpos($words, 'light') !== false || strpos($words, 'mini') !== false) {
        $band = 'light';
    }

    return array(
        'label' => $label,
        'mg'    => $mg,
        'band'  => $band,
    );
}

function kangoo_get_product_flavour_label($product) {
    if (!class_exists('WC_Product') || !$product instanceof WC_Product) {
        return '';
    }

    $flavour = trim((string) $product->get_attribute('pa_flavour'));

    if ($flavour !== '') {
        return $flavour;
    }

    $name = strtolower($product->get_name());
    $keywords = array(
        'spearmint'    => 'Spearmint',
        'peppermint'   => 'Peppermint',
        'menthol'      => 'Menthol',
        'mint'         => 'Mint',
        'black cherry' => 'Black Cherry',
        'cherry'       => 'Cherry',
        'berry'        => 'Berry',
        'citrus'       => 'Citrus',
        'orange'       => 'Orange',
        'mango'        => 'Mango',
        'watermelon'   => 'Watermelon',
        'grape'        => 'Grape',
        'coffee'       => 'Coffee',
        'cola'         => 'Cola',
        'apple'        => 'Apple',
        'banana'       => 'Banana',
        'pineapple'    => 'Pineapple',
        'blueberry'    => 'Blueberry',
        'tropical'     => 'Tropical',
        'lemon'        => 'Lemon',
    );

    foreach ($keywords as $keyword => $label) {
        if (strpos($name, $keyword) !== false) {
            return $label;
        }
    }

    return '';
}

function kangoo_get_product_pouch_count($product) {
    if (!class_exists('WC_Product') || !$product instanceof WC_Product) {
        return 0;
    }

    $product_id = $product->get_id();
    $fields = array('pouch_count', 'pouches_per_can', 'number_of_pouches');

    foreach ($fields as $field) {
        $value = function_exists('get_field') ? get_field($field, $product_id) : '';

        if ($value) {
            $count = (int) preg_replace('/[^0-9]/', '', (string) $value);

            if ($count > 0) {
                return $count;
            }
        }
    }

    $name = strtolower($product->get_name());

    if (preg_match('/(\d+)\s*pouches/i', $name, $matches)) {
        return (int) $matches[1];
    }

    return strpos($name, 'mini') !== false ? 10 : 20;
}

function kangoo_get_product_best_for_label($product) {
    $strength = kangoo_get_product_strength_details($product);
    $flavour = strtolower($product->get_name() . ' ' . kangoo_get_product_flavour_label($product));

    if ($strength['band'] === 'extra') {
        return __('Experienced adult users', 'kangoo');
    }

    if ($strength['band'] === 'strong') {
        return __('Stronger pouch feel', 'kangoo');
    }

    if (preg_match('/mint|menthol|ice|cool|freeze|spearmint|peppermint/', $flavour)) {
        return __('Fresh daily rotation', 'kangoo');
    }

    if (preg_match('/berry|cherry|mango|tropical|melon|fruit|grape|apple|pineapple/', $flavour)) {
        return __('Fruit flavour fans', 'kangoo');
    }

    if (preg_match('/coffee|cola|cappuccino/', $flavour)) {
        return __('Trying a different flavour', 'kangoo');
    }

    return __('Everyday pouch choice', 'kangoo');
}

function kangoo_get_product_pack_summary($product) {
    if (!class_exists('WC_Product') || !$product instanceof WC_Product) {
        return array(
            'label' => '',
            'value' => '',
        );
    }

    if (function_exists('kangoo_is_99p_product') && kangoo_is_99p_product($product->get_id())) {
        return array(
            'label' => __('Trial price', 'kangoo'),
            'value' => __('99p trial pouch', 'kangoo'),
        );
    }

    $tiers = function_exists('kangoo_get_pack_pricing_tiers') ? kangoo_get_pack_pricing_tiers($product->get_id()) : array();
    $best_tier = null;

    foreach ($tiers as $tier) {
        if (!$best_tier || (float) $tier['unit_price'] < (float) $best_tier['unit_price']) {
            $best_tier = $tier;
        }
    }

    if ($best_tier) {
        return array(
            'label' => __('Best multi-buy', 'kangoo'),
            'value' => sprintf(
                /* translators: 1: pack quantity, 2: formatted unit price. */
                __('%1$d-pack from %2$s/can', 'kangoo'),
                (int) $best_tier['quantity'],
                kangoo_plain_wc_price((float) $best_tier['unit_price'])
            ),
        );
    }

    return array(
        'label' => __('Single price', 'kangoo'),
        'value' => $product->get_price() !== '' ? kangoo_plain_wc_price((float) $product->get_price()) : '',
    );
}

function kangoo_get_product_seo_summary($product) {
    if (!class_exists('WC_Product') || !$product instanceof WC_Product) {
        return array();
    }

    $strength = kangoo_get_product_strength_details($product);
    $pack_summary = kangoo_get_product_pack_summary($product);

    return array(
        'id'           => $product->get_id(),
        'name'         => $product->get_name(),
        'url'          => get_permalink($product->get_id()),
        'image'        => get_the_post_thumbnail_url($product->get_id(), 'woocommerce_thumbnail'),
        'brand'        => kangoo_get_product_brand_label($product),
        'flavour'      => kangoo_get_product_flavour_label($product),
        'strength'     => $strength['label'],
        'strength_mg'  => $strength['mg'],
        'strength_band'=> $strength['band'],
        'pouch_count'  => kangoo_get_product_pouch_count($product),
        'price_html'   => function_exists('kangoo_get_product_price_html') ? kangoo_get_product_price_html($product) : $product->get_price_html(),
        'pack_label'   => $pack_summary['label'],
        'pack_value'   => $pack_summary['value'],
        'best_for'     => kangoo_get_product_best_for_label($product),
        'stock'        => $product->is_in_stock(),
        'stock_note'   => function_exists('kangoo_get_low_stock_message') ? kangoo_get_low_stock_message($product) : '',
        'is_99p'       => function_exists('kangoo_is_99p_product') && kangoo_is_99p_product($product->get_id()),
    );
}

function kangoo_get_seo_products($args = array()) {
    if (!function_exists('wc_get_products')) {
        return array();
    }

    $args = wp_parse_args($args, array(
        'limit'       => 4,
        'category'    => array(),
        'tax_query'   => array(),
        'exclude_99p' => false,
        'include_99p' => true,
        'stock_first' => true,
    ));

    static $product_cache = array();

    $limit = max(1, absint($args['limit']));
    $cache_key = md5(wp_json_encode($args));

    if (isset($product_cache[$cache_key])) {
        return $product_cache[$cache_key];
    }

    $query_args = array(
        'status'  => 'publish',
        'limit'   => max(20, $limit * 5),
        'orderby' => 'popularity',
        'return'  => 'objects',
    );

    if (!empty($args['category'])) {
        $query_args['category'] = array_values(array_filter(array_map('sanitize_title', (array) $args['category'])));
    }

    if (!empty($args['tax_query']) && is_array($args['tax_query'])) {
        $query_args['tax_query'] = $args['tax_query'];
    }

    $products = wc_get_products($query_args);
    $filtered = array();

    foreach ($products as $product) {
        if (!$product instanceof WC_Product || !$product->is_visible()) {
            continue;
        }

        $is_99p = function_exists('kangoo_is_99p_product') && kangoo_is_99p_product($product->get_id());

        if (!empty($args['exclude_99p']) && $is_99p) {
            continue;
        }

        if (empty($args['include_99p']) && $is_99p) {
            continue;
        }

        $filtered[] = $product;
    }

    usort($filtered, static function ($a, $b) use ($args) {
        if (!empty($args['stock_first'])) {
            $stock_compare = (int) $b->is_in_stock() <=> (int) $a->is_in_stock();

            if ($stock_compare !== 0) {
                return $stock_compare;
            }
        }

        $a_sales = (int) get_post_meta($a->get_id(), 'total_sales', true);
        $b_sales = (int) get_post_meta($b->get_id(), 'total_sales', true);

        if ($a_sales !== $b_sales) {
            return $b_sales <=> $a_sales;
        }

        return strcasecmp($a->get_name(), $b->get_name());
    });

    $product_cache[$cache_key] = array_slice($filtered, 0, $limit);

    return $product_cache[$cache_key];
}

function kangoo_get_trial_products($limit = 6) {
    return kangoo_get_seo_products(array(
        'limit'    => $limit,
        'category' => kangoo_99p_term_slugs(),
    ));
}

function kangoo_get_pack_priced_products($limit = 6) {
    $products = kangoo_get_seo_products(array(
        'limit'       => max(12, $limit * 2),
        'category'    => array('nicotine-pouches'),
        'exclude_99p' => true,
    ));

    $pack_products = array();

    foreach ($products as $product) {
        if (!empty(kangoo_get_pack_pricing_tiers($product->get_id()))) {
            $pack_products[] = $product;
        }

        if (count($pack_products) >= $limit) {
            break;
        }
    }

    return $pack_products;
}

function kangoo_get_best_seller_products($limit = 5) {
    return kangoo_get_seo_products(array(
        'limit'       => $limit,
        'category'    => array('nicotine-pouches'),
        'exclude_99p' => true,
    ));
}

function kangoo_get_retailer_value_comparison_rows() {
    return array(
        array(
            'label'       => __('Starting price', 'kangoo'),
            'kangoo'      => __('99p trial pouches when available; main range from £3.99', 'kangoo'),
            'supermarket' => __('Often limited to shelf range and current in-store pricing', 'kangoo'),
            'corner'      => __('Varies by store and local availability', 'kangoo'),
        ),
        array(
            'label'       => __('Multi-buy options', 'kangoo'),
            'kangoo'      => __('Pack pricing on selected products, including 3, 5 and 10-pack options', 'kangoo'),
            'supermarket' => __('Usually fewer multi-buy options for pouch shoppers', 'kangoo'),
            'corner'      => __('Usually single-can convenience buying', 'kangoo'),
        ),
        array(
            'label'       => __('Brand choice', 'kangoo'),
            'kangoo'      => __('ZYN, VELO, KILLA, PABLO and rotating trial pouches', 'kangoo'),
            'supermarket' => __('Typically a smaller range of mainstream brands', 'kangoo'),
            'corner'      => __('Depends on local stock and shelf space', 'kangoo'),
        ),
        array(
            'label'       => __('Strength and flavour range', 'kangoo'),
            'kangoo'      => __('Light, balanced, strong and extra strong options across mint, berry, citrus and more', 'kangoo'),
            'supermarket' => __('Often focused on the most common flavours and strengths', 'kangoo'),
            'corner'      => __('Usually limited to what is available behind the counter', 'kangoo'),
        ),
        array(
            'label'       => __('Delivery and packaging', 'kangoo'),
            'kangoo'      => __('UK delivery, discreet packaging and free delivery over £14.95', 'kangoo'),
            'supermarket' => __('Collection or grocery delivery terms vary by retailer', 'kangoo'),
            'corner'      => __('Immediate purchase when local stock is available', 'kangoo'),
        ),
        array(
            'label'       => __('Trial options', 'kangoo'),
            'kangoo'      => __('99p trial pouches are limited to one per order while stock lasts', 'kangoo'),
            'supermarket' => __('Trial pricing is not always available', 'kangoo'),
            'corner'      => __('Trial pricing is not always available', 'kangoo'),
        ),
    );
}

function kangoo_get_archive_seo_context() {
    if ((!function_exists('is_product_category') || !is_product_category()) && (!function_exists('is_product_taxonomy') || !is_product_taxonomy())) {
        return array();
    }

    $term = get_queried_object();

    if (!$term instanceof WP_Term) {
        return array();
    }

    if ($term->taxonomy === 'product_cat') {
        if ($term->slug === 'nicotine-pouches') {
            return array('type' => 'nicotine', 'term' => $term);
        }

        if (in_array($term->slug, kangoo_99p_term_slugs(), true)) {
            return array('type' => 'trial', 'term' => $term);
        }

        if (in_array($term->slug, array('zyn', 'velo', 'pablo', 'killa'), true)) {
            return array('type' => 'brand', 'term' => $term);
        }
    }

    if ($term->taxonomy === 'pa_flavour') {
        return array('type' => 'flavour', 'term' => $term);
    }

    if ($term->taxonomy === 'pa_strength') {
        return array('type' => 'strength', 'term' => $term);
    }

    return array();
}

function kangoo_archive_has_filter_query() {
    $filter_keys = array('filter_brand', 'filter_flavour', 'filter_strength', 'orderby', 'min_price', 'max_price', 's');

    foreach ($filter_keys as $key) {
        if (isset($_GET[$key]) && trim((string) wp_unslash($_GET[$key])) !== '') {
            return true;
        }
    }

    return false;
}

function kangoo_archive_seo_data() {
    $context = kangoo_get_archive_seo_context();

    if (empty($context['term']) || !$context['term'] instanceof WP_Term) {
        return array();
    }

    $term = $context['term'];
    $title = '';
    $description = '';

    if ($context['type'] === 'nicotine') {
        $title = __('Nicotine Pouches UK From 99p', 'kangoo');
        $description = __('Shop nicotine pouches in the UK from Kangoo, including ZYN, VELO, KILLA and PABLO, 99p trial pouches, pack pricing and discreet UK delivery.', 'kangoo');
    } elseif ($context['type'] === 'trial') {
        $title = __('99p Nicotine Pouches UK', 'kangoo');
        $description = __('Try selected nicotine pouches from 99p at Kangoo. Trial pouches are limited to one per order while stock lasts and are for adult nicotine users only.', 'kangoo');
    } elseif ($context['type'] === 'brand') {
        $brand = strtoupper($term->name);
        $title = sprintf(__('%s Nicotine Pouches UK', 'kangoo'), $brand);
        $description = sprintf(__('Shop %s nicotine pouches at Kangoo with stock-aware product choice, pack pricing where available, discreet UK delivery and adult-only ordering.', 'kangoo'), $brand);
    } elseif ($context['type'] === 'flavour') {
        $title = sprintf(__('%s Nicotine Pouches UK', 'kangoo'), $term->name);
        $description = sprintf(__('Browse %s nicotine pouches at Kangoo, compare strengths and brands, and find adult nicotine pouch options with fast UK delivery.', 'kangoo'), strtolower($term->name));
    } elseif ($context['type'] === 'strength') {
        $name = preg_replace('/\s*strength\s*/i', '', $term->name);
        $title = sprintf(__('%s Nicotine Pouches UK', 'kangoo'), $name);
        $description = sprintf(__('Shop %s nicotine pouches at Kangoo. Compare products by brand, flavour and price before choosing a pouch for adult nicotine users.', 'kangoo'), strtolower($name));
    }

    $canonical = get_term_link($term);

    if (is_wp_error($canonical)) {
        $canonical = '';
    }

    if ($canonical && !kangoo_archive_has_filter_query()) {
        $paged = max(1, (int) get_query_var('paged'));

        if ($paged > 1) {
            $canonical = get_pagenum_link($paged);
        }
    }

    return array(
        'title'       => $title,
        'description' => $description,
        'canonical'   => $canonical,
    );
}

function kangoo_archive_document_title_parts($parts) {
    $seo = kangoo_archive_seo_data();

    if (!empty($seo['title'])) {
        $parts['title'] = wp_strip_all_tags($seo['title']);
    }

    return $parts;
}
add_filter('document_title_parts', 'kangoo_archive_document_title_parts', 20);

function kangoo_archive_head_meta() {
    $seo = kangoo_archive_seo_data();

    if (empty($seo)) {
        return;
    }

    if (!empty($seo['description'])) {
        echo "\n" . '<meta name="description" content="' . esc_attr($seo['description']) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($seo['description']) . '">' . "\n";
    }

    if (!empty($seo['title'])) {
        echo '<meta property="og:title" content="' . esc_attr($seo['title']) . '">' . "\n";
    }

    if (!empty($seo['canonical'])) {
        echo '<link rel="canonical" href="' . esc_url($seo['canonical']) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url($seo['canonical']) . '">' . "\n";
    }

    echo '<meta property="og:type" content="website">' . "\n";
}
add_action('wp_head', 'kangoo_archive_head_meta', 5);

function kangoo_filtered_archive_robots($robots) {
    if (!empty(kangoo_archive_seo_data()) && kangoo_archive_has_filter_query()) {
        unset($robots['index']);
        $robots['noindex'] = true;
        $robots['follow'] = true;
    }

    return $robots;
}
add_filter('wp_robots', 'kangoo_filtered_archive_robots');

function kangoo_get_cart_99p_quantity($exclude_cart_item_key = '') {
    if (!function_exists('WC') || !WC()->cart) {
        return 0;
    }

    $quantity = 0;

    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        if ($exclude_cart_item_key && $cart_item_key === $exclude_cart_item_key) {
            continue;
        }

        $product_id = !empty($cart_item['variation_id']) ? (int) $cart_item['variation_id'] : (int) $cart_item['product_id'];

        if (kangoo_is_99p_product($product_id)) {
            $quantity += isset($cart_item['quantity']) ? (int) $cart_item['quantity'] : 0;
        }
    }

    return $quantity;
}

function kangoo_validate_99p_add_to_cart($passed, $product_id, $quantity, $variation_id = 0) {
    $target_product_id = $variation_id ? (int) $variation_id : (int) $product_id;

    if (!kangoo_is_99p_product($target_product_id)) {
        return $passed;
    }

    if ((int) $quantity > 1 || kangoo_get_cart_99p_quantity() >= 1) {
        wc_add_notice(__('99p trial pouches are limited to 1 per order. You can still add any other products to your basket.', 'kangoo'), 'error');
        return false;
    }

    return $passed;
}
add_filter('woocommerce_add_to_cart_validation', 'kangoo_validate_99p_add_to_cart', 10, 4);

function kangoo_validate_99p_cart_update($passed, $cart_item_key, $values, $quantity) {
    $product_id = !empty($values['variation_id']) ? (int) $values['variation_id'] : (int) $values['product_id'];

    if (!kangoo_is_99p_product($product_id)) {
        return $passed;
    }

    if ((int) $quantity > 1 || ((int) $quantity > 0 && kangoo_get_cart_99p_quantity($cart_item_key) >= 1)) {
        wc_add_notice(__('99p trial pouches are limited to 1 per order.', 'kangoo'), 'error');
        return false;
    }

    return $passed;
}
add_filter('woocommerce_update_cart_validation', 'kangoo_validate_99p_cart_update', 10, 4);

function kangoo_limit_99p_quantity_input_args($args, $product) {
    if (!$product instanceof WC_Product || !kangoo_is_99p_product($product->get_id())) {
        return $args;
    }

    $args['max_value'] = 1;
    $args['input_value'] = min(1, max(1, isset($args['input_value']) ? (int) $args['input_value'] : 1));

    return $args;
}
add_filter('woocommerce_quantity_input_args', 'kangoo_limit_99p_quantity_input_args', 10, 2);

function kangoo_limit_99p_available_variation($variation_data, $product, $variation) {
    $variation_id = $variation instanceof WC_Product ? $variation->get_id() : 0;
    $product_id = $variation_id ?: ($product instanceof WC_Product ? $product->get_id() : 0);

    if (!$product_id || !kangoo_is_99p_product($product_id)) {
        return $variation_data;
    }

    $variation_data['max_qty'] = 1;
    $variation_data['min_qty'] = 1;

    return $variation_data;
}
add_filter('woocommerce_available_variation', 'kangoo_limit_99p_available_variation', 10, 3);

function kangoo_enforce_99p_cart_limit($cart) {
    if (!$cart || empty($cart->cart_contents)) {
        return;
    }

    static $enforcing = false;

    if ($enforcing) {
        return;
    }

    $enforcing = true;
    $has_trial = false;

    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        $product_id = !empty($cart_item['variation_id']) ? (int) $cart_item['variation_id'] : (int) $cart_item['product_id'];

        if (!$product_id || !kangoo_is_99p_product($product_id)) {
            continue;
        }

        if ($has_trial) {
            $cart->remove_cart_item($cart_item_key);
            continue;
        }

        $has_trial = true;

        if (!empty($cart_item['quantity']) && (int) $cart_item['quantity'] > 1) {
            $cart->set_quantity($cart_item_key, 1, false);
        }
    }

    $enforcing = false;
}
add_action('woocommerce_before_calculate_totals', 'kangoo_enforce_99p_cart_limit', 5);

function kangoo_apply_99p_cart_price($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    if (!$cart || empty($cart->cart_contents)) {
        return;
    }

    foreach ($cart->cart_contents as $cart_item) {
        if (empty($cart_item['data']) || !($cart_item['data'] instanceof WC_Product)) {
            continue;
        }

        $product_id = !empty($cart_item['variation_id']) ? (int) $cart_item['variation_id'] : (int) $cart_item['product_id'];

        if (kangoo_is_99p_product($product_id)) {
            $cart_item['data']->set_price(kangoo_99p_price());
        }
    }
}
add_action('woocommerce_before_calculate_totals', 'kangoo_apply_99p_cart_price', 15);

/* =========================================================================
KANGOO REWARDS
========================================================================= */

function kangoo_rewards_points_per_pound() {
    return 1;
}

function kangoo_rewards_points_per_pound_value() {
    return 100;
}

function kangoo_rewards_min_redemption_points() {
    return 100;
}

function kangoo_rewards_max_cart_discount_ratio() {
    return 1;
}

function kangoo_rewards_coupon_code() {
    return 'kangoo-rewards';
}

function kangoo_rewards_get_balance($user_id = null) {
    $user_id = $user_id ? absint($user_id) : get_current_user_id();

    if (!$user_id) {
        return 0;
    }

    return max(0, (int) get_user_meta($user_id, 'kangoo_rewards_points_balance', true));
}

function kangoo_rewards_get_history($user_id = null) {
    $user_id = $user_id ? absint($user_id) : get_current_user_id();

    if (!$user_id) {
        return array();
    }

    $history = get_user_meta($user_id, 'kangoo_rewards_points_history', true);

    return is_array($history) ? $history : array();
}

function kangoo_rewards_add_history_entry($user_id, $points, $label, $order_id = 0) {
    $history = kangoo_rewards_get_history($user_id);

    array_unshift($history, array(
        'date'     => current_time('mysql'),
        'points'   => (int) $points,
        'label'    => sanitize_text_field($label),
        'order_id' => absint($order_id),
    ));

    $history = array_slice($history, 0, 60);

    update_user_meta($user_id, 'kangoo_rewards_points_history', $history);
}

function kangoo_rewards_adjust_points($user_id, $points, $label, $order_id = 0) {
    $user_id = absint($user_id);
    $points = (int) $points;

    if (!$user_id || $points === 0) {
        return 0;
    }

    $balance = kangoo_rewards_get_balance($user_id);
    $new_balance = max(0, $balance + $points);

    update_user_meta($user_id, 'kangoo_rewards_points_balance', $new_balance);
    kangoo_rewards_add_history_entry($user_id, $points, $label, $order_id);

    return $new_balance;
}

function kangoo_rewards_points_to_money($points) {
    $points = max(0, (int) $points);

    return floor($points / kangoo_rewards_points_per_pound_value()) * 1.0;
}

function kangoo_rewards_money_to_points($amount) {
    return max(0, (int) floor((float) $amount * kangoo_rewards_points_per_pound_value()));
}

function kangoo_rewards_get_cart_subtotal() {
    if (!function_exists('WC') || !WC()->cart) {
        return 0;
    }

    return max(0, (float) WC()->cart->get_subtotal());
}

function kangoo_rewards_cart_item_is_99p($cart_item) {
    if (!function_exists('kangoo_is_99p_product') || !is_array($cart_item)) {
        return false;
    }

    $product_ids = array(
        isset($cart_item['variation_id']) ? absint($cart_item['variation_id']) : 0,
        isset($cart_item['product_id']) ? absint($cart_item['product_id']) : 0,
    );

    if (isset($cart_item['data']) && is_object($cart_item['data']) && method_exists($cart_item['data'], 'get_id')) {
        $product_ids[] = absint($cart_item['data']->get_id());
    }

    foreach (array_filter(array_unique($product_ids)) as $product_id) {
        if (kangoo_is_99p_product($product_id)) {
            return true;
        }
    }

    return false;
}

function kangoo_rewards_get_redemption_eligible_cart_subtotal() {
    if (!function_exists('WC') || !WC()->cart) {
        return 0;
    }

    $subtotal = 0;

    foreach (WC()->cart->get_cart() as $cart_item) {
        if (kangoo_rewards_cart_item_is_99p($cart_item)) {
            continue;
        }

        if (isset($cart_item['line_subtotal'])) {
            $subtotal += max(0, (float) $cart_item['line_subtotal']);
            continue;
        }

        if (isset($cart_item['data']) && is_object($cart_item['data']) && method_exists($cart_item['data'], 'get_price')) {
            $quantity = isset($cart_item['quantity']) ? max(0, (float) $cart_item['quantity']) : 1;
            $subtotal += max(0, (float) $cart_item['data']->get_price() * $quantity);
        }
    }

    return max(0, (float) $subtotal);
}

function kangoo_rewards_get_max_redeemable_points($user_id = null) {
    $balance = kangoo_rewards_get_balance($user_id);
    $cart_subtotal = kangoo_rewards_get_redemption_eligible_cart_subtotal();
    $max_discount = floor($cart_subtotal * kangoo_rewards_max_cart_discount_ratio());
    $max_points_by_cart = kangoo_rewards_money_to_points($max_discount);

    return max(0, min($balance, $max_points_by_cart));
}

function kangoo_rewards_get_session_points() {
    if (!is_user_logged_in()) {
        return 0;
    }

    return max(0, (int) get_user_meta(get_current_user_id(), 'kangoo_rewards_redeem_points', true));
}

function kangoo_rewards_set_session_points($points) {
    if (!is_user_logged_in()) {
        return;
    }

    $points = max(0, (int) $points);

    if (function_exists('WC') && WC()->cart && WC()->cart->has_discount(kangoo_rewards_coupon_code())) {
        WC()->cart->remove_coupon(kangoo_rewards_coupon_code());
    }

    if (function_exists('WC') && WC()->session) {
        WC()->session->set_customer_session_cookie(true);
    }

    if ($points > 0) {
        update_user_meta(get_current_user_id(), 'kangoo_rewards_redeem_points', $points);
        return;
    }

    delete_user_meta(get_current_user_id(), 'kangoo_rewards_redeem_points');
}

function kangoo_rewards_current_url() {
    $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '/';
    return home_url($request_uri);
}

function kangoo_rewards_redirect_url() {
    $redirect = isset($_POST['kangoo_rewards_redirect']) ? esc_url_raw(wp_unslash($_POST['kangoo_rewards_redirect'])) : '';

    if ($redirect) {
        $redirect_host = wp_parse_url($redirect, PHP_URL_HOST);
        $home_host = wp_parse_url(home_url('/'), PHP_URL_HOST);

        if ($redirect_host && $home_host && strtolower($redirect_host) === strtolower($home_host)) {
            return $redirect;
        }
    }

    return wp_get_referer() ? wp_get_referer() : (function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/'));
}

function kangoo_rewards_handle_cart_actions() {
    if (!is_user_logged_in() || empty($_POST['kangoo_rewards_action'])) {
        return;
    }

    if (empty($_POST['kangoo_rewards_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['kangoo_rewards_nonce'])), 'kangoo_rewards_cart')) {
        wc_add_notice(__('Unable to update Kangoo Rewards. Please try again.', 'kangoo'), 'error');
        return;
    }

    $action = sanitize_key(wp_unslash($_POST['kangoo_rewards_action']));

    if ($action === 'remove') {
        kangoo_rewards_set_session_points(0);
        wc_add_notice(__('Kangoo Rewards discount removed.', 'kangoo'), 'notice');
        wp_safe_redirect(kangoo_rewards_redirect_url());
        exit;
    }

    if ($action !== 'apply') {
        return;
    }

    $requested = isset($_POST['kangoo_rewards_points']) ? absint(wp_unslash($_POST['kangoo_rewards_points'])) : 0;
    $requested_discount = isset($_POST['kangoo_rewards_discount']) ? (float) wc_clean(wp_unslash($_POST['kangoo_rewards_discount'])) : 0;
    $result = kangoo_rewards_apply_requested_discount($requested, $requested_discount);

    if (is_wp_error($result)) {
        wc_add_notice($result->get_error_message(), 'error');
        wp_safe_redirect(kangoo_rewards_redirect_url());
        exit;
    }

    wc_add_notice(sprintf(__('Applied %d Kangoo Rewards points.', 'kangoo'), $result['points']), 'success');
    wp_safe_redirect(kangoo_rewards_redirect_url());
    exit;
}
add_action('template_redirect', 'kangoo_rewards_handle_cart_actions', 5);

function kangoo_rewards_virtual_coupon_data($coupon_data, $coupon_code) {
    if (wc_format_coupon_code($coupon_code) !== kangoo_rewards_coupon_code()) {
        return $coupon_data;
    }

    if (!is_user_logged_in()) {
        return false;
    }

    $points = kangoo_rewards_get_session_points();

    if ($points < kangoo_rewards_min_redemption_points()) {
        return false;
    }

    $points = min($points, kangoo_rewards_get_balance(), kangoo_rewards_get_max_redeemable_points(get_current_user_id()));

    if ($points < kangoo_rewards_min_redemption_points()) {
        return false;
    }

    $discount = kangoo_rewards_points_to_money($points);

    if ($discount <= 0) {
        return false;
    }

    return array(
        'id'                         => 0,
        'discount_type'              => 'fixed_cart',
        'amount'                     => $discount,
        'individual_use'             => false,
        'product_ids'                => array(),
        'exclude_product_ids'        => array(),
        'usage_limit'                => 0,
        'usage_limit_per_user'       => 0,
        'limit_usage_to_x_items'     => null,
        'usage_count'                => 0,
        'expiry_date'                => null,
        'free_shipping'              => false,
        'product_categories'         => array(),
        'exclude_product_categories' => array(),
        'exclude_sale_items'         => false,
        'minimum_amount'             => '',
        'maximum_amount'             => '',
        'customer_email'             => array(),
    );
}
add_filter('woocommerce_get_shop_coupon_data', 'kangoo_rewards_virtual_coupon_data', 10, 2);

function kangoo_ajax_rewards_set_coupon_state() {
    check_ajax_referer('kangoo_rewards_ajax', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(array(
            'message' => __('Please sign in to use Kangoo Rewards.', 'kangoo'),
        ), 401);
    }

    $rewards_action = isset($_POST['kangoo_rewards_action']) ? sanitize_key(wp_unslash($_POST['kangoo_rewards_action'])) : '';

    if ($rewards_action === 'remove') {
        kangoo_rewards_set_session_points(0);

        wp_send_json_success(array(
            'action'  => 'remove',
            'message' => __('Kangoo Rewards discount removed.', 'kangoo'),
        ));
    }

    if ($rewards_action !== 'apply') {
        wp_send_json_error(array(
            'message' => __('Unable to update Kangoo Rewards. Please try again.', 'kangoo'),
        ), 400);
    }

    $requested = isset($_POST['kangoo_rewards_points']) ? absint(wp_unslash($_POST['kangoo_rewards_points'])) : 0;
    $requested_discount = isset($_POST['kangoo_rewards_discount']) ? (float) wc_clean(wp_unslash($_POST['kangoo_rewards_discount'])) : 0;

    if ($requested_discount > 0) {
        $requested = kangoo_rewards_money_to_points($requested_discount);
    }

    $result = kangoo_rewards_apply_requested_discount($requested, $requested_discount);

    if (is_wp_error($result)) {
        wp_send_json_error(array(
            'message' => $result->get_error_message(),
        ), 400);
    }

    wp_send_json_success(array(
        'action'   => 'apply',
        'points'   => $result['points'],
        'discount' => $result['discount'],
        'message'  => sprintf(__('Applied %d Kangoo Rewards points.', 'kangoo'), $result['points']),
    ));
}
add_action('wp_ajax_kangoo_rewards_set_coupon_state', 'kangoo_ajax_rewards_set_coupon_state');

function kangoo_rewards_coupon_label($label, $coupon) {
    $coupon_code = is_object($coupon) && method_exists($coupon, 'get_code') ? $coupon->get_code() : (string) $coupon;

    if ($coupon_code && wc_format_coupon_code($coupon_code) === kangoo_rewards_coupon_code()) {
        return __('Kangoo Rewards', 'kangoo');
    }

    return $label;
}
add_filter('woocommerce_cart_totals_coupon_label', 'kangoo_rewards_coupon_label', 10, 2);

function kangoo_rewards_apply_requested_discount($requested, $requested_discount) {
    if (!is_user_logged_in()) {
        return new WP_Error('kangoo_rewards_login_required', __('Please sign in to use Kangoo Rewards.', 'kangoo'));
    }

    $requested = absint($requested);
    $requested_discount = (float) $requested_discount;
    $max_points = kangoo_rewards_get_max_redeemable_points(get_current_user_id());
    $minimum = kangoo_rewards_min_redemption_points();
    $max_discount = kangoo_rewards_points_to_money($max_points);

    if (kangoo_rewards_get_redemption_eligible_cart_subtotal() <= 0) {
        return new WP_Error('kangoo_rewards_99p_only', __('Kangoo Rewards cannot be redeemed against 99p trial pouches. Add an eligible product to use points.', 'kangoo'));
    }

    if ($requested_discount > 0) {
        $requested_discount = min($requested_discount, $max_discount);
        $requested = kangoo_rewards_money_to_points($requested_discount);
    }

    if ($requested < $minimum) {
        return new WP_Error('kangoo_rewards_minimum', sprintf(__('You need at least %d points to redeem a reward.', 'kangoo'), $minimum));
    }

    if ($requested > $max_points) {
        return new WP_Error('kangoo_rewards_maximum', sprintf(__('You can use up to %d Kangoo Rewards points on this order.', 'kangoo'), $max_points));
    }

    $points = min($requested, $max_points);
    $points = kangoo_rewards_money_to_points(kangoo_rewards_points_to_money($points));

    if ($points < $minimum) {
        return new WP_Error('kangoo_rewards_ineligible', __('Your current cart is not eligible for a Kangoo Rewards discount yet.', 'kangoo'));
    }

    kangoo_rewards_set_session_points($points);

    if (function_exists('WC') && WC()->cart) {
        WC()->cart->calculate_totals();
    }

    if (function_exists('WC') && WC()->session) {
        WC()->session->set_customer_session_cookie(true);
    }

    return array(
        'points'   => $points,
        'discount' => kangoo_rewards_points_to_money($points),
    );
}

function kangoo_rewards_validate_session_discount() {
    if (!is_user_logged_in()) {
        kangoo_rewards_set_session_points(0);
        return;
    }

    $points = kangoo_rewards_get_session_points();

    if (!$points) {
        return;
    }

    if (function_exists('WC') && WC()->cart && !WC()->cart->is_empty() && kangoo_rewards_get_redemption_eligible_cart_subtotal() <= 0) {
        kangoo_rewards_set_session_points(0);
        return;
    }

    $max_points = kangoo_rewards_get_max_redeemable_points(get_current_user_id());
    $points = min($points, $max_points);
    $points = kangoo_rewards_money_to_points(kangoo_rewards_points_to_money($points));

    if ($points < kangoo_rewards_min_redemption_points()) {
        kangoo_rewards_set_session_points(0);
        return;
    }

    update_user_meta(get_current_user_id(), 'kangoo_rewards_redeem_points', $points);
}
add_action('woocommerce_before_calculate_totals', 'kangoo_rewards_validate_session_discount', 5);

function kangoo_rewards_apply_cart_discount($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    if (!is_user_logged_in() || !$cart) {
        return;
    }

    $points = kangoo_rewards_get_session_points();

    if ($points < kangoo_rewards_min_redemption_points()) {
        return;
    }

    $points = min(
        $points,
        kangoo_rewards_get_balance(),
        kangoo_rewards_get_max_redeemable_points(get_current_user_id())
    );
    $points = kangoo_rewards_money_to_points(kangoo_rewards_points_to_money($points));

    if ($points < kangoo_rewards_min_redemption_points()) {
        return;
    }

    $discount = kangoo_rewards_points_to_money($points);

    if ($discount <= 0) {
        return;
    }

    $discount = min($discount, kangoo_rewards_get_redemption_eligible_cart_subtotal());

    $cart->add_fee(__('Kangoo Rewards', 'kangoo'), -$discount, false);
}
add_action('woocommerce_cart_calculate_fees', 'kangoo_rewards_apply_cart_discount', 20);

function kangoo_rewards_get_cart_box_html() {
    if (!function_exists('WC') || !WC()->cart) {
        return '';
    }

    ob_start();

    if (!is_user_logged_in()) {
        $earn_points = (int) floor(kangoo_rewards_get_cart_subtotal() * kangoo_rewards_points_per_pound());

        if (is_checkout() && !is_cart()) {
            ?>
            <details class="kangoo-rewards-cart kangoo-rewards-cart--checkout kangoo-rewards-cart--guest">
                <summary>
                    <span class="kangoo-rewards-cart__icon" aria-hidden="true">&#9734;</span>
                    <span class="kangoo-rewards-cart__summary">
                        <?php echo wp_kses_post(sprintf(__('You&rsquo;ll earn <strong>%d points</strong> on this order', 'kangoo'), $earn_points)); ?>
                    </span>
                    <span class="kangoo-rewards-cart__info" aria-hidden="true">i</span>
                    <span class="kangoo-rewards-cart__chevron" aria-hidden="true"></span>
                </summary>
                <p><?php esc_html_e('Account created automatically after checkout.', 'kangoo'); ?></p>
            </details>
            <?php
            return ob_get_clean();
        }

        ?>
        <details class="kangoo-rewards-cart kangoo-rewards-cart--cart kangoo-rewards-cart--guest">
            <summary>
                <span class="kangoo-rewards-cart__icon" aria-hidden="true">&#9734;</span>
                <span class="kangoo-rewards-cart__summary">
                    <?php echo wp_kses_post(sprintf(__('You&rsquo;ll earn <strong>%d points</strong> on this order', 'kangoo'), $earn_points)); ?>
                </span>
                <span class="kangoo-rewards-cart__info" aria-hidden="true">i</span>
                <span class="kangoo-rewards-cart__chevron" aria-hidden="true"></span>
            </summary>
            <p><?php esc_html_e('Account created automatically after checkout.', 'kangoo'); ?></p>
        </details>
        <?php
        return ob_get_clean();
    }

    $balance = kangoo_rewards_get_balance();
    $max_points = kangoo_rewards_get_max_redeemable_points();
    $session_points = kangoo_rewards_get_session_points();
    $earn_points = (int) floor(kangoo_rewards_get_cart_subtotal() * kangoo_rewards_points_per_pound());
    $max_discount = kangoo_rewards_points_to_money($max_points);
    $session_discount = kangoo_rewards_points_to_money($session_points);

    if (is_checkout() && !is_cart()) {
        ?>
        <div class="kangoo-rewards-cart kangoo-rewards-cart--checkout kangoo-rewards-cart--customer">
            <?php if ($session_points >= kangoo_rewards_min_redemption_points()) : ?>
                <form method="post" class="kangoo-rewards-cart__form kangoo-rewards-cart__form--active" data-rewards-form data-max-discount="<?php echo esc_attr($max_discount); ?>">
                    <?php wp_nonce_field('kangoo_rewards_cart', 'kangoo_rewards_nonce'); ?>
                    <input type="hidden" name="kangoo_rewards_action" value="apply">
                    <input type="hidden" name="kangoo_rewards_redirect" value="<?php echo esc_url(kangoo_rewards_current_url()); ?>">
                    <input type="hidden" name="kangoo_rewards_points" value="<?php echo esc_attr($session_points); ?>" data-rewards-points>
                    <div class="kangoo-rewards-cart__identity">
                        <span class="kangoo-rewards-cart__icon" aria-hidden="true">&#9734;</span>
                        <span>
                            <strong><?php esc_html_e('Rewards', 'kangoo'); ?></strong>
                            <small><?php echo esc_html(sprintf(__('%d pts', 'kangoo'), $balance)); ?></small>
                        </span>
                    </div>
                    <label class="screen-reader-text" for="kangoo_rewards_discount_checkout_active"><?php esc_html_e('Reward discount', 'kangoo'); ?></label>
                    <div class="kangoo-rewards-stepper">
                        <button type="button" data-rewards-decrease aria-label="<?php esc_attr_e('Decrease reward discount', 'kangoo'); ?>">-</button>
                        <span>&pound;</span>
                        <input
                            id="kangoo_rewards_discount_checkout_active"
                            type="text"
                            inputmode="numeric"
                            name="kangoo_rewards_discount"
                            value="<?php echo esc_attr($session_discount); ?>"
                            min="<?php echo esc_attr(kangoo_rewards_points_to_money(kangoo_rewards_min_redemption_points())); ?>"
                            max="<?php echo esc_attr($max_discount); ?>"
                            step="1"
                            data-rewards-discount
                        >
                        <button type="button" data-rewards-increase aria-label="<?php esc_attr_e('Increase reward discount', 'kangoo'); ?>">+</button>
                    </div>
                    <button type="submit" class="button" data-rewards-submit>
                        <?php echo esc_html(sprintf(__('Update to %s off', 'kangoo'), kangoo_plain_wc_price($session_discount))); ?>
                    </button>
                    <button type="submit" class="button kangoo-rewards-remove-button" name="kangoo_rewards_action" value="remove">
                        <?php esc_html_e('Remove', 'kangoo'); ?>
                    </button>
                </form>
            <?php elseif ($max_points >= kangoo_rewards_min_redemption_points()) : ?>
                <form method="post" class="kangoo-rewards-cart__form" data-rewards-form data-max-discount="<?php echo esc_attr($max_discount); ?>">
                    <?php wp_nonce_field('kangoo_rewards_cart', 'kangoo_rewards_nonce'); ?>
                    <input type="hidden" name="kangoo_rewards_action" value="apply">
                    <input type="hidden" name="kangoo_rewards_redirect" value="<?php echo esc_url(kangoo_rewards_current_url()); ?>">
                    <input type="hidden" name="kangoo_rewards_points" value="<?php echo esc_attr($max_points); ?>" data-rewards-points>
                    <div class="kangoo-rewards-cart__identity">
                        <span class="kangoo-rewards-cart__icon" aria-hidden="true">&#9734;</span>
                        <span>
                            <strong><?php esc_html_e('Rewards', 'kangoo'); ?></strong>
                            <small><?php echo esc_html(sprintf(__('%d pts', 'kangoo'), $balance)); ?></small>
                        </span>
                    </div>
                    <label class="screen-reader-text" for="kangoo_rewards_discount_checkout"><?php esc_html_e('Reward discount', 'kangoo'); ?></label>
                    <div class="kangoo-rewards-stepper">
                        <button type="button" data-rewards-decrease aria-label="<?php esc_attr_e('Decrease reward discount', 'kangoo'); ?>">-</button>
                        <span>&pound;</span>
                        <input
                            id="kangoo_rewards_discount_checkout"
                            type="text"
                            inputmode="numeric"
                            name="kangoo_rewards_discount"
                            value="<?php echo esc_attr($max_discount); ?>"
                            min="<?php echo esc_attr(kangoo_rewards_points_to_money(kangoo_rewards_min_redemption_points())); ?>"
                            max="<?php echo esc_attr($max_discount); ?>"
                            step="1"
                            data-rewards-discount
                        >
                        <button type="button" data-rewards-increase aria-label="<?php esc_attr_e('Increase reward discount', 'kangoo'); ?>">+</button>
                    </div>
                    <button type="submit" class="button" data-rewards-submit>
                        <?php echo esc_html(sprintf(__('Apply %s off', 'kangoo'), kangoo_plain_wc_price($max_discount))); ?>
                    </button>
                </form>
            <?php else : ?>
                <div class="kangoo-rewards-cart__locked">
                    <span class="kangoo-rewards-cart__icon" aria-hidden="true">&#9734;</span>
                    <span>
                        <?php echo wp_kses_post(sprintf(__('You&rsquo;ll earn <strong>%d points</strong> on this order', 'kangoo'), $earn_points)); ?>
                        <small><?php echo esc_html(sprintf(__('Current balance: %d pts', 'kangoo'), $balance)); ?></small>
                    </span>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    ?>
    <div class="kangoo-rewards-cart kangoo-rewards-cart--cart kangoo-rewards-cart--customer">
        <?php if ($session_points >= kangoo_rewards_min_redemption_points()) : ?>
            <form method="post" class="kangoo-rewards-cart__form kangoo-rewards-cart__form--active" data-rewards-form data-max-discount="<?php echo esc_attr($max_discount); ?>">
                <?php wp_nonce_field('kangoo_rewards_cart', 'kangoo_rewards_nonce'); ?>
                <input type="hidden" name="kangoo_rewards_action" value="apply">
                <input type="hidden" name="kangoo_rewards_redirect" value="<?php echo esc_url(kangoo_rewards_current_url()); ?>">
                <input type="hidden" name="kangoo_rewards_points" value="<?php echo esc_attr($session_points); ?>" data-rewards-points>
                <div class="kangoo-rewards-cart__identity">
                    <span class="kangoo-rewards-cart__icon" aria-hidden="true">&#9734;</span>
                    <span>
                        <strong><?php esc_html_e('Rewards', 'kangoo'); ?></strong>
                        <small><?php echo esc_html(sprintf(__('%d pts', 'kangoo'), $balance)); ?></small>
                    </span>
                </div>
                <label class="screen-reader-text" for="kangoo_rewards_discount_active"><?php esc_html_e('Reward discount', 'kangoo'); ?></label>
                <div class="kangoo-rewards-stepper">
                    <button type="button" data-rewards-decrease aria-label="<?php esc_attr_e('Decrease reward discount', 'kangoo'); ?>">-</button>
                    <span>&pound;</span>
                    <input
                        id="kangoo_rewards_discount_active"
                        type="text"
                        inputmode="numeric"
                        name="kangoo_rewards_discount"
                        value="<?php echo esc_attr($session_discount); ?>"
                        min="<?php echo esc_attr(kangoo_rewards_points_to_money(kangoo_rewards_min_redemption_points())); ?>"
                        max="<?php echo esc_attr($max_discount); ?>"
                        step="1"
                        data-rewards-discount
                    >
                    <button type="button" data-rewards-increase aria-label="<?php esc_attr_e('Increase reward discount', 'kangoo'); ?>">+</button>
                </div>
                <button type="submit" class="button" data-rewards-submit>
                    <?php echo esc_html(sprintf(__('Update to %s off', 'kangoo'), kangoo_plain_wc_price($session_discount))); ?>
                </button>
                <button type="submit" class="button kangoo-rewards-remove-button" name="kangoo_rewards_action" value="remove">
                    <?php esc_html_e('Remove', 'kangoo'); ?>
                </button>
            </form>
        <?php elseif ($max_points >= kangoo_rewards_min_redemption_points()) : ?>
            <form method="post" class="kangoo-rewards-cart__form" data-rewards-form data-max-discount="<?php echo esc_attr($max_discount); ?>">
                <?php wp_nonce_field('kangoo_rewards_cart', 'kangoo_rewards_nonce'); ?>
                <input type="hidden" name="kangoo_rewards_action" value="apply">
                <input type="hidden" name="kangoo_rewards_redirect" value="<?php echo esc_url(kangoo_rewards_current_url()); ?>">
                <input type="hidden" name="kangoo_rewards_points" value="<?php echo esc_attr($max_points); ?>" data-rewards-points>
                <div class="kangoo-rewards-cart__identity">
                    <span class="kangoo-rewards-cart__icon" aria-hidden="true">&#9734;</span>
                    <span>
                        <strong><?php esc_html_e('Rewards', 'kangoo'); ?></strong>
                        <small><?php echo esc_html(sprintf(__('%d pts', 'kangoo'), $balance)); ?></small>
                    </span>
                </div>
                <label class="screen-reader-text" for="kangoo_rewards_discount"><?php esc_html_e('Reward discount', 'kangoo'); ?></label>
                <div class="kangoo-rewards-stepper">
                    <button type="button" data-rewards-decrease aria-label="<?php esc_attr_e('Decrease reward discount', 'kangoo'); ?>">-</button>
                    <span>&pound;</span>
                        <input
                            id="kangoo_rewards_discount"
                            type="text"
                            inputmode="numeric"
                            name="kangoo_rewards_discount"
                        value="<?php echo esc_attr($max_discount); ?>"
                        min="<?php echo esc_attr(kangoo_rewards_points_to_money(kangoo_rewards_min_redemption_points())); ?>"
                        max="<?php echo esc_attr($max_discount); ?>"
                        step="1"
                        data-rewards-discount
                    >
                    <button type="button" data-rewards-increase aria-label="<?php esc_attr_e('Increase reward discount', 'kangoo'); ?>">+</button>
                </div>
                <button type="submit" class="button" data-rewards-submit>
                    <?php echo esc_html(sprintf(__('Apply %s off', 'kangoo'), kangoo_plain_wc_price($max_discount))); ?>
                </button>
            </form>
        <?php else : ?>
            <div class="kangoo-rewards-cart__locked">
                <span class="kangoo-rewards-cart__icon" aria-hidden="true">&#9734;</span>
                <span>
                    <?php echo wp_kses_post(sprintf(__('You&rsquo;ll earn <strong>%d points</strong> on this order', 'kangoo'), $earn_points)); ?>
                    <small><?php echo esc_html(sprintf(__('Current balance: %d pts', 'kangoo'), $balance)); ?></small>
                </span>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

function kangoo_rewards_render_cart_box() {
    echo kangoo_rewards_get_cart_box_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
function kangoo_rewards_prepend_block_page_box($content) {
    if (!is_main_query() || !in_the_loop() || is_admin()) {
        return $content;
    }

    $is_cart_page = function_exists('is_cart') && is_cart();
    $is_checkout_page = function_exists('is_checkout') && is_checkout() && !(function_exists('is_order_received_page') && is_order_received_page());

    if (!$is_cart_page && !$is_checkout_page) {
        return $content;
    }

    if ($is_cart_page) {
        $post = get_post();
        $has_cart_block = $post && function_exists('has_block') && has_block('woocommerce/cart', $post);

        if (!$has_cart_block) {
            return $content;
        }
    }

    return kangoo_rewards_get_cart_box_html() . $content;
}
add_filter('the_content', 'kangoo_rewards_prepend_block_page_box', 8);
add_action('woocommerce_cart_collaterals', 'kangoo_rewards_render_cart_box', 3);

add_filter('woocommerce_order_received_verify_known_shoppers', '__return_false');
add_filter('woocommerce_order_email_verification_required', '__return_false');

function kangoo_rewards_generate_customer_username($email) {
    $base_username = sanitize_user(current(explode('@', $email)), true);
    $base_username = $base_username ? $base_username : 'customer';
    $username = $base_username;
    $suffix = 1;

    while (username_exists($username)) {
        $username = $base_username . $suffix;
        $suffix++;
    }

    return $username;
}

function kangoo_rewards_attach_order_customer($order_id) {
    $order = wc_get_order($order_id);

    if (!$order || (int) $order->get_meta('_kangoo_rewards_customer_user_id') > 0) {
        return;
    }

    $email = sanitize_email($order->get_billing_email());

    if (!$email || !is_email($email)) {
        return;
    }

    $user = get_user_by('email', $email);
    $created_account = false;

    if (!$user) {
        $first_name = sanitize_text_field($order->get_billing_first_name());
        $last_name = sanitize_text_field($order->get_billing_last_name());
        $display_name = trim($first_name . ' ' . $last_name);
        $password = wp_generate_password(24, true);

        $user_id = wp_insert_user(array(
            'user_login'   => kangoo_rewards_generate_customer_username($email),
            'user_pass'    => $password,
            'user_email'   => $email,
            'first_name'   => $first_name,
            'last_name'    => $last_name,
            'display_name' => $display_name ? $display_name : $email,
            'role'         => 'customer',
        ));

        if (is_wp_error($user_id)) {
            return;
        }

        $user = get_user_by('id', $user_id);
        $created_account = true;
    }

    if (!$user) {
        return;
    }

    if ((int) $order->get_user_id() > 0) {
        $order->set_customer_id((int) $user->ID);
    }

    $order->update_meta_data('_kangoo_rewards_customer_user_id', (int) $user->ID);
    $order->update_meta_data('_kangoo_rewards_customer_linked', 1);

    if ($created_account) {
        $order->update_meta_data('_kangoo_rewards_customer_auto_created', 1);
        update_user_meta($user->ID, 'billing_first_name', $order->get_billing_first_name());
        update_user_meta($user->ID, 'billing_last_name', $order->get_billing_last_name());
        update_user_meta($user->ID, 'billing_company', $order->get_billing_company());
        update_user_meta($user->ID, 'billing_address_1', $order->get_billing_address_1());
        update_user_meta($user->ID, 'billing_address_2', $order->get_billing_address_2());
        update_user_meta($user->ID, 'billing_city', $order->get_billing_city());
        update_user_meta($user->ID, 'billing_state', $order->get_billing_state());
        update_user_meta($user->ID, 'billing_postcode', $order->get_billing_postcode());
        update_user_meta($user->ID, 'billing_country', $order->get_billing_country());
        update_user_meta($user->ID, 'billing_phone', $order->get_billing_phone());
        update_user_meta($user->ID, 'billing_email', $email);

        do_action('woocommerce_created_customer', $user->ID, array(
            'user_login' => $user->user_login,
            'user_email' => $email,
            'user_pass'  => $password,
        ), true);
    }

    $dob = $order->get_meta('_kangoo_age_verified_dob');

    if ($dob && kangoo_calculate_age_from_date((string) $dob) >= 18) {
        update_user_meta($user->ID, 'kangoo_date_of_birth', $dob);
    }

    $order->save();
}
add_action('woocommerce_payment_complete', 'kangoo_rewards_attach_order_customer', 5);
add_action('woocommerce_order_status_processing', 'kangoo_rewards_attach_order_customer', 5);
add_action('woocommerce_order_status_completed', 'kangoo_rewards_attach_order_customer', 5);

function kangoo_rewards_get_order_user_id($order) {
    if (!$order || !is_a($order, 'WC_Order')) {
        return 0;
    }

    $user_id = (int) $order->get_user_id();

    if ($user_id > 0) {
        return $user_id;
    }

    $linked_user_id = (int) $order->get_meta('_kangoo_rewards_customer_user_id');

    if ($linked_user_id > 0) {
        return $linked_user_id;
    }

    $email = sanitize_email($order->get_billing_email());

    if ($email && is_email($email)) {
        $user = get_user_by('email', $email);
        return $user ? (int) $user->ID : 0;
    }

    return 0;
}

function kangoo_rewards_award_order_points($order_id) {
    $order = wc_get_order($order_id);

    if (!$order || $order->get_meta('_kangoo_rewards_points_awarded')) {
        return;
    }

    $user_id = kangoo_rewards_get_order_user_id($order);

    if (!$user_id) {
        return;
    }

    $eligible_total = max(0, (float) $order->get_subtotal() - (float) $order->get_discount_total());
    $points = (int) floor($eligible_total * kangoo_rewards_points_per_pound());

    if ($points <= 0) {
        return;
    }

    kangoo_rewards_adjust_points($user_id, $points, sprintf(__('Order #%d reward', 'kangoo'), $order_id), $order_id);
    $order->update_meta_data('_kangoo_rewards_points_awarded', $points);

    if (!get_user_meta($user_id, 'kangoo_rewards_first_order_bonus_awarded', true)) {
        kangoo_rewards_adjust_points($user_id, 200, __('First order bonus', 'kangoo'), $order_id);
        update_user_meta($user_id, 'kangoo_rewards_first_order_bonus_awarded', 1);
        $order->update_meta_data('_kangoo_rewards_first_order_bonus_awarded', 200);
    }

    $order->save();
}
add_action('woocommerce_order_status_completed', 'kangoo_rewards_award_order_points');

function kangoo_rewards_redeem_order_points($order_id) {
    $order = wc_get_order($order_id);

    if (!$order || $order->get_meta('_kangoo_rewards_points_redeemed')) {
        return;
    }

    $user_id = kangoo_rewards_get_order_user_id($order);
    $points = kangoo_rewards_get_session_points();

    if (!$user_id || $points < kangoo_rewards_min_redemption_points()) {
        return;
    }

    $points = min($points, kangoo_rewards_get_balance($user_id));

    if ($points < kangoo_rewards_min_redemption_points()) {
        return;
    }

    kangoo_rewards_adjust_points($user_id, -$points, sprintf(__('Redeemed on order #%d', 'kangoo'), $order_id), $order_id);
    $order->update_meta_data('_kangoo_rewards_points_redeemed', $points);
    $order->save();
    kangoo_rewards_set_session_points(0);
}
add_action('woocommerce_checkout_order_processed', 'kangoo_rewards_redeem_order_points', 20);

function kangoo_rewards_return_order_points($order_id) {
    $order = wc_get_order($order_id);

    if (!$order || $order->get_meta('_kangoo_rewards_points_returned')) {
        return;
    }

    $user_id = (int) $order->get_user_id();

    if (!$user_id) {
        return;
    }

    $awarded = (int) $order->get_meta('_kangoo_rewards_points_awarded');
    $redeemed = (int) $order->get_meta('_kangoo_rewards_points_redeemed');
    $first_order_bonus = (int) $order->get_meta('_kangoo_rewards_first_order_bonus_awarded');

    if ($awarded > 0) {
        kangoo_rewards_adjust_points($user_id, -$awarded, sprintf(__('Order #%d points reversed', 'kangoo'), $order_id), $order_id);
    }

    if ($first_order_bonus > 0) {
        kangoo_rewards_adjust_points($user_id, -$first_order_bonus, sprintf(__('Order #%d first order bonus reversed', 'kangoo'), $order_id), $order_id);
        delete_user_meta($user_id, 'kangoo_rewards_first_order_bonus_awarded');
    }

    if ($redeemed > 0) {
        kangoo_rewards_adjust_points($user_id, $redeemed, sprintf(__('Order #%d reward returned', 'kangoo'), $order_id), $order_id);
    }

    $order->update_meta_data('_kangoo_rewards_points_returned', 1);
    $order->save();
}
add_action('woocommerce_order_status_cancelled', 'kangoo_rewards_return_order_points');
add_action('woocommerce_order_status_refunded', 'kangoo_rewards_return_order_points');
add_action('woocommerce_order_status_failed', 'kangoo_rewards_return_order_points');

function kangoo_rewards_user_register_bonus($user_id) {
    if (!$user_id || get_user_meta($user_id, 'kangoo_rewards_signup_bonus_awarded', true)) {
        return;
    }

    kangoo_rewards_adjust_points($user_id, 100, __('Account welcome bonus', 'kangoo'));
    update_user_meta($user_id, 'kangoo_rewards_signup_bonus_awarded', 1);
}
add_action('user_register', 'kangoo_rewards_user_register_bonus');

function kangoo_rewards_add_account_endpoint() {
    add_rewrite_endpoint('rewards', EP_ROOT | EP_PAGES);
}
add_action('init', 'kangoo_rewards_add_account_endpoint');

function kangoo_rewards_account_menu_items($items) {
    $new_items = array();

    foreach ($items as $key => $label) {
        if ($key === 'customer-logout') {
            $new_items['rewards'] = __('Rewards', 'kangoo');
        }

        $new_items[$key] = $label;
    }

    return $new_items;
}
add_filter('woocommerce_account_menu_items', 'kangoo_rewards_account_menu_items', 20);

function kangoo_rewards_account_endpoint_content() {
    $balance = kangoo_rewards_get_balance();
    $history = kangoo_rewards_get_history();
    ?>
    <div class="kangoo-rewards-dashboard">
        <section class="kangoo-rewards-hero">
            <span><?php esc_html_e('Kangoo Rewards', 'kangoo'); ?></span>
            <h2><?php echo esc_html(sprintf(__('%d points', 'kangoo'), $balance)); ?></h2>
            <p><?php echo esc_html(sprintf(__('Worth %s off when eligible at checkout.', 'kangoo'), kangoo_plain_wc_price(kangoo_rewards_points_to_money($balance)))); ?></p>
            <a class="button" href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/')); ?>">
                <?php esc_html_e('Shop and earn', 'kangoo'); ?>
            </a>
        </section>

        <section class="kangoo-rewards-rules">
            <h3><?php esc_html_e('How it works', 'kangoo'); ?></h3>
            <div>
                <article>
                    <strong><?php esc_html_e('Earn', 'kangoo'); ?></strong>
                    <p><?php esc_html_e('Earn 1 point for every £1 spent once your order is completed.', 'kangoo'); ?></p>
                </article>
                <article>
                    <strong><?php esc_html_e('Redeem', 'kangoo'); ?></strong>
                    <p><?php esc_html_e('100 points equals £1 off. Rewards can cover up to 20% of eligible cart value.', 'kangoo'); ?></p>
                </article>
                <article>
                    <strong><?php esc_html_e('Protect', 'kangoo'); ?></strong>
                    <p><?php esc_html_e('Cancelled or refunded orders automatically reverse related rewards.', 'kangoo'); ?></p>
                </article>
            </div>
        </section>

        <section class="kangoo-rewards-history">
            <h3><?php esc_html_e('Points history', 'kangoo'); ?></h3>
            <?php if (!empty($history)) : ?>
                <table>
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Date', 'kangoo'); ?></th>
                            <th><?php esc_html_e('Activity', 'kangoo'); ?></th>
                            <th><?php esc_html_e('Points', 'kangoo'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $entry) : ?>
                            <tr>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($entry['date']))); ?></td>
                                <td><?php echo esc_html($entry['label']); ?></td>
                                <td><?php echo esc_html(((int) $entry['points'] > 0 ? '+' : '') . (int) $entry['points']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php esc_html_e('Your points history will appear here after your first reward activity.', 'kangoo'); ?></p>
            <?php endif; ?>
        </section>
    </div>
    <?php
}
add_action('woocommerce_account_rewards_endpoint', 'kangoo_rewards_account_endpoint_content');

function kangoo_rewards_account_endpoint_content_modern() {
    $balance = kangoo_rewards_get_balance();
    $history = kangoo_rewards_get_history();
    $reward_value = kangoo_rewards_points_to_money($balance);
    $next_reward_points = max(500, (int) ceil(max(1, $balance + 1) / 100) * 100);
    $remaining_points = max(0, $next_reward_points - $balance);
    $progress = $next_reward_points > 0 ? min(100, max(0, ($balance / $next_reward_points) * 100)) : 0;
    ?>
    <div class="kangoo-rewards-dashboard">
        <header class="kangoo-account-hero kangoo-account-hero--rewards">
            <span class="kangoo-account-hero__icon" aria-hidden="true"></span>
            <div>
                <h1><?php esc_html_e('Kangoo Rewards', 'kangoo'); ?></h1>
                <p><?php esc_html_e('Earn points every time you shop and redeem them for discounts on your next eligible order.', 'kangoo'); ?></p>
            </div>
        </header>

        <section class="kangoo-rewards-hero">
            <div class="kangoo-rewards-hero__balance">
                <span><?php esc_html_e('Your points balance', 'kangoo'); ?></span>
                <h2><?php echo esc_html(sprintf(__('%d points', 'kangoo'), $balance)); ?></h2>
                <p><?php echo esc_html(sprintf(__('Worth %s off when eligible at checkout', 'kangoo'), kangoo_plain_wc_price($reward_value))); ?></p>
            </div>
            <div class="kangoo-rewards-hero__progress">
                <div>
                    <strong><?php esc_html_e('How close are you to your next reward?', 'kangoo'); ?></strong>
                    <span><?php echo esc_html(sprintf(__('%d points', 'kangoo'), $next_reward_points)); ?></span>
                </div>
                <p><?php echo esc_html(sprintf(__('%d points to %s off', 'kangoo'), $remaining_points, kangoo_plain_wc_price(kangoo_rewards_points_to_money($next_reward_points)))); ?></p>
                <div class="kangoo-rewards-progress" aria-hidden="true"><span style="width: <?php echo esc_attr(number_format($progress, 2, '.', '')); ?>%"></span></div>
                <a class="button" href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/')); ?>">
                    <?php esc_html_e('Shop and earn', 'kangoo'); ?>
                </a>
            </div>
        </section>

        <section class="kangoo-rewards-rules">
            <h3><?php esc_html_e('How it works', 'kangoo'); ?></h3>
            <div>
                <article>
                    <span aria-hidden="true"></span>
                    <strong><?php esc_html_e('Earn', 'kangoo'); ?></strong>
                    <p><?php echo wp_kses_post(__('Earn 1 point for every &pound;1 spent once your order is completed.', 'kangoo')); ?></p>
                </article>
                <article>
                    <span aria-hidden="true"></span>
                    <strong><?php esc_html_e('Redeem', 'kangoo'); ?></strong>
                    <p><?php echo wp_kses_post(__('100 points equals &pound;1 off. Rewards can cover up to 20% of eligible cart value. 99p trial pouches are excluded from redemption.', 'kangoo')); ?></p>
                </article>
                <article>
                    <span aria-hidden="true"></span>
                    <strong><?php esc_html_e('Protect', 'kangoo'); ?></strong>
                    <p><?php esc_html_e('Cancelled or refunded orders automatically reverse related rewards.', 'kangoo'); ?></p>
                </article>
            </div>
        </section>

        <section class="kangoo-rewards-history">
            <div class="kangoo-account-section-heading">
                <h3><?php esc_html_e('Points history', 'kangoo'); ?></h3>
                <?php if (!empty($history) && count($history) > 5) : ?>
                    <a href="<?php echo esc_url(wc_get_account_endpoint_url('rewards')); ?>"><?php esc_html_e('View all history', 'kangoo'); ?> <span aria-hidden="true">-&gt;</span></a>
                <?php endif; ?>
            </div>
            <?php if (!empty($history)) : ?>
                <table>
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Date', 'kangoo'); ?></th>
                            <th><?php esc_html_e('Activity', 'kangoo'); ?></th>
                            <th><?php esc_html_e('Points', 'kangoo'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($history, 0, 5) as $entry) : ?>
                            <tr>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($entry['date']))); ?></td>
                                <td>
                                    <?php echo esc_html($entry['label']); ?>
                                    <?php if ((int) $entry['points'] > 0) : ?>
                                        <span class="kangoo-rewards-badge kangoo-rewards-badge--earned"><?php esc_html_e('Earned', 'kangoo'); ?></span>
                                    <?php else : ?>
                                        <span class="kangoo-rewards-badge"><?php esc_html_e('Used', 'kangoo'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html(((int) $entry['points'] > 0 ? '+' : '') . (int) $entry['points']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php esc_html_e('Your points history will appear here after your first reward activity.', 'kangoo'); ?></p>
            <?php endif; ?>
        </section>

        <section class="kangoo-rewards-help">
            <span aria-hidden="true">?</span>
            <div>
                <strong><?php esc_html_e('Have questions about your points?', 'kangoo'); ?></strong>
                <p><?php esc_html_e('Check out our FAQ or contact our support team.', 'kangoo'); ?></p>
            </div>
            <a class="button" href="<?php echo esc_url(home_url('/faq/')); ?>"><?php esc_html_e('View FAQ', 'kangoo'); ?></a>
        </section>
    </div>
    <?php
}
remove_action('woocommerce_account_rewards_endpoint', 'kangoo_rewards_account_endpoint_content');
add_action('woocommerce_account_rewards_endpoint', 'kangoo_rewards_account_endpoint_content_modern');

/* =========================================================================
KANGOO REFERRALS
========================================================================= */

function kangoo_referrals_endpoint_slug() {
    return 'refer-a-friend';
}

function kangoo_referrals_reward_amount() {
    return 10.0;
}

function kangoo_referrals_qualification_spend() {
    return 30.0;
}

function kangoo_referrals_friend_discount_percent() {
    return 15;
}

function kangoo_referrals_friend_discount_minimum_spend() {
    return 20.0;
}

function kangoo_referrals_add_account_endpoint() {
    add_rewrite_endpoint(kangoo_referrals_endpoint_slug(), EP_ROOT | EP_PAGES);
    add_rewrite_rule('^r/([A-Za-z0-9]+)/?$', 'index.php?kangoo_referral_code=$matches[1]', 'top');
}
add_action('init', 'kangoo_referrals_add_account_endpoint');

function kangoo_referrals_query_vars($vars) {
    $vars[] = 'kangoo_referral_code';

    return $vars;
}
add_filter('query_vars', 'kangoo_referrals_query_vars');

function kangoo_referrals_normalize_code($code) {
    $code = strtoupper(sanitize_text_field((string) $code));

    return preg_replace('/[^A-Z0-9]/', '', $code);
}

function kangoo_referrals_find_referrer_by_code($code) {
    $code = kangoo_referrals_normalize_code($code);

    if (!$code) {
        return 0;
    }

    $users = get_users(array(
        'meta_key'   => 'kangoo_referral_code',
        'meta_value' => $code,
        'number'     => 1,
        'fields'     => 'ID',
    ));

    return !empty($users) ? absint($users[0]) : 0;
}

function kangoo_referrals_generate_code($user_id) {
    $user_id = absint($user_id);

    if (!$user_id) {
        return '';
    }

    $existing = kangoo_referrals_normalize_code(get_user_meta($user_id, 'kangoo_referral_code', true));
    $existing_owner = $existing ? kangoo_referrals_find_referrer_by_code($existing) : 0;

    if ($existing && (!$existing_owner || $existing_owner === $user_id)) {
        if (!$existing_owner) {
            update_user_meta($user_id, 'kangoo_referral_code', $existing);
        }

        return $existing;
    }

    for ($attempt = 0; $attempt < 25; $attempt++) {
        $code = kangoo_referrals_normalize_code(wp_generate_password(6, false, false));

        if (strlen($code) < 6) {
            $code = kangoo_referrals_normalize_code(base_convert($user_id, 10, 36) . wp_generate_password(6, false, false));
            $code = substr($code, 0, 6);
        }

        if (!$code || strlen($code) < 6) {
            continue;
        }

        $owner = kangoo_referrals_find_referrer_by_code($code);

        if (!$owner || $owner === $user_id) {
            update_user_meta($user_id, 'kangoo_referral_code', $code);

            return $code;
        }
    }

    $fallback = kangoo_referrals_normalize_code('KP' . base_convert($user_id, 10, 36) . substr(wp_hash($user_id . time()), 0, 4));
    $fallback = substr($fallback, 0, 10);
    update_user_meta($user_id, 'kangoo_referral_code', $fallback);

    return $fallback;
}

function kangoo_referrals_get_code($user_id = null) {
    $user_id = $user_id ? absint($user_id) : get_current_user_id();

    if (!$user_id) {
        return '';
    }

    return kangoo_referrals_generate_code($user_id);
}

function kangoo_referrals_get_link($user_id = null) {
    $code = kangoo_referrals_get_code($user_id);

    return $code ? home_url('/r/' . rawurlencode($code) . '/') : '';
}

function kangoo_referrals_set_active_code($code) {
    $code = kangoo_referrals_normalize_code($code);

    if (!$code) {
        return;
    }

    if (function_exists('WC') && WC()->session) {
        WC()->session->set('kangoo_referral_code', $code);
    }

    if (function_exists('wc_setcookie')) {
        wc_setcookie('kangoo_referral_code', $code, time() + MONTH_IN_SECONDS, is_ssl(), false);
    }
}

function kangoo_referrals_clear_active_code() {
    if (function_exists('WC') && WC()->session) {
        WC()->session->__unset('kangoo_referral_code');
    }

    if (function_exists('wc_setcookie')) {
        wc_setcookie('kangoo_referral_code', '', time() - HOUR_IN_SECONDS, is_ssl(), false);
    }
}

function kangoo_referrals_get_active_code() {
    $code = '';

    if (function_exists('WC') && WC()->session) {
        $code = kangoo_referrals_normalize_code(WC()->session->get('kangoo_referral_code'));
    }

    if (!$code && !empty($_COOKIE['kangoo_referral_code'])) {
        $code = kangoo_referrals_normalize_code(wp_unslash($_COOKIE['kangoo_referral_code']));
    }

    return $code;
}

function kangoo_referrals_get_validation_email() {
    $email = '';

    if (!empty($_POST['billing_email'])) {
        $email = sanitize_email(wp_unslash($_POST['billing_email']));
    }

    if (!$email && function_exists('WC') && WC()->customer) {
        $email = sanitize_email(WC()->customer->get_billing_email());
    }

    if (!$email && function_exists('kangoo_get_saved_checkout_email')) {
        $email = sanitize_email(kangoo_get_saved_checkout_email());
    }

    if (!$email && is_user_logged_in()) {
        $user = wp_get_current_user();
        $email = $user ? sanitize_email($user->user_email) : '';
    }

    return $email;
}

function kangoo_referrals_is_coupon_code($coupon_code) {
    return (bool) kangoo_referrals_find_referrer_by_code($coupon_code);
}

function kangoo_referrals_handle_landing() {
    $code = get_query_var('kangoo_referral_code');

    if (!$code) {
        return;
    }

    $code = kangoo_referrals_normalize_code($code);
    $referrer_id = kangoo_referrals_find_referrer_by_code($code);

    if ($referrer_id) {
        kangoo_referrals_set_active_code($code);

        if (function_exists('wc_add_notice')) {
            wc_add_notice(sprintf(__('Your %d%% friend discount is ready for your first order.', 'kangoo'), kangoo_referrals_friend_discount_percent()), 'success');
        }
    } elseif (function_exists('wc_add_notice')) {
        wc_add_notice(__('That referral link is not active. Please check the code and try again.', 'kangoo'), 'notice');
    }

    wp_safe_redirect(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/'));
    exit;
}
add_action('template_redirect', 'kangoo_referrals_handle_landing', 4);

function kangoo_referrals_customer_has_prior_orders($user_id = 0, $email = '') {
    if (!function_exists('wc_get_orders')) {
        return false;
    }

    $statuses = array('pending', 'processing', 'on-hold', 'completed');
    $base_args = array(
        'limit'  => 1,
        'return' => 'ids',
        'status' => $statuses,
    );

    if ($user_id) {
        $orders = wc_get_orders(array_merge($base_args, array(
            'customer_id' => absint($user_id),
        )));

        if (!empty($orders)) {
            return true;
        }
    }

    $email = sanitize_email($email);

    if ($email) {
        $orders = wc_get_orders(array_merge($base_args, array(
            'billing_email' => $email,
        )));

        if (!empty($orders)) {
            return true;
        }
    }

    return false;
}

function kangoo_referrals_is_self_referral($referrer_id, $email = '') {
    $referrer_id = absint($referrer_id);

    if (!$referrer_id) {
        return false;
    }

    if (is_user_logged_in() && get_current_user_id() === $referrer_id) {
        return true;
    }

    $email = sanitize_email($email);

    if ($email) {
        $email_user = get_user_by('email', $email);

        if ($email_user && (int) $email_user->ID === $referrer_id) {
            return true;
        }
    }

    return false;
}

function kangoo_referrals_can_apply_code($code, $email = '') {
    $code = kangoo_referrals_normalize_code($code);
    $referrer_id = kangoo_referrals_find_referrer_by_code($code);

    if (!$code || !$referrer_id || kangoo_referrals_is_self_referral($referrer_id, $email)) {
        return false;
    }

    $user_id = is_user_logged_in() ? get_current_user_id() : 0;

    if (kangoo_referrals_customer_has_prior_orders($user_id, $email)) {
        return false;
    }

    return true;
}

function kangoo_referrals_can_apply_discount($email = '') {
    return kangoo_referrals_can_apply_code(kangoo_referrals_get_active_code(), $email);
}

function kangoo_referrals_virtual_coupon_data($coupon_data, $coupon_code) {
    $code = kangoo_referrals_normalize_code($coupon_code);

    if (!$code || !kangoo_referrals_find_referrer_by_code($code)) {
        return $coupon_data;
    }

    return array(
        'id'                         => 0,
        'discount_type'              => 'percent',
        'amount'                     => kangoo_referrals_friend_discount_percent(),
        'individual_use'             => true,
        'product_ids'                => array(),
        'exclude_product_ids'        => array(),
        'usage_limit'                => 0,
        'usage_limit_per_user'       => 0,
        'limit_usage_to_x_items'     => null,
        'usage_count'                => 0,
        'expiry_date'                => null,
        'free_shipping'              => false,
        'product_categories'         => array(),
        'exclude_product_categories' => array(),
        'exclude_sale_items'         => true,
        'minimum_amount'             => kangoo_referrals_friend_discount_minimum_spend(),
        'maximum_amount'             => '',
        'customer_email'             => array(),
    );
}
add_filter('woocommerce_get_shop_coupon_data', 'kangoo_referrals_virtual_coupon_data', 15, 2);

function kangoo_referrals_validate_coupon($valid, $coupon, $discounts = null) {
    $code = is_object($coupon) && method_exists($coupon, 'get_code') ? $coupon->get_code() : (string) $coupon;

    if (!kangoo_referrals_is_coupon_code($code)) {
        return $valid;
    }

    if (!$valid) {
        return $valid;
    }

    return kangoo_referrals_can_apply_code($code, kangoo_referrals_get_validation_email());
}
add_filter('woocommerce_coupon_is_valid', 'kangoo_referrals_validate_coupon', 10, 3);

function kangoo_referrals_coupon_error_message($message, $error_code, $coupon) {
    $code = is_object($coupon) && method_exists($coupon, 'get_code') ? $coupon->get_code() : (string) $coupon;

    if (!kangoo_referrals_is_coupon_code($code)) {
        return $message;
    }

    if (function_exists('WC') && WC()->cart) {
        foreach (WC()->cart->get_applied_coupons() as $coupon_code) {
            $coupon_code = kangoo_referrals_normalize_code($coupon_code);

            if ($coupon_code && $coupon_code !== kangoo_referrals_normalize_code($code)) {
                return __('Referral codes cannot be combined with other discounts.', 'kangoo');
            }
        }

        if ((float) WC()->cart->get_subtotal() < kangoo_referrals_friend_discount_minimum_spend()) {
            return sprintf(__('Referral codes can be used on first orders of %s or more.', 'kangoo'), kangoo_plain_wc_price(kangoo_referrals_friend_discount_minimum_spend()));
        }
    }

    $email = kangoo_referrals_get_validation_email();
    $referrer_id = kangoo_referrals_find_referrer_by_code($code);

    if (kangoo_referrals_is_self_referral($referrer_id, $email)) {
        return __('Referral codes cannot be used on your own account.', 'kangoo');
    }

    $user_id = is_user_logged_in() ? get_current_user_id() : 0;

    if (kangoo_referrals_customer_has_prior_orders($user_id, $email)) {
        return __('Referral codes are for a referred customer\'s first order only.', 'kangoo');
    }

    return __('This referral code is not eligible for this order.', 'kangoo');
}
add_filter('woocommerce_coupon_error', 'kangoo_referrals_coupon_error_message', 10, 3);

function kangoo_referrals_coupon_valid_for_product($valid, $product, $coupon, $values = array()) {
    $code = is_object($coupon) && method_exists($coupon, 'get_code') ? $coupon->get_code() : (string) $coupon;

    if (!$valid || !kangoo_referrals_is_coupon_code($code) || !function_exists('kangoo_is_99p_product')) {
        return $valid;
    }

    $product_ids = array();

    if ($product instanceof WC_Product) {
        $product_ids[] = $product->get_id();

        if ($product->get_parent_id()) {
            $product_ids[] = $product->get_parent_id();
        }
    }

    if (is_array($values)) {
        $product_ids[] = isset($values['product_id']) ? absint($values['product_id']) : 0;
        $product_ids[] = isset($values['variation_id']) ? absint($values['variation_id']) : 0;
    }

    foreach (array_filter(array_unique($product_ids)) as $product_id) {
        if (kangoo_is_99p_product($product_id)) {
            return false;
        }
    }

    return $valid;
}
add_filter('woocommerce_coupon_is_valid_for_product', 'kangoo_referrals_coupon_valid_for_product', 10, 4);

function kangoo_referrals_coupon_label($label, $coupon) {
    $code = is_object($coupon) && method_exists($coupon, 'get_code') ? $coupon->get_code() : (string) $coupon;

    if (kangoo_referrals_is_coupon_code($code)) {
        return sprintf(__('Referral code (%d%% off)', 'kangoo'), kangoo_referrals_friend_discount_percent());
    }

    return $label;
}
add_filter('woocommerce_cart_totals_coupon_label', 'kangoo_referrals_coupon_label', 20, 2);

function kangoo_referrals_track_coupon($coupon_code) {
    if (kangoo_referrals_is_coupon_code($coupon_code)) {
        kangoo_referrals_set_active_code($coupon_code);
    }
}
add_action('woocommerce_applied_coupon', 'kangoo_referrals_track_coupon');

function kangoo_referrals_clear_removed_coupon($coupon_code) {
    $code = kangoo_referrals_normalize_code($coupon_code);

    if ($code && $code === kangoo_referrals_get_active_code()) {
        kangoo_referrals_clear_active_code();
    }
}
add_action('woocommerce_removed_coupon', 'kangoo_referrals_clear_removed_coupon');

function kangoo_referrals_apply_active_coupon() {
    if ((is_admin() && !wp_doing_ajax()) || !function_exists('WC')) {
        return;
    }

    if (function_exists('kangoo_get_removed_coupon_code') && kangoo_get_removed_coupon_code()) {
        return;
    }

    if (function_exists('wc_load_cart') && (!WC()->cart || !WC()->session)) {
        wc_load_cart();
    }

    if (!WC()->cart || !WC()->session || WC()->cart->is_empty()) {
        return;
    }

    $code = kangoo_referrals_get_active_code();

    if (!$code || !kangoo_referrals_is_coupon_code($code) || WC()->cart->has_discount($code)) {
        return;
    }

    if (!kangoo_referrals_can_apply_code($code, kangoo_referrals_get_validation_email())) {
        kangoo_referrals_clear_active_code();
        return;
    }

    WC()->session->set_customer_session_cookie(true);
    kangoo_apply_coupon_without_customer_notices($code);
}
add_action('woocommerce_cart_loaded_from_session', 'kangoo_referrals_apply_active_coupon', 25);
add_action('woocommerce_before_calculate_totals', 'kangoo_referrals_apply_active_coupon', 6);
add_action('wp_loaded', 'kangoo_referrals_apply_active_coupon', 35);

function kangoo_referrals_validate_checkout($data, $errors) {
    $code = kangoo_referrals_get_active_code();

    if (!$code && function_exists('WC') && WC()->cart) {
        foreach (WC()->cart->get_applied_coupons() as $coupon_code) {
            if (kangoo_referrals_is_coupon_code($coupon_code)) {
                $code = kangoo_referrals_normalize_code($coupon_code);
                break;
            }
        }
    }

    if (!$code) {
        return;
    }

    $referrer_id = kangoo_referrals_find_referrer_by_code($code);
    $email = isset($data['billing_email']) ? sanitize_email($data['billing_email']) : '';

    if (!$referrer_id) {
        $errors->add('kangoo_referral_invalid', __('This referral code is no longer active.', 'kangoo'));
        return;
    }

    if (kangoo_referrals_is_self_referral($referrer_id, $email)) {
        $errors->add('kangoo_referral_self', __('Referral rewards cannot be used on your own account.', 'kangoo'));
        return;
    }

    $email_user = $email ? get_user_by('email', $email) : null;
    $user_id = $email_user ? (int) $email_user->ID : (is_user_logged_in() ? get_current_user_id() : 0);

    if (kangoo_referrals_customer_has_prior_orders($user_id, $email)) {
        $errors->add('kangoo_referral_existing_customer', __('Referral discounts are available on a referred customer\'s first order only.', 'kangoo'));
    }
}
add_action('woocommerce_after_checkout_validation', 'kangoo_referrals_validate_checkout', 10, 2);

function kangoo_referrals_attach_order_meta($order, $data) {
    if (!$order || !is_a($order, 'WC_Order')) {
        return;
    }

    $code = kangoo_referrals_get_active_code();

    if (!$code && function_exists('WC') && WC()->cart) {
        foreach (WC()->cart->get_applied_coupons() as $coupon_code) {
            if (kangoo_referrals_is_coupon_code($coupon_code)) {
                $code = kangoo_referrals_normalize_code($coupon_code);
                break;
            }
        }
    }

    $referrer_id = kangoo_referrals_find_referrer_by_code($code);
    $email = isset($data['billing_email']) ? sanitize_email($data['billing_email']) : sanitize_email($order->get_billing_email());

    if (!$code || !$referrer_id || !kangoo_referrals_can_apply_code($code, $email)) {
        return;
    }

    $order->update_meta_data('_kangoo_referral_code', $code);
    $order->update_meta_data('_kangoo_referrer_user_id', $referrer_id);
    $order->update_meta_data('_kangoo_referral_discount_percent', kangoo_referrals_friend_discount_percent());
    $order->update_meta_data('_kangoo_referral_reward_amount', kangoo_referrals_reward_amount());
}
add_action('woocommerce_checkout_create_order', 'kangoo_referrals_attach_order_meta', 20, 2);

function kangoo_referrals_clear_code_after_order($order_id) {
    $order = function_exists('wc_get_order') ? wc_get_order($order_id) : null;

    if ($order && $order->get_meta('_kangoo_referral_code')) {
        kangoo_referrals_clear_active_code();
    }
}
add_action('woocommerce_checkout_order_processed', 'kangoo_referrals_clear_code_after_order', 20);

function kangoo_referrals_record_defaults($referrer_id = 0) {
    return array(
        'referral_code'            => '',
        'referral_link'            => '',
        'referrer_user_id'         => absint($referrer_id),
        'referred_user_id'         => 0,
        'referred_customer_email'  => '',
        'referred_customer_name'   => '',
        'completed_spend_total'    => 0.0,
        'reward_status'            => 'pending',
        'reward_amount'            => kangoo_referrals_reward_amount(),
        'first_order_date'         => '',
        'qualified_date'           => '',
        'paid_date'                => '',
        'is_placeholder'           => false,
    );
}

function kangoo_referrals_prepare_record($record, $referrer_id = 0) {
    $record = is_array($record) ? wp_parse_args($record, kangoo_referrals_record_defaults($referrer_id)) : kangoo_referrals_record_defaults($referrer_id);
    $allowed_statuses = array('pending', 'qualified', 'paid', 'rejected');
    $status = sanitize_key($record['reward_status']);

    $record['referrer_user_id'] = absint($record['referrer_user_id']);
    $record['referred_user_id'] = absint($record['referred_user_id']);
    $record['referred_customer_email'] = sanitize_email($record['referred_customer_email']);
    $record['referred_customer_name'] = sanitize_text_field($record['referred_customer_name']);
    $record['completed_spend_total'] = max(0, (float) $record['completed_spend_total']);
    $record['reward_status'] = in_array($status, $allowed_statuses, true) ? $status : 'pending';
    $record['reward_amount'] = max(0, (float) $record['reward_amount']);
    $record['first_order_date'] = sanitize_text_field($record['first_order_date']);
    $record['qualified_date'] = sanitize_text_field($record['qualified_date']);
    $record['paid_date'] = sanitize_text_field($record['paid_date']);
    $record['is_placeholder'] = !empty($record['is_placeholder']);

    return $record;
}

function kangoo_referrals_record_storage_key($record) {
    $record = is_array($record) ? $record : array();
    $user_id = !empty($record['referred_user_id']) ? absint($record['referred_user_id']) : 0;

    if ($user_id) {
        return 'user_' . $user_id;
    }

    $email = !empty($record['referred_customer_email']) ? sanitize_email($record['referred_customer_email']) : '';

    if ($email) {
        return 'email_' . md5(strtolower($email));
    }

    return 'guest_' . md5(wp_json_encode($record));
}

function kangoo_referrals_get_real_records($user_id = null) {
    $user_id = $user_id ? absint($user_id) : get_current_user_id();

    if (!$user_id) {
        return array();
    }

    $records = get_user_meta($user_id, 'kangoo_referral_records', true);

    if (!is_array($records)) {
        return array();
    }

    $prepared = array();

    foreach ($records as $record) {
        $prepared[] = kangoo_referrals_prepare_record($record, $user_id);
    }

    usort($prepared, function ($a, $b) {
        return strtotime($b['first_order_date']) <=> strtotime($a['first_order_date']);
    });

    return $prepared;
}

function kangoo_referrals_get_records($user_id = null) {
    $records = kangoo_referrals_get_real_records($user_id);

    return array(
        'records'        => $records,
        'is_placeholder' => false,
    );
}

function kangoo_referrals_get_summary($records) {
    $summary = array(
        'successful_referrals' => 0,
        'pending_rewards'      => 0,
        'total_earned'         => 0,
    );

    foreach ($records as $record) {
        $status = isset($record['reward_status']) ? $record['reward_status'] : 'pending';
        $reward_amount = isset($record['reward_amount']) ? (float) $record['reward_amount'] : kangoo_referrals_reward_amount();
        $completed_spend = isset($record['completed_spend_total']) ? (float) $record['completed_spend_total'] : 0;

        if ($completed_spend >= kangoo_referrals_qualification_spend() || in_array($status, array('qualified', 'paid'), true)) {
            $summary['successful_referrals']++;
        }

        if ($status === 'qualified') {
            $summary['pending_rewards'] += $reward_amount;
        }

        if ($status === 'paid') {
            $summary['total_earned'] += $reward_amount;
        }
    }

    return $summary;
}

function kangoo_referrals_calculate_completed_spend($referred_user_id, $email, $first_order_date = '') {
    if (!function_exists('wc_get_orders')) {
        return 0;
    }

    $orders_by_id = array();
    $base_args = array(
        'limit'   => -1,
        'status'  => array('completed'),
        'return'  => 'objects',
        'orderby' => 'date',
        'order'   => 'ASC',
    );

    if ($referred_user_id) {
        foreach (wc_get_orders(array_merge($base_args, array('customer_id' => absint($referred_user_id)))) as $order) {
            if ($order instanceof WC_Order) {
                $orders_by_id[$order->get_id()] = $order;
            }
        }
    }

    $email = sanitize_email($email);

    if ($email) {
        foreach (wc_get_orders(array_merge($base_args, array('billing_email' => $email))) as $order) {
            if ($order instanceof WC_Order) {
                $orders_by_id[$order->get_id()] = $order;
            }
        }
    }

    $first_timestamp = $first_order_date ? strtotime($first_order_date . ' 00:00:00') : 0;
    $total = 0;

    foreach ($orders_by_id as $order) {
        $created = $order->get_date_created();
        $created_timestamp = $created ? $created->getTimestamp() : 0;

        if ($first_timestamp && $created_timestamp && $created_timestamp < ($first_timestamp - DAY_IN_SECONDS)) {
            continue;
        }

        $refunded = method_exists($order, 'get_total_refunded') ? (float) $order->get_total_refunded() : 0;
        $total += max(0, (float) $order->get_total() - $refunded);
    }

    return $total;
}

function kangoo_referrals_update_record_for_order($order_id) {
    $order = function_exists('wc_get_order') ? wc_get_order($order_id) : null;

    if (!$order || !is_a($order, 'WC_Order')) {
        return;
    }

    $referrer_id = absint($order->get_meta('_kangoo_referrer_user_id'));
    $code = kangoo_referrals_normalize_code($order->get_meta('_kangoo_referral_code'));

    if (!$referrer_id || !$code) {
        return;
    }

    $referred_user_id = function_exists('kangoo_rewards_get_order_user_id') ? kangoo_rewards_get_order_user_id($order) : (int) $order->get_user_id();
    $email = sanitize_email($order->get_billing_email());

    if (!$email || $referred_user_id === $referrer_id) {
        return;
    }

    $first_name = sanitize_text_field($order->get_billing_first_name());
    $last_name = sanitize_text_field($order->get_billing_last_name());
    $name = trim($first_name . ' ' . $last_name);
    $order_date = $order->get_date_created() ? $order->get_date_created()->date('Y-m-d') : current_time('Y-m-d');
    $records = get_user_meta($referrer_id, 'kangoo_referral_records', true);
    $records = is_array($records) ? $records : array();
    $prepared = array();

    foreach ($records as $record) {
        $record = kangoo_referrals_prepare_record($record, $referrer_id);
        $prepared[kangoo_referrals_record_storage_key($record)] = $record;
    }

    $key = kangoo_referrals_record_storage_key(array(
        'referred_user_id'        => $referred_user_id,
        'referred_customer_email' => $email,
    ));

    $existing = isset($prepared[$key]) ? $prepared[$key] : kangoo_referrals_record_defaults($referrer_id);
    $first_order_date = !empty($existing['first_order_date']) ? $existing['first_order_date'] : $order_date;
    $completed_spend = kangoo_referrals_calculate_completed_spend($referred_user_id, $email, $first_order_date);
    $status = $completed_spend >= kangoo_referrals_qualification_spend() ? 'qualified' : 'pending';

    if ($existing['reward_status'] === 'paid' && $completed_spend >= kangoo_referrals_qualification_spend()) {
        $status = 'paid';
    } elseif ($existing['reward_status'] === 'paid' && $completed_spend < kangoo_referrals_qualification_spend()) {
        $status = 'rejected';
    }

    $completed_date = $order->get_date_completed() ? $order->get_date_completed()->date('Y-m-d') : current_time('Y-m-d');
    $qualified_date = $existing['qualified_date'];

    if (!$qualified_date && in_array($status, array('qualified', 'paid'), true)) {
        $qualified_date = $completed_date;
    }

    $prepared[$key] = kangoo_referrals_prepare_record(array(
        'referral_code'            => $code,
        'referral_link'            => home_url('/r/' . rawurlencode($code) . '/'),
        'referrer_user_id'         => $referrer_id,
        'referred_user_id'         => $referred_user_id,
        'referred_customer_email'  => $email,
        'referred_customer_name'   => $name ? $name : $email,
        'completed_spend_total'    => $completed_spend,
        'reward_status'            => $status,
        'reward_amount'            => kangoo_referrals_reward_amount(),
        'first_order_date'         => $first_order_date,
        'qualified_date'           => $qualified_date,
        'paid_date'                => $existing['paid_date'],
    ), $referrer_id);

    update_user_meta($referrer_id, 'kangoo_referral_records', $prepared);

    if ($referred_user_id) {
        update_user_meta($referred_user_id, 'kangoo_referred_by_user_id', $referrer_id);
        update_user_meta($referred_user_id, 'kangoo_referred_by_code', $code);
        update_user_meta($referred_user_id, 'kangoo_referred_first_order_id', $order->get_id());
    }
}
add_action('woocommerce_order_status_completed', 'kangoo_referrals_update_record_for_order', 20);
add_action('woocommerce_order_status_refunded', 'kangoo_referrals_update_record_for_order', 20);
add_action('woocommerce_order_status_cancelled', 'kangoo_referrals_update_record_for_order', 20);
add_action('woocommerce_order_status_failed', 'kangoo_referrals_update_record_for_order', 20);
add_action('woocommerce_order_refunded', 'kangoo_referrals_update_record_for_order', 20);

function kangoo_referrals_account_menu_items($items) {
    $slug = kangoo_referrals_endpoint_slug();
    unset($items[$slug]);

    $new_items = array();
    $inserted = false;

    foreach ($items as $key => $label) {
        $new_items[$key] = $label;

        if ($key === 'rewards') {
            $new_items[$slug] = __('Refer a Friend', 'kangoo');
            $inserted = true;
        }
    }

    if ($inserted) {
        return $new_items;
    }

    $fallback_items = array();

    foreach ($new_items as $key => $label) {
        if ($key === 'customer-logout') {
            $fallback_items[$slug] = __('Refer a Friend', 'kangoo');
            $inserted = true;
        }

        $fallback_items[$key] = $label;
    }

    if (!$inserted) {
        $fallback_items[$slug] = __('Refer a Friend', 'kangoo');
    }

    return $fallback_items;
}
add_filter('woocommerce_account_menu_items', 'kangoo_referrals_account_menu_items', 25);

function kangoo_referrals_status_label($status) {
    switch ($status) {
        case 'qualified':
            return __('Qualified', 'kangoo');
        case 'paid':
            return __('Paid', 'kangoo');
        case 'rejected':
            return __('Rejected', 'kangoo');
        case 'pending':
        default:
            return __('Pending', 'kangoo');
    }
}

function kangoo_referrals_mask_email($email) {
    $email = sanitize_email($email);

    if (!$email || strpos($email, '@') === false) {
        return '';
    }

    list($local, $domain) = explode('@', $email, 2);
    $first = substr($local, 0, 1);

    return $first . '***@' . $domain;
}

function kangoo_referrals_display_name($record) {
    if (!empty($record['referred_customer_name'])) {
        return $record['referred_customer_name'];
    }

    return kangoo_referrals_mask_email(isset($record['referred_customer_email']) ? $record['referred_customer_email'] : '');
}

function kangoo_referrals_initials($label) {
    $label = trim((string) $label);

    if (!$label) {
        return 'RF';
    }

    if (strpos($label, '@') !== false) {
        return strtoupper(substr($label, 0, 1));
    }

    $parts = preg_split('/\s+/', $label);
    $initials = '';

    foreach (array_slice(array_filter($parts), 0, 2) as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }

    return $initials ? $initials : 'RF';
}

function kangoo_referrals_format_date($date) {
    $timestamp = $date ? strtotime($date) : 0;

    return $timestamp ? date_i18n('j M Y', $timestamp) : '-';
}

function kangoo_referrals_account_endpoint_content() {
    if (!is_user_logged_in()) {
        return;
    }

    $user_id = get_current_user_id();
    $code = kangoo_referrals_get_code($user_id);
    $link = kangoo_referrals_get_link($user_id);
    $records_data = kangoo_referrals_get_records($user_id);
    $records = $records_data['records'];
    $summary = kangoo_referrals_get_summary($records);
    $target = kangoo_referrals_qualification_spend();
    $reward_amount = kangoo_referrals_reward_amount();
    $icon_url = get_template_directory_uri() . '/assets/images/kangoo-icon-white.png';
    $referral_icon_base = get_template_directory_uri() . '/assets/images/referrals/';
    $referral_icons = array(
        'earn'       => $referral_icon_base . 'earn.png',
        'gifts'      => $referral_icon_base . 'gifts.png',
        'referral'   => $referral_icon_base . 'referral.png',
        'share'      => $referral_icon_base . 'share.png',
        'wall_clock' => $referral_icon_base . 'wall-clock.png',
        'wallet'     => $referral_icon_base . 'wallet.png',
    );
    ?>
    <div class="kangoo-referrals-dashboard">
        <section class="kangoo-referrals-hero">
            <div class="kangoo-referrals-hero__content">
                <span class="kangoo-referrals-hero__badge" aria-hidden="true"><img src="<?php echo esc_url($referral_icons['referral']); ?>" alt="" loading="lazy"></span>
                <div>
                    <h1><?php esc_html_e('Refer a Friend', 'kangoo'); ?></h1>
                    <p class="kangoo-referrals-hero__lead">
                        <?php echo wp_kses_post(sprintf(__('Give your friends <strong>%1$d%% off</strong> their first order and earn <strong>%2$s cash</strong> once they spend %3$s+.', 'kangoo'), kangoo_referrals_friend_discount_percent(), kangoo_plain_wc_price($reward_amount), kangoo_plain_wc_price($target))); ?>
                    </p>
                    <p><?php echo wp_kses_post(sprintf(__('Share your referral link with friends. When they place their first order and reach %1$s or more in completed orders, you will receive a %2$s cash reward.', 'kangoo'), kangoo_plain_wc_price($target), kangoo_plain_wc_price($reward_amount))); ?></p>
                </div>
            </div>
            <img class="kangoo-referrals-hero__mascot" src="<?php echo esc_url($icon_url); ?>" alt="" loading="lazy">
        </section>

        <section class="kangoo-referrals-card kangoo-referrals-share" aria-label="<?php esc_attr_e('Referral sharing details', 'kangoo'); ?>">
            <div class="kangoo-referrals-share__row">
                <label for="kangoo_referral_link"><?php esc_html_e('Your referral link', 'kangoo'); ?></label>
                <div class="kangoo-referrals-copy-field">
                    <input id="kangoo_referral_link" type="text" value="<?php echo esc_attr($link); ?>" readonly>
                    <button type="button" class="button kangoo-referrals-copy-button" data-copy-value="<?php echo esc_attr($link); ?>" data-copy-label="<?php esc_attr_e('Copy Link', 'kangoo'); ?>" data-copy-success="<?php esc_attr_e('Copied', 'kangoo'); ?>">
                        <span class="kangoo-referrals-copy-button__icon" aria-hidden="true"></span>
                        <span><?php esc_html_e('Copy Link', 'kangoo'); ?></span>
                    </button>
                </div>
            </div>
            <div class="kangoo-referrals-share__row">
                <label for="kangoo_referral_code"><?php esc_html_e('Your referral code', 'kangoo'); ?></label>
                <div class="kangoo-referrals-copy-field">
                    <input id="kangoo_referral_code" type="text" value="<?php echo esc_attr($code); ?>" readonly>
                    <button type="button" class="button kangoo-referrals-copy-button" data-copy-value="<?php echo esc_attr($code); ?>" data-copy-label="<?php esc_attr_e('Copy Code', 'kangoo'); ?>" data-copy-success="<?php esc_attr_e('Copied', 'kangoo'); ?>">
                        <span class="kangoo-referrals-copy-button__icon" aria-hidden="true"></span>
                        <span><?php esc_html_e('Copy Code', 'kangoo'); ?></span>
                    </button>
                </div>
            </div>
        </section>

        <section class="kangoo-referrals-stats" aria-label="<?php esc_attr_e('Referral summary', 'kangoo'); ?>">
            <article class="kangoo-referrals-stat kangoo-referrals-stat--blue">
                <span aria-hidden="true"><img src="<?php echo esc_url($referral_icons['referral']); ?>" alt="" loading="lazy"></span>
                <div>
                    <p><?php esc_html_e('Successful Referrals', 'kangoo'); ?></p>
                    <strong><?php echo esc_html(number_format_i18n($summary['successful_referrals'])); ?></strong>
                    <small><?php echo wp_kses_post(sprintf(__('Friends who have spent %s+', 'kangoo'), kangoo_plain_wc_price($target))); ?></small>
                </div>
            </article>
            <article class="kangoo-referrals-stat kangoo-referrals-stat--orange">
                <span aria-hidden="true"><img src="<?php echo esc_url($referral_icons['wall_clock']); ?>" alt="" loading="lazy"></span>
                <div>
                    <p><?php esc_html_e('Pending Rewards', 'kangoo'); ?></p>
                    <strong><?php echo esc_html(kangoo_plain_wc_price($summary['pending_rewards'])); ?></strong>
                    <small><?php esc_html_e('Rewards waiting to be paid', 'kangoo'); ?></small>
                </div>
            </article>
            <article class="kangoo-referrals-stat kangoo-referrals-stat--green">
                <span aria-hidden="true"><img src="<?php echo esc_url($referral_icons['wallet']); ?>" alt="" loading="lazy"></span>
                <div>
                    <p><?php esc_html_e('Total Earned', 'kangoo'); ?></p>
                    <strong><?php echo esc_html(kangoo_plain_wc_price($summary['total_earned'])); ?></strong>
                    <small><?php esc_html_e('Total rewards paid to you', 'kangoo'); ?></small>
                </div>
            </article>
        </section>

        <section class="kangoo-referrals-card kangoo-referrals-steps">
            <h2><?php esc_html_e('How it works', 'kangoo'); ?></h2>
            <div class="kangoo-referrals-steps__grid">
                <article>
                    <span class="kangoo-referrals-step-icon kangoo-referrals-step-icon--share" aria-hidden="true"><img src="<?php echo esc_url($referral_icons['share']); ?>" alt="" loading="lazy"></span>
                    <strong><?php esc_html_e('1. Invite', 'kangoo'); ?></strong>
                    <p><?php esc_html_e('Share your referral link or code with friends.', 'kangoo'); ?></p>
                </article>
                <article>
                    <span class="kangoo-referrals-step-icon kangoo-referrals-step-icon--gift" aria-hidden="true"><img src="<?php echo esc_url($referral_icons['gifts']); ?>" alt="" loading="lazy"></span>
                    <strong><?php esc_html_e('2. Friend gets 15% off', 'kangoo'); ?></strong>
                    <p><?php esc_html_e('Your friend receives 15% off their first order.', 'kangoo'); ?></p>
                </article>
                <article>
                    <span class="kangoo-referrals-step-icon kangoo-referrals-step-icon--cash" aria-hidden="true"><img src="<?php echo esc_url($referral_icons['earn']); ?>" alt="" loading="lazy"></span>
                    <strong><?php echo esc_html(sprintf(__('3. Earn %s cash', 'kangoo'), kangoo_plain_wc_price($reward_amount))); ?></strong>
                    <p><?php echo wp_kses_post(sprintf(__('Once your referred friend spends %s or more in completed orders, you will receive a %s reward.', 'kangoo'), kangoo_plain_wc_price($target), kangoo_plain_wc_price($reward_amount))); ?></p>
                </article>
            </div>
        </section>

        <section class="kangoo-referrals-card kangoo-referrals-progress-list">
            <h2><?php esc_html_e('Referral progress', 'kangoo'); ?></h2>
            <div class="kangoo-referrals-progress-list__items">
                <?php if (!empty($records)) : ?>
                    <?php foreach ($records as $record) : ?>
                    <?php
                    $display_name = kangoo_referrals_display_name($record);
                    $completed_spend = (float) $record['completed_spend_total'];
                    $progress = $target > 0 ? min(100, max(0, ($completed_spend / $target) * 100)) : 0;
                    $remaining = max(0, $target - $completed_spend);
                    $status = $record['reward_status'];
                    ?>
                    <article class="kangoo-referrals-progress-card">
                        <span class="kangoo-referrals-avatar" aria-hidden="true"><?php echo esc_html(kangoo_referrals_initials($display_name)); ?></span>
                        <div class="kangoo-referrals-progress-card__main">
                            <div class="kangoo-referrals-progress-card__topline">
                                <strong><?php echo esc_html($display_name); ?></strong>
                                <small><?php echo esc_html(sprintf(__('First order: %s', 'kangoo'), kangoo_referrals_format_date($record['first_order_date']))); ?></small>
                            </div>
                            <p><?php echo esc_html(sprintf(__('%1$s / %2$s completed', 'kangoo'), kangoo_plain_wc_price($completed_spend), kangoo_plain_wc_price($target))); ?></p>
                            <div class="kangoo-referrals-progress-track" aria-hidden="true"><span style="width: <?php echo esc_attr(number_format($progress, 2, '.', '')); ?>%"></span></div>
                            <small><?php echo $remaining > 0 ? esc_html(sprintf(__('%s remaining until reward unlocks', 'kangoo'), kangoo_plain_wc_price($remaining))) : esc_html__('Spend target reached', 'kangoo'); ?></small>
                        </div>
                        <div class="kangoo-referrals-progress-card__reward">
                            <span class="kangoo-referrals-status kangoo-referrals-status--<?php echo esc_attr($status); ?>"><?php echo esc_html(kangoo_referrals_status_label($status)); ?></span>
                            <strong><?php echo esc_html(sprintf(__('Reward: %s', 'kangoo'), kangoo_plain_wc_price($record['reward_amount']))); ?></strong>
                            <small><?php echo wp_kses_post(sprintf(__('Reward is paid after %s+ in completed orders.', 'kangoo'), kangoo_plain_wc_price($target))); ?></small>
                        </div>
                    </article>
                    <?php endforeach; ?>
                <?php else : ?>
                    <div class="kangoo-referrals-empty">
                        <strong><?php esc_html_e('No referral activity yet', 'kangoo'); ?></strong>
                        <p><?php esc_html_e('Share your link or code with friends. Their progress will appear here once they place an order through your referral.', 'kangoo'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="kangoo-referrals-card kangoo-referrals-history">
            <div class="kangoo-account-section-heading">
                <h2><?php esc_html_e('Referral history', 'kangoo'); ?></h2>
                <?php if (!empty($records)) : ?>
                    <a href="<?php echo esc_url(wc_get_account_endpoint_url(kangoo_referrals_endpoint_slug())); ?>"><?php esc_html_e('View full history', 'kangoo'); ?> <span aria-hidden="true">-&gt;</span></a>
                <?php endif; ?>
            </div>
            <?php if (!empty($records)) : ?>
                <table>
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Date', 'kangoo'); ?></th>
                            <th><?php esc_html_e('Referred friend', 'kangoo'); ?></th>
                            <th><?php esc_html_e('Their spend', 'kangoo'); ?></th>
                            <th><?php esc_html_e('Status', 'kangoo'); ?></th>
                            <th><?php esc_html_e('Reward', 'kangoo'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $record) : ?>
                            <?php
                            $status = $record['reward_status'];
                            $spend_label = (float) $record['completed_spend_total'] < $target
                                ? sprintf(__('%1$s / %2$s', 'kangoo'), kangoo_plain_wc_price($record['completed_spend_total']), kangoo_plain_wc_price($target))
                                : kangoo_plain_wc_price($record['completed_spend_total']);
                            ?>
                            <tr>
                                <td data-label="<?php esc_attr_e('Date', 'kangoo'); ?>"><?php echo esc_html(kangoo_referrals_format_date($record['first_order_date'])); ?></td>
                                <td data-label="<?php esc_attr_e('Referred friend', 'kangoo'); ?>"><?php echo esc_html(kangoo_referrals_display_name($record)); ?></td>
                                <td data-label="<?php esc_attr_e('Their spend', 'kangoo'); ?>"><?php echo esc_html($spend_label); ?></td>
                                <td data-label="<?php esc_attr_e('Status', 'kangoo'); ?>"><span class="kangoo-referrals-status kangoo-referrals-status--<?php echo esc_attr($status); ?>"><?php echo esc_html(kangoo_referrals_status_label($status)); ?></span></td>
                                <td data-label="<?php esc_attr_e('Reward', 'kangoo'); ?>"><?php echo esc_html(kangoo_plain_wc_price($record['reward_amount'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="kangoo-referrals-empty">
                    <strong><?php esc_html_e('No referral history yet', 'kangoo'); ?></strong>
                    <p><?php esc_html_e('Referral history will appear here after friends use your link and start placing qualifying orders.', 'kangoo'); ?></p>
                </div>
            <?php endif; ?>
        </section>

        <section class="kangoo-referrals-info-grid" aria-label="<?php esc_attr_e('Referral rules', 'kangoo'); ?>">
            <article>
                <strong><?php esc_html_e('Valid orders only', 'kangoo'); ?></strong>
                <p><?php esc_html_e('Rewards are paid on completed orders only.', 'kangoo'); ?></p>
            </article>
            <article>
                <strong><?php esc_html_e('Minimum spend', 'kangoo'); ?></strong>
                <p><?php echo wp_kses_post(sprintf(__('Your friend must spend %s or more in total completed orders.', 'kangoo'), kangoo_plain_wc_price($target))); ?></p>
            </article>
            <article>
                <strong><?php echo esc_html(sprintf(__('%s cash reward', 'kangoo'), kangoo_plain_wc_price($reward_amount))); ?></strong>
                <p><?php echo wp_kses_post(sprintf(__('We will pay %s to your chosen payout method once the referral qualifies.', 'kangoo'), kangoo_plain_wc_price($reward_amount))); ?></p>
            </article>
            <article>
                <strong><?php esc_html_e('Fraud protection', 'kangoo'); ?></strong>
                <p><?php esc_html_e('Self-referrals, duplicate accounts, refunded orders, and suspicious activity are excluded.', 'kangoo'); ?></p>
            </article>
        </section>

        <section class="kangoo-referrals-terms">
            <span aria-hidden="true">i</span>
            <p><?php echo wp_kses_post(sprintf(__('Referrers receive a %1$s reward once the referred customer has spent %2$s or more in completed orders. Rewards are reviewed before payment and cancelled/refunded orders, chargebacks, disputes, duplicate accounts, and self-referrals do not qualify.', 'kangoo'), kangoo_plain_wc_price($reward_amount), kangoo_plain_wc_price($target))); ?></p>
            <a href="<?php echo esc_url(home_url('/terms-and-conditions/')); ?>"><?php esc_html_e('View full terms', 'kangoo'); ?> <span aria-hidden="true">-&gt;</span></a>
        </section>
    </div>
    <?php
}
add_action('woocommerce_account_refer-a-friend_endpoint', 'kangoo_referrals_account_endpoint_content');

function kangoo_account_get_user_initials($user) {
    $name = trim((string) $user->display_name);

    if (!$name) {
        $name = trim((string) $user->user_login);
    }

    $parts = preg_split('/\s+/', $name);
    $initials = '';

    foreach (array_slice(array_filter($parts), 0, 2) as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }

    return $initials ? $initials : 'KP';
}

function kangoo_account_get_customer_orders($user_id, $limit = -1) {
    if (!function_exists('wc_get_orders')) {
        return array();
    }

    $user = get_user_by('id', $user_id);
    $email = $user ? sanitize_email($user->user_email) : '';
    $statuses = function_exists('wc_get_order_statuses') ? array_keys(wc_get_order_statuses()) : array();
    $orders_by_id = array();

    $base_args = array(
        'limit'   => -1,
        'orderby' => 'date',
        'order'   => 'DESC',
        'return'  => 'objects',
    );

    if (!empty($statuses)) {
        $base_args['status'] = $statuses;
    }

    foreach (wc_get_orders(array_merge($base_args, array('customer_id' => $user_id))) as $order) {
        if ($order instanceof WC_Order) {
            $orders_by_id[$order->get_id()] = $order;
        }
    }

    if ($email) {
        foreach (wc_get_orders(array_merge($base_args, array('billing_email' => $email))) as $order) {
            if (!$order instanceof WC_Order) {
                continue;
            }

            if (!$order->get_customer_id()) {
                $order->set_customer_id($user_id);
                $order->save();
            }

            $orders_by_id[$order->get_id()] = $order;
        }
    }

    $orders = array_values($orders_by_id);

    usort($orders, function ($a, $b) {
        $a_time = $a instanceof WC_Order && $a->get_date_created() ? $a->get_date_created()->getTimestamp() : 0;
        $b_time = $b instanceof WC_Order && $b->get_date_created() ? $b->get_date_created()->getTimestamp() : 0;

        return $b_time <=> $a_time;
    });

    return $limit > -1 ? array_slice($orders, 0, $limit) : $orders;
}

function kangoo_account_get_order_spend_total($orders) {
    $total = 0;
    $counted_statuses = array('processing', 'completed', 'on-hold');

    foreach ($orders as $order) {
        if (!$order instanceof WC_Order || !in_array($order->get_status(), $counted_statuses, true)) {
            continue;
        }

        $total += (float) $order->get_total();
    }

    return $total;
}

function kangoo_account_attach_matching_guest_orders() {
    if (!is_user_logged_in() || !function_exists('is_account_page') || !is_account_page()) {
        return;
    }

    kangoo_account_get_customer_orders(get_current_user_id());
}
add_action('wp', 'kangoo_account_attach_matching_guest_orders', 8);

function kangoo_account_dashboard_endpoint_content() {
    if (!is_user_logged_in()) {
        return;
    }

    $user = wp_get_current_user();
    $user_id = (int) $user->ID;
    $all_orders = kangoo_account_get_customer_orders($user_id);
    $orders = array_slice($all_orders, 0, 4);
    $total_orders = count($all_orders);
    $total_spent = kangoo_account_get_order_spend_total($all_orders);
    $reward_points = function_exists('kangoo_rewards_get_balance') ? kangoo_rewards_get_balance($user_id) : 0;
    $member_since = $user->user_registered ? date_i18n('M Y', strtotime($user->user_registered)) : '';
    ?>
    <div class="kangoo-account-dashboard">
        <section class="kangoo-account-welcome">
            <div>
                <h1><?php echo esc_html(sprintf(__('Hello %s', 'kangoo'), $user->display_name ? $user->display_name : $user->user_login)); ?></h1>
                <p><?php esc_html_e('From your account dashboard you can view your recent orders, manage your shipping and billing addresses, and edit your password and account details.', 'kangoo'); ?></p>
            </div>
            <span class="kangoo-account-avatar" aria-hidden="true"><?php echo esc_html(kangoo_account_get_user_initials($user)); ?></span>
        </section>

        <section class="kangoo-account-stats" aria-label="<?php esc_attr_e('Account summary', 'kangoo'); ?>">
            <article>
                <span aria-hidden="true"></span>
                <p><?php esc_html_e('Total orders', 'kangoo'); ?></p>
                <strong><?php echo esc_html(number_format_i18n($total_orders)); ?></strong>
                <a href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>"><?php esc_html_e('View orders', 'kangoo'); ?> <span aria-hidden="true">-&gt;</span></a>
            </article>
            <article>
                <span aria-hidden="true"></span>
                <p><?php esc_html_e('Total spent', 'kangoo'); ?></p>
                <strong><?php echo esc_html(kangoo_plain_wc_price($total_spent)); ?></strong>
                <a href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>"><?php esc_html_e('View orders', 'kangoo'); ?> <span aria-hidden="true">-&gt;</span></a>
            </article>
            <article>
                <span aria-hidden="true"></span>
                <p><?php esc_html_e('Reward points', 'kangoo'); ?></p>
                <strong><?php echo esc_html(number_format_i18n($reward_points)); ?></strong>
                <a href="<?php echo esc_url(wc_get_account_endpoint_url('rewards')); ?>"><?php esc_html_e('View rewards', 'kangoo'); ?> <span aria-hidden="true">-&gt;</span></a>
            </article>
            <article>
                <span aria-hidden="true"></span>
                <p><?php esc_html_e('Member since', 'kangoo'); ?></p>
                <strong><?php echo esc_html($member_since); ?></strong>
                <a href="<?php echo esc_url(wc_get_account_endpoint_url('edit-account')); ?>"><?php esc_html_e('Account details', 'kangoo'); ?> <span aria-hidden="true">-&gt;</span></a>
            </article>
        </section>

        <section class="kangoo-account-recent-orders">
            <div class="kangoo-account-section-heading">
                <h2><?php esc_html_e('Recent orders', 'kangoo'); ?></h2>
                <a href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>"><?php esc_html_e('View all orders', 'kangoo'); ?> <span aria-hidden="true">-&gt;</span></a>
            </div>
            <?php if (!empty($orders)) : ?>
                <div class="kangoo-account-order-list">
                    <?php foreach ($orders as $order) : ?>
                        <?php
                        $items = $order->get_items();
                        $first_item = !empty($items) ? reset($items) : null;
                        $product = $first_item && method_exists($first_item, 'get_product') ? $first_item->get_product() : null;
                        ?>
                        <a class="kangoo-account-order-card" href="<?php echo esc_url($order->get_view_order_url()); ?>">
                            <span class="kangoo-account-order-card__image">
                                <?php
                                if ($product) {
                                    echo wp_kses_post($product->get_image('thumbnail'));
                                }
                                ?>
                            </span>
                            <span>
                                <strong><?php echo esc_html(sprintf(__('#%s', 'kangoo'), $order->get_order_number())); ?></strong>
                                <small><?php echo esc_html(wc_format_datetime($order->get_date_created())); ?></small>
                            </span>
                            <span class="kangoo-account-status kangoo-account-status--<?php echo esc_attr($order->get_status()); ?>"><?php echo esc_html(wc_get_order_status_name($order->get_status())); ?></span>
                            <strong><?php echo wp_kses_post($order->get_formatted_order_total()); ?></strong>
                            <span aria-hidden="true">-&gt;</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p><?php esc_html_e('Your recent orders will appear here after your first purchase.', 'kangoo'); ?></p>
            <?php endif; ?>
        </section>
    </div>
    <?php
}

function kangoo_account_replace_default_dashboard() {
    if (function_exists('woocommerce_account_dashboard')) {
        remove_action('woocommerce_account_dashboard', 'woocommerce_account_dashboard');
    }
}
add_action('init', 'kangoo_account_replace_default_dashboard', 30);
add_action('wp', 'kangoo_account_replace_default_dashboard', 5);
add_action('woocommerce_account_dashboard', 'kangoo_account_dashboard_endpoint_content', 10);

function kangoo_body_classes($classes) {
    if (is_front_page()) {
        $classes[] = 'is-front-page';
    }

    if (function_exists('is_woocommerce') && is_woocommerce()) {
        $classes[] = 'is-woocommerce';
    }

    if (is_page_template('template-parts/global/template-info-page.php')) {
        $classes[] = 'is-info-page';
    }

    return $classes;
}
add_filter('body_class', 'kangoo_body_classes');

function kangoo_get_cart_badge_html() {
    ob_start();
    ?>
    <span class="cart-badge" id="header-cart-count">
        <?php echo function_exists('WC') && WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0; ?>
    </span>
    <?php
    return ob_get_clean();
}

function kangoo_free_shipping_threshold() {
    return 14.95;
}

function kangoo_first_order_free_shipping_threshold() {
    return 9.99;
}

function kangoo_first_order_shipping_coupon_code() {
    return apply_filters('kangoo_first_order_shipping_coupon_code', 'firstfree');
}

function kangoo_email_belongs_to_existing_customer($email) {
    $email = sanitize_email($email);

    if (!is_email($email)) {
        return false;
    }

    if (email_exists($email)) {
        return true;
    }

    if (!function_exists('wc_get_orders') || !function_exists('wc_get_order_statuses')) {
        return false;
    }

    $orders = wc_get_orders(array(
        'billing_email' => $email,
        'limit'         => 1,
        'return'        => 'ids',
        'status'        => array_keys(wc_get_order_statuses()),
    ));

    return !empty($orders);
}

function kangoo_saved_checkout_email_is_existing_customer() {
    if (is_user_logged_in()) {
        return true;
    }

    $email = function_exists('kangoo_get_saved_checkout_email') ? kangoo_get_saved_checkout_email() : '';

    return kangoo_email_belongs_to_existing_customer($email);
}

function kangoo_remove_first_order_coupon_for_existing_customer($email = '') {
    if (!function_exists('WC') || !WC()->cart) {
        return false;
    }

    $email = $email ? sanitize_email($email) : (function_exists('kangoo_get_saved_checkout_email') ? kangoo_get_saved_checkout_email() : '');

    if (!kangoo_email_belongs_to_existing_customer($email)) {
        return false;
    }

    $coupon_code = kangoo_first_order_shipping_coupon_code();
    $coupon_code = function_exists('wc_format_coupon_code') ? wc_format_coupon_code($coupon_code) : strtolower($coupon_code);

    if (!$coupon_code || !WC()->cart->has_discount($coupon_code)) {
        return false;
    }

    WC()->cart->remove_coupon($coupon_code);
    WC()->cart->calculate_totals();

    if (WC()->session) {
        WC()->session->__unset('kangoo_pending_url_coupon');
        WC()->session->__unset('kangoo_pending_url_coupon_at');
        WC()->session->__unset('kangoo_url_coupon_applied');
        WC()->session->__unset('kangoo_url_coupon_applied_at');
        WC()->session->__unset('kangoo_url_coupon_last_attempted');
        WC()->session->__unset('kangoo_url_coupon_last_attempted_at');
    }

    return true;
}

function kangoo_remove_saved_first_order_coupon_for_existing_customer() {
    if ((is_admin() && !wp_doing_ajax()) || !function_exists('WC') || !WC()->cart) {
        return;
    }

    kangoo_remove_first_order_coupon_for_existing_customer();
}
add_action('wp_loaded', 'kangoo_remove_saved_first_order_coupon_for_existing_customer', 35);

function kangoo_cart_has_first_order_shipping_coupon() {
    if (!function_exists('WC') || !WC()->cart) {
        return false;
    }

    $coupon_code = kangoo_first_order_shipping_coupon_code();

    if (!$coupon_code) {
        return false;
    }

    $coupon_code = function_exists('wc_format_coupon_code') ? wc_format_coupon_code($coupon_code) : strtolower($coupon_code);

    return WC()->cart->has_discount($coupon_code);
}

function kangoo_is_first_order_free_shipping_offer_active() {
    return !kangoo_saved_checkout_email_is_existing_customer() && kangoo_cart_has_first_order_shipping_coupon();
}

function kangoo_get_active_free_shipping_threshold() {
    if (kangoo_is_first_order_free_shipping_offer_active()) {
        return kangoo_first_order_free_shipping_threshold();
    }

    return kangoo_free_shipping_threshold();
}

function kangoo_get_product_delivery_note_html() {
    $threshold = (float) kangoo_free_shipping_threshold();

    ob_start();
    ?>
    <div class="product-card__delivery-note">
        <span><?php esc_html_e('£2.99 delivery', 'kangoo'); ?></span>
        <span aria-hidden="true">&middot;</span>
        <span><?php echo esc_html(sprintf(__('Free over %s', 'kangoo'), kangoo_plain_wc_price($threshold))); ?></span>
    </div>
    <?php
    return ob_get_clean();
}

function kangoo_get_free_shipping_nudge_html($context = 'cart-drawer') {
    if (!function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
        return '';
    }

    $threshold = (float) kangoo_get_active_free_shipping_threshold();
    $subtotal = (float) WC()->cart->get_subtotal();
    $is_first_order_offer = kangoo_is_first_order_free_shipping_offer_active();

    if ($threshold <= 0) {
        return '';
    }

    $remaining = $threshold - $subtotal;
    $progress = max(0, min(100, ($subtotal / $threshold) * 100));
    $classes = array('kangoo-free-shipping-nudge', 'kangoo-free-shipping-nudge--' . sanitize_html_class($context));

    if ($remaining <= 0) {
        $classes[] = 'is-unlocked';
    }

    ob_start();
    ?>
    <div class="<?php echo esc_attr(implode(' ', $classes)); ?>" data-kangoo-free-shipping-nudge>
        <span class="kangoo-free-shipping-nudge__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" focusable="false">
                <path d="M3 7h11v10H3zM14 11h3.5l2.5 3v3h-6z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                <path d="M6.5 19a1.7 1.7 0 1 0 0-3.4 1.7 1.7 0 0 0 0 3.4ZM17.5 19a1.7 1.7 0 1 0 0-3.4 1.7 1.7 0 0 0 0 3.4Z" fill="none" stroke="currentColor" stroke-width="1.8"/>
            </svg>
        </span>
        <div class="kangoo-free-shipping-nudge__copy">
            <?php if ($remaining > 0) : ?>
                <strong><?php echo esc_html(sprintf($is_first_order_offer ? __('New customer: %s away from free delivery', 'kangoo') : __('%s away from free delivery', 'kangoo'), kangoo_plain_wc_price($remaining))); ?></strong>
                <span><?php echo esc_html(sprintf($is_first_order_offer ? __('First-order free UK delivery unlocks at %s.', 'kangoo') : __('Free UK delivery unlocks at %s.', 'kangoo'), kangoo_plain_wc_price($threshold))); ?></span>
            <?php else : ?>
                <strong><?php echo esc_html($is_first_order_offer ? __('First-order free delivery unlocked', 'kangoo') : __('Free delivery unlocked', 'kangoo')); ?></strong>
                <span><?php echo esc_html($is_first_order_offer ? __('Your first order qualifies for free UK delivery.', 'kangoo') : __('Your order qualifies for free UK delivery.', 'kangoo')); ?></span>
            <?php endif; ?>
        </div>
        <div class="kangoo-free-shipping-nudge__track" aria-hidden="true">
            <span style="width: <?php echo esc_attr(number_format($remaining > 0 ? $progress : 100, 2, '.', '')); ?>%"></span>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function kangoo_get_cart_product_savings_total() {
    if (!function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
        return 0;
    }

    if (method_exists(WC()->cart, 'calculate_totals')) {
        WC()->cart->calculate_totals();
    }

    $savings = (float) WC()->cart->get_discount_total();

    foreach (WC()->cart->get_cart() as $cart_item) {
        $product = isset($cart_item['data']) && $cart_item['data'] instanceof WC_Product ? $cart_item['data'] : null;

        if (!$product) {
            continue;
        }

        $quantity = isset($cart_item['quantity']) ? (int) $cart_item['quantity'] : 0;
        $regular_price = (float) $product->get_regular_price();
        $current_price = (float) $product->get_price();

        if ($quantity > 0 && $regular_price > $current_price) {
            $savings += ($regular_price - $current_price) * $quantity;
        }
    }

    return max(0, $savings);
}

function kangoo_get_mobile_cart_sticky_html() {
    if (function_exists('is_product') && is_product()) {
        return '<button type="button" class="kangoo-mobile-cart-sticky" data-cart-sticky-open hidden aria-hidden="true"></button>';
    }

    $has_cart = function_exists('WC') && WC()->cart && !WC()->cart->is_empty();
    $count = $has_cart ? (int) WC()->cart->get_cart_contents_count() : 0;
    $subtotal = $has_cart ? (float) WC()->cart->get_subtotal() : 0;
    $savings = $has_cart ? kangoo_get_cart_product_savings_total() : 0;
    $reward_points = function_exists('kangoo_rewards_points_per_pound') ? (int) floor($subtotal * kangoo_rewards_points_per_pound()) : 0;
    $threshold = (float) kangoo_get_active_free_shipping_threshold();
    $remaining = max(0, $threshold - $subtotal);
    $is_first_order_offer = kangoo_is_first_order_free_shipping_offer_active();
    $classes = array('kangoo-mobile-cart-sticky');

    if ($has_cart) {
        $classes[] = 'has-items';
    }

    if ($has_cart && $remaining <= 0) {
        $classes[] = 'is-unlocked';
    }

    ob_start();
    ?>
    <button
        type="button"
        class="<?php echo esc_attr(implode(' ', $classes)); ?>"
        data-cart-sticky-open
        aria-label="<?php esc_attr_e('Open cart summary', 'kangoo'); ?>"
    >
        <span class="kangoo-mobile-cart-sticky__left">
            <span class="kangoo-mobile-cart-sticky__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" focusable="false">
                    <path d="M6 6h15l-1.5 8.5a2 2 0 0 1-2 1.5H9a2 2 0 0 1-2-1.3L4.3 4H2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="9" cy="20" r="1.75" fill="currentColor"/>
                    <circle cx="18" cy="20" r="1.75" fill="currentColor"/>
                </svg>
                <span class="kangoo-mobile-cart-sticky__count"><?php echo esc_html($count); ?></span>
            </span>

            <span class="kangoo-mobile-cart-sticky__total">
                <strong><?php echo wp_kses_post(wc_price($subtotal)); ?></strong>
            </span>

            <?php if ($savings > 0) : ?>
                <span class="kangoo-mobile-cart-sticky__saving"><?php echo esc_html(sprintf(__('Saved %s', 'kangoo'), kangoo_plain_wc_price($savings))); ?></span>
            <?php endif; ?>
        </span>

        <span class="kangoo-mobile-cart-sticky__main">
            <span class="kangoo-mobile-cart-sticky__delivery">
                <?php if ($remaining > 0) : ?>
                    <span class="kangoo-mobile-cart-sticky__truck" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false">
                            <path d="M3 7h11v9H3zM14 10h3.5l3 3v3H14z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                            <circle cx="7" cy="18" r="2" fill="currentColor"/>
                            <circle cx="18" cy="18" r="2" fill="currentColor"/>
                        </svg>
                    </span>
                    <span><?php echo esc_html(sprintf($is_first_order_offer ? __('New customer: %s away from free delivery', 'kangoo') : __('%s away from free delivery', 'kangoo'), kangoo_plain_wc_price($remaining))); ?></span>
                <?php else : ?>
                    <span class="kangoo-mobile-cart-sticky__truck" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false">
                            <path d="M3 7h11v9H3zM14 10h3.5l3 3v3H14z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                            <circle cx="7" cy="18" r="2" fill="currentColor"/>
                            <circle cx="18" cy="18" r="2" fill="currentColor"/>
                        </svg>
                    </span>
                    <span><?php echo esc_html($is_first_order_offer ? __('First-order free delivery unlocked', 'kangoo') : __('Free delivery unlocked', 'kangoo')); ?></span>
                <?php endif; ?>
            </span>

            <?php if ($reward_points > 0) : ?>
                <span class="kangoo-mobile-cart-sticky__rewards">
                    <span class="kangoo-mobile-cart-sticky__coin" aria-hidden="true">&#9733;</span>
                    <span><?php echo esc_html(sprintf(__('Earn %d points', 'kangoo'), $reward_points)); ?></span>
                </span>
            <?php endif; ?>
        </span>

        <span class="kangoo-mobile-cart-sticky__cta">
            <?php esc_html_e('View cart', 'kangoo'); ?>
            <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                <path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </span>
    </button>
    <?php
    return ob_get_clean();
}

function kangoo_get_mini_cart_container_html() {
    ob_start();
    ?>
    <div class="cart-drawer__content">
        <?php woocommerce_mini_cart(); ?>
    </div>
    <?php
    return ob_get_clean();
}

function kangoo_get_mini_cart_html() {
    ob_start();
    woocommerce_mini_cart();
    return ob_get_clean();
}

function kangoo_get_cart_drawer_footer_html() {
    $has_cart = function_exists('WC') && WC()->cart && !WC()->cart->is_empty();
    $subtotal = $has_cart ? (float) WC()->cart->get_subtotal() : 0;
    $threshold = (float) kangoo_get_active_free_shipping_threshold();
    $remaining = $threshold > 0 ? max(0, $threshold - $subtotal) : 0;
    $progress = $threshold > 0 ? max(0, min(100, ($subtotal / $threshold) * 100)) : 0;
    $is_first_order_offer = kangoo_is_first_order_free_shipping_offer_active();
    $reward_points = ($has_cart && function_exists('kangoo_rewards_points_per_pound')) ? (int) floor($subtotal * kangoo_rewards_points_per_pound()) : 0;
    $cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');
    $classes = array('cart-drawer__footer');

    if (!$has_cart) {
        $classes[] = 'is-empty';
    }

    ob_start();
    ?>
    <div class="<?php echo esc_attr(implode(' ', $classes)); ?>">
        <div class="cart-drawer-summary">
            <div class="cart-drawer-summary__top">
                <strong>
                    <span><?php esc_html_e('Subtotal:', 'kangoo'); ?></span>
                    <span><?php echo wp_kses_post(wc_price($subtotal)); ?></span>
                </strong>
                <button type="button" class="cart-drawer__clear" data-cart-clear>
                    <?php esc_html_e('Clear', 'kangoo'); ?>
                </button>
            </div>

            <?php if ($threshold > 0) : ?>
                <div class="cart-drawer-summary__shipping<?php echo $remaining <= 0 ? ' is-unlocked' : ''; ?>">
                    <span class="cart-drawer-summary__truck" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false">
                            <path d="M3 7h11v9H3zM14 10h3.5l3 3v3H14z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                            <circle cx="7" cy="18" r="2" fill="currentColor"/>
                            <circle cx="18" cy="18" r="2" fill="currentColor"/>
                        </svg>
                    </span>
                    <div class="cart-drawer-summary__shipping-main">
                        <p>
                            <?php if ($remaining > 0) : ?>
                                <?php
                                echo wp_kses_post(sprintf(
                                    $is_first_order_offer ? __('New customer: <strong>%s</strong> away from free delivery', 'kangoo') : __('<strong>%s</strong> away from free delivery', 'kangoo'),
                                    wc_price($remaining)
                                ));
                                ?>
                            <?php else : ?>
                                <strong><?php echo esc_html($is_first_order_offer ? __('First-order free delivery unlocked', 'kangoo') : __('Free UK delivery unlocked', 'kangoo')); ?></strong>
                            <?php endif; ?>
                        </p>
                        <div class="cart-drawer-summary__progress" aria-hidden="true">
                            <span style="width: <?php echo esc_attr(number_format($remaining > 0 ? $progress : 100, 2, '.', '')); ?>%"></span>
                            <i style="left: <?php echo esc_attr(number_format(max(4, min(96, $progress)), 2, '.', '')); ?>%"></i>
                        </div>
                    </div>
                    <span class="cart-drawer-summary__threshold"><?php echo wp_kses_post(wc_price($threshold)); ?></span>
                </div>
            <?php endif; ?>

            <div class="cart-drawer-summary__perks">
                <span class="cart-drawer-summary__reward">
                    <span class="cart-drawer-summary__badge" aria-hidden="true">&#9733;</span>
                    <span><?php echo wp_kses_post(sprintf(__('Earn <strong>%d</strong> Kangoo Rewards', 'kangoo'), $reward_points)); ?></span>
                </span>
            </div>

            <a href="<?php echo esc_url($cart_url); ?>" class="btn btn--primary cart-drawer-summary__checkout<?php echo !$has_cart ? ' kangoo-checkout-disabled' : ''; ?>">
                <span><?php esc_html_e('Review & Checkout', 'kangoo'); ?></span>
                <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                    <path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function kangoo_get_refreshed_cart_fragments_payload() {
    return array(
        'fragments' => apply_filters('woocommerce_add_to_cart_fragments', array()),
        'cart_hash' => WC()->cart ? WC()->cart->get_cart_hash() : '',
    );
}

add_filter('woocommerce_add_to_cart_fragments', function ($fragments) {
    $fragments['.cart-badge'] = kangoo_get_cart_badge_html();
    $fragments['.cart-drawer__content'] = kangoo_get_mini_cart_container_html();
    $fragments['.cart-drawer__footer'] = kangoo_get_cart_drawer_footer_html();
    $fragments['.kangoo-mobile-cart-sticky'] = kangoo_get_mobile_cart_sticky_html();
    $fragments['div.widget_shopping_cart_content'] = '<div class="widget_shopping_cart_content">' . kangoo_get_mini_cart_html() . '</div>';

    return $fragments;
});

function kangoo_render_mini_cart_free_shipping_nudge() {
    echo kangoo_get_free_shipping_nudge_html('cart-drawer');
}
add_action('woocommerce_widget_shopping_cart_before_buttons', 'kangoo_render_mini_cart_free_shipping_nudge', 5);

function kangoo_get_saved_checkout_email() {
    $email = '';

    if (function_exists('WC') && WC()->session) {
        $email = WC()->session->get('kangoo_checkout_email');
    }

    if (!$email && !empty($_COOKIE['kangoo_checkout_email'])) {
        $email = sanitize_email(wp_unslash($_COOKIE['kangoo_checkout_email']));
    }

    if (!$email && is_user_logged_in()) {
        $user = wp_get_current_user();
        $email = $user instanceof WP_User ? $user->user_email : '';
    }

    return is_email($email) ? $email : '';
}

function kangoo_get_saved_checkout_dob() {
    $dob = '';

    if (function_exists('WC') && WC()->session) {
        $dob = WC()->session->get('kangoo_checkout_dob');
    }

    if (!$dob && !empty($_COOKIE['kangoo_checkout_dob'])) {
        $dob = sanitize_text_field(wp_unslash($_COOKIE['kangoo_checkout_dob']));
    }

    if (!$dob && is_user_logged_in()) {
        $dob = get_user_meta(get_current_user_id(), 'kangoo_date_of_birth', true);
    }

    $age = kangoo_calculate_age_from_date($dob);

    if ($age !== null && $age >= 18) {
        if (is_user_logged_in()) {
            update_user_meta(get_current_user_id(), 'kangoo_date_of_birth', $dob);
        }

        return $dob;
    }

    return '';
}

function kangoo_get_saved_checkout_dob_parts() {
    $dob = kangoo_get_saved_checkout_dob();

    if (!$dob || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $dob, $matches)) {
        return array(
            'day' => '',
            'month' => '',
            'year' => '',
        );
    }

    return array(
        'day' => $matches[3],
        'month' => $matches[2],
        'year' => $matches[1],
    );
}

function kangoo_format_checkout_dob_display($dob_parts) {
    if (empty($dob_parts['day']) || empty($dob_parts['month']) || empty($dob_parts['year'])) {
        return '';
    }

    return sprintf(
        '%s / %s / %s',
        str_pad((string) $dob_parts['day'], 2, '0', STR_PAD_LEFT),
        str_pad((string) $dob_parts['month'], 2, '0', STR_PAD_LEFT),
        (string) $dob_parts['year']
    );
}

function kangoo_ajax_store_checkout_email() {
    check_ajax_referer('kangoo_rewards_ajax', 'nonce');

    $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
    $dob = kangoo_normalize_dob_parts(
        isset($_POST['dob_day']) ? wc_clean(wp_unslash($_POST['dob_day'])) : '',
        isset($_POST['dob_month']) ? wc_clean(wp_unslash($_POST['dob_month'])) : '',
        isset($_POST['dob_year']) ? wc_clean(wp_unslash($_POST['dob_year'])) : ''
    );
    $age = $dob ? kangoo_calculate_age_from_date($dob) : null;

    if (!is_email($email)) {
        wp_send_json_error(array(
            'message' => __('Enter a valid email address.', 'kangoo'),
        ), 400);
    }

    if (!$dob || $age === null) {
        wp_send_json_error(array(
            'message' => __('Enter a valid date of birth.', 'kangoo'),
        ), 400);
    }

    if ($age < 18) {
        wp_send_json_error(array(
            'message' => __('You must be 18 or over to place an order.', 'kangoo'),
        ), 400);
    }

    if (function_exists('WC') && WC()->session) {
        WC()->session->set('kangoo_checkout_email', $email);
        WC()->session->set('kangoo_checkout_dob', $dob);
    }

    if (function_exists('wc_setcookie')) {
        wc_setcookie('kangoo_checkout_email', $email, time() + (14 * DAY_IN_SECONDS), is_ssl(), false);
        wc_setcookie('kangoo_checkout_dob', $dob, time() + (14 * DAY_IN_SECONDS), is_ssl(), false);
    }

    if (is_user_logged_in()) {
        update_user_meta(get_current_user_id(), 'kangoo_date_of_birth', $dob);
    }

    $existing_customer = kangoo_email_belongs_to_existing_customer($email);
    $removed_first_order_coupon = $existing_customer ? kangoo_remove_first_order_coupon_for_existing_customer($email) : false;

    wp_send_json_success(array(
        'email' => $email,
        'dob' => $dob,
        'existing_customer' => $existing_customer,
        'removed_first_order_coupon' => $removed_first_order_coupon,
    ));
}
add_action('wp_ajax_kangoo_store_checkout_email', 'kangoo_ajax_store_checkout_email');
add_action('wp_ajax_nopriv_kangoo_store_checkout_email', 'kangoo_ajax_store_checkout_email');

function kangoo_ajax_check_existing_customer_email() {
    check_ajax_referer('kangoo_rewards_ajax', 'nonce');

    $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';

    if (!is_email($email)) {
        wp_send_json_error(array(
            'message' => __('Enter a valid email address.', 'kangoo'),
        ), 400);
    }

    $existing_customer = kangoo_email_belongs_to_existing_customer($email);
    $removed_first_order_coupon = $existing_customer ? kangoo_remove_first_order_coupon_for_existing_customer($email) : false;

    wp_send_json_success(array(
        'email' => $email,
        'existing_customer' => $existing_customer,
        'removed_first_order_coupon' => $removed_first_order_coupon,
    ));
}
add_action('wp_ajax_kangoo_check_existing_customer_email', 'kangoo_ajax_check_existing_customer_email');
add_action('wp_ajax_nopriv_kangoo_check_existing_customer_email', 'kangoo_ajax_check_existing_customer_email');

function kangoo_render_account_dob_field() {
    $dob = kangoo_get_saved_checkout_dob();
    ?>
    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide kangoo-account-dob-field">
        <label for="kangoo_account_dob"><?php esc_html_e('Date of birth (used for verification)', 'kangoo'); ?></label>
        <input
            type="date"
            class="woocommerce-Input woocommerce-Input--text input-text"
            name="kangoo_account_dob"
            id="kangoo_account_dob"
            value="<?php echo esc_attr($dob); ?>"
            max="<?php echo esc_attr(gmdate('Y-m-d', strtotime('-18 years'))); ?>"
            autocomplete="bday"
        >
    </p>
    <?php
}
add_action('woocommerce_edit_account_form', 'kangoo_render_account_dob_field', 12);

function kangoo_save_account_dob_field($user_id) {
    if (!isset($_POST['kangoo_account_dob'])) {
        return;
    }

    $dob = sanitize_text_field(wp_unslash($_POST['kangoo_account_dob']));
    $age = kangoo_calculate_age_from_date($dob);

    if ($dob !== '' && ($age === null || $age < 18)) {
        wc_add_notice(__('Please enter a valid date of birth. You must be 18 or over.', 'kangoo'), 'error');
        return;
    }

    if ($dob === '') {
        delete_user_meta($user_id, 'kangoo_date_of_birth');
        return;
    }

    update_user_meta($user_id, 'kangoo_date_of_birth', $dob);

    if (function_exists('WC') && WC()->session) {
        WC()->session->set('kangoo_checkout_dob', $dob);
    }

    if (function_exists('wc_setcookie')) {
        wc_setcookie('kangoo_checkout_dob', $dob, time() + (14 * DAY_IN_SECONDS), is_ssl(), false);
    }
}
add_action('woocommerce_save_account_details', 'kangoo_save_account_dob_field', 12);

function kangoo_prefill_checkout_email($value, $input) {
    if ('billing_email' !== $input || !empty($value)) {
        return $value;
    }

    return kangoo_get_saved_checkout_email();
}
add_filter('woocommerce_checkout_get_value', 'kangoo_prefill_checkout_email', 10, 2);

function kangoo_render_cart_email_capture() {
    static $rendered = false;

    if ($rendered || !function_exists('is_cart') || !is_cart()) {
        return;
    }

    $rendered = true;
    $email = kangoo_get_saved_checkout_email();
    $dob_parts = kangoo_get_saved_checkout_dob_parts();
    $has_identity = $email && $dob_parts['day'] && $dob_parts['month'] && $dob_parts['year'];
    $dob_display = kangoo_format_checkout_dob_display($dob_parts);
    ?>
    <div class="kangoo-cart-email-capture<?php echo $has_identity ? ' has-email' : ' is-editing'; ?>" data-kangoo-cart-email-capture>
        <div class="kangoo-cart-email-capture__header">
            <span class="kangoo-cart-email-capture__status" aria-hidden="true">
                <svg viewBox="0 0 24 24" focusable="false">
                    <path d="M5 12.5l4.2 4.2L19 7" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
            <strong><?php esc_html_e('Checkout Details', 'kangoo'); ?></strong>
            <button type="button" class="kangoo-cart-email-capture__edit" data-kangoo-cart-edit>
                <span><?php esc_html_e('Edit', 'kangoo'); ?></span>
                <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                    <path d="M4 20h4l10.5-10.5a2.1 2.1 0 0 0-3-3L5 17v3z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                    <path d="M14 8l2 2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
        </div>

        <div class="kangoo-cart-email-capture__form">
            <label class="kangoo-cart-email-capture__field" for="kangoo-cart-email">
                <span><?php esc_html_e('Email address', 'kangoo'); ?></span>
                <input
                    id="kangoo-cart-email"
                    type="email"
                    value="<?php echo esc_attr($email); ?>"
                    placeholder="<?php esc_attr_e('Email address', 'kangoo'); ?>"
                    autocomplete="email"
                    data-kangoo-cart-email
                >
            </label>
            <div class="kangoo-cart-email-capture__dob" data-kangoo-cart-dob>
                <span class="kangoo-cart-email-capture__dob-title"><?php esc_html_e('Date of birth', 'kangoo'); ?></span>
                <label>
                    <span><?php esc_html_e('DD', 'kangoo'); ?></span>
                    <input type="text" inputmode="numeric" maxlength="2" value="<?php echo esc_attr($dob_parts['day']); ?>" autocomplete="bday-day" data-kangoo-cart-dob-day>
                </label>
                <label>
                    <span><?php esc_html_e('MM', 'kangoo'); ?></span>
                    <input type="text" inputmode="numeric" maxlength="2" value="<?php echo esc_attr($dob_parts['month']); ?>" autocomplete="bday-month" data-kangoo-cart-dob-month>
                </label>
                <label>
                    <span><?php esc_html_e('YYYY', 'kangoo'); ?></span>
                    <input type="text" inputmode="numeric" maxlength="4" value="<?php echo esc_attr($dob_parts['year']); ?>" autocomplete="bday-year" data-kangoo-cart-dob-year>
                </label>
            </div>
        </div>
        <p class="kangoo-cart-email-capture__message" data-kangoo-cart-email-message>
            <?php echo $has_identity ? esc_html__('Checkout details saved.', 'kangoo') : esc_html__('Enter your email and date of birth to unlock checkout.', 'kangoo'); ?>
        </p>
    </div>
    <?php
}
add_action('woocommerce_cart_collaterals', 'kangoo_render_cart_email_capture', 6);

function kangoo_render_cart_secure_checkout_banner() {
    static $rendered = false;

    if ($rendered) {
        return;
    }

    if (!function_exists('is_cart') || !is_cart()) {
        return;
    }

    $rendered = true;

    $image_url = get_theme_file_uri('/assets/images/secure-checkout-stripe.png');
    $avif_url = get_theme_file_uri('/assets/images/secure-checkout-stripe.avif');
    ?>
    <aside class="kangoo-cart-secure-checkout" aria-label="<?php esc_attr_e('Secure checkout powered by Stripe', 'kangoo'); ?>">
        <picture>
            <source srcset="<?php echo esc_url($avif_url); ?>" type="image/avif">
            <img src="<?php echo esc_url($image_url); ?>" width="808" height="264" alt="<?php esc_attr_e('Guaranteed safe and secure checkout powered by Stripe', 'kangoo'); ?>">
        </picture>
    </aside>
    <?php
}
add_action('woocommerce_cart_collaterals', 'kangoo_render_cart_secure_checkout_banner', 30);
add_action('wp_footer', 'kangoo_render_cart_secure_checkout_banner', 20);

function kangoo_guard_checkout_identity() {
    if (
        is_admin()
        || wp_doing_ajax()
        || !function_exists('is_checkout')
        || !is_checkout()
        || (function_exists('is_order_received_page') && is_order_received_page())
        || !function_exists('WC')
        || !WC()->cart
        || WC()->cart->is_empty()
    ) {
        return;
    }

    $email = kangoo_get_saved_checkout_email();
    $dob = kangoo_get_saved_checkout_dob();
    $age = $dob ? kangoo_calculate_age_from_date($dob) : null;

    if ($email && $age !== null && $age >= 18) {
        return;
    }

    wc_add_notice(__('Please enter your email and date of birth before secure checkout.', 'kangoo'), 'notice');
    wp_safe_redirect(wc_get_cart_url());
    exit;
}
add_action('template_redirect', 'kangoo_guard_checkout_identity', 9);

function kangoo_render_cart_frequently_bought() {
    if (!function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
        return;
    }

    $exclude_ids = array();

    foreach (WC()->cart->get_cart() as $cart_item) {
        if (!empty($cart_item['product_id'])) {
            $exclude_ids[] = (int) $cart_item['product_id'];
        }
    }

    $products = wc_get_products(array(
        'status'       => 'publish',
        'limit'        => 8,
        'orderby'      => 'rand',
        'stock_status' => 'instock',
        'exclude'      => array_unique($exclude_ids),
        'return'       => 'objects',
    ));

    if (!$products) {
        return;
    }
    ?>
    <section class="kangoo-cart-recommendations" aria-labelledby="kangoo-cart-recommendations-title">
        <div class="kangoo-cart-recommendations__header">
            <span><?php esc_html_e('Before checkout', 'kangoo'); ?></span>
            <h2 id="kangoo-cart-recommendations-title"><?php esc_html_e('Frequently bought together', 'kangoo'); ?></h2>
        </div>
        <div class="kangoo-cart-recommendations__track">
            <?php foreach ($products as $product) : ?>
                <article class="kangoo-cart-recommendation">
                    <a href="<?php echo esc_url($product->get_permalink()); ?>" class="kangoo-cart-recommendation__image" aria-label="<?php echo esc_attr($product->get_name()); ?>">
                        <?php echo $product->get_image('woocommerce_thumbnail'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </a>
                    <div class="kangoo-cart-recommendation__body">
                        <h3><a href="<?php echo esc_url($product->get_permalink()); ?>"><?php echo esc_html($product->get_name()); ?></a></h3>
                        <?php
                        if (function_exists('kangoo_reviews_theme_render_card_summary')) {
                            kangoo_reviews_theme_render_card_summary($product->get_id());
                        }
                        ?>
                        <div class="kangoo-cart-recommendation__price"><?php echo wp_kses_post($product->get_price_html()); ?></div>
                        <a class="button" href="<?php echo esc_url($product->get_permalink()); ?>"><?php esc_html_e('View', 'kangoo'); ?></a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
}
add_action('woocommerce_after_cart_collaterals', 'kangoo_render_cart_frequently_bought', 20);

function kangoo_cart_item_review_summary($name, $cart_item, $cart_item_key) {
    if (!function_exists('is_cart') || !is_cart() || empty($cart_item['product_id']) || !function_exists('kangoo_reviews_theme_render_card_summary')) {
        return $name;
    }

    ob_start();
    kangoo_reviews_theme_render_card_summary((int) $cart_item['product_id']);
    $summary = trim(ob_get_clean());

    if ($summary === '') {
        return $name;
    }

    return $name . '<div class="kangoo-cart-item-review-summary">' . $summary . '</div>';
}
add_filter('woocommerce_cart_item_name', 'kangoo_cart_item_review_summary', 20, 3);

function kangoo_mini_cart_quantity_stock_data($quantity_html, $cart_item, $cart_item_key) {
    $product = isset($cart_item['data']) && $cart_item['data'] instanceof WC_Product ? $cart_item['data'] : null;
    $max_quantity = $product ? (int) $product->get_max_purchase_quantity() : 0;
    $attributes = array();

    if (strpos($quantity_html, 'data-cart-item-key=') === false) {
        $attributes[] = sprintf('data-cart-item-key="%s"', esc_attr($cart_item_key));
    }

    if ($product && $max_quantity > 0 && strpos($quantity_html, 'data-stock-limit=') === false) {
        $attributes[] = sprintf('data-stock-limit="%d"', $max_quantity);
    }

    if ($product && strpos($quantity_html, 'data-line-total=') === false) {
        $quantity = isset($cart_item['quantity']) ? max(1, (int) $cart_item['quantity']) : 1;
        $line_total = isset($cart_item['line_subtotal']) ? (float) $cart_item['line_subtotal'] : ((float) $product->get_price() * $quantity);
        $line_total_html = function_exists('WC') && WC()->cart
            ? WC()->cart->get_product_subtotal($product, $quantity)
            : wc_price($line_total);
        $line_total_text = trim(wp_strip_all_tags($line_total_html));

        $attributes[] = sprintf('data-line-total="%s"', esc_attr(wc_format_decimal($line_total, 4)));
        $attributes[] = sprintf('data-line-total-text="%s"', esc_attr($line_total_text));
    }

    if (empty($attributes)) {
        return $quantity_html;
    }

    return str_replace(
        '<span class="quantity"',
        '<span class="quantity" ' . implode(' ', $attributes),
        $quantity_html
    );
}
add_filter('woocommerce_widget_cart_item_quantity', 'kangoo_mini_cart_quantity_stock_data', 10, 3);

add_filter('woocommerce_product_single_add_to_cart_text', function () {
    return __('ADD TO CART', 'kangoo');
});

function kangoo_ajax_update_mini_cart_quantity() {
    if (!function_exists('WC') || !WC()->cart) {
        wp_send_json_error(array(
            'message' => __('Cart is unavailable.', 'kangoo'),
        ), 400);
    }

    check_ajax_referer('kangoo_update_mini_cart_qty', 'nonce');

    $cart_item_key = isset($_POST['cart_item_key']) ? wc_clean(wp_unslash($_POST['cart_item_key'])) : '';
    $quantity = isset($_POST['quantity']) ? wc_stock_amount(wp_unslash($_POST['quantity'])) : 0;
    $quantity = max(0, (int) $quantity);

    if ('' === $cart_item_key || !array_key_exists($cart_item_key, WC()->cart->get_cart())) {
        wp_send_json_error(array(
            'message' => __('Unable to update this cart item.', 'kangoo'),
        ), 400);
    }

    $cart_item = WC()->cart->get_cart_item($cart_item_key);
    $product = isset($cart_item['data']) && $cart_item['data'] instanceof WC_Product ? $cart_item['data'] : null;
    $product_id = !empty($cart_item['variation_id']) ? (int) $cart_item['variation_id'] : (int) $cart_item['product_id'];
    $max_quantity = $product ? (int) $product->get_max_purchase_quantity() : 0;

    if (function_exists('kangoo_is_99p_product') && kangoo_is_99p_product($product_id)) {
        $quantity = min($quantity, 1);

        if ($quantity > 0 && function_exists('kangoo_get_cart_99p_quantity') && kangoo_get_cart_99p_quantity($cart_item_key) >= 1) {
            wp_send_json_error(array(
                'message' => __('99p trial pouches are limited to 1 per order.', 'kangoo'),
            ), 400);
        }
    }

    if ($max_quantity > 0) {
        $quantity = min($quantity, $max_quantity);
    }

    WC()->cart->set_quantity($cart_item_key, $quantity, true);
    WC()->cart->calculate_totals();

    wp_send_json_success(array(
        'mini_cart_html'  => kangoo_get_mini_cart_html(),
        'cart_badge_html' => kangoo_get_cart_badge_html(),
        'fragments'       => apply_filters('woocommerce_add_to_cart_fragments', array()),
        'cart_hash'       => WC()->cart->get_cart_hash(),
    ));
}
add_action('wp_ajax_kangoo_update_mini_cart_quantity', 'kangoo_ajax_update_mini_cart_quantity');
add_action('wp_ajax_nopriv_kangoo_update_mini_cart_quantity', 'kangoo_ajax_update_mini_cart_quantity');

function kangoo_ajax_remove_mini_cart_item() {
    if (!function_exists('WC') || !WC()->cart) {
        wp_send_json_error(array(
            'message' => __('Cart is unavailable.', 'kangoo'),
        ), 400);
    }

    check_ajax_referer('kangoo_remove_mini_cart_item', 'nonce');

    $cart_item_key = isset($_POST['cart_item_key']) ? wc_clean(wp_unslash($_POST['cart_item_key'])) : '';

    if ('' === $cart_item_key || !array_key_exists($cart_item_key, WC()->cart->get_cart())) {
        wp_send_json_error(array(
            'message' => __('Unable to remove this cart item.', 'kangoo'),
        ), 400);
    }

    WC()->cart->remove_cart_item($cart_item_key);
    WC()->cart->calculate_totals();

    wp_send_json_success(array(
        'mini_cart_html'  => kangoo_get_mini_cart_html(),
        'cart_badge_html' => kangoo_get_cart_badge_html(),
        'fragments'       => apply_filters('woocommerce_add_to_cart_fragments', array()),
        'cart_hash'       => WC()->cart->get_cart_hash(),
    ));
}
add_action('wp_ajax_kangoo_remove_mini_cart_item', 'kangoo_ajax_remove_mini_cart_item');
add_action('wp_ajax_nopriv_kangoo_remove_mini_cart_item', 'kangoo_ajax_remove_mini_cart_item');

function kangoo_ajax_clear_cart() {
    if (!function_exists('WC') || !WC()->cart) {
        wp_send_json_error(array(
            'message' => __('Cart is unavailable.', 'kangoo'),
        ), 400);
    }

    check_ajax_referer('kangoo_clear_cart', 'nonce');

    WC()->cart->empty_cart();
    WC()->cart->calculate_totals();

    if (WC()->session) {
        WC()->cart->set_session();
    }

    wp_send_json_success(array(
        'mini_cart_html'  => kangoo_get_mini_cart_html(),
        'cart_badge_html' => kangoo_get_cart_badge_html(),
        'fragments'       => apply_filters('woocommerce_add_to_cart_fragments', array()),
        'cart_hash'       => WC()->cart->get_cart_hash(),
    ));
}
add_action('wp_ajax_kangoo_clear_cart', 'kangoo_ajax_clear_cart');
add_action('wp_ajax_nopriv_kangoo_clear_cart', 'kangoo_ajax_clear_cart');

function kangoo_ajax_get_cart_fragments() {
    if (!function_exists('WC') || !WC()->cart) {
        wp_send_json_error(array(
            'message' => __('Cart is unavailable.', 'kangoo'),
        ), 400);
    }

    wp_send_json_success(kangoo_get_refreshed_cart_fragments_payload());
}
add_action('wp_ajax_kangoo_get_cart_fragments', 'kangoo_ajax_get_cart_fragments');
add_action('wp_ajax_nopriv_kangoo_get_cart_fragments', 'kangoo_ajax_get_cart_fragments');

add_filter('woocommerce_add_to_cart_quantity', function ($quantity) {
    error_log('woocommerce_add_to_cart_quantity => ' . $quantity);
    return $quantity;
}, 9999);

add_filter('woocommerce_add_cart_item_data', function ($cart_item_data, $product_id, $variation_id, $quantity) {
    error_log('woocommerce_add_cart_item_data quantity => ' . $quantity);
    error_log('woocommerce_add_cart_item_data product_id => ' . $product_id);

    error_log('woocommerce_add_cart_item_data variation_id => ' . $variation_id);
    return $cart_item_data;
}, 9999, 4);

add_action('woocommerce_add_to_cart', function ($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
    error_log('woocommerce_add_to_cart action quantity => ' . $quantity);
    error_log('woocommerce_add_to_cart action product_id => ' . $product_id);
    error_log('woocommerce_add_to_cart action variation_id => ' . $variation_id);
}, 9999, 6);

function kangoo_ajax_add_to_cart() {
    if (!function_exists('WC') || !WC()->cart) {
        wp_send_json_error(array(
            'message' => __('Cart is unavailable.', 'kangoo'),
        ), 400);
    }

    check_ajax_referer('kangoo_ajax_add_to_cart', 'nonce');

    // Prevent WooCommerce form-handler logic from auto-processing add-to-cart on this AJAX endpoint.
    if (isset($_REQUEST['add-to-cart'])) {
        unset($_REQUEST['add-to-cart']);
    }

    $product_id = isset($_POST['product_id']) ? absint(wp_unslash($_POST['product_id'])) : 0;
    if (!$product_id && isset($_POST['add-to-cart'])) {
        $product_id = absint(wp_unslash($_POST['add-to-cart']));
    }

    $quantity = isset($_POST['quantity']) ? wc_stock_amount(wp_unslash($_POST['quantity'])) : 1;
    $quantity = max(1, (int) $quantity);


    $variation_id = isset($_POST['variation_id']) ? absint(wp_unslash($_POST['variation_id'])) : 0;
    $variation = array();

    foreach ($_POST as $key => $value) {
        if (0 === strpos($key, 'attribute_')) {
            $variation[wc_clean(wp_unslash($key))] = wc_clean(wp_unslash($value));
        }
    }

    $cart_id = WC()->cart->generate_cart_id($product_id, $variation_id, $variation);
    $cart_item_key = WC()->cart->find_product_in_cart($cart_id);
    $stock_product = $variation_id ? wc_get_product($variation_id) : wc_get_product($product_id);
    $cart = WC()->cart->get_cart();
    $existing_quantity = $cart_item_key && isset($cart[$cart_item_key]['quantity']) ? (int) $cart[$cart_item_key]['quantity'] : 0;
    $target_product_id = $variation_id ? $variation_id : $product_id;

    if ($target_product_id && function_exists('kangoo_is_99p_product') && kangoo_is_99p_product($target_product_id)) {
        $other_99p_quantity = function_exists('kangoo_get_cart_99p_quantity') ? kangoo_get_cart_99p_quantity($cart_item_key) : 0;

        if ($quantity > 1 || $existing_quantity >= 1 || $other_99p_quantity >= 1) {
            wp_send_json_error(array(
                'message' => __('99p trial pouches are limited to 1 per order. You can still add any other products to your basket.', 'kangoo'),
            ), 409);
        }

        $quantity = 1;
    }

    if ($stock_product instanceof WC_Product) {
        $max_quantity = (int) $stock_product->get_max_purchase_quantity();

        if ($max_quantity > 0 && $existing_quantity + $quantity > $max_quantity) {
            $remaining = max(0, $max_quantity - $existing_quantity);
            $message = $remaining > 0 && $remaining < kangoo_low_stock_public_threshold()
                ? sprintf(_n('Limited availability: only %d more can be added.', 'Limited availability: only %d more can be added.', $remaining, 'kangoo'), $remaining)
                : __('Limited availability: reduce the quantity and try again.', 'kangoo');

            wp_send_json_error(array(
                'message' => $message,
            ), 409);
        }
    }

    error_log(
        sprintf(
            'kangoo_ajax_add_to_cart request => product_id:%d variation_id:%d quantity:%d cart_id:%s cart_item_key:%s',
            (int) $product_id,
            (int) $variation_id,
            (int) $quantity,
            (string) $cart_id,
            $cart_item_key ? (string) $cart_item_key : 'none'
        )
    );

    if ($cart_item_key) {
        $new_quantity = $existing_quantity + $quantity;

        error_log(
            sprintf(
                'kangoo_ajax_add_to_cart set_quantity => key:%s existing:%d requested:%d new:%d',
                (string) $cart_item_key,
                (int) $existing_quantity,
                (int) $quantity,
                (int) $new_quantity
            )
        );

        WC()->cart->set_quantity($cart_item_key, $new_quantity, true);
        $added = $cart_item_key;
    } else {
        error_log(
            sprintf(
                'kangoo_ajax_add_to_cart add_to_cart => product_id:%d variation_id:%d quantity:%d',
                (int) $product_id,
                (int) $variation_id,
                (int) $quantity
            )
        );
        $added = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation);
    }

    if (!$added) {
        wp_send_json_error(array(
            'message'     => __('Unable to add this item to the cart.', 'kangoo'),
            'product_url' => get_permalink($product_id),
        ), 400);
    }

    WC()->cart->calculate_totals();

    if (WC()->session) {
        WC()->cart->set_session();
    }

    wp_send_json_success(array(
        'fragments' => apply_filters('woocommerce_add_to_cart_fragments', array()),
        'cart_hash' => WC()->cart->get_cart_hash(),
    ));
}
add_action('wp_ajax_kangoo_ajax_add_to_cart', 'kangoo_ajax_add_to_cart');
add_action('wp_ajax_nopriv_kangoo_ajax_add_to_cart', 'kangoo_ajax_add_to_cart');

/* =========================================================================
AUTH DRAWER HELPERS
========================================================================= */

function kangoo_account_get_redirect_url() {
    if (function_exists('wc_get_page_permalink')) {
        $url = wc_get_page_permalink('myaccount');
        if ($url) {
            return $url;
        }
    }

    return home_url('/my-account/');
}

function kangoo_account_get_user_payload($user) {
    return array(
        'id'           => (int) $user->ID,
        'display_name' => $user->display_name,
        'email'        => $user->user_email,
        'account_url'  => kangoo_account_get_redirect_url(),
        'rewards_points' => function_exists('kangoo_rewards_get_balance') ? kangoo_rewards_get_balance($user->ID) : 0,
    );
}

function kangoo_ajax_account_status() {
    check_ajax_referer('kangoo_account_status', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_success(array(
            'logged_in' => false,
        ));
    }

    $user = wp_get_current_user();

    wp_send_json_success(array(
        'logged_in' => true,
        'user'      => kangoo_account_get_user_payload($user),
    ));
}
add_action('wp_ajax_kangoo_account_status', 'kangoo_ajax_account_status');
add_action('wp_ajax_nopriv_kangoo_account_status', 'kangoo_ajax_account_status');

function kangoo_ajax_account_login() {
    check_ajax_referer('kangoo_account_login', 'nonce');

    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        wp_send_json_success(array(
            'message' => __('You are already signed in.', 'kangoo'),
            'user'    => kangoo_account_get_user_payload($user),
        ));
    }

    $email_or_username = isset($_POST['login']) ? sanitize_text_field(wp_unslash($_POST['login'])) : '';
    $password = isset($_POST['password']) ? (string) wp_unslash($_POST['password']) : '';
    $remember = !empty($_POST['remember']);

    if ('' === $email_or_username || '' === $password) {
        wp_send_json_error(array(
            'message' => __('Please enter your email/username and password.', 'kangoo'),
            'field'   => 'login',
        ), 400);
    }

    $user_login = $email_or_username;
    if (is_email($email_or_username)) {
        $user = get_user_by('email', $email_or_username);
        if ($user) {
            $user_login = $user->user_login;
        }
    }

    $creds = array(
        'user_login'    => $user_login,
        'user_password' => $password,
        'remember'      => $remember,
    );

    $signed_in_user = wp_signon($creds, is_ssl());

    if (is_wp_error($signed_in_user)) {
        wp_send_json_error(array(
            'message' => kangoo_account_login_error_message($signed_in_user),
            'field'   => 'login',
        ), 400);
    }

    wp_set_current_user($signed_in_user->ID);

    wp_send_json_success(array(
        'message' => __('Signed in successfully.', 'kangoo'),
        'user'    => kangoo_account_get_user_payload($signed_in_user),
    ));
}
add_action('wp_ajax_nopriv_kangoo_account_login', 'kangoo_ajax_account_login');
add_action('wp_ajax_kangoo_account_login', 'kangoo_ajax_account_login');

function kangoo_account_login_error_message($error) {
    $code = is_wp_error($error) ? $error->get_error_code() : '';

    if (in_array($code, array('incorrect_password', 'invalid_username', 'invalid_email', 'empty_username', 'empty_password'), true)) {
        return __('The email/username or password is incorrect. Please try again or reset your password.', 'kangoo');
    }

    $message = is_wp_error($error) ? $error->get_error_message() : (string) $error;
    $message = wp_strip_all_tags(html_entity_decode($message, ENT_QUOTES, get_bloginfo('charset')));

    return $message ? $message : __('Unable to sign in. Please try again.', 'kangoo');
}

function kangoo_ajax_account_register() {
    check_ajax_referer('kangoo_account_register', 'nonce');

    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        wp_send_json_success(array(
            'message' => __('You are already signed in.', 'kangoo'),
            'user'    => kangoo_account_get_user_payload($user),
        ));
    }

    $first_name = isset($_POST['first_name']) ? sanitize_text_field(wp_unslash($_POST['first_name'])) : '';
    $last_name  = isset($_POST['last_name']) ? sanitize_text_field(wp_unslash($_POST['last_name'])) : '';
    $email      = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
    $password   = isset($_POST['password']) ? (string) wp_unslash($_POST['password']) : '';
    $confirm    = isset($_POST['confirm_password']) ? (string) wp_unslash($_POST['confirm_password']) : '';

    if ('' === $first_name) {
        wp_send_json_error(array(
            'message' => __('Please enter your first name.', 'kangoo'),
            'field'   => 'first_name',
        ), 400);
    }

    if ('' === $last_name) {
        wp_send_json_error(array(
            'message' => __('Please enter your last name.', 'kangoo'),
            'field'   => 'last_name',
        ), 400);
    }

    if ('' === $email || !is_email($email)) {
        wp_send_json_error(array(
            'message' => __('Please enter a valid email address.', 'kangoo'),
            'field'   => 'email',
        ), 400);
    }

    if (email_exists($email)) {
        wp_send_json_error(array(
            'message' => __('An account with this email already exists.', 'kangoo'),
            'field'   => 'email',
        ), 400);
    }

    if (strlen($password) < 8) {
        wp_send_json_error(array(
            'message' => __('Your password must be at least 8 characters long.', 'kangoo'),
            'field'   => 'password',
        ), 400);
    }

    if ($password !== $confirm) {
        wp_send_json_error(array(
            'message' => __('Passwords do not match.', 'kangoo'),
            'field'   => 'confirm_password',
        ), 400);
    }

    $base_username = sanitize_user(current(explode('@', $email)), true);
    $username = $base_username ? $base_username : 'customer';
    $suffix = 1;

    while (username_exists($username)) {
        $username = $base_username . $suffix;
        $suffix++;
    }

    $user_id = wp_insert_user(array(
        'user_login'   => $username,
        'user_pass'    => $password,
        'user_email'   => $email,
        'first_name'   => $first_name,
        'last_name'    => $last_name,
        'display_name' => trim($first_name . ' ' . $last_name),
        'role'         => 'customer',
    ));

    if (is_wp_error($user_id)) {
        wp_send_json_error(array(
            'message' => $user_id->get_error_message(),
        ), 400);
    }

    $user = get_user_by('id', $user_id);

    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id, true);

    do_action('wp_login', $user->user_login, $user);

    wp_send_json_success(array(
        'message' => __('Account created successfully.', 'kangoo'),
        'user'    => kangoo_account_get_user_payload($user),
    ));
}
add_action('wp_ajax_nopriv_kangoo_account_register', 'kangoo_ajax_account_register');
add_action('wp_ajax_kangoo_account_register', 'kangoo_ajax_account_register');

function kangoo_ajax_account_logout() {
    check_ajax_referer('kangoo_account_logout', 'nonce');

    wp_logout();

    wp_send_json_success(array(
        'message' => __('Signed out successfully.', 'kangoo'),
    ));
}
add_action('wp_ajax_kangoo_account_logout', 'kangoo_ajax_account_logout');

/* =========================================================================
MEGA MENU HELPERS
========================================================================= */

function kangoo_acf_option_value($keys, $default = null) {
    if (!function_exists('get_field')) {
        return $default;
    }

    foreach ((array) $keys as $key) {
        $value = get_field($key, 'option');

        if ($value === null) {
            continue;
        }

        if (is_string($value) && trim($value) === '') {
            continue;
        }

        if (is_array($value) && empty($value)) {
            continue;
        }

        return $value;
    }

    return $default;
}

function kangoo_acf_link_url($link) {
    if (is_array($link) && !empty($link['url'])) {
        return (string) $link['url'];
    }

    if (is_string($link) && $link !== '') {
        return $link;
    }

    return '';
}

function kangoo_acf_link_target($link) {
    if (is_array($link) && !empty($link['target'])) {
        return (string) $link['target'];
    }

    return '_self';
}

function kangoo_normalize_mega_menu_source($source) {
    if (is_array($source)) {
        if (isset($source['value'])) {
            $source = $source['value'];
        } elseif (isset($source['label'])) {
            $source = $source['label'];
        } else {
            $source = reset($source);
        }
    }

    $value = sanitize_title((string) $source);

    if (in_array($value, array('brand', 'brands'), true)) {
        return 'brands';
    }

    if (in_array($value, array('strength', 'strengths'), true)) {
        return 'strengths';
    }

    if (in_array($value, array('type', 'types'), true)) {
        return 'types';
    }

    if (in_array($value, array('flavour', 'flavours', 'flavor', 'flavors', 'flavour-cards', 'flavour_cards', 'flavor-cards', 'flavor_cards'), true)) {
        return 'flavours';
    }

    return $value;
}

function kangoo_get_mega_menu_settings() {
    $settings = array(
        'enabled'               => 0,
        'trigger_label'         => '',
        'mobile_drawer_title'   => 'Browse',
        'top_links'             => array(),
        'desktop_sidebar_links' => array(),
        'brands_panel_title'    => 'Brands',
        'brands_view_all_label' => '',
        'brands_view_all_link'  => array(),
        'brand_cards'           => array(),
        'strengths_panel_title' => 'Strengths',
        'strength_cards'        => array(),
        'types_panel_title'     => 'Types',
        'type_cards'            => array(),
        'flavours_panel_title'  => 'Flavours',
        'flavour_cards'         => array(),
        'mobile_sections'       => array(),
    );

    if (!function_exists('get_field')) {
        return $settings;
    }

    $settings['enabled'] = (int) kangoo_acf_option_value(
        array('mega_menu_enabled', 'enable_mega_menu'),
        0
    );

    $settings['trigger_label'] = (string) kangoo_acf_option_value(
        array('mega_menu_trigger_label', 'desktop_trigger_label'),
        ''
    );

    $settings['mobile_drawer_title'] = (string) kangoo_acf_option_value(
        array('mega_menu_mobile_label', 'mobile_drawer_title'),
        'Browse'
    );

    $settings['brands_panel_title'] = (string) kangoo_acf_option_value(
        array('mega_menu_brand_panel_title', 'brands_panel_title'),
        'Brands'
    );

    $settings['brands_view_all_label'] = (string) kangoo_acf_option_value(
        array('mega_menu_brand_view_all_label', 'brands_view_all_label'),
        ''
    );

    $settings['brands_view_all_link'] = kangoo_acf_option_value(
        array('mega_menu_brand_view_all_link', 'brands_view_all_link', 'brands_view_all_url'),
        array()
    );

    $settings['strengths_panel_title'] = (string) kangoo_acf_option_value(
        array('mega_menu_strength_panel_title', 'strengths_panel_title'),
        'Strengths'
    );

    $settings['types_panel_title'] = (string) kangoo_acf_option_value(
        array('mega_menu_type_panel_title', 'types_panel_title', 'type_panel_title'),
        'Type'
    );

    $settings['flavours_panel_title'] = (string) kangoo_acf_option_value(
        array('mega_menu_flavour_panel_title', 'flavours_panel_title'),
        'Flavours'
    );

    $top_links = kangoo_acf_option_value(
        array('mega_menu_top_links', 'top_links'),
        array()
    );

    if (is_array($top_links)) {
        foreach ($top_links as $item) {
            if (!is_array($item)) {
                continue;
            }

            $settings['top_links'][] = array(
                'label' => isset($item['label']) ? (string) $item['label'] : '',
                'link'  => isset($item['link']) ? $item['link'] : (isset($item['url']) ? $item['url'] : array()),
            );
        }
    }

    $desktop_links = kangoo_acf_option_value(
        array('mega_menu_desktop_links', 'desktop_sidebar_links'),
        array()
    );

    if (is_array($desktop_links)) {
        foreach ($desktop_links as $item) {
            if (!is_array($item)) {
                continue;
            }

            $settings['desktop_sidebar_links'][] = array(
                'label'     => isset($item['label']) ? (string) $item['label'] : '',
                'type'      => isset($item['type']) ? (string) $item['type'] : 'panel',
                'panel_key' => isset($item['panel_key']) ? (string) $item['panel_key'] : '',
                'link'      => isset($item['link']) ? $item['link'] : (isset($item['url']) ? $item['url'] : array()),
            );
        }
    }

    if (empty($settings['top_links']) && !empty($settings['desktop_sidebar_links'])) {
        foreach ($settings['desktop_sidebar_links'] as $item) {
            if (empty($item['type']) || strtolower(trim((string) $item['type'])) !== 'link') {
                continue;
            }

            $settings['top_links'][] = array(
                'label' => $item['label'],
                'link'  => isset($item['link']) ? $item['link'] : array(),
            );
        }
    }

    $brand_cards = kangoo_acf_option_value(
        array('mega_menu_brand_cards', 'brand_cards'),
        array()
    );

    if (is_array($brand_cards)) {
        foreach ($brand_cards as $item) {
            if (!is_array($item)) {
                continue;
            }

            $manual_link = isset($item['link']) ? $item['link'] : (isset($item['url']) ? $item['url'] : array());
            $resolved_card = kangoo_resolve_brand_category_card(
                isset($item['label']) ? $item['label'] : '',
                isset($item['label']) ? (string) $item['label'] : '',
                isset($item['image']) ? $item['image'] : null,
                $manual_link,
                array(
                    'featured'   => !empty($item['featured']),
                    'badge_text' => isset($item['badge_text']) ? (string) $item['badge_text'] : '',
                )
            );

            $settings['brand_cards'][] = array(
                'label'      => isset($resolved_card['label']) ? (string) $resolved_card['label'] : '',
                'link'       => isset($resolved_card['link']) ? $resolved_card['link'] : array(),
                'image'      => isset($resolved_card['image']) ? $resolved_card['image'] : array(),
                'featured'   => !empty($item['featured']),
                'badge_text' => isset($item['badge_text']) ? (string) $item['badge_text'] : '',
            );
        }
    }

    $strength_cards = kangoo_acf_option_value(
        array('mega_menu_strength_cards', 'strength_cards'),
        array()
    );

    $type_cards = kangoo_acf_option_value(
        array('mega_menu_type_cards', 'type_cards'),
        array()
    );

    if (is_array($strength_cards)) {
        foreach ($strength_cards as $item) {
            if (!is_array($item)) {
                continue;
            }

            $settings['strength_cards'][] = array(
                'label'       => isset($item['label']) ? (string) $item['label'] : '',
                'description' => isset($item['description']) ? (string) $item['description'] : (isset($item['short_description']) ? (string) $item['short_description'] : ''),
                'mg_range'    => isset($item['mg_range']) ? (string) $item['mg_range'] : '',
                'link'        => isset($item['link']) ? $item['link'] : (isset($item['url']) ? $item['url'] : array()),
                'dots_on'     => isset($item['dots_on']) ? (int) $item['dots_on'] : (isset($item['dots_filled']) ? (int) $item['dots_filled'] : 0),
                'dot_color'   => isset($item['dot_color']) ? (string) $item['dot_color'] : '#4da3ff',
            );
        }
    }

    if (is_array($type_cards)) {
        foreach ($type_cards as $item) {
            if (!is_array($item)) {
                continue;
            }

            $settings['type_cards'][] = array(
                'label'            => isset($item['label']) ? (string) $item['label'] : '',
                'link'             => isset($item['link']) ? $item['link'] : (isset($item['url']) ? $item['url'] : array()),
                'background_color' => isset($item['background_color']) ? (string) $item['background_color'] : '#1b1d23',
                'text_color'       => isset($item['text_color']) ? (string) $item['text_color'] : '#ffffff',
                'icon'             => isset($item['icon']) && is_array($item['icon']) ? $item['icon'] : array(),
            );
        }
    }

    $flavour_cards = kangoo_acf_option_value(
        array('mega_menu_flavour_cards', 'flavour_cards'),
        array()
    );

    if (is_array($flavour_cards)) {
        foreach ($flavour_cards as $item) {
            if (!is_array($item)) {
                continue;
            }

            $settings['flavour_cards'][] = array(
                'label'            => isset($item['label']) ? (string) $item['label'] : '',
                'link'             => isset($item['link']) ? $item['link'] : (isset($item['url']) ? $item['url'] : array()),
                'background_color' => isset($item['background_color']) ? (string) $item['background_color'] : '#1b1d23',
                'text_color'       => isset($item['text_color']) ? (string) $item['text_color'] : '#ffffff',
                'icon'             => isset($item['icon']) && is_array($item['icon']) ? $item['icon'] : array(),
            );
        }
    }

    $mobile_sections = kangoo_acf_option_value(
        array('mega_menu_mobile_sections', 'mobile_sections'),
        array()
    );

    if (is_array($mobile_sections)) {
        foreach ($mobile_sections as $item) {
            if (!is_array($item)) {
                continue;
            }

            $custom_links = array();

            if (!empty($item['custom_links']) && is_array($item['custom_links'])) {
                foreach ($item['custom_links'] as $custom_item) {
                    if (!is_array($custom_item)) {
                        continue;
                    }

                    $custom_links[] = array(
                        'label' => isset($custom_item['label']) ? (string) $custom_item['label'] : '',
                        'link'  => isset($custom_item['link']) ? $custom_item['link'] : (isset($custom_item['url']) ? $custom_item['url'] : array()),
                    );
                }
            }

            $settings['mobile_sections'][] = array(
                'label'           => isset($item['label']) ? (string) $item['label'] : (isset($item['section_label']) ? (string) $item['section_label'] : ''),
                'source'          => isset($item['source']) ? kangoo_normalize_mega_menu_source($item['source']) : '',
                'open_by_default' => !empty($item['open_by_default']),
                'custom_links'    => $custom_links,
            );
        }
    }

    if (empty($settings['mobile_sections']) && !empty($settings['desktop_sidebar_links'])) {
        foreach ($settings['desktop_sidebar_links'] as $item) {
            if (empty($item['type']) || strtolower((string) $item['type']) !== 'panel') {
                continue;
            }

            $panel_key = strtolower(trim((string) $item['panel_key']));
            if (!in_array($panel_key, array('brands', 'strengths', 'types', 'flavours'), true)) {
                continue;
            }

            $settings['mobile_sections'][] = array(
                'label'           => $item['label'],
                'source'          => $panel_key,
                'open_by_default' => false,
                'custom_links'    => array(),
            );
        }
    }

    return $settings;
}

function kangoo_get_age_gate_settings() {
    $settings = array(
        'enabled'    => true,
        'title'      => __('Confirm your age', 'kangoo'),
        'message'    => __('Please confirm you are 18 or over to browse Kangoo Pouches.', 'kangoo'),
        'smallprint' => __('Nicotine products are not for anyone under 18.', 'kangoo'),
    );

    if (!function_exists('get_field')) {
        $settings['enabled'] = (bool) get_option('kangoo_age_gate_enabled', 1);

        return $settings;
    }

    if (get_option('options_kangoo_age_gate_enabled', null) !== null) {
        $settings['enabled'] = (bool) get_field('kangoo_age_gate_enabled', 'option');
    }

    $text_fields = array(
        'title'      => 'kangoo_age_gate_title',
        'message'    => 'kangoo_age_gate_message',
        'smallprint' => 'kangoo_age_gate_smallprint',
    );

    foreach ($text_fields as $setting_key => $field_key) {
        $value = get_field($field_key, 'option');

        if (is_string($value) && trim($value) !== '') {
            $settings[$setting_key] = trim($value);
        }
    }

    return $settings;
}

function kangoo_is_age_gate_enabled() {
    $settings = kangoo_get_age_gate_settings();

    return !empty($settings['enabled']);
}

function kangoo_register_age_gate_acf_fields() {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group(array(
        'key' => 'group_kangoo_age_gate',
        'title' => __('Age Gate', 'kangoo'),
        'fields' => array(
            array(
                'key' => 'field_kangoo_age_gate_enabled',
                'label' => __('Enable Age Gate', 'kangoo'),
                'name' => 'kangoo_age_gate_enabled',
                'type' => 'true_false',
                'ui' => 1,
                'default_value' => 1,
                'instructions' => __('Show the 18+ confirmation screen before visitors browse the site.', 'kangoo'),
            ),
            array(
                'key' => 'field_kangoo_age_gate_title',
                'label' => __('Age Gate Title', 'kangoo'),
                'name' => 'kangoo_age_gate_title',
                'type' => 'text',
                'default_value' => __('Confirm your age', 'kangoo'),
                'maxlength' => 60,
            ),
            array(
                'key' => 'field_kangoo_age_gate_message',
                'label' => __('Age Gate Message', 'kangoo'),
                'name' => 'kangoo_age_gate_message',
                'type' => 'textarea',
                'rows' => 2,
                'default_value' => __('Please confirm you are 18 or over to browse Kangoo Pouches.', 'kangoo'),
                'maxlength' => 160,
            ),
            array(
                'key' => 'field_kangoo_age_gate_smallprint',
                'label' => __('Age Gate Small Note', 'kangoo'),
                'name' => 'kangoo_age_gate_smallprint',
                'type' => 'text',
                'default_value' => __('Nicotine products are not for anyone under 18.', 'kangoo'),
                'maxlength' => 120,
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
add_action('acf/init', 'kangoo_register_age_gate_acf_fields');

function kangoo_sanitize_age_gate_enabled($value) {
    return !empty($value) ? 1 : 0;
}

function kangoo_register_theme_options() {
    register_setting('kangoo_theme_options', 'kangoo_age_gate_enabled', array(
        'sanitize_callback' => 'kangoo_sanitize_age_gate_enabled',
        'default'           => 1,
    ));

    add_settings_section(
        'kangoo_theme_options_section',
        __('Kangoo Theme Options', 'kangoo'),
        'kangoo_theme_options_section_callback',
        'kangoo-theme-options'
    );

    add_settings_field(
        'kangoo_age_gate_enabled',
        __('Enable age gate', 'kangoo'),
        'kangoo_age_gate_enabled_field',
        'kangoo-theme-options',
        'kangoo_theme_options_section'
    );
}
add_action('admin_init', 'kangoo_register_theme_options');

function kangoo_theme_options_section_callback() {
    echo '<p>' . esc_html__('Show or hide the frontend age verification gate for UK nicotine/caffeine shoppers.', 'kangoo') . '</p>';
}

function kangoo_age_gate_enabled_field() {
    $enabled = get_option('kangoo_age_gate_enabled', 1);
    ?>
    <label for="kangoo_age_gate_enabled">
        <input type="checkbox" id="kangoo_age_gate_enabled" name="kangoo_age_gate_enabled" value="1" <?php checked(1, $enabled); ?> />
        <?php esc_html_e('Display the age gate overlay on the frontend.', 'kangoo'); ?>
    </label>
    <p class="description"><?php esc_html_e('Visitors must confirm they are 18 or older before browsing the site.', 'kangoo'); ?></p>
    <?php
}

function kangoo_render_theme_options_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Kangoo Theme Options', 'kangoo'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('kangoo_theme_options');
            do_settings_sections('kangoo-theme-options');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function kangoo_theme_options_menu() {
    return;
}
add_action('admin_menu', 'kangoo_theme_options_menu');

require_once get_template_directory() . '/inc/theme-appearance.php';
require_once get_template_directory() . '/inc/pack-pricing-admin.php';
require_once get_template_directory() . '/inc/product-url-brand-audit.php';
require_once get_template_directory() . '/inc/email-templates.php';
require_once get_template_directory() . '/inc/shipping-operations.php';
require_once get_template_directory() . '/inc/cart-recommendations.php';
require_once get_template_directory() . '/inc/event-themes.php';
require_once get_template_directory() . '/inc/reviews.php';

function kangoo_acf_add_types_panel_choice($field) {
    if (!empty($field['choices']) && !array_key_exists('types', $field['choices'])) {
        $field['choices']['types'] = __('Types', 'kangoo');
    }

    return $field;
}
add_filter('acf/load_field/key=field_69f5218bc596d', 'kangoo_acf_add_types_panel_choice');

function kangoo_acf_add_home_product_sources($field) {
    if (empty($field['choices']) || !is_array($field['choices'])) {
        return $field;
    }

    $field['choices']['summer_collection'] = __('Summer collection', 'kangoo');
    $field['choices']['pouches_99p'] = __('99p Pouches', 'kangoo');

    return $field;
}
add_filter('acf/load_field/name=source', 'kangoo_acf_add_home_product_sources');

function kangoo_acf_field_parent_reference($parent) {
    if ($parent === null || $parent === false || $parent === '') {
        return '';
    }

    if (is_string($parent) || is_numeric($parent)) {
        return (string) $parent;
    }

    if (is_array($parent)) {
        foreach (array('key', 'ID', 'id', 'name') as $key) {
            if (!empty($parent[$key]) && (is_string($parent[$key]) || is_numeric($parent[$key]))) {
                return (string) $parent[$key];
            }
        }

        return '';
    }

    if (is_object($parent)) {
        foreach (array('key', 'ID', 'id', 'post_name') as $key) {
            if (!empty($parent->{$key}) && (is_string($parent->{$key}) || is_numeric($parent->{$key}))) {
                return (string) $parent->{$key};
            }
        }
    }

    return '';
}

function kangoo_acf_field_parent_names($field) {
    static $is_resolving = false;
    static $cache = array();

    $names = array();
    $parent = kangoo_acf_field_parent_reference(isset($field['parent']) ? $field['parent'] : '');
    $guard = 0;

    if ($parent === '' || $is_resolving) {
        return $names;
    }

    if (isset($cache[$parent])) {
        return $cache[$parent];
    }

    $cache_key = $parent;
    $is_resolving = true;

    while ($parent !== '' && $guard < 8) {
        if (function_exists('acf_get_raw_field')) {
            $parent_field = acf_get_raw_field($parent);
        } elseif (function_exists('acf_get_field')) {
            $parent_field = acf_get_field($parent);
        } else {
            break;
        }

        if (!is_array($parent_field)) {
            break;
        }

        if (!empty($parent_field['name'])) {
            $names[] = (string) $parent_field['name'];
        }

        $parent = kangoo_acf_field_parent_reference(isset($parent_field['parent']) ? $parent_field['parent'] : '');
        $guard++;
    }

    $is_resolving = false;
    $cache[$cache_key] = $names;

    return $names;
}

function kangoo_acf_field_has_parent_name($field, $parent_names) {
    $names = kangoo_acf_field_parent_names($field);

    foreach ((array) $parent_names as $parent_name) {
        if (in_array($parent_name, $names, true)) {
            return true;
        }
    }

    return false;
}

function kangoo_product_category_select_choices($parent = 0, $depth = 0) {
    static $cache = array();

    if (!taxonomy_exists('product_cat')) {
        return array();
    }

    $cache_key = (int) $parent . ':' . (int) $depth;

    if (isset($cache[$cache_key])) {
        return $cache[$cache_key];
    }

    $choices = array();
    $terms = get_terms(array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        'parent'     => (int) $parent,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ));

    if (is_wp_error($terms) || !is_array($terms)) {
        return $choices;
    }

    foreach ($terms as $term) {
        if (!$term instanceof WP_Term) {
            continue;
        }

        $prefix = $depth > 0 ? str_repeat('- ', $depth) : '';
        $choices[(string) $term->term_id] = $prefix . $term->name;
        $choices += kangoo_product_category_select_choices($term->term_id, $depth + 1);
    }

    $cache[$cache_key] = $choices;

    return $choices;
}

function kangoo_acf_brand_category_selector_field($field) {
    $name = isset($field['name']) ? (string) $field['name'] : '';
    $is_home_brand_card = $name === 'brand_name' && kangoo_acf_field_has_parent_name($field, array('brands_cards'));
    $is_mega_brand_card = $name === 'label' && kangoo_acf_field_has_parent_name($field, array('mega_menu_brand_cards', 'brand_cards'));

    if (!$is_home_brand_card && !$is_mega_brand_card) {
        return $field;
    }

    $field['label'] = __('Brand category', 'kangoo');
    $field['instructions'] = __('Choose a product category. The card title, link, and image are pulled from that category automatically.', 'kangoo');
    $field['type'] = 'select';
    $field['choices'] = kangoo_product_category_select_choices();
    $field['allow_null'] = 1;
    $field['multiple'] = 0;
    $field['ui'] = 1;
    $field['ajax'] = 0;
    $field['placeholder'] = __('Select a brand category', 'kangoo');

    return $field;
}
add_filter('acf/load_field/name=brand_name', 'kangoo_acf_brand_category_selector_field');
add_filter('acf/load_field/name=label', 'kangoo_acf_brand_category_selector_field');

function kangoo_acf_brand_category_selector_value($value, $post_id, $field) {
    if (is_wp_error($value)) {
        return '';
    }

    $name = isset($field['name']) ? (string) $field['name'] : '';
    $is_home_brand_card = $name === 'brand_name' && kangoo_acf_field_has_parent_name($field, array('brands_cards'));
    $is_mega_brand_card = $name === 'label' && kangoo_acf_field_has_parent_name($field, array('mega_menu_brand_cards', 'brand_cards'));

    if (!$is_home_brand_card && !$is_mega_brand_card) {
        return $value;
    }

    $term = kangoo_resolve_product_category_term($value);

    return $term ? (string) $term->term_id : $value;
}
add_filter('acf/load_value/name=brand_name', 'kangoo_acf_brand_category_selector_value', 10, 3);
add_filter('acf/load_value/name=label', 'kangoo_acf_brand_category_selector_value', 10, 3);

function kangoo_acf_hide_legacy_brand_card_fields($field) {
    $name = isset($field['name']) ? (string) $field['name'] : '';

    if (in_array($name, array('brand_image', 'brand_link'), true)) {
        return false;
    }

    if (in_array($name, array('image', 'link'), true) && kangoo_acf_field_has_parent_name($field, array('mega_menu_brand_cards', 'brand_cards'))) {
        return false;
    }

    return $field;
}
add_filter('acf/prepare_field/name=brand_image', 'kangoo_acf_hide_legacy_brand_card_fields');
add_filter('acf/prepare_field/name=brand_link', 'kangoo_acf_hide_legacy_brand_card_fields');
add_filter('acf/prepare_field/name=image', 'kangoo_acf_hide_legacy_brand_card_fields');
add_filter('acf/prepare_field/name=link', 'kangoo_acf_hide_legacy_brand_card_fields');

function kangoo_sold_out_availability_text($availability, $product) {
    if ($product && !$product->is_in_stock()) {
        return __('Sold Out', 'kangoo');
    }

    if ($product instanceof WC_Product && function_exists('kangoo_get_low_stock_message')) {
        return kangoo_get_low_stock_message($product);
    }

    return '';
}
add_filter('woocommerce_get_availability_text', 'kangoo_sold_out_availability_text', 10, 2);

function kangoo_product_filter_taxonomy($filter) {
    $taxonomies = array(
        'brand'    => 'pa_brand',
        'flavour'  => 'pa_flavour',
        'strength' => 'pa_strength',
    );

    $taxonomy = isset($taxonomies[$filter]) ? $taxonomies[$filter] : '';

    return taxonomy_exists($taxonomy) ? $taxonomy : '';
}

function kangoo_product_filter_fallback_options($filter) {
    if ($filter === 'brand') {
        return array(
            'zyn'           => __('ZYN', 'kangoo'),
            'velo'          => __('VELO', 'kangoo'),
            'killa'         => __('KILLA', 'kangoo'),
            'pablo'         => __('PABLO', 'kangoo'),
            'fumi'          => __('FUMi', 'kangoo'),
            'nordic-spirit' => __('Nordic Spirit', 'kangoo'),
            'xqs'           => __('XQS', 'kangoo'),
        );
    }

    if ($filter === 'flavour') {
        return array(
            'mint'         => __('Mint', 'kangoo'),
            'fruit'        => __('Fruit', 'kangoo'),
            'berry'        => __('Berry', 'kangoo'),
            'citrus'       => __('Citrus', 'kangoo'),
            'tropical'     => __('Tropical', 'kangoo'),
            'sweet-candy'  => __('Sweet / Candy', 'kangoo'),
            'coffee'       => __('Coffee', 'kangoo'),
            'cola-soda'    => __('Cola / Soda', 'kangoo'),
            'energy-drink' => __('Energy Drink', 'kangoo'),
            'spice'        => __('Spice', 'kangoo'),
            'herbal'       => __('Herbal', 'kangoo'),
            'mixed-fusion' => __('Mixed / Fusion', 'kangoo'),
        );
    }

    return array(
        'light'        => __('Light', 'kangoo'),
        'medium'       => __('Medium', 'kangoo'),
        'strong'       => __('Strong', 'kangoo'),
        'extra-strong' => __('Extra Strong', 'kangoo'),
    );
}

function kangoo_product_filter_options($filter) {
    if ($filter === 'brand') {
        $options = array();
        $taxonomy = kangoo_product_filter_taxonomy($filter);

        if ($taxonomy) {
            $terms = get_terms(array(
                'taxonomy'   => $taxonomy,
                'hide_empty' => true,
            ));

            if (!is_wp_error($terms) && !empty($terms)) {
                foreach ($terms as $term) {
                    $options[$term->slug] = $term->name;
                }
            }
        }

        $fallback_options = kangoo_product_filter_fallback_options($filter);

        foreach ($fallback_options as $brand_slug => $brand_label) {
            if (isset($options[$brand_slug])) {
                continue;
            }

            $has_category = taxonomy_exists('product_cat') && get_term_by('slug', $brand_slug, 'product_cat');
            $has_tag = taxonomy_exists('product_tag') && get_term_by('slug', $brand_slug, 'product_tag');

            if ($has_category || $has_tag) {
                $options[$brand_slug] = $brand_label;
            }
        }

        return !empty($options) ? $options : $fallback_options;
    }

    $taxonomy = kangoo_product_filter_taxonomy($filter);

    if ($taxonomy) {
        $terms = get_terms(array(
            'taxonomy'   => $taxonomy,
            'hide_empty' => true,
        ));

        if (!is_wp_error($terms) && !empty($terms)) {
            $options = array();

            foreach ($terms as $term) {
                $options[$term->slug] = $term->name;
            }

            return $options;
        }
    }

    return kangoo_product_filter_fallback_options($filter);
}

function kangoo_add_product_filter_query($query, $filter, $value) {
    $value = sanitize_title((string) $value);

    if ($value === '') {
        return;
    }

    if ($filter === 'brand') {
        $brand_clauses = array();
        $brand_taxonomies = array_filter(array(
            kangoo_product_filter_taxonomy('brand'),
            taxonomy_exists('product_cat') ? 'product_cat' : '',
            taxonomy_exists('product_tag') ? 'product_tag' : '',
        ));

        foreach ($brand_taxonomies as $brand_taxonomy) {
            if (!get_term_by('slug', $value, $brand_taxonomy)) {
                continue;
            }

            $brand_clauses[] = array(
                'taxonomy' => $brand_taxonomy,
                'field'    => 'slug',
                'terms'    => $value,
            );
        }

        if (empty($brand_clauses)) {
            return;
        }

        $tax_query = (array) $query->get('tax_query');
        $tax_query[] = count($brand_clauses) === 1
            ? $brand_clauses[0]
            : array_merge(array('relation' => 'OR'), $brand_clauses);

        $query->set('tax_query', $tax_query);
        return;
    }

    $taxonomy = kangoo_product_filter_taxonomy($filter);

    if ($taxonomy && get_term_by('slug', $value, $taxonomy)) {
        $tax_query = (array) $query->get('tax_query');
        $tax_query[] = array(
            'taxonomy' => $taxonomy,
            'field'    => 'slug',
            'terms'    => $value,
        );

        $query->set('tax_query', $tax_query);
    }
}

function kangoo_remove_product_cat_tax_query_clauses($tax_query) {
    if (!is_array($tax_query)) {
        return array();
    }

    $cleaned = array();

    foreach ($tax_query as $key => $clause) {
        if ($key === 'relation') {
            $cleaned[$key] = $clause;
            continue;
        }

        if (!is_array($clause)) {
            continue;
        }

        if (isset($clause['taxonomy']) && $clause['taxonomy'] === 'product_cat') {
            continue;
        }

        if (!isset($clause['taxonomy'])) {
            $nested = kangoo_remove_product_cat_tax_query_clauses($clause);
            $has_nested_clause = false;

            foreach ($nested as $nested_key => $nested_clause) {
                if ($nested_key !== 'relation') {
                    $has_nested_clause = true;
                    break;
                }
            }

            if ($has_nested_clause) {
                $cleaned[] = $nested;
            }

            continue;
        }

        $cleaned[] = $clause;
    }

    return $cleaned;
}

function kangoo_is_product_brand_category_slug($slug) {
    $slug = sanitize_title((string) $slug);

    if ($slug === '' || !taxonomy_exists('product_cat') || !get_term_by('slug', $slug, 'product_cat')) {
        return false;
    }

    $brand_options = function_exists('kangoo_product_filter_options')
        ? kangoo_product_filter_options('brand')
        : kangoo_product_filter_fallback_options('brand');

    return isset($brand_options[$slug]);
}

function kangoo_nicotine_brand_filter_redirect() {
    if (is_admin() || !function_exists('is_product_category') || !is_product_category()) {
        return;
    }

    if (empty($_GET['filter_brand']) || !taxonomy_exists('product_cat')) {
        return;
    }

    $brand_slug = sanitize_title(wp_unslash($_GET['filter_brand']));
    $brand_term = get_term_by('slug', $brand_slug, 'product_cat');

    if (!$brand_term instanceof WP_Term) {
        return;
    }

    $current_term = get_queried_object();

    if (!$current_term instanceof WP_Term || $current_term->taxonomy !== 'product_cat') {
        return;
    }

    if ($current_term->slug !== 'nicotine-pouches' && !kangoo_is_product_brand_category_slug($current_term->slug)) {
        return;
    }

    $target_url = get_term_link($brand_term);

    if (is_wp_error($target_url)) {
        return;
    }

    $query_args = array();

    foreach (array('filter_flavour', 'filter_strength', 'orderby', 'min_price', 'max_price', 's') as $key) {
        if (!isset($_GET[$key])) {
            continue;
        }

        $value = trim((string) wp_unslash($_GET[$key]));

        if ($value === '') {
            continue;
        }

        $query_args[$key] = in_array($key, array('filter_flavour', 'filter_strength'), true)
            ? sanitize_title($value)
            : sanitize_text_field($value);
    }

    wp_safe_redirect(add_query_arg($query_args, $target_url), 302);
    exit;
}
add_action('template_redirect', 'kangoo_nicotine_brand_filter_redirect', 5);

function kangoo_add_attribute_landing_rewrites() {
    add_rewrite_rule(
        '^([a-z0-9-]+)-strength-nicotine-pouches/?$',
        'index.php?pa_strength=$matches[1]',
        'top'
    );

    add_rewrite_rule(
        '^([a-z0-9-]+)-nicotine-pouches/?$',
        'index.php?pa_flavour=$matches[1]',
        'top'
    );
}
add_action('init', 'kangoo_add_attribute_landing_rewrites', 30);

function kangoo_attribute_landing_term_link($termlink, $term, $taxonomy) {
    if (!$term instanceof WP_Term) {
        return $termlink;
    }

    if ($taxonomy === 'pa_strength') {
        return home_url('/' . $term->slug . '-strength-nicotine-pouches/');
    }

    if ($taxonomy === 'pa_flavour') {
        return home_url('/' . $term->slug . '-nicotine-pouches/');
    }

    return $termlink;
}
add_filter('term_link', 'kangoo_attribute_landing_term_link', 10, 3);

add_action('wp_footer', function () {
    get_template_part('template-parts/global/account-drawer');
}, 30);

function kangoo_account_page_assets() {
    if (!function_exists('is_account_page') || !is_account_page()) {
        return;
    }

    $theme_version = wp_get_theme()->get('Version');
    $css_uri = get_template_directory_uri() . '/assets/css/';
    $js_uri  = get_template_directory_uri() . '/assets/js/';

    wp_enqueue_style(
        'kangoo-account-page',
        $css_uri . 'account-page.css',
        array('kangoo-woocommerce'),
        $theme_version
    );

    wp_enqueue_script(
        'kangoo-account-page',
        $js_uri . 'account-page.js',
        array(),
        $theme_version,
        true
    );

    wp_localize_script('kangoo-account-page', 'kangooAccountPage', array(
        'menu_label' => __('Account menu', 'kangoo'),
        'close_label' => __('Close menu', 'kangoo'),
    ));
}
add_action('wp_enqueue_scripts', 'kangoo_account_page_assets', 30);

function kangoo_account_page_body_class($classes) {
    if (function_exists('is_account_page') && is_account_page()) {
        $classes[] = 'is-account-page';

        $endpoint = 'dashboard';
        $account_endpoints = array('orders', 'edit-address', 'payment-methods', 'edit-account', 'rewards', kangoo_referrals_endpoint_slug());

        foreach ($account_endpoints as $account_endpoint) {
            if (function_exists('is_wc_endpoint_url') && is_wc_endpoint_url($account_endpoint)) {
                $endpoint = $account_endpoint;
                break;
            }
        }

        $classes[] = 'is-account-endpoint-' . sanitize_html_class($endpoint);
    }

    return $classes;
}
add_filter('body_class', 'kangoo_account_page_body_class');

function kangoo_account_menu_items($items) {
    unset($items['downloads']);

    if (isset($items['dashboard'])) {
        $items['dashboard'] = __('Dashboard', 'kangoo');
    }

    if (isset($items['orders'])) {
        $items['orders'] = __('Orders', 'kangoo');
    }

    if (isset($items['edit-address'])) {
        $items['edit-address'] = __('Addresses', 'kangoo');
    }

    if (isset($items['edit-account'])) {
        $items['edit-account'] = __('Account details', 'kangoo');
    }

    if (isset($items['customer-logout'])) {
        $items['customer-logout'] = __('Log out', 'kangoo');
    }

    return $items;
}
add_filter('woocommerce_account_menu_items', 'kangoo_account_menu_items');

add_filter('woocommerce_get_endpoint_url', function ($url, $endpoint, $value, $permalink) {
    if ($endpoint === 'customer-logout') {
        return wp_logout_url(wc_get_page_permalink('myaccount'));
    }

    return $url;
}, 10, 4);

function kangoo_filter_product_category_query($query) {
    if (is_admin() || !$query->is_main_query()) {
        return;
    }

    if (!is_product_category() && !is_shop() && !is_product_taxonomy()) {
        return;
    }

    $query->set('posts_per_page', 10);

    $meta_query = (array) $query->get('meta_query');

    $brand_filter_handled = false;
    $should_rebuild_tax_query = false;

    if (!empty($_GET['filter_brand'])) {
        $brand_filter = sanitize_title(wp_unslash($_GET['filter_brand']));
        $archive_product_cat = sanitize_title((string) $query->get('product_cat'));
        $queried_object = get_queried_object();

        if (
            $archive_product_cat === ''
            && $queried_object instanceof WP_Term
            && $queried_object->taxonomy === 'product_cat'
        ) {
            $archive_product_cat = $queried_object->slug;
        }

        $brand_category = taxonomy_exists('product_cat') ? get_term_by('slug', $brand_filter, 'product_cat') : false;

        if ($archive_product_cat === $brand_filter) {
            $brand_filter_handled = true;
        } elseif ($archive_product_cat === 'nicotine-pouches' && $brand_category instanceof WP_Term) {
            $tax_query = kangoo_remove_product_cat_tax_query_clauses((array) $query->get('tax_query'));
            $tax_query[] = array(
                'taxonomy'         => 'product_cat',
                'field'            => 'term_id',
                'terms'            => array((int) $brand_category->term_id),
                'include_children' => true,
            );

            $query->set('tax_query', $tax_query);
            $query->set('product_cat', '');
            $query->set('taxonomy', '');
            $query->set('term', '');
            $brand_filter_handled = true;
            $should_rebuild_tax_query = true;
        }

        if (!$brand_filter_handled) {
            kangoo_add_product_filter_query($query, 'brand', $brand_filter);
            $should_rebuild_tax_query = true;
        }
    }

    if (!empty($_GET['filter_flavour'])) {
        kangoo_add_product_filter_query($query, 'flavour', wp_unslash($_GET['filter_flavour']));
        $should_rebuild_tax_query = true;
    }

    if (!empty($_GET['filter_strength'])) {
        kangoo_add_product_filter_query($query, 'strength', wp_unslash($_GET['filter_strength']));
        $should_rebuild_tax_query = true;
    }

    if ($should_rebuild_tax_query && class_exists('WP_Tax_Query')) {
        $query->tax_query = new WP_Tax_Query((array) $query->get('tax_query'));
    }

    if (!empty($_GET['orderby'])) {
        $orderby = sanitize_text_field(wp_unslash($_GET['orderby']));

        if ($orderby === 'price') {
            $query->set('meta_key', '_price');
            $query->set('orderby', 'meta_value_num');
            $query->set('order', 'ASC');
        }

        if ($orderby === 'price-desc') {
            $query->set('meta_key', '_price');
            $query->set('orderby', 'meta_value_num');
            $query->set('order', 'DESC');
        }

        if ($orderby === 'title') {
            $query->set('orderby', 'title');
            $query->set('order', 'ASC');
        }

        if ($orderby === 'date') {
            $query->set('orderby', 'date');
            $query->set('order', 'DESC');
        }
    }
}
add_action('pre_get_posts', 'kangoo_filter_product_category_query');

function kangoo_product_archive_stock_order_clauses($clauses, $query) {
    if (is_admin() || !$query->is_main_query()) {
        return $clauses;
    }

    $post_type = $query->get('post_type');
    $is_product_query = $post_type === 'product' || (is_array($post_type) && in_array('product', $post_type, true));

    if (!is_product_category() && !is_shop() && !is_product_taxonomy() && !$is_product_query && $query->get('wc_query') !== 'product_query') {
        return $clauses;
    }

    global $wpdb;

    $stock_alias = 'kangoo_product_stock_lookup';
    $lookup_table = $wpdb->prefix . 'wc_product_meta_lookup';

    if (strpos($clauses['join'], $stock_alias) === false) {
        $clauses['join'] .= " LEFT JOIN {$lookup_table} AS {$stock_alias} ON ({$wpdb->posts}.ID = {$stock_alias}.product_id)";
    }

    $stock_order = "CASE WHEN {$stock_alias}.stock_status = 'outofstock' THEN 1 ELSE 0 END ASC";
    $clauses['orderby'] = $clauses['orderby'] ? $stock_order . ', ' . $clauses['orderby'] : $stock_order;

    return $clauses;
}
add_filter('posts_clauses', 'kangoo_product_archive_stock_order_clauses', 999, 2);
