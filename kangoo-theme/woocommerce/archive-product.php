<?php
defined('ABSPATH') || exit;
get_header();
?>

<main class="section">

  <div class="container">

    <!-- HEADER -->
    <header class="category-header">

      <h1><?php woocommerce_page_title(); ?></h1>

		<p class="category-intro">
		  <?php echo get_field('category_intro', 'product_cat_' . get_queried_object_id()); ?>
		</p>
		
		<?php
		$seo_content = get_field('category_seo_content', 'product_cat_' . get_queried_object_id());

		if ($seo_content):
		?>
		<section class="category-seo">
		  <div class="container container--narrow">
			<?php echo $seo_content; ?>
		  </div>
		</section>
		<?php endif; ?>

    </header>

    <!-- PRODUCTS -->
    <?php if (woocommerce_product_loop()) : ?>

      <div class="woo-grid">
        <?php while (have_posts()) : the_post(); ?>
          <?php wc_get_template_part('content', 'product'); ?>
        <?php endwhile; ?>
      </div>

    <?php endif; ?>

  </div>

</main>

<!-- SEO CONTENT (outside container for full width control later) -->
<section class="category-seo">
  <div class="container container--narrow">

    <h2>Nicotine pouches in the UK</h2>

    <p>
      Nicotine pouches are a tobacco-free alternative designed for discreet use.
      Our range includes options for beginners through to strong nicotine pouches.
    </p>

    <div class="category-seo__more">

      <p>
        Browse our full range of <a href="/nicotine-pouches/">nicotine pouches</a>,
        including mint, fruit and strong options. You can also explore our
        <a href="/sample-packs/">sample packs</a> to find your preference.
      </p>

    </div>

  </div>
</section>

<?php if (have_rows('category_faq', 'product_cat_' . get_queried_object_id())): ?>
<section class="faq">
  <div class="container container--narrow">
    <h2>Frequently asked questions</h2>

    <?php while (have_rows('category_faq', 'product_cat_' . get_queried_object_id())): the_row(); ?>
      <details>
        <summary><?php the_sub_field('question'); ?></summary>
        <p><?php the_sub_field('answer'); ?></p>
      </details>
    <?php endwhile; ?>

  </div>
</section>
<?php endif; ?>

<?php get_footer(); ?>