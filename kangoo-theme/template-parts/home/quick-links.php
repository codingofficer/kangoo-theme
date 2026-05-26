<section class="section quick-shop">
  <div class="container">

    <div class="grid grid--4 quick-shop__grid">

      <?php if (have_rows('links')): ?>
        <?php while (have_rows('links')): the_row(); 
          $image = get_sub_field('image');
        ?>

		<a href="<?php the_sub_field('link'); ?>" 
		   class="quick-card quick-card--<?php echo esc_attr(get_sub_field('style') ?: 'default'); ?>">

            <div class="quick-card__media">
              <?php if ($image): ?>
                <img src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt']); ?>">
              <?php endif; ?>
            </div>

            <div class="quick-card__overlay"></div>

            <div class="quick-card__content">
              <h2><?php the_sub_field('title'); ?></h2>
            </div>

          </a>

        <?php endwhile; ?>
      <?php endif; ?>

    </div>

  </div>
</section>