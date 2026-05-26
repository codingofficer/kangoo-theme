<?php get_header(); ?>

<main id="site-main">

<?php $kangoo_home_proof_rendered = false; ?>

<?php if (have_rows('homepage_sections')): ?>
    <?php while (have_rows('homepage_sections')): the_row(); ?>

        <?php
        $layout = get_row_layout();
        $layouts_without_visibility_toggle = array(
            'rewards_cta',
            'rewards',
            'app_download_cta',
            'app_download',
            'android_app_cta',
            'pouch_finder_cta',
            'pouch_finder',
            'finder_cta',
        );

        if (!in_array($layout, $layouts_without_visibility_toggle, true)) {
            $show_section = get_sub_field('show_section');

            if ($show_section === false || $show_section === '0' || $show_section === 0) {
                continue;
            }
        }
        ?>

        <?php if ($layout == 'hero'): ?>
            <?php get_template_part('template-parts/home/hero'); ?>
            <?php if (!$kangoo_home_proof_rendered) : ?>
                <?php get_template_part('template-parts/home/seo-proof-strip'); ?>
                <?php $kangoo_home_proof_rendered = true; ?>
            <?php endif; ?>

        <?php elseif ($layout == 'quick_links'): ?>
            <?php get_template_part('template-parts/home/quick-links'); ?>

        <?php elseif (in_array($layout, array('rewards_cta', 'rewards'), true)): ?>
            <?php get_template_part('template-parts/home/rewards-cta'); ?>

        <?php elseif (in_array($layout, array('app_download_cta', 'app_download', 'android_app_cta'), true)): ?>
            <?php get_template_part('template-parts/home/app-download-cta'); ?>

        <?php elseif (in_array($layout, array('pouch_finder_cta', 'pouch_finder', 'finder_cta'), true)): ?>
            <?php get_template_part('template-parts/home/pouch-finder-cta'); ?>

        <?php elseif ($layout == 'product_section'): ?>
            <?php get_template_part('template-parts/home/product-section'); ?>

        <?php elseif ($layout == 'seo_content'): ?>
            <?php get_template_part('template-parts/home/seo'); ?>

        <?php elseif ($layout == 'category_links'): ?>
            <?php get_template_part('template-parts/home/categories'); ?>

        <?php elseif ($layout == 'why'): ?>
            <?php get_template_part('template-parts/home/why'); ?>

        <?php elseif ($layout == 'faq'): ?>
            <?php get_template_part('template-parts/home/faq'); ?>

        <?php elseif ($layout == 'auto_products'): ?>
            <?php get_template_part('template-parts/home/auto-products'); ?>
	
        <?php elseif ($layout == 'brand_grid'): ?>
            <?php get_template_part('template-parts/home/brand-grid'); ?>

        <?php endif; ?>

    <?php endwhile; ?>
<?php endif; ?>

<?php if (!$kangoo_home_proof_rendered) : ?>
    <?php get_template_part('template-parts/home/seo-proof-strip'); ?>
<?php endif; ?>

</main>

<?php get_footer(); ?>
