<?php get_header(); ?>

<main class="blog-archive">
    <section class="blog-hero">
        <div class="container">
            <div class="blog-hero__content">
                <span class="eyebrow"><?php esc_html_e('Kangoo Blog', 'kangoo'); ?></span>
                <h1><?php esc_html_e('Guides, product know-how and smarter buying advice', 'kangoo'); ?></h1>
                <p><?php esc_html_e('Clear, search-friendly articles built to help shoppers compare products, understand strengths, and choose with confidence.', 'kangoo'); ?></p>
            </div>
        </div>
    </section>

    <?php if (have_posts()) : ?>
        <section class="blog-listing section">
            <div class="container">
                <div class="blog-grid">
                    <?php while (have_posts()) : the_post(); ?>
                        <?php
                        $standfirst = kangoo_blog_get_field('blog_standfirst');
                        $topics = get_the_terms(get_the_ID(), 'blog_topic');
                        $primary_topic = !empty($topics) && !is_wp_error($topics) ? $topics[0] : null;
                        ?>
                        <article class="blog-card">
                            <a class="blog-card__media" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr(get_the_title()); ?>">
                                <?php echo kangoo_blog_featured_image_html(get_the_ID(), 'large'); ?>
                            </a>

                            <div class="blog-card__body">
                                <div class="blog-card__meta">
                                    <?php if ($primary_topic) : ?>
                                        <a href="<?php echo esc_url(get_term_link($primary_topic)); ?>"><?php echo esc_html($primary_topic->name); ?></a>
                                    <?php else : ?>
                                        <span><?php esc_html_e('Guide', 'kangoo'); ?></span>
                                    <?php endif; ?>
                                    <span><?php echo esc_html(kangoo_blog_estimated_read_time()); ?> <?php esc_html_e('min read', 'kangoo'); ?></span>
                                </div>

                                <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>

                                <p>
                                    <?php
                                    echo esc_html($standfirst ? $standfirst : wp_trim_words(get_the_excerpt(), 24));
                                    ?>
                                </p>
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
                <p><?php esc_html_e('No blog articles found yet.', 'kangoo'); ?></p>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php get_footer(); ?>
