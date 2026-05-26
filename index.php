<?php get_header(); ?>

<main class="section">
    <div class="container">
        <header class="section-header">
            <h1><?php bloginfo('name'); ?></h1>
            <p><?php bloginfo('description'); ?></p>
        </header>

        <?php if (have_posts()) : ?>
            <div class="stack">
                <?php while (have_posts()) : the_post(); ?>
                    <article class="card">
                        <h2 class="card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                        <div class="wysiwyg"><?php the_excerpt(); ?></div>
                    </article>
                <?php endwhile; ?>
            </div>
        <?php else : ?>
            <p>No content found.</p>
        <?php endif; ?>
    </div>
</main>

<?php get_footer(); ?>
