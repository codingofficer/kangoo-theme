<?php
/**
 * Template Name: Pouch Comparison
 */

defined('ABSPATH') || exit;

get_header();

$comparison_products = array();

if (function_exists('wc_get_products')) {
    $products = wc_get_products(array(
        'status'  => 'publish',
        'limit'   => 80,
        'orderby' => 'popularity',
        'return'  => 'objects',
    ));

    foreach ($products as $product) {
        if (!$product instanceof WC_Product || !$product->is_visible()) {
            continue;
        }

        $product_id = $product->get_id();
        $strength_mg = function_exists('get_field') ? get_field('strength_mg', $product_id) : '';
        $strength_label = $strength_mg ? strtoupper((string) $strength_mg) : $product->get_attribute('pa_strength');

        if ($strength_label && stripos($strength_label, 'mg') === false && preg_match('/\d/', $strength_label)) {
            $strength_label .= 'MG';
        }

        $pouch_count = 0;
        $pouch_count_fields = array('pouch_count', 'pouches_per_can', 'number_of_pouches');

        foreach ($pouch_count_fields as $field_name) {
            $field_value = function_exists('get_field') ? get_field($field_name, $product_id) : '';

            if ($field_value) {
                $pouch_count = (int) preg_replace('/[^0-9]/', '', (string) $field_value);
                break;
            }
        }

        if ($pouch_count <= 0) {
            $product_name_for_count = strtolower((string) get_the_title($product_id));
            $pouch_count = strpos($product_name_for_count, 'mini') !== false ? 10 : 20;
        }

        $display_price = function_exists('wc_get_price_to_display') ? (float) wc_get_price_to_display($product) : (float) $product->get_price();
        $price_per_pouch = $pouch_count > 0 && $display_price > 0 ? $display_price / $pouch_count : 0;
        $categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'names'));

        $comparison_products[] = array(
            'id'             => $product_id,
            'name'           => get_the_title($product_id),
            'url'            => get_permalink($product_id),
            'image'          => get_the_post_thumbnail_url($product_id, 'woocommerce_thumbnail'),
            'priceHtml'      => function_exists('kangoo_get_product_price_html') ? kangoo_get_product_price_html($product) : $product->get_price_html(),
            'price'          => $display_price,
            'pricePerPouch'  => $price_per_pouch,
            'currency'       => function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '£',
            'brand'          => $product->get_attribute('pa_brand'),
            'flavour'        => $product->get_attribute('pa_flavour'),
            'strength'       => $strength_label,
            'mg'             => $strength_mg ? (float) preg_replace('/[^0-9.]/', '', (string) $strength_mg) : 0,
            'pouchCount'     => $pouch_count,
            'categories'     => !is_wp_error($categories) ? $categories : array(),
            'stock'          => $product->is_in_stock(),
            'rating'         => (float) $product->get_average_rating(),
            'reviewCount'    => (int) $product->get_review_count(),
        );
    }
}

$comparison_nicotine_url = function_exists('kangoo_get_term_url_by_slug') ? kangoo_get_term_url_by_slug('product_cat', 'nicotine-pouches', '/product-category/nicotine-pouches/') : home_url('/product-category/nicotine-pouches/');
$comparison_trial_url = function_exists('kangoo_get_term_url_by_slug') ? kangoo_get_term_url_by_slug('product_cat', '99p-pouches', '/product-category/99p-pouches/') : home_url('/product-category/99p-pouches/');
$comparison_finder_url = function_exists('kangoo_get_page_url_by_template') ? kangoo_get_page_url_by_template('page-templates/template-pouch-finder.php', '/pouch-finder/') : home_url('/pouch-finder/');
$comparison_strength_url = function_exists('kangoo_get_page_url_by_template') ? kangoo_get_page_url_by_template('page-templates/template-strength-ladder.php', '/strength-ladder/') : home_url('/strength-ladder/');

$comparison_seo_links = array(
    array('label' => __('Shop nicotine pouches', 'kangoo'), 'url' => $comparison_nicotine_url),
    array('label' => __('99p pouch trials', 'kangoo'), 'url' => $comparison_trial_url),
    array('label' => __('Pouch finder', 'kangoo'), 'url' => $comparison_finder_url),
    array('label' => __('Strength ladder', 'kangoo'), 'url' => $comparison_strength_url),
    array('label' => __('ZYN pouches', 'kangoo'), 'url' => function_exists('kangoo_get_term_url_by_slug') ? kangoo_get_term_url_by_slug('product_cat', 'zyn', '/product-category/zyn/') : home_url('/product-category/zyn/')),
    array('label' => __('VELO pouches', 'kangoo'), 'url' => function_exists('kangoo_get_term_url_by_slug') ? kangoo_get_term_url_by_slug('product_cat', 'velo', '/product-category/velo/') : home_url('/product-category/velo/')),
);
?>

<main id="site-main" class="pouch-compare">
    <section class="pouch-compare__hero section">
        <div class="container">
            <div class="pouch-compare__hero-inner">
                <span class="eyebrow"><?php esc_html_e('Kangoo Compare', 'kangoo'); ?></span>
                <h1><?php esc_html_e('Compare pouches side by side', 'kangoo'); ?></h1>
                <p><?php esc_html_e('Choose up to four products and quickly compare strength, flavour, price per pouch, stock, and the kind of user each one suits.', 'kangoo'); ?></p>
            </div>
        </div>
    </section>

    <section class="kangoo-seo-strip section" aria-label="<?php esc_attr_e('Pouch comparison buying links', 'kangoo'); ?>">
        <div class="container">
            <div class="kangoo-seo-strip__inner">
                <div>
                    <span class="eyebrow"><?php esc_html_e('Compare before buying', 'kangoo'); ?></span>
                    <h2><?php esc_html_e('Compare ZYN, VELO, PABLO and KILLA pouches', 'kangoo'); ?></h2>
                    <p><?php esc_html_e('Use side-by-side product data to compare strength, flavour, price per pouch, stock and review signals before moving into brand or category pages.', 'kangoo'); ?></p>
                </div>
                <div class="kangoo-seo-strip__links">
                    <?php foreach ($comparison_seo_links as $comparison_seo_link) : ?>
                        <a href="<?php echo esc_url($comparison_seo_link['url']); ?>"><?php echo esc_html($comparison_seo_link['label']); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="pouch-compare__tool section">
        <div class="container">
            <div class="pouch-compare__panel" data-pouch-compare>
                <div class="pouch-compare__controls">
                    <label class="pouch-compare__search">
                        <span><?php esc_html_e('Search products', 'kangoo'); ?></span>
                        <input type="search" placeholder="<?php esc_attr_e('Search by brand, flavour, strength...', 'kangoo'); ?>" data-compare-search>
                    </label>

                    <div class="pouch-compare__selected" data-compare-selected aria-live="polite"></div>
                </div>

                <div class="pouch-compare__picker" data-compare-picker></div>
            </div>

            <section class="pouch-compare__results" data-compare-results hidden>
                <div class="pouch-compare__results-head">
                    <div>
                        <span class="eyebrow"><?php esc_html_e('Comparison', 'kangoo'); ?></span>
                        <h2><?php esc_html_e('Your selected pouches', 'kangoo'); ?></h2>
                    </div>
                    <button type="button" class="btn btn--secondary" data-compare-clear><?php esc_html_e('Clear all', 'kangoo'); ?></button>
                </div>

                <div class="pouch-compare__table-wrap">
                    <table class="pouch-compare-table" data-compare-table></table>
                </div>
            </section>

            <script type="application/json" data-compare-products>
                <?php echo wp_json_encode($comparison_products, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
            </script>
        </div>
    </section>
</main>

<?php get_footer(); ?>
