<section class="section why">
    <div class="container">

        <header class="section-header">
            <?php if (get_sub_field('eyebrow')) : ?>
                <span class="eyebrow"><?php the_sub_field('eyebrow'); ?></span>
            <?php endif; ?>

            <?php if (get_sub_field('heading')) : ?>
                <h2><?php the_sub_field('heading'); ?></h2>
            <?php endif; ?>

            <?php if (get_sub_field('subheading')) : ?>
                <p><?php the_sub_field('subheading'); ?></p>
            <?php endif; ?>
        </header>

        <?php if (have_rows('items')) : ?>
            <div class="grid grid--4">
                <?php while (have_rows('items')) : the_row(); ?>
                    <div class="why-item">
                        <h3><?php the_sub_field('text'); ?></h3>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

    </div>
</section>