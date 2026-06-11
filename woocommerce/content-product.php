<?php
defined('ABSPATH') || exit;

global $product;

if (empty($product) || !$product->is_visible()) {
    return;
}

$badge = function_exists('kangoo_get_product_badge') ? kangoo_get_product_badge($product->get_id()) : null;

$is_quick_add_card = is_front_page() || is_product_category() || is_shop() || is_product_taxonomy() || (function_exists('is_cart') && is_cart());
$is_variable = $product->is_type('variable');
$card_unique_suffix = function_exists('wp_unique_id') ? wp_unique_id() : uniqid('', false);
$modal_id = 'quick-add-' . $product->get_id() . '-' . $card_unique_suffix;
$pack_modal_id = 'pack-add-' . $product->get_id() . '-' . $card_unique_suffix;
$card_pack_tiers = function_exists('kangoo_get_pack_pricing_tiers') ? kangoo_get_pack_pricing_tiers($product->get_id()) : array();
$card_stock_limit = function_exists('kangoo_get_product_stock_limit') ? kangoo_get_product_stock_limit($product) : null;
$card_low_stock_message = function_exists('kangoo_get_low_stock_message') ? kangoo_get_low_stock_message($product) : '';
$card_is_99p = function_exists('kangoo_is_99p_product') && kangoo_is_99p_product($product->get_id());
$card_purchase_limit = $card_is_99p ? 1 : $card_stock_limit;
$card_default_pack_tier = !empty($card_pack_tiers) ? $card_pack_tiers[0] : null;
$card_has_available_pack_tier = false;
$card_price = (float) $product->get_price();
$card_regular_price = (float) $product->get_regular_price();
$card_saving_per_can = max(0, $card_regular_price - $card_price);
$modal_image_attrs = array(
    'loading' => 'eager',
    'sizes'   => '(max-width: 640px) 44vw, 300px',
);
$card_strength_label = '';
$card_strength_mg = function_exists('get_field') ? get_field('strength_mg') : '';

if ($card_strength_mg) {
    $card_strength_label = strtoupper((string) $card_strength_mg);

    if (strpos($card_strength_label, 'MG') === false) {
        $card_strength_label .= 'MG';
    }
}

if (!empty($card_pack_tiers)) {
    foreach ($card_pack_tiers as $card_pack_tier) {
        if ($card_stock_limit !== null && (int) $card_pack_tier['quantity'] > $card_stock_limit) {
            continue;
        }

        $card_has_available_pack_tier = true;

        if (!empty($card_pack_tier['default_selected'])) {
            $card_default_pack_tier = $card_pack_tier;
            break;
        }

        if ($card_default_pack_tier && $card_stock_limit !== null && (int) $card_default_pack_tier['quantity'] > $card_stock_limit) {
            $card_default_pack_tier = $card_pack_tier;
        }
    }
}

if ($card_is_99p) {
    $card_default_pack_tier = null;
    $card_has_available_pack_tier = false;
}

$available_variations = array();
$has_purchasable_variation = false;

if ($is_quick_add_card && $is_variable) {
    $available_variations = $product->get_available_variations();

    foreach ($available_variations as $variation_data) {
        $is_in_stock = !empty($variation_data['is_in_stock']);
        $is_purchasable = !isset($variation_data['is_purchasable']) || !empty($variation_data['is_purchasable']);

        if ($is_in_stock && $is_purchasable) {
            $has_purchasable_variation = true;
            break;
        }
    }
}
?>

<article <?php wc_product_class('product-card', $product); ?> data-product-card-id="<?php echo esc_attr($product->get_id()); ?>">
    <?php if (function_exists('kangoo_render_product_card_event_decoration')) : ?>
        <?php kangoo_render_product_card_event_decoration(); ?>
    <?php endif; ?>

    <?php if (!$product->is_in_stock()) : ?>
        <div class="product-badge product-badge--out-of-stock">
            <?php esc_html_e('Sold Out', 'kangoo'); ?>
        </div>
    <?php if ($badge) : ?>
        <span class="product-badge product-badge--<?php echo esc_attr($badge['key']); ?>">
            <?php echo esc_html($badge['label']); ?>
        </span>
    <?php endif; ?>

    <a href="<?php the_permalink(); ?>" class="product-card__media">
        <?php
        $card_image_attrs = array(
            'loading' => 'lazy',
        );

        if (function_exists('kangoo_should_prioritize_product_card_image') && kangoo_should_prioritize_product_card_image()) {
            $card_image_attrs['loading'] = 'eager';
            $card_image_attrs['fetchpriority'] = 'high';
        }

        echo function_exists('kangoo_get_product_card_thumbnail')
            ? kangoo_get_product_card_thumbnail($product, $card_image_attrs)
            : woocommerce_get_product_thumbnail('woocommerce_thumbnail');
        ?>
    </a>

    <div class="product-card__content">
        <div class="product-card__heading">
            <h3 class="product-card__title">
                <a href="<?php the_permalink(); ?>">
                    <?php the_title(); ?>
                </a>
            </h3>

            <?php if ($card_strength_label) : ?>
                <div class="product-card__strength-badges">
                    <span class="product-card__strength-badge">
                        <?php echo esc_html($card_strength_label); ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>

        <?php if (function_exists('kangoo_reviews_theme_render_card_summary')) : ?>
            <?php kangoo_reviews_theme_render_card_summary($product->get_id()); ?>
        <?php endif; ?>

        <div class="product-card__price">
            <?php echo wp_kses_post(function_exists('kangoo_get_product_price_html') ? kangoo_get_product_price_html($product) : $product->get_price_html()); ?>
        </div>

        <?php if (!$is_variable && $card_saving_per_can > 0) : ?>
            <div class="product-card__saving" data-card-saving>
                You save <?php echo wp_kses_post(wc_price($card_saving_per_can)); ?>
            </div>
        <?php endif; ?>

        <?php if ($card_low_stock_message) : ?>
            <div class="product-card__stock product-card__stock--low">
                <?php echo esc_html($card_low_stock_message); ?>
            </div>
        <?php endif; ?>
		
		<div class="quick-add-modal__saving" data-quick-add-saving></div>

        <div class="product-card__actions">
            <?php if ($is_quick_add_card && $is_variable) : ?>
                <?php if ($has_purchasable_variation && !empty($available_variations)) : ?>
                    <button
                        type="button"
                        class="btn btn--primary quick-add-open"
                        data-quick-add-target="<?php echo esc_attr($modal_id); ?>"
                    >
                        Choose options
                    </button>
                <?php else : ?>
                    <button type="button" class="btn btn--primary is-disabled" disabled>
                        Sold Out
                    </button>
                <?php endif; ?>
			<?php else : ?>
				<?php if (!$product->is_in_stock()) : ?>
					<button type="button" class="btn btn--primary is-disabled" disabled>
						Sold Out
					</button>
				<?php else : ?>
				<?php
				$product_id     = $product->get_id();
				$price          = $card_price;
				$regular_price  = $card_regular_price;
                $pack_tiers     = $card_is_99p ? array() : $card_pack_tiers;
                $default_pack   = $card_default_pack_tier;
                $default_qty    = $default_pack ? (int) $default_pack['quantity'] : 1;
                $default_unit   = $default_pack ? (float) $default_pack['unit_price'] : $price;

                if ($card_is_99p || (!empty($pack_tiers) && !$card_has_available_pack_tier)) {
                    $default_pack = null;
                    $default_qty = 1;
                    $default_unit = $price;
                }

                $default_total  = $default_unit * $default_qty;
                $has_visible_pack_options = !$card_is_99p && !empty($pack_tiers) && $default_pack && $card_has_available_pack_tier;
				?>

				<div
					class="product-card__qty<?php echo $has_visible_pack_options ? ' product-card__qty--pack' : ''; ?><?php echo $card_is_99p ? ' product-card__qty--hidden' : ''; ?>"
					data-card-qty
					data-price="<?php echo esc_attr($price); ?>"
					data-regular-price="<?php echo esc_attr($regular_price); ?>"
					data-stock-limit="<?php echo esc_attr($card_purchase_limit !== null ? $card_purchase_limit : ''); ?>"
					data-is-99p="<?php echo $card_is_99p ? '1' : '0'; ?>"
					data-pack-tiers="<?php echo esc_attr(wp_json_encode($pack_tiers)); ?>"
				>
                    <?php if ($card_is_99p) : ?>
                        <input
                            type="hidden"
                            class="product-card__qty-input"
                            value="1"
                            min="1"
                            max="1"
                            data-card-qty-input
                        >
                    <?php elseif ($has_visible_pack_options) : ?>
                        <input
                            type="hidden"
                            class="product-card__qty-input"
                            value="<?php echo esc_attr($default_qty); ?>"
                            min="1"
                            max="<?php echo esc_attr($card_purchase_limit !== null ? $card_purchase_limit : ''); ?>"
                            data-card-qty-input
                        >

                        <button
                            type="button"
                            class="product-card__pack-trigger"
                            data-pack-add-open
                            data-pack-add-target="<?php echo esc_attr($pack_modal_id); ?>"
                        >
                            <span data-card-pack-label>
                                <?php
                                printf(
                                    esc_html(_n('%d-pack', '%d-pack', $default_qty, 'kangoo')),
                                    $default_qty
                                );
                                ?>
                            </span>
                            <span data-card-pack-unit><?php echo wp_kses_post(wc_price($default_unit)); ?><?php esc_html_e('/unit', 'kangoo'); ?></span>
                        </button>
                    <?php else : ?>
                        <button type="button" class="qty-btn qty-btn--minus" data-card-minus>-</button>

                        <input
                            type="number"
                            class="product-card__qty-input"
                            value="1"
                            min="1"
                            max="<?php echo esc_attr($card_purchase_limit !== null ? $card_purchase_limit : ''); ?>"
                            aria-label="Quantity"
                            data-card-qty-input
                        >

                        <button type="button" class="qty-btn qty-btn--plus" data-card-plus <?php disabled($card_is_99p); ?>>+</button>
                    <?php endif; ?>
				</div>

				<a
					href="#"
					data-quantity="<?php echo esc_attr($default_qty); ?>"
					class="btn btn--primary product-card__add"
					data-product_id="<?php echo esc_attr($product_id); ?>"
					data-product_sku="<?php echo esc_attr($product->get_sku()); ?>"
					data-is-99p="<?php echo $card_is_99p ? '1' : '0'; ?>"
					data-card-add
					rel="nofollow"
				>
					Add to cart &middot; <?php echo wp_kses_post(wc_price($default_total)); ?>
				</a>
				<?php endif; ?>
			<?php endif; ?>
        </div>
    </div>
</article>

<?php if (!$is_variable && !$card_is_99p && !empty($card_pack_tiers) && $card_default_pack_tier && $card_has_available_pack_tier) : ?>
    <div
        class="pack-add-modal"
        id="<?php echo esc_attr($pack_modal_id); ?>"
        aria-hidden="true"
        data-product-card-id="<?php echo esc_attr($product->get_id()); ?>"
        data-product-title="<?php echo esc_attr(get_the_title($product->get_id())); ?>"
        data-product-image="<?php echo esc_url(get_the_post_thumbnail_url($product->get_id(), 'woocommerce_thumbnail')); ?>"
    >
        <div class="pack-add-modal__overlay" data-pack-add-close></div>

        <div class="pack-add-modal__panel" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr(sprintf(__('Choose pack size for %s', 'kangoo'), get_the_title($product->get_id()))); ?>">
            <button type="button" class="pack-add-modal__close" data-pack-add-close aria-label="<?php esc_attr_e('Close', 'kangoo'); ?>">
                &times;
            </button>

            <div class="pack-add-modal__top">
                <div class="pack-add-modal__image">
                    <?php
                    echo function_exists('kangoo_get_product_card_thumbnail')
                        ? kangoo_get_product_card_thumbnail($product, $modal_image_attrs)
                        : woocommerce_get_product_thumbnail('woocommerce_thumbnail');
                    ?>
                </div>
                <div>
                    <div class="pack-add-modal__brand"><?php echo esc_html($product->get_attribute('pa_brand')); ?></div>
                    <h3 class="pack-add-modal__title"><?php echo esc_html(get_the_title($product->get_id())); ?></h3>
                </div>
            </div>

            <div class="pack-add-modal__options">
                <?php foreach ($card_pack_tiers as $tier) : ?>
                    <?php
                    $tier_qty = (int) $tier['quantity'];
                    $tier_total = (float) $tier['pack_price'];
                    $tier_unit = (float) $tier['unit_price'];
                    $is_unavailable = $card_stock_limit !== null && $tier_qty > $card_stock_limit;
                    $is_default = !$is_unavailable && $card_default_pack_tier && $tier_qty === (int) $card_default_pack_tier['quantity'];
                    ?>
                    <button
                        type="button"
                        class="pack-add-modal__option<?php echo $is_default ? ' is-active' : ''; ?><?php echo $is_unavailable ? ' is-disabled' : ''; ?>"
                        data-card-pack-option
                        data-pack-qty="<?php echo esc_attr($tier_qty); ?>"
                        data-pack-price="<?php echo esc_attr($tier_total); ?>"
                        data-unit-price="<?php echo esc_attr($tier_unit); ?>"
                        aria-pressed="<?php echo $is_default ? 'true' : 'false'; ?>"
                        <?php disabled($is_unavailable); ?>
                    >
                        <span>
                            <?php
                            printf(
                                esc_html(_n('%d-pack', '%d-pack', $tier_qty, 'kangoo')),
                                $tier_qty
                            );
                            ?>
                        </span>
                        <strong><?php echo wp_kses_post(wc_price($tier_total)); ?></strong>
                        <small>
                            <?php if ($is_unavailable) : ?>
                                <?php
                                if ($card_stock_limit !== null && $card_stock_limit > 0 && $card_stock_limit < kangoo_low_stock_public_threshold()) {
                                    printf(
                                        esc_html(_n('Only %d left', 'Only %d left', (int) $card_stock_limit, 'kangoo')),
                                        (int) $card_stock_limit
                                    );
                                } else {
                                    esc_html_e('Not enough stock', 'kangoo');
                                }
                                ?>
                            <?php else : ?>
                                <?php echo wp_kses_post(wc_price($tier_unit)); ?><?php esc_html_e('/unit', 'kangoo'); ?>
                            <?php endif; ?>
                        </small>
                    </button>
                <?php endforeach; ?>
            </div>

            <button type="button" class="btn btn--primary pack-add-modal__submit" data-pack-add-submit>
                <?php esc_html_e('Add to cart', 'kangoo'); ?>
            </button>
        </div>
    </div>
<?php endif; ?>

<?php if ($is_quick_add_card && $is_variable && $has_purchasable_variation && !empty($available_variations)) : ?>
    <div
        class="quick-add-modal"
        id="<?php echo esc_attr($modal_id); ?>"
        aria-hidden="true"
        data-product-id="<?php echo esc_attr($product->get_id()); ?>"
        data-is-99p="<?php echo $card_is_99p ? '1' : '0'; ?>"
        data-suppress-stock-note="<?php echo $card_is_99p ? '1' : '0'; ?>"
        data-pack-tiers="<?php echo esc_attr(wp_json_encode($card_pack_tiers)); ?>"
    >
        <div class="quick-add-modal__overlay" data-quick-add-close></div>

        <div class="quick-add-modal__panel" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr(get_the_title()); ?>">
            <button type="button" class="quick-add-modal__close" data-quick-add-close aria-label="Close">
                &times;
            </button>

            <div class="quick-add-modal__top">
                <div class="quick-add-modal__image">
                    <?php
                    echo function_exists('kangoo_get_product_card_thumbnail')
                        ? kangoo_get_product_card_thumbnail($product, $modal_image_attrs)
                        : woocommerce_get_product_thumbnail('woocommerce_thumbnail');
                    ?>
                </div>

                <div class="quick-add-modal__summary">
                    <div class="quick-add-modal__title"><?php the_title(); ?></div>
                    <div class="quick-add-modal__price" data-quick-add-price>
                        <?php echo wp_kses_post(function_exists('kangoo_get_product_price_html') ? kangoo_get_product_price_html($product) : $product->get_price_html()); ?>
                    </div>
                </div>
            </div>

            <form class="quick-add-form">
                <input type="hidden" name="product_id" value="<?php echo esc_attr($product->get_id()); ?>">
                <input type="hidden" name="variation_id" value="">
                <input type="hidden" name="quantity" value="1">

                <?php foreach ($product->get_variation_attributes() as $attribute_name => $options) : ?>
                    <?php
                    $label = wc_attribute_label($attribute_name);
                    ?>
                    <div class="quick-add-group">
                        <div class="quick-add-group__label"><?php echo esc_html($label); ?></div>

                        <div class="quick-add-pills" data-attribute="<?php echo esc_attr('attribute_' . $attribute_name); ?>">
                            <?php foreach ($options as $option) : ?>
                                <?php
                                $term = taxonomy_exists($attribute_name) ? get_term_by('slug', $option, $attribute_name) : false;
                                $option_label = $term && !is_wp_error($term) ? $term->name : $option;
                                ?>
                                <button
                                    type="button"
                                    class="quick-add-pill"
                                    data-name="<?php echo esc_attr('attribute_' . $attribute_name); ?>"
                                    data-value="<?php echo esc_attr($option); ?>"
                                >
                                    <?php echo esc_html($option_label); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="quick-add-qty">
                    <button type="button" class="qty-btn qty-btn--minus" data-quick-add-minus>-</button>
                    <input type="number" class="quick-add-qty__input" value="1" min="1" <?php echo $card_is_99p ? 'max="1"' : ''; ?> aria-label="Quantity">
                    <button type="button" class="qty-btn qty-btn--plus" data-quick-add-plus <?php disabled($card_is_99p); ?>>+</button>
                </div>

                <div class="quick-add-stock-note" data-quick-add-stock-note hidden></div>

                <button type="submit" class="btn btn--primary quick-add-submit is-disabled" disabled>
                    Add to cart
                </button>
            </form>

            <script type="application/json" class="quick-add-variations">
                <?php echo wp_json_encode($available_variations); ?>
            </script>
        </div>
    </div>
<?php endif; ?>
