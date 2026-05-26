<?php
/**
 * Template Name: Pick n Mix Bundle
 */

defined('ABSPATH') || exit;

get_header();

$box_products = array();

if (function_exists('wc_get_products')) {
    $products = wc_get_products(array(
        'status'  => 'publish',
        'limit'   => 80,
        'orderby' => 'popularity',
        'return'  => 'objects',
        'type'    => array('simple'),
        'tax_query' => array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => array('99p', '99p-pouches', '99p-collection'),
                'operator' => 'NOT IN',
            ),
            array(
                'taxonomy' => 'product_tag',
                'field'    => 'slug',
                'terms'    => array('99p', '99p-pouches', '99p-collection'),
                'operator' => 'NOT IN',
            ),
        ),
    ));

    foreach ($products as $product) {
        if (!$product instanceof WC_Product || !$product->is_visible() || !$product->is_purchasable()) {
            continue;
        }

        $product_id = $product->get_id();

        if (function_exists('kangoo_is_99p_product') && kangoo_is_99p_product($product_id)) {
            continue;
        }

        $price = function_exists('wc_get_price_to_display') ? (float) wc_get_price_to_display($product) : (float) $product->get_price();

        if ($price > 0 && $price <= 1.01) {
            continue;
        }

        $strength_mg = function_exists('get_field') ? get_field('strength_mg', $product_id) : '';
        $strength_label = $strength_mg ? strtoupper((string) $strength_mg) : $product->get_attribute('pa_strength');

        if ($strength_label && stripos($strength_label, 'mg') === false && preg_match('/\d/', $strength_label)) {
            $strength_label .= 'MG';
        }

        $product_name = get_the_title($product_id);
        $pouch_count = stripos($product_name, 'mini') !== false ? 10 : 20;
        $regular_price = (float) $product->get_regular_price();
        $stock_limit = function_exists('kangoo_get_product_stock_limit') ? kangoo_get_product_stock_limit($product) : null;

        $box_products[] = array(
            'id'            => $product_id,
            'name'          => $product_name,
            'url'           => get_permalink($product_id),
            'image'         => get_the_post_thumbnail_url($product_id, 'woocommerce_thumbnail'),
            'price'         => $price,
            'regularPrice'  => $regular_price > 0 ? $regular_price : $price,
            'priceHtml'     => function_exists('kangoo_get_product_price_html') ? kangoo_get_product_price_html($product) : $product->get_price_html(),
            'currency'      => function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '£',
            'brand'         => $product->get_attribute('pa_brand'),
            'flavour'       => $product->get_attribute('pa_flavour'),
            'strength'      => $strength_label,
            'pouchCount'    => $pouch_count,
            'stock'         => $product->is_in_stock(),
            'stockQuantity' => $stock_limit,
        );
    }
}
?>

<main id="site-main" class="build-box">
    <section class="build-box__hero section">
        <div class="container">
            <div class="build-box__hero-grid">
                <div>
                    <span class="eyebrow"><?php esc_html_e('Pick n Mix Bundle', 'kangoo'); ?></span>
                    <h1><?php esc_html_e('Build your Pick n Mix Bundle', 'kangoo'); ?></h1>
                    <p><?php esc_html_e('Choose a 5, 10, or 20 can bundle, mix flavours and strengths, then add everything to your cart in one go.', 'kangoo'); ?></p>
                </div>

                <div class="build-box__hero-points" aria-label="<?php esc_attr_e('Pick n Mix Bundle benefits', 'kangoo'); ?>">
                    <span><?php esc_html_e('Mix brands', 'kangoo'); ?></span>
                    <span><?php esc_html_e('Balance strengths', 'kangoo'); ?></span>
                    <span><?php esc_html_e('One cart action', 'kangoo'); ?></span>
                </div>
            </div>
        </div>
    </section>

    <section class="build-box__tool section">
        <div class="container">
            <div class="build-box__layout" data-build-box>
                <div class="build-box__main">
                    <div class="build-box__controls">
                        <div class="build-box__sizes" role="group" aria-label="<?php esc_attr_e('Choose box size', 'kangoo'); ?>">
                            <button type="button" class="is-active" data-box-size="5"><?php esc_html_e('5 cans', 'kangoo'); ?></button>
                            <button type="button" data-box-size="10"><?php esc_html_e('10 cans', 'kangoo'); ?></button>
                            <button type="button" data-box-size="20"><?php esc_html_e('20 cans', 'kangoo'); ?></button>
                        </div>

                        <label class="build-box__search">
                            <span><?php esc_html_e('Search pouches', 'kangoo'); ?></span>
                            <input type="search" placeholder="<?php esc_attr_e('Search mint, berry, 6mg, ZYN...', 'kangoo'); ?>" data-box-search>
                        </label>

                        <div class="build-box__filters" aria-label="<?php esc_attr_e('Filter bundle products', 'kangoo'); ?>">
                            <select data-box-brand>
                                <option value=""><?php esc_html_e('All brands', 'kangoo'); ?></option>
                            </select>
                            <select data-box-flavour>
                                <option value=""><?php esc_html_e('All flavours', 'kangoo'); ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="build-box__products" data-box-products></div>
                    <div class="build-box__pager" data-box-pager></div>
                </div>

                <aside class="build-box__summary" data-box-summary>
                    <div class="build-box__summary-head">
                        <span class="eyebrow"><?php esc_html_e('Your bundle', 'kangoo'); ?></span>
                        <h2 data-box-summary-title><?php esc_html_e('0 of 5 cans', 'kangoo'); ?></h2>
                        <div class="build-box__meter"><span data-box-meter></span></div>
                    </div>

                    <div class="build-box__selected" data-box-selected>
                        <p><?php esc_html_e('Start adding pouches to build your bundle.', 'kangoo'); ?></p>
                    </div>

                    <dl class="build-box__totals">
                        <div>
                            <dt><?php esc_html_e('Estimated total', 'kangoo'); ?></dt>
                            <dd data-box-total>£0.00</dd>
                        </div>
                        <div>
                            <dt><?php esc_html_e('You save', 'kangoo'); ?></dt>
                            <dd data-box-saving>£0.00</dd>
                        </div>
                    </dl>

                    <button type="button" class="btn btn--primary build-box__submit" data-box-submit disabled>
                        <?php esc_html_e('Fill your bundle', 'kangoo'); ?>
                    </button>

                    <p class="build-box__note"><?php esc_html_e('Nicotine is addictive. Kangoo products are for adults only.', 'kangoo'); ?></p>
                </aside>
            </div>

            <script type="application/json" data-box-products-json>
                <?php echo wp_json_encode($box_products, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
            </script>
        </div>
    </section>
</main>

<?php get_footer(); ?>
