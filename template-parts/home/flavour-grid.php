<?php
/**
 * Homepage flavour grid.
 */

$show_section = get_sub_field('show_section');

if ($show_section === false || $show_section === '0' || $show_section === 0) {
    return;
}

$eyebrow    = get_sub_field('eyebrow');
$heading    = get_sub_field('heading');
$subheading = get_sub_field('subheading');
?>

<section class="section flavour-grid-section">
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

        <?php if (have_rows('flavour_cards')) : ?>
            <div class="taxonomy-slider" data-taxonomy-slider>
                <div class="taxonomy-slider__controls" aria-label="<?php esc_attr_e('Flavour slider controls', 'kangoo'); ?>">
                    <button type="button" class="taxonomy-slider__arrow" data-taxonomy-slider-prev aria-label="<?php esc_attr_e('Previous flavours', 'kangoo'); ?>">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 5-7 7 7 7"/></svg>
                    </button>
                    <button type="button" class="taxonomy-slider__arrow" data-taxonomy-slider-next aria-label="<?php esc_attr_e('Next flavours', 'kangoo'); ?>">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg>
                    </button>
                </div>
            <div class="flavour-grid taxonomy-slider__track" data-taxonomy-slider-track tabindex="0">
                <?php while (have_rows('flavour_cards')) : the_row(); ?>
                    <?php
                    $term = kangoo_resolve_flavour_term(get_sub_field('flavour_term'));

                    if (!$term) {
                        continue;
                    }

                    $url = get_term_link($term);

                    if (is_wp_error($url)) {
                        continue;
                    }

                    $image_url = kangoo_get_flavour_term_image_url($term, 'thumbnail');
                    ?>
                    <a class="flavour-card" href="<?php echo esc_url($url); ?>">
                        <?php if ($image_url) : ?>
                            <div class="flavour-card__image">
                                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($term->name . ' nicotine pouches'); ?>" width="150" height="150" loading="lazy" decoding="async">
                            </div>
                        <?php endif; ?>

                        <span class="flavour-card__title"><?php echo esc_html($term->name); ?></span>
                    </a>
                <?php endwhile; ?>
            </div>
            </div>
        <?php endif; ?>
    </div>
</section>
