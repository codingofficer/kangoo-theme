<?php
defined('ABSPATH') || exit;

$trial_count = function_exists('kangoo_get_trial_products') ? count(kangoo_get_trial_products(60)) : 0;
$pack_count = function_exists('kangoo_get_pack_priced_products') ? count(kangoo_get_pack_priced_products(60)) : 0;
$nicotine_url = function_exists('kangoo_get_term_url_by_slug') ? kangoo_get_term_url_by_slug('product_cat', 'nicotine-pouches', '/product-category/nicotine-pouches/') : home_url('/product-category/nicotine-pouches/');
$trial_url = function_exists('kangoo_get_term_url_by_slug') ? kangoo_get_term_url_by_slug('product_cat', '99p-pouches', '/product-category/99p-pouches/') : home_url('/product-category/99p-pouches/');
$compare_url = function_exists('kangoo_get_page_url_by_template') ? kangoo_get_page_url_by_template('page-templates/template-pouch-comparison.php', '/compare-pouches/') : home_url('/compare-pouches/');
$free_shipping_price = function_exists('kangoo_plain_wc_price') && function_exists('kangoo_free_shipping_threshold') ? kangoo_plain_wc_price(kangoo_free_shipping_threshold()) : html_entity_decode('&pound;', ENT_QUOTES, 'UTF-8') . '14.95';

$proof_items = array(
    array(
        'label' => __('99p trials', 'kangoo'),
        'value' => $trial_count ? sprintf(_n('Try %d selected pouch for 99p', 'Try %d selected pouches for 99p', $trial_count, 'kangoo'), $trial_count) : __('Try selected pouches for 99p', 'kangoo'),
        'url'   => $trial_url,
    ),
    array(
        'label' => __('Multi-buy packs', 'kangoo'),
        'value' => $pack_count ? sprintf(_n('Multi-buy savings on %d product', 'Multi-buy savings on %d+ products', $pack_count, 'kangoo'), $pack_count) : __('3, 5 and 10-pack savings', 'kangoo'),
        'url'   => $nicotine_url,
    ),
    array(
        'label' => __('Delivery', 'kangoo'),
        'value' => sprintf(__('Free discreet UK delivery over %s', 'kangoo'), $free_shipping_price),
        'url'   => $nicotine_url,
    ),
    array(
        'label' => __('Top brands', 'kangoo'),
        'value' => __('ZYN, VELO, KILLA, PABLO', 'kangoo'),
        'url'   => $compare_url,
    ),
);
?>

<section class="home-seo-proof" aria-label="<?php esc_attr_e('Kangoo nicotine pouch shopping highlights', 'kangoo'); ?>">
    <div class="container">
        <div class="home-seo-proof__grid">
            <?php foreach ($proof_items as $item) : ?>
                <a href="<?php echo esc_url($item['url']); ?>">
                    <span><?php echo esc_html($item['label']); ?></span>
                    <strong><?php echo esc_html($item['value']); ?></strong>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
