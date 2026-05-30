<?php
defined('ABSPATH') || exit;

$cta_primary_link = get_sub_field('cta_primary_link');
$cta_primary_text = get_sub_field('cta_primary_text');
$cta_secondary_link = get_sub_field('cta_secondary_link');
$cta_secondary_text = get_sub_field('cta_secondary_text');
$finder_url = function_exists('kangoo_get_page_url_by_template') ? kangoo_get_page_url_by_template('page-templates/template-pouch-finder.php', '/pouch-finder/') : home_url('/pouch-finder/');
$cta_secondary_link = $finder_url;
$cta_secondary_text = __('Nicotine Pouch Finder', 'kangoo');
?>

<section class="hero section section--hero">
    <div class="container">
        <div class="hero__grid">

            <div class="hero__content">
                <span class="eyebrow"><?php the_sub_field('eyebrow'); ?></span>

                <h1><?php the_sub_field('heading'); ?></h1>

                <p><?php the_sub_field('subheading'); ?></p>

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
