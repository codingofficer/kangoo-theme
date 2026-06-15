<?php get_header(); ?>

<?php while (have_posts()) : the_post(); ?>
    <?php
    $standfirst = kangoo_blog_get_field('blog_standfirst');
    $eyebrow = kangoo_blog_get_field('blog_eyebrow', get_the_ID(), __('Guide', 'kangoo'));
    $read_time = kangoo_blog_estimated_read_time();
    $topics = get_the_terms(get_the_ID(), 'blog_topic');
    $featured_product = kangoo_blog_get_field('blog_featured_product');
    $reviewer = trim((string) kangoo_blog_get_field('blog_reviewer'));
    $reviewer_credentials = trim((string) kangoo_blog_get_field('blog_reviewer_credentials'));
    $sources = kangoo_blog_get_field('blog_sources', get_the_ID(), array());
    $author_name = get_the_author_meta('display_name') ?: get_bloginfo('name');
    $was_updated = get_the_modified_time('U') > get_the_time('U') + DAY_IN_SECONDS;
    $article_content = apply_filters('the_content', get_the_content());
    $article_content = preg_replace('/(<table\b[^>]*>.*?<\/table>)/is', '<div class="blog-table-scroll" tabindex="0">$1</div>', $article_content);
    ?>

    <main class="blog-single">
        <article <?php post_class('blog-article'); ?>>
            <header class="blog-article__hero">
                <div class="container container--narrow">
                    <nav class="blog-article__breadcrumbs" aria-label="<?php esc_attr_e('Breadcrumb', 'kangoo'); ?>">
                        <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'kangoo'); ?></a>
                        <span aria-hidden="true">/</span>
                        <a href="<?php echo esc_url(get_post_type_archive_link('kangoo_blog')); ?>"><?php esc_html_e('Blog', 'kangoo'); ?></a>
                    </nav>
                    <span class="eyebrow"><?php echo esc_html($eyebrow); ?></span>
                    <h1><?php the_title(); ?></h1>

                    <?php if ($standfirst) : ?>
                        <p class="blog-article__standfirst"><?php echo esc_html($standfirst); ?></p>
                    <?php endif; ?>

                    <div class="blog-article__meta">
                        <span><?php echo esc_html(get_the_date()); ?></span>
                        <span><?php echo esc_html(sprintf(__('By %s', 'kangoo'), $author_name)); ?></span>
                        <?php if ($was_updated) : ?>
                            <span><?php echo esc_html(sprintf(__('Updated %s', 'kangoo'), get_the_modified_date())); ?></span>
                        <?php endif; ?>
                        <span><?php echo esc_html($read_time); ?> <?php esc_html_e('min read', 'kangoo'); ?></span>
                        <?php if (!empty($topics) && !is_wp_error($topics)) : ?>
                            <span>
                                <?php
                                echo esc_html(implode(', ', wp_list_pluck($topics, 'name')));
                                ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </header>

            <div class="container">
                <figure class="blog-article__image">
                    <?php echo kangoo_blog_featured_image_html(get_the_ID(), 'full'); ?>
                </figure>
            </div>

            <div class="blog-article__layout container">
                <div class="blog-article__content wysiwyg">
                    <?php echo $article_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

                    <?php if (is_array($sources) && !empty($sources)) : ?>
                        <section class="blog-article__sources" aria-labelledby="blog-sources-title">
                            <h2 id="blog-sources-title"><?php esc_html_e('Sources', 'kangoo'); ?></h2>
                            <ul>
                                <?php foreach ($sources as $source) : ?>
                                    <?php if (!empty($source['label']) && !empty($source['url'])) : ?>
                                        <li><a href="<?php echo esc_url($source['url']); ?>" rel="noopener noreferrer"><?php echo esc_html($source['label']); ?></a></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        </section>
                    <?php endif; ?>
                </div>

                <aside class="blog-article__aside" aria-label="<?php esc_attr_e('Article details', 'kangoo'); ?>">
                    <div class="blog-aside-card">
                        <h2><?php esc_html_e('Article Details', 'kangoo'); ?></h2>
                        <dl>
                            <div>
                                <dt><?php esc_html_e('Published', 'kangoo'); ?></dt>
                                <dd><?php echo esc_html(get_the_date()); ?></dd>
                            </div>
                            <div>
                                <dt><?php esc_html_e('Author', 'kangoo'); ?></dt>
                                <dd><?php echo esc_html($author_name); ?></dd>
                            </div>
                            <?php if ($was_updated) : ?>
                                <div>
                                    <dt><?php esc_html_e('Last Updated', 'kangoo'); ?></dt>
                                    <dd><?php echo esc_html(get_the_modified_date()); ?></dd>
                                </div>
                            <?php endif; ?>
                            <?php if ($reviewer !== '') : ?>
                                <div>
                                    <dt><?php esc_html_e('Editorial Review', 'kangoo'); ?></dt>
                                    <dd>
                                        <?php echo esc_html($reviewer); ?>
                                        <?php if ($reviewer_credentials !== '') : ?>
                                            <small><?php echo esc_html($reviewer_credentials); ?></small>
                                        <?php endif; ?>
                                    </dd>
                                </div>
                            <?php endif; ?>
                            <div>
                                <dt><?php esc_html_e('Read Time', 'kangoo'); ?></dt>
                                <dd><?php echo esc_html($read_time); ?> <?php esc_html_e('minutes', 'kangoo'); ?></dd>
                            </div>
                            <?php if (!empty($topics) && !is_wp_error($topics)) : ?>
                                <div>
                                    <dt><?php esc_html_e('Topics', 'kangoo'); ?></dt>
                                    <dd>
                                        <?php foreach ($topics as $topic) : ?>
                                            <a href="<?php echo esc_url(get_term_link($topic)); ?>"><?php echo esc_html($topic->name); ?></a>
                                        <?php endforeach; ?>
                                    </dd>
                                </div>
                            <?php endif; ?>
                        </dl>
                    </div>

                    <?php if ($featured_product && function_exists('wc_get_product')) : ?>
                        <?php
                        $product_id = is_object($featured_product) ? $featured_product->ID : (int) $featured_product;
                        $product = wc_get_product($product_id);
                        ?>
                        <?php if ($product) : ?>
                            <div class="blog-aside-card blog-product-card">
                                <span><?php esc_html_e('Featured product', 'kangoo'); ?></span>
                                <h2><?php echo esc_html($product->get_name()); ?></h2>
                                <?php if (has_post_thumbnail($product_id)) : ?>
                                    <a href="<?php echo esc_url(get_permalink($product_id)); ?>">
                                        <?php echo get_the_post_thumbnail($product_id, 'medium'); ?>
                                    </a>
                                <?php endif; ?>
                                <a class="btn btn--primary" href="<?php echo esc_url(get_permalink($product_id)); ?>"><?php esc_html_e('View Product', 'kangoo'); ?></a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </aside>
            </div>

            <?php
            $related_guides = new WP_Query(array(
                'post_type'           => 'kangoo_blog',
                'post_status'         => 'publish',
                'posts_per_page'      => 6,
                'post__not_in'        => array(get_the_ID()),
                'orderby'             => 'date',
                'order'               => 'DESC',
                'ignore_sticky_posts' => true,
            ));
            ?>

            <?php if ($related_guides->have_posts()) : ?>
                <section class="blog-related section">
                    <div class="container">
                        <header class="section-header section-header--left">
                            <span class="eyebrow"><?php esc_html_e('Keep reading', 'kangoo'); ?></span>
                            <h2><?php esc_html_e('Related Guides', 'kangoo'); ?></h2>
                        </header>

                        <div class="blog-related__controls" aria-label="<?php esc_attr_e('Related guide controls', 'kangoo'); ?>">
                            <button type="button" class="blog-related__arrow" data-blog-related-prev aria-label="<?php esc_attr_e('Previous guides', 'kangoo'); ?>">&lsaquo;</button>
                            <button type="button" class="blog-related__arrow" data-blog-related-next aria-label="<?php esc_attr_e('Next guides', 'kangoo'); ?>">&rsaquo;</button>
                        </div>

                        <div class="blog-related__track" data-blog-related-track>
                            <?php while ($related_guides->have_posts()) : $related_guides->the_post(); ?>
                                <?php
                                $related_standfirst = kangoo_blog_get_field('blog_standfirst');
                                $related_topics = get_the_terms(get_the_ID(), 'blog_topic');
                                $related_primary_topic = !empty($related_topics) && !is_wp_error($related_topics) ? $related_topics[0] : null;
                                ?>
                                <article class="blog-card">
                                    <a class="blog-card__media" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr(get_the_title()); ?>">
                                        <?php echo kangoo_blog_featured_image_html(get_the_ID(), 'large'); ?>
                                    </a>

                                    <div class="blog-card__body">
                                        <div class="blog-card__meta">
                                            <?php if ($related_primary_topic) : ?>
                                                <a href="<?php echo esc_url(get_term_link($related_primary_topic)); ?>"><?php echo esc_html($related_primary_topic->name); ?></a>
                                            <?php else : ?>
                                                <span><?php esc_html_e('Guide', 'kangoo'); ?></span>
                                            <?php endif; ?>
                                            <span><?php echo esc_html(kangoo_blog_estimated_read_time()); ?> <?php esc_html_e('min read', 'kangoo'); ?></span>
                                        </div>

                                        <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                                        <p><?php echo esc_html($related_standfirst ? $related_standfirst : wp_trim_words(get_the_excerpt(), 24)); ?></p>
                                    </div>
                                </article>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </section>
                <?php wp_reset_postdata(); ?>
            <?php endif; ?>
        </article>
    </main>
<?php endwhile; ?>

<?php get_footer(); ?>
