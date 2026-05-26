<?php get_header(); ?>

<main class="section">
    <div class="container container--narrow">
        <?php while (have_posts()) : the_post(); ?>
            <article class="entry">
                <header class="section-header section-header--left">
                    <h1><?php the_title(); ?></h1>
                </header>
                <div class="wysiwyg"><?php the_content(); ?></div>
            </article>
        <?php endwhile; ?>
    </div>
</main>

<?php get_footer(); ?>
