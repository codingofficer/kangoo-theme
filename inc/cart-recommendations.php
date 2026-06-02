<?php
defined('ABSPATH') || exit;

function kangoo_cart_recommendation_product_ids($limit = 4) {
    if (!function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
        return array();
    }

    $limit = max(1, (int) $limit);
    $cart_product_ids = array();
    $candidate_ids = array();

    foreach (WC()->cart->get_cart() as $cart_item) {
        $cart_product = isset($cart_item['data']) && $cart_item['data'] instanceof WC_Product ? $cart_item['data'] : null;

        if (!$cart_product) {
            continue;
        }

        $base_id = $cart_product->get_parent_id() ? $cart_product->get_parent_id() : $cart_product->get_id();
        $cart_product_ids[] = $base_id;
        $candidate_ids = array_merge($candidate_ids, $cart_product->get_cross_sell_ids());
        $candidate_ids = array_merge($candidate_ids, wc_get_related_products($base_id, $limit));
    }

    $cart_product_ids = array_unique(array_filter(array_map('absint', $cart_product_ids)));
    $candidate_ids = array_unique(array_filter(array_map('absint', $candidate_ids)));
    $product_ids = array();

    foreach ($candidate_ids as $candidate_id) {
        if (in_array($candidate_id, $cart_product_ids, true)) {
            continue;
        }

        $candidate = wc_get_product($candidate_id);

        if (
            !$candidate
            || !$candidate->is_visible()
            || !$candidate->is_in_stock()
            || (function_exists('kangoo_is_99p_product') && kangoo_is_99p_product($candidate_id))
        ) {
            continue;
        }

        $product_ids[] = $candidate_id;

        if (count($product_ids) >= $limit) {
            break;
        }
    }

    if (count($product_ids) < $limit) {
        $fallback_query = new WP_Query(array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => 12,
            'post__not_in'   => array_merge($cart_product_ids, $product_ids),
            'meta_query'     => array(
                array(
                    'key'     => '_stock_status',
                    'value'   => 'instock',
                    'compare' => '=',
                ),
            ),
            'orderby'        => 'menu_order date',
            'order'          => 'DESC',
            'fields'         => 'ids',
        ));

        foreach ($fallback_query->posts as $fallback_id) {
            $fallback_id = absint($fallback_id);

            if (
                !$fallback_id
                || in_array($fallback_id, $cart_product_ids, true)
                || in_array($fallback_id, $product_ids, true)
                || (function_exists('kangoo_is_99p_product') && kangoo_is_99p_product($fallback_id))
            ) {
                continue;
            }

            $fallback_product = wc_get_product($fallback_id);

            if (!$fallback_product || !$fallback_product->is_visible() || !$fallback_product->is_in_stock()) {
                continue;
            }

            $product_ids[] = $fallback_id;

            if (count($product_ids) >= $limit) {
                break;
            }
        }
    }

    return array_slice($product_ids, 0, $limit);
}

function kangoo_render_cart_recommendations() {
    static $rendered = false;

    if ($rendered || is_admin() || !function_exists('is_cart') || !is_cart()) {
        return '';
    }

    $product_ids = kangoo_cart_recommendation_product_ids(4);

    if (empty($product_ids)) {
        return '';
    }

    $rendered = true;
    $original_post = $GLOBALS['post'] ?? null;
    $original_product = $GLOBALS['product'] ?? null;

    ob_start();
    ?>
    <section class="kangoo-cart-recommendations" aria-labelledby="kangoo-cart-recommendations-title">
        <header class="kangoo-cart-recommendations__header">
            <span class="eyebrow"><?php esc_html_e('Add-ons', 'kangoo'); ?></span>
            <h2 id="kangoo-cart-recommendations-title"><?php esc_html_e('Frequently bought together', 'kangoo'); ?></h2>
        </header>

        <div class="kangoo-cart-recommendations__grid">
            <?php
            foreach ($product_ids as $product_id) {
                $GLOBALS['post'] = get_post($product_id);
                $GLOBALS['product'] = wc_get_product($product_id);

                if (!$GLOBALS['post'] || !$GLOBALS['product']) {
                    continue;
                }

                setup_postdata($GLOBALS['post']);
                wc_get_template_part('content', 'product');
            }

            wp_reset_postdata();
            $GLOBALS['post'] = $original_post;
            $GLOBALS['product'] = $original_product;
            ?>
        </div>
    </section>
    <?php

    return ob_get_clean();
}

function kangoo_cart_recommendations_after_classic_cart() {
    echo kangoo_render_cart_recommendations(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action('woocommerce_after_cart', 'kangoo_cart_recommendations_after_classic_cart', 20);

function kangoo_cart_recommendations_after_block_cart($block_content, $block) {
    if (!is_array($block) || ($block['blockName'] ?? '') !== 'woocommerce/cart') {
        return $block_content;
    }

    return $block_content . kangoo_render_cart_recommendations();
}
add_filter('render_block', 'kangoo_cart_recommendations_after_block_cart', 20, 2);

function kangoo_cart_recommendations_after_cart_content($content) {
    if (!is_admin() && function_exists('is_cart') && is_cart() && is_main_query() && in_the_loop()) {
        return $content . kangoo_render_cart_recommendations();
    }

    return $content;
}
add_filter('the_content', 'kangoo_cart_recommendations_after_cart_content', 30);
