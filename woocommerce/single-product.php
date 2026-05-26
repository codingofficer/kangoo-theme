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
                    </div>

                    <?php if ($main_image_id || !empty($gallery_image_ids)) : ?>
                        <div class="product-thumbs">
                            <?php if ($main_image_id) : ?>
                                <button
                                    type="button"
                                    class="product-thumb is-active"
                                    data-image="<?php echo esc_url($main_image_url); ?>"
                                    data-alt="<?php echo esc_attr($main_image_alt ?: get_the_title()); ?>"
                                >
                                    <?php echo wp_get_attachment_image($main_image_id, 'thumbnail'); ?>
                                </button>
                            <?php endif; ?>

                            <?php foreach ($gallery_image_ids as $gallery_image_id) :
                                $thumb_large = wp_get_attachment_image_url($gallery_image_id, 'large');
                                $thumb_alt   = get_post_meta($gallery_image_id, '_wp_attachment_image_alt', true);
                            ?>
                                <button
                                    type="button"
                                    class="product-thumb"
                                    data-image="<?php echo esc_url($thumb_large); ?>"
                                    data-alt="<?php echo esc_attr($thumb_alt ?: get_the_title()); ?>"
                                >
                                    <?php echo wp_get_attachment_image($gallery_image_id, 'thumbnail'); ?>
                                </button>
                            <?php endforeach; ?>
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

						<?php if (!$product->is_in_stock()) : ?>
							<span class="product-title__badge product-title__badge--out-of-stock">
								<?php esc_html_e('Sold Out', 'kangoo'); ?>
							</span>
						<?php endif; ?>

						<?php
						$strength_mg = get_field('strength_mg');
						if ($strength_mg) :
							$label = strtoupper($strength_mg);
							if (strpos($label, 'MG') === false) $label .= 'MG';
						?>
							<span class="product-title__badge">
								<?php echo esc_html($label); ?>
							</span>
						<?php endif; ?>
					</h1>

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
                    $suppress_stock_note = function_exists('kangoo_is_99p_product') && kangoo_is_99p_product($product->get_id());
                    ?>

                    <div class="product-stock-note<?php echo $low_stock_message ? ' product-stock-note--low' : ''; ?>" data-product-stock-note data-suppress-stock-note="<?php echo $suppress_stock_note ? '1' : '0'; ?>"<?php echo $low_stock_message ? '' : ' hidden'; ?>>
                        <?php echo esc_html($low_stock_message); ?>
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
						<div class="product-cart">
							<button class="single_add_to_cart_button button alt is-disabled" disabled>
								<span class="button-text">SOLD OUT</span>
							</button>
						</div>
					<?php else : ?>
						<div class="product-cart">
							<?php woocommerce_template_single_add_to_cart(); ?>
						</div>
					<?php endif; ?>

                    <ul class="product-trust">
                        <li>Free UK Delivery over &pound;14.95</li>
                        <li>Discreet Packaging</li>
                        <li>18+ Only</li>
                        <li>Earn Kangoo Rewards</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="product-accordion">
            <?php if (get_the_content()) : ?>
                <details class="product-accordion__item" open>
                    <summary>Description</summary>
                    <div class="product-accordion__content wysiwyg">
                        <?php the_content(); ?>
                    </div>
                </details>
            <?php endif; ?>

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

    <div class="sticky-add" data-sticky-add>
        <div class="container">
            <div class="sticky-add__bar">
                <label class="sticky-add__pack">
                    <select data-sticky-pack-select aria-label="<?php esc_attr_e('Choose pack size', 'kangoo'); ?>">
                        <option value="1"><?php esc_html_e('1 pack', 'kangoo'); ?></option>
                    </select>
                </label>

                <div class="sticky-add__summary" data-sticky-summary>
                    <span class="sticky-add__unit" data-sticky-unit-price></span>
                    <span class="sticky-add__saving" data-sticky-saving></span>
                    <span class="sticky-add__meta" data-sticky-meta hidden>
                        <?php esc_html_e('1 pack', 'kangoo'); ?>
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
