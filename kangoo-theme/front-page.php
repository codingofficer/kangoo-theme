<?php get_header(); ?>

<main id="site-main">

<?php if (have_rows('homepage_sections')): ?>
    <?php while (have_rows('homepage_sections')): the_row(); ?>

        <?php if (get_row_layout() == 'hero'): ?>
            <?php get_template_part('template-parts/home/hero'); ?>

        <?php elseif (get_row_layout() == 'quick_links'): ?>
            <?php get_template_part('template-parts/home/quick-links'); ?>

        <?php elseif (get_row_layout() == 'product_section'): ?>
            <?php get_template_part('template-parts/home/product-section'); ?>

        <?php elseif (get_row_layout() == 'seo_content'): ?>
            <?php get_template_part('template-parts/home/seo'); ?>

        <?php elseif (get_row_layout() == 'category_links'): ?>
            <?php get_template_part('template-parts/home/categories'); ?>

        <?php elseif (get_row_layout() == 'why'): ?>
            <?php get_template_part('template-parts/home/why'); ?>

        <?php elseif (get_row_layout() == 'faq'): ?>
            <?php get_template_part('template-parts/home/faq'); ?>

		<?php elseif (get_row_layout() == 'auto_products'): ?>
			<?php get_template_part('template-parts/home/auto-products'); ?>
	
        <?php endif; ?>

    <?php endwhile; ?>
<?php endif; ?>

</main>

<?php get_footer(); ?>