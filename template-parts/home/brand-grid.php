<?php
$show_section = get_sub_field('show_section');

if (!$show_section) {
    return;
}

$eyebrow    = get_sub_field('eyebrow');
$heading    = get_sub_field('heading');
$subheading = get_sub_field('subheading');
?>

<section class="section brand-grid-section">
    <div class="container">

        <?php if ($eyebrow || $heading || $subheading) : ?>
            <header class="section-header">
                <?php if ($eyebrow) : ?>
                    <span class="eyebrow"><?php echo esc_html($eyebrow); ?></span>
                <?php endif; ?>

                <?php if ($heading) : ?>
                    <h2><?php echo esc_html($heading); ?></h2>
                <?php endif; ?>

                <?php if ($subheading) : ?>
                    <p><?php echo esc_html($subheading); ?></p>
                <?php endif; ?>
            </header>
        <?php endif; ?>

		<?php if (have_rows('brands_cards')) : ?>
			<div class="brand-grid">
				<?php while (have_rows('brands_cards')) : the_row(); ?>
					<?php
					$brand_name  = get_sub_field('brand_name');
					$brand_image = get_sub_field('brand_image');
					$brand_link  = get_sub_field('brand_link');

					$url = !empty($brand_link['url']) ? $brand_link['url'] : '#';
					$target = !empty($brand_link['target']) ? $brand_link['target'] : '_self';

					$image_url = '';

					if (is_array($brand_image) && !empty($brand_image['url'])) {
						$image_url = $brand_image['url'];
					} elseif (is_numeric($brand_image)) {
						$image_url = wp_get_attachment_image_url($brand_image, 'medium');
					} elseif (is_string($brand_image)) {
						$image_url = $brand_image;
					}
					?>

					<a class="brand-card" href="<?php echo esc_url($url); ?>" target="<?php echo esc_attr($target); ?>">
						<?php if ($image_url) : ?>
							<div class="brand-card__image">
								<img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($brand_name . ' nicotine pouches'); ?>" loading="lazy">
							</div>
						<?php endif; ?>

						<?php if ($brand_name) : ?>
							<span class="brand-card__title"><?php echo esc_html($brand_name); ?></span>
						<?php endif; ?>
					</a>
				<?php endwhile; ?>
			</div>
		<?php endif; ?>

    </div>
</section>