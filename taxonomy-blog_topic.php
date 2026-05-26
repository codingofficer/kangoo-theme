<?php get_header(); ?>

<main class="blog-archive">
    <section class="blog-hero">
        <div class="container">
            <div class="blog-hero__content">
                <span class="eyebrow"><?php esc_html_e('Blog Topic', 'kangoo'); ?></span>
                <h1><?php single_term_title(); ?></h1>
                <?php if (term_description()) : ?>
                    <div class="archive-description"><?php echo wp_kses_post(term_description()); ?></div>
                <?php else : ?>
                    <p><?php esc_html_e('Helpful articles, comparisons and buying advice from the Kangoo team.', 'kangoo'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php if (have_posts()) : ?>
        <section class="blog-listing section">
            <div class="container">
                <div class="blog-grid">
                    <?php while (have_posts()) : the_post(); ?>
                        <?php $standfirst = kangoo_blog_get_field('blog_standfirst'); ?>
                        <article class="blog-card">
                            <a class="blog-card__media" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr(get_the_title()); ?>">
                                <?php echo kangoo_blog_featured_image_html(get_the_ID(), 'large'); ?>
                            </a>

                            <div class="blog-card__body">
                                <div class="blog-card__meta">
                                    <span><?php echo esc_html(get_the_date()); ?></span>
                                    <span><?php echo esc_html(kangoo_blog_estimated_read_time()); ?> <?php esc_html_e('min read', 'kangoo'); ?></span>
                                </div>

                                <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                                <p><?php echo esc_html($standfirst ? $standfirst : wp_trim_words(get_the_excerpt(), 24)); ?></p>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>

                <div class="blog-pagination">
                    <?php
                    the_posts_pagination(array(
                        'mid_size'  => 1,
                        'prev_text' => __('Newer articles', 'kangoo'),
                        'next_text' => __('Older articles', 'kangoo'),
                    ));
                    ?>
                </div>
            </div>
        </section>
    <?php else : ?>
        <section class="section">
            <div class="container">
                <p><?php esc_html_e('No articles found for this topic yet.', 'kangoo'); ?></p>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php get_footer(); ?>
