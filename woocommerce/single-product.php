<?php
defined('ABSPATH') || exit;
get_header();
?>

<?php while (have_posts()) : the_post(); global $product; ?>

<?php
$main_image_id     = $product->get_image_id();
$gallery_image_ids = $product->get_gallery_image_ids();

$main_image_url = $main_image_id ? wp_get_attachment_image_url($main_image_id, 'large') : wc_placeholder_img_src('large');
$main_image_alt = $main_image_id ? get_post_meta($main_image_id, '_wp_attachment_image_alt', true) : get_the_title();
$is_99p_product = function_exists('kangoo_is_99p_product') && kangoo_is_99p_product($product->get_id());
$product_badge = function_exists('kangoo_get_product_badge') ? kangoo_get_product_badge($product->get_id()) : null;
$product_strength_label = '';

if (function_exists('kangoo_get_product_strength_details')) {
    $product_strength = kangoo_get_product_strength_details($product);
    $product_strength_label = !empty($product_strength['label']) ? (string) $product_strength['label'] : '';
} elseif (function_exists('get_field')) {
    $product_strength_label = (string) get_field('strength_mg');

    if ($product_strength_label !== '') {
        $product_strength_label = strtoupper($product_strength_label);

        if (strpos($product_strength_label, 'MG') === false) {
            $product_strength_label .= 'MG';
        }
    }
}

$product_gallery_items = array();

if ($main_image_id) {
    $product_gallery_items[] = array(
        'id'    => $main_image_id,
        'large' => $main_image_url,
        'alt'   => $main_image_alt ?: get_the_title(),
    );
}

foreach ($gallery_image_ids as $gallery_image_id) {
    $thumb_large = wp_get_attachment_image_url($gallery_image_id, 'large');

    if (!$thumb_large) {
        continue;
    }

    $product_gallery_items[] = array(
        'id'    => $gallery_image_id,
        'large' => $thumb_large,
        'alt'   => get_post_meta($gallery_image_id, '_wp_attachment_image_alt', true) ?: get_the_title(),
    );
}

if (count($product_gallery_items) < 2) {
    $attached_image_ids = get_posts(array(
        'post_parent'    => $product->get_id(),
        'post_type'      => 'attachment',
        'post_mime_type' => 'image',
        'post_status'    => 'inherit',
        'posts_per_page' => 4,
        'fields'         => 'ids',
        'exclude'        => $main_image_id ? array($main_image_id) : array(),
    ));

    foreach ($attached_image_ids as $attached_image_id) {
        $attached_large = wp_get_attachment_image_url($attached_image_id, 'large');

        if (!$attached_large) {
            continue;
        }

        $product_gallery_items[] = array(
            'id'    => $attached_image_id,
            'large' => $attached_large,
            'alt'   => get_post_meta($attached_image_id, '_wp_attachment_image_alt', true) ?: get_the_title(),
        );
    }
}

$product_flavour_label     = function_exists('kangoo_get_product_flavour_label') ? kangoo_get_product_flavour_label($product) : trim(wp_strip_all_tags((string) $product->get_attribute('pa_flavour')));
$product_flavour_label     = trim(explode(',', $product_flavour_label)[0]);
$product_flavour_words     = strtolower($product_flavour_label . ' ' . get_the_title());
$product_flavour_icon_slug = 'fruit';

if (preg_match('/tropical|mango|pineapple|passion/i', $product_flavour_words)) {
    $product_flavour_label     = __('Tropical', 'kangoo');
    $product_flavour_icon_slug = 'tropical';
} elseif (preg_match('/coffee|espresso|mocha/i', $product_flavour_words)) {
    $product_flavour_label     = __('Coffee', 'kangoo');
    $product_flavour_icon_slug = 'coffee';
} elseif (preg_match('/cola|soda/i', $product_flavour_words)) {
    $product_flavour_label     = __('Cola', 'kangoo');
    $product_flavour_icon_slug = 'cola';
} elseif (preg_match('/mint|menthol|peppermint|spearmint|ice|freeze/i', $product_flavour_words)) {
    $product_flavour_label     = __('Fresh Mint', 'kangoo');
    $product_flavour_icon_slug = 'mint';
} elseif (preg_match('/citrus|lemon|lime|orange|grapefruit/i', $product_flavour_words)) {
    $product_flavour_label     = __('Citrus', 'kangoo');
    $product_flavour_icon_slug = 'citrus';
} elseif (preg_match('/berry|berries|cherry|fruit|fruits|grape|raspberry|strawberry|blueberry/i', $product_flavour_words)) {
    $product_flavour_label     = __('Sweet Berry', 'kangoo');
    $product_flavour_icon_slug = 'berry';
} elseif ($product_flavour_label === '') {
    $product_flavour_label = __('Flavour', 'kangoo');
}

$product_feature_icon_base_url = trailingslashit(get_theme_file_uri('assets/images/flavour-icons-orange-samples'));
$product_flavour_icon_url = function_exists('kangoo_get_product_flavour_icon_url') ? kangoo_get_product_flavour_icon_url($product, 'thumbnail') : '';

if ($product_flavour_icon_url === '') {
    $product_flavour_icon_url = $product_feature_icon_base_url . $product_flavour_icon_slug . '-icon-orange.png';
}

$product_strength_feature = $product_strength_label !== ''
    ? preg_replace('/\s*MG\b/i', 'mg', $product_strength_label) . ' ' . __('Strength', 'kangoo')
    : __('Strength', 'kangoo');
$product_pouch_count = function_exists('kangoo_get_product_pouch_count') ? kangoo_get_product_pouch_count($product) : 20;

if ($product_pouch_count <= 0) {
    $product_pouch_count = 20;
}

$product_mobile_features = array(
    array('key' => 'strength', 'label' => $product_strength_feature),
    array('key' => 'flavour', 'label' => $product_flavour_label, 'icon' => $product_flavour_icon_url),
    array('key' => 'green', 'label' => __('Tobacco Free', 'kangoo'), 'icon' => $product_feature_icon_base_url . 'tobacco-free-icon-orange.png'),
    array('key' => 'box', 'label' => sprintf(_n('%d Pouch', '%d Pouches', $product_pouch_count, 'kangoo'), $product_pouch_count)),
);

$strength_attribute_name  = '';
$strength_attribute_label = '';
$strength_options         = array();

if ($product && $product->is_type('variable')) {
    $variation_attributes = $product->get_variation_attributes();

    foreach ($variation_attributes as $attribute_name => $options) {
        $attribute_slug = wc_variation_attribute_name(str_replace('attribute_', '', $attribute_name));

        if (
            $attribute_slug === 'attribute_pa_strength' ||
            $attribute_slug === 'attribute_strength' ||
            strpos($attribute_slug, 'strength') !== false
        ) {
            $strength_attribute_name  = $attribute_slug;
            $strength_attribute_label = wc_attribute_label(str_replace('attribute_', '', $attribute_slug));
            $strength_options         = array_values(array_filter($options));
            break;
        }
    }
}

if (!$strength_attribute_label) {
    $strength_attribute_label = __('Select Strength', 'kangoo');
}

$product_faq_rows = array();
$product_faq_fields = array('product_faqs', 'product_faq', 'faqs');

foreach ($product_faq_fields as $product_faq_field) {
    $maybe_rows = function_exists('get_field') ? get_field($product_faq_field) : array();

    if (is_array($maybe_rows) && !empty($maybe_rows)) {
        $product_faq_rows = $maybe_rows;
        break;
    }
}

$product_faq_schema = array();
$product_highlights = function_exists('get_field') ? get_field('highlights') : array();

if (!is_array($product_highlights)) {
    $product_highlights = array();
}

foreach ($product_faq_rows as $product_faq_row) {
    $question = isset($product_faq_row['question']) ? wp_strip_all_tags((string) $product_faq_row['question']) : '';
    $answer   = isset($product_faq_row['answer']) ? wp_strip_all_tags((string) $product_faq_row['answer']) : '';

    if ($question === '' || $answer === '') {
        continue;
    }

    $product_faq_schema[] = array(
        '@type' => 'Question',
        'name'  => $question,
        'acceptedAnswer' => array(
            '@type' => 'Answer',
            'text'  => $answer,
        ),
    );
}
?>

<main class="section">
    <div class="container">
        <div class="product-page">
            <div class="product-page__grid">
                <div class="product-media">
                    <div class="product-image">
                        <img
                            id="product-main-image"
                            src="<?php echo esc_url($main_image_url); ?>"
                            alt="<?php echo esc_attr($main_image_alt ?: get_the_title()); ?>"
                        >

                        <?php if (!$product->is_in_stock()) : ?>
                            <span class="product-badge product-badge--out-of-stock product-image__badge">
                                <?php esc_html_e('Sold Out', 'kangoo'); ?>
                            </span>
                        <?php elseif ($product_badge) : ?>
                            <span class="product-badge product-badge--<?php echo esc_attr(sanitize_html_class($product_badge['key'])); ?> product-image__badge">
                                <?php echo esc_html($product_badge['label']); ?>
                            </span>
                        <?php endif; ?>

                        <?php if ($product_strength_label !== '') : ?>
                            <span class="product-image__strength-badge">
                                <?php echo esc_html($product_strength_label); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($product_gallery_items)) : ?>
                        <?php
                        $product_gallery_count = count($product_gallery_items);
                        $product_visible_thumb_count = min(2, $product_gallery_count);
                        ?>
                        <div class="product-thumbs-wrap<?php echo $product_gallery_count > 2 ? ' product-thumbs-wrap--has-arrows' : ''; ?>" data-product-gallery>
                            <?php if ($product_gallery_count > 2) : ?>
                                <button type="button" class="product-thumbs__arrow product-thumbs__arrow--prev" data-product-gallery-arrow="prev" aria-label="<?php esc_attr_e('Previous product image', 'kangoo'); ?>">
                                    <span aria-hidden="true">&#8249;</span>
                                </button>
                            <?php endif; ?>

                            <div class="product-thumbs product-thumbs--count-<?php echo esc_attr($product_visible_thumb_count); ?>" data-product-gallery-track>
                            <?php foreach ($product_gallery_items as $gallery_index => $gallery_item) : ?>
                                <button
                                    type="button"
                                    class="product-thumb<?php echo $gallery_index === 0 ? ' is-active' : ''; ?>"
                                    data-image="<?php echo esc_url($gallery_item['large']); ?>"
                                    data-alt="<?php echo esc_attr($gallery_item['alt']); ?>"
                                >
                                    <?php echo wp_get_attachment_image($gallery_item['id'], 'thumbnail'); ?>
                                </button>
                            <?php endforeach; ?>
                            </div>

                            <?php if ($product_gallery_count > 2) : ?>
                                <button type="button" class="product-thumbs__arrow product-thumbs__arrow--next" data-product-gallery-arrow="next" aria-label="<?php esc_attr_e('Next product image', 'kangoo'); ?>">
                                    <span aria-hidden="true">&#8250;</span>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($product_highlights)) : ?>
                        <div class="product-highlights-panel" aria-label="<?php esc_attr_e('Product highlights', 'kangoo'); ?>">
                            <span><?php esc_html_e('Product details', 'kangoo'); ?></span>
                            <ul class="product-highlights product-highlights--desktop">
                                <?php foreach ($product_highlights as $highlight) : ?>
                                    <?php if (!empty($highlight['text'])) : ?>
                                        <li><?php echo esc_html($highlight['text']); ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="product-page__info">
					<h1 class="product-title">
						<?php the_title(); ?>
					</h1>

                    <?php
                    if (function_exists('kangoo_reviews_theme_render_summary_link')) {
                        kangoo_reviews_theme_render_summary_link($product->get_id());
                    }
                    ?>

                    <div class="product-mobile-summary" data-product-mobile-summary>
                        <p class="product-subtitle">
                            <?php echo get_the_excerpt(); ?>
                        </p>

                        <?php if (!empty($product_highlights)) : ?>
                            <ul class="product-highlights product-highlights--mobile">
                                <?php foreach ($product_highlights as $highlight) : ?>
                                    <?php if (!empty($highlight['text'])) : ?>
                                        <li><?php echo esc_html($highlight['text']); ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <button type="button" class="product-mobile-summary__toggle" data-product-mobile-summary-toggle aria-expanded="false">
                            <span data-product-mobile-summary-toggle-text><?php esc_html_e('Read more', 'kangoo'); ?></span>
                            <span aria-hidden="true">&#8964;</span>
                        </button>
                    </div>

					<div
						class="product-price"
						id="product-price"
						data-product-price="<?php echo esc_attr(wc_get_price_to_display($product)); ?>"
						data-product-regular-price="<?php echo esc_attr((float) $product->get_regular_price()); ?>"
					>
						<?php echo wp_kses_post(function_exists('kangoo_get_product_price_html') ? kangoo_get_product_price_html($product) : $product->get_price_html()); ?>
					</div>
					
					<?php
					$regular_price = (float) $product->get_regular_price();
					$current_price = (float) $product->get_price();
					$saving        = max(0, $regular_price - $current_price);
					?>

					<?php if ($saving > 0) : ?>
						<div
							class="product-saving"
							data-single-saving
							data-saving-per-item="<?php echo esc_attr($saving); ?>"
						>
							You save <?php echo wp_kses_post(wc_price($saving)); ?>
						</div>
					<?php endif; ?>

                    <?php
                    $low_stock_message = function_exists('kangoo_get_low_stock_message') ? kangoo_get_low_stock_message($product) : '';
                    $suppress_stock_note = $is_99p_product;
                    $stock_limit = function_exists('kangoo_get_product_stock_limit') ? kangoo_get_product_stock_limit($product) : null;
                    $product_cart_classes = array('product-cart');

                    if ($is_99p_product) {
                        $product_cart_classes[] = 'product-cart--99p';
                    }

                    if ($stock_limit !== null && $stock_limit <= 1) {
                        $product_cart_classes[] = 'product-cart--single-quantity';
                    }

                    $product_cart_class = implode(' ', $product_cart_classes);
                    ?>

                    <div class="product-stock-note<?php echo $low_stock_message ? ' product-stock-note--low' : ''; ?>" data-product-stock-note data-suppress-stock-note="<?php echo $suppress_stock_note ? '1' : '0'; ?>"<?php echo $low_stock_message ? '' : ' hidden'; ?>>
                        <?php echo esc_html($low_stock_message); ?>
                    </div>

                    <div class="product-mobile-features" aria-label="<?php esc_attr_e('Product quick facts', 'kangoo'); ?>">
                        <?php foreach ($product_mobile_features as $feature) : ?>
                            <div class="product-mobile-feature product-mobile-feature--<?php echo esc_attr($feature['key']); ?>">
                                <span class="product-mobile-feature__icon" aria-hidden="true">
                                    <?php if (!empty($feature['icon'])) : ?>
                                        <img class="product-mobile-feature__image" src="<?php echo esc_url($feature['icon']); ?>" alt="" width="22" height="22" loading="lazy" decoding="async">
                                    <?php elseif ($feature['key'] === 'strength') : ?>
                                        <svg viewBox="0 0 24 24" focusable="false"><circle cx="12" cy="12" r="8.2" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M8 13.2h8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                                    <?php else : ?>
                                        <svg viewBox="0 0 24 24" focusable="false"><path d="m5 8 7-3 7 3-7 3-7-3Z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M5 8v8l7 3 7-3V8M12 11v8" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                                    <?php endif; ?>
                                </span>
                                <span class="product-mobile-feature__text"><?php echo esc_html($feature['label']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (!empty($strength_options) && !empty($strength_attribute_name)) : ?>
                        <div class="product-strength-ui">
                            <span class="product-strength-ui__label">
                                <?php echo esc_html($strength_attribute_label); ?>
                            </span>

                            <div
                                class="strength-options"
                                data-attribute="<?php echo esc_attr($strength_attribute_name); ?>"
                            >
                                <?php foreach ($strength_options as $option_value) : ?>
                                    <?php
                                    $term = taxonomy_exists(str_replace('attribute_', '', $strength_attribute_name))
                                        ? get_term_by('slug', $option_value, str_replace('attribute_', '', $strength_attribute_name))
                                        : false;

                                    $option_label = $term && !is_wp_error($term) ? $term->name : wc_clean($option_value);
                                    ?>
                                    <button
                                        type="button"
                                        class="strength-option"
                                        data-value="<?php echo esc_attr($option_value); ?>"
                                        aria-pressed="false"
                                    >
                                        <?php echo esc_html($option_label); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

					<?php if (!$product->is_in_stock()) : ?>
						<div class="<?php echo esc_attr($product_cart_class); ?>">
							<button class="single_add_to_cart_button button alt is-disabled" disabled>
								<span class="button-text">SOLD OUT</span>
							</button>
						</div>
					<?php else : ?>
						<div class="<?php echo esc_attr($product_cart_class); ?>">
							<?php woocommerce_template_single_add_to_cart(); ?>
						</div>
					<?php endif; ?>

                    <ul class="product-trust">
                        <li>Discreet Packaging</li>
                        <li>18+ Only</li>
                        <li>Earn Kangoo Rewards</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="product-accordion">
            <?php if (get_the_content()) : ?>
                <details class="product-accordion__item">
                    <summary>Description</summary>
                    <div class="product-accordion__content wysiwyg">
                        <?php the_content(); ?>
                    </div>
                </details>
            <?php endif; ?>

            <?php
            if (function_exists('kangoo_reviews_theme_render_accordion')) {
                kangoo_reviews_theme_render_accordion($product->get_id());
            }
            ?>

			<?php
			$delivery_override = get_field('override_delivery_info');
			$delivery_custom   = get_field('delivery_info');

			$how_override = get_field('override_how_to_use_info');
			$how_custom   = get_field('how_to_use');

			$delivery_info = '
			<p><strong>Free UK Delivery over &pound;14.95</strong></p>
			<ul>
				<li>Same-day dispatch on orders placed before 10am</li>
				<li>Free standard UK delivery on orders over &pound;14.95</li>
				<li>Shipped with Royal Mail</li>
				<li>UK delivery only</li>
				<li>Discreet packaging</li>
			</ul>
			';

			$how_to_use = '
			<p>Place one nicotine pouch under your upper lip and leave it in place for up to 30 minutes.</p>
			<p>No need to chew or spit. Dispose of responsibly after use.</p>
			';

			if ($delivery_override && $delivery_custom) {
				$delivery_info = $delivery_custom;
			}

			if ($how_override && $how_custom) {
				$how_to_use = $how_custom;
			}
			?>
			
			<?php if ($delivery_info) : ?>
				<details class="product-accordion__item">
					<summary>Delivery</summary>
					<div class="product-accordion__content wysiwyg">
						<?php echo wp_kses_post($delivery_info); ?>
					</div>
				</details>
			<?php endif; ?>

			<?php if ($how_to_use) : ?>
				<details class="product-accordion__item">
					<summary>How to use</summary>
					<div class="product-accordion__content wysiwyg">
						<?php echo wp_kses_post($how_to_use); ?>
					</div>
				</details>
			<?php endif; ?>

            <?php if (!empty($product_faq_rows)) : ?>
                <details class="product-accordion__item">
                    <summary>FAQs</summary>
                    <div class="product-accordion__content">
                        <div class="faq-list">
                            <?php foreach ($product_faq_rows as $product_faq_row) : ?>
                                <?php
                                $question = isset($product_faq_row['question']) ? trim((string) $product_faq_row['question']) : '';
                                $answer   = isset($product_faq_row['answer']) ? trim((string) $product_faq_row['answer']) : '';

                                if ($question === '' || $answer === '') {
                                    continue;
                                }
                                ?>
                                <details>
                                    <summary><?php echo esc_html($question); ?></summary>
                                    <div class="wysiwyg">
                                        <p><?php echo wp_kses_post($answer); ?></p>
                                    </div>
                                </details>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </details>
            <?php endif; ?>

            <?php
            if (function_exists('kangoo_reviews_theme_render_section')) {
                kangoo_reviews_theme_render_section($product->get_id());
            }
            ?>

        <?php
        $related_ids = wc_get_related_products($product->get_id(), 4);

        if (!empty($related_ids)) :
        ?>
            <section class="related-products">
                <div class="section-header section-header--left">
                    <span class="eyebrow">You may also like</span>
                    <h2>Related products</h2>
                </div>

                <div class="woo-grid">
                    <?php
                    $related_query = new WP_Query(array(
                        'post_type'      => 'product',
                        'post__in'       => $related_ids,
                        'posts_per_page' => 4,
                        'orderby'        => 'post__in',
                    ));

                    if ($related_query->have_posts()) :
                        while ($related_query->have_posts()) : $related_query->the_post();
                            wc_get_template_part('content', 'product');
                        endwhile;
                        wp_reset_postdata();
                    endif;
                    ?>
                </div>
            </section>
        <?php endif; ?>
    </div>

    <div class="sticky-add<?php echo $is_99p_product ? ' sticky-add--single-pack' : ''; ?>" data-sticky-add>
        <div class="container">
            <div class="sticky-add__bar">
                <label class="sticky-add__pack">
                    <select data-sticky-pack-select aria-label="<?php esc_attr_e('Choose pack size', 'kangoo'); ?>">
                        <option value="1"><?php esc_html_e('1-pack', 'kangoo'); ?></option>
                    </select>
                </label>

                <div class="sticky-add__summary" data-sticky-summary>
                    <span class="sticky-add__unit" data-sticky-unit-price></span>
                    <span class="sticky-add__saving" data-sticky-saving></span>
                    <span class="sticky-add__meta" data-sticky-meta hidden>
                        <?php esc_html_e('1-pack', 'kangoo'); ?>
                    </span>
                    <span data-sticky-price hidden><?php echo wp_kses_post(wc_price(wc_get_price_to_display($product))); ?></span>
                </div>

                <button id="sticky-add-btn" type="button" class="btn btn--primary sticky-add__button" data-sticky-add-button>
                    <span class="sticky-add__button-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false">
                            <path d="M6 6h15l-1.5 8.5a2 2 0 0 1-2 1.5H9a2 2 0 0 1-2-1.3L4.3 4H2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="9" cy="20" r="1.75" fill="currentColor"/>
                            <circle cx="18" cy="20" r="1.75" fill="currentColor"/>
                        </svg>
                    </span>
                    <span data-sticky-button-text><?php esc_html_e('Add to cart', 'kangoo'); ?></span>
                </button>

                <div class="sticky-add__message" data-sticky-message hidden></div>
            </div>
        </div>
    </div>
</main>

<?php if (!empty($product_faq_schema)) : ?>
    <script type="application/ld+json">
        <?php
        echo wp_json_encode(
            array(
                '@context'   => 'https://schema.org',
                '@type'      => 'FAQPage',
                'mainEntity' => $product_faq_schema,
            ),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
        ?>
    </script>
<?php endif; ?>

<?php endwhile; ?>

<?php get_footer(); ?>
