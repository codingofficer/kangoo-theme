<section class="hero section section--hero">
    <div class="container">
        <div class="hero__grid">

            <div class="hero__content">
                <span class="eyebrow"><?php the_sub_field('eyebrow'); ?></span>

                <h1><?php the_sub_field('heading'); ?></h1>

                <p><?php the_sub_field('subheading'); ?></p>

                <div class="hero__cta">
                    <a href="<?php the_sub_field('cta_primary_link'); ?>" class="btn btn--primary">
                        <?php the_sub_field('cta_primary_text'); ?>
                    </a>

                    <a href="<?php the_sub_field('cta_secondary_link'); ?>" class="btn btn--secondary">
                        <?php the_sub_field('cta_secondary_text'); ?>
                    </a>
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