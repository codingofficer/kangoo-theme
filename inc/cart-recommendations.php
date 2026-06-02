<?php
defined('ABSPATH') || exit;

function kangoo_cart_recommendation_product_ids($limit = 4) {
    if (!function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
        return array();
    }

    $limit = max(1, (int) apply_filters('kangoo_cart_recommendations_limit', $limit));
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
            'posts_per_page' => 30,
            'post__not_in'   => array_merge($cart_product_ids, $product_ids),
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

function kangoo_cart_recommendation_strength_label($product) {
    if (!$product instanceof WC_Product) {
        return '';
    }

    $strength = function_exists('get_field') ? get_field('strength_mg', $product->get_id()) : '';

    if (!$strength && $product->get_parent_id()) {
        $strength = function_exists('get_field') ? get_field('strength_mg', $product->get_parent_id()) : '';
    }

    if (!$strength) {
        foreach (array('pa_strength', 'pa_strengths') as $attribute_name) {
            $terms = wc_get_product_terms($product->get_id(), $attribute_name, array('fields' => 'names'));

            if (!empty($terms)) {
                $strength = reset($terms);
                break;
            }
        }
    }

    if (!$strength) {
        return '';
    }

    $strength = strtoupper(trim((string) $strength));

    if (is_numeric($strength) || preg_match('/^\d+(\.\d+)?$/', $strength)) {
        $strength .= 'MG';
    } elseif (strpos($strength, 'MG') === false && preg_match('/\d/', $strength)) {
        $strength .= 'MG';
    }

    return $strength;
}

function kangoo_cart_recommendation_short_copy($product) {
    if (!$product instanceof WC_Product) {
        return '';
    }

    $copy = $product->get_short_description();

    if (!$copy) {
        $copy = $product->get_description();
    }

    $copy = trim(wp_strip_all_tags($copy));

    if (!$copy) {
        return __('Popular mint choice', 'kangoo');
    }

    return wp_trim_words($copy, 8, '...');
}

function kangoo_cart_recommendation_add_button($product) {
    if (!$product instanceof WC_Product) {
        return '';
    }

    if (!$product->is_purchasable() || !$product->is_in_stock()) {
        return sprintf(
            '<a class="kangoo-cart-rec-card__add kangoo-cart-rec-card__add--outline" href="%s">%s</a>',
            esc_url($product->get_permalink()),
            esc_html__('View', 'kangoo')
        );
    }

    if (!$product->supports('ajax_add_to_cart') || !$product->is_type('simple')) {
        return sprintf(
            '<a class="kangoo-cart-rec-card__add kangoo-cart-rec-card__add--outline" href="%s">%s</a>',
            esc_url($product->get_permalink()),
            esc_html__('View', 'kangoo')
        );
    }

    return sprintf(
        '<a href="%1$s" data-quantity="1" class="button product_type_simple add_to_cart_button ajax_add_to_cart kangoo-cart-rec-card__add" data-product_id="%2$d" data-product_sku="%3$s" aria-label="%4$s" rel="nofollow">%5$s</a>',
        esc_url($product->add_to_cart_url()),
        absint($product->get_id()),
        esc_attr($product->get_sku()),
        esc_attr(sprintf(__('Add %s to your cart', 'kangoo'), $product->get_name())),
        esc_html__('+ Add', 'kangoo')
    );
}

function kangoo_render_cart_recommendation_card($product_id) {
    $product = wc_get_product($product_id);

    if (!$product instanceof WC_Product) {
        return '';
    }

    $strength = kangoo_cart_recommendation_strength_label($product);
    $image = $product->get_image('woocommerce_thumbnail', array(
        'loading' => 'lazy',
        'alt'     => $product->get_name(),
    ));

    ob_start();
    ?>
    <article class="kangoo-cart-rec-card">
        <a class="kangoo-cart-rec-card__image" href="<?php echo esc_url($product->get_permalink()); ?>" aria-label="<?php echo esc_attr($product->get_name()); ?>">
            <?php echo wp_kses_post($image); ?>
        </a>
        <div class="kangoo-cart-rec-card__body">
            <h3><a href="<?php echo esc_url($product->get_permalink()); ?>"><?php echo esc_html($product->get_name()); ?></a></h3>
            <p><?php echo esc_html(kangoo_cart_recommendation_short_copy($product)); ?></p>
        </div>
        <div class="kangoo-cart-rec-card__side">
            <?php if ($strength) : ?>
                <span class="kangoo-cart-rec-card__badge"><?php echo esc_html($strength); ?></span>
            <?php endif; ?>
            <strong class="kangoo-cart-rec-card__price"><?php echo wp_kses_post($product->get_price_html()); ?></strong>
            <?php echo kangoo_cart_recommendation_add_button($product); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
    </article>
    <?php

    return ob_get_clean();
}

function kangoo_render_cart_recommendations() {
    static $rendered = false;

    if ($rendered || is_admin() || !function_exists('is_cart') || !is_cart()) {
        return '';
    }

    $product_ids = kangoo_cart_recommendation_product_ids(3);

    if (empty($product_ids)) {
        return '';
    }

    $rendered = true;

    ob_start();
    ?>
    <section class="kangoo-cart-recommendations" aria-labelledby="kangoo-cart-recommendations-title">
        <header class="kangoo-cart-recommendations__header">
            <span class="kangoo-cart-recommendations__icon" aria-hidden="true">&#127942;</span>
            <span class="kangoo-cart-recommendations__label kangoo-cart-recommendations__label--desktop"><?php esc_html_e('Customers also add these to save on delivery', 'kangoo'); ?></span>
            <span class="kangoo-cart-recommendations__label kangoo-cart-recommendations__label--mobile"><?php esc_html_e('Frequently bought together', 'kangoo'); ?></span>
            <h2 id="kangoo-cart-recommendations-title"><?php esc_html_e('Frequently bought together', 'kangoo'); ?></h2>
        </header>

        <div class="kangoo-cart-recommendations__grid">
            <?php foreach ($product_ids as $product_id) : ?>
                <?php echo kangoo_render_cart_recommendation_card($product_id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php endforeach; ?>
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
