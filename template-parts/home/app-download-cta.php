<?php
defined('ABSPATH') || exit;

$app_page_url = home_url('/app/');
$app_page = get_posts(array(
    'post_type'      => 'page',
    'post_status'    => 'publish',
    'posts_per_page' => 1,
    'fields'         => 'ids',
    'meta_key'       => '_wp_page_template',
    'meta_value'     => 'page-templates/template-kangoo-app.php',
));

if (!empty($app_page)) {
    $app_page_url = get_permalink($app_page[0]);
}

$app_release = function_exists('kangoo_get_app_apk_release') ? kangoo_get_app_apk_release() : array();
$apk_url = !empty($app_release['apk_url']) ? $app_release['apk_url'] : home_url('/app/android/kangoo-pouches-v0.6.0.apk');
?>

<section class="home-app-cta" aria-label="<?php esc_attr_e('Download the Kangoo Android app', 'kangoo'); ?>">
    <div class="container">
        <div class="home-app-cta__inner">
            <div class="home-app-cta__copy">
                <span><?php esc_html_e('Android app is live', 'kangoo'); ?></span>
                <strong><?php esc_html_e('Shop Kangoo faster in the app.', 'kangoo'); ?></strong>
                <p><?php esc_html_e('Search pouches, add to cart, view rewards, and continue to secure checkout from your phone.', 'kangoo'); ?></p>
            </div>
            <div class="home-app-cta__actions">
                <a class="btn btn--primary home-app-cta__button" href="<?php echo esc_url($apk_url); ?>" download><?php esc_html_e('Download APP', 'kangoo'); ?></a>
                <a class="btn btn--secondary home-app-cta__button" href="<?php echo esc_url($app_page_url); ?>"><?php esc_html_e('App details', 'kangoo'); ?></a>
            </div>
        </div>
    </div>
</section>
