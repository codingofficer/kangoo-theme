<section class="section categories">
    <div class="container">

        <header class="section-header">
            <span class="eyebrow">Explore</span>
            <h2>Popular pouch categories</h2>
        </header>

        <ul class="category-links">
            <?php if (have_rows('links')): ?>
                <?php while (have_rows('links')): the_row(); ?>
                    <li>
                        <a href="<?php the_sub_field('url'); ?>">
                            <?php the_sub_field('text'); ?>
                        </a>
                    </li>
                <?php endwhile; ?>
            <?php endif; ?>
        </ul>

    </div>
</section>