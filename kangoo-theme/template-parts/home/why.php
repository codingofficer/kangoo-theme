<section class="section why">
    <div class="container">

        <header class="section-header">
            <span class="eyebrow">Why Kango</span>
            <h2><?php the_sub_field('heading'); ?></h2>
        </header>

        <div class="grid grid--4">
            <?php if (have_rows('items')): ?>
                <?php while (have_rows('items')): the_row(); ?>
                    <div class="why-item">
                        <h3><?php the_sub_field('text'); ?></h3>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>

    </div>
</section>