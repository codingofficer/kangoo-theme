<section class="section faq">
    <div class="container container--narrow">

        <header class="section-header section-header--left">
            <span class="eyebrow">FAQ</span>
            <h2><?php the_sub_field('heading'); ?></h2>
        </header>

        <div class="faq-list">
            <?php if (have_rows('faqs')): ?>
                <?php while (have_rows('faqs')): the_row(); ?>
                    <details>
                        <summary><?php the_sub_field('question'); ?></summary>
                        <p><?php the_sub_field('answer'); ?></p>
                    </details>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>

    </div>
</section>