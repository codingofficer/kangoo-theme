<?php
defined('ABSPATH') || exit;

$cta_primary_link = get_sub_field('cta_primary_link');
$cta_primary_text = get_sub_field('cta_primary_text');
$cta_secondary_link = get_sub_field('cta_secondary_link');
$cta_secondary_text = get_sub_field('cta_secondary_text');
$finder_url = function_exists('kangoo_get_page_url_by_template') ? kangoo_get_page_url_by_template('page-templates/template-pouch-finder.php', '/pouch-finder/') : home_url('/pouch-finder/');
$compare_url = function_exists('kangoo_get_page_url_by_template') ? kangoo_get_page_url_by_template('page-templates/template-pouch-comparison.php', '/compare-pouches/') : home_url('/compare-pouches/');
$hero_products = array();

if (function_exists('wc_get_products')) {
    $hero_products = wc_get_products(array(
        'status'       => 'publish',
        'stock_status' => 'instock',
        'limit'        => 3,
        'orderby'      => 'popularity',
        'order'        => 'DESC',
        'return'       => 'objects',
    ));

    $hero_products = array_values(array_filter($hero_products, function ($hero_product) {
        return $hero_product instanceof WC_Product && $hero_product->get_image_id();
    }));
}
?>

<section class="hero section section--hero">
    <div class="container">
        <div class="hero__grid">

            <div class="hero__content">
                <span class="eyebrow"><?php the_sub_field('eyebrow'); ?></span>

                <h1><?php the_sub_field('heading'); ?></h1>

                <p><?php the_sub_field('subheading'); ?></p>

                <?php if (!empty($hero_products)) : ?>
                    <div class="hero__product-peek" aria-label="<?php esc_attr_e('Popular pouch products', 'kangoo'); ?>">
                        <span class="hero__product-peek-label"><?php esc_html_e('Popular now', 'kangoo'); ?></span>

                        <div class="hero__product-peek-stack">
                            <?php foreach ($hero_products as $index => $hero_product) : ?>
                                <?php
                                $hero_image_id = $hero_product->get_image_id();
                                ?>
                                <a
                                    href="<?php echo esc_url($hero_product->get_permalink()); ?>"
                                    class="hero__product-peek-item"
                                    aria-label="<?php echo esc_attr($hero_product->get_name()); ?>"
                                    style="--hero-product-offset: -<?php echo esc_attr((string) (14 * $index)); ?>px; --hero-product-offset-mobile: -<?php echo esc_attr((string) (10 * $index)); ?>px;"
                                >
                                    <?php
                                    echo wp_get_attachment_image(
                                        $hero_image_id,
                                        'woocommerce_thumbnail',
                                        false,
                                        array(
                                            'loading'  => 'eager',
                                            'decoding' => 'async',
                                        )
                                    );
                                    ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="hero__cta">
                    <?php if ($cta_primary_link && $cta_primary_text) : ?>
                        <a href="<?php echo esc_url($cta_primary_link); ?>" class="btn btn--primary">
                            <?php echo esc_html($cta_primary_text); ?>
                        </a>
                    <?php endif; ?>

                    <?php if ($cta_secondary_link && $cta_secondary_text) : ?>
                        <a href="<?php echo esc_url($cta_secondary_link); ?>" class="btn btn--secondary">
                            <?php echo esc_html($cta_secondary_text); ?>
                        </a>
                    <?php endif; ?>
                </div>

                <div class="hero__tools" aria-label="<?php esc_attr_e('Popular buying tools', 'kangoo'); ?>">
                    <a href="<?php echo esc_url($finder_url); ?>"><?php esc_html_e('Use pouch finder', 'kangoo'); ?></a>
                    <a href="<?php echo esc_url($compare_url); ?>"><?php esc_html_e('Compare pouches', 'kangoo'); ?></a>
                </div>

                <?php if (have_rows('trust_items')): ?>
                <ul class="hero__trust">
                    <?php while (have_rows('trust_items')): the_row(); ?>
                        <li><?php the_sub_field('text'); ?></li>
                    <?php endwhile; ?>
                </ul>
                <?php endif; ?>

            </div>

            <div class="hero__media">
                <?php $img = get_sub_field('hero_image'); ?>
                <?php if ($img): ?>
                    <img src="<?php echo $img['url']; ?>" alt="<?php echo $img['alt']; ?>">
                <?php endif; ?>
            </div>

        </div>
    </div>
</section>
