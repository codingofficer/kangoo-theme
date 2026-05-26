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
                </div>

                <div class="product-page__info">
                    <h1 class="product-title"><?php the_title(); ?></h1>

                    <p class="product-subtitle">
                        <?php echo get_the_excerpt(); ?>
                    </p>

                    <?php if (have_rows('highlights')) : ?>
                        <ul class="product-highlights">
                            <?php while (have_rows('highlights')) : the_row(); ?>
                                <li><?php the_sub_field('text'); ?></li>
                            <?php endwhile; ?>
                        </ul>
                    <?php endif; ?>

					<div
						class="product-price"
						id="product-price"
						data-product-price="<?php echo esc_attr($product->get_price()); ?>"
					>
						<?php echo $product->get_price_html(); ?>
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

                    <div class="product-cart">
                        <?php woocommerce_template_single_add_to_cart(); ?>
                    </div>

                    <ul class="product-trust">
                        <li>Fast UK Delivery</li>
                        <li>Discreet Packaging</li>
                        <li>18+ Only</li>
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

            <?php if (get_field('delivery_info')) : ?>
                <details class="product-accordion__item">
                    <summary>Delivery</summary>
                    <div class="product-accordion__content wysiwyg">
                        <?php the_field('delivery_info'); ?>
                    </div>
                </details>
            <?php endif; ?>

            <?php if (get_field('how_to_use')) : ?>
                <details class="product-accordion__item">
                    <summary>How to use</summary>
                    <div class="product-accordion__content wysiwyg">
                        <?php the_field('how_to_use'); ?>
                    </div>
                </details>
            <?php endif; ?>

            <?php if (have_rows('product_faqs')) : ?>
                <details class="product-accordion__item">
                    <summary>Frequently asked questions</summary>
                    <div class="product-accordion__content">
                        <div class="faq-list">
                            <?php while (have_rows('product_faqs')) : the_row(); ?>
                                <details>
                                    <summary><?php the_sub_field('question'); ?></summary>
                                    <p><?php the_sub_field('answer'); ?></p>
                                </details>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </details>
            <?php endif; ?>
        </div>

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

    <div class="sticky-add">
        <div class="container">
            <button id="sticky-add-btn" class="btn btn--primary" style="width:100%;">
                Add to cart
            </button>
        </div>
    </div>
</main>

<?php endwhile; ?>

<?php get_footer(); ?>