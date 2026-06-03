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
					$brand_card  = function_exists('kangoo_resolve_brand_category_card')
						? kangoo_resolve_brand_category_card($brand_name, $brand_name, $brand_image, $brand_link)
						: array(
							'label'     => $brand_name,
							'url'       => !empty($brand_link['url']) ? $brand_link['url'] : '#',
							'target'    => !empty($brand_link['target']) ? $brand_link['target'] : '_self',
							'image_url' => is_array($brand_image) && !empty($brand_image['url']) ? $brand_image['url'] : '',
						);

					$brand_name = isset($brand_card['label']) ? (string) $brand_card['label'] : '';
					$url = !empty($brand_card['url']) ? (string) $brand_card['url'] : '#';
					$target = !empty($brand_card['target']) ? (string) $brand_card['target'] : '_self';
					$image_url = !empty($brand_card['image_url']) ? (string) $brand_card['image_url'] : '';
					?>

					<a class="brand-card" href="<?php echo esc_url($url); ?>" target="<?php echo esc_attr($target); ?>">
						<?php if ($image_url) : ?>
							<div class="brand-card__image">
								<?php if ($brand_image) : ?>
									<?php
									echo kangoo_render_acf_image($brand_image, 'woocommerce_thumbnail', array(
										'alt' => $brand_name . ' nicotine pouches',
										'loading' => 'lazy',
										'sizes' => '(max-width: 640px) 44vw, 180px',
									));
									?>
								<?php else : ?>
									<img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($brand_name . ' nicotine pouches'); ?>" loading="lazy" decoding="async" width="300" height="300">
								<?php endif; ?>
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
