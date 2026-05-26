<section class="section faq">
    <div class="container container--narrow">

        <header class="section-header section-header--left">
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

        <div class="faq-list">
            <?php if (have_rows('faqs')) : ?>
                <?php while (have_rows('faqs')) : the_row(); ?>
                    <details>
                        <summary><?php the_sub_field('question'); ?></summary>
                        <div class="faq-answer">
                            <?php the_sub_field('answer'); ?>
                        </div>
                    </details>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>

    </div>
</section>