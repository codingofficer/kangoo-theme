<?php
if (!defined('ABSPATH')) {
    exit;
}

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

    register_nav_menus(array(
        'primary' => __('Primary Menu', 'kangoo'),
        'footer'  => __('Footer Menu', 'kangoo'),
    ));
}
add_action('after_setup_theme', 'kangoo_theme_setup');

function kangoo_enqueue_assets() {
    $theme_version = wp_get_theme()->get('Version');
    $css_uri = get_template_directory_uri() . '/assets/css/';
    $js_uri  = get_template_directory_uri() . '/assets/js/';

    wp_enqueue_style('kangoo-base', $css_uri . 'base.css', array(), $theme_version);
    wp_enqueue_style('kangoo-components', $css_uri . 'components.css', array('kangoo-base'), $theme_version);
    wp_enqueue_style('kangoo-header-footer', $css_uri . 'header-footer.css', array('kangoo-components'), $theme_version);
    wp_enqueue_style('kangoo-home', $css_uri . 'home.css', array('kangoo-header-footer'), $theme_version);
    wp_enqueue_style('kangoo-shop', $css_uri . 'shop.css', array('kangoo-home'), $theme_version);
    wp_enqueue_style('kangoo-product', $css_uri . 'product.css', array('kangoo-shop'), $theme_version);
    wp_enqueue_style('kangoo-woocommerce', $css_uri . 'woocommerce.css', array('kangoo-product'), $theme_version);

    wp_enqueue_script(
        'kangoo-main',
        $js_uri . 'main.js',
        array(),
        $theme_version,
        true
    );

    wp_enqueue_script(
        'kangoo-ajax-cart',
        $js_uri . 'ajax-cart.js',
        array('jquery'),
        $theme_version,
        true
    );

    if (function_exists('is_product') && is_product()) {
        wp_enqueue_script('wc-add-to-cart-variation');
        wp_enqueue_script('wc-cart-fragments');
    }
}
add_action('wp_enqueue_scripts', 'kangoo_enqueue_assets');

function kangoo_body_classes($classes) {
    if (is_front_page()) {
        $classes[] = 'is-front-page';
    }

    if (function_exists('is_woocommerce') && is_woocommerce()) {
        $classes[] = 'is-woocommerce';
    }

    return $classes;
}
add_filter('body_class', 'kangoo_body_classes');

add_theme_support('custom-logo', array(
    'height'      => 60,
    'width'       => 200,
    'flex-height' => true,
    'flex-width'  => true,
));

add_filter('woocommerce_add_to_cart_fragments', function ($fragments) {
    ob_start();
    ?>
    <span class="cart-badge">
        <?php echo WC()->cart->get_cart_contents_count(); ?>
    </span>
    <?php
    $fragments['.cart-badge'] = ob_get_clean();

    return $fragments;
});