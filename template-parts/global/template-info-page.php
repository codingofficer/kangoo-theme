<?php
/**
 * Template Name: Info Page
 */
get_header();
?>

<main id="site-main" class="info-page">
    <?php while (have_posts()) : the_post(); ?>
        <section class="info-page__hero section">
            <div class="container container--narrow">
                <div class="info-page__hero-inner">
                    <span class="eyebrow"><?php echo esc_html(get_bloginfo('name')); ?></span>
                    <h1 class="info-page__title"><?php the_title(); ?></h1>

                    <?php if (has_excerpt()) : ?>
                        <p class="info-page__intro"><?php echo esc_html(get_the_excerpt()); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="info-page__content-wrap">
            <div class="container container--narrow">
                <article <?php post_class('info-page__article'); ?>>
                    <div class="info-page__content wysiwyg">
                        <?php the_content(); ?>
                    </div>
                </article>
            </div>
        </section>
    <?php endwhile; ?>
</main>

<?php get_footer(); ?>