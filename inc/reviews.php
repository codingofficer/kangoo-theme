<?php
defined('ABSPATH') || exit;

function kangoo_reviews_theme_summary($product_id) {
    if (!function_exists('kangoo_reviews_get_summary')) {
        return array('count' => 0, 'average' => 0);
    }

    return kangoo_reviews_get_summary($product_id);
}

function kangoo_reviews_theme_render_stars($rating) {
    if (function_exists('kangoo_reviews_stars_html')) {
        return kangoo_reviews_stars_html($rating);
    }

    $rating = max(1, min(5, (int) round((float) $rating)));
    $html = '<span class="kangoo-review-stars" aria-hidden="true">';

    for ($i = 1; $i <= 5; $i++) {
        $html .= '<span class="' . ($i <= $rating ? 'is-filled' : 'is-empty') . '">&#9733;</span>';
    }

    return $html . '</span>';
}

function kangoo_reviews_theme_render_summary_link($product_id) {
    $summary = kangoo_reviews_theme_summary($product_id);

    if (empty($summary['count'])) {
        return;
    }

    $count = absint($summary['count']);
    $average = number_format_i18n((float) $summary['average'], 1);
    ?>
    <a class="product-review-summary" href="#kangoo-customer-reviews">
        <?php echo wp_kses_post(kangoo_reviews_theme_render_stars((float) $summary['average'])); ?>
        <span><?php echo esc_html($average); ?></span>
        <span aria-hidden="true">|</span>
        <span>
            <?php
            echo esc_html(sprintf(
                _n('%s verified review', '%s verified reviews', $count, 'kangoo'),
                number_format_i18n($count)
            ));
            ?>
        </span>
    </a>
    <?php
}

function kangoo_reviews_theme_get_reviews($product_id, $limit = 8) {
    if (!function_exists('kangoo_reviews_get_product_reviews')) {
        return array();
    }

    return kangoo_reviews_get_product_reviews($product_id, $limit);
}

function kangoo_reviews_theme_render_cards($product_id, $limit = 8) {
    $reviews = kangoo_reviews_theme_get_reviews($product_id, $limit);

    if (empty($reviews)) {
        return;
    }
    ?>
    <div class="kangoo-review-list">
        <?php foreach ($reviews as $review) : ?>
            <article class="kangoo-review-card">
                <div class="kangoo-review-card__stars">
                    <?php echo wp_kses_post(kangoo_reviews_theme_render_stars($review['rating'])); ?>
                </div>
                <div class="kangoo-review-card__meta">
                    <strong><?php echo esc_html($review['reviewer_name'] ?: __('Kangoo customer', 'kangoo')); ?></strong>
                    <?php if (!empty($review['verified'])) : ?>
                        <span class="kangoo-review-verified">&#10003; <?php esc_html_e('Verified Purchase', 'kangoo'); ?></span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($review['pack_label']) || !empty($review['purchase_month'])) : ?>
                    <p class="kangoo-review-card__purchase">
                        <?php
                        $parts = array_filter(array($review['pack_label'] ?? '', $review['purchase_month'] ?? ''));
                        echo esc_html(implode(' - ', $parts));
                        ?>
                    </p>
                <?php endif; ?>
                <blockquote><?php echo esc_html($review['review_body']); ?></blockquote>
                <button class="kangoo-review-helpful" type="button" data-kangoo-review-helpful="<?php echo esc_attr($review['id']); ?>">
                    <span aria-hidden="true">&#128077;</span>
                    <span><?php echo esc_html(sprintf(__('Helpful (%d)', 'kangoo'), absint($review['helpful_count']))); ?></span>
                </button>
            </article>
        <?php endforeach; ?>
    </div>
    <?php
}

function kangoo_reviews_theme_render_accordion($product_id) {
    $summary = kangoo_reviews_theme_summary($product_id);

    if (empty($summary['count'])) {
        return;
    }
    ?>
    <details class="product-accordion__item product-review-accordion">
        <summary><?php esc_html_e('Reviews', 'kangoo'); ?></summary>
        <div class="product-accordion__content">
            <?php kangoo_reviews_theme_render_cards($product_id, 4); ?>
        </div>
    </details>
    <?php
}

function kangoo_reviews_theme_render_section($product_id) {
    $summary = kangoo_reviews_theme_summary($product_id);

    if (empty($summary['count'])) {
        return;
    }
    ?>
    <section class="kangoo-customer-reviews" id="kangoo-customer-reviews">
        <div class="section-header section-header--left">
            <span class="eyebrow"><?php esc_html_e('Customer reviews', 'kangoo'); ?></span>
            <h2><?php esc_html_e('What customers say', 'kangoo'); ?></h2>
            <p>
                <?php
                echo esc_html(sprintf(
                    _n('%1$s average from %2$s verified review.', '%1$s average from %2$s verified reviews.', absint($summary['count']), 'kangoo'),
                    number_format_i18n((float) $summary['average'], 1),
                    number_format_i18n(absint($summary['count']))
                ));
                ?>
            </p>
        </div>
        <?php kangoo_reviews_theme_render_cards($product_id, 8); ?>
    </section>
    <?php
}

function kangoo_reviews_theme_render_order_review_forms($order) {
    if (!$order instanceof WC_Order || !is_user_logged_in() || $order->get_status() !== 'completed') {
        return;
    }

    if (!function_exists('kangoo_reviews_customer_order_item_context')) {
        return;
    }

    $items = $order->get_items();

    if (empty($items)) {
        return;
    }
    ?>
    <section class="kangoo-order-review-submit" id="kangoo-order-reviews">
        <h2><?php esc_html_e('Review your pouches', 'kangoo'); ?></h2>
        <p><?php esc_html_e('Share a quick review for completed order items. Verified reviews are checked against your order email before approval.', 'kangoo'); ?></p>
        <div class="kangoo-order-review-submit__grid">
            <?php foreach ($items as $item_id => $item) : ?>
                <?php
                if (!$item instanceof WC_Order_Item_Product) {
                    continue;
                }

                $context = kangoo_reviews_customer_order_item_context(get_current_user_id(), $order->get_id(), $item_id);

                if (is_wp_error($context)) {
                    continue;
                }

                $existing_id = function_exists('kangoo_reviews_find_existing_order_item_review') ? kangoo_reviews_find_existing_order_item_review($order->get_id(), $item_id) : 0;
                $existing_status = $existing_id ? get_post_status($existing_id) : '';
                ?>
                <article class="kangoo-order-review-card">
                    <h3><?php echo esc_html($context['product_name']); ?></h3>
                    <p><?php echo esc_html($context['pack_label']); ?></p>
                    <?php if ($existing_id) : ?>
                        <span class="kangoo-review-submitted"><?php echo esc_html($existing_status === 'publish' ? __('Review published', 'kangoo') : __('Review awaiting approval', 'kangoo')); ?></span>
                    <?php else : ?>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <?php wp_nonce_field('kangoo_submit_order_review_' . $order->get_id() . '_' . $item_id, 'kangoo_review_nonce'); ?>
                            <input type="hidden" name="action" value="kangoo_submit_order_review">
                            <input type="hidden" name="order_id" value="<?php echo esc_attr($order->get_id()); ?>">
                            <input type="hidden" name="order_item_id" value="<?php echo esc_attr($item_id); ?>">
                            <label>
                                <span><?php esc_html_e('Rating', 'kangoo'); ?></span>
                                <select name="rating" required>
                                    <option value="5">5/5</option>
                                    <option value="4">4/5</option>
                                    <option value="3">3/5</option>
                                    <option value="2">2/5</option>
                                    <option value="1">1/5</option>
                                </select>
                            </label>
                            <label>
                                <span><?php esc_html_e('Review', 'kangoo'); ?></span>
                                <textarea name="review_body" rows="4" required placeholder="<?php esc_attr_e('What did you think?', 'kangoo'); ?>"></textarea>
                            </label>
                            <button class="button" type="submit"><?php esc_html_e('Submit review', 'kangoo'); ?></button>
                        </form>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
}
add_action('woocommerce_order_details_after_order_table', 'kangoo_reviews_theme_render_order_review_forms', 20);

function kangoo_reviews_theme_order_actions($actions, $order) {
    if (!$order instanceof WC_Order || $order->get_status() !== 'completed') {
        return $actions;
    }

    if (!function_exists('kangoo_reviews_customer_order_item_context')) {
        return $actions;
    }

    $actions['kangoo-review'] = array(
        'url'  => $order->get_view_order_url() . '#kangoo-order-reviews',
        'name' => __('Review item', 'kangoo'),
    );

    return $actions;
}
add_filter('woocommerce_my_account_my_orders_actions', 'kangoo_reviews_theme_order_actions', 20, 2);

function kangoo_reviews_theme_handle_order_review_submission() {
    if (!is_user_logged_in() || !function_exists('kangoo_reviews_create_customer_review')) {
        wp_safe_redirect(wp_get_referer() ?: wc_get_account_endpoint_url('orders'));
        exit;
    }

    $order_id = absint($_POST['order_id'] ?? 0);
    $order_item_id = absint($_POST['order_item_id'] ?? 0);
    $nonce = sanitize_text_field(wp_unslash($_POST['kangoo_review_nonce'] ?? ''));

    if (!$order_id || !$order_item_id || !wp_verify_nonce($nonce, 'kangoo_submit_order_review_' . $order_id . '_' . $order_item_id)) {
        wc_add_notice(__('Review submission could not be verified. Please try again.', 'kangoo'), 'error');
        wp_safe_redirect(wp_get_referer() ?: wc_get_account_endpoint_url('orders'));
        exit;
    }

    $result = kangoo_reviews_create_customer_review(
        get_current_user_id(),
        $order_id,
        $order_item_id,
        $_POST['rating'] ?? 5,
        wp_unslash($_POST['review_body'] ?? '')
    );

    if (is_wp_error($result)) {
        wc_add_notice($result->get_error_message(), 'error');
    } else {
        wc_add_notice(__('Thanks. Your verified review is awaiting approval.', 'kangoo'), 'success');
    }

    wp_safe_redirect(wp_get_referer() ?: wc_get_account_endpoint_url('orders'));
    exit;
}
add_action('admin_post_kangoo_submit_order_review', 'kangoo_reviews_theme_handle_order_review_submission');
