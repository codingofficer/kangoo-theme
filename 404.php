<?php
defined('ABSPATH') || exit;

get_header();

$featured_products = array();

if (function_exists('wc_get_products')) {
    $featured_products = wc_get_products(array(
        'status' => 'publish',
        'limit' => 6,
        'orderby' => 'date',
        'order' => 'DESC',
        'return' => 'objects',
    ));
}
?>

<main class="section not-found-page">
    <div class="container">
        <section class="not-found-hero">
            <span class="eyebrow"><?php esc_html_e('404', 'kangoo'); ?></span>
            <h1><?php esc_html_e('Page not found', 'kangoo'); ?></h1>
            <p><?php esc_html_e('The page may have moved, but you can still find nicotine pouches, caffeine pouches and buying guides quickly.', 'kangoo'); ?></p>

            <div class="not-found-hero__actions">
                <button type="button" class="btn btn--primary" data-search-open>
                    <?php esc_html_e('Search Kangoo', 'kangoo'); ?>
                </button>
                <a class="btn btn--secondary" href="<?php echo esc_url(home_url('/product-category/nicotine-pouches/')); ?>">
                    <?php esc_html_e('Shop nicotine pouches', 'kangoo'); ?>
                </a>
            </div>

            <div class="not-found-links" aria-label="<?php esc_attr_e('Helpful links', 'kangoo'); ?>">
                <a href="<?php echo esc_url(home_url('/product-category/nicotine-pouches/')); ?>"><?php esc_html_e('Nicotine pouches', 'kangoo'); ?></a>
                <a href="<?php echo esc_url(home_url('/product-category/zyn/')); ?>"><?php esc_html_e('ZYN', 'kangoo'); ?></a>
                <a href="<?php echo esc_url(home_url('/product-category/velo/')); ?>"><?php esc_html_e('VELO', 'kangoo'); ?></a>
                <a href="<?php echo esc_url(home_url('/blog/')); ?>"><?php esc_html_e('Guides', 'kangoo'); ?></a>
            </div>
        </section>

        <?php if (!empty($featured_products)) : ?>
            <section class="not-found-products">
                <header class="section-header section-header--left">
                    <span class="eyebrow"><?php esc_html_e('Keep browsing', 'kangoo'); ?></span>
                    <h2><?php esc_html_e('Latest products', 'kangoo'); ?></h2>
                </header>

                <div class="woo-grid">
                    <?php
                    foreach ($featured_products as $product) :
                        $post_object = get_post($product->get_id());

                        if (!$post_object) {
                            continue;
                        }

                        $GLOBALS['post'] = $post_object;
                        $GLOBALS['product'] = $product;
                        setup_postdata($post_object);
                        wc_get_template_part('content', 'product');
                    endforeach;
                    wp_reset_postdata();
                    unset($GLOBALS['product']);
                    ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
</main>

<?php get_footer(); ?>
